
<?php
require_once __DIR__ . '/product_helpers.php';
requireLogin();

$message = '';
$error = '';

function e($value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function rupiah($value): string
{
    return 'Rp ' . number_format((float) $value, 0, ',', '.');
}

$orderId = filter_input(INPUT_GET, 'order_id', FILTER_VALIDATE_INT);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken();

    $orderId = filter_input(INPUT_POST, 'order_id', FILTER_VALIDATE_INT);

    if (!$orderId || $orderId < 1) {
        http_response_code(400);
        exit('Nomor pesanan tidak valid.');
    }

    try {
        $stmt = $pdo->prepare(
            "SELECT id, payment_status
             FROM orders
             WHERE id = ?
             LIMIT 1"
        );
        $stmt->execute([$orderId]);
        $orderToUpdate = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$orderToUpdate) {
            http_response_code(404);
            exit('Pesanan tidak ditemukan.');
        }

        if (!in_array(
            $orderToUpdate['payment_status'],
            ['Menunggu Pembayaran', 'Ditolak'],
            true
        )) {
            throw new RuntimeException(
                'Pesanan ini tidak menerima bukti pembayaran baru.'
            );
        }

        if (
            !isset($_FILES['proof_file']) ||
            $_FILES['proof_file']['error'] === UPLOAD_ERR_NO_FILE
        ) {
            throw new RuntimeException(
                'Pilih file bukti transfer terlebih dahulu.'
            );
        }

        $file = $_FILES['proof_file'];

        if ($file['error'] !== UPLOAD_ERR_OK || is_array($file['name'])) {
            throw new RuntimeException('Upload file gagal. Coba lagi.');
        }

        if ($file['size'] > 3 * 1024 * 1024) {
            throw new RuntimeException('Ukuran file maksimal 3 MB.');
        }

        if (!is_uploaded_file($file['tmp_name'])) {
            throw new RuntimeException('File yang diunggah tidak valid.');
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']);

        $allowed = [
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/webp' => 'webp',
            'application/pdf' => 'pdf',
        ];

        if (!isset($allowed[$mime])) {
            throw new RuntimeException(
                'Bukti pembayaran harus berupa JPG, PNG, WebP, atau PDF.'
            );
        }

        if (str_starts_with($mime, 'image/') &&
            getimagesize($file['tmp_name']) === false) {
            throw new RuntimeException('File gambar tidak valid.');
        }

        $directory = __DIR__ . '/uploads/payments';

        if (!is_dir($directory) &&
            !mkdir($directory, 0755, true) &&
            !is_dir($directory)) {
            throw new RuntimeException(
                'Folder penyimpanan bukti pembayaran gagal dibuat.'
            );
        }

        $filename = bin2hex(random_bytes(16)) . '.' . $allowed[$mime];
        $destination = $directory . '/' . $filename;
        $relativePath = 'uploads/payments/' . $filename;

        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            throw new RuntimeException(
                'Bukti pembayaran gagal disimpan.'
            );
        }

        try {
            $update = $pdo->prepare(
                "UPDATE orders
                 SET proof_file = ?, payment_status = 'Menunggu Verifikasi'
                 WHERE id = ?"
            );
            $update->execute([$relativePath, $orderId]);
        } catch (Throwable $e) {
            if (is_file($destination)) {
                unlink($destination);
            }
            throw $e;
        }

        header('Location: payment.php?order_id=' . $orderId . '&uploaded=1');
        exit;

    } catch (RuntimeException $e) {
        $error = $e->getMessage();
    } catch (Throwable $e) {
        $error = 'Terjadi kesalahan saat menyimpan pembayaran. Coba lagi.';
    }
}

if (!$orderId || $orderId < 1) {
    http_response_code(400);
    exit(
        'Nomor pesanan tidak ditemukan. Silakan buka pembayaran dari halaman checkout.'
    );
}

$stmt = $pdo->prepare(
    "SELECT
        o.id,
        o.order_code,
        o.seller_id,
        o.buyer_name,
        o.buyer_major,
        o.pickup_building,
        o.total_amount,
        o.payment_status,
        o.proof_file,
        o.created_at,
        u.name AS seller_name,
        u.bank_name,
        u.bank_account_name,
        u.bank_account_number
     FROM orders o
     LEFT JOIN users u ON u.id = o.seller_id
     WHERE o.id = ?
     LIMIT 1"
);
$stmt->execute([$orderId]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    http_response_code(404);
    exit('Pesanan tidak ditemukan.');
}

$itemStmt = $pdo->prepare(
    "SELECT product_name, unit_price, quantity, subtotal
     FROM order_items
     WHERE order_id = ?
     ORDER BY id ASC"
);
$itemStmt->execute([$orderId]);
$items = $itemStmt->fetchAll(PDO::FETCH_ASSOC);

$isUploaded = isset($_GET['uploaded']) &&
    $_GET['uploaded'] === '1';

