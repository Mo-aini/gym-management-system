<?php
require_once __DIR__ . '/includes/auth.php';
$user = require_role();
$pageTitle = 'Dashboard';
require __DIR__ . '/includes/header.php';

if ($user['role'] === 'admin'):
    $stats = [
        'Active members'      => q("SELECT COUNT(*) FROM members WHERE status = 'active'")->fetchColumn(),
        'Revenue this month'  => money(q("SELECT COALESCE(SUM(amount),0) FROM payments
                                          WHERE DATE_FORMAT(paid_at,'%Y-%m') = DATE_FORMAT(CURDATE(),'%Y-%m')")->fetchColumn()),
        "Today's check-ins"   => q('SELECT COUNT(*) FROM attendance WHERE DATE(check_in) = CURDATE()')->fetchColumn(),
        'Expiring in 7 days'  => q("SELECT COUNT(*) FROM subscriptions WHERE status = 'active'
                                    AND end_date BETWEEN CURDATE() AND CURDATE() + INTERVAL 7 DAY")->fetchColumn(),
    ];
    $revenue = q("SELECT DATE_FORMAT(paid_at,'%Y-%m') AS ym, SUM(amount) AS total,
                         SUM(method = 'online') AS online_count, SUM(method = 'cash') AS cash_count
                  FROM payments WHERE paid_at >= DATE_FORMAT(CURDATE() - INTERVAL 5 MONTH, '%Y-%m-01')
                  GROUP BY ym ORDER BY ym")->fetchAll();
    $max = max(array_merge([1], array_column($revenue, 'total')));
    $popularPlans = q("SELECT p.name, COUNT(s.id) AS subs FROM plans p
                       LEFT JOIN subscriptions s ON s.plan_id = p.id AND s.status = 'active'
                       GROUP BY p.id ORDER BY subs DESC")->fetchAll();
    $expiring = q("SELECT u.name, p.name AS plan, s.end_date FROM subscriptions s
                   JOIN members m ON m.id = s.member_id JOIN users u ON u.id = m.user_id
                   JOIN plans p ON p.id = s.plan_id
                   WHERE s.status = 'active' AND s.end_date BETWEEN CURDATE() AND CURDATE() + INTERVAL 7 DAY
                   ORDER BY s.end_date")->fetchAll();
    $recent = q("SELECT u.name, pay.amount, pay.method, pay.paid_at FROM payments pay
                 JOIN members m ON m.id = pay.member_id JOIN users u ON u.id = m.user_id
                 ORDER BY pay.paid_at DESC LIMIT 6")->fetchAll();
?>
    <div class="cards">
        <?php foreach ($stats as $label => $value): ?>
            <div class="card stat"><small><?= e($label) ?></small><strong><?= e($value) ?></strong></div>
        <?php endforeach; ?>
    </div>
    <div class="grid-2">
        <section class="card">
            <h2>Revenue (last 6 months)</h2>
            <div class="bars">
                <?php foreach ($revenue as $r): ?>
                    <div class="bar-row">
                        <span><?= e(date('M Y', strtotime($r['ym'] . '-01'))) ?></span>
                        <div class="bar"><i style="width: <?= round($r['total'] / $max * 100) ?>%"></i></div>
                        <b><?= money($r['total']) ?></b>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
        <section class="card">
            <h2>Active subscriptions by plan</h2>
            <table>
                <?php foreach ($popularPlans as $p): ?>
                    <tr><td><?= e($p['name']) ?></td><td class="num"><?= (int) $p['subs'] ?></td></tr>
                <?php endforeach; ?>
            </table>
        </section>
        <section class="card">
            <h2>Expiring soon</h2>
            <table>
                <tr><th>Member</th><th>Plan</th><th>Ends</th></tr>
                <?php foreach ($expiring as $x): ?>
                    <tr><td><?= e($x['name']) ?></td><td><?= e($x['plan']) ?></td><td><?= e($x['end_date']) ?></td></tr>
                <?php endforeach; ?>
                <?php if (!$expiring): ?><tr><td colspan="3" class="muted">Nothing expires this week.</td></tr><?php endif; ?>
            </table>
        </section>
        <section class="card">
            <h2>Recent payments</h2>
            <table>
                <?php foreach ($recent as $r): ?>
                    <tr><td><?= e($r['name']) ?></td><td><?= badge($r['method']) ?></td>
                        <td class="num"><?= money($r['amount']) ?></td></tr>
                <?php endforeach; ?>
            </table>
        </section>
    </div>

<?php elseif ($user['role'] === 'trainer'):
    $classes = q('SELECT c.*, (SELECT COUNT(*) FROM bookings b WHERE b.class_id = c.id
                    AND b.status = "booked" AND b.class_date >= CURDATE()) AS upcoming_bookings
                  FROM classes c WHERE c.trainer_id = ? ORDER BY FIELD(day_of_week,
                  "Mon","Tue","Wed","Thu","Fri","Sat","Sun"), start_time', [$user['trainer_id']])->fetchAll();
    $appts = q('SELECT a.*, u.name AS member FROM appointments a JOIN members m ON m.id = a.member_id
                JOIN users u ON u.id = m.user_id WHERE a.trainer_id = ? AND a.appointment_at >= NOW()
                ORDER BY a.appointment_at LIMIT 8', [$user['trainer_id']])->fetchAll();
?>
    <div class="grid-2">
        <section class="card">
            <h2>My weekly classes</h2>
            <table>
                <tr><th>Class</th><th>Day</th><th>Time</th><th>Booked</th></tr>
                <?php foreach ($classes as $c): ?>
                    <tr><td><?= e($c['name']) ?></td><td><?= e($c['day_of_week']) ?></td>
                        <td><?= e(substr($c['start_time'], 0, 5)) ?></td>
                        <td><?= (int) $c['upcoming_bookings'] ?> / <?= (int) $c['capacity'] ?></td></tr>
                <?php endforeach; ?>
            </table>
        </section>
        <section class="card">
            <h2>Upcoming appointments</h2>
            <table>
                <?php foreach ($appts as $a): ?>
                    <tr><td><?= e($a['member']) ?></td><td><?= e(date('D d M, H:i', strtotime($a['appointment_at']))) ?></td>
                        <td><?= badge($a['status']) ?></td></tr>
                <?php endforeach; ?>
                <?php if (!$appts): ?><tr><td class="muted">No upcoming appointments.</td></tr><?php endif; ?>
            </table>
        </section>
    </div>

<?php else:
    $sub = q('SELECT s.*, p.name AS plan FROM subscriptions s JOIN plans p ON p.id = s.plan_id
              WHERE s.member_id = ? ORDER BY s.end_date DESC LIMIT 1', [$user['member_id']])->fetch();
    $visits = q('SELECT COUNT(*) FROM attendance WHERE member_id = ?
                 AND check_in >= CURDATE() - INTERVAL 30 DAY', [$user['member_id']])->fetchColumn();
    $bookings = q('SELECT b.class_date, c.name, c.start_time, b.status FROM bookings b JOIN classes c ON c.id = b.class_id
                   WHERE b.member_id = ? AND b.class_date >= CURDATE() ORDER BY b.class_date', [$user['member_id']])->fetchAll();
    $daysLeft = $sub ? (int) ((strtotime($sub['end_date']) - strtotime('today')) / 86400) : 0;
?>
    <div class="cards">
        <div class="card stat"><small>Current plan</small><strong><?= e($sub['plan'] ?? 'None') ?></strong></div>
        <div class="card stat"><small>Days left</small><strong><?= max(0, $daysLeft) ?></strong></div>
        <div class="card stat"><small>Visits (30 days)</small><strong><?= (int) $visits ?></strong></div>
        <div class="card stat"><small>Upcoming classes</small><strong><?= count($bookings) ?></strong></div>
    </div>
    <section class="card">
        <h2>My upcoming classes</h2>
        <table>
            <tr><th>Date</th><th>Class</th><th>Time</th><th>Status</th></tr>
            <?php foreach ($bookings as $b): ?>
                <tr><td><?= e($b['class_date']) ?></td><td><?= e($b['name']) ?></td>
                    <td><?= e(substr($b['start_time'], 0, 5)) ?></td><td><?= badge($b['status']) ?></td></tr>
            <?php endforeach; ?>
            <?php if (!$bookings): ?><tr><td colspan="4" class="muted">No bookings yet. <a href="classes.php">Book a class</a></td></tr><?php endif; ?>
        </table>
    </section>
<?php endif;

require __DIR__ . '/includes/footer.php';
