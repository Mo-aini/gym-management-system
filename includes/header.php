<?php
/** @var string $pageTitle */
$user = current_user();
$active = basename($_SERVER['SCRIPT_NAME']);
$menu = [
    'admin'   => ['dashboard.php' => 'Dashboard', 'members.php' => 'Members', 'trainers.php' => 'Trainers',
                  'plans.php' => 'Plans', 'subscriptions.php' => 'Subscriptions', 'classes.php' => 'Classes',
                  'appointments.php' => 'Appointments', 'payments.php' => 'Payments', 'attendance.php' => 'Attendance'],
    'trainer' => ['dashboard.php' => 'Dashboard', 'classes.php' => 'My Classes',
                  'appointments.php' => 'Appointments', 'attendance.php' => 'Attendance'],
    'member'  => ['dashboard.php' => 'Dashboard', 'classes.php' => 'Book a Class',
                  'appointments.php' => 'Personal Training', 'payments.php' => 'My Payments',
                  'attendance.php' => 'My Attendance'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle ?? 'Gym Management System') ?> | IronCore Gym</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="layout">
    <aside class="sidebar">
        <div class="brand">Iron<span>Core</span></div>
        <nav>
            <?php foreach ($menu[$user['role']] as $file => $label): ?>
                <a href="<?= $file ?>" class="<?= $active === $file ? 'active' : '' ?>"><?= e($label) ?></a>
            <?php endforeach; ?>
        </nav>
        <div class="who">
            <strong><?= e($user['name']) ?></strong>
            <small><?= e(ucfirst($user['role'])) ?></small>
            <a href="logout.php" class="logout">Log out</a>
        </div>
    </aside>
    <main class="content">
        <h1><?= e($pageTitle ?? '') ?></h1>
        <?php if ($f = flash()): ?>
            <div class="alert <?= e($f['type']) ?>"><?= e($f['msg']) ?></div>
        <?php endif; ?>
