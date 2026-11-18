<?php

declare(strict_types=1);
?>
<div class="mx-auto max-w-6xl">
    <h1 class="text-3xl font-semibold tracking-tight">Boletas</h1>
    <p class="mt-1 text-sm text-muted">Comprobantes internos de caja. No reemplazan una boleta electrónica de SUNAT.</p>
    <form method="get" action="/ventas" class="mt-5 flex flex-col gap-2 sm:flex-row">
        <label class="sr-only" for="q">Buscar boletas</label>
        <input id="q" class="<?= field_class() ?>" type="search" name="q" value="<?= e($query) ?>" placeholder="Número, cliente o documento">
        <button class="<?= btn('ink') ?>" type="submit">Buscar</button>
    </form>
    <?php if (!$sales): ?>
        <p class="mt-8 text-sm text-muted">No hay boletas para mostrar.</p>
    <?php else: ?>
        <ul class="mt-4 grid gap-3">
            <?php foreach ($sales as $sale): ?>
                <li>
                    <a class="<?= card_class() ?> flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between" href="/ventas/<?= (int) $sale['id'] ?>">
                        <span class="min-w-0">
                            <span class="block font-mono text-sm font-semibold"><?= e((string) $sale['number']) ?></span>
                            <span class="mt-1 block text-sm text-muted">
                                <?= e(format_when((string) $sale['created_at'])) ?>
                                · <?= e(payment_label((string) $sale['payment_method'])) ?>
                                <?php if ($sale['customer_name'] !== ''): ?>
                                    · <?= e((string) $sale['customer_name']) ?>
                                <?php endif; ?>
                            </span>
                        </span>
                        <span class="text-lg font-semibold tabular-nums"><?= e(money((int) $sale['total_cents'])) ?></span>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
        <div class="mt-4 flex gap-2">
            <?php if ($page > 1): ?>
                <a class="<?= btn('ghost') ?>" href="/ventas?q=<?= rawurlencode($query) ?>&amp;p=<?= $page - 1 ?>">Anterior</a>
            <?php endif; ?>
            <?php if (count($sales) === 30): ?>
                <a class="<?= btn('ghost') ?>" href="/ventas?q=<?= rawurlencode($query) ?>&amp;p=<?= $page + 1 ?>">Siguiente</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>
