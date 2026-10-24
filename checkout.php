
<?php

require_once __DIR__ . '/product_helpers.php';
requireLogin();

$userId = (int) $_SESSION['user_id'];

if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

function checkoutPageEscape($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function checkoutPageRupiah($amount): string
{
    return 'Rp' . number_format((float) $amount, 0, ',', '.');
}

// Ambil seller_id dari URL saat GET, atau dari form saat POST.
$sellerId = filter_input(
    $_SERVER['REQUEST_METHOD'] === 'POST' ? INPUT_POST : INPUT_GET,
    'seller_id',
    FILTER_VALIDATE_INT
);

if (!$sellerId || $sellerId < 1 || $sellerId === $userId) {
    header('Location: cart.php?error=invalid');
    exit;
}

// Ambil data pembeli untuk nilai awal formulir.
$stmt = $pdo->prepare(
    'SELECT name, major FROM users WHERE id = ? LIMIT 1'
);
$stmt->execute([$userId]);
$currentUser = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$currentUser) {
    header('Location: logout.php');
    exit;
}

$buyerName = trim((string) $currentUser['name']);
$buyerMajor = trim((string) ($currentUser['major'] ?? ''));
$pickupBuilding = 'Gedung A';
$error = '';
$items = [];
$total = 0;

/**
 * Ambil produk dari database dan batasi ke penjual terpilih.
 * Harga dan nama produk tidak diambil dari input form.
 */
function getSellerCheckoutItems(
    PDO $pdo,
    array $cart,
    int $sellerId
): array {
    $validCart = [];

    foreach ($cart as $id => $quantity) {
        $id = filter_var($id, FILTER_VALIDATE_INT);
        $quantity = filter_var($quantity, FILTER_VALIDATE_INT);

        if (
            $id !== false && $id !== null && $id > 0 &&
            $quantity !== false && $quantity !== null &&
            $quantity >= 1 && $quantity <= 99
        ) {
            $validCart[(int) $id] = (int) $quantity;
        }
    }

    if (!$validCart) {
        return [];
    }

    $placeholders = implode(
        ',',
        array_fill(0, count($validCart), '?')
    );

    $stmt = $pdo->prepare(
        "SELECT id, user_id, name, price, image_url
         FROM products
         WHERE id IN ($placeholders)
           AND user_id = ?"
    );

    $stmt->execute([
        ...array_keys($validCart),
        $sellerId
    ]);

    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $items = [];

    foreach ($products as $product) {
        $id = (int) $product['id'];
        $quantity = $validCart[$id];

        $product['id'] = $id;
        $product['quantity'] = $quantity;
        $product['price'] = (float) $product['price'];
        $product['subtotal'] = $product['price'] * $quantity;

        $items[] = $product;
    }

    return $items;
}

// Muat keranjang untuk penjual ini.
$items = getSellerCheckoutItems(
    $pdo,
    $_SESSION['cart'],
    (int) $sellerId
);

foreach ($items as $item) {
    $total += $item['subtotal'];
}

if (!$items) {
    header('Location: cart.php?error=invalid');
    exit;
}

// Proses pembuatan pesanan.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken();

    $buyerName = trim((string) ($_POST['buyer_name'] ?? ''));
    $buyerMajor = trim((string) ($_POST['buyer_major'] ?? ''));
    $pickupBuilding = (string) ($_POST['pickup_building'] ?? '');

    if (
        $buyerName === '' ||
        mb_strlen($buyerName) > 120
    ) {
        $error = 'Nama pembeli wajib diisi, maksimal 120 karakter.';
    } elseif (
        $buyerMajor === '' ||
        mb_strlen($buyerMajor) > 120
    ) {
        $error = 'Jurusan wajib diisi, maksimal 120 karakter.';
    } elseif (
        !in_array($pickupBuilding, ['Gedung A', 'Gedung B'], true)
    ) {
        $error = 'Silakan pilih Gedung A atau Gedung B.';
    } else {
        try {
            $pdo->beginTransaction();

            // Ambil ulang harga dan produk saat pesanan dibuat.
            $items = getSellerCheckoutItems(
                $pdo,
                $_SESSION['cart'],
                (int) $sellerId
            );

            if (!$items) {
                throw new RuntimeException(
                    'Produk tidak tersedia lagi. Periksa keranjangmu.'
                );
            }

            $total = 0;

            foreach ($items as $item) {
                if ($item['price'] <= 0) {
                    throw new RuntimeException(
                        'Ada harga produk yang tidak valid.'
                    );
                }

                $total += $item['subtotal'];
            }

            if ($total <= 0) {
                throw new RuntimeException(
                    'Total pesanan tidak valid.'
                );
            }

            // Buat kode pesanan unik.
            $orderCode = 'CM-' . strtoupper(bin2hex(random_bytes(5)));

            $stmt = $pdo->prepare(
                "INSERT INTO orders (
                    order_code,
                    seller_id,
                    buyer_name,
                    buyer_major,
                    pickup_building,
                    total_amount,
                    payment_status
                ) VALUES (?, ?, ?, ?, ?, ?, 'Menunggu Pembayaran')"
            );

            $stmt->execute([
                $orderCode,
                $sellerId,
                $buyerName,
                $buyerMajor,
                $pickupBuilding,
                $total
            ]);

            $orderId = (int) $pdo->lastInsertId();

            // Simpan detail barang pesanan.
            $itemStmt = $pdo->prepare(
                'INSERT INTO order_items (
                    order_id,
                    product_id,
                    product_name,
                    unit_price,
                    quantity,
                    subtotal
                ) VALUES (?, ?, ?, ?, ?, ?)'
            );

            foreach ($items as $item) {
                $itemStmt->execute([
                    $orderId,
                    $item['id'],
                    $item['name'],
                    $item['price'],
                    $item['quantity'],
                    $item['subtotal']
                ]);
            }

            $pdo->commit();

            // Hapus hanya produk milik penjual ini dari keranjang.
            foreach ($items as $item) {
                unset($_SESSION['cart'][$item['id']]);
            }

            // Hindari pesanan dibuat ulang saat halaman di-refresh.
            header(
                'Location: payment.php?order_id=' . $orderId
            );
            exit;

        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            error_log($exception->getMessage());

            if ($exception instanceof RuntimeException) {
                $error = $exception->getMessage();
            } else {
                $error = 'Pesanan gagal disimpan. Silakan coba lagi.';
            }

            // Muat ulang ringkasan setelah kegagalan.
            $items = getSellerCheckoutItems(
                $pdo,
                $_SESSION['cart'],
                (int) $sellerId
            );

            $total = 0;

            foreach ($items as $item) {
                $total += $item['subtotal'];
            }
        }
    }
}

