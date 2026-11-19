<?php
$pageTitle = 'Dashboard';
require_once __DIR__ . '/includes/header.php';

$today = date('Y-m-d');
$salesToday = $pdo->prepare("SELECT COUNT(*) AS c, COALESCE(SUM(total),0) AS t FROM sales WHERE date(created_at) = ?");
$salesToday->execute([$today]);
$todayStats = $salesToday->fetch();

$totalProducts = (int) $pdo->query('SELECT COUNT(*) FROM products WHERE active = 1')->fetchColumn();
$lowStock = (int) $pdo->query('SELECT COUNT(*) FROM products WHERE active = 1 AND stock <= min_stock')->fetchColumn();
$totalStockValue = (float) $pdo->query('SELECT COALESCE(SUM(stock * cost),0) FROM products WHERE active = 1')->fetchColumn();

$recentSales = $pdo->query(
    'SELECT id, ticket_number, total, payment_method, created_at FROM sales ORDER BY id DESC LIMIT 8'
)->fetchAll();

$lowProducts = $pdo->query(
    'SELECT name, stock, min_stock, barcode FROM products WHERE active = 1 AND stock <= min_stock ORDER BY stock ASC LIMIT 6'
)->fetchAll();
?>

<div class="mb-8">
    <h1 class="text-3xl sm:text-4xl font-bold tracking-tight">Dashboard</h1>
    <p class="muted mt-1">Resumen operativo de <?= htmlspecialchars(COMPANY_NAME) ?></p>
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 mb-8">
    <div class="panel p-5">
        <p class="text-xs uppercase tracking-wider muted">Ventas hoy</p>
        <p class="stat-value text-3xl font-bold text-accent mt-2"><?= money((float) $todayStats['t']) ?></p>
        <p class="text-sm muted mt-1"><?= (int) $todayStats['c'] ?> tickets</p>
    </div>
    <div class="panel p-5">
        <p class="text-xs uppercase tracking-wider muted">Productos activos</p>
        <p class="stat-value text-3xl font-bold mt-2"><?= $totalProducts ?></p>
        <p class="text-sm muted mt-1">En catálogo</p>
    </div>
    <div class="panel p-5">
        <p class="text-xs uppercase tracking-wider muted">Stock bajo</p>
        <p class="stat-value text-3xl font-bold <?= $lowStock > 0 ? 'text-danger' : 'text-success' ?> mt-2"><?= $lowStock ?></p>
        <p class="text-sm muted mt-1">Requieren reposición</p>
    </div>
    <div class="panel p-5">
        <p class="text-xs uppercase tracking-wider muted">Valor inventario</p>
        <p class="stat-value text-3xl font-bold mt-2"><?= money($totalStockValue) ?></p>
        <p class="text-sm muted mt-1">A costo</p>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2 panel overflow-hidden">
        <div class="px-5 py-4 border-b divider flex items-center justify-between">
            <h2 class="text-lg font-semibold">Últimas ventas</h2>
            <a href="sales.php" class="text-sm link-accent">Ver todas</a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="text-left muted text-xs uppercase tracking-wider">
                    <tr>
                        <th class="px-5 py-3 font-medium">Ticket</th>
                        <th class="px-5 py-3 font-medium">Fecha</th>
                        <th class="px-5 py-3 font-medium">Pago</th>
                        <th class="px-5 py-3 font-medium text-right">Total</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (!$recentSales): ?>
                    <tr><td colspan="4" class="px-5 py-8 text-center muted">Aún no hay ventas</td></tr>
                <?php else: foreach ($recentSales as $s): ?>
                    <tr class="border-t divider row-hover">
                        <td class="px-5 py-3">
                            <a href="ticket.php?id=<?= (int) $s['id'] ?>" class="mono link-accent"><?= htmlspecialchars($s['ticket_number']) ?></a>
                        </td>
                        <td class="px-5 py-3 muted"><?= htmlspecialchars($s['created_at']) ?></td>
                        <td class="px-5 py-3 capitalize"><?= htmlspecialchars($s['payment_method']) ?></td>
                        <td class="px-5 py-3 text-right font-medium"><?= money((float) $s['total']) ?></td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="panel overflow-hidden">
        <div class="px-5 py-4 border-b divider">
            <h2 class="text-lg font-semibold">Alertas de stock</h2>
        </div>
        <ul class="divide-y divider">
            <?php if (!$lowProducts): ?>
                <li class="px-5 py-8 text-center muted text-sm">Todo en orden</li>
            <?php else: foreach ($lowProducts as $p): ?>
                <li class="px-5 py-3.5">
                    <p class="text-sm font-medium"><?= htmlspecialchars($p['name']) ?></p>
                    <p class="text-xs muted mono mt-0.5"><?= htmlspecialchars($p['barcode']) ?></p>
                    <p class="text-xs mt-1 <?= (int)$p['stock'] === 0 ? 'text-danger' : 'text-warn' ?>">
                        Stock: <?= (int) $p['stock'] ?> / mín <?= (int) $p['min_stock'] ?>
                    </p>
                </li>
            <?php endforeach; endif; ?>
        </ul>
        <div class="p-4 border-t divider">
            <a href="pos.php" class="btn-primary block w-full text-center py-3">
                Ir a vender
            </a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
