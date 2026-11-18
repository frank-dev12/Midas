<?php

declare(strict_types=1);

function midas_dispatch(): void
{
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    $path = request_path();

    if ($path === '/login') {
        if ($method === 'GET') {
            page_login();
            return;
        }
        if ($method === 'POST') {
            csrf_check();
            action_login();
            return;
        }
    }

    if ($method === 'POST') {
        csrf_check();
    } elseif ($method !== 'GET') {
        http_response_code(405);
        echo 'Método no permitido.';
        return;
    }

    $user = require_user();

    if ($path === '/' && $method === 'GET') {
        page_home();
        return;
    }
    if ($path === '/caja' && $method === 'GET') {
        page_pos();
        return;
    }
    if ($path === '/productos' && $method === 'GET') {
        page_products();
        return;
    }
    if ($path === '/productos' && $method === 'POST') {
        action_product_create();
        return;
    }
    if ($path === '/productos/nuevo' && $method === 'GET') {
        page_product_form(null);
        return;
    }
    if ($path === '/etiquetas' && $method === 'GET') {
        page_labels();
        return;
    }
    if ($path === '/stock' && $method === 'GET') {
        page_stock();
        return;
    }
    if ($path === '/stock' && $method === 'POST') {
        action_stock();
        return;
    }
    if ($path === '/ventas' && $method === 'GET') {
        page_sales();
        return;
    }
    if ($path === '/ajustes' && $method === 'GET') {
        page_settings();
        return;
    }
    if ($path === '/ajustes' && $method === 'POST') {
        action_settings();
        return;
    }
    if ($path === '/clave' && $method === 'POST') {
        action_password($user);
        return;
    }
    if ($path === '/salir' && $method === 'POST') {
        logout();
        redirect('/login');
    }
    if ($path === '/api/productos/codigo' && $method === 'GET') {
        api_barcode();
        return;
    }
    if ($path === '/api/ventas' && $method === 'POST') {
        api_sale();
        return;
    }
    if (preg_match('#^/productos/(\d+)/codigo\.svg$#', $path, $matches) && $method === 'GET') {
        page_barcode_svg((int) $matches[1]);
        return;
    }
    if (preg_match('#^/productos/(\d+)/editar$#', $path, $matches) && $method === 'GET') {
        page_product_form((int) $matches[1]);
        return;
    }
    if (preg_match('#^/productos/(\d+)$#', $path, $matches) && $method === 'POST') {
        action_product_update((int) $matches[1]);
        return;
    }
    if (preg_match('#^/productos/(\d+)/estado$#', $path, $matches) && $method === 'POST') {
        action_product_active((int) $matches[1]);
        return;
    }
    if (preg_match('#^/ventas/(\d+)$#', $path, $matches) && $method === 'GET') {
        page_sale((int) $matches[1]);
        return;
    }

    if (str_starts_with($path, '/api/')) {
        json_out(['ok' => false, 'error' => 'No encontrado.'], 404);
    }
    http_response_code(404);
    render('not-found', ['title' => 'No encontrada', 'current' => '']);
}

function page_login(): void
{
    if (current_user()) {
        redirect('/');
    }
    $old = old_input();
    render('login', [
        'title' => 'Entrar',
        'chrome' => false,
        'username' => (string) ($old['username'] ?? ''),
    ]);
}

function action_login(): void
{
    $username = field('username');
    $password = (string) ($_POST['password'] ?? '');
    try {
        $ok = attempt_login($username, $password);
    } catch (InvalidArgumentException $error) {
        flash('error', $error->getMessage());
        $_SESSION['old'] = ['username' => $username];
        redirect('/login');
    }
    if (!$ok) {
        flash('error', 'Usuario o clave incorrectos.');
        $_SESSION['old'] = ['username' => $username];
        redirect('/login');
    }
    redirect('/caja');
}

function page_home(): void
{
    render('home', [
        'title' => 'Hoy',
        'current' => 'inicio',
        'summary' => today_summary(),
        'low' => low_stock(),
        'lowCount' => low_stock_count(),
        'sales' => sales_search('', 0),
        'dateLabel' => lima_date_long(),
    ]);
}

function page_pos(): void
{
    $recent = null;
    $saleId = $_GET['venta'] ?? '';
    if (is_string($saleId) && ctype_digit($saleId)) {
        $recent = sale_find((int) $saleId);
    }
    render('pos', [
        'title' => 'Caja',
        'current' => 'caja',
        'products' => products_search('', true),
        'recentSale' => $recent,
        'igv' => setting('igv', '18'),
        'business' => setting('business_name', 'Midas'),
    ]);
}

function page_products(): void
{
    $query = trim((string) ($_GET['q'] ?? ''));
    render('products', [
        'title' => 'Productos',
        'current' => 'productos',
        'query' => $query,
        'products' => products_search($query),
    ]);
}

function page_product_form(?int $id): void
{
    $product = null;
    if ($id !== null) {
        $product = product_find($id);
        if (!$product) {
            http_response_code(404);
            render('not-found', ['title' => 'No encontrada', 'current' => 'productos']);
            return;
        }
    }
    render('product-form', [
        'title' => $product ? 'Editar producto' : 'Nuevo producto',
        'current' => 'productos',
        'product' => $product,
        'old' => old_input(),
        'categories' => categories(),
        'prefillBarcode' => trim((string) ($_GET['barcode'] ?? '')),
    ]);
}

