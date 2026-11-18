<?php

declare(strict_types=1);

$database = sys_get_temp_dir() . '/midas-flow-' . getmypid() . '.sqlite';
@unlink($database);
putenv('MIDAS_DB=' . $database);

require dirname(__DIR__) . '/src/bootstrap.php';

$failures = 0;

function check(bool $condition, string $message): void
{
    global $failures;
    if ($condition) {
        echo "OK  $message\n";
        return;
    }
    $failures++;
    fwrite(STDERR, "FAIL $message\n");
}

function expect_throw(callable $fn, string $message): void
{
    try {
        $fn();
        check(false, $message);
    } catch (InvalidArgumentException) {
        check(true, $message);
    }
}

check(ean13_check_digit('400638133393') === '1', 'dígito EAN-13 conocido');
$bits = ean13_bits('4006381333931');
check(strlen($bits) === 95, 'EAN-13 tiene 95 módulos');
check(str_starts_with($bits, '101') && str_ends_with($bits, '101'), 'guardas de inicio y fin');
check(substr($bits, 45, 5) === '01010', 'guarda central');
$svg = ean13_svg('2000000000015');
check(str_contains($svg, '2000000000015') && substr_count($svg, '<rect ') >= 2, 'SVG del código interno');

check(parse_money('12,50') === 1250, 'monto con coma');
check(parse_money('S/ 1,250.50') === 125050, 'monto con miles');
[$base, $igv] = split_igv(840, 18);
check($base === 712 && $igv === 128 && $base + $igv === 840, 'IGV incluido de S/ 8.40');

$hash = db()->query("SELECT password_hash FROM users WHERE username = 'caja'")->fetchColumn();
check(is_string($hash) && password_verify('midas-caja', $hash), 'clave inicial de caja');

$leche = product_by_barcode('2000000000015');
check($leche !== null && (int) $leche['stock'] === 24, 'semilla de leche por código');
$upcBody = '123456789012';
$upcEan = '0' . substr($upcBody, 0, 11);
$upcEan .= ean13_check_digit($upcEan);
$upcProduct = product_save(null, [
    'name' => 'Lata de prueba UPC',
    'category' => 'Abarrotes',
    'barcode' => $upcEan,
    'price' => '3.50',
    'cost' => '',
    'stock' => '4',
    'min_stock' => '1',
]);
$upc = product_by_barcode(substr($upcEan, 1));
check($upc !== null && (int) $upc['id'] === $upcProduct, 'un UPC-A de 12 dígitos encuentra el EAN-13');

$sale = sale_create([
    'items' => [['id' => (int) $leche['id'], 'qty' => 2]],
    'payment_method' => 'efectivo',
    'paid' => '10.00',
]);
check($sale['number'] === 'B001-00000001', 'primera boleta ' . $sale['number']);
check((int) $sale['total_cents'] === 840, 'total de dos leches');
check((int) $sale['subtotal_cents'] === 712 && (int) $sale['igv_cents'] === 128, 'desglose IGV');
check((int) $sale['change_cents'] === 160, 'vuelto de la boleta');
check((int) product_find((int) $leche['id'])['stock'] === 22, 'la venta descuenta stock');

$moves = movements_recent(5);
check($moves[0]['type'] === 'sale' && (int) $moves[0]['sale_id'] === (int) $sale['id'], 'movimiento de venta');

$detergente = product_by_barcode('2000000000107');
$before = (int) $detergente['stock'];
expect_throw(function () use ($detergente): void {
    sale_create([
        'items' => [['id' => (int) $detergente['id'], 'qty' => $detergente['stock'] + 1]],
        'payment_method' => 'efectivo',
        'paid' => '100',
    ]);
}, 'rechaza venta sin stock');
check((int) product_find((int) $detergente['id'])['stock'] === $before, 'el rechazo no mueve stock');

expect_throw(function () use ($leche): void {
    sale_create([
        'items' => [['id' => (int) $leche['id'], 'qty' => 1]],
        'payment_method' => 'efectivo',
        'paid' => '1',
    ]);
}, 'rechaza efectivo insuficiente');

$agua = product_by_barcode('2000000000084');
$card = sale_create([
    'items' => [['id' => (int) $agua['id'], 'qty' => 1]],
    'payment_method' => 'yape',
    'paid' => '0',
]);
check($card['number'] === 'B001-00000002' && (int) $card['change_cents'] === 0, 'Yape no calcula vuelto');

$azucar = product_by_barcode('2000000000046');
stock_apply((int) $azucar['id'], 'in', 5, 'Compra a proveedor');
check((int) product_find((int) $azucar['id'])['stock'] === 9, 'entrada de stock');
expect_throw(function () use ($azucar): void {
    stock_apply((int) $azucar['id'], 'out', 100, 'Merma');
}, 'rechaza salida mayor al stock');

$id = product_save(null, [
    'name' => 'Huevo a granel',
    'category' => 'Abarrotes',
    'barcode' => '',
    'price' => '0.80',
    'cost' => '',
    'stock' => '12',
    'min_stock' => '4',
]);
$created = product_find($id);
check($created !== null && preg_match('/^200\d{10}$/', (string) $created['barcode']) && (int) $created['stock'] === 12, 'producto nuevo con EAN interno');
expect_throw(function (): void {
    product_save(null, [
        'name' => 'Código malo',
        'category' => '',
        'barcode' => '1234567890123',
        'price' => '1',
        'cost' => '',
        'stock' => '1',
        'min_stock' => '0',
    ]);
}, 'rechaza EAN-13 con dígito incorrecto');

product_set_active($id, false);
expect_throw(function () use ($id): void {
    sale_create([
        'items' => [['id' => $id, 'qty' => 1]],
        'payment_method' => 'tarjeta',
    ]);
}, 'no vende un producto inactivo');

$found = products_search('100% leche');
check($found === [], 'la búsqueda trata % como texto');

foreach ([$database, $database . '-wal', $database . '-shm'] as $file) {
    @unlink($file);
}

if ($failures > 0) {
    fwrite(STDERR, "$failures prueba(s) fallaron\n");
    exit(1);
}

echo "Todas las pruebas pasaron\n";
