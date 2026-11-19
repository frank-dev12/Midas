<?php
$pageTitle = 'Productos';
require_once __DIR__ . '/includes/header.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'save') {
        $id = (int) ($_POST['id'] ?? 0);
        $barcode = trim($_POST['barcode'] ?? '');
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $price = (float) ($_POST['price'] ?? 0);
        $cost = (float) ($_POST['cost'] ?? 0);
        $stock = (int) ($_POST['stock'] ?? 0);
        $minStock = (int) ($_POST['min_stock'] ?? 5);
        $active = isset($_POST['active']) ? 1 : 0;

        if ($barcode === '' || $name === '' || $price < 0) {
            flash('error', 'Completa código, nombre y precio válidos.');
            redirect('products.php');
        }

        try {
            if ($id > 0) {
                $stmt = $pdo->prepare(
                    'UPDATE products SET barcode=?, name=?, description=?, price=?, cost=?, min_stock=?, active=? WHERE id=?'
                );
                $stmt->execute([$barcode, $name, $description, $price, $cost, $minStock, $active, $id]);
                flash('success', 'Producto actualizado.');
            } else {
                $stmt = $pdo->prepare(
                    'INSERT INTO products (barcode, name, description, price, cost, stock, min_stock, active) VALUES (?,?,?,?,?,?,?,?)'
                );
                $stmt->execute([$barcode, $name, $description, $price, $cost, $stock, $minStock, $active]);
                $newId = (int) $pdo->lastInsertId();
                if ($stock > 0) {
                    $pdo->prepare(
                        'INSERT INTO stock_movements (product_id, type, quantity, previous_stock, new_stock, note) VALUES (?,\'in\',?,0,?,?)'
                    )->execute([$newId, $stock, $stock, 'Stock inicial']);
                }
                flash('success', 'Producto creado.');
            }
        } catch (PDOException $e) {
            flash('error', str_contains($e->getMessage(), 'Duplicate') || str_contains($e->getMessage(), 'UNIQUE')
                ? 'El código de barras ya existe.'
                : 'No se pudo guardar.');
        }
        redirect('products.php');
    }

    if ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        $pdo->prepare('UPDATE products SET active = 0 WHERE id = ?')->execute([$id]);
        flash('success', 'Producto desactivado.');
        redirect('products.php');
    }
}

$q = trim($_GET['q'] ?? '');
if ($q !== '') {
    $like = '%' . $q . '%';
    $stmt = $pdo->prepare(
        'SELECT * FROM products WHERE name LIKE ? OR barcode LIKE ? ORDER BY active DESC, name'
    );
    $stmt->execute([$like, $like]);
} else {
    $stmt = $pdo->query('SELECT * FROM products ORDER BY active DESC, name');
}
$products = $stmt->fetchAll();

$editId = (int) ($_GET['edit'] ?? 0);
$edit = null;
if ($editId > 0) {
    $s = $pdo->prepare('SELECT * FROM products WHERE id = ?');
    $s->execute([$editId]);
    $edit = $s->fetch() ?: null;
}
?>

<div class="mb-6 flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4">
    <div>
        <h1 class="text-3xl font-bold tracking-tight">Productos</h1>
        <p class="muted mt-1">Catálogo con código de barras y precios</p>
    </div>
    <form method="get" class="flex gap-2">
        <input type="search" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Buscar nombre o código…"
               class="field w-64">
        <button class="btn-ghost px-4 py-2.5 text-sm">Buscar</button>
    </form>
</div>

<div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
    <div class="panel p-5 xl:col-span-1 h-fit">
        <h2 class="text-lg font-semibold mb-4"><?= $edit ? 'Editar producto' : 'Nuevo producto' ?></h2>
        <form method="post" class="space-y-3">
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="id" value="<?= (int) ($edit['id'] ?? 0) ?>">

            <label class="block text-xs muted">Código de barras
                <input name="barcode" required value="<?= htmlspecialchars($edit['barcode'] ?? '') ?>" class="field mt-1 mono">
            </label>
            <label class="block text-xs muted">Nombre
                <input name="name" required value="<?= htmlspecialchars($edit['name'] ?? '') ?>" class="field mt-1">
            </label>
            <label class="block text-xs muted">Descripción / categoría
                <input name="description" value="<?= htmlspecialchars($edit['description'] ?? '') ?>" class="field mt-1">
            </label>
            <div class="grid grid-cols-2 gap-3">
                <label class="block text-xs muted">Precio venta
                    <input type="number" step="0.01" min="0" name="price" required value="<?= htmlspecialchars((string) ($edit['price'] ?? '')) ?>" class="field mt-1">
                </label>
                <label class="block text-xs muted">Costo
                    <input type="number" step="0.01" min="0" name="cost" value="<?= htmlspecialchars((string) ($edit['cost'] ?? '0')) ?>" class="field mt-1">
                </label>
            </div>
            <?php if (!$edit): ?>
            <label class="block text-xs muted">Stock inicial
                <input type="number" min="0" name="stock" value="0" class="field mt-1">
            </label>
            <?php endif; ?>
            <label class="block text-xs muted">Stock mínimo
                <input type="number" min="0" name="min_stock" value="<?= (int) ($edit['min_stock'] ?? 5) ?>" class="field mt-1">
            </label>
            <label class="flex items-center gap-2 text-sm pt-1">
                <input type="checkbox" name="active" value="1"
                       <?= !isset($edit['active']) || (int)$edit['active'] === 1 ? 'checked' : '' ?>>
                Activo
            </label>
            <div class="flex gap-2 pt-2">
                <button type="submit" class="btn-primary flex-1 py-2.5 text-sm">Guardar</button>
                <?php if ($edit): ?>
                <a href="products.php" class="btn-ghost px-4 py-2.5 text-sm inline-flex items-center">Cancelar</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <div class="panel overflow-hidden xl:col-span-2">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="text-left text-xs uppercase tracking-wider muted border-b divider">
                    <tr>
                        <th class="px-4 py-3">Código</th>
                        <th class="px-4 py-3">Producto</th>
                        <th class="px-4 py-3 text-right">Precio</th>
                        <th class="px-4 py-3 text-right">Stock</th>
                        <th class="px-4 py-3">Estado</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($products as $p): ?>
                    <tr class="border-t divider row-hover <?= !(int)$p['active'] ? 'opacity-50' : '' ?>">
                        <td class="px-4 py-3 mono text-xs muted"><?= htmlspecialchars($p['barcode']) ?></td>
                        <td class="px-4 py-3">
                            <p class="font-medium"><?= htmlspecialchars($p['name']) ?></p>
                            <p class="text-xs muted"><?= htmlspecialchars($p['description']) ?></p>
                        </td>
                        <td class="px-4 py-3 text-right"><?= money((float) $p['price']) ?></td>
                        <td class="px-4 py-3 text-right <?= (int)$p['stock'] <= (int)$p['min_stock'] ? 'text-warn' : '' ?>">
                            <?= (int) $p['stock'] ?>
                        </td>
                        <td class="px-4 py-3">
                            <span class="text-xs px-2 py-0.5 rounded-full <?= (int)$p['active'] ? 'badge-ok' : 'badge-off' ?>">
                                <?= (int)$p['active'] ? 'Activo' : 'Inactivo' ?>
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right whitespace-nowrap">
                            <a href="products.php?edit=<?= (int)$p['id'] ?>" class="link-accent text-xs">Editar</a>
                            <?php if ((int)$p['active']): ?>
                            <form method="post" class="inline ml-2" onsubmit="return confirm('¿Desactivar producto?')">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                                <button class="text-danger text-xs">Desactivar</button>
                            </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
