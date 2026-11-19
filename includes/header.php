<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/database.php';
$pdo = db();
$current = basename($_SERVER['PHP_SELF'], '.php');
$flash = get_flash();

$links = [
    'index' => ['Dashboard', 'M12 3v18m0-18l4 4M12 3L8 7m12 5H4'],
    'pos' => ['Vender', 'M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.3 2.3c-.4.4-.1 1.1.4 1.1H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z'],
    'products' => ['Productos', 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4'],
    'stock' => ['Stock', 'M4 7v10c0 2.2 3.6 4 8 4s8-1.8 8-4V7M4 7c0 2.2 3.6 4 8 4s8-1.8 8-4M4 7c0-2.2 3.6-4 8-4s8 1.8 8 4'],
    'sales' => ['Ventas', 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4'],
];
?>
<!DOCTYPE html>
<html lang="es" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#eef2f7" id="meta-theme-color">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <title><?= htmlspecialchars($pageTitle ?? APP_NAME) ?> · <?= APP_NAME ?></title>
    <script>
        (function () {
            try {
                var t = localStorage.getItem('midas-theme');
                if (t === 'dark' || t === 'light') {
                    document.documentElement.setAttribute('data-theme', t);
                }
            } catch (e) {}
        })();
    </script>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="assets/css/app.css">
</head>
<body class="min-h-dvh antialiased">
    <div class="ambient" aria-hidden="true"></div>

    <div class="relative z-10 flex min-h-dvh">
        <aside class="hidden lg:flex w-64 flex-col border-r glass-nav">
            <div class="px-6 py-7 border-b divider flex items-start justify-between gap-2">
                <a href="index.php" class="block">
                    <span class="brand text-2xl tracking-tight">MIDAS</span>
                    <span class="block text-xs uppercase tracking-[0.2em] muted mt-1">Punto de venta</span>
                </a>
                <button type="button" class="btn-theme theme-toggle" title="Cambiar tema" aria-label="Cambiar tema">
                    <svg class="icon-moon w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 12.79A9 9 0 1111.21 3 7 7 0 0021 12.79z"/></svg>
                    <svg class="icon-sun w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2m0 14v2m9-9h-2M5 12H3m15.364 6.364l-1.414-1.414M7.05 7.05L5.636 5.636m12.728 0l-1.414 1.414M7.05 16.95l-1.414 1.414M12 8a4 4 0 100 8 4 4 0 000-8z"/></svg>
                </button>
            </div>
            <nav class="flex-1 px-3 py-5 space-y-1">
                <?php foreach ($links as $file => [$label, $icon]):
                    $active = $current === $file;
                ?>
                <a href="<?= $file ?>.php"
                   class="nav-link flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition min-h-11 <?= $active ? 'active' : '' ?>">
                    <svg class="w-5 h-5 shrink-0 opacity-80" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="<?= $icon ?>"/>
                    </svg>
                    <?= $label ?>
                </a>
                <?php endforeach; ?>
            </nav>
            <div class="px-5 py-4 border-t divider text-xs muted">
                <?= date('d/m/Y H:i') ?>
            </div>
        </aside>

        <div class="flex-1 flex flex-col min-w-0 w-full">
            <header class="lg:hidden sticky top-0 z-30 border-b glass-nav pt-safe">
                <div class="px-4 min-h-14 flex items-center justify-between gap-2">
                    <a href="index.php" class="brand text-xl tracking-tight">MIDAS</a>
                    <div class="flex items-center gap-2">
                        <button type="button" class="btn-theme theme-toggle" title="Cambiar tema" aria-label="Cambiar tema">
                            <svg class="icon-moon w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 12.79A9 9 0 1111.21 3 7 7 0 0021 12.79z"/></svg>
                            <svg class="icon-sun w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2m0 14v2m9-9h-2M5 12H3m15.364 6.364l-1.414-1.414M7.05 7.05L5.636 5.636m12.728 0l-1.414 1.414M7.05 16.95l-1.414 1.414M12 8a4 4 0 100 8 4 4 0 000-8z"/></svg>
                        </button>
                        <a href="pos.php"
                           class="btn-primary inline-flex items-center justify-center min-h-11 px-4 text-sm <?= $current === 'pos' ? 'ring-2 ring-offset-1' : '' ?>">
                            Vender
                        </a>
                    </div>
                </div>
            </header>

            <main class="flex-1 w-full max-w-full min-w-0 p-3 sm:p-6 lg:p-8 pb-[calc(4.5rem+env(safe-area-inset-bottom))] lg:pb-8">
                <?php if ($flash): ?>
                <div class="mb-4 sm:mb-6 rounded-xl px-4 py-3 text-sm
                    <?= $flash['type'] === 'success' ? 'flash-success' : '' ?>
                    <?= $flash['type'] === 'error' ? 'flash-error' : '' ?>
                    <?= $flash['type'] === 'info' ? 'flash-info' : '' ?>">
                    <?= htmlspecialchars($flash['message']) ?>
                </div>
                <?php endif; ?>