function action_product_create(): void
{
    try {
        $id = product_save(null, $_POST);
    } catch (Throwable $error) {
        fail_back('/productos/nuevo', $error);
    }
    flash('ok', 'Producto creado.');
    redirect('/productos/' . $id . '/editar');
}

function action_product_update(int $id): void
{
    try {
        product_save($id, $_POST);
    } catch (Throwable $error) {
        fail_back('/productos/' . $id . '/editar', $error);
    }
    flash('ok', 'Producto actualizado.');
    redirect('/productos/' . $id . '/editar');
}

function action_product_active(int $id): void
{
    try {
        product_set_active($id, field('active') === '1');
    } catch (Throwable $error) {
        fail_back('/productos/' . $id . '/editar', $error);
    }
    flash('ok', field('active') === '1' ? 'Producto activado.' : 'Producto desactivado.');
    redirect('/productos/' . $id . '/editar');
}

function page_barcode_svg(int $id): void
{
    $product = product_find($id);
    if (!$product) {
        http_response_code(404);
        echo 'Producto no encontrado.';
        return;
    }
    header('Content-Type: image/svg+xml; charset=utf-8');
    header('Cache-Control: private, max-age=300');
    echo barcode_svg((string) $product['barcode']);
}

function page_labels(): void
{
    render('labels', [
        'title' => 'Etiquetas',
        'current' => 'productos',
        'products' => products_search('', true),
    ]);
}

function page_stock(): void
{
    $selected = (int) ($_GET['producto'] ?? 0);
    render('stock', [
        'title' => 'Stock',
        'current' => 'stock',
        'products' => products_search(''),
        'movements' => movements_recent(),
        'selected' => $selected,
        'old' => old_input(),
        'lowCount' => low_stock_count(),
    ]);
}

function action_stock(): void
{
    $type = field('type');
    $reason = field('reason');
    $detail = field('detail');
    try {
        if (!in_array($type, ['in', 'out'], true)) {
            throw new InvalidArgumentException('Elige entrada o salida.');
        }
        if (!in_array($reason, stock_reasons($type), true)) {
            throw new InvalidArgumentException('Elige un motivo válido para ese movimiento.');
        }
        $note = $reason . ($detail !== '' ? ' — ' . $detail : '');
        stock_apply((int) field('product_id'), $type, parse_whole(field('qty'), 'La cantidad'), $note);
    } catch (Throwable $error) {
        fail_back('/stock?producto=' . (int) field('product_id'), $error);
    }
    flash('ok', $type === 'in' ? 'Entrada registrada.' : 'Salida registrada.');
    redirect('/stock?producto=' . (int) field('product_id'));
}

function page_sales(): void
{
    $query = trim((string) ($_GET['q'] ?? ''));
    $page = max(1, (int) ($_GET['p'] ?? 1));
    $offset = ($page - 1) * 30;
    render('sales', [
        'title' => 'Boletas',
        'current' => 'boletas',
        'query' => $query,
        'page' => $page,
        'sales' => sales_search($query, $offset),
    ]);
}

function page_sale(int $id): void
{
    $sale = sale_find($id);
    if (!$sale) {
        http_response_code(404);
        render('not-found', ['title' => 'No encontrada', 'current' => 'boletas']);
        return;
    }
    render('sale', [
        'title' => $sale['number'],
        'current' => 'boletas',
        'sale' => $sale,
        'autoprint' => isset($_GET['print']),
    ]);
}

function page_settings(): void
{
    render('settings', [
        'title' => 'Ajustes',
        'current' => 'ajustes',
        'old' => old_input(),
        'settings' => [
            'business_name' => setting('business_name'),
            'ruc' => setting('ruc'),
            'address' => setting('address'),
            'phone' => setting('phone'),
            'igv' => setting('igv', '18'),
            'series' => setting('series', 'B001'),
            'ticket_footer' => setting('ticket_footer'),
        ],
    ]);
}

function action_settings(): void
{
    try {
        settings_update($_POST);
    } catch (Throwable $error) {
        fail_back('/ajustes', $error);
    }
    flash('ok', 'Datos del negocio guardados.');
    redirect('/ajustes');
}

function action_password(array $user): void
{
    $next = (string) ($_POST['new_password'] ?? '');
    $confirm = (string) ($_POST['confirm_password'] ?? '');
    try {
        if (!hash_equals($next, $confirm)) {
            throw new InvalidArgumentException('La confirmación no coincide.');
        }
        change_password((int) $user['id'], (string) ($_POST['current_password'] ?? ''), $next);
    } catch (Throwable $error) {
        fail_back('/ajustes', $error);
    }
    flash('ok', 'Clave actualizada.');
    redirect('/ajustes');
}

function api_barcode(): void
{
    $code = trim((string) ($_GET['code'] ?? ''));
    if ($code === '') {
        json_out(['ok' => false, 'error' => 'Falta el código.'], 422);
    }
    $product = product_by_barcode($code);
    if (!$product || (int) $product['active'] !== 1) {
        json_out(['ok' => false, 'error' => 'No hay un producto activo con ese código.'], 404);
    }
    json_out(['ok' => true, 'product' => product_public($product)]);
}

function api_sale(): void
{
    try {
        $sale = sale_create(body_json());
    } catch (InvalidArgumentException $error) {
        json_out(['ok' => false, 'error' => $error->getMessage()], 422);
    } catch (Throwable $error) {
        error_log($error->__toString());
        json_out(['ok' => false, 'error' => 'No se pudo registrar la venta.'], 500);
    }
    json_out(['ok' => true, 'sale' => sale_public($sale)]);
}
