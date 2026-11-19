<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
session_start();
require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Método no permitido']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'JSON inválido']);
    exit;
}

$items = $input['items'] ?? [];
$paymentMethod = $input['payment_method'] ?? 'efectivo';
$amountPaid = (float) ($input['amount_paid'] ?? 0);
$discount = max(0, (float) ($input['discount'] ?? 0));
$customer = trim($input['customer_name'] ?? 'Cliente general') ?: 'Cliente general';
$notes = trim($input['notes'] ?? '');

if (!is_array($items) || count($items) === 0) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'El carrito está vacío']);
    exit;
}

$allowedPayments = ['efectivo', 'tarjeta', 'yape', 'plin', 'transferencia'];
if (!in_array($paymentMethod, $allowedPayments, true)) {
    $paymentMethod = 'efectivo';
}

$pdo = db();

try {
    $pdo->beginTransaction();

    $subtotal = 0.0;
    $resolved = [];

    foreach ($items as $item) {
        $productId = (int) ($item['id'] ?? 0);
        $qty = (int) ($item['qty'] ?? 0);

        if ($productId <= 0 || $qty <= 0) {
            throw new RuntimeException('Ítem inválido en el carrito');
        }

        $stmt = $pdo->prepare(
            'SELECT id, barcode, name, price, stock, active FROM products WHERE id = ?'
        );
        $stmt->execute([$productId]);
        $product = $stmt->fetch();

        if (!$product || !(int) $product['active']) {
            throw new RuntimeException('Producto no disponible');
        }

        if ((int) $product['stock'] < $qty) {
            throw new RuntimeException(
                'Stock insuficiente para: ' . $product['name'] . ' (disponible: ' . $product['stock'] . ')'
            );
        }

        $lineTotal = (float) $product['price'] * $qty;
        $subtotal += $lineTotal;
        $resolved[] = [
            'product' => $product,
            'qty' => $qty,
            'line_total' => $lineTotal,
        ];
    }

    $discount = min($discount, $subtotal);
    $total = $subtotal - $discount;

    if ($paymentMethod === 'efectivo' && $amountPaid < $total) {
        throw new RuntimeException('El monto pagado es insuficiente');
    }

    if ($paymentMethod !== 'efectivo') {
        $amountPaid = $total;
    }

    $change = max(0, $amountPaid - $total);
    $ticket = next_ticket_number($pdo);

    $stmt = $pdo->prepare(
        'INSERT INTO sales (ticket_number, subtotal, discount, tax, total, payment_method, amount_paid, change_amount, customer_name, notes)
         VALUES (?,?,?,0,?,?,?,?,?,?)'
    );
    $stmt->execute([
        $ticket,
        $subtotal,
        $discount,
        $total,
        $paymentMethod,
        $amountPaid,
        $change,
        $customer,
        $notes,
    ]);
    $saleId = (int) $pdo->lastInsertId();

    $itemStmt = $pdo->prepare(
        'INSERT INTO sale_items (sale_id, product_id, barcode, product_name, quantity, unit_price, line_total)
         VALUES (?,?,?,?,?,?,?)'
    );
    $stockStmt = $pdo->prepare('UPDATE products SET stock = ? WHERE id = ?');
    $movStmt = $pdo->prepare(
        'INSERT INTO stock_movements (product_id, type, quantity, previous_stock, new_stock, note, reference)
         VALUES (?,\'sale\',?,?,?,?,?)'
    );

    foreach ($resolved as $row) {
        $p = $row['product'];
        $prev = (int) $p['stock'];
        $new = $prev - $row['qty'];

        $itemStmt->execute([
            $saleId,
            $p['id'],
            $p['barcode'],
            $p['name'],
            $row['qty'],
            $p['price'],
            $row['line_total'],
        ]);
        $stockStmt->execute([$new, $p['id']]);
        $movStmt->execute([
            $p['id'],
            $row['qty'],
            $prev,
            $new,
            'Venta',
            $ticket,
        ]);
    }

    $pdo->commit();

    echo json_encode([
        'ok' => true,
        'sale_id' => $saleId,
        'ticket_number' => $ticket,
        'total' => $total,
        'change' => $change,
        'redirect' => 'ticket.php?id=' . $saleId,
    ]);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
