<?php

declare(strict_types=1);

function product_public(array $product): array
{
    return [
        'id' => (int) $product['id'],
        'name' => $product['name'],
        'category' => $product['category'],
        'barcode' => $product['barcode'],
        'price_cents' => (int) $product['price_cents'],
        'stock' => (int) $product['stock'],
        'min_stock' => (int) $product['min_stock'],
        'active' => (int) $product['active'] === 1,
    ];
}

function sale_public(array $sale): array
{
    return [
        'id' => (int) $sale['id'],
        'number' => $sale['number'],
        'total_cents' => (int) $sale['total_cents'],
        'paid_cents' => (int) $sale['paid_cents'],
        'change_cents' => (int) $sale['change_cents'],
        'payment_method' => $sale['payment_method'],
        'url' => '/ventas/' . (int) $sale['id'],
    ];
}

function product_find(int $id): ?array
{
    $stmt = db()->prepare('SELECT * FROM products WHERE id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();

    return $row ?: null;
}

function product_by_barcode(string $code): ?array
{
    $code = trim($code);
    $candidates = [$code];
    if (preg_match('/^\d{12}$/', $code)) {
        $candidates[] = '0' . $code;
    }
    if (preg_match('/^\d{13}$/', $code) && str_starts_with($code, '0')) {
        $candidates[] = substr($code, 1);
    }
    $stmt = db()->prepare('SELECT * FROM products WHERE barcode = ?');
    foreach ($candidates as $candidate) {
        $stmt->execute([$candidate]);
        $row = $stmt->fetch();
        if ($row) {
            return $row;
        }
    }

    return null;
}

function products_search(string $query, bool $onlyActive = false, int $limit = 300): array
{
    $limit = max(1, min(500, $limit));
    $where = [];
    $params = [];
    if ($onlyActive) {
        $where[] = 'active = 1';
    }
    $query = trim($query);
    if ($query !== '') {
        $where[] = "(name LIKE ? ESCAPE '\\' OR barcode LIKE ? ESCAPE '\\' OR category LIKE ? ESCAPE '\\')";
        $like = like_pattern($query);
        $params = [$like, $like, $like];
    }
    $sql = 'SELECT * FROM products';
    if ($where) {
        $sql .= ' WHERE ' . implode(' AND ', $where);
    }
    $sql .= ' ORDER BY name COLLATE NOCASE LIMIT ' . $limit;
    $stmt = db()->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetchAll();
}

function categories(): array
{
    $rows = db()->query("SELECT DISTINCT category FROM products WHERE category != '' ORDER BY category COLLATE NOCASE")->fetchAll();
    $found = array_map(static fn (array $row): string => (string) $row['category'], $rows);

    return array_values(array_unique([...$found, 'Abarrotes', 'Bebidas', 'Limpieza', 'Panadería', 'Snacks']));
}

function low_stock(int $limit = 8): array
{
    $limit = max(1, min(50, $limit));
    $sql = 'SELECT * FROM products WHERE active = 1 AND stock <= min_stock ORDER BY stock ASC, name COLLATE NOCASE LIMIT ' . $limit;

    return db()->query($sql)->fetchAll();
}

function low_stock_count(): int
{
    return (int) db()->query('SELECT COUNT(*) FROM products WHERE active = 1 AND stock <= min_stock')->fetchColumn();
}

function product_save(?int $id, array $input): int
{
    $name = preg_replace('/\s+/', ' ', trim((string) ($input['name'] ?? ''))) ?? '';
    $category = preg_replace('/\s+/', ' ', trim((string) ($input['category'] ?? ''))) ?? '';
    $barcode = trim((string) ($input['barcode'] ?? ''));
    $price = parse_money($input['price'] ?? '');
    $costRaw = trim((string) ($input['cost'] ?? ''));
    $cost = $costRaw === '' ? 0 : parse_money($costRaw);
    $minStock = parse_whole($input['min_stock'] ?? '0', 'El stock mínimo');
    if ($name === '' || mb_strlen($name) > 80) {
        throw new InvalidArgumentException('El nombre debe tener entre 1 y 80 caracteres.');
    }
    if (mb_strlen($category) > 40) {
        throw new InvalidArgumentException('La categoría es demasiado larga.');
    }

    return db_tx(function (PDO $pdo) use ($id, $name, $category, $barcode, $price, $cost, $minStock, $input): int {
        if ($barcode !== '') {
            assert_barcode($barcode);
            $duplicate = $pdo->prepare('SELECT id FROM products WHERE barcode = ? AND id != ?');
            $duplicate->execute([$barcode, $id ?? 0]);
            if ($duplicate->fetch()) {
                throw new InvalidArgumentException('Ese código ya pertenece a otro producto.');
            }
        }
        $now = now_lima();
        if ($id === null) {
            if ($barcode === '') {
                $barcode = generate_barcode($pdo);
            }
            $stock = parse_whole($input['stock'] ?? '0', 'El stock inicial');
            $stmt = $pdo->prepare('INSERT INTO products (name, category, barcode, price_cents, cost_cents, stock, min_stock, active, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, 1, ?, ?)');
            $stmt->execute([$name, $category, $barcode, $price, $cost, $stock, $minStock, $now, $now]);
            $newId = (int) $pdo->lastInsertId();
            if ($stock > 0) {
                $pdo->prepare('INSERT INTO movements (product_id, type, qty, stock_after, note, sale_id, user_id, created_at) VALUES (?, "in", ?, ?, "Stock inicial", NULL, ?, ?)')
                    ->execute([$newId, $stock, $stock, current_user_id(), $now]);
            }

            return $newId;
        }

        $current = $pdo->prepare('SELECT barcode FROM products WHERE id = ?');
        $current->execute([$id]);
        $existingBarcode = $current->fetchColumn();
        if (!is_string($existingBarcode)) {
            throw new InvalidArgumentException('Producto no encontrado.');
        }
        if ($barcode === '') {
            $barcode = $existingBarcode;
        }
        $stmt = $pdo->prepare('UPDATE products SET name = ?, category = ?, barcode = ?, price_cents = ?, cost_cents = ?, min_stock = ?, updated_at = ? WHERE id = ?');
        $stmt->execute([$name, $category, $barcode, $price, $cost, $minStock, $now, $id]);

        return $id;
    });
}

function product_set_active(int $id, bool $active): void
{
    $product = product_find($id);
    if (!$product) {
        throw new InvalidArgumentException('Producto no encontrado.');
    }
    db()->prepare('UPDATE products SET active = ?, updated_at = ? WHERE id = ?')
        ->execute([$active ? 1 : 0, now_lima(), $id]);
}

function stock_reasons(string $type): array
{
    if ($type === 'in') {
        return ['Compra a proveedor', 'Devolución de cliente', 'Ajuste de inventario'];
    }

    return ['Merma', 'Vencimiento', 'Consumo interno', 'Devolución a proveedor', 'Ajuste de inventario'];
}

function stock_apply(int $productId, string $type, int $qty, string $note): void
{
    if (!in_array($type, ['in', 'out'], true)) {
        throw new InvalidArgumentException('Tipo de movimiento inválido.');
    }
    if ($qty < 1 || $qty > 100000) {
        throw new InvalidArgumentException('Cantidad inválida.');
    }
    $note = trim($note);
    if ($note === '' || mb_strlen($note) > 180) {
        throw new InvalidArgumentException('Indica un motivo de hasta 180 caracteres.');
    }
    db_tx(function (PDO $pdo) use ($productId, $type, $qty, $note): void {
        $stmt = $pdo->prepare('SELECT * FROM products WHERE id = ?');
        $stmt->execute([$productId]);
        $product = $stmt->fetch();
        if (!$product) {
            throw new InvalidArgumentException('Producto no encontrado.');
        }
        $stock = (int) $product['stock'];
        if ($type === 'out' && $stock < $qty) {
            throw new InvalidArgumentException('No puedes sacar ' . $qty . '. Stock actual: ' . $stock . '.');
        }
        $after = $type === 'in' ? $stock + $qty : $stock - $qty;
        $now = now_lima();
        $pdo->prepare('UPDATE products SET stock = ?, updated_at = ? WHERE id = ?')->execute([$after, $now, $productId]);
        $pdo->prepare('INSERT INTO movements (product_id, type, qty, stock_after, note, sale_id, user_id, created_at) VALUES (?, ?, ?, ?, ?, NULL, ?, ?)')
            ->execute([$productId, $type, $qty, $after, $note, current_user_id(), $now]);
    });
}

function movements_recent(int $limit = 40): array
{
    $limit = max(1, min(200, $limit));
    $sql = 'SELECT m.*, p.name AS product_name, u.name AS user_name
        FROM movements m
        JOIN products p ON p.id = m.product_id
        LEFT JOIN users u ON u.id = m.user_id
        ORDER BY m.id DESC
        LIMIT ' . $limit;

    return db()->query($sql)->fetchAll();
}

function sale_create(array $input): array
{
    $items = $input['items'] ?? null;
    if (!is_array($items) || $items === []) {
        throw new InvalidArgumentException('Agrega al menos un producto.');
    }
    if (count($items) > 80) {
        throw new InvalidArgumentException('La boleta tiene demasiados ítems.');
    }
    $qtyById = [];
    foreach ($items as $item) {
        if (!is_array($item)) {
            throw new InvalidArgumentException('Hay un ítem inválido.');
        }
        $id = (int) ($item['id'] ?? 0);
        $qty = (int) ($item['qty'] ?? 0);
        if ($id < 1 || $qty < 1 || $qty > 999) {
            throw new InvalidArgumentException('Cantidad inválida.');
        }
        $qtyById[$id] = ($qtyById[$id] ?? 0) + $qty;
        if ($qtyById[$id] > 999) {
            throw new InvalidArgumentException('Cantidad inválida.');
        }
    }

    $method = (string) ($input['payment_method'] ?? '');
    if (!in_array($method, payment_methods(), true)) {
        throw new InvalidArgumentException('Elige un medio de pago.');
    }
    $customer = preg_replace('/\s+/', ' ', trim((string) ($input['customer_name'] ?? ''))) ?? '';
    $doc = strtoupper(trim((string) ($input['customer_doc'] ?? '')));
    if (mb_strlen($customer) > 120) {
        throw new InvalidArgumentException('El nombre del cliente es demasiado largo.');
    }
    if ($doc !== '' && !preg_match('/^[A-Z0-9]{6,15}$/', $doc)) {
        throw new InvalidArgumentException('El documento del cliente no es válido.');
    }
    $paidInput = $input['paid'] ?? '0';

    $saleId = db_tx(function (PDO $pdo) use ($qtyById, $method, $customer, $doc, $paidInput): int {
        $ids = array_keys($qtyById);
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $pdo->prepare('SELECT * FROM products WHERE id IN (' . $placeholders . ')');
        $stmt->execute($ids);
        $products = [];
        foreach ($stmt->fetchAll() as $row) {
            $products[(int) $row['id']] = $row;
        }
        if (count($products) !== count($ids)) {
            throw new InvalidArgumentException('Hay un producto que ya no existe.');
        }

        $total = 0;
        $lines = [];
        foreach ($qtyById as $id => $qty) {
            $product = $products[$id];
            if ((int) $product['active'] !== 1) {
                throw new InvalidArgumentException($product['name'] . ' está inactivo.');
            }
            if ((int) $product['stock'] < $qty) {
                throw new InvalidArgumentException('Stock insuficiente de ' . $product['name'] . '. Disponible: ' . $product['stock'] . '.');
            }
            $lineTotal = (int) $product['price_cents'] * $qty;
            $total += $lineTotal;
            $lines[] = ['product' => $product, 'qty' => $qty, 'total' => $lineTotal];
        }
        if ($total < 0 || $total > 99_999_999) {
            throw new InvalidArgumentException('El total de la boleta no es válido.');
        }

        $rateRaw = setting_value($pdo, 'igv', '18');
        [$base, $igv] = split_igv($total, (float) $rateRaw);
        if ($method === 'efectivo') {
            $paid = parse_money($paidInput);
            if ($paid < $total) {
                throw new InvalidArgumentException('El efectivo no cubre el total.');
            }
        } else {
            $paid = $total;
        }
        $change = $paid - $total;

        $series = strtoupper(setting_value($pdo, 'series', 'B001'));
        if (!preg_match('/^[A-Z0-9]{3,4}$/', $series)) {
            $series = 'B001';
        }
        $seq = $pdo->prepare('SELECT last_number FROM sale_seq WHERE series = ?');
        $seq->execute([$series]);
        $last = $seq->fetchColumn();
        $next = $last === false ? 1 : ((int) $last + 1);
        if ($next > 99999999) {
            throw new InvalidArgumentException('La serie llegó al máximo de boletas.');
        }
        if ($last === false) {
            $pdo->prepare('INSERT INTO sale_seq (series, last_number) VALUES (?, ?)')->execute([$series, $next]);
        } else {
            $pdo->prepare('UPDATE sale_seq SET last_number = ? WHERE series = ?')->execute([$next, $series]);
        }
        $number = $series . '-' . str_pad((string) $next, 8, '0', STR_PAD_LEFT);
        $now = now_lima();
        $userId = current_user_id();
        $pdo->prepare('INSERT INTO sales (
            number, series, correlative, customer_name, customer_doc,
            subtotal_cents, igv_cents, igv_rate, total_cents,
            payment_method, paid_cents, change_cents, user_id, created_at,
            biz_name, biz_ruc, biz_address, biz_phone, footer
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)')->execute([
            $number,
            $series,
            $next,
            $customer,
            $doc,
            $base,
            $igv,
            $rateRaw,
            $total,
            $method,
            $paid,
            $change,
            $userId,
            $now,
            setting_value($pdo, 'business_name', 'Midas'),
            setting_value($pdo, 'ruc', ''),
            setting_value($pdo, 'address', ''),
            setting_value($pdo, 'phone', ''),
            setting_value($pdo, 'ticket_footer', ''),
        ]);
        $newSaleId = (int) $pdo->lastInsertId();
        $itemStmt = $pdo->prepare('INSERT INTO sale_items (sale_id, product_id, name, barcode, qty, unit_price_cents, line_total_cents) VALUES (?, ?, ?, ?, ?, ?, ?)');
        $stockStmt = $pdo->prepare('UPDATE products SET stock = stock - ?, updated_at = ? WHERE id = ? AND stock >= ? AND active = 1');
        $moveStmt = $pdo->prepare('INSERT INTO movements (product_id, type, qty, stock_after, note, sale_id, user_id, created_at) VALUES (?, "sale", ?, ?, ?, ?, ?, ?)');
        foreach ($lines as $line) {
            $product = $line['product'];
            $qty = $line['qty'];
            $stockStmt->execute([$qty, $now, $product['id'], $qty]);
            $changed = (int) $pdo->query('SELECT changes()')->fetchColumn();
            if ($changed !== 1) {
                throw new InvalidArgumentException('Stock insuficiente de ' . $product['name'] . '.');
            }
            $itemStmt->execute([
                $newSaleId,
                $product['id'],
                $product['name'],
                $product['barcode'],
                $qty,
                $product['price_cents'],
                $line['total'],
            ]);
            $moveStmt->execute([
                $product['id'],
                $qty,
                (int) $product['stock'] - $qty,
                'Boleta ' . $number,
                $newSaleId,
                $userId,
                $now,
            ]);
        }

        return $newSaleId;
    });

    $sale = sale_find($saleId);
    if (!$sale) {
        throw new RuntimeException('La venta no quedó registrada.');
    }

    return $sale;
}

