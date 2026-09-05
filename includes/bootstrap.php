<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_set_cookie_params([
        'httponly' => true,
        'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
        'samesite' => 'Lax',
    ]);
    session_start();
}

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) return $pdo;

    $host = getenv('DB_HOST') ?: 'localhost';
    $name = getenv('DB_DATABASE') ?: 'kadad_kadad_class';
    $user = getenv('DB_USERNAME') ?: 'kadad_kadad_class';
    $pass = getenv('DB_PASSWORD') ?: '';

    if ($pass === '') {
        throw new RuntimeException('DB_PASSWORD is not configured on the server.');
    }

    $pdo = new PDO(
        "mysql:host={$host};dbname={$name};charset=utf8mb4",
        $user,
        $pass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );
    return $pdo;
}

function redirect(string $path): never
{
    header('Location: ' . $path, true, 302);
    exit;
}

function csrf_token(): string
{
    if (empty($_SESSION['_csrf'])) $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['_csrf'];
}

function verify_csrf(): void
{
    if (!hash_equals($_SESSION['_csrf'] ?? '', $_POST['_csrf'] ?? '')) {
        http_response_code(419);
        exit('درخواست نامعتبر است.');
    }
}

function current_user(): ?array
{
    if (empty($_SESSION['user_id'])) return null;
    static $user = false;
    if ($user !== false) return $user;
    $stmt = db()->prepare('SELECT id,name,email,role,avatar FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([(int)$_SESSION['user_id']]);
    $user = $stmt->fetch() ?: null;
    if (!$user) unset($_SESSION['user_id']);
    return $user;
}

function require_auth(): array
{
    $user = current_user();
    if (!$user) redirect('/login/signin.php');
    return $user;
}

function require_teacher(): array
{
    $user = require_auth();
    if (!in_array($user['role'], ['teacher', 'admin'], true)) {
        http_response_code(403);
        exit('دسترسی به این بخش فقط برای مدرس یا مدیر مجاز است.');
    }
    return $user;
}
