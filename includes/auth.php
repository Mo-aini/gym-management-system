<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function attempt_login(string $email, string $password): bool
{
    $user = q('SELECT id, name, email, password_hash, role FROM users WHERE email = ?', [$email])->fetch();
    if (!$user || !password_verify($password, $user['password_hash'])) {
        return false;
    }
    session_regenerate_id(true);
    unset($user['password_hash']);

    // Attach the role-specific profile id (member_id / trainer_id).
    if ($user['role'] === 'member') {
        $user['member_id'] = (int) q('SELECT id FROM members WHERE user_id = ?', [$user['id']])->fetchColumn();
    } elseif ($user['role'] === 'trainer') {
        $user['trainer_id'] = (int) q('SELECT id FROM trainers WHERE user_id = ?', [$user['id']])->fetchColumn();
    }
    $_SESSION['user'] = $user;
    return true;
}

/** Stop the request unless the user is logged in with one of the given roles. */
function require_role(string ...$roles): array
{
    $user = current_user();
    if (!$user) {
        redirect('index.php');
    }
    if ($roles && !in_array($user['role'], $roles, true)) {
        http_response_code(403);
        exit('403 - You do not have permission to open this page.');
    }
    return $user;
}

function logout(): void
{
    $_SESSION = [];
    session_destroy();
}
