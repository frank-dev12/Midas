<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Midas ERP</title>
        <link rel="manifest" href="/manifest.webmanifest">
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-gradient-to-br from-slate-950 via-slate-900 to-amber-950 text-slate-100 antialiased">
        <div class="mx-auto flex min-h-screen max-w-5xl flex-col px-6 py-10">
            <header class="flex items-center justify-between">
                <p class="text-sm font-semibold uppercase tracking-[0.25em] text-amber-400">Midas ERP</p>
                <nav class="flex gap-4 text-sm">
                    @auth
                        <a href="{{ url('/dashboard') }}" class="text-amber-200 hover:text-white">Dashboard</a>
                    @else
                        <a href="{{ route('login') }}" class="text-amber-200 hover:text-white">Ingresar</a>
                        @if (Route::has('register'))
                            <a href="{{ route('register') }}" class="text-slate-300 hover:text-white">Registro</a>
                        @endif
                    @endauth
                </nav>
            </header>

            <main class="flex flex-1 flex-col justify-center py-16">
                <h1 class="max-w-2xl text-4xl font-semibold tracking-tight text-white sm:text-5xl">
                    Retail POS + inventario + SUNAT
                </h1>
                <p class="mt-4 max-w-xl text-base text-slate-300">
                    Scaffold MVP multi-tenant (shared MySQL + <code class="text-amber-200">tenant_id</code>):
                    Filament backoffice, POS Livewire y cimiento offline PWA. Demo sin Cashier.
                </p>
                <div class="mt-8 flex flex-wrap gap-3">
                    <a href="{{ route('login') }}" class="rounded-lg bg-amber-500 px-5 py-2.5 text-sm font-semibold text-slate-950 hover:bg-amber-400">
                        Abrir sesión
                    </a>
                    <a href="{{ url('/admin') }}" class="rounded-lg border border-slate-600 px-5 py-2.5 text-sm font-medium text-slate-200 hover:border-slate-400">
                        Filament /admin
                    </a>
                </div>
                <p class="mt-10 text-xs text-slate-500">
                    Demo: admin@midas.test / cajero@midas.test · password · ver README
                </p>
            </main>
        </div>
    </body>
</html>
