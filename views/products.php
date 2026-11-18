<?php

declare(strict_types=1);
?>
<div class="mx-auto max-w-6xl">
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="text-3xl font-semibold tracking-tight">Productos</h1>
            <p class="mt-1 text-sm text-muted">El código de barras identifica cada producto en la caja.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a class="<?= btn('ghost') ?>" href="/etiquetas">Etiquetas</a>
            <a class="<?= btn() ?>" href="/productos/nuevo">Nuevo producto</a>
        </div>
    </div>
    <form method="get" action="/productos" class="mt-5 flex flex-col gap-2 sm:flex-row">
        <label class="sr-only" for="q">Buscar productos</label>
        <input id="q" class="<?= field_class() ?>" type="search" name="q" value="<?= e($query) ?>" placeholder="Nombre, categoría o código">
        <button class="<?= btn('ink') ?>" type="submit">Buscar</button>
    </form>
    <?php if (!$products): ?>
        <p class="mt-8 text-sm text-muted">No hay productos con esa búsqueda.</p>
    <?php else: ?>
        <ul class="mt-4 grid gap-3">
            <?php foreach ($products as $product): ?>
                <li class="<?= card_class() ?> flex flex-col gap-3 sm:flex-row sm:items-center">
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <h2 class="font-semibold leading-snug"><?= e((string) $product['name']) ?></h2>
                            <?php if (!(int) $product['active']): ?>
                                <span class="rounded-full bg-ink/5 px-2 py-0.5 text-xs font-semibold text-muted">Inactivo</span>
                            <?php endif; ?>
                        </div>
                        <p class="mt-1 text-sm text-muted"><?= e((string) ($product['category'] !== '' ? $product['category'] : 'Sin categoría')) ?></p>
                        <p class="mt-1 font-mono text-xs text-muted"><?= e((string) $product['barcode']) ?></p>
                    </div>
                    <div class="flex items-center justify-between gap-4 sm:justify-end">
                        <span class="rounded-full px-2.5 py-1 text-xs font-semibold <?= stock_tone((int) $product['stock'], (int) $product['min_stock']) ?>">
                            <?= e(stock_label((int) $product['stock'], (int) $product['min_stock'])) ?> · <?= (int) $product['stock'] ?>
                        </span>
                        <p class="text-lg font-semibold tabular-nums"><?= e(money((int) $product['price_cents'])) ?></p>
                    </div>
                    <div class="flex gap-2">
                        <a class="<?= btn('ghost') ?>" href="/productos/<?= (int) $product['id'] ?>/editar">Editar</a>
                        <a class="<?= btn('ghost') ?>" href="/stock?producto=<?= (int) $product['id'] ?>">Stock</a>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div>
