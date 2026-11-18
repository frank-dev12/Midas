<?php

declare(strict_types=1);

$scripts = ['/assets/html5-qrcode.min.js', '/assets/pos.js'];
?>
<div id="pos" class="mx-auto max-w-7xl" data-igv="<?= e($igv) ?>">
    <div id="toast" class="pointer-events-none fixed bottom-24 left-1/2 z-50 hidden w-[min(24rem,calc(100%-2rem))] -translate-x-1/2 rounded-2xl bg-ink px-4 py-3 text-center text-sm text-white shadow-lg lg:bottom-8" role="status"></div>
    <noscript>
        <p class="mb-4 rounded-xl border border-wine/30 bg-wine/10 px-4 py-3 text-sm text-wine">La caja necesita JavaScript para armar la venta y usar la cámara.</p>
    </noscript>
    <?php if ($recentSale): ?>
        <div class="mb-4 rounded-2xl border border-moss/20 bg-moss-soft p-4" role="status">
            <p class="font-medium text-moss">Boleta <?= e((string) $recentSale['number']) ?> lista. Total <?= e(money((int) $recentSale['total_cents'])) ?><?php if ((int) $recentSale['change_cents'] > 0): ?>. Vuelto <?= e(money((int) $recentSale['change_cents'])) ?><?php endif; ?>.</p>
            <div class="mt-3 flex flex-wrap gap-2">
                <a class="<?= btn() ?>" href="/ventas/<?= (int) $recentSale['id'] ?>?print=1" target="_blank" rel="noopener">Imprimir boleta</a>
                <a class="<?= btn('ghost') ?>" href="/caja">Seguir vendiendo</a>
            </div>
        </div>
    <?php endif; ?>
    <div class="flex flex-col gap-4 lg:grid lg:grid-cols-[minmax(0,1fr)_22.5rem] lg:items-start">
        <form id="scan-form" class="order-1 lg:col-span-2" autocomplete="off">
            <div class="flex flex-wrap items-end justify-between gap-3">
                <div>
                    <h1 class="text-3xl font-semibold tracking-tight">Caja</h1>
                    <p class="mt-1 text-sm text-muted"><?= e($business) ?> · IGV <?= e($igv) ?>% incluido</p>
                </div>
                <button id="beep-toggle" class="<?= btn('ghost') ?>" type="button" aria-pressed="true">Sonido</button>
            </div>
            <label class="mt-4 block">
                <span class="<?= label_class() ?>">Código de barras</span>
                <span class="flex flex-col gap-2 sm:flex-row">
                    <input id="scan-input" class="<?= field_class() ?> font-mono sm:min-w-0 sm:flex-1" inputmode="text" autocomplete="off" autocapitalize="off" spellcheck="false" placeholder="Escanea, escribe o usa 2*código" autofocus>
                    <button id="open-scanner" class="<?= btn('ink') ?> w-full shrink-0 sm:w-auto" type="button">Cámara</button>
                    <button class="<?= btn() ?> w-full shrink-0 sm:w-auto" type="submit">Agregar</button>
                </span>
            </label>
            <p class="mt-2 text-sm text-muted">Un lector USB escribe en este campo y confirma con Enter. La cámara lee EAN, UPC y Code 128.</p>
        </form>

        <section id="ticket" class="order-2 rounded-2xl border border-line bg-sand p-4 shadow-sm lg:sticky lg:top-4 lg:order-3" aria-label="Boleta en curso">
            <div class="flex items-baseline justify-between gap-3">
                <h2 class="font-semibold">Boleta</h2>
                <p id="ticket-count" class="text-sm text-muted">0 productos</p>
            </div>
            <div id="ticket-lines" class="mt-2 max-h-[40dvh] overflow-y-auto lg:max-h-[48dvh]"></div>
            <dl class="mt-3 grid grid-cols-[1fr_auto] gap-x-3 gap-y-1 border-t border-dashed border-line pt-3 text-sm">
                <dt class="text-muted">Op. gravada</dt>
                <dd id="ticket-subtotal" class="text-right font-mono tabular-nums">S/ 0.00</dd>
                <dt id="ticket-igv-label" class="text-muted">IGV</dt>
                <dd id="ticket-igv" class="text-right font-mono tabular-nums">S/ 0.00</dd>
                <dt class="text-base font-semibold">Total</dt>
                <dd id="ticket-total" class="text-right font-mono text-base font-semibold tabular-nums">S/ 0.00</dd>
            </dl>
            <button id="pay-open" class="<?= btn() ?> mt-4 w-full" type="button" disabled>Cobrar</button>
        </section>

        <section class="order-3 min-w-0 lg:order-2 lg:max-h-[calc(100dvh-8rem)] lg:overflow-y-auto lg:pr-1" aria-label="Catálogo">
            <label class="block">
                <span class="<?= label_class() ?>">Catálogo</span>
                <input id="catalog-search" class="<?= field_class() ?>" type="search" placeholder="Filtra por nombre o código" autocomplete="off">
            </label>
            <p id="catalog-empty" class="mt-4 hidden text-sm text-muted">Ningún producto coincide.</p>
            <div id="catalog" class="mt-3 grid grid-cols-2 gap-2 sm:grid-cols-3 xl:grid-cols-4">
                <?php foreach ($products as $product): ?>
                    <?php
                    $search = mb_strtolower((string) $product['name'] . ' ' . $product['barcode'] . ' ' . $product['category']);
                    $disabled = (int) $product['stock'] <= 0;
                    ?>
                    <button
                        type="button"
                        class="add-product flex min-h-28 flex-col items-start rounded-2xl border border-line bg-panel p-3 text-left focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-copper disabled:opacity-50"
                        data-search="<?= e($search) ?>"
                        data-product="<?= e(json_encode(product_public($product), JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT)) ?>"
                        <?= $disabled ? 'disabled' : '' ?>
                    >
                        <span class="text-[11px] font-semibold uppercase tracking-wide text-muted"><?= e((string) ($product['category'] !== '' ? $product['category'] : 'General')) ?></span>
                        <span class="mt-1 font-semibold leading-snug"><?= e((string) $product['name']) ?></span>
                        <span class="mt-auto flex w-full items-end justify-between gap-2 pt-3">
                            <span class="font-semibold tabular-nums"><?= e(money((int) $product['price_cents'])) ?></span>
                            <span class="rounded-full px-2 py-1 text-[11px] font-semibold <?= stock_tone((int) $product['stock'], (int) $product['min_stock']) ?>"><?= (int) $product['stock'] ?></span>
                        </span>
                    </button>
                <?php endforeach; ?>
            </div>
        </section>
    </div>
