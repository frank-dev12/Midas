<?php

declare(strict_types=1);

$scripts = ['/assets/print.js'];
?>
<div class="mx-auto max-w-6xl">
    <div class="no-print flex flex-wrap items-end justify-between gap-3">
        <div>
            <a class="text-sm font-semibold text-copper" href="/productos">Volver a productos</a>
            <h1 class="mt-2 text-3xl font-semibold tracking-tight">Etiquetas</h1>
            <p class="mt-1 max-w-xl text-sm leading-6 text-muted">Imprime los códigos y escanéalos con la cámara de la caja. Los EAN-13 internos empiezan con 200.</p>
        </div>
        <button id="print-ticket" class="<?= btn() ?>" type="button">Imprimir</button>
    </div>
    <div class="mt-5 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
        <?php foreach ($products as $product): ?>
            <article class="break-inside-avoid rounded-2xl border border-line bg-white p-3">
                <h2 class="min-h-10 text-sm font-semibold leading-snug"><?= e((string) $product['name']) ?></h2>
                <p class="mt-2 text-lg font-semibold tabular-nums"><?= e(money((int) $product['price_cents'])) ?></p>
                <img class="mt-2 h-auto w-full" src="/productos/<?= (int) $product['id'] ?>/codigo.svg" alt="Código <?= e((string) $product['barcode']) ?>">
            </article>
        <?php endforeach; ?>
    </div>
</div>
