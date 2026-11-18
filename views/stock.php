<?php

declare(strict_types=1);

$type = (string) ($old['type'] ?? 'in');
$reason = (string) ($old['reason'] ?? '');
$detail = (string) ($old['detail'] ?? '');
$qty = (string) ($old['qty'] ?? '1');
$chosen = (int) ($old['product_id'] ?? $selected);
$scripts = ['/assets/filter.js'];
?>
<div class="mx-auto max-w-6xl">
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="text-3xl font-semibold tracking-tight">Stock</h1>
            <p class="mt-1 text-sm text-muted"><?= (int) $lowCount ?> producto<?= (int) $lowCount === 1 ? '' : 's' ?> en el mínimo o por debajo.</p>
        </div>
    </div>
    <div class="mt-5 grid gap-4 lg:grid-cols-[minmax(0,22rem)_minmax(0,1fr)] lg:items-start">
        <form method="post" action="/stock" class="<?= card_class() ?> grid gap-4">
            <?= csrf_field() ?>
            <h2 class="text-lg font-semibold">Entrada o salida</h2>
            <label>
                <span class="<?= label_class() ?>">Buscar producto</span>
                <input id="product-filter" class="<?= field_class() ?>" type="search" placeholder="Filtra la lista" autocomplete="off">
            </label>
            <label>
                <span class="<?= label_class() ?>">Producto</span>
                <select id="product_id" name="product_id" class="<?= field_class() ?>" required>
                    <option value="">Elige un producto</option>
                    <?php foreach ($products as $product): ?>
                        <option value="<?= (int) $product['id'] ?>"<?= $chosen === (int) $product['id'] ? ' selected' : '' ?>>
                            <?= e((string) $product['name']) ?> · <?= (int) $product['stock'] ?> ud.
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
            <fieldset>
                <legend class="<?= label_class() ?>">Movimiento</legend>
                <div class="grid grid-cols-2 gap-2">
                    <label class="choice flex min-h-11 items-center justify-center gap-2 rounded-xl border border-line bg-sand px-3 text-sm font-semibold">
                        <input type="radio" name="type" value="in" <?= $type === 'out' ? '' : 'checked' ?>>
                        Entrada
                    </label>
                    <label class="choice flex min-h-11 items-center justify-center gap-2 rounded-xl border border-line bg-sand px-3 text-sm font-semibold">
                        <input type="radio" name="type" value="out" <?= $type === 'out' ? 'checked' : '' ?>>
                        Salida
                    </label>
                </div>
            </fieldset>
            <label>
                <span class="<?= label_class() ?>">Cantidad</span>
                <input class="<?= field_class() ?>" name="qty" inputmode="numeric" required value="<?= e($qty) ?>">
            </label>
            <label>
                <span class="<?= label_class() ?>">Motivo</span>
                <select name="reason" class="<?= field_class() ?>" required>
                    <option value="">Elige un motivo</option>
                    <optgroup label="Entrada">
                        <?php foreach (stock_reasons('in') as $item): ?>
                            <option data-kind="in" value="<?= e($item) ?>"<?= $reason === $item ? ' selected' : '' ?>><?= e($item) ?></option>
                        <?php endforeach; ?>
                    </optgroup>
                    <optgroup label="Salida">
                        <?php foreach (stock_reasons('out') as $item): ?>
                            <option data-kind="out" value="<?= e($item) ?>"<?= $reason === $item ? ' selected' : '' ?>><?= e($item) ?></option>
                        <?php endforeach; ?>
                    </optgroup>
                </select>
            </label>
            <label>
                <span class="<?= label_class() ?>">Detalle <span class="font-normal text-muted">(opcional)</span></span>
                <input class="<?= field_class() ?>" name="detail" maxlength="120" value="<?= e($detail) ?>" placeholder="Proveedor, lote o nota">
            </label>
            <button class="<?= btn() ?>" type="submit">Registrar movimiento</button>
        </form>
        <section class="<?= card_class() ?> min-w-0">
            <h2 class="text-lg font-semibold">Movimientos</h2>
            <?php if (!$movements): ?>
                <p class="mt-4 text-sm text-muted">Aún no hay movimientos.</p>
            <?php else: ?>
                <ul class="mt-3 divide-y divide-line">
                    <?php foreach ($movements as $movement): ?>
                        <?php $incoming = $movement['type'] === 'in'; ?>
                        <li class="grid gap-1 py-3 sm:grid-cols-[7.5rem_minmax(0,1fr)_auto] sm:items-center sm:gap-3">
                            <p class="text-sm font-semibold <?= $incoming ? 'text-moss' : 'text-wine' ?>">
                                <?= e(movement_label((string) $movement['type'])) ?>
                                <?= $incoming ? '+' : '−' ?><?= (int) $movement['qty'] ?>
                            </p>
                            <div class="min-w-0">
                                <p class="truncate font-medium"><?= e((string) $movement['product_name']) ?></p>
                                <p class="truncate text-sm text-muted"><?= e((string) $movement['note']) ?></p>
                            </div>
                            <div class="text-sm text-muted sm:text-right">
                                <p><?= e(format_when((string) $movement['created_at'])) ?></p>
                                <p class="tabular-nums">Queda <?= (int) $movement['stock_after'] ?></p>
                                <?php if ($movement['sale_id']): ?>
                                    <a class="font-semibold text-copper" href="/ventas/<?= (int) $movement['sale_id'] ?>">Ver boleta</a>
                                <?php endif; ?>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>
    </div>
</div>
