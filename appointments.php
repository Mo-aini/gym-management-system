<?php
require_once __DIR__ . '/includes/auth.php';
$user = require_role('admin', 'trainer', 'member');
verify_csrf();

$action = $_POST['action'] ?? '';
if ($action === 'request' && $user['role'] === 'member') {
    $at = date('Y-m-d H:i:s', strtotime(post('appointment_at')));
    $trainerId = (int) post('trainer_id');
    if (strtotime($at) <= time()) {
        flash('Please choose a future date and time.', 'error');
        redirect('appointments.php');
    }
    // A trainer cannot have two non-cancelled sessions that start within the same hour.
    $clash = q("SELECT COUNT(*) FROM appointments WHERE trainer_id = ? AND status <> 'cancelled'
                AND ABS(TIMESTAMPDIFF(MINUTE, appointment_at, ?)) < 60", [$trainerId, $at])->fetchColumn();
    if ($clash) {
        flash('The trainer is already booked at that time. Try another slot.', 'error');
    } else {
        q("INSERT INTO appointments (member_id, trainer_id, appointment_at, notes, status) VALUES (?, ?, ?, ?, 'pending')",
          [$user['member_id'], $trainerId, $at, post('notes')]);
        flash('Request sent. The trainer will confirm it.');
    }
    redirect('appointments.php');
}
if ($action === 'status') {
    $allowed = ['trainer' => ['confirmed', 'completed', 'cancelled'], 'admin' => ['confirmed', 'completed', 'cancelled'],
                'member' => ['cancelled']];
    $status = post('status');
    if (in_array($status, $allowed[$user['role']], true)) {
        $scope = match ($user['role']) {
            'trainer' => ['AND trainer_id = ?', $user['trainer_id']],
            'member'  => ['AND member_id = ?', $user['member_id']],
            default   => ['AND 1 = ?', 1],
        };
        q("UPDATE appointments SET status = ? WHERE id = ? {$scope[0]}", [$status, (int) post('id'), $scope[1]]);
        flash('Appointment ' . $status . '.');
    }
    redirect('appointments.php');
}

[$cond, $param] = match ($user['role']) {
    'trainer' => ['a.trainer_id = ?', $user['trainer_id']],
    'member'  => ['a.member_id = ?', $user['member_id']],
    default   => ['1 = ?', 1],
};
$appointments = q("SELECT a.*, mu.name AS member, tu.name AS trainer FROM appointments a
                   JOIN members m ON m.id = a.member_id JOIN users mu ON mu.id = m.user_id
                   JOIN trainers t ON t.id = a.trainer_id JOIN users tu ON tu.id = t.user_id
                   WHERE $cond ORDER BY a.appointment_at DESC", [$param])->fetchAll();
$trainers = q('SELECT t.id, u.name, t.specialization FROM trainers t JOIN users u ON u.id = t.user_id ORDER BY u.name')->fetchAll();

$pageTitle = $user['role'] === 'member' ? 'Personal Training' : 'Appointments';
require __DIR__ . '/includes/header.php';
?>
<div class="grid-2 <?= $user['role'] === 'member' ? 'wide-left' : '' ?>">
    <section class="card <?= $user['role'] !== 'member' ? 'span-2' : '' ?>">
        <table>
            <tr><th>When</th><th>Member</th><th>Trainer</th><th>Notes</th><th>Status</th><th></th></tr>
            <?php foreach ($appointments as $a): ?>
                <tr>
                    <td><?= e(date('D d M Y, H:i', strtotime($a['appointment_at']))) ?></td>
                    <td><?= e($a['member']) ?></td><td><?= e($a['trainer']) ?></td>
                    <td><small><?= e($a['notes']) ?></small></td>
                    <td><?= badge($a['status']) ?></td>
                    <td class="actions">
                        <?php if (in_array($a['status'], ['pending', 'confirmed'], true)): ?>
                            <?php foreach ($user['role'] === 'member' ? ['cancelled'] : ($a['status'] === 'pending' ? ['confirmed', 'cancelled'] : ['completed', 'cancelled']) as $st): ?>
                                <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="status">
                                    <input type="hidden" name="id" value="<?= $a['id'] ?>"><input type="hidden" name="status" value="<?= $st ?>">
                                    <button class="btn small <?= $st === 'cancelled' ? 'danger' : 'primary' ?>"><?= ['confirmed' => 'Confirm', 'completed' => 'Complete', 'cancelled' => 'Cancel'][$st] ?></button>
                                </form>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$appointments): ?><tr><td colspan="6" class="muted">No appointments yet.</td></tr><?php endif; ?>
        </table>
    </section>
    <?php if ($user['role'] === 'member'): ?>
    <section class="card">
        <h2>Request a session</h2>
        <form method="post" class="form">
            <?= csrf_field() ?><input type="hidden" name="action" value="request">
            <label>Trainer
                <select name="trainer_id"><?php foreach ($trainers as $t): ?>
                    <option value="<?= $t['id'] ?>"><?= e($t['name']) ?> — <?= e($t['specialization']) ?></option>
                <?php endforeach; ?></select>
            </label>
            <label>Date & time <input type="datetime-local" name="appointment_at" required></label>
            <label>Goal / notes <textarea name="notes" rows="3" placeholder="e.g. deadlift technique"></textarea></label>
            <button class="btn primary">Send request</button>
        </form>
    </section>
    <?php endif; ?>
</div>
<?php require __DIR__ . '/includes/footer.php';
