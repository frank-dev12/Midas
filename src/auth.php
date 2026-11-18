<?php

declare(strict_types=1);

function auth_start(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    session_name('midas');
    $secure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'httponly' => true,
        'samesite' => 'Lax',
        'secure' => $secure,
    ]);
    session_start();
}

function csrf_token(): string
{
    auth_start();
    if (empty($_SESSION['csrf']) || !is_string($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

function csrf_check(): void
{
    auth_start();
    $sent = $_POST['_csrf'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    $expected = $_SESSION['csrf'] ?? '';
    $valid = is_string($sent) && is_string($expected) && $expected !== '' && hash_equals($expected, $sent);
    if ($valid) {
        return;
    }
    if (str_starts_with(request_path(), '/api/')) {
        json_out(['ok' => false, 'error' => 'La sesión expiró. Recarga la página.'], 419);
    }
    http_response_code(419);
    echo 'La sesión expiró. Vuelve a intentar.';
    exit;
}

function current_user(): ?array
{
    auth_start();
    $id = $_SESSION['user_id'] ?? null;
    if (!is_int($id) && !(is_string($id) && ctype_digit($id))) {
        return null;
    }
    $stmt = db()->prepare('SELECT id, username, name FROM users WHERE id = ?');
    $stmt->execute([(int) $id]);
    $user = $stmt->fetch();
    if (!$user) {
        unset($_SESSION['user_id']);

        return null;
    }

    return $user;
}

function current_user_id(): ?int
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        return null;
    }
    $id = $_SESSION['user_id'] ?? null;

    return is_numeric($id) ? (int) $id : null;
}

function require_user(): array
{
    $user = current_user();
    if ($user) {
        return $user;
    }
    if (str_starts_with(request_path(), '/api/')) {
        json_out(['ok' => false, 'error' => 'Inicia sesión.'], 401);
    }
    redirect('/login');
}

function attempt_login(string $username, string $password): bool
{
    auth_start();
    if (mb_strlen($username) > 40 || strlen($password) > 200) {
        return false;
    }
    $lockedUntil = (int) ($_SESSION['login_locked_until'] ?? 0);
    if ($lockedUntil > time()) {
        throw new InvalidArgumentException('Demasiados intentos. Espera un momento.');
    }
    $stmt = db()->prepare('SELECT * FROM users WHERE username = ?');
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    if (!$user || !password_verify($password, (string) $user['password_hash'])) {
        $fails = (int) ($_SESSION['login_fails'] ?? 0) + 1;
        $_SESSION['login_fails'] = $fails;
        if ($fails >= 8) {
            $_SESSION['login_locked_until'] = time() + 30;
            $_SESSION['login_fails'] = 0;
        }

        return false;
    }
    session_regenerate_id(true);
    $_SESSION['user_id'] = (int) $user['id'];
    $_SESSION['login_fails'] = 0;
    unset($_SESSION['login_locked_until']);
    $_SESSION['csrf'] = bin2hex(random_bytes(32));

    return true;
}

function logout(): void
{
    auth_start();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'] ?? '', (bool) $params['secure'], (bool) $params['httponly']);
    }
    session_destroy();
}

function change_password(int $userId, string $current, string $next): void
{
    if (strlen($next) < 6) {
        throw new InvalidArgumentException('La nueva clave debe tener al menos 6 caracteres.');
    }
    if (strlen($next) > 72) {
        throw new InvalidArgumentException('La clave es demasiado larga.');
    }
    $stmt = db()->prepare('SELECT password_hash FROM users WHERE id = ?');
    $stmt->execute([$userId]);
    $hash = $stmt->fetchColumn();
    if (!is_string($hash) || !password_verify($current, $hash)) {
        throw new InvalidArgumentException('La clave actual no coincide.');
    }
    db()->prepare('UPDATE users SET password_hash = ? WHERE id = ?')
        ->execute([password_hash($next, PASSWORD_DEFAULT), $userId]);
}
