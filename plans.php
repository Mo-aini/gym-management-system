<?php
require_once __DIR__ . '/includes/auth.php';
require_role('admin');
verify_csrf();

$action = $_POST['action'] ?? '';
if ($action === 'save') {
    $price = (float) post('price');
    $months = (int) post('duration_months');
    if ($price <= 0 || $months <= 0) {
        flash('Price and duration must be positive.', 'error');
        redirect('plans.php');
    }
    $params = [post('name'), $months, $price, post('description'), isset($_POST['is_active']) ? 1 : 0];
    if ($id = (int) post('id')) {
        q('UPDATE plans SET name = ?, duration_months = ?, price = ?, description = ?, is_active = ? WHERE id = ?', [...$params, $id]);
    } else {
        q('INSERT INTO plans (name, duration_months, price, description, is_active) VALUES (?, ?, ?, ?, ?)', $params);
    }
    flash('Plan saved.');
    redirect('plans.php');
}

$plans = q("SELECT p.*, SUM(s.status = 'active') AS active_subs FROM plans p
            LEFT JOIN subscriptions s ON s.plan_id = p.id GROUP BY p.id ORDER BY p.price")->fetchAll();
$edit = isset($_GET['edit']) ? q('SELECT * FROM plans WHERE id = ?', [(int) $_GET['edit']])->fetch() : null;

$pageTitle = 'Membership Plans';
require __DIR__ . '/includes/header.php';
?>
<div class="plans">
    <?php foreach ($plans as $p): ?>
        <div class="card plan <?= $p['is_active'] ? '' : 'disabled' ?>">
            <h3><?= e($p['name']) ?></h3>
            <div class="price"><?= money($p['price']) ?> <small>/ <?= (int) $p['duration_months'] ?> month(s)</small></div>
            <p class="muted"><?= e($p['description']) ?></p>
            <p><b><?= (int) $p['active_subs'] ?></b> active subscribers</p>
            <a class="btn small" href="?edit=<?= $p['id'] ?>">Edit</a>
        </div>
    <?php endforeach; ?>
</div>
<section class="card narrow">
    <h2><?= $edit ? 'Edit plan' : 'New plan' ?></h2>
    <form method="post" class="form">
        <?= csrf_field() ?><input type="hidden" name="action" value="save">
        <input type="hidden" name="id" value="<?= e($edit['id'] ?? '') ?>">
        <label>Name <input name="name" required value="<?= e($edit['name'] ?? '') ?>"></label>
        <div class="row">
            <label>Duration (months) <input type="number" min="1" name="duration_months" value="<?= e($edit['duration_months'] ?? 1) ?>"></label>
            <label>Price (PKR) <input type="number" min="50" step="50" name="price" value="<?= e($edit['price'] ?? '') ?>"></label>
        </div>
        <label>Description <textarea name="description" rows="2"><?= e($edit['description'] ?? '') ?></textarea></label>
        <label class="check"><input type="checkbox" name="is_active" <?= ($edit['is_active'] ?? 1) ? 'checked' : '' ?>> Available for sale</label>
        <button class="btn primary">Save plan</button>
    </form>
</section>
<?php require __DIR__ . '/includes/footer.php';
