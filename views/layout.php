<?php

declare(strict_types=1);

$user = $user ?? current_user();
$businessName = setting('business_name', 'Midas');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="robots" content="noindex">
    <meta name="theme-color" content="#1c1917">
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
    <title><?= e($title) ?> · Midas</title>
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <link rel="stylesheet" href="/assets/app.css">
</head>
<body class="min-h-dvh bg-paper text-ink antialiased"<?= !empty($autoprint) ? ' data-autoprint="1"' : '' ?>>
<a href="#contenido" class="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-50 focus:rounded-xl focus:bg-ink focus:px-4 focus:py-3 focus:text-white">Saltar al contenido</a>
<?php if ($chrome): ?>
    <div class="lg:grid lg:min-h-dvh lg:grid-cols-[15.5rem_minmax(0,1fr)]">
        <aside class="no-print hidden bg-ink text-white lg:flex lg:flex-col lg:px-4 lg:py-5">
            <a href="/" class="rounded-xl px-3 py-2 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-copper">
                <span class="block text-xs font-semibold uppercase tracking-[0.18em] text-copper">Punto de venta</span>
                <span class="mt-1 block text-2xl font-semibold tracking-tight">Midas</span>
                <span class="mt-1 block truncate text-sm text-white/60"><?= e($businessName) ?></span>
            </a>
            <nav class="mt-6 grid gap-1" aria-label="Secciones">
                <?= nav_item('/', 'inicio', 'Hoy', 'home', $current, 'side') ?>
                <?= nav_item('/caja', 'caja', 'Caja', 'pos', $current, 'side') ?>
                <?= nav_item('/productos', 'productos', 'Productos', 'box', $current, 'side') ?>
                <?= nav_item('/stock', 'stock', 'Stock', 'stock', $current, 'side') ?>
                <?= nav_item('/ventas', 'boletas', 'Boletas', 'receipt', $current, 'side') ?>
                <?= nav_item('/ajustes', 'ajustes', 'Ajustes', 'gear', $current, 'side') ?>
            </nav>
            <div class="mt-auto px-3 pt-6">
                <?php if ($user): ?>
                    <p class="truncate text-sm text-white/80"><?= e((string) $user['name']) ?></p>
                    <p class="truncate text-xs text-white/45"><?= e((string) $user['username']) ?></p>
                <?php endif; ?>
                <form method="post" action="/salir" class="mt-3">
                    <?= csrf_field() ?>
                    <button type="submit" class="flex min-h-11 items-center gap-3 rounded-xl px-0 text-sm font-medium text-white/70 hover:text-white focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-copper">
                        <?= icon('out') ?>
                        <span>Salir</span>
                    </button>
                </form>
            </div>
        </aside>
        <div class="min-w-0 pb-[calc(5.5rem+env(safe-area-inset-bottom))] lg:pb-0">
            <header class="no-print sticky top-0 z-30 flex items-center justify-between gap-3 border-b border-line bg-paper/90 px-4 py-3 backdrop-blur lg:hidden">
                <a href="/" class="min-h-11 rounded-xl px-1 text-lg font-semibold tracking-tight focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-copper">Midas</a>
                <div class="flex min-w-0 items-center gap-2">
                    <?php if ($user): ?>
                        <span class="max-w-[8rem] truncate text-sm text-muted"><?= e((string) $user['name']) ?></span>
                    <?php endif; ?>
                    <a href="/ajustes" aria-label="Ajustes" class="grid h-11 w-11 place-items-center rounded-full text-ink focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-copper"><?= icon('gear') ?></a>
                </div>
            </header>
            <?php if (!empty($flashMessage)): ?>
                <div class="px-4 pt-4 lg:px-8" role="status">
                    <div class="rounded-xl border px-4 py-3 text-sm <?= ($flashMessage['type'] ?? '') === 'error' ? 'border-wine/30 bg-wine/10 text-wine' : 'border-moss/20 bg-moss-soft text-moss' ?>">
                        <?= e((string) ($flashMessage['message'] ?? '')) ?>
                    </div>
                </div>
            <?php endif; ?>
            <main id="contenido" class="px-4 py-4 lg:px-8 lg:py-6">
                <?= $content ?>
            </main>
        </div>
    </div>
    <nav class="no-print fixed inset-x-0 bottom-0 z-40 border-t border-white/10 bg-ink pb-[env(safe-area-inset-bottom)] lg:hidden" aria-label="Secciones">
        <div class="grid grid-cols-4">
            <?= nav_item('/caja', 'caja', 'Caja', 'pos', $current, 'bar') ?>
            <?= nav_item('/productos', 'productos', 'Productos', 'box', $current, 'bar') ?>
            <?= nav_item('/stock', 'stock', 'Stock', 'stock', $current, 'bar') ?>
            <?= nav_item('/ventas', 'boletas', 'Boletas', 'receipt', $current, 'bar') ?>
        </div>
    </nav>
<?php else: ?>
    <main id="contenido">
        <?= $content ?>
    </main>
<?php endif; ?>
<?php foreach ($scripts as $script): ?>
    <script src="<?= e((string) $script) ?>"></script>
<?php endforeach; ?>
</body>
</html>
