
<?php
require_once __DIR__ . '/product_helpers.php';
requireLogin();

$stmt = $pdo->prepare(
    'SELECT id, name, price, description, image_url, created_at
     FROM products
     WHERE user_id = ?
     ORDER BY id DESC'
);
$stmt->execute([(int) $_SESSION['user_id']]);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

$messages = [
    'added' => 'Produk berhasil ditambahkan!',
    'updated' => 'Produk berhasil diperbarui!',
    'deleted' => 'Produk berhasil dihapus!',
    'invalid' => 'ID produk tidak valid.',
    'notfound' => 'Produk tidak ditemukan atau bukan milik akun kamu.'
];

$message = '';
$messageType = 'success';

foreach ($messages as $key => $value) {
    if (isset($_GET[$key]) && $_GET[$key] === '1') {
        $message = $value;

        if (in_array($key, ['invalid', 'notfound'], true)) {
            $messageType = 'error';
        }

        break;
    }
}

function formatRupiah($value): string
{
    return 'Rp' . number_format((float) $value, 0, ',', '.');
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Jualan Saya - CampusMarket</title>
    <link rel="stylesheet" href="assets/style.css">

    <style>
        .seller-page {
            width: min(1100px, calc(100% - 32px));
            margin: 32px auto 64px;
        }

        .seller-topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            flex-wrap: wrap;
            margin-bottom: 32px;
        }

        .seller-topbar nav {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 16px;
            font-weight: 700;
        }

        .seller-topbar nav a:hover {
            color: var(--orange);
        }

        .seller-hero {
            background: var(--yellow);
            border: 2px solid var(--ink);
            border-radius: 24px;
            padding: clamp(22px, 4vw, 38px);
            box-shadow: 5px 5px 0 var(--ink);
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
            margin-bottom: 32px;
        }

        .seller-hero h1 {
            font-size: clamp(30px, 5vw, 44px);
            line-height: 1.1;
            margin: 12px 0;
        }

        .seller-hero p {
            margin: 0;
            line-height: 1.7;
        }

        .seller-hero-art {
            font-size: clamp(54px, 8vw, 88px);
            flex-shrink: 0;
            transform: rotate(8deg);
        }

        .seller-section-heading {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 14px;
            margin: 34px 0 20px;
        }

        .seller-section-heading h2 {
            margin: 0 0 6px;
        }

        .seller-section-heading p {
            margin: 0;
        }

        .seller-product-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 22px;
        }

        .seller-product-card {
            min-width: 0;
            background: var(--white, #fff);
            border: 2px solid var(--ink);
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 4px 4px 0 var(--ink);
            display: flex;
            flex-direction: column;
            transition: transform .18s, box-shadow .18s;
        }

        .seller-product-card:hover {
            transform: translateY(-3px);
            box-shadow: 6px 7px 0 var(--ink);
        }

        .seller-product-image,
        .seller-product-placeholder {
            width: 100%;
            height: 190px;
            object-fit: cover;
            display: grid;
            place-items: center;
            background: var(--cream, #fff9f0);
        }

        .seller-product-placeholder {
            background: var(--pink, #ffb8c8);
            font-size: 54px;
        }

        .seller-product-body {
            padding: 18px;
            display: flex;
            flex: 1;
            flex-direction: column;
        }

        .seller-product-body h3 {
            margin: 12px 0 8px;
            overflow-wrap: anywhere;
        }

        .seller-product-description {
            font-size: 14px;
            line-height: 1.6;
            overflow-wrap: anywhere;
            white-space: normal;
        }

        .seller-product-price {
            color: var(--orange);
            font-size: 20px;
            font-weight: 900;
            margin: 8px 0 16px;
        }

        .seller-product-date {
            font-size: 12px;
            color: #777;
            margin-top: auto;
            padding-top: 8px;
        }

        .seller-product-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 18px;
        }

        .seller-product-actions .button,
        .seller-product-actions form {
            flex: 1;
        }

        .seller-product-actions form {
            display: flex;
        }

        .seller-product-actions button {
            width: 100%;
        }

        .seller-delete-button {
            background: #ffe8e5;
            color: #a52d24;
            border: 2px solid var(--ink);
            box-shadow: 3px 3px 0 var(--ink);
        }

        .seller-delete-button:hover {
            background: #ffc8c1;
        }

        .seller-notice {
            padding: 14px 18px;
            border: 2px solid var(--ink);
            border-radius: 14px;
            margin: 20px 0;
            font-weight: 700;
        }

        .seller-notice.success {
            background: #dff4d3;
        }

        .seller-notice.error {
            background: #ffe1dc;
            color: #8d2019;
        }

        .seller-empty {
            padding: 40px 20px;
            text-align: center;
            background: #fff;
            border: 2px dashed var(--ink);
            border-radius: 22px;
        }

        .seller-empty-icon {
            font-size: 58px;
            margin-bottom: 12px;
        }

        .seller-empty h2 {
            margin: 0 0 10px;
        }

        .seller-empty p {
            line-height: 1.7;
            margin-bottom: 22px;
        }

        .seller-footer {
            text-align: center;
            color: #777;
            font-size: 13px;
            margin-top: 44px;
        }

        @media (max-width: 850px) {
            .seller-product-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 560px) {
            .seller-page {
                width: calc(100% - 28px);
                margin-top: 18px;
            }

            .seller-topbar {
                margin-bottom: 22px;
            }

            .seller-hero {
                align-items: flex-start;
                padding: 22px;
            }

            .seller-hero-art {
                font-size: 48px;
            }

            .seller-product-grid {
                grid-template-columns: 1fr;
                gap: 18px;
            }

            .seller-product-image,
            .seller-product-placeholder {
                height: 210px;
            }

            .seller-product-actions .button,
            .seller-product-actions button {
                padding: 10px;
                font-size: 13px;
            }
        }
    </style>
</head>
<body>
<main class="seller-page">

    <div class="seller-topbar">
        <a href="index.php" class="logo">
            Campus<span>Market</span>
        </a>

        <nav aria-label="Navigasi akun">
            <a href="index.php">← Beranda</a>
            <a href="profile.php">Profil Saya</a>
            <a href="logout.php">Logout</a>
        </nav>
    </div>

    <section class="seller-hero">
        <div>
            <span class="eyebrow">✦ AREA PENJUAL</span>
            <h1>Lapak kamu,<br>aturan kamu! 🛍️</h1>
            <p>Kelola produk jualanmu dan bikin teman kampus makin ngiler.</p>
        </div>

        <div class="seller-hero-art" aria-hidden="true">🧋</div>
    </section>

    <?php if ($message !== ''): ?>
        <div class="seller-notice <?= h($messageType) ?>" role="status">
            <?= h($message) ?>
        </div>
    <?php endif; ?>

    <section>
        <div class="seller-section-heading">
            <div>
                <h2>Produk jualanmu ✨</h2>
                <p class="muted">
                    <?= count($products) ?> produk terdaftar di lapakmu.
                </p>
            </div>

            <a class="button" href="add_product.php">
                + Tambah Produk
            </a>
        </div>

        <?php if (count($products) === 0): ?>
            <div class="seller-empty">
                <div class="seller-empty-icon" aria-hidden="true">🍜</div>
                <h2>Lapakmu masih kosong, cuy!</h2>
                <p>
                    Saatnya pamerin makanan atau produk andalanmu.
                    Tambahkan produk pertama dan mulai jualan di kampus.
                </p>
                <a class="button" href="add_product.php">
                    🚀 Tambah Produk Pertama
                </a>
            </div>
        <?php else: ?>
            <div class="seller-product-grid">
                <?php foreach ($products as $product): ?>
                    <article class="seller-product-card">
                        <?php if (!empty($product['image_url'])): ?>
                            <img
                                class="seller-product-image"
                                src="<?= h($product['image_url']) ?>"
                                alt="<?= h($product['name']) ?>"
                                loading="lazy"
                            >
                        <?php else: ?>
                            <div
                                class="seller-product-placeholder"
                                aria-label="Belum ada foto produk"
                            >🍱</div>
                        <?php endif; ?>

                        <div class="seller-product-body">
                            <span class="category">Produk lapakmu</span>

                            <h3><?= h($product['name']) ?></h3>

                            <p class="seller-product-price">
                                <?= h(formatRupiah($product['price'])) ?>
                            </p>

                            <p class="seller-product-description">
                                <?php
                                $description = trim(
                                    (string) ($product['description'] ?? '')
                                );
                                echo $description !== ''
                                    ? nl2br(h($description))
                                    : 'Belum ada deskripsi produk.';
                                ?>
                            </p>

                            <?php if (!empty($product['created_at'])): ?>
                                <p class="seller-product-date">
                                    Ditambahkan:
                                    <?= h(date(
                                        'd/m/Y',
                                        strtotime($product['created_at'])
                                    )) ?>
                                </p>
                            <?php endif; ?>

                            <div class="seller-product-actions">
                                <a
                                    class="button button-secondary"
                                    href="edit_product.php?id=<?= (int) $product['id'] ?>"
                                >
                                    ✏️ Edit
                                </a>

                                <form
                                    method="POST"
                                    action="delete_product.php"
                                    onsubmit="return confirm('Yakin mau menghapus produk ini?')"
                                >
                                    <input
                                        type="hidden"
                                        name="csrf_token"
                                        value="<?= h(getCsrfToken()) ?>"
                                    >
                                    <input
                                        type="hidden"
                                        name="id"
                                        value="<?= (int) $product['id'] ?>"
                                    >
                                    <button
                                        class="button seller-delete-button"
                                        type="submit"
                                    >
                                        🗑️ Hapus
                                    </button>
                                </form>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <footer class="seller-footer">
        <p>CampusMarket · Jajan dekat, teman makin erat 💛</p>
        <a href="index.php">Kembali ke katalog</a>
    </footer>

</main>
</body>
</html>