function sale_find(int $id): ?array
{
    $stmt = db()->prepare('SELECT s.*, u.name AS cashier FROM sales s LEFT JOIN users u ON u.id = s.user_id WHERE s.id = ?');
    $stmt->execute([$id]);
    $sale = $stmt->fetch();
    if (!$sale) {
        return null;
    }
    $items = db()->prepare('SELECT * FROM sale_items WHERE sale_id = ? ORDER BY id');
    $items->execute([$id]);
    $sale['items'] = $items->fetchAll();

    return $sale;
}

function sales_search(string $query, int $offset = 0): array
{
    $offset = max(0, $offset);
    $query = trim($query);
    $sql = 'SELECT s.*, u.name AS cashier FROM sales s LEFT JOIN users u ON u.id = s.user_id';
    $params = [];
    if ($query !== '') {
        $sql .= " WHERE s.number LIKE ? ESCAPE '\\' OR s.customer_name LIKE ? ESCAPE '\\' OR s.customer_doc LIKE ? ESCAPE '\\'";
        $like = like_pattern($query);
        $params = [$like, $like, $like];
    }
    $sql .= ' ORDER BY s.id DESC LIMIT 30 OFFSET ' . $offset;
    $stmt = db()->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetchAll();
}

function today_summary(): array
{
    $start = lima_now()->format('Y-m-d') . ' 00:00:00';
    $stmt = db()->prepare('SELECT payment_method, COUNT(*) AS tickets, COALESCE(SUM(total_cents), 0) AS total FROM sales WHERE created_at >= ? GROUP BY payment_method');
    $stmt->execute([$start]);
    $byMethod = [];
    $tickets = 0;
    $total = 0;
    foreach ($stmt as $row) {
        $byMethod[(string) $row['payment_method']] = [
            'tickets' => (int) $row['tickets'],
            'total' => (int) $row['total'],
        ];
        $tickets += (int) $row['tickets'];
        $total += (int) $row['total'];
    }

    return [
        'tickets' => $tickets,
        'total' => $total,
        'by_method' => $byMethod,
    ];
}

