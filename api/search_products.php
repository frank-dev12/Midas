<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/database.php';

$q = trim($_GET['q'] ?? '');
$pdo = db();

if ($q === '') {
    $stmt = $pdo->query(
        'SELECT id, barcode, name, price, stock FROM products WHERE active = 1 ORDER BY name LIMIT 30'
    );
} else {
    $like = '%' . $q . '%';
    $stmt = $pdo->prepare(
        'SELECT id, barcode, name, price, stock FROM products
         WHERE active = 1 AND (name LIKE ? OR barcode LIKE ?)
         ORDER BY name LIMIT 30'
    );
    $stmt->execute([$like, $like]);
}

$rows = array_map(static function ($r) {
    return [
        'id' => (int) $r['id'],
        'barcode' => $r['barcode'],
        'name' => $r['name'],
        'price' => (float) $r['price'],
        'stock' => (int) $r['stock'],
    ];
}, $stmt->fetchAll());

echo json_encode(['ok' => true, 'products' => $rows]);
