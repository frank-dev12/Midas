<?php
$pageTitle = 'Ventas';
require_once __DIR__ . '/includes/header.php';

$from = $_GET['from'] ?? date('Y-m-d', strtotime('-30 days'));
$to = $_GET['to'] ?? date('Y-m-d');

$stmt = $pdo->prepare(
    "SELECT * FROM sales WHERE date(created_at) BETWEEN ? AND ? ORDER BY id DESC"
);
$stmt->execute([$from, $to]);
$sales = $stmt->fetchAll();

$sum = $pdo->prepare(
    "SELECT COUNT(*) AS c, COALESCE(SUM(total),0) AS t FROM sales WHERE date(created_at) BETWEEN ? AND ?"
);
$sum->execute([$from, $to]);
$stats = $sum->fetch();
?>

<div class="mb-6 flex flex-col lg:flex-row lg:items-end lg:justify-between gap-4">
    <div>
        <h1 class="text-3xl font-bold tracking-tight">Ventas</h1>
        <p class="muted mt-1">Historial de tickets y boletas</p>
    </div>
    <form method="get" class="flex flex-wrap items-end gap-2">
        <label class="text-xs muted">Desde
            <input type="date" name="from" value="<?= htmlspecialchars($from) ?>" class="field mt-1 block">
        </label>
        <label class="text-xs muted">Hasta
            <input type="date" name="to" value="<?= htmlspecialchars($to) ?>" class="field mt-1 block">
        </label>
        <button class="btn-ghost px-4 py-2 text-sm">Filtrar</button>
    </form>
</div>

<div class="grid grid-cols-2 gap-4 mb-6 max-w-lg">
    <div class="panel p-4">
        <p class="text-xs muted uppercase tracking-wider">Tickets</p>
        <p class="text-2xl font-bold mt-1"><?= (int) $stats['c'] ?></p>
    </div>
    <div class="panel p-4">
        <p class="text-xs muted uppercase tracking-wider">Total</p>
        <p class="text-2xl font-bold text-accent mt-1"><?= money((float) $stats['t']) ?></p>
    </div>
</div>

<div class="panel overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="text-left text-xs uppercase tracking-wider muted border-b divider">
                <tr>
                    <th class="px-4 py-3">Ticket</th>
                    <th class="px-4 py-3">Fecha</th>
                    <th class="px-4 py-3">Cliente</th>
                    <th class="px-4 py-3">Pago</th>
                    <th class="px-4 py-3 text-right">Total</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody>
            <?php if (!$sales): ?>
                <tr><td colspan="6" class="px-4 py-10 text-center muted">Sin ventas en el rango</td></tr>
            <?php else: foreach ($sales as $s): ?>
                <tr class="border-t divider row-hover">
                    <td class="px-4 py-3 mono text-accent"><?= htmlspecialchars($s['ticket_number']) ?></td>
                    <td class="px-4 py-3 muted"><?= htmlspecialchars($s['created_at']) ?></td>
                    <td class="px-4 py-3"><?= htmlspecialchars($s['customer_name']) ?></td>
                    <td class="px-4 py-3 capitalize"><?= htmlspecialchars($s['payment_method']) ?></td>
                    <td class="px-4 py-3 text-right font-medium"><?= money((float) $s['total']) ?></td>
                    <td class="px-4 py-3 text-right">
                        <a href="ticket.php?id=<?= (int)$s['id'] ?>" class="text-xs link-accent">Ver boleta</a>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
