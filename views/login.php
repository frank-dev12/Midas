<?php

declare(strict_types=1);
?>
<div class="mx-auto flex min-h-dvh w-full max-w-md flex-col justify-center px-4 py-10">
    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-copper">Punto de venta</p>
    <h1 class="mt-2 text-4xl font-semibold tracking-tight">Midas</h1>
    <p class="mt-2 text-sm leading-6 text-muted">Caja, stock y boletas para el mostrador. Los precios se cobran con IGV incluido.</p>
    <?php if (!empty($flashMessage)): ?>
        <div class="mt-5 rounded-xl border border-wine/30 bg-wine/10 px-4 py-3 text-sm text-wine" role="alert">
            <?= e((string) ($flashMessage['message'] ?? '')) ?>
        </div>
    <?php endif; ?>
    <form method="post" action="/login" class="mt-6 grid gap-4">
        <?= csrf_field() ?>
        <label class="block">
            <span class="<?= label_class() ?>">Usuario</span>
            <input class="<?= field_class() ?>" name="username" autocomplete="username" required maxlength="40" value="<?= e($username) ?>">
        </label>
        <label class="block">
            <span class="<?= label_class() ?>">Clave</span>
            <input class="<?= field_class() ?>" type="password" name="password" autocomplete="current-password" required>
        </label>
        <button class="<?= btn() ?> w-full" type="submit">Entrar a caja</button>
    </form>
    <p class="mt-5 text-sm leading-6 text-muted">Usuario inicial <span class="font-mono text-ink">caja</span>. Clave inicial <span class="font-mono text-ink">midas-caja</span>. Cámbiala en Ajustes.</p>
</div>