$csrfToken = getCsrfToken();

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Checkout — CampusMarket</title>
    <link rel="stylesheet" href="assets/style.css">

    <style>
        .checkout-page {
            max-width: 1050px;
            margin: auto;
            padding: 30px 20px 60px;
            color: var(--ink, #242424);
        }

        .checkout-hero {
            padding: 28px;
            margin-bottom: 25px;
            background: var(--yellow, #ffe66d);
            border: 2px solid var(--ink, #242424);
            border-radius: 24px;
            box-shadow: 5px 5px 0 var(--ink, #242424);
        }

        .checkout-hero h1 {
            margin: 12px 0 8px;
            font-size: clamp(30px, 5vw, 44px);
            letter-spacing: -1.5px;
        }

        .checkout-hero p {
            margin: 0;
            line-height: 1.7;
        }

        .checkout-layout {
            display: grid;
            grid-template-columns: minmax(0, 1.1fr) minmax(280px, .9fr);
            gap: 24px;
            align-items: start;
        }

        .checkout-card {
            padding: 24px;
            background: #fff;
            border: 2px solid var(--ink, #242424);
            border-radius: 20px;
            box-shadow: 4px 4px 0 var(--ink, #242424);
        }

        .checkout-card h2 {
            margin: 0 0 18px;
            font-size: 21px;
        }

        .checkout-form label {
            display: block;
            margin: 16px 0 7px;
            font-size: 13px;
            font-weight: 900;
        }

        .checkout-form input,
        .checkout-form select {
            display: block;
            width: 100%;
            padding: 12px 13px;
            border: 1.5px solid #242424;
            border-radius: 11px;
            background: #fffdf9;
            color: #242424;
            font: inherit;
            box-sizing: border-box;
        }

        .checkout-form input:focus,
        .checkout-form select:focus {
            outline: 3px solid #ffcfad;
        }

        .checkout-submit {
            display: block;
            width: 100%;
            margin-top: 24px;
            padding: 14px;
            border: 2px solid #242424;
            border-radius: 13px;
            background: var(--orange, #ff6b35);
            color: #fff;
            box-shadow: 0 4px 0 #242424;
            font-weight: 900;
            cursor: pointer;
        }

        .checkout-submit:hover {
            transform: translateY(-2px);
        }

        .checkout-item {
            display: flex;
            justify-content: space-between;
            gap: 14px;
            padding: 14px 0;
            border-bottom: 1px solid #eee4d8;
            font-size: 13px;
        }

        .checkout-item:last-of-type {
            border-bottom: 0;
        }

        .checkout-item-name {
            font-weight: 900;
            overflow-wrap: anywhere;
        }

        .checkout-muted {
            margin-top: 5px;
            color: #6c665c;
            font-size: 12px;
        }

        .checkout-total {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            margin-top: 18px;
            padding-top: 18px;
            border-top: 2px dashed #ddd0c0;
            font-size: 18px;
            font-weight: 1000;
        }

        .checkout-error {
            margin-bottom: 20px;
            padding: 13px 15px;
            border: 1.5px solid #9f2929;
            border-radius: 12px;
            background: #ffe0df;
            color: #7b1f1f;
            line-height: 1.6;
        }

        .checkout-back {
            display: inline-block;
            margin-top: 18px;
            font-size: 13px;
            font-weight: 900;
            color: #c94c20;
        }

        .checkout-tip {
            margin-top: 17px;
            padding: 13px;
            background: #e7f6dc;
            border-radius: 12px;
            font-size: 12px;
            line-height: 1.7;
        }

        @media (max-width: 760px) {
            .checkout-layout {
                grid-template-columns: 1fr;
            }

            .checkout-page {
                padding: 20px 14px 45px;
            }

            .checkout-card {
                padding: 19px;
            }
        }
    </style>
</head>
<body>

<header class="navbar">
    <div class="container nav-container">
        <a href="index.php" class="logo">CampusMarket</a>

        <nav class="nav-links">
            <a href="index.php">Beranda</a>
            <a href="cart.php">Keranjang</a>
            <a href="my_products.php">Jualan Saya</a>
            <a href="profile.php">Profil Saya</a>
            <a href="logout.php">Logout</a>
        </nav>
    </div>
</header>

<main class="checkout-page">

    <section class="checkout-hero">
        <span class="eyebrow">🧾 LANGKAH TERAKHIR</span>
        <h1>Checkout pesananmu!</h1>
        <p>Periksa detail barang dan isi lokasi pengambilan sebelum membuat pesanan.</p>
    </section>

    <?php if ($error !== ''): ?>
        <div class="checkout-error">
            <?= checkoutPageEscape($error) ?>
        </div>
    <?php endif; ?>

    <?php if (!$items): ?>
        <section class="checkout-card">
            <h2>Keranjang tidak tersedia</h2>
            <p>Produk untuk checkout ini tidak ditemukan. Silakan periksa keranjangmu.</p>
            <a class="checkout-back" href="cart.php">← Kembali ke keranjang</a>
        </section>
    <?php else: ?>

        <div class="checkout-layout">

            <section class="checkout-card">
                <h2>👤 Data pembeli</h2>

                <form
                    class="checkout-form"
                    method="post"
                    action="checkout.php?seller_id=<?= (int) $sellerId ?>"
                >
                    <input
                        type="hidden"
                        name="csrf_token"
                        value="<?= checkoutPageEscape($csrfToken) ?>"
                    >

                    <input
                        type="hidden"
                        name="seller_id"
                        value="<?= (int) $sellerId ?>"
                    >

                    <label for="buyer_name">Nama pembeli</label>
                    <input
                        id="buyer_name"
                        name="buyer_name"
                        maxlength="120"
                        value="<?= checkoutPageEscape($buyerName) ?>"
                        autocomplete="name"
                        required
                    >

                    <label for="buyer_major">Jurusan</label>
                    <input
                        id="buyer_major"
                        name="buyer_major"
                        maxlength="120"
                        value="<?= checkoutPageEscape($buyerMajor) ?>"
                        required
                    >

                    <label for="pickup_building">Lokasi pengambilan</label>
                    <select id="pickup_building" name="pickup_building" required>
                        <option value="Gedung A"
                            <?= $pickupBuilding === 'Gedung A' ? 'selected' : '' ?>>
                            Gedung A
                        </option>
                        <option value="Gedung B"
                            <?= $pickupBuilding === 'Gedung B' ? 'selected' : '' ?>>
                            Gedung B
                        </option>
                    </select>

                    <div class="checkout-tip">
                        📍 Pastikan nama dan jurusan benar agar penjual mudah
                        mengenali pesananmu. Pembayaran akan dilanjutkan setelah
                        pesanan berhasil dibuat.
                    </div>

                    <button class="checkout-submit" type="submit">
                        Buat Pesanan →
                    </button>
                </form>

                <a class="checkout-back" href="cart.php">
                    ← Kembali ke keranjang
                </a>
            </section>

            <aside class="checkout-card">
                <h2>🛍️ Ringkasan pesanan</h2>

                <?php foreach ($items as $item): ?>
                    <div class="checkout-item">
                        <div>
                            <div class="checkout-item-name">
                                <?= checkoutPageEscape($item['name']) ?>
                            </div>
                            <div class="checkout-muted">
                                <?= (int) $item['quantity'] ?> ×
                                <?= checkoutPageRupiah($item['price']) ?>
                            </div>
                        </div>

                        <strong>
                            <?= checkoutPageRupiah($item['subtotal']) ?>
                        </strong>
                    </div>
                <?php endforeach; ?>

                <div class="checkout-total">
                    <span>Total pembayaran</span>
                    <span><?= checkoutPageRupiah($total) ?></span>
                </div>

                <p class="checkout-muted">
                    Harga dihitung ulang dari database. Pesanan ini hanya
                    mencakup produk milik satu penjual.
                </p>
            </aside>

        </div>

    <?php endif; ?>

</main>

</body>
</html>
