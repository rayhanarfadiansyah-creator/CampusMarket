
<?php
session_start();
require_once __DIR__ . '/config/database.php';

function fail($message, $status = 400) {
    http_response_code($status);
    exit(htmlspecialchars($message, ENT_QUOTES, 'UTF-8'));
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$productId = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
$quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT);
$name = trim($_POST['buyer_name'] ?? '');
$major = trim($_POST['buyer_major'] ?? '');
$building = $_POST['pickup_building'] ?? '';

if (!$productId || !$quantity || $quantity < 1 || $quantity > 100) {
    fail('Produk atau jumlah porsi tidak valid.');
}

if ($name === '' || mb_strlen($name) > 120 ||
    $major === '' || mb_strlen($major) > 120) {
    fail('Nama dan jurusan wajib diisi dengan benar.');
}

if (!in_array($building, ['Gedung A', 'Gedung B'], true)) {
    fail('Lokasi pengambilan tidak valid.');
}

$stmt = $pdo->prepare(
    "SELECT p.id, p.name, p.price, p.user_id
     FROM products p
     WHERE p.id = ?"
);
$stmt->execute([$productId]);
$product = $stmt->fetch();

if (!$product || !$product['user_id']) {
    fail('Produk tidak ditemukan atau belum memiliki penjual.', 404);
}

if ((float)$product['price'] < 0) {
    fail('Harga produk tidak valid.');
}

$total = round((float)$product['price'] * $quantity, 2);
$orderCode = 'CM-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(4)));

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare(
        "INSERT INTO orders
            (order_code, seller_id, buyer_name, buyer_major,
             pickup_building, total_amount, payment_status)
         VALUES (?, ?, ?, ?, ?, ?, 'Menunggu Pembayaran')"
    );
    $stmt->execute([
        $orderCode,
        $product['user_id'],
        $name,
        $major,
        $building,
        $total
    ]);

    $orderId = (int)$pdo->lastInsertId();

    $stmt = $pdo->prepare(
        "INSERT INTO order_items
            (order_id, product_id, product_name,
             unit_price, quantity, subtotal)
         VALUES (?, ?, ?, ?, ?, ?)"
    );
    $stmt->execute([
        $orderId,
        $product['id'],
        $product['name'],
        $product['price'],
        $quantity,
        $total
    ]);

    $pdo->commit();

    header('Location: upload_payment.php?order=' .
           rawurlencode($orderCode));
    exit;

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log($e->getMessage());
    fail('Pesanan gagal disimpan. Periksa konfigurasi database.', 500);
}
