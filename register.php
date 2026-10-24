
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
        .register-page {
            min-height: 100vh;
            padding: 20px;
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

        .register-nav-link {
            font-size: 14px;
            font-weight: 800;
        }

        .register-nav-link:hover {
            color: var(--orange);
        }

        .register-layout {
            width: min(1040px, 100%);
            margin: auto;
            padding: 38px 0;
            display: grid;
            grid-template-columns: 0.85fr 1.15fr;
            align-items: center;
            gap: 50px;
        }

        .register-intro h1 {
            font-size: clamp(38px, 5vw, 58px);
            line-height: 1.04;
            letter-spacing: -2px;
            margin: 20px 0 16px;
        }

        .register-intro h1 span {
            color: var(--orange);
        }

        .register-intro > p {
            color: #746b60;
            line-height: 1.8;
            max-width: 390px;
        }

        .register-benefits {
            list-style: none;
            padding: 0;
            margin: 25px 0 0;
            display: grid;
            gap: 12px;
        }

        .register-benefits li {
            display: flex;
            align-items: center;
            gap: 11px;
            font-weight: 800;
            font-size: 14px;
        }

        .benefit-icon {
            width: 36px;
            height: 36px;
            display: grid;
            place-items: center;
            flex-shrink: 0;
            background: #fff;
            border: 2px solid var(--ink);
            border-radius: 12px;
        }

        .register-art {
            margin-top: 28px;
            padding: 22px;
            border: 2px solid var(--ink);
            border-radius: 24px;
            background: var(--yellow);
            box-shadow: 5px 5px 0 var(--ink);
            text-align: center;
            font-size: 45px;
        }

        .register-art p {
            margin: 12px 0 0;
            font-size: 13px;
            font-weight: 900;
        }

        .register-card {
            background: #fff;
            border: 2px solid var(--ink);
            border-radius: 26px;
            padding: clamp(24px, 4vw, 36px);
            box-shadow: 6px 6px 0 var(--ink);
        }

        .register-card-heading {
            display: flex;
            align-items: center;
            gap: 14px;
            margin-bottom: 22px;
        }

        .register-icon {
            width: 58px;
            height: 58px;
            flex-shrink: 0;
            display: grid;
            place-items: center;
            background: #ffc2d1;
            border: 2px solid var(--ink);
            border-radius: 18px;
            font-size: 30px;
            transform: rotate(-5deg);
        }

        .register-card h2 {
            font-size: 27px;
            margin: 0 0 5px;
            letter-spacing: -0.8px;
        }

        .register-card-heading p {
            margin: 0;
            color: #746b60;
            font-size: 13px;
            line-height: 1.6;
        }

        .register-error {
            padding: 12px 14px;
            margin-bottom: 18px;
            border: 2px solid var(--ink);
            border-radius: 12px;
            color: #922d22;
            background: #ffe5e0;
            font-size: 13px;
            font-weight: 700;
            line-height: 1.6;
        }

        .register-form label {
            display: block;
            margin: 16px 0 7px;
            font-size: 14px;
            font-weight: 800;
        }

        .register-form input {
            box-sizing: border-box;
            width: 100%;
            min-width: 0;
            padding: 12px 13px;
            border: 2px solid #e5ded4;
            border-radius: 12px;
            background: #fffdf9;
            color: var(--ink);
            font: inherit;
            outline: none;
        }

        .register-form input:focus {
            border-color: var(--orange);
            box-shadow: 0 0 0 3px #ff6b3525;
        }

        .register-form input::placeholder {
            color: #aaa096;
        }

        .register-field-note {
            display: block;
            margin-top: 7px;
            color: #746b60;
            font-size: 12px;
            line-height: 1.5;
        }

        .register-password-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px;
        }

        .register-submit {
            width: 100%;
            justify-content: center;
            margin-top: 25px;
        }

        .register-divider {
            display: flex;
            align-items: center;
            gap: 12px;
            margin: 22px 0;
            color: #8a8176;
            font-size: 12px;
        }

        .register-divider::before,
        .register-divider::after {
            content: "";
            height: 1px;
            flex: 1;
            background: #e5ded4;
        }

        .register-login {
            text-align: center;
            font-size: 14px;
            line-height: 1.8;
        }

        .register-login a {
            color: var(--orange);
            font-weight: 900;
        }

        .register-login a:hover {
            text-decoration: underline;
        }

        .register-footer {
            text-align: center;
            color: #746b60;
            font-size: 12px;
            padding: 16px 0 4px;
        }

        @media (max-width: 800px) {
            .register-layout {
                max-width: 500px;
                grid-template-columns: 1fr;
                gap: 30px;
                padding: 35px 0 28px;
            }

            .register-intro {
                text-align: center;
            }

            .register-intro h1 {
                font-size: clamp(36px, 8vw, 48px);
            }

            .register-intro > p {
                margin-left: auto;
                margin-right: auto;
                font-size: 14px;
            }

            .register-benefits {
                max-width: 300px;
                margin: 22px auto 0;
                text-align: left;
            }

            .register-art {
                display: none;
            }
        }

        @media (max-width: 480px) {
            .register-page {
                padding: 14px;
            }

            .register-nav-link {
                font-size: 12px;
            }

            .register-card {
                padding: 22px 17px;
                border-radius: 22px;
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
                    <span class="benefit-icon">🤝</span>
                    Dukung usaha teman kampus
                </li>
            </ul>

            <div class="register-art" aria-hidden="true">
                🥟 🍢 🍡 🧋
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
        CampusMarket · Tempat jajan dan usaha mahasiswa 💛
    </footer>

</main>
</body>
</html>
