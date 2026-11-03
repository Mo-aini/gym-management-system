<?php
require_once __DIR__ . '/includes/auth.php';
$user = require_role('admin', 'trainer', 'member');
verify_csrf();

$action = $_POST['action'] ?? '';
if ($action === 'checkin' && $user['role'] !== 'member') {
    $memberId = (int) post('member_id');
    $open = q('SELECT id FROM attendance WHERE member_id = ? AND check_out IS NULL AND DATE(check_in) = CURDATE()', [$memberId])->fetchColumn();
    $active = q("SELECT COUNT(*) FROM subscriptions WHERE member_id = ? AND status = 'active' AND CURDATE() BETWEEN start_date AND end_date",
                [$memberId])->fetchColumn();
    if ($open) {
        flash('This member is already checked in.', 'error');
    } elseif (!$active) {
        flash('No active subscription — please renew before check-in.', 'error');
    } else {
        q('INSERT INTO attendance (member_id, check_in) VALUES (?, NOW())', [$memberId]);
        flash('Checked in.');
    }
    redirect('attendance.php');
}
if ($action === 'checkout' && $user['role'] !== 'member') {
    q('UPDATE attendance SET check_out = NOW() WHERE id = ? AND check_out IS NULL', [(int) post('id')]);
    flash('Checked out.');
    redirect('attendance.php');
}

$date = $_GET['date'] ?? date('Y-m-d');
if ($user['role'] === 'member') {
    $rows = q('SELECT a.*, u.name FROM attendance a JOIN members m ON m.id = a.member_id JOIN users u ON u.id = m.user_id
               WHERE a.member_id = ? ORDER BY a.check_in DESC LIMIT 60', [$user['member_id']])->fetchAll();
} else {
    $rows = q('SELECT a.*, u.name FROM attendance a JOIN members m ON m.id = a.member_id JOIN users u ON u.id = m.user_id
               WHERE DATE(a.check_in) = ? ORDER BY a.check_in DESC', [$date])->fetchAll();
}
$members = q("SELECT m.id, u.name FROM members m JOIN users u ON u.id = m.user_id WHERE m.status = 'active' ORDER BY u.name")->fetchAll();

$pageTitle = $user['role'] === 'member' ? 'My Attendance' : 'Attendance';
require __DIR__ . '/includes/header.php';
?>
<div class="grid-2 <?= $user['role'] !== 'member' ? 'wide-left' : '' ?>">
    <section class="card <?= $user['role'] === 'member' ? 'span-2' : '' ?>">
        <?php if ($user['role'] !== 'member'): ?>
            <form class="toolbar" method="get"><input type="date" name="date" value="<?= e($date) ?>"><button class="btn">Show</button>
                <span class="total"><b><?= count($rows) ?></b> visits</span></form>
        <?php endif; ?>
        <table>
            <tr><th>Member</th><th>Check in</th><th>Check out</th><th>Duration</th><th></th></tr>
            <?php foreach ($rows as $r):
                $mins = $r['check_out'] ? round((strtotime($r['check_out']) - strtotime($r['check_in'])) / 60) : null; ?>
                <tr>
                    <td><?= e($r['name']) ?></td>
                    <td><?= e(date('d M H:i', strtotime($r['check_in']))) ?></td>
                    <td><?= $r['check_out'] ? e(date('H:i', strtotime($r['check_out']))) : '<span class="badge amber">In gym</span>' ?></td>
                    <td><?= $mins !== null ? intdiv($mins, 60) . 'h ' . ($mins % 60) . 'm' : '—' ?></td>
                    <td>
                        <?php if (!$r['check_out'] && $user['role'] !== 'member'): ?>
                            <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="checkout">
                                <input type="hidden" name="id" value="<?= $r['id'] ?>"><button class="btn small">Check out</button></form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$rows): ?><tr><td colspan="5" class="muted">No visits recorded.</td></tr><?php endif; ?>
        </table>
    </section>
    <?php if ($user['role'] !== 'member'): ?>
    <section class="card">
        <h2>Check in a member</h2>
        <form method="post" class="form">
            <?= csrf_field() ?><input type="hidden" name="action" value="checkin">
            <label>Member <select name="member_id"><?php foreach ($members as $m): ?><option value="<?= $m['id'] ?>"><?= e($m['name']) ?></option><?php endforeach; ?></select></label>
            <button class="btn primary">Check in</button>
        </form>
    </section>
    <?php endif; ?>
</div>
<?php require __DIR__ . '/includes/footer.php';
