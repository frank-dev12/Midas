<?php
$pageTitle = 'Boleta';
require_once __DIR__ . '/includes/header.php';

$id = (int) ($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT * FROM sales WHERE id = ?');
$stmt->execute([$id]);
$sale = $stmt->fetch();

if (!$sale) {
    flash('error', 'Ticket no encontrado.');
    redirect('sales.php');
}

$items = $pdo->prepare('SELECT * FROM sale_items WHERE sale_id = ?');
$items->execute([$id]);
$lines = $items->fetchAll();
?>

<div class="no-print mb-4 sm:mb-6 flex flex-col gap-3">
    <div>
        <h1 class="text-2xl sm:text-3xl font-bold tracking-tight">Boleta / Ticket</h1>
        <p class="muted mt-1 mono text-sm"><?= htmlspecialchars($sale['ticket_number']) ?></p>
    </div>
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
        <button onclick="window.print()" class="btn-primary touch-target px-5 py-3 text-sm">Imprimir</button>
        <a href="pos.php" class="btn-ghost touch-target inline-flex items-center justify-center px-5 py-3 text-sm">Nueva venta</a>
        <a href="sales.php" class="btn-ghost touch-target inline-flex items-center justify-center px-5 py-3 text-sm">Historial</a>
    </div>
</div>

<div class="ticket-print panel max-w-md mx-auto p-6 sm:p-8 print:text-black print:bg-white print:border-0">
    <div class="text-center border-b border-dashed divider pb-4 mb-4">
        <p class="text-2xl font-bold brand print:text-black tracking-tight"><?= htmlspecialchars(COMPANY_NAME) ?></p>
        <p class="text-xs muted print:text-gray-600 mt-1">RUC <?= htmlspecialchars(COMPANY_RUC) ?></p>
        <p class="text-xs muted print:text-gray-600"><?= htmlspecialchars(COMPANY_ADDRESS) ?></p>
        <p class="text-xs muted print:text-gray-600"><?= htmlspecialchars(COMPANY_PHONE) ?></p>
        <p class="mt-3 mono text-sm font-semibold tracking-wide">BOLETA DE VENTA</p>
        <p class="mono text-accent print:text-black text-sm"><?= htmlspecialchars($sale['ticket_number']) ?></p>
    </div>

    <div class="text-xs space-y-1 mb-4">
        <p><span class="muted">Fecha:</span> <?= htmlspecialchars($sale['created_at']) ?></p>
        <p><span class="muted">Cliente:</span> <?= htmlspecialchars($sale['customer_name']) ?></p>
        <p><span class="muted">Pago:</span> <span class="capitalize"><?= htmlspecialchars($sale['payment_method']) ?></span></p>
    </div>

    <table class="w-full text-xs mb-4">
        <thead>
            <tr class="border-b border-dashed divider muted text-left">
                <th class="py-2 font-medium">Cant</th>
                <th class="py-2 font-medium">Descripción</th>
                <th class="py-2 font-medium text-right">Importe</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($lines as $line): ?>
            <tr class="border-b divider">
                <td class="py-2 align-top mono"><?= (int) $line['quantity'] ?></td>
                <td class="py-2">
                    <p><?= htmlspecialchars($line['product_name']) ?></p>
                    <p class="text-[10px] muted mono">
                        <?= htmlspecialchars($line['barcode']) ?> · <?= money((float) $line['unit_price']) ?>
                    </p>
                </td>
                <td class="py-2 text-right align-top mono"><?= money((float) $line['line_total']) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <div class="space-y-1 text-sm border-t border-dashed divider pt-3">
        <div class="flex justify-between muted">
            <span>Subtotal</span>
            <span class="mono"><?= money((float) $sale['subtotal']) ?></span>
        </div>
        <?php if ((float) $sale['discount'] > 0): ?>
        <div class="flex justify-between muted">
            <span>Descuento</span>
            <span class="mono">-<?= money((float) $sale['discount']) ?></span>
        </div>
        <?php endif; ?>
        <div class="flex justify-between font-bold text-lg pt-1">
            <span>TOTAL</span>
            <span class="mono text-accent print:text-black"><?= money((float) $sale['total']) ?></span>
        </div>
        <?php if ($sale['payment_method'] === 'efectivo'): ?>
        <div class="flex justify-between muted text-xs pt-2">
            <span>Pagado</span>
            <span class="mono"><?= money((float) $sale['amount_paid']) ?></span>
        </div>
        <div class="flex justify-between muted text-xs">
            <span>Vuelto</span>
            <span class="mono"><?= money((float) $sale['change_amount']) ?></span>
        </div>
        <?php endif; ?>
    </div>

    <p class="text-center text-xs muted mt-6 pt-4 border-t border-dashed divider">
        ¡Gracias por su compra!<br>
        Powered by <?= htmlspecialchars(APP_NAME) ?>
    </p>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
