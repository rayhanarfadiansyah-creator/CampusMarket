
<?php
session_start();
require_once __DIR__ . '/config/database.php';

if (isset($_SESSION['user_id'])) {
    header('Location: profile.php');
    exit;
}

$error = '';

function e($value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
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

    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $major = trim($_POST['major'] ?? '');
    $whatsapp = trim($_POST['whatsapp'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if (
        $name === '' ||
        $major === '' ||
        mb_strlen($name) > 120 ||
        mb_strlen($major) > 120 ||
        !filter_var($email, FILTER_VALIDATE_EMAIL) ||
        strlen($email) > 190
    ) {
        $error = 'Lengkapi nama, jurusan, dan email dengan benar.';
    } elseif (mb_strlen($whatsapp) > 25) {
        $error = 'Nomor WhatsApp maksimal 25 karakter.';
    } elseif (!is_string($password) || strlen($password) < 8) {
        $error = 'Password minimal 8 karakter.';
    } elseif (!is_string($confirm) || $password !== $confirm) {
        $error = 'Konfirmasi password tidak cocok.';
    } else {
        try {
            $stmt = $pdo->prepare(
                'SELECT id FROM users WHERE email = ?'
            );
            $stmt->execute([$email]);

            if ($stmt->fetch()) {
                $error = 'Email sudah terdaftar. Silakan login.';
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);

                $stmt = $pdo->prepare(
                    'INSERT INTO users
                        (name, email, password, major, whatsapp)
                     VALUES (?, ?, ?, ?, ?)'
                );

                $stmt->execute([
                    $name,
                    $email,
                    $hash,
                    $major,
                    $whatsapp !== '' ? $whatsapp : null
                ]);

                header('Location: login.php?registered=1');
                exit;
            }
        } catch (PDOException $exception) {
            error_log($exception->getMessage());

            if ($exception->getCode() === '23000') {
                $error = 'Email sudah terdaftar. Silakan login.';
            } else {
                $error = 'Registrasi gagal. Silakan coba lagi.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Daftar - CampusMarket</title>
    <link rel="stylesheet" href="assets/style.css">

    
<style>
:root {
    --reg-blue: #2457d6;
    --reg-blue-dark: #173b9b;
    --reg-orange: #ff7139;
    --reg-ink: #202844;
    --reg-muted: #727b91;
    --reg-border: #e5eaf5;
    --ink: #202844;
    --orange: #ff7139;
    --yellow: #ffe58a;
}

* {
    box-sizing: border-box;
}

body {
    margin: 0;
    color: var(--reg-ink);
    background: linear-gradient(135deg, #eef2ff 0%, #f8f9fd 55%, #fff0e8 100%);
    font-family: "Plus Jakarta Sans", sans-serif;
}

.register-page {
    min-height: 100vh;
    padding: 22px;
    display: flex;
    flex-direction: column;
}

.register-nav {
    width: min(1100px, 100%);
    margin: 0 auto;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
}

.register-nav .logo {
    color: var(--reg-blue);
    font-size: 24px;
    font-weight: 800;
    letter-spacing: -1px;
    text-decoration: none;
}

.register-nav .logo span {
    color: var(--reg-orange);
}

.register-nav-link {
    color: var(--reg-blue);
    font-size: 13px;
    font-weight: 800;
    text-decoration: none;
}

.register-nav-link:hover {
    color: var(--reg-orange);
}

.register-layout {
    width: min(480px, 100%);
    margin: auto;
    padding: 36px 0;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 24px;
}

.register-intro {
    width: 100%;
    text-align: center;
}

.register-intro .eyebrow {
    display: inline-block;
    margin: 0 0 12px;
    color: var(--reg-blue);
    font-size: 11px;
    font-weight: 800;
    letter-spacing: 1.3px;
}

.register-intro h1 {
    margin: 0;
    color: var(--reg-ink);
    font-size: clamp(34px, 7vw, 45px);
    line-height: 1.15;
    letter-spacing: -1.8px;
}

.register-intro h1 span {
    color: var(--reg-blue);
}

.register-intro > p {
    max-width: 410px;
    margin: 14px auto 0;
    color: var(--reg-muted);
    font-size: 13px;
    line-height: 1.8;
}

/* Hilangkan ilustrasi dan daftar benefit di samping */
.register-art,
.register-benefits {
    display: none !important;
}

.register-card {
    width: 100%;
    padding: 30px;
    border: 1px solid var(--reg-border);
    border-radius: 24px;
    background: #fff;
    box-shadow: 0 18px 55px rgba(35, 61, 117, .10);
}

.register-card-heading {
    display: flex;
    align-items: center;
    gap: 14px;
    margin-bottom: 23px;
}

.register-icon {
    width: 52px;
    height: 52px;
    flex-shrink: 0;
    display: grid;
    place-items: center;
    border: 0;
    border-radius: 16px;
    background: #e8eeff;
    color: var(--reg-blue);
    font-size: 26px;
    transform: none;
}

.register-card h2 {
    margin: 0 0 5px;
    font-size: 25px;
    letter-spacing: -.7px;
}

.register-card-heading p {
    margin: 0;
    color: var(--reg-muted);
    font-size: 12px;
    line-height: 1.6;
}

.register-error {
    padding: 12px 14px;
    margin-bottom: 18px;
    border: 1px solid #ffd1c9;
    border-radius: 12px;
    color: #922d22;
    background: #fff0ed;
    font-size: 12px;
    font-weight: 700;
    line-height: 1.6;
}

.register-form label {
    display: block;
    margin: 16px 0 8px;
    color: var(--reg-ink);
    font-size: 12px;
    font-weight: 800;
}

.register-form input {
    display: block;
    width: 100%;
    min-width: 0;
    padding: 13px 14px;
    border: 1px solid #dfe4ef;
    border-radius: 12px;
    outline: none;
    background: #fafbff;
    color: var(--reg-ink);
    font: inherit;
    font-size: 13px;
    transition: border-color .2s, box-shadow .2s;
}

.register-form input::placeholder {
    color: #a0a8b9;
}

.register-form input:focus {
    border-color: var(--reg-blue);
    box-shadow: 0 0 0 3px rgba(36, 87, 214, .12);
    background: #fff;
}

.register-field-note {
    display: block;
    margin-top: 7px;
    color: var(--reg-muted);
    font-size: 11px;
    line-height: 1.6;
}

.register-password-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 14px;
}

.register-password-grid > div {
    min-width: 0;
}

.register-submit {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 100%;
    min-height: 47px;
    margin-top: 25px;
    padding: 12px 16px;
    border: 0;
    border-radius: 12px;
    background: var(--reg-blue);
    color: #fff;
    font: inherit;
    font-size: 13px;
    font-weight: 800;
    cursor: pointer;
    transition: background .2s, transform .2s;
}

.register-submit:hover {
    background: var(--reg-blue-dark);
    transform: translateY(-1px);
}

.register-divider {
    display: flex;
    align-items: center;
    gap: 12px;
    margin: 23px 0;
    color: #9299aa;
    font-size: 10px;
    font-weight: 800;
    letter-spacing: .5px;
}

.register-divider::before,
.register-divider::after {
    content: "";
    flex: 1;
    height: 1px;
    background: var(--reg-border);
}

.register-login {
    margin: 0;
    color: var(--reg-muted);
    font-size: 12px;
    line-height: 1.9;
    text-align: center;
}

.register-login a {
    color: var(--reg-blue);
    font-weight: 800;
    text-decoration: none;
}

.register-login a:hover {
    color: var(--reg-orange);
    text-decoration: underline;
}

.register-footer {
    padding: 14px 0 4px;
    color: var(--reg-muted);
    font-size: 11px;
    text-align: center;
}

@media (max-width: 480px) {
    .register-page {
        padding: 16px;
    }

    .register-nav .logo {
        font-size: 21px;
    }

    .register-nav-link {
        font-size: 11px;
    }

    .register-layout {
        padding: 30px 0 24px;
        gap: 20px;
    }

    .register-intro h1 {
        font-size: 35px;
    }

    .register-intro > p {
        font-size: 12px;
    }

    .register-card {
        padding: 23px 18px;
        border-radius: 20px;
    }

    .register-card h2 {
        font-size: 22px;
    }

    .register-password-grid {
        grid-template-columns: 1fr;
        gap: 0;
    }
}
</style>

</head>
<body>
<main class="register-page">

    <header class="register-nav">
        <a href="index.php" class="logo">
            Campus<span>Market</span>
        </a>

        <a href="index.php" class="register-nav-link">
            ← Kembali ke Beranda
        </a>
    </header>

    <div class="register-layout">

        <section class="register-intro">
            <span class="eyebrow">✦ GABUNG KOMUNITAS KAMPUS</span>

            <h1>
                Satu akun,<br>
                banyak <span>keseruan!</span>
            </h1>

            <p>
                Bikin akun CampusMarket dan mulai jelajahi jajanan
                teman kampus. Punya produk sendiri? Kamu juga bisa
                mulai berjualan di sini!
            </p>

            <ul class="register-benefits">
                <li>
                    <span class="benefit-icon">🍡</span>
                    Temukan jajanan favorit
                </li>
                <li>
                    <span class="benefit-icon">🛍️</span>
                    Promosikan jualanmu
                </li>
                <li>
                    <span class="benefit-icon"></span>
                    Dukung usaha teman kampus
                </li>
            </ul>

            <div class="register-art" aria-hidden="true">
                
                <p>Jajan dekat, teman makin erat!</p>
            </div>
        </section>

        <section class="register-card">
            <div class="register-card-heading">
                <div class="register-icon" aria-hidden="true">✨</div>
                <div>
                    <h2>Buat akun baru</h2>
                    <p>Isi data di bawah, lalu siap gabung!</p>
                </div>
            </div>

            <?php if ($error !== ''): ?>
                <div class="register-error" role="alert">
                    ⚠️ <?= e($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="register-form">
                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= e($_SESSION['csrf_token']) ?>"
                >

                <label for="name">Nama lengkap *</label>
                <input
                    id="name"
                    name="name"
                    type="text"
                    maxlength="120"
                    autocomplete="name"
                    placeholder="Nama kamu"
                    required
                    value="<?= e($_POST['name'] ?? '') ?>"
                >

                <label for="email">Email *</label>
                <input
                    id="email"
                    name="email"
                    type="email"
                    maxlength="190"
                    autocomplete="email"
                    placeholder="nama@email.com"
                    required
                    value="<?= e($_POST['email'] ?? '') ?>"
                >

                <label for="major">Jurusan *</label>
                <input
                    id="major"
                    name="major"
                    type="text"
                    maxlength="120"
                    placeholder="Contoh: Sistem Informasi"
                    required
                    value="<?= e($_POST['major'] ?? '') ?>"
                >

                <label for="whatsapp">Nomor WhatsApp</label>
                <input
                    id="whatsapp"
                    name="whatsapp"
                    type="tel"
                    maxlength="25"
                    autocomplete="tel"
                    placeholder="Contoh: 628123456789"
                    value="<?= e($_POST['whatsapp'] ?? '') ?>"
                >
                <small class="register-field-note">
                    Opsional, tetapi berguna untuk komunikasi transaksi.
                </small>

                <div class="register-password-grid">
                    <div>
                        <label for="password">Password *</label>
                        <input
                            id="password"
                            name="password"
                            type="password"
                            minlength="8"
                            autocomplete="new-password"
                            placeholder="Min. 8 karakter"
                            required
                        >
                    </div>

                    <div>
                        <label for="confirm_password">Ulangi password *</label>
                        <input
                            id="confirm_password"
                            name="confirm_password"
                            type="password"
                            minlength="8"
                            autocomplete="new-password"
                            placeholder="Ketik ulang"
                            required
                        >
                    </div>
                </div>

                <button type="submit" class="button register-submit">
                    Buat Akun Gratis 🚀
                </button>
            </form>

            <div class="register-divider">SUDAH PUNYA AKUN?</div>

            <p class="register-login">
                Selamat datang kembali!<br>
                <a href="login.php">Login ke akunmu ↗</a>
            </p>
        </section>

    </div>

    <footer class="register-footer">
        CampusMarket · Tempat jajan dan usaha mahasiswa 
    </footer>

</main>
</body>
</html>
