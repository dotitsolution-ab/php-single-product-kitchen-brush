<?php

declare(strict_types=1);

function active_product(): ?array
{
    $stmt = db()->query('SELECT * FROM products WHERE is_active = 1 ORDER BY id ASC LIMIT 1');
    $product = $stmt->fetch();
    return $product ?: null;
}

function product_highlights(array $product): array
{
    $lines = preg_split('/\r\n|\r|\n/', (string)($product['highlights'] ?? '')) ?: [];
    return array_values(array_filter(array_map('trim', $lines)));
}

function landing_defaults(): array
{
    return [
        'badge' => 'স্মার্ট ক্লিনিং, সহজ জীবন',
        'hero_title' => '৩৬০° রোটেটিং কিচেন ক্লিনিং ব্রাশ',
        'hero_subtitle' => 'দাগ দূর হবে সহজে, ক্লিনিং হবে আরামে ও নিরাপদে',
        'discount_label' => '২৫% ছাড়',
        'cta_text' => 'এখনই অর্ডার করুন',
        'hero_image_url' => 'assets/images/kitchen-brush-hero-drain.jpg',
        'demo_image_url' => 'assets/images/kitchen-brush-plate-demo.jpg',
        'delivery_inside_charge' => '60',
        'delivery_outside_charge' => '120',
        'feature_rows' => "৩৬০° রোটেটিং ব্রাশ হেড|সব কোণায় পরিষ্কার|assets/images/kitchen-brush-pan-close.jpg\nশক্ত ব্রাশ|দাগ তুলতে সাহায্য করে|assets/images/kitchen-brush-frypan-foam.jpg\nলম্বা হ্যান্ডেল|ব্যবহারে সহজ|assets/images/kitchen-brush-pan-cleaning.jpg\nওয়াল হ্যাঙ্গিং|স্টোরেজ সহজ|assets/images/kitchen-brush-hanging-storage.jpg",
        'usage_rows' => "প্লেট|assets/images/kitchen-brush-plate-demo.jpg\nফ্রাইপ্যান|assets/images/kitchen-brush-frypan-foam.jpg\nহাঁড়ি|assets/images/kitchen-brush-pan-cleaning.jpg\nস্টোরেজ|assets/images/kitchen-brush-hanging-storage.jpg",
        'reason_rows' => "শ্রম ও সময় বাঁচায়\nদাগ দূর করে সহজে, স্ক্র্যাচ হয় না\nটেকসই রোটেশন, ঝামেলামুক্ত প্রয়োগ\nপচা গন্ধ কমায়, দাগ থাকে না",
    ];
}

function landing_value(string $key): string
{
    $defaults = landing_defaults();
    return setting('landing_' . $key, (string)($defaults[$key] ?? ''));
}

function landing_image_value(string $key): string
{
    $value = landing_value($key);
    $defaults = landing_defaults();

    if ($value === '' || str_contains($value, 'placehold.co')) {
        return (string)($defaults[$key] ?? '');
    }

    return $value;
}

function image_src(string $value, string $fallback = ''): string
{
    $value = trim($value);
    if ($value === '') {
        $value = $fallback;
    }
    if ($value === '') {
        return '';
    }
    if (preg_match('/^(https?:)?\/\//', $value) === 1 || str_starts_with($value, '/')) {
        return $value;
    }

    return base_url($value);
}

function landing_rows(string $key, array $columns): array
{
    $rows = [];
    $lines = preg_split('/\r\n|\r|\n/', landing_value($key)) ?: [];

    foreach ($lines as $line) {
        $parts = array_map('trim', explode('|', trim($line)));
        if ($parts === [''] || $parts[0] === '') {
            continue;
        }

        $row = [];
        foreach ($columns as $index => $column) {
            $row[$column] = (string)($parts[$index] ?? '');
        }
        $rows[] = $row;
    }

    return $rows;
}

function delivery_options(array $product): array
{
    return [
        'inside_dhaka' => [
            'label' => 'ঢাকার ভিতরে',
            'charge' => (float)landing_value('delivery_inside_charge'),
        ],
        'outside_dhaka' => [
            'label' => 'ঢাকার বাইরে',
            'charge' => (float)landing_value('delivery_outside_charge'),
        ],
    ];
}

function save_landing_content(array $data): void
{
    foreach (array_keys(landing_defaults()) as $key) {
        save_setting('landing_' . $key, trim((string)($data['landing_' . $key] ?? '')));
    }
}

