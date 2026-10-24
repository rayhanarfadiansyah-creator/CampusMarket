
<?php
session_start();
require_once __DIR__ . '/config/database.php';

if (isset($_SESSION['user_id'])) {
    header('Location: profile.php');
    exit;
}

$error = '';
$registered = isset($_GET['registered']);

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
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

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || !is_string($password)) {
        $error = 'Email atau password salah.';
    } else {
        $stmt = $pdo->prepare(
            'SELECT id, name, email, password
             FROM users
             WHERE email = ?'
        );
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            session_regenerate_id(true);

            $_SESSION['user_id'] = (int) $user['id'];
            $_SESSION['user_name'] = $user['name'];

            header('Location: profile.php');
            exit;
        }

        $error = 'Email atau password salah.';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login - CampusMarket</title>
    <link rel="stylesheet" href="assets/style.css">

    <style>
        .login-page {
            min-height: 100vh;
            padding: 20px;
            display: flex;
            flex-direction: column;
        }

        .login-nav {
            width: min(1100px, 100%);
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
        }

        .login-nav-link {
            font-size: 14px;
            font-weight: 800;
        }

        .login-nav-link:hover {
            color: var(--orange);
        }

        .login-layout {
            width: min(1000px, 100%);
            margin: auto;
            padding: 45px 0;
            display: grid;
            grid-template-columns: 1fr 0.9fr;
            align-items: center;
            gap: 55px;
        }

        .login-intro {
            position: relative;
        }

        .login-intro h1 {
            font-size: clamp(38px, 5vw, 58px);
            line-height: 1.04;
            letter-spacing: -2px;
            margin: 20px 0 16px;
        }

        .login-intro h1 span {
            color: var(--orange);
        }

        .login-intro > p {
            color: #746b60;
            line-height: 1.8;
            font-size: 16px;
            max-width: 410px;
        }

        .login-foods {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 28px;
        }

        .login-food {
            display: grid;
            place-items: center;
            width: 66px;
            height: 66px;
            border: 2px solid var(--ink);
            border-radius: 18px;
            font-size: 32px;
            box-shadow: 3px 3px 0 var(--ink);
        }

        .login-food:nth-child(1) { background: #ffcfaa; transform: rotate(-5deg); }
        .login-food:nth-child(2) { background: #ffc2d1; transform: rotate(4deg); }
        .login-food:nth-child(3) { background: #cde9b9; transform: rotate(-3deg); }
        .login-food:nth-child(4) { background: #c4e2ff; transform: rotate(5deg); }

        .login-card {
            background: #fff;
            border: 2px solid var(--ink);
            border-radius: 26px;
            padding: clamp(24px, 4vw, 36px);
            box-shadow: 6px 6px 0 var(--ink);
        }

        .login-card-top {
            display: flex;
            align-items: center;
            gap: 14px;
            margin-bottom: 22px;
        }

        .login-icon {
            display: grid;
            place-items: center;
            flex-shrink: 0;
            width: 58px;
            height: 58px;
            background: var(--yellow);
            border: 2px solid var(--ink);
            border-radius: 18px;
            font-size: 30px;
            transform: rotate(-5deg);
        }

        .login-card h2 {
            font-size: 28px;
            margin: 0 0 5px;
            letter-spacing: -0.8px;
        }

        .login-card-top p {
            color: #746b60;
            font-size: 13px;
            margin: 0;
            line-height: 1.6;
        }

        .login-alert {
            border: 2px solid var(--ink);
            border-radius: 12px;
            padding: 12px 14px;
            margin-bottom: 18px;
            font-size: 13px;
            font-weight: 700;
            line-height: 1.6;
        }

        .login-alert.error {
            color: #922d22;
            background: #ffe5e0;
        }

        .login-alert.success {
            color: #245b2d;
            background: #e5f4db;
        }

        .login-form label {
            display: block;
            font-weight: 800;
            font-size: 14px;
            margin: 17px 0 8px;
        }

        .login-input-wrap {
            position: relative;
        }

        .login-form input {
            box-sizing: border-box;
            width: 100%;
            min-width: 0;
            padding: 13px 14px;
            border: 2px solid #e5ded4;
            border-radius: 12px;
            font: inherit;
            background: #fffdf9;
            color: var(--ink);
            outline: none;
        }

        .login-form input:focus {
            border-color: var(--orange);
            box-shadow: 0 0 0 3px #ff6b3525;
        }

        .login-submit {
            width: 100%;
            margin-top: 25px;
            justify-content: center;
        }

        .login-divider {
            display: flex;
            align-items: center;
            gap: 12px;
            color: #8a8176;
            font-size: 12px;
            margin: 22px 0;
        }

        .login-divider::before,
        .login-divider::after {
            content: "";
            flex: 1;
            height: 1px;
            background: #e5ded4;
        }

        .login-register {
            text-align: center;
            line-height: 1.8;
            font-size: 14px;
        }

        .login-register a {
            color: var(--orange);
            font-weight: 900;
        }

        .login-register a:hover {
            text-decoration: underline;
        }

        .login-footer {
            text-align: center;
            color: #746b60;
            font-size: 12px;
            padding: 16px 0 4px;
        }

        @media (max-width: 760px) {
            .login-layout {
                max-width: 460px;
                grid-template-columns: 1fr;
                gap: 28px;
                padding: 38px 0 28px;
            }

            .login-intro {
                text-align: center;
            }

            .login-intro h1 {
                font-size: clamp(35px, 9vw, 48px);
            }

            .login-intro > p {
                margin-left: auto;
                margin-right: auto;
                font-size: 14px;
            }

            .login-foods {
                justify-content: center;
                margin-top: 20px;
            }

            .login-food {
                width: 48px;
                height: 48px;
                font-size: 24px;
                border-radius: 14px;
            }

            .login-card {
                border-radius: 22px;
            }
        }

        @media (max-width: 380px) {
            .login-page {
                padding: 14px;
            }

            .login-nav-link {
                font-size: 12px;
            }

            .login-card {
                padding: 21px 17px;
            }
        }
    </style>
</head>
<body>
<main class="login-page">

    <header class="login-nav">
        <a href="index.php" class="logo">
            Campus<span>Market</span>
        </a>

        <a href="index.php" class="login-nav-link">
            ← Kembali ke Beranda
        </a>
    </header>

    <div class="login-layout">

        <section class="login-intro">
            <span class="eyebrow">✦ HALO, ANAK KAMPUS!</span>

            <h1>
                Yuk, lanjut<br>
                <span>jajan & jualan.</span>
            </h1>

            <p>
                Masuk ke CampusMarket dan temukan jajanan favoritmu,
                atau lanjutkan perjalanan membangun bisnis kecil-kecilan
                bareng teman kampus!
            </p>

            <div class="login-foods" aria-label="Jajanan favorit">
                <span class="login-food">🥟</span>
                <span class="login-food">🍢</span>
                <span class="login-food">🍡</span>
                <span class="login-food">🧋</span>
            </div>
        </section>

        <section class="login-card">
            <div class="login-card-top">
                <div class="login-icon" aria-hidden="true">👋</div>
                <div>
                    <h2>Welcome back!</h2>
                    <p>Masuk dulu, yuk. Kami sudah menunggumu.</p>
                </div>
            </div>

            <?php if ($registered): ?>
                <div class="login-alert success" role="status">
                    🎉 Registrasi berhasil! Sekarang kamu bisa login.
                </div>
            <?php endif; ?>

            <?php if ($error !== ''): ?>
                <div class="login-alert error" role="alert">
                    ⚠️ <?= e($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="login-form">
                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= e($_SESSION['csrf_token']) ?>"
                >

                <label for="email">Email kamu</label>
                <input
                    id="email"
                    name="email"
                    type="email"
                    placeholder="nama@email.com"
                    value="<?= e($_POST['email'] ?? '') ?>"
                    autocomplete="email"
                    maxlength="254"
                    required
                >

                <label for="password">Password</label>
                <input
                    id="password"
                    name="password"
                    type="password"
                    placeholder="Masukkan password kamu"
                    autocomplete="current-password"
                    required
                >

                <button type="submit" class="button login-submit">
                    Login ke Akun ✨
                </button>
            </form>

            <div class="login-divider">BELUM PUNYA AKUN?</div>

            <p class="login-register">
                Belum gabung komunitas kami?<br>
                <a href="register.php">Daftar sekarang, gratis! ↗</a>
            </p>
        </section>

    </div>

    <footer class="login-footer">
        CampusMarket · Jajan dekat, teman makin erat 💛
    </footer>

</main>
</body>
</html>
