<?php
require_once __DIR__ . '/includes/auth.php';

if (current_user()) {
    redirect('dashboard.php');
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    if (attempt_login(post('email'), post('password'))) {
        redirect('dashboard.php');
    }
    $error = 'Wrong email or password.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login | IronCore Gym</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="login-page">
<form method="post" class="login-card">
    <div class="brand big">Iron<span>Core</span></div>
    <p class="muted">Gym Management System</p>
    <?php if ($error): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?>
    <?= csrf_field() ?>
    <label>Email <input type="email" name="email" required value="<?= e(post('email')) ?>"></label>
    <label>Password <input type="password" name="password" required></label>
    <button type="submit" class="btn primary wide">Sign in</button>
    <div class="demo">
        <strong>Demo accounts</strong>
        <span>admin@ironcore.test / admin123</span>
        <span>trainer@ironcore.test / trainer123</span>
        <span>member@ironcore.test / member123</span>
    </div>
</form>
</body>
</html>