function seed_kitchen_brush_content(): void
{
    $product = active_product();
    if (!$product) {
        throw new RuntimeException('No active product found.');
    }

    $data = landing_defaults();
    $data['name'] = '৩৬০° রোটেটিং কিচেন ক্লিনিং ব্রাশ';
    $data['tagline'] = 'স্মার্ট ক্লিনিং, সহজ জীবন';
    $data['description'] = 'দাগ দূর হবে সহজে, ক্লিনিং হবে আরামে ও নিরাপদে।';
    $data['highlights'] = "৩৬০° রোটেটিং ব্রাশ হেড\nশক্ত ব্রাশ দাগ তুলতে সহায়ক\nলম্বা হ্যান্ডেল ব্যবহারে সহজ\nওয়াল হ্যাঙ্গিং স্টোরেজ";
    $data['price'] = '299';
    $data['compare_price'] = '399';
    $data['stock'] = (string)max(100, (int)$product['stock']);
    $data['image_url'] = 'assets/images/kitchen-brush-pan-cleaning.jpg';
    foreach ($data as $key => $value) {
        if (array_key_exists($key, landing_defaults())) {
            $data['landing_' . $key] = $value;
        }
    }

    save_product($data);
}

function create_cod_order(array $data): array
{
    $product = active_product();
    if (!$product) {
        throw new RuntimeException('No active product is available.');
    }

    $name = trim((string)($data['name'] ?? ''));
    $phone = normalize_phone((string)($data['phone'] ?? ''));
    $address = trim((string)($data['address'] ?? ''));
    $deliveryArea = (string)($data['delivery_area'] ?? 'inside_dhaka');
    $deliveryOptions = delivery_options($product);
    if (!array_key_exists($deliveryArea, $deliveryOptions)) {
        $deliveryArea = 'inside_dhaka';
    }
    $districtArea = $deliveryOptions[$deliveryArea]['label'];
    $deliveryNote = trim((string)($data['delivery_note'] ?? ''));
    $quantity = max(1, (int)($data['quantity'] ?? 1));

    if ($name === '' || strlen($name) > 120) {
        throw new InvalidArgumentException('Please enter a valid name.');
    }
    if (!valid_bd_phone($phone)) {
        throw new InvalidArgumentException('Please enter a valid Bangladesh phone number.');
    }
    if ($address === '' || strlen($address) > 500) {
        throw new InvalidArgumentException('Please enter a valid delivery address.');
    }
    if ((int)$product['stock'] < $quantity) {
        throw new InvalidArgumentException('Requested quantity is not available.');
    }

    $unitPrice = (float)$product['price'];
    $subtotal = $unitPrice * $quantity;
    $deliveryCharge = (float)$deliveryOptions[$deliveryArea]['charge'];
    $total = $subtotal + $deliveryCharge;
    $pdo = db();

    $pdo->beginTransaction();
    try {
        $customerId = upsert_customer($name, $phone, $address, $districtArea);
        $orderNumber = unique_order_number();

        $stmt = $pdo->prepare(
            'INSERT INTO orders
            (order_number, customer_id, customer_name, customer_phone, customer_address, district_area, delivery_note, subtotal, delivery_charge, total, payment_method, status)
            VALUES
            (:order_number, :customer_id, :customer_name, :customer_phone, :customer_address, :district_area, :delivery_note, :subtotal, :delivery_charge, :total, :payment_method, :status)'
        );
        $stmt->execute([
            'order_number' => $orderNumber,
            'customer_id' => $customerId,
            'customer_name' => $name,
            'customer_phone' => $phone,
            'customer_address' => $address,
            'district_area' => $districtArea,
            'delivery_note' => $deliveryNote,
            'subtotal' => $subtotal,
            'delivery_charge' => $deliveryCharge,
            'total' => $total,
            'payment_method' => 'COD',
            'status' => 'Pending',
        ]);

        $orderId = (int)$pdo->lastInsertId();
        $item = $pdo->prepare(
            'INSERT INTO order_items (order_id, product_id, product_name, unit_price, quantity, line_total)
             VALUES (:order_id, :product_id, :product_name, :unit_price, :quantity, :line_total)'
        );
        $item->execute([
            'order_id' => $orderId,
            'product_id' => (int)$product['id'],
            'product_name' => $product['name'],
            'unit_price' => $unitPrice,
            'quantity' => $quantity,
            'line_total' => $subtotal,
        ]);

        $stock = $pdo->prepare('UPDATE products SET stock = stock - :quantity_remove WHERE id = :id AND stock >= :quantity_check');
        $stock->execute([
            'quantity_remove' => $quantity,
            'quantity_check' => $quantity,
            'id' => (int)$product['id'],
        ]);
        if ($stock->rowCount() !== 1) {
            throw new InvalidArgumentException('Requested quantity is no longer available.');
        }

        $pdo->commit();
        return order_with_items_by_id($orderId) ?? [];
    } catch (Throwable $exception) {
        $pdo->rollBack();
        throw $exception;
    }
}

