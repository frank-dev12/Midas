<?php

declare(strict_types=1);

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function lima_now(): DateTimeImmutable
{
    return new DateTimeImmutable('now', new DateTimeZone('America/Lima'));
}

function now_lima(): string
{
    return lima_now()->format('Y-m-d H:i:s');
}

function lima_date_long(): string
{
    $dt = lima_now();
    $days = ['domingo', 'lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado'];
    $months = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
    $day = mb_convert_case($days[(int) $dt->format('w')], MB_CASE_TITLE, 'UTF-8');

    return $day . ' ' . $dt->format('j') . ' de ' . $months[(int) $dt->format('n') - 1];
}

function format_when(string $stored): string
{
    $dt = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $stored, new DateTimeZone('America/Lima'));
    if (!$dt) {
        return $stored;
    }

    return $dt->format('d/m/Y H:i');
}

function money(int $cents): string
{
    $negative = $cents < 0;
    $formatted = number_format(abs($cents) / 100, 2, '.', ',');

    return ($negative ? '-' : '') . 'S/ ' . $formatted;
}

function cents_to_input(int $cents): string
{
    return number_format($cents / 100, 2, '.', '');
}

function parse_money(mixed $value): int
{
    $raw = trim((string) $value);
    $raw = str_replace(['S/', 's/', ' '], '', $raw);
    if ($raw === '') {
        throw new InvalidArgumentException('Ingresa un monto.');
    }
    if (str_contains($raw, ',') && str_contains($raw, '.')) {
        $raw = str_replace(',', '', $raw);
    } else {
        $raw = str_replace(',', '.', $raw);
    }
    if (!preg_match('/^\d+(\.\d{1,2})?$/', $raw)) {
        throw new InvalidArgumentException('Monto inválido.');
    }
    $cents = (int) round(((float) $raw) * 100);
    if ($cents > 99_999_999) {
        throw new InvalidArgumentException('El monto es demasiado alto.');
    }

    return $cents;
}

function parse_whole(mixed $value, string $label, int $max = 100000): int
{
    $raw = trim((string) $value);
    if (!preg_match('/^\d{1,6}$/', $raw)) {
        throw new InvalidArgumentException($label . ' debe ser un número entero.');
    }
    $number = (int) $raw;
    if ($number > $max) {
        throw new InvalidArgumentException($label . ' es demasiado alto.');
    }

    return $number;
}

function split_igv(int $totalCents, float $rate): array
{
    if ($rate < 0 || $rate > 100) {
        throw new InvalidArgumentException('IGV inválido.');
    }
    $base = (int) round($totalCents / (1 + ($rate / 100)));

    return [$base, $totalCents - $base];
}

function payment_label(string $method): string
{
    return [
        'efectivo' => 'Efectivo',
        'tarjeta' => 'Tarjeta',
        'yape' => 'Yape',
        'plin' => 'Plin',
        'transferencia' => 'Transferencia',
    ][$method] ?? $method;
}

function movement_label(string $type): string
{
    return [
        'in' => 'Entrada',
        'out' => 'Salida',
        'sale' => 'Venta',
    ][$type] ?? $type;
}

function payment_methods(): array
{
    return ['efectivo', 'tarjeta', 'yape', 'plin', 'transferencia'];
}

function redirect(string $to): never
{
    header('Location: ' . $to);
    exit;
}

function json_out(array $payload, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

function request_path(): string
{
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
    $path = is_string($path) ? $path : '/';
    $path = rtrim($path, '/');

    return $path === '' ? '/' : $path;
}

function body_json(): array
{
    $raw = file_get_contents('php://input');
    if ($raw === false || trim($raw) === '') {
        return [];
    }
    $data = json_decode($raw, true);
    if (!is_array($data)) {
        throw new InvalidArgumentException('El cuerpo de la solicitud no es JSON.');
    }

    return $data;
}

function field(string $key, string $default = ''): string
{
    $value = $_POST[$key] ?? $default;

    return is_string($value) ? trim($value) : $default;
}

function flash(string $type, string $message): void
{
    auth_start();
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function flash_take(): ?array
{
    if (session_status() !== PHP_SESSION_ACTIVE || !isset($_SESSION['flash']) || !is_array($_SESSION['flash'])) {
        return null;
    }
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);

    return $flash;
}

function remember_input(): void
{
    auth_start();
    $copy = $_POST;
    unset($copy['password'], $copy['current_password'], $copy['new_password'], $copy['confirm_password'], $copy['_csrf']);
    $_SESSION['old'] = $copy;
}

function old_input(): array
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        return [];
    }
    $old = $_SESSION['old'] ?? [];
    unset($_SESSION['old']);

    return is_array($old) ? $old : [];
}

