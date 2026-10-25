
<?php
require_once __DIR__ . '/product_helpers.php';
requireLogin();

$userId = (int) $_SESSION['user_id'];

function dashboardRupiah($amount): string
{
    return 'Rp' . number_format((float) $amount, 0, ',', '.');
}

function dashboardStatusClass(string $status): string
{
    switch ($status) {
        case 'Dibayar':
            return 'paid';
        case 'Ditolak':
            return 'rejected';
        case 'Menunggu Verifikasi':
            return 'review';
        default:
            return 'pending';
    }
}

// Ambil nama pengguna
$stmt = $pdo->prepare('SELECT name FROM users WHERE id = ?');
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    http_response_code(404);
    exit('Akun tidak ditemukan.');
}

// Total produk milik pengguna
$stmt = $pdo->prepare(
    'SELECT COUNT(*) FROM products WHERE user_id = ?'
);
$stmt->execute([$userId]);
$totalProducts = (int) $stmt->fetchColumn();

// Total seluruh pesanan masuk
$stmt = $pdo->prepare(
    'SELECT COUNT(*) FROM orders WHERE seller_id = ?'
);
$stmt->execute([$userId]);
$totalOrders = (int) $stmt->fetchColumn();

// Total omzet dari pesanan yang sudah dikonfirmasi dibayar
$stmt = $pdo->prepare(
    "SELECT COALESCE(SUM(total_amount), 0)
     FROM orders
     WHERE seller_id = ? AND payment_status = 'Dibayar'"
);
$stmt->execute([$userId]);
$totalRevenue = (float) $stmt->fetchColumn();

// Pesanan yang masih menunggu tindakan
$stmt = $pdo->prepare(
    "SELECT COUNT(*)
     FROM orders
     WHERE seller_id = ?
       AND payment_status IN (
           'Menunggu Pembayaran',
           'Menunggu Verifikasi'
       )"
);
$stmt->execute([$userId]);
$pendingOrders = (int) $stmt->fetchColumn();

// Produk terlaris milik pengguna
$stmt = $pdo->prepare(
    "SELECT
        p.id,
        p.name,
        p.image_url,
        COALESCE(SUM(oi.quantity), 0) AS total_sold,
        COALESCE(SUM(
            CASE WHEN o.payment_status = 'Dibayar'
                 THEN oi.quantity ELSE 0 END
        ), 0) AS paid_sold
     FROM products p
     LEFT JOIN order_items oi ON oi.product_id = p.id
     LEFT JOIN orders o ON o.id = oi.order_id
     WHERE p.user_id = ?
     GROUP BY p.id, p.name, p.image_url
     ORDER BY paid_sold DESC, total_sold DESC, p.id DESC
     LIMIT 5"
);
$stmt->execute([$userId]);
$bestProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Pesanan terbaru
$stmt = $pdo->prepare(
    "SELECT
        id,
        order_code,
        buyer_name,
        total_amount,
        payment_status,
        pickup_building,
        created_at
     FROM orders
     WHERE seller_id = ?
     ORDER BY created_at DESC, id DESC
     LIMIT 5"
);
$stmt->execute([$userId]);
$recentOrders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Penjual - CampusMarket</title>

<style>
:root {
    --orange: #f47b36;
    --yellow: #eaf3ff;
    --cream: #f7f9fc;
    --ink: #243047;
    --muted: #7b8799;
    --line: #e3eaf3;
    --green: #e5f7ed;
}

* { box-sizing: border-box; }

body {
    margin: 0;
    background: var(--cream);
    color: var(--ink);
    font-family: "Trebuchet MS", Arial, sans-serif;
}

a {
    color: inherit;
    text-decoration: none;
}

.wrap {
    width: min(1120px, calc(100% - 32px));
    margin: auto;
}

.navbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    padding: 20px 0;
    flex-wrap: wrap;
}

.brand {
    font-size: 22px;
    font-weight: 900;
    letter-spacing: -.7px;
    color: #244d83;
}

.brand span { color: var(--orange); }

.navlinks {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 10px 14px;
    border: 1px solid #dce5f0;
    border-radius: 12px;
    font-weight: 800;
    font-size: 13px;
    background: white;
    color: #34445e;
    cursor: pointer;
    transition: .2s;
}

.btn:hover {
    background: #edf4ff;
    transform: translateY(-1px);
}

.btn-orange {
    background: var(--orange);
    color: white;
    border-color: var(--orange);
    box-shadow: 0 4px 10px rgba(244, 123, 54, .18);
}

.btn-orange:hover {
    background: #e96b28;
    color: white;
}

.btn-yellow {
    background: #eaf3ff;
    color: #285a9c;
    border-color: #d5e5fa;
}

