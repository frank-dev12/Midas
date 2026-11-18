<?php

declare(strict_types=1);

function midas_db_path(): string
{
    $fromEnv = getenv('MIDAS_DB');
    if (is_string($fromEnv) && $fromEnv !== '') {
        return $fromEnv;
    }

    return BASE . '/storage/midas.sqlite';
}

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $path = midas_db_path();
    $directory = dirname($path);
    if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
        throw new RuntimeException('No se pudo crear la carpeta de datos.');
    }
    $fresh = !is_file($path);
    $pdo = new PDO('sqlite:' . $path);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->exec('PRAGMA foreign_keys = ON');
    $pdo->exec('PRAGMA busy_timeout = 5000');
    migrate($pdo);
    if ($fresh) {
        seed($pdo);
    }

    return $pdo;
}

function db_tx(callable $fn): mixed
{
    $pdo = db();
    $pdo->exec('BEGIN IMMEDIATE');
    try {
        $result = $fn($pdo);
        $pdo->exec('COMMIT');

        return $result;
    } catch (Throwable $error) {
        try {
            $pdo->exec('ROLLBACK');
        } catch (Throwable) {
        }
        throw $error;
    }
}

function migrate(PDO $pdo): void
{
    $pdo->exec(<<<'SQL'
        CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY,
            username TEXT NOT NULL UNIQUE,
            name TEXT NOT NULL,
            password_hash TEXT NOT NULL,
            created_at TEXT NOT NULL
        );

        CREATE TABLE IF NOT EXISTS settings (
            key TEXT PRIMARY KEY,
            value TEXT NOT NULL
        );

        CREATE TABLE IF NOT EXISTS products (
            id INTEGER PRIMARY KEY,
            name TEXT NOT NULL,
            category TEXT NOT NULL DEFAULT '',
            barcode TEXT NOT NULL UNIQUE,
            price_cents INTEGER NOT NULL CHECK (price_cents >= 0),
            cost_cents INTEGER NOT NULL DEFAULT 0 CHECK (cost_cents >= 0),
            stock INTEGER NOT NULL DEFAULT 0 CHECK (stock >= 0),
            min_stock INTEGER NOT NULL DEFAULT 0 CHECK (min_stock >= 0),
            active INTEGER NOT NULL DEFAULT 1 CHECK (active IN (0, 1)),
            created_at TEXT NOT NULL,
            updated_at TEXT NOT NULL
        );

        CREATE TABLE IF NOT EXISTS sale_seq (
            series TEXT PRIMARY KEY,
            last_number INTEGER NOT NULL
        );

        CREATE TABLE IF NOT EXISTS sales (
            id INTEGER PRIMARY KEY,
            number TEXT NOT NULL UNIQUE,
            series TEXT NOT NULL,
            correlative INTEGER NOT NULL,
            customer_name TEXT NOT NULL DEFAULT '',
            customer_doc TEXT NOT NULL DEFAULT '',
            subtotal_cents INTEGER NOT NULL,
            igv_cents INTEGER NOT NULL,
            igv_rate TEXT NOT NULL,
            total_cents INTEGER NOT NULL,
            payment_method TEXT NOT NULL,
            paid_cents INTEGER NOT NULL,
            change_cents INTEGER NOT NULL,
            user_id INTEGER,
            created_at TEXT NOT NULL,
            biz_name TEXT NOT NULL,
            biz_ruc TEXT NOT NULL DEFAULT '',
            biz_address TEXT NOT NULL DEFAULT '',
            biz_phone TEXT NOT NULL DEFAULT '',
            footer TEXT NOT NULL DEFAULT ''
        );

        CREATE TABLE IF NOT EXISTS sale_items (
            id INTEGER PRIMARY KEY,
            sale_id INTEGER NOT NULL REFERENCES sales(id),
            product_id INTEGER NOT NULL REFERENCES products(id),
            name TEXT NOT NULL,
            barcode TEXT NOT NULL DEFAULT '',
            qty INTEGER NOT NULL CHECK (qty > 0),
            unit_price_cents INTEGER NOT NULL,
            line_total_cents INTEGER NOT NULL
        );

        CREATE TABLE IF NOT EXISTS movements (
            id INTEGER PRIMARY KEY,
            product_id INTEGER NOT NULL REFERENCES products(id),
            type TEXT NOT NULL CHECK (type IN ('in', 'out', 'sale')),
            qty INTEGER NOT NULL CHECK (qty > 0),
            stock_after INTEGER NOT NULL,
            note TEXT NOT NULL DEFAULT '',
            sale_id INTEGER REFERENCES sales(id),
            user_id INTEGER,
            created_at TEXT NOT NULL
        );

        CREATE INDEX IF NOT EXISTS idx_products_name ON products(name);
        CREATE INDEX IF NOT EXISTS idx_sales_created ON sales(created_at);
        CREATE INDEX IF NOT EXISTS idx_movements_created ON movements(created_at);
        CREATE INDEX IF NOT EXISTS idx_movements_product ON movements(product_id);
    SQL);
}

