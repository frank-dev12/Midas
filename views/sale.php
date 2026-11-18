<?php

declare(strict_types=1);

$customer = $sale['customer_name'] !== '' ? (string) $sale['customer_name'] : 'Público general';
$scripts = ['/assets/print.js'];
?>
<div class="mx-auto max-w-xl">
    <div class="no-print mb-4 flex flex-wrap gap-2">
        <a class="<?= btn('ghost') ?>" href="/ventas">Volver</a>
        <button id="print-ticket" class="<?= btn() ?>" type="button">Imprimir boleta</button>
        <a class="<?= btn('ghost') ?>" href="/caja">Nueva venta</a>
    </div>
    <article class="ticket-print mx-auto w-full max-w-[80mm] bg-white px-4 py-6 text-[13px] leading-5 text-ink shadow-sm">
        <header class="text-center">
            <h1 class="text-base font-semibold"><?= e((string) $sale['biz_name']) ?></h1>
            <?php if ($sale['biz_ruc'] !== ''): ?>
                <p>RUC <?= e((string) $sale['biz_ruc']) ?></p>
            <?php endif; ?>
            <?php if ($sale['biz_address'] !== ''): ?>
                <p><?= e((string) $sale['biz_address']) ?></p>
            <?php endif; ?>
            <?php if ($sale['biz_phone'] !== ''): ?>
                <p>Tel. <?= e((string) $sale['biz_phone']) ?></p>
            <?php endif; ?>
        </header>
        <div class="my-3 border-t border-dashed border-ink/30"></div>
        <p class="text-center font-semibold">BOLETA DE VENTA</p>
        <p class="text-center font-mono text-sm"><?= e((string) $sale['number']) ?></p>
        <p class="mt-2">Fecha: <?= e(format_when((string) $sale['created_at'])) ?></p>
        <?php if (!empty($sale['cashier'])): ?>
            <p>Cajero: <?= e((string) $sale['cashier']) ?></p>
        <?php endif; ?>
        <p>Cliente: <?= e($customer) ?></p>
        <?php if ($sale['customer_doc'] !== ''): ?>
            <p>Doc.: <?= e((string) $sale['customer_doc']) ?></p>
        <?php endif; ?>
        <div class="my-3 border-t border-dashed border-ink/30"></div>
        <ul>
            <?php foreach ($sale['items'] as $item): ?>
                <li class="mb-2">
                    <p><?= (int) $item['qty'] ?> <?= e((string) $item['name']) ?></p>
                    <p class="flex justify-between gap-3 font-mono text-xs">
                        <span><?= e(money((int) $item['unit_price_cents'])) ?></span>
                        <span><?= e(money((int) $item['line_total_cents'])) ?></span>
                    </p>
                </li>
            <?php endforeach; ?>
        </ul>
        <div class="my-3 border-t border-dashed border-ink/30"></div>
        <dl class="grid grid-cols-[1fr_auto] gap-x-3 gap-y-1">
            <dt>OP. GRAVADA</dt>
            <dd class="text-right font-mono"><?= e(money((int) $sale['subtotal_cents'])) ?></dd>
            <dt>IGV <?= e((string) $sale['igv_rate']) ?>%</dt>
            <dd class="text-right font-mono"><?= e(money((int) $sale['igv_cents'])) ?></dd>
            <dt class="font-semibold">TOTAL</dt>
            <dd class="text-right font-mono font-semibold"><?= e(money((int) $sale['total_cents'])) ?></dd>
        </dl>
        <div class="my-3 border-t border-dashed border-ink/30"></div>
        <dl class="grid grid-cols-[1fr_auto] gap-x-3 gap-y-1">
            <dt><?= e(strtoupper(payment_label((string) $sale['payment_method']))) ?></dt>
            <dd class="text-right font-mono"><?= e(money((int) $sale['paid_cents'])) ?></dd>
            <?php if ($sale['payment_method'] === 'efectivo'): ?>
                <dt>VUELTO</dt>
                <dd class="text-right font-mono"><?= e(money((int) $sale['change_cents'])) ?></dd>
            <?php endif; ?>
        </dl>
        <footer class="mt-4 text-center text-xs leading-5">
            <?php if ($sale['footer'] !== ''): ?>
                <p><?= e((string) $sale['footer']) ?></p>
            <?php endif; ?>
            <p class="mt-2">Documento interno de caja. No es un comprobante electrónico SUNAT.</p>
        </footer>
    </article>
</div>
