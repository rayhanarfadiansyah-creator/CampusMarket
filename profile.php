
<?php
require_once __DIR__ . '/config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId = (int) $_SESSION['user_id'];
$error = '';
$success = '';

function e($value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function profileRupiah($amount): string
{
    return 'Rp' . number_format((float) $amount, 0, ',', '.');
}

function profileStatusClass($status): string
{
    return match ($status) {
        'Dibayar' => 'status-paid',
        'Ditolak' => 'status-rejected',
        'Menunggu Verifikasi' => 'status-review',
        default => 'status-pending',
    };
}

function profileOrderItems(PDO $pdo, int $orderId): array
{
    $stmt = $pdo->prepare(
        'SELECT product_name, unit_price, quantity, subtotal
         FROM order_items
         WHERE order_id = ?
         ORDER BY id ASC'
    );
    $stmt->execute([$orderId]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';

    if (
        !is_string($token) ||
        !hash_equals($_SESSION['csrf_token'], $token)
    ) {
        http_response_code(403);
        exit('Permintaan tidak valid. Muat ulang halaman lalu coba lagi.');
    }

    $action = $_POST['action'] ?? 'update_profile';

    if ($action === 'update_order_status') {
        $orderId = filter_var(
            $_POST['order_id'] ?? null,
            FILTER_VALIDATE_INT
        );
        $newStatus = $_POST['new_status'] ?? '';

        if (
            !$orderId ||
            !in_array($newStatus, ['Dibayar', 'Ditolak'], true)
        ) {
            $error = 'Data pesanan tidak valid.';
        } else {
            $stmt = $pdo->prepare(
                "UPDATE orders
                 SET payment_status = ?
                 WHERE id = ?
                   AND seller_id = ?
                   AND payment_status = 'Menunggu Verifikasi'
                   AND proof_file IS NOT NULL"
            );

            $stmt->execute([$newStatus, $orderId, $userId]);

            if ($stmt->rowCount() > 0) {
                $success = $newStatus === 'Dibayar'
                    ? 'Pembayaran berhasil dikonfirmasi!'
                    : 'Pembayaran berhasil ditolak.';
            } else {
                $error = 'Pesanan tidak ditemukan atau statusnya sudah berubah.';
            }
        }
    } else {
        $name = trim($_POST['name'] ?? '');
        $major = trim($_POST['major'] ?? '');
        $whatsapp = trim($_POST['whatsapp'] ?? '');
        $bankName = trim($_POST['bank_name'] ?? '');
        $accountName = trim($_POST['bank_account_name'] ?? '');
        $accountNumber = trim($_POST['bank_account_number'] ?? '');

        if (
            $name === '' ||
            $major === '' ||
            mb_strlen($name) > 120 ||
            mb_strlen($major) > 120
        ) {
            $error = 'Nama dan jurusan wajib diisi dengan benar.';
        } elseif (
            mb_strlen($whatsapp) > 25 ||
            mb_strlen($bankName) > 80 ||
            mb_strlen($accountName) > 120 ||
            mb_strlen($accountNumber) > 50
        ) {
            $error = 'Ada informasi yang melebihi batas karakter.';
        } else {
            try {
                $stmt = $pdo->prepare(
                    'UPDATE users
                     SET name = ?, major = ?, whatsapp = ?,
                         bank_name = ?, bank_account_name = ?,
                         bank_account_number = ?
                     WHERE id = ?'
                );

                $stmt->execute([
                    $name,
                    $major,
                    $whatsapp !== '' ? $whatsapp : null,
                    $bankName !== '' ? $bankName : null,
                    $accountName !== '' ? $accountName : null,
                    $accountNumber !== '' ? $accountNumber : null,
                    $userId
                ]);

                $_SESSION['user_name'] = $name;
                $success = 'Profil berhasil diperbarui!';
            } catch (Throwable $ex) {
                error_log($ex->getMessage());
                $error = 'Profil belum berhasil disimpan. Silakan coba lagi.';
            }
        }
    }
}

$stmt = $pdo->prepare(
    'SELECT name, email, major, whatsapp, bank_name,
            bank_account_name, bank_account_number
     FROM users
     WHERE id = ?'
);
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    session_unset();
    session_destroy();
    header('Location: login.php');
    exit;
}

// Pesanan pembelian. Saat ini tabel orders memakai buyer_name.
$stmt = $pdo->prepare(
    'SELECT o.*, u.name AS seller_name
     FROM orders o
     LEFT JOIN users u ON u.id = o.seller_id
     WHERE o.buyer_name = ?
     ORDER BY o.created_at DESC'
);
$stmt->execute([$user['name']]);
$myOrders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Pesanan yang diterima oleh penjual yang sedang login.
$stmt = $pdo->prepare(
    'SELECT o.*
     FROM orders o
     WHERE o.seller_id = ?
     ORDER BY o.created_at DESC'
);
$stmt->execute([$userId]);
$incomingOrders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Profil Saya - CampusMarket</title>
    <link rel="stylesheet" href="assets/style.css">
    
<style>
:root {
    --ink: #202844;
    --orange: #ff7139;
    --yellow: #ffe58a;
    --profile-blue: #2457d6;
    --profile-blue-dark: #173b9b;
    --profile-muted: #727b91;
    --profile-border: #e5eaf5;
}

* {
    box-sizing: border-box;
}

body {
    margin: 0;
    color: var(--ink);
    background: #f6f8ff;
    font-family: "Plus Jakarta Sans", sans-serif;
}

.profile-page {
    width: min(1100px, calc(100% - 36px));
    margin: 24px auto 50px;
}

.profile-topbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 14px;
    margin-bottom: 28px;
}