</div>

<dialog id="pay-dialog" aria-labelledby="pay-title">
    <form id="pay-form" class="grid gap-4 p-5" action="/caja" method="post">
        <div class="flex items-start justify-between gap-3">
            <div>
                <h2 id="pay-title" class="text-xl font-semibold">Cobrar</h2>
                <p id="pay-total" class="mt-1 text-3xl font-semibold tabular-nums">S/ 0.00</p>
            </div>
            <button id="pay-close" class="grid h-11 w-11 place-items-center rounded-full text-2xl leading-none" type="button" aria-label="Cerrar">×</button>
        </div>
        <fieldset>
            <legend class="<?= label_class() ?>">Medio de pago</legend>
            <div id="pay-methods" class="grid grid-cols-2 gap-2">
                <?php foreach (payment_methods() as $method): ?>
                    <button class="pay-method min-h-11 rounded-xl border border-line bg-sand px-3 text-sm font-semibold" type="button" data-method="<?= e($method) ?>" aria-pressed="<?= $method === 'efectivo' ? 'true' : 'false' ?>"><?= e(payment_label($method)) ?></button>
                <?php endforeach; ?>
            </div>
        </fieldset>
        <div id="cash-box">
            <label for="paid" class="<?= label_class() ?>">Efectivo recibido</label>
            <input id="paid" class="<?= field_class() ?> text-lg tabular-nums" inputmode="decimal" autocomplete="off" placeholder="0.00">
            <div class="mt-2 flex flex-wrap gap-2">
                <button class="min-h-11 rounded-full border border-line px-3 text-sm font-semibold" type="button" data-bill="exact">Exacto</button>
                <?php foreach ([10, 20, 50, 100, 200] as $bill): ?>
                    <button class="min-h-11 rounded-full border border-line px-3 text-sm font-semibold tabular-nums" type="button" data-bill="<?= $bill ?>"><?= $bill ?></button>
                <?php endforeach; ?>
            </div>
            <p id="change" class="mt-3 text-lg font-semibold tabular-nums"></p>
        </div>
        <details>
            <summary class="min-h-11 cursor-pointer text-sm font-semibold">Cliente (opcional)</summary>
            <div class="mt-3 grid gap-3">
                <label>
                    <span class="<?= label_class() ?>">Nombre</span>
                    <input id="customer-name" class="<?= field_class() ?>" maxlength="120" autocomplete="name">
                </label>
                <label>
                    <span class="<?= label_class() ?>">DNI o RUC</span>
                    <input id="customer-doc" class="<?= field_class() ?>" maxlength="15" inputmode="text" autocapitalize="characters" autocomplete="off">
                </label>
            </div>
        </details>
        <button id="pay-confirm" class="<?= btn() ?> w-full" type="submit">Confirmar boleta</button>
        <p id="pay-error" class="hidden text-sm text-wine" role="alert"></p>
    </form>
</dialog>

<div id="scanner" class="fixed inset-0 z-50 hidden h-dvh flex-col bg-ink text-white" role="dialog" aria-modal="true" aria-labelledby="scanner-title">
    <div class="flex items-center justify-between gap-3 px-4 pt-[max(1rem,env(safe-area-inset-top))]">
        <h2 id="scanner-title" class="text-lg font-semibold">Lector de barras</h2>
        <button id="scanner-close" class="min-h-11 rounded-full px-4 text-sm font-semibold" type="button">Cerrar</button>
    </div>
    <div class="min-h-0 flex-1 overflow-y-auto px-4 pb-[max(1rem,env(safe-area-inset-bottom))]">
        <div id="reader" class="mt-4"></div>
        <div id="reader-file" class="hidden"></div>
        <p id="scanner-status" class="mt-3 text-sm leading-6 text-white/80">Apunta la cámara al código. Mantén el trazo horizontal y con luz.</p>
        <button id="scanner-file-button" class="mt-4 min-h-11 rounded-xl border border-white/30 px-4 text-sm font-semibold" type="button">Elegir una foto del código</button>
        <input id="scanner-file" class="sr-only" type="file" accept="image/*" capture="environment">
    </div>
</div>
