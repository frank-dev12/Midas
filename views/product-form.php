<?php

declare(strict_types=1);

$isNew = $product === null;
$action = $isNew ? '/productos' : '/productos/' . (int) $product['id'];
$value = static function (string $key, string $fallback) use ($old): string {
    return array_key_exists($key, $old) ? (string) $old[$key] : $fallback;
};
$name = $value('name', $isNew ? '' : (string) $product['name']);
$category = $value('category', $isNew ? '' : (string) $product['category']);
$barcode = $value('barcode', $isNew ? $prefillBarcode : (string) $product['barcode']);
$price = array_key_exists('price', $old)
    ? (string) $old['price']
    : ($product ? cents_to_input((int) $product['price_cents']) : '');
$cost = array_key_exists('cost', $old)
    ? (string) $old['cost']
    : ($product && (int) $product['cost_cents'] > 0 ? cents_to_input((int) $product['cost_cents']) : '');
$minStock = $value('min_stock', $product ? (string) $product['min_stock'] : '3');
$stock = $value('stock', '0');
?>
<div class="mx-auto max-w-2xl">
    <a class="text-sm font-semibold text-copper" href="/productos">Volver a productos</a>
    <h1 class="mt-2 text-3xl font-semibold tracking-tight"><?= e($title) ?></h1>
    <?php if (!$isNew): ?>
        <p class="mt-2 text-sm text-muted">El stock se mueve desde Stock para dejar constancia. Aquí solo cambian los datos del producto.</p>
    <?php endif; ?>
    <form method="post" action="<?= e($action) ?>" class="<?= card_class() ?> mt-5 grid gap-4">
        <?= csrf_field() ?>
        <label>
            <span class="<?= label_class() ?>">Nombre</span>
            <input class="<?= field_class() ?>" name="name" required maxlength="80" value="<?= e($name) ?>">
        </label>
        <label>
            <span class="<?= label_class() ?>">Categoría</span>
            <input class="<?= field_class() ?>" name="category" list="categories" maxlength="40" value="<?= e($category) ?>">
            <datalist id="categories">
                <?php foreach ($categories as $item): ?>
                    <option value="<?= e($item) ?>"></option>
                <?php endforeach; ?>
            </datalist>
        </label>
        <label>
            <span class="<?= label_class() ?>">Código de barras</span>
            <input class="<?= field_class() ?> font-mono" name="barcode" maxlength="48" value="<?= e($barcode) ?>" placeholder="Vacío para generar un EAN-13 interno" autocomplete="off" spellcheck="false">
        </label>
        <div class="grid gap-4 sm:grid-cols-2">
            <label>
                <span class="<?= label_class() ?>">Precio de venta</span>
                <input class="<?= field_class() ?> tabular-nums" name="price" inputmode="decimal" required value="<?= e($price) ?>" placeholder="0.00">
            </label>
            <label>
                <span class="<?= label_class() ?>">Costo <span class="font-normal text-muted">(opcional)</span></span>
                <input class="<?= field_class() ?> tabular-nums" name="cost" inputmode="decimal" value="<?= e($cost) ?>" placeholder="0.00">
            </label>
        </div>
        <div class="grid gap-4 sm:grid-cols-2">
            <?php if ($isNew): ?>
                <label>
                    <span class="<?= label_class() ?>">Stock inicial</span>
                    <input class="<?= field_class() ?>" name="stock" inputmode="numeric" required value="<?= e($stock) ?>">
                </label>
            <?php else: ?>
                <div>
                    <span class="<?= label_class() ?>">Stock actual</span>
                    <p class="flex min-h-11 items-center text-lg font-semibold tabular-nums"><?= (int) $product['stock'] ?> ud.</p>
                    <a class="text-sm font-semibold text-copper" href="/stock?producto=<?= (int) $product['id'] ?>">Registrar entrada o salida</a>
                </div>
            <?php endif; ?>
            <label>
                <span class="<?= label_class() ?>">Stock mínimo</span>
                <input class="<?= field_class() ?>" name="min_stock" inputmode="numeric" required value="<?= e($minStock) ?>">
            </label>
        </div>
        <p class="text-sm leading-6 text-muted">El precio incluye IGV. En la boleta se muestra la base y el impuesto por separado.</p>
        <button class="<?= btn() ?>" type="submit"><?= $isNew ? 'Crear producto' : 'Guardar cambios' ?></button>
    </form>
    <?php if (!$isNew): ?>
        <figure class="<?= card_class() ?> mt-4">
            <figcaption class="text-sm font-medium">Etiqueta</figcaption>
            <img class="mt-3 h-auto w-full max-w-xs bg-white" src="/productos/<?= (int) $product['id'] ?>/codigo.svg" alt="Código de barras <?= e((string) $product['barcode']) ?>">
        </figure>
        <form method="post" action="/productos/<?= (int) $product['id'] ?>/estado" class="mt-4">
            <?= csrf_field() ?>
            <?php if ((int) $product['active'] === 1): ?>
                <input type="hidden" name="active" value="0">
                <button class="<?= btn('danger') ?>" type="submit">Desactivar producto</button>
            <?php else: ?>
                <input type="hidden" name="active" value="1">
                <button class="<?= btn('ghost') ?>" type="submit">Activar producto</button>
            <?php endif; ?>
        </form>
    <?php endif; ?>
</div>
