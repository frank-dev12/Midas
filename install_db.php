<?php
declare(strict_types=1);
/**
 * Instalador / verificación de conexión MySQL (midas_pos).
 * Úsalo una vez desde el navegador o CLI: php install_db.php
 */
require_once __DIR__ . '/config/database.php';

header('Content-Type: text/plain; charset=utf-8');

try {
    $pdo = db();
    $version = $pdo->query('SELECT VERSION()')->fetchColumn();
    $tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
    $products = (int) $pdo->query('SELECT COUNT(*) FROM products')->fetchColumn();

    echo "Conexión OK\n";
    echo "Host: " . DB_HOST . ':' . DB_PORT . "\n";
    echo "Base: " . DB_NAME . "\n";
    echo "Usuario: " . DB_USER . "\n";
    echo "MySQL: {$version}\n";
    echo "Tablas: " . implode(', ', $tables) . "\n";
    echo "Productos: {$products}\n";
} catch (Throwable $e) {
    http_response_code(500);
    echo "Error de conexión: " . $e->getMessage() . "\n\n";
    echo "Revisa config/database.php (DB_HOST, DB_USER, DB_PASS, DB_NAME).\n";
    echo "Importa sql/midas_pos.sql desde phpMyAdmin o:\n";
    echo "  mysql -u root -p < sql/midas_pos.sql\n";
    exit(1);
}
