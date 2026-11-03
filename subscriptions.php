<?php
require_once __DIR__ . '/includes/auth.php';
$user = require_role('admin');
verify_csrf();

// Mark subscriptions whose end date has passed as expired.
q("UPDATE subscriptions SET status = 'expired' WHERE status = 'active' AND end_date < CURDATE()");

$action = $_POST['action'] ?? '';
if ($action === 'create') {
    $plan = q('SELECT * FROM plans WHERE id = ? AND is_active = 1', [(int) post('plan_id')])->fetch();
    $memberId = (int) post('member_id');
    $start = post('start_date') ?: date('Y-m-d');
    if (!$plan || !$memberId) {
        flash('Choose a member and an active plan.', 'error');
        redirect('subscriptions.php');
    }
    if (isset($_POST['paid_now']) && post('method') === 'online' && post('reference') === '') {
        flash('Online payments need a transaction reference.', 'error');
        redirect('subscriptions.php');
    }
    $end = date('Y-m-d', strtotime("$start +{$plan['duration_months']} month -1 day"));

    db()->beginTransaction();
    // A member has only one active subscription: the old one is closed.
    q("UPDATE subscriptions SET status = 'cancelled' WHERE member_id = ? AND status = 'active'", [$memberId]);
    q("INSERT INTO subscriptions (member_id, plan_id, start_date, end_date, status) VALUES (?, ?, ?, ?, 'active')",
      [$memberId, $plan['id'], $start, $end]);
    $subId = db()->lastInsertId();
    if (isset($_POST['paid_now'])) {
        q('INSERT INTO payments (member_id, subscription_id, amount, method, reference, paid_at, recorded_by)
           VALUES (?, ?, ?, ?, ?, NOW(), ?)',
          [$memberId, $subId, $plan['price'], post('method'), post('reference') ?: null, $user['id']]);
    }
    db()->commit();
    flash("Subscription created: {$plan['name']} until $end.");
    redirect('subscriptions.php');
}
if ($action === 'cancel') {
    q("UPDATE subscriptions SET status = 'cancelled' WHERE id = ?", [(int) post('id')]);
    flash('Subscription cancelled.');
    redirect('subscriptions.php');
}

$filter = $_GET['status'] ?? 'active';
$subs = q("SELECT s.*, u.name AS member, p.name AS plan, p.price,
             (SELECT COALESCE(SUM(amount),0) FROM payments WHERE subscription_id = s.id) AS paid
           FROM subscriptions s JOIN members m ON m.id = s.member_id JOIN users u ON u.id = m.user_id
           JOIN plans p ON p.id = s.plan_id
           WHERE (? = 'all' OR s.status = ?) ORDER BY s.end_date", [$filter, $filter])->fetchAll();
$members = q("SELECT m.id, u.name FROM members m JOIN users u ON u.id = m.user_id WHERE m.status = 'active' ORDER BY u.name")->fetchAll();
$plans = q('SELECT * FROM plans WHERE is_active = 1 ORDER BY price')->fetchAll();

$pageTitle = 'Subscriptions';
require __DIR__ . '/includes/header.php';
?>
<div class="grid-2 wide-left">
    <section class="card">
        <div class="tabs">
            <?php foreach (['active', 'expired', 'cancelled', 'all'] as $s): ?>
                <a href="?status=<?= $s ?>" class="<?= $filter === $s ? 'on' : '' ?>"><?= ucfirst($s) ?></a>
            <?php endforeach; ?>
        </div>
        <table>
            <tr><th>Member</th><th>Plan</th><th>Start</th><th>End</th><th>Paid</th><th>Status</th><th></th></tr>
            <?php foreach ($subs as $s): ?>
                <tr>
                    <td><?= e($s['member']) ?></td><td><?= e($s['plan']) ?></td>
                    <td><?= e($s['start_date']) ?></td><td><?= e($s['end_date']) ?></td>
                    <td><?= $s['paid'] >= $s['price'] ? badge('paid') : '<span class="badge amber">Due ' . money($s['price'] - $s['paid']) . '</span>' ?></td>
                    <td><?= badge($s['status']) ?></td>
                    <td>
                        <?php if ($s['status'] === 'active'): ?>
                            <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="cancel">
                                <input type="hidden" name="id" value="<?= $s['id'] ?>"><button class="btn small danger">Cancel</button></form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    </section>
    <section class="card">
        <h2>New subscription</h2>
        <form method="post" class="form">
            <?= csrf_field() ?><input type="hidden" name="action" value="create">
            <label>Member
                <select name="member_id" required>
                    <option value="">Select member…</option>
                    <?php foreach ($members as $m): ?><option value="<?= $m['id'] ?>"><?= e($m['name']) ?></option><?php endforeach; ?>
                </select>
            </label>
            <label>Plan
                <select name="plan_id" required>
                    <?php foreach ($plans as $p): ?>
                        <option value="<?= $p['id'] ?>"><?= e($p['name']) ?> — <?= money($p['price']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Start date <input type="date" name="start_date" value="<?= date('Y-m-d') ?>"></label>
            <label class="check"><input type="checkbox" name="paid_now" checked> Record payment now</label>
            <div class="row">
                <label>Method <select name="method"><option value="cash">cash</option><option value="online">online</option></select></label>
                <label>Reference <input name="reference" placeholder="online transfer ID"></label>
            </div>
            <button class="btn primary">Create subscription</button>
        </form>
    </section>
</div>
<?php require __DIR__ . '/includes/footer.php';
