<?php
require_once __DIR__ . '/includes/auth.php';
require_role('admin');
verify_csrf();

$action = $_POST['action'] ?? '';
if ($action === 'save') {
    $id = (int) post('id');
    $data = [post('name'), post('email'), post('phone')];
    if (!filter_var(post('email'), FILTER_VALIDATE_EMAIL) || post('name') === '') {
        flash('Name and a valid email are required.', 'error');
        redirect('members.php');
    }
    try {
        db()->beginTransaction();
        if ($id) {
            $userId = q('SELECT user_id FROM members WHERE id = ?', [$id])->fetchColumn();
            q('UPDATE users SET name = ?, email = ?, phone = ? WHERE id = ?', [...$data, $userId]);
            q('UPDATE members SET gender = ?, date_of_birth = ?, address = ?, emergency_contact = ?, status = ? WHERE id = ?',
              [post('gender'), post('date_of_birth') ?: null, post('address'), post('emergency_contact'), post('status'), $id]);
            flash('Member updated.');
        } else {
            $password = post('password') ?: 'member123';
            q("INSERT INTO users (name, email, phone, password_hash, role) VALUES (?, ?, ?, ?, 'member')",
              [...$data, password_hash($password, PASSWORD_DEFAULT)]);
            q('INSERT INTO members (user_id, gender, date_of_birth, address, emergency_contact, join_date, status)
               VALUES (?, ?, ?, ?, ?, CURDATE(), ?)',
              [db()->lastInsertId(), post('gender'), post('date_of_birth') ?: null, post('address'),
               post('emergency_contact'), post('status') ?: 'active']);
            flash('Member registered.');
        }
        db()->commit();
    } catch (PDOException $ex) {
        db()->rollBack();
        flash($ex->getCode() == 23000 ? 'That email is already registered.' : 'Could not save member.', 'error');
    }
    redirect('members.php');
}
if ($action === 'delete') {
    // Deleting the user cascades to the member profile and its history.
    q('DELETE u FROM users u JOIN members m ON m.user_id = u.id WHERE m.id = ?', [(int) post('id')]);
    flash('Member deleted.');
    redirect('members.php');
}

$search = trim($_GET['q'] ?? '');
$members = q("SELECT m.*, u.name, u.email, u.phone,
                (SELECT p.name FROM subscriptions s JOIN plans p ON p.id = s.plan_id
                  WHERE s.member_id = m.id AND s.status = 'active' ORDER BY s.end_date DESC LIMIT 1) AS plan
              FROM members m JOIN users u ON u.id = m.user_id
              WHERE u.name LIKE ? OR u.email LIKE ? OR u.phone LIKE ?
              ORDER BY u.name", array_fill(0, 3, "%$search%"))->fetchAll();
$edit = isset($_GET['edit'])
    ? q('SELECT m.*, u.name, u.email, u.phone FROM members m JOIN users u ON u.id = m.user_id WHERE m.id = ?',
        [(int) $_GET['edit']])->fetch()
    : null;

$pageTitle = 'Members';
require __DIR__ . '/includes/header.php';
?>
<div class="grid-2 wide-left">
    <section class="card">
        <form class="toolbar" method="get">
            <input name="q" placeholder="Search name, email or phone" value="<?= e($search) ?>">
            <button class="btn">Search</button>
        </form>
        <table>
            <tr><th>Name</th><th>Phone</th><th>Plan</th><th>Joined</th><th>Status</th><th></th></tr>
            <?php foreach ($members as $m): ?>
                <tr>
                    <td><?= e($m['name']) ?><br><small class="muted"><?= e($m['email']) ?></small></td>
                    <td><?= e($m['phone']) ?></td>
                    <td><?= e($m['plan'] ?? '—') ?></td>
                    <td><?= e($m['join_date']) ?></td>
                    <td><?= badge($m['status']) ?></td>
                    <td class="actions">
                        <a class="btn small" href="?edit=<?= $m['id'] ?>">Edit</a>
                        <form method="post" onsubmit="return confirm('Delete this member?')">
                            <?= csrf_field() ?><input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= $m['id'] ?>">
                            <button class="btn small danger">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    </section>
    <section class="card">
        <h2><?= $edit ? 'Edit member' : 'Register new member' ?></h2>
        <form method="post" class="form">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="id" value="<?= e($edit['id'] ?? '') ?>">
            <label>Full name <input name="name" required value="<?= e($edit['name'] ?? '') ?>"></label>
            <label>Email <input type="email" name="email" required value="<?= e($edit['email'] ?? '') ?>"></label>
            <label>Phone <input name="phone" value="<?= e($edit['phone'] ?? '') ?>"></label>
            <?php if (!$edit): ?>
                <label>Initial password <input name="password" placeholder="default: member123"></label>
            <?php endif; ?>
            <div class="row">
                <label>Gender
                    <select name="gender">
                        <?php foreach (['male', 'female'] as $g): ?>
                            <option <?= ($edit['gender'] ?? '') === $g ? 'selected' : '' ?>><?= $g ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>Date of birth <input type="date" name="date_of_birth" value="<?= e($edit['date_of_birth'] ?? '') ?>"></label>
            </div>
            <label>Address <input name="address" value="<?= e($edit['address'] ?? '') ?>"></label>
            <label>Emergency contact <input name="emergency_contact" value="<?= e($edit['emergency_contact'] ?? '') ?>"></label>
            <label>Status
                <select name="status">
                    <?php foreach (['active', 'inactive'] as $s): ?>
                        <option <?= ($edit['status'] ?? 'active') === $s ? 'selected' : '' ?>><?= $s ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <button class="btn primary"><?= $edit ? 'Update' : 'Register' ?></button>
            <?php if ($edit): ?><a class="btn" href="members.php">Cancel</a><?php endif; ?>
        </form>
    </section>
</div>
<?php require __DIR__ . '/includes/footer.php';