$canUpload = in_array(
    $order['payment_status'],
    ['Menunggu Pembayaran', 'Ditolak'],
    true
);

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pembayaran Pesanan — CampusMarket</title>
    <link rel="stylesheet" href="assets/style.css">
    ```html
<style>
    * {
        box-sizing: border-box;
    }

    body {
        margin: 0;
        background: #f7f9fc;
        color: #243047;
        font-family: "Trebuchet MS", Arial, sans-serif;
    }

    .payment-wrap {
        width: min(760px, calc(100% - 28px));
        margin: 28px auto 60px;
    }

    .payment-card {
        background: #fff;
        border: 1px solid #e2eaf4;
        border-radius: 20px;
        padding: 24px;
        margin-bottom: 20px;
        box-shadow: 0 5px 22px rgba(36, 65, 105, .06);
    }

    .payment-card h1,
    .payment-card h2 {
        color: #243e63;
        margin-top: 0;
    }

    .payment-card h1 {
        font-size: clamp(25px, 5vw, 32px);
        letter-spacing: -.8px;
        margin-bottom: 22px;
    }

    .payment-card h2 {
        font-size: 19px;
    }

    .payment-card > p:first-child {
        color: #4778b6;
        font-size: 11px;
        font-weight: 900;
        letter-spacing: 1px;
    }

    .payment-row {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 16px;
        padding: 13px 0;
        border-bottom: 1px solid #edf0f5;
    }

    .payment-row:last-child {
        border-bottom: 0;
    }

    .payment-row span:first-child {
        color: #7b8799;
    }

    .payment-row strong {
        text-align: right;
        overflow-wrap: anywhere;
    }

    .payment-total {
        font-size: 21px;
        font-weight: 900;
        color: #f07832;
    }

    .bank-box {
        background: #edf4ff;
        border: 1px solid #d6e5fa;
        border-radius: 16px;
        padding: 20px;
        overflow-wrap: anywhere;
    }

    .bank-box p {
        margin: 8px 0;
        color: #71819a;
        font-size: 13px;
    }

    .bank-box h2 {
        margin-bottom: 18px;
        color: #285a9c;
    }

    .bank-number {
        font-size: clamp(20px, 4vw, 26px);
        font-weight: 900;
        letter-spacing: 1px;
        color: #244d83;
        overflow-wrap: anywhere;
    }

    .payment-card label {
        display: block;
        margin: 16px 0 9px;
        font-weight: 700;
        font-size: 14px;
        color: #34445e;
    }

    .payment-card input[type="file"] {
        display: block;
        width: 100%;
        padding: 12px;
        border: 1px solid #dce5f0;
        border-radius: 12px;
        background: #f9fbfe;
        color: #34445e;
        font-size: 13px;
    }

    .payment-card input[type="file"]:focus {
        outline: 2px solid #bcd8ff;
        border-color: #6c9edb;
    }

    .payment-btn {
        display: block;
        width: 100%;
        margin-top: 20px;
        padding: 15px;
        border: 0;
        border-radius: 12px;
        background: #f47b36;
        color: #fff;
        font-weight: 900;
        font-size: 15px;
        cursor: pointer;
        transition: background .2s, transform .2s;
    }

    .payment-btn:hover {
        background: #e96b28;
        transform: translateY(-1px);
    }

    .notice {
        padding: 14px 16px;
        border-radius: 12px;
        margin-bottom: 17px;
        line-height: 1.65;
        font-size: 13px;
    }

    .notice.success {
        background: #eaf8ef;
        border: 1px solid #c9e9d4;
        color: #267344;
    }

    .notice.error {
        background: #fff0f0;
        border: 1px solid #f1d0d0;
        color: #b83c3c;
    }

    .status {
        display: inline-block;
        padding: 7px 11px;
        background: #fff4df;
        color: #99641e;
        border-radius: 999px;
        font-size: 12px;
        font-weight: 800;
        text-align: center;
    }

    .muted {
        color: #7b8799;
        line-height: 1.7;
        font-size: 13px;
    }

    .back-link {
        display: inline-flex;
        margin-bottom: 18px;
        font-size: 14px;
        font-weight: 800;
        color: #3169b2;
    }

    .back-link:hover {
        color: #f07832;
    }

    @media (max-width: 480px) {
        .payment-wrap {
            width: calc(100% - 24px);
            margin-top: 18px;
        }

        .payment-card {
            padding: 18px;
            border-radius: 17px;
            margin-bottom: 16px;
        }

        .payment-row {
            gap: 12px;
            font-size: 13px;
        }

        .payment-row span:first-child {
            flex: 0 0 42%;
        }

        .payment-row strong {
            min-width: 0;
        }

        .bank-box {
            padding: 16px;
        }

        .payment-total {
            font-size: 18px;
        }
    }
</style>
<script type="text/javascript">
    (function(c,l,a,r,i,t,y){
        c[a]=c[a]||function(){(c[a].q=c[a].q||[]).push(arguments)};
        t=l.createElement(r);t.async=1;t.src="https://www.clarity.ms/tag/"+i;
        y=l.getElementsByTagName(r)[0];y.parentNode.insertBefore(t,y);
    })(window, document, "clarity", "script", "z5t50kimie");
</script>
</head>
<body>

<div class="payment-wrap">
    <a class="back-link" href="index.php">← Kembali ke CampusMarket</a>

    <section class="payment-card">
        <p>🧾 DETAIL PESANAN</p>
        <h1>Pembayaran Pesanan</h1>

        <?php if ($isUploaded): ?>
            <div class="notice success">
                Bukti pembayaran berhasil dikirim. Tunggu penjual atau admin
                memverifikasi pembayaranmu.
            </div>
        <?php endif; ?>

        <?php if ($error !== ''): ?>
            <div class="notice error"><?= e($error) ?></div>
        <?php endif; ?>

        <div class="payment-row">
            <span>Kode pesanan</span>
            <strong><?= e($order['order_code']) ?></strong>
        </div>

        <div class="payment-row">
            <span>Nama pembeli</span>
            <strong><?= e($order['buyer_name']) ?></strong>
        </div>

        <div class="payment-row">
            <span>Jurusan</span>
            <strong><?= e($order['buyer_major']) ?></strong>
        </div>

        <div class="payment-row">
            <span>Lokasi pengambilan</span>
            <strong><?= e($order['pickup_building']) ?></strong>
        </div>

        <div class="payment-row">
            <span>Penjual</span>
            <strong><?= e($order['seller_name'] ?: 'Penjual') ?></strong>
        </div>

        <div class="payment-row">
            <span>Status pembayaran</span>
            <span class="status"><?= e($order['payment_status']) ?></span>
        </div>
    </section>

    <section class="payment-card">
        <h2>🛍️ Rincian Produk</h2>

        <?php if (!$items): ?>
            <p class="muted">Belum ada rincian produk untuk pesanan ini.</p>
        <?php else: ?>
            <?php foreach ($items as $item): ?>
                <div class="payment-row">
                    <div>
                        <strong><?= e($item['product_name']) ?></strong>
                        <div class="muted">
                            <?= (int) $item['quantity'] ?> ×
                            <?= e(rupiah($item['unit_price'])) ?>
                        </div>
                    </div>

                    <strong><?= e(rupiah($item['subtotal'])) ?></strong>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <div class="payment-row">
            <strong>Total pembayaran</strong>
            <strong class="payment-total">
                <?= e(rupiah($order['total_amount'])) ?>
            </strong>
        </div>
    </section>

    <section class="payment-card">
        <h2>🏦 Transfer ke Rekening Penjual</h2>

        <?php if (
            !empty($order['bank_name']) &&
            !empty($order['bank_account_number'])
        ): ?>
            <div class="bank-box">
                <p>Nama bank</p>
                <h2><?= e($order['bank_name']) ?></h2>

                <p>Nomor rekening</p>
                <div class="bank-number">
                    <?= e($order['bank_account_number']) ?>
                </div>

                <p>Atas nama</p>
                <strong>
                    <?= e($order['bank_account_name'] ?: $order['seller_name']) ?>
                </strong>
            </div>

            <p class="muted">
                Transfer sesuai total pembayaran di atas. Setelah transfer,
                unggah bukti pembayaran untuk diverifikasi.
            </p>
        <?php else: ?>
            <div class="notice error">
                Rekening penjual belum diisi. Hubungi penjual untuk meminta
                informasi pembayaran sebelum melakukan transfer.
            </div>
        <?php endif; ?>

        <?php if ($canUpload): ?>
            <h2 style="margin-top:24px">📤 Kirim Bukti Transfer</h2>

            <form method="POST" enctype="multipart/form-data">
                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= e(getCsrfToken()) ?>"
                >

                <input
                    type="hidden"
                    name="order_id"
                    value="<?= (int) $order['id'] ?>"
                >

                <label for="proof_file">
                    Pilih bukti pembayaran (maksimal 3 MB)
                </label>

                <input
                    type="file"
                    name="proof_file"
                    id="proof_file"
                    accept=".jpg,.jpeg,.png,.webp,.pdf"
                    required
                >

                <button class="payment-btn" type="submit">
                    Kirim Bukti Pembayaran
                </button>
            </form>
        <?php elseif ($order['payment_status'] === 'Menunggu Verifikasi'): ?>
            <div class="notice success">
                Bukti pembayaran sudah dikirim dan sedang menunggu verifikasi.
            </div>

            <?php if (!empty($order['proof_file'])): ?>
                <p>
                    File bukti:
                    <strong><?= e(basename($order['proof_file'])) ?></strong>
                </p>
            <?php endif; ?>
        <?php elseif ($order['payment_status'] === 'Dibayar'): ?>
            <div class="notice success">
                Pembayaran sudah dikonfirmasi. Simpan kode pesananmu untuk
                proses pengambilan produk.
            </div>
        <?php else: ?>
            <p class="muted">
                Hubungi penjual atau admin untuk informasi status pembayaran ini.
            </p>
        <?php endif; ?>
    </section>

    <p class="muted" style="text-align:center">
        Simpan kode pesananmu: <strong><?= e($order['order_code']) ?></strong>
    </p>
</div>

</body>
</html>