function upsert_customer(string $name, string $phone, string $address, string $districtArea): int
{
    $stmt = db()->prepare('SELECT id FROM customers WHERE phone = :phone LIMIT 1');
    $stmt->execute(['phone' => $phone]);
    $customer = $stmt->fetch();

    if ($customer) {
        $update = db()->prepare(
            'UPDATE customers SET name = :name, address = :address, district_area = :district_area, updated_at = NOW() WHERE id = :id'
        );
        $update->execute([
            'name' => $name,
            'address' => $address,
            'district_area' => $districtArea,
            'id' => (int)$customer['id'],
        ]);
        return (int)$customer['id'];
    }

    $insert = db()->prepare(
        'INSERT INTO customers (name, phone, address, district_area) VALUES (:name, :phone, :address, :district_area)'
    );
    $insert->execute([
        'name' => $name,
        'phone' => $phone,
        'address' => $address,
        'district_area' => $districtArea,
    ]);

    return (int)db()->lastInsertId();
}

function unique_order_number(): string
{
    do {
        $number = 'SP' . date('ymd') . strtoupper(bin2hex(random_bytes(3)));
        $stmt = db()->prepare('SELECT id FROM orders WHERE order_number = :order_number LIMIT 1');
        $stmt->execute(['order_number' => $number]);
    } while ($stmt->fetch());

    return $number;
}

