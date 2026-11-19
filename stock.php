<?php
$pageTitle = 'Stock';
require_once __DIR__ . '/includes/header.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $productId = (int) ($_POST['product_id'] ?? 0);
    $type = $_POST['type'] ?? '';
    $quantity = abs((int) ($_POST['quantity'] ?? 0));
    $note = trim($_POST['note'] ?? '');

    if (!in_array($type, ['in', 'out', 'adjust'], true) || $productId <= 0 || $quantity <= 0) {
        flash('error', 'Datos de movimiento inválidos.');
        redirect('stock.php');
    }

    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare('SELECT id, name, stock FROM products WHERE id = ?');
        $stmt->execute([$productId]);
        $product = $stmt->fetch();

        if (!$product) {
            throw new RuntimeException('Producto no encontrado.');
        }

        $prev = (int) $product['stock'];

        if ($type === 'in') {
            $new = $prev + $quantity;
        } elseif ($type === 'out') {
            if ($quantity > $prev) {
                throw new RuntimeException('No hay suficiente stock para la salida.');
            }
            $new = $prev - $quantity;
        } else {
            $new = $quantity;
            $quantity = abs($new - $prev);
        }

        $pdo->prepare('UPDATE products SET stock = ? WHERE id = ?')->execute([$new, $productId]);
        $pdo->prepare(
            'INSERT INTO stock_movements (product_id, type, quantity, previous_stock, new_stock, note)
             VALUES (?,?,?,?,?,?)'
        )->execute([$productId, $type, $quantity, $prev, $new, $note]);

        $pdo->commit();
        $labels = ['in' => 'Entrada', 'out' => 'Salida', 'adjust' => 'Ajuste'];
        flash('success', $labels[$type] . ' registrada para ' . $product['name'] . '. Nuevo stock: ' . $new);
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        flash('error', $e->getMessage());
    }
    redirect('stock.php');
}

$products = $pdo->query(
    'SELECT id, barcode, name, stock, min_stock FROM products WHERE active = 1 ORDER BY name'
)->fetchAll();

$movements = $pdo->query(
    "SELECT m.*, p.name AS product_name, p.barcode
     FROM stock_movements m
     JOIN products p ON p.id = m.product_id
     ORDER BY m.id DESC LIMIT 40"
)->fetchAll();

$typeLabels = [
    'in' => ['Entrada', 'badge-in'],
    'out' => ['Salida', 'badge-out'],
    'sale' => ['Venta', 'badge-sale'],
    'adjust' => ['Ajuste', 'badge-adjust'],
];
?>

<div class="mb-6">
    <h1 class="text-3xl font-bold tracking-tight">Stock</h1>
    <p class="muted mt-1">Entradas, salidas y movimientos de inventario</p>
</div>

<div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
    <div class="panel p-5 h-fit">
        <h2 class="text-lg font-semibold mb-4">Registrar movimiento</h2>
        <form method="post" class="space-y-3">
            <label class="block text-xs muted">Producto
                <select name="product_id" required class="field mt-1">
                    <option value="">Seleccionar…</option>
                    <?php foreach ($products as $p): ?>
                    <option value="<?= (int)$p['id'] ?>">
                        <?= htmlspecialchars($p['name']) ?> — stock <?= (int)$p['stock'] ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label class="block text-xs muted">Tipo
                <select name="type" required class="field mt-1">
                    <option value="in">Entrada (+)</option>
                    <option value="out">Salida (−)</option>
                    <option value="adjust">Ajuste (definir stock exacto)</option>
                </select>
            </label>
            <label class="block text-xs muted">Cantidad / stock objetivo
                <input type="number" name="quantity" min="1" required value="1" class="field mt-1">
            </label>
            <label class="block text-xs muted">Nota
                <input name="note" placeholder="Compra, merma, inventario…" class="field mt-1">
            </label>
            <button type="submit" class="btn-primary w-full py-2.5 text-sm">Guardar movimiento</button>
        </form>

        <div class="mt-6 pt-5 border-t divider">
            <h3 class="text-sm font-medium mb-3">Inventario actual</h3>
            <ul class="space-y-2 max-h-72 overflow-y-auto text-sm">
                <?php foreach ($products as $p): ?>
                <li class="flex justify-between gap-2">
                    <span class="truncate muted"><?= htmlspecialchars($p['name']) ?></span>
                    <span class="mono <?= (int)$p['stock'] <= (int)$p['min_stock'] ? 'text-warn' : '' ?>">
                        <?= (int)$p['stock'] ?>
                    </span>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>

    <div class="panel overflow-hidden xl:col-span-2">
        <div class="px-5 py-4 border-b divider">
            <h2 class="text-lg font-semibold">Historial de movimientos</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="text-left text-xs uppercase tracking-wider muted">
                    <tr>
                        <th class="px-4 py-3">Fecha</th>
                        <th class="px-4 py-3">Producto</th>
                        <th class="px-4 py-3">Tipo</th>
                        <th class="px-4 py-3 text-right">Cant.</th>
                        <th class="px-4 py-3 text-right">Antes → Después</th>
                        <th class="px-4 py-3">Ref / Nota</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($movements as $m):
                    [$label, $cls] = $typeLabels[$m['type']] ?? ['?', 'badge-off'];
                ?>
                    <tr class="border-t divider">
                        <td class="px-4 py-3 muted whitespace-nowrap"><?= htmlspecialchars($m['created_at']) ?></td>
                        <td class="px-4 py-3">
                            <p><?= htmlspecialchars($m['product_name']) ?></p>
                            <p class="text-xs mono muted"><?= htmlspecialchars($m['barcode']) ?></p>
                        </td>
                        <td class="px-4 py-3">
                            <span class="text-xs px-2 py-0.5 rounded-full <?= $cls ?>"><?= $label ?></span>
                        </td>
                        <td class="px-4 py-3 text-right mono"><?= (int)$m['quantity'] ?></td>
                        <td class="px-4 py-3 text-right mono muted">
                            <?= (int)$m['previous_stock'] ?> → <?= (int)$m['new_stock'] ?>
                        </td>
                        <td class="px-4 py-3 muted text-xs">
                            <?= htmlspecialchars(trim(($m['reference'] ?? '') . ' ' . ($m['note'] ?? ''))) ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