function seed(PDO $pdo): void
{
    $now = (new DateTimeImmutable('now', new DateTimeZone('America/Lima')))->format('Y-m-d H:i:s');
    $pdo->prepare('INSERT INTO users (username, name, password_hash, created_at) VALUES (?, ?, ?, ?)')
        ->execute(['caja', 'Caja principal', password_hash('midas-caja', PASSWORD_DEFAULT), $now]);

    $settings = [
        'business_name' => 'Bodega Midas',
        'ruc' => '20123456786',
        'address' => 'Av. Colonial 1234, Lima',
        'phone' => '01 555 0142',
        'igv' => '18',
        'series' => 'B001',
        'ticket_footer' => 'Gracias por su compra. Vuelva pronto.',
    ];
    $setting = $pdo->prepare('INSERT INTO settings (key, value) VALUES (?, ?)');
    foreach ($settings as $key => $value) {
        $setting->execute([$key, $value]);
    }

    $catalog = [
        ['Leche Gloria entera 400 g', 'Abarrotes', 420, 24, 6],
        ['Arroz extra Costeño 5 kg', 'Abarrotes', 1890, 10, 3],
        ['Aceite Primor 1 L', 'Abarrotes', 950, 8, 3],
        ['Azúcar rubia 1 kg', 'Abarrotes', 460, 4, 5],
        ['Fideos Don Vittorio 500 g', 'Abarrotes', 380, 20, 5],
        ['Atún Florida en aceite 170 g', 'Abarrotes', 540, 15, 4],
        ['Inca Kola 500 ml', 'Bebidas', 250, 36, 12],
        ['Agua San Luis 625 ml', 'Bebidas', 150, 48, 12],
        ['Pan de molde Bimbo 500 g', 'Panadería', 890, 6, 2],
        ['Detergente Bolívar 500 g', 'Limpieza', 410, 2, 4],
        ['Galletas Casino 6 und.', 'Snacks', 220, 18, 6],
        ['Sublime clásico', 'Snacks', 180, 30, 8],
    ];
    $insert = $pdo->prepare('INSERT INTO products (name, category, barcode, price_cents, cost_cents, stock, min_stock, active, created_at, updated_at) VALUES (?, ?, ?, ?, 0, ?, ?, 1, ?, ?)');
    $movement = $pdo->prepare('INSERT INTO movements (product_id, type, qty, stock_after, note, sale_id, user_id, created_at) VALUES (?, "in", ?, ?, "Stock inicial", NULL, NULL, ?)');
    foreach ($catalog as $index => $item) {
        $twelve = '200' . str_pad((string) ($index + 1), 9, '0', STR_PAD_LEFT);
        $barcode = $twelve . ean13_check_digit($twelve);
        $insert->execute([$item[0], $item[1], $barcode, $item[2], $item[3], $item[4], $now, $now]);
        $id = (int) $pdo->lastInsertId();
        $movement->execute([$id, $item[3], $item[3], $now]);
    }
}

function setting_value(PDO $pdo, string $key, string $default = ''): string
{
    $stmt = $pdo->prepare('SELECT value FROM settings WHERE key = ?');
    $stmt->execute([$key]);
    $value = $stmt->fetchColumn();

    return is_string($value) ? $value : $default;
}

function setting(string $key, string $default = ''): string
{
    return setting_value(db(), $key, $default);
}
