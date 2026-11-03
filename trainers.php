<?php
require_once __DIR__ . '/includes/auth.php';
require_role('admin');
verify_csrf();

$action = $_POST['action'] ?? '';
if ($action === 'save') {
    $id = (int) post('id');
    try {
        db()->beginTransaction();
        if ($id) {
            $userId = q('SELECT user_id FROM trainers WHERE id = ?', [$id])->fetchColumn();
            q('UPDATE users SET name = ?, email = ?, phone = ? WHERE id = ?', [post('name'), post('email'), post('phone'), $userId]);
            q('UPDATE trainers SET specialization = ?, experience_years = ?, bio = ? WHERE id = ?',
              [post('specialization'), (int) post('experience_years'), post('bio'), $id]);
        } else {
            q("INSERT INTO users (name, email, phone, password_hash, role) VALUES (?, ?, ?, ?, 'trainer')",
              [post('name'), post('email'), post('phone'), password_hash(post('password') ?: 'trainer123', PASSWORD_DEFAULT)]);
            q('INSERT INTO trainers (user_id, specialization, experience_years, bio) VALUES (?, ?, ?, ?)',
              [db()->lastInsertId(), post('specialization'), (int) post('experience_years'), post('bio')]);
        }
        db()->commit();
        flash('Trainer saved.');
    } catch (PDOException $ex) {
        db()->rollBack();
        flash($ex->getCode() == 23000 ? 'That email is already registered.' : 'Could not save trainer.', 'error');
    }
    redirect('trainers.php');
}
if ($action === 'delete') {
    q('DELETE u FROM users u JOIN trainers t ON t.user_id = u.id WHERE t.id = ?', [(int) post('id')]);
    flash('Trainer removed.');
    redirect('trainers.php');
}

$trainers = q('SELECT t.*, u.name, u.email, u.phone,
                 (SELECT COUNT(*) FROM classes c WHERE c.trainer_id = t.id) AS classes,
                 (SELECT COUNT(*) FROM appointments a WHERE a.trainer_id = t.id AND a.appointment_at >= NOW()) AS upcoming
               FROM trainers t JOIN users u ON u.id = t.user_id ORDER BY u.name')->fetchAll();
$edit = isset($_GET['edit'])
    ? q('SELECT t.*, u.name, u.email, u.phone FROM trainers t JOIN users u ON u.id = t.user_id WHERE t.id = ?', [(int) $_GET['edit']])->fetch()
    : null;

$pageTitle = 'Trainers';
require __DIR__ . '/includes/header.php';
?>
<div class="grid-2 wide-left">
    <section class="card">
        <table>
            <tr><th>Trainer</th><th>Specialization</th><th>Experience</th><th>Classes</th><th>Upcoming PT</th><th></th></tr>
            <?php foreach ($trainers as $t): ?>
                <tr>
                    <td><?= e($t['name']) ?><br><small class="muted"><?= e($t['email']) ?></small></td>
                    <td><?= e($t['specialization']) ?></td>
                    <td><?= (int) $t['experience_years'] ?> yrs</td>
                    <td class="num"><?= (int) $t['classes'] ?></td>
                    <td class="num"><?= (int) $t['upcoming'] ?></td>
                    <td class="actions">
                        <a class="btn small" href="?edit=<?= $t['id'] ?>">Edit</a>
                        <form method="post" onsubmit="return confirm('Remove this trainer and their classes?')">
                            <?= csrf_field() ?><input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= $t['id'] ?>">
                            <button class="btn small danger">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    </section>
    <section class="card">
        <h2><?= $edit ? 'Edit trainer' : 'Add trainer' ?></h2>
        <form method="post" class="form">
            <?= csrf_field() ?><input type="hidden" name="action" value="save">
            <input type="hidden" name="id" value="<?= e($edit['id'] ?? '') ?>">
            <label>Full name <input name="name" required value="<?= e($edit['name'] ?? '') ?>"></label>
            <label>Email <input type="email" name="email" required value="<?= e($edit['email'] ?? '') ?>"></label>
            <label>Phone <input name="phone" value="<?= e($edit['phone'] ?? '') ?>"></label>
            <?php if (!$edit): ?><label>Initial password <input name="password" placeholder="default: trainer123"></label><?php endif; ?>
            <label>Specialization <input name="specialization" value="<?= e($edit['specialization'] ?? '') ?>"></label>
            <label>Experience (years) <input type="number" min="0" name="experience_years" value="<?= e($edit['experience_years'] ?? 0) ?>"></label>
            <label>Bio <textarea name="bio" rows="3"><?= e($edit['bio'] ?? '') ?></textarea></label>
            <button class="btn primary">Save</button>
        </form>
    </section>
</div>
<?php require __DIR__ . '/includes/footer.php';
