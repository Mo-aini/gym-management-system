<?php
require_once __DIR__ . '/includes/auth.php';
$user = require_role('admin', 'member');
verify_csrf();

/*
 * Payments are RECORDED here, not processed. "online" means the member paid by
 * bank transfer / mobile wallet outside the system and staff enter the reference.
 * No real payment gateway is connected.
 */
if (($_POST['action'] ?? '') === 'record' && $user['role'] === 'admin') {
    $amount = (float) post('amount');
    $method = post('method');
    if ($amount <= 0 || !in_array($method, ['cash', 'online'], true)) {
        flash('Enter a positive amount and a valid method.', 'error');
    } elseif ($method === 'online' && post('reference') === '') {
        flash('Online payments need a transaction reference.', 'error');
    } else {
        q('INSERT INTO payments (member_id, subscription_id, amount, method, reference, paid_at, recorded_by)
           VALUES (?, ?, ?, ?, ?, NOW(), ?)',
          [(int) post('member_id'), post('subscription_id') ?: null, $amount, $method, post('reference') ?: null, $user['id']]);
        flash('Payment recorded.');
    }
    redirect('payments.php');
}

$method = $_GET['method'] ?? '';
$month = $_GET['month'] ?? '';
$sql = 'SELECT pay.*, u.name AS member, p.name AS plan, ru.name AS recorded_by_name FROM payments pay
        JOIN members m ON m.id = pay.member_id JOIN users u ON u.id = m.user_id
        LEFT JOIN subscriptions s ON s.id = pay.subscription_id LEFT JOIN plans p ON p.id = s.plan_id
        LEFT JOIN users ru ON ru.id = pay.recorded_by WHERE 1 = 1';
$params = [];
if ($user['role'] === 'member') { $sql .= ' AND pay.member_id = ?'; $params[] = $user['member_id']; }
if ($method) { $sql .= ' AND pay.method = ?'; $params[] = $method; }
if ($month) { $sql .= " AND DATE_FORMAT(pay.paid_at, '%Y-%m') = ?"; $params[] = $month; }
$payments = q($sql . ' ORDER BY pay.paid_at DESC', $params)->fetchAll();
$total = array_sum(array_column($payments, 'amount'));
$members = q("SELECT m.id, u.name FROM members m JOIN users u ON u.id = m.user_id ORDER BY u.name")->fetchAll();

$pageTitle = $user['role'] === 'member' ? 'My Payments' : 'Payments';
require __DIR__ . '/includes/header.php';
?>
<div class="grid-2 <?= $user['role'] === 'admin' ? 'wide-left' : '' ?>">
    <section class="card <?= $user['role'] === 'member' ? 'span-2' : '' ?>">
        <form class="toolbar" method="get">
            <select name="method">
                <option value="">All methods</option>
                <option value="cash" <?= $method === 'cash' ? 'selected' : '' ?>>Cash</option>
                <option value="online" <?= $method === 'online' ? 'selected' : '' ?>>Online</option>
            </select>
            <input type="month" name="month" value="<?= e($month) ?>">
            <button class="btn">Filter</button>
            <span class="total">Total: <b><?= money($total) ?></b></span>
        </form>
        <table>
            <tr><th>Date</th><th>Member</th><th>For</th><th>Method</th><th>Reference</th><th class="num">Amount</th></tr>
            <?php foreach ($payments as $p): ?>
                <tr>
                    <td><?= e(date('d M Y H:i', strtotime($p['paid_at']))) ?></td>
                    <td><?= e($p['member']) ?></td>
                    <td><?= e($p['plan'] ?? 'Other') ?></td>
                    <td><?= badge($p['method']) ?></td>
                    <td><small><?= e($p['reference'] ?? '—') ?></small></td>
                    <td class="num"><?= money($p['amount']) ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    </section>
    <?php if ($user['role'] === 'admin'): ?>
    <section class="card">
        <h2>Record a payment</h2>
        <form method="post" class="form">
            <?= csrf_field() ?><input type="hidden" name="action" value="record">
            <label>Member <select name="member_id"><?php foreach ($members as $m): ?><option value="<?= $m['id'] ?>"><?= e($m['name']) ?></option><?php endforeach; ?></select></label>
            <label>Amount (PKR) <input type="number" name="amount" min="50" step="50" required></label>
            <div class="row">
                <label>Method <select name="method"><option value="cash">cash</option><option value="online">online</option></select></label>
                <label>Reference <input name="reference" placeholder="required for online"></label>
            </div>
            <button class="btn primary">Save payment</button>
            <p class="muted small">Online = paid by bank transfer / wallet and verified by staff. No card data is stored.</p>
        </form>
    </section>
    <?php endif; ?>
</div>
<?php require __DIR__ . '/includes/footer.php';
