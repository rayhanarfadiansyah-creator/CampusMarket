
<?php
require_once __DIR__ . '/product_helpers.php';
requireLogin();

$userId = (int) $_SESSION['user_id'];
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken();

    $orderId = filter_input(INPUT_POST, 'order_id', FILTER_VALIDATE_INT);
    $newStatus = $_POST['new_status'] ?? '';

    if (!$orderId || $orderId < 1 ||
        !in_array($newStatus, ['Dibayar', 'Ditolak'], true)) {
        $error = 'Data perubahan status tidak valid.';
    } else {
        try {
            $stmt = $pdo->prepare(
                "UPDATE orders
                 SET payment_status = ?
                 WHERE id = ?
                   AND seller_id = ?
                   AND payment_status = 'Menunggu Verifikasi'
                   AND proof_file IS NOT NULL"
            );

            $stmt->execute([$newStatus, $orderId, $userId]);

            if ($stmt->rowCount() === 1) {
                $success = $newStatus === 'Dibayar'
                    ? 'Pembayaran berhasil dikonfirmasi.'
                    : 'Pembayaran ditolak.';
            } else {
                $error = 'Pesanan tidak ditemukan atau statusnya sudah berubah.';
            }
        } catch (Throwable $e) {
            $error = 'Gagal mengubah status pesanan. Silakan coba lagi.';
        }
    }
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
        o.proof_file,
        o.created_at
     FROM orders o
     WHERE o.seller_id = ?
     ORDER BY o.created_at DESC, o.id DESC"
);
$stmt->execute([$userId]);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

$itemStmt = $pdo->prepare(
    "SELECT product_name, unit_price, quantity, subtotal
     FROM order_items
     WHERE order_id = ?
     ORDER BY id ASC"
);

function incomingRupiah($value): string
{
    return 'Rp ' . number_format((float) $value, 0, ',', '.');
}

