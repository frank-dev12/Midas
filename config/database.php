<?php
declare(strict_types=1);

/**
 * Credenciales MySQL / MariaDB (XAMPP o servidor local).
 * En XAMPP clásico suele ser: root + contraseña vacía.
 * En este equipo se usa el usuario del sistema con acceso local.
 */
define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3306');
define('DB_NAME', 'midas_pos');
define('DB_USER', 'winter');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

define('APP_NAME', 'Midas POS');
define('APP_CURRENCY', 'S/');
define('COMPANY_NAME', 'Midas Comercial');
define('COMPANY_RUC', '20123456789');
define('COMPANY_ADDRESS', 'Av. Comercio 123, Lima');
define('COMPANY_PHONE', '+51 999 888 777');

function db(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            DB_HOST,
            DB_PORT,
            DB_NAME,
            DB_CHARSET
        );

        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    }

    return $pdo;
}

function money(float $amount): string
{
    return APP_CURRENCY . ' ' . number_format($amount, 2, '.', ',');
}

function next_ticket_number(PDO $pdo): string
{
    $date = date('Ymd');
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM sales WHERE ticket_number LIKE ?'
    );
    $stmt->execute(["TKT-{$date}-%"]);
    $count = (int) $stmt->fetchColumn() + 1;

    return sprintf('TKT-%s-%04d', $date, $count);
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function get_flash(): ?array
{
    if (!isset($_SESSION['flash'])) {
        return null;
    }
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $flash;
}

function redirect(string $path): never
{
    header('Location: ' . $path);
    exit;
}
