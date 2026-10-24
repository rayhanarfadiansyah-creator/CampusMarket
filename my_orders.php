
<?php
require_once __DIR__ . '/product_helpers.php';
requireLogin();

$userId = (int) $_SESSION['user_id'];

$userStmt = $pdo->prepare("SELECT name FROM users WHERE id = ?");
$userStmt->execute([$userId]);
$currentUser = $userStmt->fetch(PDO::FETCH_ASSOC);

if (!$currentUser) {
    http_response_code(403);
    exit('Akun tidak ditemukan.');
}

$stmt = $pdo->prepare(
    "SELECT
        o.id,
        o.order_code,
        o.buyer_name,
        o.buyer_major,
        o.pickup_building,
        o.total_amount,
        o.payment_status,
        o.created_at,
        u.name AS seller_name
     FROM orders o
     LEFT JOIN users u ON u.id = o.seller_id
     WHERE o.buyer_name = ?
     ORDER BY o.created_at DESC, o.id DESC"
);
$stmt->execute([$currentUser['name']]);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

$itemStmt = $pdo->prepare(
    "SELECT product_name, unit_price, quantity, subtotal
     FROM order_items
     WHERE order_id = ?
     ORDER BY id ASC"
);

function orderRupiah($value): string
{
    return 'Rp ' . number_format((float) $value, 0, ',', '.');
}

function orderStatusColor(string $status): string
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
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pesanan Saya — CampusMarket</title>
    <link rel="stylesheet" href="assets/style.css">
    <style>
        body{background:#fff9f0;color:#242424;font-family:Arial,sans-serif}
        .orders-wrap{width:min(900px,calc(100% - 28px));margin:30px auto 60px}
        .orders-head{margin-bottom:24px}
        .orders-head h1{font-size:clamp(28px,5vw,38px);margin:10px 0}
        .orders-head p{color:#6c665c;line-height:1.6}
        .order-card{background:#fff;border:2px solid #242424;border-radius:18px;padding:20px;margin:18px 0;box-shadow:4px 4px 0 #242424}
        .order-top{display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap;align-items:center}
        .order-code{font-size:18px;font-weight:900;overflow-wrap:anywhere}
        .status{display:inline-block;border-radius:999px;padding:8px 12px;font-size:12px;font-weight:900}
        .pending{background:#fff0c2;color:#775100}
        .review{background:#dcecff;color:#174d8b}
        .paid{background:#dff5d5;color:#27652b}
        .rejected{background:#ffe1df;color:#9a2923}
        .order-info{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px;margin:18px 0}
        .info-box{background:#fff9f0;border-radius:10px;padding:12px}
        .info-box small{display:block;color:#756d62;margin-bottom:5px}
        .items{border-top:1px solid #eee5d9;margin-top:12px;padding-top:10px}
        .item-row,.total-row{display:flex;justify-content:space-between;gap:14px;padding:9px 0}
        .total-row{border-top:1px solid #eee5d9;margin-top:5px;padding-top:15px;font-weight:900;font-size:17px}
        .muted{color:#6c665c;font-size:13px;line-height:1.5}
        .action-link{display:inline-block;margin-top:14px;padding:12px 16px;background:#ff6b35;color:white;border:2px solid #242424;border-radius:11px;font-weight:900;box-shadow:0 3px 0 #242424}
        .empty{background:#fff;border:2px dashed #c8bca9;border-radius:18px;padding:35px 20px;text-align:center}
        .back{display:inline-block;margin-bottom:18px;color:#d94f20;font-weight:900}
        @media(max-width:520px){.order-card{padding:15px}.order-info{grid-template-columns:1fr}.order-top{align-items:flex-start}.item-row{font-size:14px}}
    </style>
</head>
<body>
<main class="orders-wrap">
    <a class="back" href="index.php">← Kembali ke beranda</a>

    <header class="orders-head">
        <p>🛍️ CAMPUSMARKET</p>
        <h1>Pesanan Saya</h1>
        <p>Hai, <?= h($currentUser['name']) ?>! Ini daftar pesananmu.</p>
    </header>

    <?php if (!$orders): ?>
        <section class="empty">
            <div style="font-size:45px">🛍️</div>
            <h2>Belum ada pesanan</h2>
            <p class="muted">
                Pesanan yang dibuat dengan nama akunmu akan muncul di sini.
            </p>
            <a class="action-link" href="index.php">Jelajahi Produk</a>
        </section>
    <?php else: ?>
        <?php foreach ($orders as $order): ?>
            <article class="order-card">
                <div class="order-top">
                    <div>
                        <div class="muted">Kode pesanan</div>
                        <div class="order-code"><?= h($order['order_code']) ?></div>
                    </div>
                    <span class="status <?= orderStatusColor($order['payment_status']) ?>">
                        <?= h($order['payment_status']) ?>
                    </span>
                </div>

                <div class="order-info">
                    <div class="info-box">
                        <small>Penjual</small>
                        <strong><?= h($order['seller_name'] ?: 'Penjual') ?></strong>
                    </div>
                    <div class="info-box">
                        <small>Lokasi pengambilan</small>
                        <strong><?= h($order['pickup_building']) ?></strong>
                    </div>
                    <div class="info-box">
                        <small>Tanggal pesanan</small>
                        <strong><?= h($order['created_at']) ?></strong>
                    </div>
                    <div class="info-box">
                        <small>Jurusan</small>
                        <strong><?= h($order['buyer_major']) ?></strong>
                    </div>
                </div>

                <div class="items">
                    <strong>Rincian produk</strong>

                    <?php
                    $itemStmt->execute([(int) $order['id']]);
                    $items = $itemStmt->fetchAll(PDO::FETCH_ASSOC);
                    ?>

                    <?php foreach ($items as $item): ?>
                        <div class="item-row">
                            <div>
                                <?= h($item['product_name']) ?>
                                <div class="muted">
                                    <?= (int) $item['quantity'] ?> ×
                                    <?= h(orderRupiah($item['unit_price'])) ?>
                                </div>
                            </div>
                            <strong><?= h(orderRupiah($item['subtotal'])) ?></strong>
                        </div>
                    <?php endforeach; ?>

                    <div class="total-row">
                        <span>Total</span>
                        <span><?= h(orderRupiah($order['total_amount'])) ?></span>
                    </div>
                </div>

                <?php if (in_array($order['payment_status'], ['Menunggu Pembayaran', 'Ditolak'], true)): ?>
                    <a class="action-link" href="payment.php?order_id=<?= (int) $order['id'] ?>">
                        💳 Buka Pembayaran
                    </a>
                <?php elseif ($order['payment_status'] === 'Menunggu Verifikasi'): ?>
                    <p class="muted">Bukti pembayaran sedang diperiksa penjual.</p>
                <?php elseif ($order['payment_status'] === 'Dibayar'): ?>
                    <p class="muted">✅ Pembayaran sudah dikonfirmasi. Simpan kode pesananmu.</p>
                <?php endif; ?>
            </article>
        <?php endforeach; ?>
    <?php endif; ?>
</main>
</body>
</html>
