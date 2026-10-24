
<?php
require_once __DIR__ . '/product_helpers.php';

requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: my_products.php');
    exit;
}

verifyCsrfToken();

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    header('Location: my_products.php?error=invalid');
    exit;
}

$userId = (int) $_SESSION['user_id'];

$stmt = $pdo->prepare(
    'SELECT id, image_url
     FROM products
     WHERE id = ? AND user_id = ?'
);
$stmt->execute([$id, $userId]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    header('Location: my_products.php?error=notfound');
    exit;
}

try {
    $stmt = $pdo->prepare(
        'DELETE FROM products
         WHERE id = ? AND user_id = ?'
    );
    $stmt->execute([$id, $userId]);

    if ($stmt->rowCount() !== 1) {
        header('Location: my_products.php?error=notfound');
        exit;
    }

    // Hapus gambar hanya setelah penghapusan produk berhasil.
    deleteProductImage($product['image_url']);

    header('Location: my_products.php?deleted=1');
    exit;

} catch (PDOException $exception) {
    error_log($exception->getMessage());

    // Kemungkinan produk masih direferensikan oleh pesanan.
    header('Location: my_products.php?error=delete_failed');
    exit;
}
