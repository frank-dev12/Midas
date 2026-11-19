<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/database.php';

$barcode = trim($_GET['barcode'] ?? '');

if ($barcode === '') {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Código vacío']);
    exit;
}

$pdo = db();
$stmt = $pdo->prepare(
    'SELECT id, barcode, name, price, stock, active FROM products WHERE barcode = ? LIMIT 1'
);
$stmt->execute([$barcode]);
$product = $stmt->fetch();

if (!$product) {
    http_response_code(404);
    echo json_encode(['ok' => false, 'error' => 'Producto no encontrado']);
    exit;
}

if (!(int) $product['active']) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Producto inactivo']);
    exit;
}

echo json_encode([
    'ok' => true,
    'product' => [
        'id' => (int) $product['id'],
        'barcode' => $product['barcode'],
        'name' => $product['name'],
        'price' => (float) $product['price'],
        'stock' => (int) $product['stock'],
    ],
]);