.hero {
    background: linear-gradient(120deg, #eaf3ff, #f5f8ff);
    border: 1px solid #d9e7f8;
    border-radius: 24px;
    padding: 30px;
    margin: 18px 0 28px;
}

.eyebrow {
    font-size: 11px;
    font-weight: 900;
    letter-spacing: 1.3px;
    text-transform: uppercase;
    color: #4778b6;
}

.hero h1 {
    font-size: clamp(28px, 5vw, 42px);
    letter-spacing: -1.5px;
    margin: 10px 0;
    color: #204779;
}

.hero p {
    margin: 0;
    line-height: 1.7;
    color: #63738b;
}

.section-title {
    font-size: 23px;
    letter-spacing: -.6px;
    margin: 32px 0 16px;
}

.stats {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 16px;
}

.stat {
    background: white;
    border: 1px solid var(--line);
    border-radius: 18px;
    padding: 19px;
    box-shadow: 0 5px 20px rgba(36, 65, 105, .05);
    min-width: 0;
    transition: transform .2s, box-shadow .2s;
}

.stat:hover {
    transform: translateY(-3px);
    box-shadow: 0 9px 24px rgba(36, 65, 105, .09);
}

.stat-icon {
    width: 45px;
    height: 45px;
    display: grid;
    place-items: center;
    background: #edf4ff;
    border-radius: 13px;
    font-size: 24px;
}

.stat-label {
    color: var(--muted);
    font-size: 12px;
    font-weight: 800;
    margin: 12px 0 7px;
}

.stat-value {
    font-size: clamp(20px, 2.4vw, 29px);
    font-weight: 1000;
    overflow-wrap: anywhere;
    color: #243e63;
}

.stat-note {
    color: var(--muted);
    font-size: 11px;
    margin-top: 8px;
    line-height: 1.5;
}

.layout {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    align-items: start;
    margin-top: 30px;
}

.panel {
    background: white;
    border: 1px solid var(--line);
    border-radius: 20px;
    padding: 20px;
    box-shadow: 0 5px 20px rgba(36, 65, 105, .05);
    min-width: 0;
}

.panel h2 {
    margin: 0 0 5px;
    font-size: 21px;
    color: #263c5b;
}

.panel-desc {
    color: var(--muted);
    font-size: 13px;
    line-height: 1.5;
    margin: 0 0 18px;
}

.product-row,
.order-row {
    display: flex;
    gap: 12px;
    align-items: center;
    padding: 14px 0;
    border-top: 1px solid var(--line);
    min-width: 0;
}

.product-row:first-of-type,
.order-row:first-of-type {
    border-top: 0;
}

.product-image {
    width: 60px;
    height: 60px;
    flex-shrink: 0;
    border-radius: 13px;
    object-fit: cover;
    background: #edf4ff;
    border: 1px solid var(--line);
}

.product-placeholder {
    display: grid;
    place-items: center;
    font-size: 26px;
}

.row-main {
    min-width: 0;
    flex: 1;
}

.row-title {
    font-size: 14px;
    font-weight: 900;
    overflow-wrap: anywhere;
    margin-bottom: 6px;
    color: #263c5b;
}

.row-meta {
    color: var(--muted);
    font-size: 12px;
    line-height: 1.7;
    overflow-wrap: anywhere;
}

.status {
    display: inline-block;
    font-size: 10px;
    font-weight: 900;
    padding: 6px 9px;
    border-radius: 8px;
    margin-top: 7px;
    line-height: 1.4;
}

.paid {
    background: #e5f7ed;
    color: #247344;
}

.rejected {
    background: #fff0f0;
    color: #b83c3c;
}

.review {
    background: #eaf3ff;
    color: #285a9c;
}

.pending {
    background: #fff4df;
    color: #99641e;
}

.empty {
    padding: 22px 10px;
    text-align: center;
    color: var(--muted);
    font-size: 13px;
    line-height: 1.8;
}

.quick-actions {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    margin-top: 26px;
}

footer {
    padding: 32px 0;
    margin-top: 20px;
    color: var(--muted);
    font-size: 12px;
    text-align: center;
}

@media (max-width: 850px) {
    .stats {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .layout {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 480px) {
    .wrap {
        width: calc(100% - 24px);
    }

    .hero {
        padding: 21px;
        border-radius: 19px;
    }

    .stat {
        padding: 14px;
    }

    .panel {
        padding: 15px;
    }

    .navbar {
        align-items: flex-start;
    }

    .navlinks {
        width: 100%;
        gap: 7px;
    }

    .navlinks .btn {
        flex: 1;
        padding: 10px 8px;
        font-size: 12px;
    }

    .quick-actions .btn {
        flex: 1 1 auto;
    }

    .stat-value {
        font-size: 21px;
    }
}
</style>

</head>
<body>
<div class="wrap">
    <header class="navbar">
        <a class="brand" href="index.php">Campus<span>Market</span></a>
        <nav class="navlinks">
            <a class="btn" href="index.php">🏠 Beranda</a>
            <a class="btn" href="profile.php">👤 Profil</a>
            <a class="btn btn-orange" href="add_product.php">＋ Tambah Produk</a>
        </nav>
    </header>

    <section class="hero">
        <div class="eyebrow">✦ Ruang usaha mahasiswa</div>
        <h1>Dashboard Penjual 👋</h1>
        <p>
            Halo, <?= h($user['name']) ?>! Pantau produk, pesanan,
            dan omzet jualanmu di CampusMarket.
        </p>
    </section>

    <h2 class="section-title">Ringkasan Jualanmu 📊</h2>

    <section class="stats">
        <article class="stat">
            <div class="stat-icon">🛍️</div>
            <div class="stat-label">Total Produk</div>
            <div class="stat-value"><?= number_format($totalProducts) ?></div>
            <div class="stat-note">Produk milik akunmu</div>
        </article>

        <article class="stat">
            <div class="stat-icon">📦</div>
            <div class="stat-label">Total Pesanan Masuk</div>
            <div class="stat-value"><?= number_format($totalOrders) ?></div>
            <div class="stat-note">Termasuk pesanan lama</div>
        </article>

        <article class="stat">
            <div class="stat-icon">💰</div>
            <div class="stat-label">Total Omzet</div>
            <div class="stat-value"><?= dashboardRupiah($totalRevenue) ?></div>
            <div class="stat-note">Pesanan berstatus Dibayar</div>
        </article>

        <article class="stat">
            <div class="stat-icon">🔔</div>
            <div class="stat-label">Perlu Tindakan</div>
            <div class="stat-value"><?= number_format($pendingOrders) ?></div>
            <div class="stat-note">Menunggu pembayaran atau verifikasi</div>
        </article>
    </section>

    <div class="quick-actions">
        <a class="btn btn-orange" href="add_product.php">＋ Jual Produk Baru</a>
        <a class="btn btn-yellow" href="my_products.php">🛍️ Kelola Produk</a>
        <a class="btn" href="profile.php">📋 Lihat Pesanan Masuk</a>
    </div>

    <section class="layout">
        <article class="panel">
            <h2>🏆 Produk Terlaris</h2>
            <p class="panel-desc">
                Produk dengan jumlah penjualan tertinggi.
            </p>

            <?php if (empty($bestProducts)): ?>
                <div class="empty">
                    Kamu belum punya produk.<br>
                    Yuk, mulai jualan di CampusMarket!
                    <p><a class="btn btn-orange" href="add_product.php">Tambah Produk</a></p>
                </div>
            <?php else: ?>
                <?php foreach ($bestProducts as $product): ?>
                    <div class="product-row">
                        <?php
                        $imagePath = $product['image_url'] ?? '';
                        $validImage = is_string($imagePath)
                            && preg_match(
                                '~\Auploads/products/[a-f0-9]{32}\.(jpg|png|webp)\z~',
                                $imagePath
                            )
                            && is_file(__DIR__ . '/' . $imagePath);
                        ?>

                        <?php if ($validImage): ?>
                            <img
                                class="product-image"
                                src="<?= h($imagePath) ?>"
                                alt="<?= h($product['name']) ?>"
                            >
                        <?php else: ?>
                            <div class="product-image product-placeholder">🛍️</div>
                        <?php endif; ?>

                        <div class="row-main">
                            <div class="row-title"><?= h($product['name']) ?></div>
                            <div class="row-meta">
                                <?= (int) $product['paid_sold'] ?> unit dari pesanan dibayar
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </article>

        <article class="panel">
            <h2>📬 Pesanan Terbaru</h2>
            <p class="panel-desc">
                Lima pesanan terakhir yang masuk ke akunmu.
            </p>

            <?php if (empty($recentOrders)): ?>
                <div class="empty">
                    Belum ada pesanan masuk.<br>
                    Saat pembeli memesan produkmu, pesanan akan muncul di sini.
                </div>
            <?php else: ?>
                <?php foreach ($recentOrders as $order): ?>
                    <div class="order-row">
                        <div class="row-main">
                            <div class="row-title">
                                <?= h($order['order_code']) ?>
                            </div>
                            <div class="row-meta">
                                Pembeli: <?= h($order['buyer_name']) ?><br>
                                <?= h($order['pickup_building']) ?> ·
                                <?= dashboardRupiah($order['total_amount']) ?><br>
                                <?= h(date(
                                    'd-m-Y H:i',
                                    strtotime($order['created_at'])
                                )) ?>
                            </div>
                            <span class="status <?= dashboardStatusClass($order['payment_status']) ?>">
                                <?= h($order['payment_status']) ?>
                            </span>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>

            <p style="margin:18px 0 0">
                <a class="btn btn-yellow" href="profile.php">
                    Lihat Semua Pesanan →
                </a>
            </p>
        </article>
    </section>

    <footer>
        CampusMarket · Jajan dekat, teman makin erat. ✦
    </footer>
</div>
</body>
</html>