function order_with_items_by_id(int $id): ?array
{
    $stmt = db()->prepare('SELECT * FROM orders WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $id]);
    $order = $stmt->fetch();
    if (!$order) {
        return null;
    }

    $order['items'] = order_items((int)$order['id']);
    $order['shipment'] = order_shipment((int)$order['id']);
    return $order;
}

function order_by_number(string $orderNumber, ?string $phone = null): ?array
{
    $sql = 'SELECT * FROM orders WHERE order_number = :order_number';
    $params = ['order_number' => strtoupper(trim($orderNumber))];

    if ($phone !== null) {
        $sql .= ' AND customer_phone = :phone';
        $params['phone'] = normalize_phone($phone);
    }

    $sql .= ' LIMIT 1';
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    $order = $stmt->fetch();
    if (!$order) {
        return null;
    }

    $order['items'] = order_items((int)$order['id']);
    $order['shipment'] = order_shipment((int)$order['id']);
    return $order;
}

function order_items(int $orderId): array
{
    $stmt = db()->prepare('SELECT * FROM order_items WHERE order_id = :order_id ORDER BY id ASC');
    $stmt->execute(['order_id' => $orderId]);
    return $stmt->fetchAll();
}

function order_shipment(int $orderId): ?array
{
    $stmt = db()->prepare('SELECT * FROM courier_shipments WHERE order_id = :order_id LIMIT 1');
    $stmt->execute(['order_id' => $orderId]);
    $shipment = $stmt->fetch();
    return $shipment ?: null;
}

function list_orders(array $filters = []): array
{
    $where = [];
    $params = [];

    $status = (string)($filters['status'] ?? '');
    if ($status !== '' && in_array($status, status_options(), true)) {
        $where[] = 'status = :status';
        $params['status'] = $status;
    }

    $query = trim((string)($filters['q'] ?? ''));
    if ($query !== '') {
        $where[] = '(order_number LIKE :query_order OR customer_name LIKE :query_customer OR customer_phone LIKE :query_phone)';
        $likeQuery = '%' . $query . '%';
        $phoneQuery = normalize_phone($query);
        $params['query_order'] = $likeQuery;
        $params['query_customer'] = $likeQuery;
        $params['query_phone'] = '%' . ($phoneQuery !== '' ? $phoneQuery : $query) . '%';
    }

    $sql = 'SELECT * FROM orders';
    if ($where) {
        $sql .= ' WHERE ' . implode(' AND ', $where);
    }
    $sql .= ' ORDER BY created_at DESC LIMIT 100';

    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function dashboard_stats(): array
{
    $stats = [
        'orders_today' => 0,
        'orders_total' => 0,
        'sales_total' => 0.0,
        'pending' => 0,
    ];

    $row = db()->query(
        "SELECT
            COUNT(*) AS orders_total,
            COALESCE(SUM(CASE WHEN DATE(created_at) = CURDATE() THEN 1 ELSE 0 END), 0) AS orders_today,
            COALESCE(SUM(total), 0) AS sales_total,
            COALESCE(SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END), 0) AS pending
         FROM orders"
    )->fetch();

    if ($row) {
        $stats = array_merge($stats, $row);
    }

    return $stats;
}

function update_order_status(int $orderId, string $status): void
{
    if (!in_array($status, status_options(), true)) {
        throw new InvalidArgumentException('Invalid status.');
    }

    $stmt = db()->prepare('UPDATE orders SET status = :status, updated_at = NOW() WHERE id = :id');
    $stmt->execute([
        'status' => $status,
        'id' => $orderId,
    ]);
}

function save_manual_shipment(int $orderId, array $data): void
{
    $stmt = db()->prepare(
        'INSERT INTO courier_shipments
        (order_id, courier_name, consignment_id, tracking_code, shipment_status, raw_response)
        VALUES (:order_id, :courier_name, :consignment_id, :tracking_code, :shipment_status, :raw_response)
        ON DUPLICATE KEY UPDATE
            courier_name = VALUES(courier_name),
            consignment_id = VALUES(consignment_id),
            tracking_code = VALUES(tracking_code),
            shipment_status = VALUES(shipment_status),
            raw_response = VALUES(raw_response),
            updated_at = NOW()'
    );
    $stmt->execute([
        'order_id' => $orderId,
        'courier_name' => trim((string)($data['courier_name'] ?? 'Steadfast')),
        'consignment_id' => trim((string)($data['consignment_id'] ?? '')),
        'tracking_code' => trim((string)($data['tracking_code'] ?? '')),
        'shipment_status' => trim((string)($data['shipment_status'] ?? '')),
        'raw_response' => trim((string)($data['raw_response'] ?? '')) === '' ? null : (string)$data['raw_response'],
    ]);
}

function create_steadfast_shipment(int $orderId): array
{
    $order = order_with_items_by_id($orderId);
    if (!$order) {
        throw new InvalidArgumentException('Order not found.');
    }

    $response = (new SteadfastClient())->createOrder($order);
    $consignment = $response['consignment']
        ?? $response['data']['consignment']
        ?? $response['data']
        ?? [];

    $consignmentId = (string)($consignment['consignment_id'] ?? $consignment['id'] ?? '');
    $trackingCode = (string)($consignment['tracking_code'] ?? $consignment['trackingCode'] ?? '');
    $shipmentStatus = (string)($consignment['status'] ?? $response['status'] ?? 'Created');

    save_manual_shipment($orderId, [
        'courier_name' => 'Steadfast',
        'consignment_id' => $consignmentId,
        'tracking_code' => $trackingCode,
        'shipment_status' => $shipmentStatus,
        'raw_response' => json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
    ]);

    if ($order['status'] === 'Pending' || $order['status'] === 'Confirmed') {
        update_order_status($orderId, 'Processing');
    }

    return $response;
}

function save_product(array $data): void
{
    $product = active_product();
    if (!$product) {
        throw new RuntimeException('No product found.');
    }

    $stmt = db()->prepare(
        'UPDATE products SET
            name = :name,
            tagline = :tagline,
            description = :description,
            highlights = :highlights,
            price = :price,
            compare_price = :compare_price,
            delivery_charge = :delivery_charge,
            stock = :stock,
            image_url = :image_url,
            updated_at = NOW()
         WHERE id = :id'
    );
    $stmt->execute([
        'name' => trim((string)$data['name']),
        'tagline' => trim((string)$data['tagline']),
        'description' => trim((string)$data['description']),
        'highlights' => trim((string)$data['highlights']),
        'price' => (float)$data['price'],
        'compare_price' => $data['compare_price'] === '' ? null : (float)$data['compare_price'],
        'delivery_charge' => (float)($data['landing_delivery_inside_charge'] ?? $data['delivery_charge']),
        'stock' => (int)$data['stock'],
        'image_url' => trim((string)$data['image_url']),
        'id' => (int)$product['id'],
    ]);

    save_landing_content($data);
}
