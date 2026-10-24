
<?php

require_once __DIR__ . '/product_helpers.php';

requireLogin();

$userId = (int) $_SESSION['user_id'];

if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

function cartRupiah($amount): string
{
    return 'Rp' . number_format((float) $amount, 0, ',', '.');
}

// Proses ubah jumlah atau hapus produk.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken();

    $action = $_POST['action'] ?? '';
    $productId = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);

    if (!$productId || $productId < 1 ||
        !isset($_SESSION['cart'][$productId])) {
        header('Location: cart.php?error=invalid');
        exit;
    }

    if ($action === 'remove') {
        unset($_SESSION['cart'][$productId]);
        header('Location: cart.php?removed=1');
        exit;
    }

    if ($action === 'update') {
        $quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT);

        if ($quantity === false || $quantity === null ||
            $quantity < 1 || $quantity > 99) {
            header('Location: cart.php?error=quantity');
            exit;
        }

        $_SESSION['cart'][$productId] = $quantity;

        header('Location: cart.php?updated=1');
        exit;
    }

    header('Location: cart.php?error=invalid');
    exit;
}

// Ambil produk dari database, bukan mempercayai harga dari session.
$cart = $_SESSION['cart'];
$cartProducts = [];

if (!empty($cart)) {
    $productIds = [];

    foreach ($cart as $id => $quantity) {
        $id = (int) $id;
        $quantity = (int) $quantity;

        if ($id > 0 && $quantity > 0 && $quantity <= 99) {
            $productIds[$id] = $quantity;
        }
    }

    $_SESSION['cart'] = $productIds;
    $cart = $productIds;

    if (!empty($cart)) {
        $placeholders = implode(',', array_fill(0, count($cart), '?'));

        $stmt = $pdo->prepare(
            "SELECT
                p.id,
                p.user_id,
                p.name,
                p.description,
                p.price,
                p.image_url,
                c.name AS category_name,
                u.name AS seller_name
             FROM products p
             LEFT JOIN categories c ON c.id = p.category_id
             LEFT JOIN users u ON u.id = p.user_id
             WHERE p.id IN ($placeholders)"
        );

        $stmt->execute(array_keys($cart));
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rows as $row) {
            $id = (int) $row['id'];
            $row['quantity'] = $cart[$id];
            $row['subtotal'] = (float) $row['price'] * $row['quantity'];
            $row['seller_id'] = $row['user_id'] !== null
                ? (int) $row['user_id']
                : null;
            $row['can_buy'] = $row['seller_id'] !== null
                && $row['seller_id'] !== $userId;

            $cartProducts[] = $row;
        }
    }
}

// Kelompokkan produk berdasarkan penjual.
// Satu pesanan hanya untuk satu penjual.
$groups = [];

foreach ($cartProducts as $product) {
    $key = $product['seller_id'] === null
        ? 'unavailable'
        : (string) $product['seller_id'];

    if (!isset($groups[$key])) {
        $groups[$key] = [
            'seller_id' => $product['seller_id'],
            'seller_name' => $product['seller_name'] ?: 'Penjual',
            'items' => [],
            'total' => 0,
            'can_checkout' => $product['can_buy'],
        ];
    }

    $groups[$key]['items'][] = $product;
    $groups[$key]['total'] += $product['subtotal'];

    if (!$product['can_buy']) {
        $groups[$key]['can_checkout'] = false;
    }
}

$totalItems = 0;
$grandTotal = 0;

foreach ($cartProducts as $product) {
    $totalItems += $product['quantity'];
    $grandTotal += $product['subtotal'];
}

$csrfToken = getCsrfToken();

