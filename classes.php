<?php
require_once __DIR__ . '/includes/auth.php';
$user = require_role('admin', 'trainer', 'member');
verify_csrf();

$days = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];

/** Date of the next session: today if the class has not started yet, otherwise the next matching weekday. */
function next_date_for(string $day, string $startTime = '23:59:59'): string
{
    if (date('D') === $day && date('H:i:s') < $startTime) {
        return date('Y-m-d');
    }
    return date('Y-m-d', strtotime("next $day"));
}

$action = $_POST['action'] ?? '';
if ($action === 'create' && $user['role'] === 'admin') {
    q('INSERT INTO classes (name, trainer_id, day_of_week, start_time, duration_min, capacity, room) VALUES (?, ?, ?, ?, ?, ?, ?)',
      [post('name'), (int) post('trainer_id'), post('day_of_week'), post('start_time'), (int) post('duration_min'),
       (int) post('capacity'), post('room')]);
    flash('Class added to the weekly schedule.');
    redirect('classes.php');
}
if ($action === 'book' && $user['role'] === 'member') {
    $class = q('SELECT * FROM classes WHERE id = ?', [(int) post('class_id')])->fetch();
    $date = next_date_for($class['day_of_week'], $class['start_time']);
    $active = q("SELECT COUNT(*) FROM subscriptions WHERE member_id = ? AND status = 'active' AND ? BETWEEN start_date AND end_date",
                [$user['member_id'], $date])->fetchColumn();
    $taken = q("SELECT COUNT(*) FROM bookings WHERE class_id = ? AND class_date = ? AND status = 'booked'",
               [$class['id'], $date])->fetchColumn();
    if (!$active) {
        flash('You need an active subscription to book classes.', 'error');
    } elseif ($taken >= $class['capacity']) {
        flash('Sorry, this class is full.', 'error');
    } else {
        try {
            q("INSERT INTO bookings (class_id, member_id, class_date, status) VALUES (?, ?, ?, 'booked')
               ON DUPLICATE KEY UPDATE status = 'booked'", [$class['id'], $user['member_id'], $date]);
            flash("Booked {$class['name']} on $date.");
        } catch (PDOException $ex) {
            flash('Could not book this class.', 'error');
        }
    }
    redirect('classes.php');
}
if ($action === 'cancel' && $user['role'] === 'member') {
    q("UPDATE bookings SET status = 'cancelled' WHERE id = ? AND member_id = ?", [(int) post('booking_id'), $user['member_id']]);
    flash('Booking cancelled.');
    redirect('classes.php');
}

$where = $user['role'] === 'trainer' ? 'WHERE c.trainer_id = ' . (int) $user['trainer_id'] : '';
$classes = q("SELECT c.*, u.name AS trainer FROM classes c JOIN trainers t ON t.id = c.trainer_id
              JOIN users u ON u.id = t.user_id $where
              ORDER BY FIELD(c.day_of_week,'Mon','Tue','Wed','Thu','Fri','Sat','Sun'), c.start_time")->fetchAll();

foreach ($classes as &$c) {
    $c['next_date'] = next_date_for($c['day_of_week'], $c['start_time']);
    $c['booked'] = (int) q("SELECT COUNT(*) FROM bookings WHERE class_id = ? AND class_date = ? AND status = 'booked'",
                          [$c['id'], $c['next_date']])->fetchColumn();
    $c['my_booking'] = $user['role'] === 'member'
        ? q("SELECT id FROM bookings WHERE class_id = ? AND member_id = ? AND class_date = ? AND status = 'booked'",
            [$c['id'], $user['member_id'], $c['next_date']])->fetchColumn()
        : null;
}
unset($c);
$trainers = q('SELECT t.id, u.name FROM trainers t JOIN users u ON u.id = t.user_id ORDER BY u.name')->fetchAll();

$pageTitle = $user['role'] === 'member' ? 'Book a Class' : ($user['role'] === 'trainer' ? 'My Classes' : 'Class Schedule');
require __DIR__ . '/includes/header.php';
?>
<section class="card">
    <table>
        <tr><th>Day</th><th>Time</th><th>Class</th><th>Trainer</th><th>Room</th><th>Next session</th><th>Seats</th><th></th></tr>
        <?php foreach ($classes as $c): $left = $c['capacity'] - $c['booked']; ?>
            <tr>
                <td><?= e($c['day_of_week']) ?></td>
                <td><?= e(substr($c['start_time'], 0, 5)) ?> <small class="muted">(<?= (int) $c['duration_min'] ?> min)</small></td>
                <td><b><?= e($c['name']) ?></b></td>
                <td><?= e($c['trainer']) ?></td>
                <td><?= e($c['room']) ?></td>
                <td><?= e($c['next_date']) ?></td>
                <td><div class="seats"><i style="width: <?= round($c['booked'] / max(1, $c['capacity']) * 100) ?>%"></i></div>
                    <small><?= $c['booked'] ?> / <?= (int) $c['capacity'] ?></small></td>
                <td>
                    <?php if ($user['role'] === 'member'): ?>
                        <form method="post"><?= csrf_field() ?>
                            <?php if ($c['my_booking']): ?>
                                <input type="hidden" name="action" value="cancel"><input type="hidden" name="booking_id" value="<?= $c['my_booking'] ?>">
                                <button class="btn small danger">Cancel</button>
                            <?php else: ?>
                                <input type="hidden" name="action" value="book"><input type="hidden" name="class_id" value="<?= $c['id'] ?>">
                                <button class="btn small primary" <?= $left <= 0 ? 'disabled' : '' ?>><?= $left <= 0 ? 'Full' : 'Book' ?></button>
                            <?php endif; ?>
                        </form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
</section>
<?php if ($user['role'] === 'admin'): ?>
<section class="card narrow">
    <h2>Add class</h2>
    <form method="post" class="form">
        <?= csrf_field() ?><input type="hidden" name="action" value="create">
        <label>Class name <input name="name" required placeholder="e.g. HIIT Burn"></label>
        <label>Trainer <select name="trainer_id"><?php foreach ($trainers as $t): ?><option value="<?= $t['id'] ?>"><?= e($t['name']) ?></option><?php endforeach; ?></select></label>
        <div class="row">
            <label>Day <select name="day_of_week"><?php foreach ($days as $d): ?><option><?= $d ?></option><?php endforeach; ?></select></label>
            <label>Start <input type="time" name="start_time" required value="18:00"></label>
        </div>
        <div class="row">
            <label>Duration (min) <input type="number" name="duration_min" value="60" min="15"></label>
            <label>Capacity <input type="number" name="capacity" value="15" min="1"></label>
        </div>
        <label>Room <input name="room" value="Studio A"></label>
        <button class="btn primary">Add class</button>
    </form>
</section>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php';
