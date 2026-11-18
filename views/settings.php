<?php

declare(strict_types=1);

$value = static function (string $key) use ($old, $settings): string {
    return array_key_exists($key, $old) ? (string) $old[$key] : (string) ($settings[$key] ?? '');
};
?>
<div class="mx-auto grid max-w-3xl gap-4">
    <div>
        <h1 class="text-3xl font-semibold tracking-tight">Ajustes</h1>
        <p class="mt-1 text-sm leading-6 text-muted">Estos datos salen en la boleta. El RUC de demostración no corresponde a un emisor electrónico.</p>
    </div>
    <form method="post" action="/ajustes" class="<?= card_class() ?> grid gap-4">
        <?= csrf_field() ?>
        <h2 class="text-lg font-semibold">Negocio</h2>
        <label>
            <span class="<?= label_class() ?>">Nombre</span>
            <input class="<?= field_class() ?>" name="business_name" required maxlength="80" value="<?= e($value('business_name')) ?>">
        </label>
        <div class="grid gap-4 sm:grid-cols-2">
            <label>
                <span class="<?= label_class() ?>">RUC</span>
                <input class="<?= field_class() ?>" name="ruc" inputmode="numeric" maxlength="11" value="<?= e($value('ruc')) ?>">
            </label>
            <label>
                <span class="<?= label_class() ?>">Teléfono</span>
                <input class="<?= field_class() ?>" name="phone" maxlength="40" value="<?= e($value('phone')) ?>">
            </label>
        </div>
        <label>
            <span class="<?= label_class() ?>">Dirección</span>
            <input class="<?= field_class() ?>" name="address" maxlength="140" value="<?= e($value('address')) ?>">
        </label>
        <div class="grid gap-4 sm:grid-cols-2">
            <label>
                <span class="<?= label_class() ?>">IGV incluido (%)</span>
                <input class="<?= field_class() ?>" name="igv" inputmode="decimal" required value="<?= e($value('igv')) ?>">
            </label>
            <label>
                <span class="<?= label_class() ?>">Serie de boleta</span>
                <input class="<?= field_class() ?> font-mono uppercase" name="series" required maxlength="4" value="<?= e($value('series')) ?>">
            </label>
        </div>
        <label>
            <span class="<?= label_class() ?>">Mensaje al pie</span>
            <input class="<?= field_class() ?>" name="ticket_footer" maxlength="180" value="<?= e($value('ticket_footer')) ?>">
        </label>
        <p class="text-sm leading-6 text-muted">Si cambias la serie, la siguiente boleta de esa serie empieza en 1. Las ya emitidas conservan su número.</p>
        <button class="<?= btn() ?>" type="submit">Guardar datos</button>
    </form>
    <form method="post" action="/clave" class="<?= card_class() ?> grid gap-4">
        <?= csrf_field() ?>
        <h2 class="text-lg font-semibold">Clave de la caja</h2>
        <label>
            <span class="<?= label_class() ?>">Clave actual</span>
            <input class="<?= field_class() ?>" type="password" name="current_password" autocomplete="current-password" required>
        </label>
        <label>
            <span class="<?= label_class() ?>">Nueva clave</span>
            <input class="<?= field_class() ?>" type="password" name="new_password" autocomplete="new-password" required minlength="6">
        </label>
        <label>
            <span class="<?= label_class() ?>">Confirmar clave</span>
            <input class="<?= field_class() ?>" type="password" name="confirm_password" autocomplete="new-password" required minlength="6">
        </label>
        <button class="<?= btn('ink') ?>" type="submit">Cambiar clave</button>
    </form>
</div>
