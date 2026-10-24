
<?php

require_once __DIR__ . '/product_helpers.php';

requireLogin();

// Keranjang disimpan dalam session.
if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Penambahan produk hanya boleh melalui POST.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

// Periksa token keamanan.
verifyCsrfToken();

// Validasi ID produk.
$id = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    header('Location: index.php?cart_error=invalid');
    exit;
}

$userId = (int) $_SESSION['user_id'];

try {
    // Ambil data produk langsung dari database.
    $stmt = $pdo->prepare(
        'SELECT id, user_id, name
         FROM products
         WHERE id = ?
         LIMIT 1'
    );

    $stmt->execute([$id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        header('Location: index.php?cart_error=notfound');
        exit;
    }

    // Produk harus memiliki penjual agar dapat dipesan.
    if (empty($product['user_id'])) {
        header('Location: index.php?cart_error=noseller');
        exit;
    }

    // Penjual tidak boleh membeli produknya sendiri.
    if ((int) $product['user_id'] === $userId) {
        header('Location: index.php?cart_error=ownproduct');
        exit;
    }

    // Tambahkan satu unit atau naikkan jumlahnya.
    $currentQuantity = (int) ($_SESSION['cart'][$id] ?? 0);

    if ($currentQuantity >= 99) {
        header('Location: cart.php?error=maxquantity');
        exit;
    }

    $_SESSION['cart'][$id] = $currentQuantity + 1;

    header('Location: cart.php?added=1');
    exit;

} catch (PDOException $e) {
    error_log($e->getMessage());

    http_response_code(500);
    exit('Keranjang gagal diperbarui. Silakan coba lagi.');
}
