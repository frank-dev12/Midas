<?php

declare(strict_types=1);

$byMethod = $summary['by_method'];
?>
<div class="mx-auto max-w-6xl">
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <p class="text-sm text-muted"><?= e($dateLabel) ?></p>
            <h1 class="mt-1 text-3xl font-semibold tracking-tight">Hoy en caja</h1>
        </div>
        <a class="<?= btn() ?>" href="/caja">Ir a caja</a>
    </div>
    <div class="mt-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        <article class="<?= card_class() ?>">
            <p class="text-sm text-muted">Boletas</p>
            <p class="mt-2 text-3xl font-semibold tabular-nums"><?= (int) $summary['tickets'] ?></p>
        </article>
        <article class="<?= card_class() ?>">
            <p class="text-sm text-muted">Vendido</p>
            <p class="mt-2 text-3xl font-semibold tabular-nums"><?= e(money((int) $summary['total'])) ?></p>
        </article>
        <article class="<?= card_class() ?>">
            <p class="text-sm text-muted">Efectivo</p>
            <p class="mt-2 text-3xl font-semibold tabular-nums"><?= e(money((int) ($byMethod['efectivo']['total'] ?? 0))) ?></p>
        </article>
        <article class="<?= card_class() ?>">
            <p class="text-sm text-muted">Stock bajo</p>
            <p class="mt-2 text-3xl font-semibold tabular-nums"><?= (int) $lowCount ?></p>
        </article>
    </div>
    <section class="<?= card_class() ?> mt-4">
        <h2 class="text-lg font-semibold">Cobros de hoy</h2>
        <dl class="mt-3 grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
            <?php foreach (payment_methods() as $method): ?>
                <div class="rounded-xl bg-sand px-3 py-3">
                    <dt class="text-sm text-muted"><?= e(payment_label($method)) ?></dt>
                    <dd class="mt-1 font-semibold tabular-nums"><?= e(money((int) ($byMethod[$method]['total'] ?? 0))) ?></dd>
                </div>
            <?php endforeach; ?>
        </dl>
    </section>
    <div class="mt-4 grid gap-4 lg:grid-cols-2">
        <section class="<?= card_class() ?>">
            <div class="flex items-center justify-between gap-3">
                <h2 class="text-lg font-semibold">Para reponer</h2>
                <a class="text-sm font-semibold text-copper" href="/stock">Mover stock</a>
            </div>
            <?php if (!$low): ?>
                <p class="mt-4 text-sm text-muted">Ningún producto está en el mínimo.</p>
            <?php else: ?>
                <ul class="mt-3 divide-y divide-line">
                    <?php foreach ($low as $product): ?>
                        <li class="flex items-center justify-between gap-3 py-3">
                            <div class="min-w-0">
                                <p class="truncate font-medium"><?= e((string) $product['name']) ?></p>
                                <p class="text-sm text-muted">Mínimo <?= (int) $product['min_stock'] ?></p>
                            </div>
                            <span class="rounded-full px-2.5 py-1 text-xs font-semibold <?= stock_tone((int) $product['stock'], (int) $product['min_stock']) ?>"><?= (int) $product['stock'] ?> ud.</span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>
        <section class="<?= card_class() ?>">
            <div class="flex items-center justify-between gap-3">
                <h2 class="text-lg font-semibold">Últimas boletas</h2>
                <a class="text-sm font-semibold text-copper" href="/ventas">Ver todas</a>
            </div>
            <?php if (!$sales): ?>
                <p class="mt-4 text-sm text-muted">Todavía no hay ventas.</p>
            <?php else: ?>
                <ul class="mt-3 divide-y divide-line">
                    <?php foreach (array_slice($sales, 0, 6) as $sale): ?>
                        <li>
                            <a class="flex items-center justify-between gap-3 py-3 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-copper" href="/ventas/<?= (int) $sale['id'] ?>">
                                <span class="min-w-0">
                                    <span class="block font-mono text-sm"><?= e((string) $sale['number']) ?></span>
                                    <span class="block text-sm text-muted"><?= e(format_when((string) $sale['created_at'])) ?> · <?= e(payment_label((string) $sale['payment_method'])) ?></span>
                                </span>
                                <span class="font-semibold tabular-nums"><?= e(money((int) $sale['total_cents'])) ?></span>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>
    </div>
</div>