function cartH($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Keranjang Belanja — CampusMarket</title>
    <link rel="stylesheet" href="assets/style.css">

    <style>
        .cart-page {
            max-width: 1100px;
            margin: 0 auto;
            padding: 30px 20px 65px;
            color: var(--ink, #242424);
        }

        .cart-hero {
            padding: 30px;
            margin-bottom: 26px;
            background: var(--yellow, #ffe66d);
            border: 2px solid var(--ink, #242424);
            border-radius: 25px;
            box-shadow: 5px 5px 0 var(--ink, #242424);
        }

        .cart-hero h1 {
            margin: 8px 0;
            font-size: clamp(30px, 5vw, 45px);
            letter-spacing: -1.5px;
        }

        .cart-hero p {
            margin: 0;
            line-height: 1.7;
        }

        .cart-layout {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 310px;
            gap: 24px;
            align-items: start;
        }

        .cart-group,
        .cart-summary,
        .cart-empty {
            background: #fff;
            border: 2px solid var(--ink, #242424);
            border-radius: 20px;
            box-shadow: 4px 4px 0 var(--ink, #242424);
        }

        .cart-group {
            padding: 20px;
            margin-bottom: 22px;
        }

        .seller-heading {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 18px;
        }

        .seller-icon {
            width: 43px;
            height: 43px;
            display: grid;
            place-items: center;
            flex-shrink: 0;
            background: #e7f6dc;
            border: 1.5px solid var(--ink, #242424);
            border-radius: 13px;
            font-size: 22px;
        }

        .seller-heading h2 {
            margin: 0 0 4px;
            font-size: 18px;
        }

        .seller-heading p {
            margin: 0;
            font-size: 12px;
            color: #6c665c;
        }

        .cart-item {
            display: grid;
            grid-template-columns: 90px minmax(0, 1fr);
            gap: 15px;
            padding: 18px 0;
            border-top: 1px solid #eee4d8;
        }

        .cart-item:first-of-type {
            border-top: 0;
        }

        .cart-photo {
            width: 90px;
            height: 90px;
            display: grid;
            place-items: center;
            overflow: hidden;
            border: 1.5px solid var(--ink, #242424);
            border-radius: 15px;
            background: #fff0df;
            font-size: 38px;
        }

        .cart-photo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .cart-item h3 {
            margin: 0 0 6px;
            font-size: 16px;
            overflow-wrap: anywhere;
        }

        .cart-category {
            display: inline-block;
            margin-bottom: 7px;
            padding: 4px 8px;
            background: #e7f6dc;
            border-radius: 7px;
            font-size: 10px;
            font-weight: 800;
        }

        .cart-price {
            margin: 0 0 12px;
            font-weight: 900;
        }

        .cart-controls {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            flex-wrap: wrap;
        }

        .quantity-form {
            display: flex;
            align-items: center;
            gap: 7px;
        }

        .quantity-input {
            width: 66px;
            padding: 8px;
            border: 1.5px solid #242424;
            border-radius: 9px;
            text-align: center;
            background: #fff;
            color: #242424;
        }

        .small-button {
            padding: 8px 11px;
            border: 1.5px solid #242424;
            border-radius: 9px;
            background: #fff;
            color: #242424;
            font-size: 12px;
            font-weight: 800;
            cursor: pointer;
        }

        .small-button:hover {
            background: #ffe66d;
        }

        .remove-button {
            border: 0;
            background: transparent;
            color: #c43838;
            font-size: 12px;
            font-weight: 800;
            cursor: pointer;
        }

        .item-subtotal {
            font-size: 13px;
            font-weight: 900;
        }

        .cart-summary {
            position: sticky;
            top: 20px;
            padding: 22px;
        }

        .cart-summary h2 {
            margin: 0 0 20px;
            font-size: 21px;
        }

        .summary-line {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            margin: 13px 0;
            font-size: 14px;
        }

        .summary-total {
            margin-top: 18px;
            padding-top: 18px;
            border-top: 2px dashed #ddd0c0;
            font-size: 17px;
            font-weight: 1000;
        }

        .checkout-button {
            display: flex;
            width: 100%;
            margin-top: 18px;
            padding: 14px;
            justify-content: center;
            border: 2px solid #242424;
            border-radius: 13px;
            background: var(--orange, #ff6b35);
            color: #fff;
            box-shadow: 0 4px 0 #242424;
            text-align: center;
            text-decoration: none;
            font-weight: 900;
        }

        .checkout-button:hover {
            transform: translateY(-2px);
        }

        .cart-note {
            margin-top: 15px;
            font-size: 12px;
            color: #6c665c;
            line-height: 1.6;
        }

        .cart-empty {
            padding: 45px 20px;
            text-align: center;
        }

        .cart-empty .empty-emoji {
            font-size: 65px;
        }

        .cart-empty h2 {
            margin: 12px 0 8px;
        }

        .cart-empty p {
            color: #6c665c;
            line-height: 1.7;
        }

        .cart-notice {
            padding: 13px 16px;
            margin-bottom: 20px;
            border: 1.5px solid #242424;
            border-radius: 12px;
            background: #e7f6dc;
            line-height: 1.5;
        }

        .cart-notice.error {
            background: #ffe0df;
        }

        .seller-checkout {
            padding-top: 16px;
            border-top: 2px dashed #ddd0c0;
        }

        .seller-total {
            display: flex;
            justify-content: space-between;
            gap: 10px;
            font-weight: 900;
            margin-bottom: 15px;
        }

        .seller-checkout .checkout-button {
            margin-top: 0;
        }

        .checkout-disabled {
            padding: 12px;
            border-radius: 10px;
            background: #fff0df;
            font-size: 12px;
            line-height: 1.6;
        }

        @media (max-width: 800px) {
            .cart-layout {
                grid-template-columns: 1fr;
            }

            .cart-summary {
                position: static;
            }
        }

        @media (max-width: 480px) {
            .cart-page {
                padding: 20px 14px 45px;
            }

            .cart-hero {
                padding: 22px;
            }

            .cart-group {
                padding: 15px;
            }

            .cart-item {
                grid-template-columns: 70px minmax(0, 1fr);
                gap: 11px;
            }

            .cart-photo {
                width: 70px;
                height: 70px;
                font-size: 30px;
            }

            .cart-controls {
                align-items: flex-start;
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
            <a href="index.php#produk">Jelajahi</a>
            <a href="my_products.php">Jualan Saya</a>
            <a href="profile.php">Profil Saya</a>
            <a href="logout.php">Logout</a>
        </nav>
    </div>
</header>

<main class="cart-page">

    <section class="cart-hero">
        <span class="eyebrow">🛍️ KERANJANG BELANJA</span>
        <h1>Jajan dulu, yuk! 😋</h1>
        <p>Periksa pesananmu sebelum lanjut ke checkout.</p>
    </section>

    <?php if (isset($_GET['added'])): ?>
        <div class="cart-notice">🎉 Produk berhasil ditambahkan ke keranjang!</div>
    <?php elseif (isset($_GET['updated'])): ?>
        <div class="cart-notice">✅ Jumlah produk berhasil diperbarui.</div>
    <?php elseif (isset($_GET['removed'])): ?>
        <div class="cart-notice">🗑️ Produk berhasil dihapus dari keranjang.</div>
    <?php elseif (isset($_GET['error'])): ?>
        <div class="cart-notice error">
            <?php
            $errorMessages = [
                'invalid' => 'Permintaan tidak valid. Silakan coba lagi.',
                'quantity' => 'Jumlah harus antara 1 sampai 99.',
                'maxquantity' => 'Jumlah maksimum produk adalah 99.'
            ];
            echo cartH(
                $errorMessages[$_GET['error']] ??
                'Terjadi kesalahan. Silakan coba lagi.'
            );
            ?>
        </div>
    <?php endif; ?>

    <?php if (empty($cartProducts)): ?>

        <section class="cart-empty">
            <div class="empty-emoji">🛒</div>
            <h2>Keranjangmu masih kosong!</h2>
            <p>
                Belum ada jajanan di sini. Yuk, cari makanan favoritmu
                dan tambahkan ke keranjang.
            </p>
            <a href="index.php#produk" class="button">
                🍡 Jelajahi Produk
            </a>
        </section>

    <?php else: ?>

        <div class="cart-layout">

            <section>
                <?php foreach ($groups as $group): ?>
                    <article class="cart-group">

                        <div class="seller-heading">
                            <div class="seller-icon">🏪</div>
                            <div>
                                <h2>
                                    <?= cartH($group['seller_name']) ?>
                                </h2>
                                <p>
                                    <?= count($group['items']) ?>
                                    jenis produk dalam keranjang
                                </p>
                            </div>
                        </div>

                        <?php foreach ($group['items'] as $product): ?>
                            <div class="cart-item">

                                <div class="cart-photo">
                                    <?php
                                    $imagePath = $product['image_url'] ?? '';
                                    $safeImagePath = $imagePath !== ''
                                        && !preg_match('~^(https?:)?//~i', $imagePath)
                                        && !str_contains($imagePath, '..');
                                    ?>

                                    <?php if ($safeImagePath && is_file(__DIR__ . '/' . $imagePath)): ?>
                                        <img
                                            src="<?= cartH($imagePath) ?>"
                                            alt="<?= cartH($product['name']) ?>"
                                        >
                                    <?php else: ?>
                                        🍱
                                    <?php endif; ?>
                                </div>

                                <div>
                                    <h3><?= cartH($product['name']) ?></h3>

                                    <?php if (!empty($product['category_name'])): ?>
                                        <span class="cart-category">
                                            <?= cartH($product['category_name']) ?>
                                        </span>
                                    <?php endif; ?>

                                    <p class="cart-price">
                                        <?= cartRupiah($product['price']) ?>
                                    </p>

                                    <div class="cart-controls">
                                        <form
                                            method="post"
                                            action="cart.php"
                                            class="quantity-form"
                                        >
                                            <input type="hidden" name="csrf_token"
                                                value="<?= cartH($csrfToken) ?>">
                                            <input type="hidden" name="product_id"
                                                value="<?= (int) $product['id'] ?>">
                                            <input type="hidden" name="action"
                                                value="update">

                                            <label class="muted" for="qty-<?= (int) $product['id'] ?>">
                                                Qty
                                            </label>

                                            <input
                                                class="quantity-input"
                                                type="number"
                                                id="qty-<?= (int) $product['id'] ?>"
                                                name="quantity"
                                                min="1"
                                                max="99"
                                                value="<?= (int) $product['quantity'] ?>"
                                                required
                                            >

                                            <button class="small-button" type="submit">
                                                Ubah
                                            </button>
                                        </form>

                                        <form method="post" action="cart.php"
                                            onsubmit="return confirm('Hapus produk ini dari keranjang?')">
                                            <input type="hidden" name="csrf_token"
                                                value="<?= cartH($csrfToken) ?>">
                                            <input type="hidden" name="product_id"
                                                value="<?= (int) $product['id'] ?>">
                                            <input type="hidden" name="action"
                                                value="remove">

                                            <button class="remove-button" type="submit">
                                                Hapus
                                            </button>
                                        </form>
                                    </div>

                                    <p class="item-subtotal">
                                        Subtotal: <?= cartRupiah($product['subtotal']) ?>
                                    </p>

                                    <?php if (!$product['can_buy']): ?>
                                        <div class="checkout-disabled">
                                            <?php if ($product['seller_id'] === null): ?>
                                                Produk ini belum memiliki penjual.
                                            <?php else: ?>
                                                Kamu tidak bisa membeli produk milikmu sendiri.
                                            <?php endif; ?>
                                            Hapus produk ini dari keranjang untuk melanjutkan.
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>

                        <div class="seller-checkout">
                            <div class="seller-total">
                                <span>Total dari penjual ini</span>
                                <span><?= cartRupiah($group['total']) ?></span>
                            </div>

                            <?php if ($group['can_checkout']): ?>
                                <a
                                    class="checkout-button"
                                    href="checkout.php?seller_id=<?= (int) $group['seller_id'] ?>"
                                >
                                    Checkout Pesanan Ini →
                                </a>
                            <?php else: ?>
                                <div class="checkout-disabled">
                                    Checkout untuk kelompok ini belum tersedia.
                                    Pastikan semua produk memiliki penjual yang valid.
                                </div>
                            <?php endif; ?>
                        </div>

                    </article>
                <?php endforeach; ?>
            </section>

            <aside class="cart-summary">
                <h2>🧾 Ringkasan</h2>

                <div class="summary-line">
                    <span>Total jenis produk</span>
                    <strong><?= count($cartProducts) ?></strong>
                </div>

                <div class="summary-line">
                    <span>Total barang</span>
                    <strong><?= $totalItems ?> item</strong>
                </div>

                <div class="summary-line summary-total">
                    <span>Total semua</span>
                    <span><?= cartRupiah($grandTotal) ?></span>
                </div>

                <p class="cart-note">
                    Total ini mencakup semua produk di keranjang.
                    Jika produk berasal dari penjual berbeda, checkout
                    dilakukan secara terpisah untuk setiap penjual.
                </p>

                <a href="index.php#produk" class="button"
                    style="display:block;text-align:center;margin-top:18px">
                    ← Lanjut Belanja
                </a>
            </aside>

        </div>

    <?php endif; ?>

</main>

</body>
</html>
