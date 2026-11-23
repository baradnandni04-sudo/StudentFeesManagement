<?php
declare(strict_types=1);

$config = require __DIR__ . '/config.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_set_cookie_params([
        'httponly' => true,
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'samesite' => 'Lax',
    ]);
    session_start();
}

function db(): PDO
{
    static $pdo;
    global $config;
    if (!$pdo) {
        $pdo = new PDO($config['dsn'], $config['db_user'], $config['db_password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    }
    return $pdo;
}

function e(mixed $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function money(float|int|string $amount): string
{
    return '$' . number_format((float)$amount, 2);
}

function redirect(string $url): never
{
    header('Location: ' . $url);
    exit;
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

function require_csrf(): void
{
    if (!hash_equals(csrf_token(), (string)($_POST['csrf'] ?? ''))) {
        http_response_code(419);
        exit('Your session expired. Return to the previous page and try again.');
    }
}

function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function require_login(): array
{
    $user = current_user();
    if (!$user) {
        redirect('index.php?page=login');
    }
    $stmt = db()->prepare('SELECT id, name, email, role, is_active FROM users WHERE id = ?');
    $stmt->execute([$user['id']]);
    $account = $stmt->fetch();
    if (!$account || !(int)$account['is_active']) {
        $_SESSION = [];
        session_regenerate_id(true);
        flash('danger', 'Your account is unavailable. Contact an administrator if you need help.');
        redirect('index.php?page=login');
    }
    unset($account['is_active']);
    $_SESSION['user'] = $account;
    return $account;
}

function require_admin(): array
{
    $user = require_login();
    if ($user['role'] !== 'admin') {
        http_response_code(403);
        exit('Access denied.');
    }
    return $user;
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function take_flash(): ?array
{
    $message = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $message;
}

function post_string(string $key): string
{
    return trim((string)($_POST[$key] ?? ''));
}

function valid_email(string $email): bool
{
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function fee_balance(int $feeId): array
{
    $stmt = db()->prepare(
        'SELECT f.id, f.amount, f.created_at, f.semester_name, f.course_name,
                COALESCE(SUM(CASE WHEN p.status = "paid" THEN p.amount ELSE 0 END), 0) AS paid
         FROM student_fees f
         LEFT JOIN payments p ON p.student_fee_id = f.id
         WHERE f.id = ?
         GROUP BY f.id, f.amount, f.created_at, f.semester_name, f.course_name'
    );
    $stmt->execute([$feeId]);
    $fee = $stmt->fetch();
    if (!$fee) {
        throw new RuntimeException('Fee record not found.');
    }
    $fee['balance'] = max(0, (float)$fee['amount'] - (float)$fee['paid']);
    return $fee;
}