function settings_update(array $input): void
{
    $name = preg_replace('/\s+/', ' ', trim((string) ($input['business_name'] ?? ''))) ?? '';
    $ruc = preg_replace('/\s+/', '', (string) ($input['ruc'] ?? '')) ?? '';
    $address = preg_replace('/\s+/', ' ', trim((string) ($input['address'] ?? ''))) ?? '';
    $phone = preg_replace('/\s+/', ' ', trim((string) ($input['phone'] ?? ''))) ?? '';
    $igvRaw = str_replace(',', '.', trim((string) ($input['igv'] ?? '18')));
    $series = strtoupper(trim((string) ($input['series'] ?? '')));
    $footer = trim((string) ($input['ticket_footer'] ?? ''));
    if ($name === '' || mb_strlen($name) > 80) {
        throw new InvalidArgumentException('Indica el nombre del negocio.');
    }
    if ($ruc !== '' && !preg_match('/^\d{11}$/', $ruc)) {
        throw new InvalidArgumentException('El RUC debe tener 11 dígitos.');
    }
    if (mb_strlen($address) > 140 || mb_strlen($phone) > 40 || mb_strlen($footer) > 180) {
        throw new InvalidArgumentException('Hay un texto demasiado largo.');
    }
    if (!preg_match('/^\d{1,2}(\.\d{1,2})?$/', $igvRaw)) {
        throw new InvalidArgumentException('El IGV debe ser un porcentaje, por ejemplo 18.');
    }
    $igv = (float) $igvRaw;
    if ($igv < 0 || $igv > 50) {
        throw new InvalidArgumentException('El IGV queda fuera del rango permitido.');
    }
    if (!preg_match('/^[A-Z0-9]{3,4}$/', $series)) {
        throw new InvalidArgumentException('La serie debe tener 3 o 4 letras o números. Ejemplo: B001.');
    }
    $igvStored = rtrim(rtrim(number_format($igv, 2, '.', ''), '0'), '.');
    if ($igvStored === '') {
        $igvStored = '0';
    }
    $pairs = [
        'business_name' => $name,
        'ruc' => $ruc,
        'address' => $address,
        'phone' => $phone,
        'igv' => $igvStored,
        'series' => $series,
        'ticket_footer' => $footer,
    ];
    db_tx(function (PDO $pdo) use ($pairs): void {
        $stmt = $pdo->prepare('INSERT INTO settings (key, value) VALUES (?, ?) ON CONFLICT(key) DO UPDATE SET value = excluded.value');
        foreach ($pairs as $key => $value) {
            $stmt->execute([$key, $value]);
        }
    });
}