.profile-topbar .logo {
    color: var(--profile-blue);
    font-size: 24px;
    font-weight: 800;
    letter-spacing: -1px;
    text-decoration: none;
}

.profile-topbar .logo span {
    color: var(--orange);
}

.profile-nav {
    display: flex;
    flex-wrap: wrap;
    gap: 18px;
    font-size: 13px;
    font-weight: 800;
}

.profile-nav a {
    color: var(--ink);
    text-decoration: none;
}

.profile-nav a:hover {
    color: var(--profile-blue);
}

.profile-banner {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 20px;
    padding: 30px;
    margin-bottom: 26px;
    border: 1px solid var(--profile-border);
    border-radius: 24px;
    background: linear-gradient(120deg, #e5ecff, #fff1e9);
    text-align: left;
}

.profile-avatar {
    width: 72px;
    height: 72px;
    flex-shrink: 0;
    display: grid;
    place-items: center;
    border: 0;
    border-radius: 22px;
    background: #fff;
    font-size: 34px;
    transform: none;
}

.profile-banner .eyebrow {
    color: var(--profile-blue);
    font-size: 11px;
    font-weight: 800;
    letter-spacing: 1px;
}

.profile-banner h1 {
    font-size: clamp(26px, 4vw, 36px);
    line-height: 1.2;
    letter-spacing: -1px;
    overflow-wrap: anywhere;
    margin: 8px 0;
}

.profile-banner p {
    margin: 0;
    color: var(--profile-muted);
    font-size: 13px;
    line-height: 1.7;
}

.profile-layout,
.order-sections {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 20px;
    align-items: start;
}

.profile-panel,
.order-panel {
    min-width: 0;
    padding: 25px;
    background: #fff;
    border: 1px solid var(--profile-border);
    border-radius: 20px;
    box-shadow: 0 8px 28px rgba(35, 61, 117, .05);
}

.profile-panel-heading {
    margin-bottom: 20px;
}

.profile-panel-heading h2,
.order-panel h2 {
    font-size: 20px;
    letter-spacing: -.4px;
    margin: 0 0 8px;
}

.profile-panel-heading p,
.order-intro {
    color: var(--profile-muted);
    line-height: 1.7;
    margin: 0;
    font-size: 12px;
}

.profile-form label {
    display: block;
    color: var(--ink);
    font-size: 12px;
    font-weight: 800;
    margin: 17px 0 8px;
}

.profile-form input {
    display: block;
    width: 100%;
    min-width: 0;
    padding: 12px 13px;
    border: 1px solid #dfe4ef;
    border-radius: 11px;
    background: #fafbff;
    color: var(--ink);
    font: inherit;
    font-size: 13px;
    outline: none;
    transition: border-color .2s, box-shadow .2s;
}

.profile-form input:focus {
    border-color: var(--profile-blue);
    box-shadow: 0 0 0 3px rgba(36, 87, 214, .11);
}

.profile-form input:disabled {
    color: #81889a;
    background: #f0f2f7;
    cursor: not-allowed;
}

.profile-field-help {
    display: block;
    margin-top: 7px;
    color: var(--profile-muted);
    font-size: 11px;
    line-height: 1.6;
}

.profile-notice {
    padding: 13px 16px;
    border: 1px solid;
    border-radius: 12px;
    margin-bottom: 20px;
    font-size: 13px;
    line-height: 1.6;
    font-weight: 700;
}

.profile-notice.error {
    color: #922d22;
    background: #fff0ed;
    border-color: #ffd1c9;
}

.profile-notice.success {
    color: #245b2d;
    background: #edf9e9;
    border-color: #cdebc3;
}

.profile-save {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 100%;
    min-height: 46px;
    padding: 12px;
    margin-top: 24px;
    border: 0;
    border-radius: 11px;
    background: var(--profile-blue);
    color: white;
    font: inherit;
    font-size: 13px;
    font-weight: 800;
    cursor: pointer;
    transition: background .2s, transform .2s;
}

.profile-save:hover {
    background: var(--profile-blue-dark);
    transform: translateY(-1px);
}

/* Riwayat pesanan */
.order-sections {
    margin-top: 25px;
}

.order-intro {
    margin-bottom: 18px;
}

.order-card {
    min-width: 0;
    border: 1px solid var(--profile-border);
    border-radius: 14px;
    padding: 15px;
    margin-top: 13px;
    overflow-wrap: anywhere;
    background: #fff;
}

.order-card h3 {
    font-size: 14px;
    margin: 0 0 8px;
    overflow-wrap: anywhere;
}

.order-meta {
    color: var(--profile-muted);
    font-size: 12px;
    line-height: 1.8;
    margin: 7px 0;
}

.order-status {
    display: inline-block;
    padding: 6px 9px;
    border: 0;
    border-radius: 7px;
    font-size: 10px;
    font-weight: 800;
    margin: 4px 0 8px;
}

.status-pending {
    background: #fff3ce;
    color: #795700;
}

.status-review {
    background: #e3edff;
    color: #174a86;
}

.status-paid {
    background: #e4f7df;
    color: #245b2d;
}

.status-rejected {
    background: #fff0ed;
    color: #922d22;
}

.order-items {
    padding-left: 18px;
    font-size: 12px;
    line-height: 1.9;
    color: var(--ink);
}

.order-total {
    border-top: 1px dashed #dce2ee;
    padding-top: 10px;
    margin-top: 10px;
    font-size: 13px;
    font-weight: 800;
}

.order-actions {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
    margin-top: 12px;
}

.order-actions form {
    flex: 1;
    min-width: 110px;
}

.order-actions button,
.order-action-link {
    display: inline-flex;
    justify-content: center;
    align-items: center;
    box-sizing: border-box;
    width: 100%;
    min-height: 40px;
    padding: 10px;
    border: 1px solid var(--profile-border);
    border-radius: 9px;
    font: inherit;
    font-size: 11px;
    font-weight: 800;
    text-align: center;
    text-decoration: none;
    cursor: pointer;
}

.order-confirm {
    background: #e4f7df;
    color: #245b2d;
}

.order-reject {
    background: #fff0ed;
    color: #922d22;
}

.order-pay {
    background: var(--profile-blue);
    color: #fff;
}

.order-proof {
    background: #f1f4fb;
    color: var(--ink);
}

.order-empty {
    padding: 20px 14px;
    border: 1px dashed #d5ddec;
    border-radius: 13px;
    background: #fafbff;
    text-align: center;
    color: var(--profile-muted);
    font-size: 12px;
    line-height: 1.8;
    margin-top: 16px;
}

.order-empty p {
    margin-bottom: 0;
}

.order-empty a {
    color: var(--profile-blue);
    font-weight: 800;
    text-decoration: none;
}

.profile-security-note {
    background: #edf3ff;
    border: 1px solid #d8e3ff;
    border-radius: 15px;
    padding: 18px 20px;
    margin-top: 24px;
    color: #33456f;
    font-size: 12px;
    line-height: 1.8;
}

.profile-security-note strong {
    display: block;
    color: var(--profile-blue);
    margin-bottom: 5px;
}

.profile-footer {
    text-align: center;
    margin-top: 30px;
    color: var(--profile-muted);
    font-size: 11px;
}

@media (max-width: 720px) {
    .profile-layout,
    .order-sections {
        grid-template-columns: minmax(0, 1fr);
    }

    .profile-page {
        width: min(520px, calc(100% - 28px));
        margin-top: 16px;
    }

    .profile-topbar {
        margin-bottom: 20px;
    }

    .profile-banner {
        justify-content: flex-start;
        padding: 22px 18px;
    }

    .profile-panel,
    .order-panel {
        padding: 21px 17px;
    }
}

@media (max-width: 480px) {
    .profile-topbar .logo {
        font-size: 21px;
    }

    .profile-nav {
        gap: 12px;
        font-size: 11px;
    }

    .profile-banner {
        gap: 13px;
        border-radius: 18px;
        padding: 18px 14px;
    }

    .profile-avatar {
        width: 52px;
        height: 52px;
        border-radius: 16px;
        font-size: 27px;
    }

    .profile-banner h1 {
        font-size: 25px;
    }

    .profile-banner p {
        font-size: 11px;
    }

    .profile-panel,
    .order-panel {
        border-radius: 17px;
        padding: 19px 15px;
    }

    .order-actions {
        flex-direction: column;
    }

    .order-actions form {
        width: 100%;
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
<main class="profile-page">

    <header class="profile-topbar">
        <a href="index.php" class="logo">
            Campus<span>Market</span>
        </a>

        <nav class="profile-nav" aria-label="Navigasi akun">
            <a href="index.php">← Beranda</a>
            <a href="my_products.php">Jualan Saya</a>
            <a href="logout.php">Logout</a>
        </nav>
    </header>

    <section class="profile-banner">
        <div class="profile-avatar" aria-hidden="true">👋</div>
        <div>
            <span class="eyebrow">✦ AKUN CAMPUSMARKET</span>
            <h1>Hai, <?= e($user['name']) ?>!</h1>
            <p>Kelola profil dan pantau transaksi CampusMarket kamu.</p>
        </div>
    </section>

    <?php if ($error !== ''): ?>
        <div class="profile-notice error" role="alert">
            ⚠️ <?= e($error) ?>
        </div>
    <?php endif; ?>

    <?php if ($success !== ''): ?>
        <div class="profile-notice success" role="status">
            🎉 <?= e($success) ?>
        </div>
    <?php endif; ?>

    <form method="POST" class="profile-form">
        <input type="hidden" name="csrf_token"
               value="<?= e($_SESSION['csrf_token']) ?>">
        <input type="hidden" name="action" value="update_profile">

        <div class="profile-layout">

            <section class="profile-panel">
                <div class="profile-panel-heading">
                    <h2>👤 Identitas mahasiswa</h2>
                    <p>Informasi dasar akun dan kontak kamu.</p>
                </div>

                <label for="name">Nama lengkap *</label>
                <input id="name" name="name" type="text" maxlength="120"
                       value="<?= e($_POST['name'] ?? $user['name']) ?>"
                       required>

                <label for="email">Email akun</label>
                <input id="email" type="email"
                       value="<?= e($user['email']) ?>" disabled>
                <small class="profile-field-help">
                    Email akun tidak bisa diubah dari halaman ini.
                </small>

                <label for="major">Jurusan *</label>
                <input id="major" name="major" type="text" maxlength="120"
                       value="<?= e($_POST['major'] ?? $user['major']) ?>"
                       placeholder="Contoh: Sistem Informasi" required>

                <label for="whatsapp">Nomor WhatsApp</label>
                <input id="whatsapp" name="whatsapp" type="tel" maxlength="25"
                       value="<?= e($_POST['whatsapp'] ?? $user['whatsapp']) ?>"
                       placeholder="Contoh: 628123456789">
                <small class="profile-field-help">
                    Gunakan nomor aktif jika ingin dihubungi pembeli.
                </small>
            </section>

            <section class="profile-panel">
                <div class="profile-panel-heading">
                    <h2>💳 Rekening penjual</h2>
                    <p>Isi jika kamu ingin menerima pembayaran melalui transfer bank.</p>
                </div>

                <label for="bank_name">Nama bank</label>
                <input id="bank_name" name="bank_name" type="text" maxlength="80"
                       value="<?= e($_POST['bank_name'] ?? $user['bank_name']) ?>"
                       placeholder="Contoh: BCA, BRI, BNI">

                <label for="bank_account_name">Nama pemilik rekening</label>
                <input id="bank_account_name" name="bank_account_name"
                       type="text" maxlength="120"
                       value="<?= e($_POST['bank_account_name'] ?? $user['bank_account_name']) ?>"
                       placeholder="Sesuai nama di rekening">

                <label for="bank_account_number">Nomor rekening</label>
                <input id="bank_account_number" name="bank_account_number"
                       type="text" maxlength="50"
                       value="<?= e($_POST['bank_account_number'] ?? $user['bank_account_number']) ?>"
                       placeholder="Masukkan nomor rekening">

                <small class="profile-field-help">
                    Pastikan detail rekening benar sebelum menerima pembayaran.
                </small>

                <button class="button profile-save" type="submit">
                    💾 Simpan Perubahan
                </button>
            </section>

        </div>
    </form>

    <!-- PESANAN SAYA DAN PESANAN MASUK -->
    <section class="order-sections">

        <section class="order-panel">
            <h2>🛍️ Pesanan Saya</h2>
            <p class="order-intro">
                Pantau barang yang kamu pesan dan status pembayarannya.
            </p>

            <?php if (empty($myOrders)): ?>
                <div class="order-empty">
                    Belum ada pesanan yang tercatat untuk akun ini.
                    <p><a href="index.php">Jelajahi produk →</a></p>
                </div>
            <?php else: ?>
                <?php foreach ($myOrders as $order): ?>
                    <?php $items = profileOrderItems($pdo, (int) $order['id']); ?>

                    <article class="order-card">
                        <h3><?= e($order['order_code']) ?></h3>

                        <span class="order-status <?= e(profileStatusClass($order['payment_status'])) ?>">
                            <?= e($order['payment_status']) ?>
                        </span>

                        <p class="order-meta">
                            Penjual: <strong><?= e($order['seller_name'] ?? 'Penjual') ?></strong><br>
                            Lokasi pengambilan: <?= e($order['pickup_building']) ?><br>
                            Tanggal: <?= e($order['created_at']) ?>
                        </p>

                        <ul class="order-items">
                            <?php foreach ($items as $item): ?>
                                <li>
                                    <?= e($item['product_name']) ?>
                                    × <?= (int) $item['quantity'] ?>
                                    — <?= profileRupiah($item['subtotal']) ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>

                        <div class="order-total">
                            Total: <?= profileRupiah($order['total_amount']) ?>
                        </div>

                        <?php if (in_array($order['payment_status'], ['Menunggu Pembayaran', 'Ditolak'], true)): ?>
                            <div class="order-actions">
                                <a class="order-action-link order-pay"
                                   href="payment.php?order_id=<?= (int) $order['id'] ?>">
                                    💳 Bayar / Upload Bukti
                                </a>
                            </div>
                        <?php elseif ($order['payment_status'] === 'Menunggu Verifikasi'): ?>
                            <p class="order-meta">
                                ⏳ Bukti pembayaran sedang menunggu verifikasi penjual.
                            </p>
                        <?php elseif ($order['payment_status'] === 'Dibayar'): ?>
                            <p class="order-meta">
                                ✅ Pembayaran sudah dikonfirmasi penjual.
                            </p>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>

        <section class="order-panel">
            <h2>📦 Pesanan Masuk</h2>
            <p class="order-intro">
                Pesanan dari pembeli untuk produk yang kamu jual.
            </p>

            <?php if (empty($incomingOrders)): ?>
                <div class="order-empty">
                    Belum ada pesanan masuk untuk produkmu.
                    <p><a href="my_products.php">Lihat jualan saya →</a></p>
                </div>
            <?php else: ?>
                <?php foreach ($incomingOrders as $order): ?>
                    <?php $items = profileOrderItems($pdo, (int) $order['id']); ?>

                    <article class="order-card">
                        <h3><?= e($order['order_code']) ?></h3>

                        <span class="order-status <?= e(profileStatusClass($order['payment_status'])) ?>">
                            <?= e($order['payment_status']) ?>
                        </span>

                        <p class="order-meta">
                            Pembeli: <strong><?= e($order['buyer_name']) ?></strong><br>
                            Jurusan: <?= e($order['buyer_major']) ?><br>
                            Pengambilan: <?= e($order['pickup_building']) ?><br>
                            Tanggal: <?= e($order['created_at']) ?>
                        </p>

                        <ul class="order-items">
                            <?php foreach ($items as $item): ?>
                                <li>
                                    <?= e($item['product_name']) ?>
                                    × <?= (int) $item['quantity'] ?>
                                    — <?= profileRupiah($item['subtotal']) ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>

                        <div class="order-total">
                            Total: <?= profileRupiah($order['total_amount']) ?>
                        </div>

                        <?php
                        $proofPath = $order['proof_file'] ?? '';
                        $validProof = is_string($proofPath) &&
                            preg_match(
                                '~\Auploads/payments/[a-f0-9]{32}\.(jpg|png|webp|pdf)\z~',
                                $proofPath
                            );
                        ?>

                        <?php if ($validProof): ?>
                            <div class="order-actions">
                                <a class="order-action-link order-proof"
                                   href="<?= e($proofPath) ?>"
                                   target="_blank" rel="noopener noreferrer">
                                    🧾 Lihat Bukti Transfer
                                </a>
                            </div>
                        <?php endif; ?>

                        <?php if (
                            $order['payment_status'] === 'Menunggu Verifikasi'
                            && $validProof
                        ): ?>
                            <div class="order-actions">
                                <form method="POST"
                                      onsubmit="return confirm('Konfirmasi bahwa pembayaran ini sudah diterima?')">
                                    <input type="hidden" name="csrf_token"
                                           value="<?= e($_SESSION['csrf_token']) ?>">
                                    <input type="hidden" name="action"
                                           value="update_order_status">
                                    <input type="hidden" name="order_id"
                                           value="<?= (int) $order['id'] ?>">
                                    <input type="hidden" name="new_status"
                                           value="Dibayar">
                                    <button class="order-confirm" type="submit">
                                        ✓ Konfirmasi Dibayar
                                    </button>
                                </form>

                                <form method="POST"
                                      onsubmit="return confirm('Yakin ingin menolak pembayaran ini?')">
                                    <input type="hidden" name="csrf_token"
                                           value="<?= e($_SESSION['csrf_token']) ?>">
                                    <input type="hidden" name="action"
                                           value="update_order_status">
                                    <input type="hidden" name="order_id"
                                           value="<?= (int) $order['id'] ?>">
                                    <input type="hidden" name="new_status"
                                           value="Ditolak">
                                    <button class="order-reject" type="submit">
                                        ✕ Tolak Pembayaran
                                    </button>
                                </form>
                            </div>
                        <?php elseif ($order['payment_status'] === 'Menunggu Pembayaran'): ?>
                            <p class="order-meta">
                                ⏳ Menunggu pembeli mengirim bukti pembayaran.
                            </p>
                        <?php elseif ($order['payment_status'] === 'Menunggu Verifikasi'): ?>
                            <p class="order-meta">
                                ⚠️ Bukti transfer belum tersedia atau tidak dapat ditampilkan.
                            </p>
                        <?php elseif ($order['payment_status'] === 'Dibayar'): ?>
                            <p class="order-meta">
                                ✅ Pembayaran telah dikonfirmasi.
                            </p>
                        <?php elseif ($order['payment_status'] === 'Ditolak'): ?>
                            <p class="order-meta">
                                ❌ Pembayaran ditolak. Pembeli perlu menindaklanjuti pesanan.
                            </p>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>

    </section>

    <aside class="profile-security-note">
        <strong>🔐 Jaga informasi akunmu</strong>
        Jangan bagikan kata sandi kepada siapa pun. Informasi rekening
        dan WhatsApp sebaiknya hanya digunakan untuk transaksi yang kamu setujui.
    </aside>

    <footer class="profile-footer">
        <p>CampusMarket · Jajan dekat, teman makin erat 💛</p>
    </footer>

</main>
</body>
</html>