function incomingStatusClass(string $status): string
{
    if ($status === 'Dibayar') return 'paid';
    if ($status === 'Ditolak') return 'rejected';
    if ($status === 'Menunggu Verifikasi') return 'review';
    return 'pending';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pesanan Masuk — CampusMarket</title>
    <link rel="stylesheet" href="assets/style.css">
    <style>
        body{background:#fff9f0;color:#242424;font-family:Arial,sans-serif}
        .orders-wrap{width:min(900px,calc(100% - 28px));margin:30px auto 60px}
        .head{margin-bottom:24px}
        .head h1{font-size:clamp(28px,5vw,38px);margin:10px 0}
        .head p{color:#6c665c;line-height:1.6}
        .order-card{background:#fff;border:2px solid #242424;border-radius:18px;padding:20px;margin:18px 0;box-shadow:4px 4px 0 #242424}
        .order-top{display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap}
        .order-code{font-size:18px;font-weight:900;overflow-wrap:anywhere}
        .status{display:inline-block;border-radius:999px;padding:8px 12px;font-size:12px;font-weight:900}
        .pending{background:#fff0c2;color:#775100}
        .review{background:#dcecff;color:#174d8b}
        .paid{background:#dff5d5;color:#27652b}
        .rejected{background:#ffe1df;color:#9a2923}
        .info{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px;margin:18px 0}
        .info-box{background:#fff9f0;border-radius:10px;padding:12px;overflow-wrap:anywhere}
        .info-box small{display:block;color:#756d62;margin-bottom:5px}
        .item-row,.total-row{display:flex;justify-content:space-between;gap:14px;padding:9px 0}
        .items{border-top:1px solid #eee5d9;margin-top:12px;padding-top:10px}
        .total-row{border-top:1px solid #eee5d9;margin-top:5px;padding-top:15px;font-size:17px;font-weight:900}
        .muted{color:#6c665c;font-size:13px;line-height:1.5}
        .proof-link{display:inline-block;margin:12px 0;padding:11px 14px;border:2px solid #242424;border-radius:10px;background:#fff0d5;font-weight:900;overflow-wrap:anywhere}
        .btn{padding:12px 16px;border:2px solid #242424;border-radius:10px;font-weight:900;cursor:pointer}
        .approve{background:#bce6a7;color:#242424}
        .reject{background:#ffe1df;color:#8c211b}
        .actions{display:flex;gap:10px;flex-wrap:wrap;margin-top:14px}
        .notice{padding:13px 15px;border-radius:10px;margin-bottom:14px;line-height:1.5}
        .success{background:#e7f6dc;border:1px solid #9ac789}
        .error{background:#ffe1df;border:1px solid #e49a94}
        .empty{background:#fff;border:2px dashed #c8bca9;border-radius:18px;padding:35px 20px;text-align:center}
        .back{display:inline-block;margin-bottom:18px;color:#d94f20;font-weight:900}
        @media(max-width:520px){.order-card{padding:15px}.info{grid-template-columns:1fr}.item-row{font-size:14px}.actions .btn{width:100%}}
    </style>
</head>
<body>
<main class="orders-wrap">
    <a class="back" href="index.php">← Kembali ke beranda</a>

    <header class="head">
        <p>📦 DASHBOARD PENJUAL</p>
        <h1>Pesanan Masuk</h1>
        <p>Periksa pesanan, cek bukti transfer, lalu perbarui status pembayaran.</p>
    </header>

    <?php if ($success !== ''): ?>
        <div class="notice success"><?= h($success) ?></div>
    <?php endif; ?>

    <?php if ($error !== ''): ?>
        <div class="notice error"><?= h($error) ?></div>
    <?php endif; ?>

    <?php if (!$orders): ?>
        <section class="empty">
            <div style="font-size:45px">📦</div>
            <h2>Belum ada pesanan masuk</h2>
            <p class="muted">Pesanan untuk produk milikmu akan muncul di sini.</p>
        </section>
    <?php else: ?>
        <?php foreach ($orders as $order): ?>
            <article class="order-card">
                <div class="order-top">
                    <div>
                        <div class="muted">Kode pesanan</div>
                        <div class="order-code"><?= h($order['order_code']) ?></div>
                    </div>

                    <span class="status <?= incomingStatusClass($order['payment_status']) ?>">
                        <?= h($order['payment_status']) ?>
                    </span>
                </div>

                <div class="info">
                    <div class="info-box">
                        <small>Nama pembeli</small>
                        <strong><?= h($order['buyer_name']) ?></strong>
                    </div>
                    <div class="info-box">
                        <small>Jurusan pembeli</small>
                        <strong><?= h($order['buyer_major']) ?></strong>
                    </div>
                    <div class="info-box">
                        <small>Lokasi pengambilan</small>
                        <strong><?= h($order['pickup_building']) ?></strong>
                    </div>
                    <div class="info-box">
                        <small>Tanggal pesanan</small>
                        <strong><?= h($order['created_at']) ?></strong>
                    </div>
                </div>

                <div class="items">
                    <strong>Produk yang dipesan</strong>

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
                                    <?= h(incomingRupiah($item['unit_price'])) ?>
                                </div>
                            </div>
                            <strong><?= h(incomingRupiah($item['subtotal'])) ?></strong>
                        </div>
                    <?php endforeach; ?>

                    <div class="total-row">
                        <span>Total pesanan</span>
                        <span><?= h(incomingRupiah($order['total_amount'])) ?></span>
                    </div>
                </div>

                <?php
                $proofPath = $order['proof_file'] ?? '';
                $validProofPath = is_string($proofPath) &&
                    preg_match('~\Auploads/payments/[a-f0-9]{32}\.(jpg|png|webp|pdf)\z~', $proofPath);
                ?>

                <?php if ($validProofPath): ?>
                    <a
                        class="proof-link"
                        href="<?= h($proofPath) ?>"
                        target="_blank"
                        rel="noopener noreferrer"
                    >
                        📄 Buka Bukti Pembayaran
                    </a>
                <?php elseif ($order['payment_status'] === 'Menunggu Pembayaran'): ?>
                    <p class="muted">Pembeli belum mengirim bukti pembayaran.</p>
                <?php else: ?>
                    <p class="muted">File bukti pembayaran tidak tersedia.</p>
                <?php endif; ?>

                <?php if (
                    $order['payment_status'] === 'Menunggu Verifikasi' &&
                    $validProofPath
                ): ?>
                    <form method="POST" class="actions">
                        <input
                            type="hidden"
                            name="csrf_token"
                            value="<?= h(getCsrfToken()) ?>"
                        >
                        <input
                            type="hidden"
                            name="order_id"
                            value="<?= (int) $order['id'] ?>"
                        >

                        <button
                            class="btn approve"
                            type="submit"
                            name="new_status"
                            value="Dibayar"
                            onclick="return confirm('Konfirmasi pembayaran pesanan ini?')"
                        >
                            ✅ Konfirmasi Dibayar
                        </button>

                        <button
                            class="btn reject"
                            type="submit"
                            name="new_status"
                            value="Ditolak"
                            onclick="return confirm('Tolak bukti pembayaran ini?')"
                        >
                            ✖ Tolak Pembayaran
                        </button>
                    </form>
                <?php endif; ?>
            </article>
        <?php endforeach; ?>
    <?php endif; ?>
</main>
</body>
</html>
