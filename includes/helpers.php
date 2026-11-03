<?php
function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function redirect(string $url): void
{
    header('Location: ' . $url);
    exit;
}

function flash(?string $message = null, string $type = 'success'): ?array
{
    if ($message !== null) {
        $_SESSION['flash'] = ['msg' => $message, 'type' => $type];
        return null;
    }
    $f = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $f;
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . csrf_token() . '">';
}

function verify_csrf(): void
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST' &&
        !hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
        http_response_code(419);
        exit('Invalid form token. Please go back and try again.');
    }
}

function money($amount): string
{
    return 'PKR ' . number_format((float) $amount, 0);
}

function post(string $key, $default = '')
{
    return isset($_POST[$key]) ? trim((string) $_POST[$key]) : $default;
}

function badge(string $status): string
{
    $map = [
        'active' => 'green', 'booked' => 'blue', 'confirmed' => 'blue', 'attended' => 'green',
        'completed' => 'green', 'paid' => 'green', 'present' => 'green', 'expired' => 'grey', 'inactive' => 'grey', 'pending' => 'amber',
        'cancelled' => 'red', 'cash' => 'grey', 'online' => 'blue',
    ];
    $c = $map[$status] ?? 'grey';
    return '<span class="badge ' . $c . '">' . e(ucfirst($status)) . '</span>';
}