function render(string $view, array $data = []): void
{
    $title = $data['title'] ?? 'Midas';
    $current = $data['current'] ?? '';
    $chrome = $data['chrome'] ?? true;
    $scripts = $data['scripts'] ?? [];
    $autoprint = (bool) ($data['autoprint'] ?? false);
    $flashMessage = array_key_exists('flashMessage', $data) ? $data['flashMessage'] : flash_take();
    extract($data, EXTR_SKIP);
    $viewFile = BASE . '/views/' . $view . '.php';
    if (!is_file($viewFile)) {
        throw new RuntimeException('Vista no encontrada.');
    }
    ob_start();
    require $viewFile;
    $content = (string) ob_get_clean();
    require BASE . '/views/layout.php';
}

function fail_back(string $to, Throwable $error): never
{
    if ($error instanceof InvalidArgumentException) {
        flash('error', $error->getMessage());
        remember_input();
        redirect($to);
    }
    throw $error;
}

function stock_tone(int $stock, int $min): string
{
    if ($stock <= 0) {
        return 'bg-wine/10 text-wine';
    }
    if ($stock <= $min) {
        return 'bg-copper/10 text-copper-dark';
    }

    return 'bg-moss-soft text-moss';
}

function stock_label(int $stock, int $min): string
{
    if ($stock <= 0) {
        return 'Agotado';
    }
    if ($stock <= $min) {
        return 'Bajo';
    }

    return 'OK';
}

function btn(string $variant = 'primary'): string
{
    $base = 'inline-flex min-h-11 items-center justify-center gap-2 rounded-xl px-4 text-sm font-semibold transition focus-visible:outline-2 focus-visible:outline-offset-2 disabled:cursor-not-allowed disabled:opacity-50';
    $variants = [
        'primary' => 'bg-copper text-white hover:bg-copper-dark focus-visible:outline-copper',
        'ink' => 'bg-ink text-white hover:bg-ink/90 focus-visible:outline-ink',
        'ghost' => 'border border-line bg-panel text-ink hover:bg-sand focus-visible:outline-copper',
        'danger' => 'border border-wine/30 bg-white text-wine hover:bg-wine/10 focus-visible:outline-wine',
    ];

    return $base . ' ' . ($variants[$variant] ?? $variants['primary']);
}

function field_class(): string
{
    return 'min-h-11 w-full rounded-xl border border-line bg-panel px-3 text-base text-ink outline-none placeholder:text-muted focus:border-copper focus:ring-2 focus:ring-copper/30';
}

function label_class(): string
{
    return 'mb-1.5 block text-sm font-medium';
}

function card_class(): string
{
    return 'rounded-2xl border border-line bg-panel p-4 shadow-sm sm:p-5';
}

function like_pattern(string $value): string
{
    return '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value) . '%';
}

function icon(string $name): string
{
    $paths = [
        'home' => '<path d="M4 10.5 12 4l8 6.5"/><path d="M6 9.8V20h4.5v-5.5h3V20H18V9.8"/>',
        'pos' => '<rect x="4" y="3" width="16" height="18" rx="2"/><path d="M8 8h8M8 12h8M8 16h4"/>',
        'box' => '<path d="m3 8 9-4 9 4-9 4-9-4Z"/><path d="M3 8v8l9 4 9-4V8"/><path d="M12 12v8"/>',
        'stock' => '<path d="M4 7h11"/><path d="m12 4 3 3-3 3"/><path d="M20 17H9"/><path d="m12 14-3 3 3 3"/>',
        'receipt' => '<path d="M6 3h12v18l-2-1.2-2 1.2-2-1.2-2 1.2-2-1.2L6 21V3Z"/><path d="M9 8h6M9 12h6M9 16h3"/>',
        'gear' => '<circle cx="12" cy="12" r="3"/><path d="M12 3.5v2.2M12 18.3v2.2M3.5 12h2.2M18.3 12h2.2M5.8 5.8l1.6 1.6M16.6 16.6l1.6 1.6M18.2 5.8l-1.6 1.6M7.4 16.6 5.8 18.2"/>',
        'out' => '<path d="M10 7V5H5v14h5v-2"/><path d="M10 12h9"/><path d="m16 9 3 3-3 3"/>',
    ];
    $body = $paths[$name] ?? '';

    return '<svg viewBox="0 0 24 24" class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $body . '</svg>';
}

function nav_item(string $href, string $key, string $label, string $iconName, string $current, string $variant): string
{
    $active = $current === $key;
    if ($variant === 'side') {
        $class = $active ? 'bg-white/10 text-white' : 'text-white/70 hover:bg-white/10 hover:text-white';

        return '<a href="' . e($href) . '" class="flex min-h-11 items-center gap-3 rounded-xl px-3 text-sm font-medium focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-copper ' . $class . '"'
            . ($active ? ' aria-current="page"' : '') . '>' . icon($iconName) . '<span>' . e($label) . '</span></a>';
    }
    $class = $active ? 'text-copper' : 'text-white/60';

    return '<a href="' . e($href) . '" class="flex min-h-14 flex-col items-center justify-center gap-1 px-1 text-[11px] font-medium focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-copper ' . $class . '"'
        . ($active ? ' aria-current="page"' : '') . '>' . icon($iconName) . '<span>' . e($label) . '</span></a>';
}
