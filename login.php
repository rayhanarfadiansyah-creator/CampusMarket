
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
:root {
    --login-blue: #2457d6;
    --login-blue-dark: #173b9b;
    --login-orange: #ff7139;
    --login-ink: #202844;
    --login-muted: #727b91;
    --login-border: #e5eaf5;
    --ink: #202844;
    --orange: #ff7139;
    --yellow: #ffe58a;
}

* {
    box-sizing: border-box;
}

body {
    margin: 0;
    color: var(--login-ink);
    background: linear-gradient(135deg, #eef2ff 0%, #f8f9fd 55%, #fff0e8 100%);
    font-family: "Plus Jakarta Sans", sans-serif;
}

.login-page {
    min-height: 100vh;
    padding: 22px;
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

.login-nav .logo {
    color: var(--login-blue);
    font-size: 24px;
    font-weight: 800;
    letter-spacing: -1px;
    text-decoration: none;
}

.login-nav .logo span {
    color: var(--login-orange);
}

.login-nav-link {
    color: var(--login-blue);
    font-size: 13px;
    font-weight: 800;
    text-decoration: none;
}

.login-nav-link:hover {
    color: var(--login-orange);
}

.login-layout {
    width: min(440px, 100%);
    margin: auto;
    padding: 38px 0;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 24px;
}

.login-intro {
    width: 100%;
    text-align: center;
}

.login-intro .eyebrow {
    display: inline-block;
    margin: 0 0 12px;
    color: var(--login-blue);
    font-size: 11px;
    font-weight: 800;
    letter-spacing: 1.3px;
}

.login-intro h1 {
    margin: 0;
    color: var(--login-ink);
    font-size: clamp(34px, 7vw, 45px);
    line-height: 1.15;
    letter-spacing: -1.8px;
}

.login-intro h1 span {
    color: var(--login-blue);
}

.login-intro > p {
    max-width: 390px;
    margin: 14px auto 0;
    color: var(--login-muted);
    font-size: 13px;
    line-height: 1.8;
}

/* Hilangkan ilustrasi makanan */
.login-foods {
    display: none !important;
}

.login-card {
    width: 100%;
    padding: 30px;
    border: 1px solid var(--login-border);
    border-radius: 24px;
    background: #fff;
    box-shadow: 0 18px 55px rgba(35, 61, 117, .10);
}

.login-card-top {
    display: flex;
    align-items: center;
    gap: 14px;
    margin-bottom: 24px;
}

.login-icon {
    display: grid;
    place-items: center;
    flex-shrink: 0;
    width: 52px;
    height: 52px;
    border-radius: 16px;
    background: #e8eeff;
    color: var(--login-blue);
    font-size: 26px;
}

.login-card h2 {
    margin: 0 0 5px;
    font-size: 25px;
    letter-spacing: -.7px;
}

.login-card-top p {
    margin: 0;
    color: var(--login-muted);
    font-size: 12px;
    line-height: 1.6;
}

.login-alert {
    padding: 12px 14px;
    margin-bottom: 17px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 700;
    line-height: 1.6;
}

.login-alert.error {
    color: #922d22;
    background: #fff0ed;
    border: 1px solid #ffd1c9;
}

.login-alert.success {
    color: #245b2d;
    background: #edf9e9;
    border: 1px solid #cdebc3;
}

.login-form label {
    display: block;
    margin: 17px 0 8px;
    color: var(--login-ink);
    font-size: 12px;
    font-weight: 800;
}

.login-form input {
    display: block;
    width: 100%;
    min-width: 0;
    padding: 13px 14px;
    border: 1px solid #dfe4ef;
    border-radius: 12px;
    outline: none;
    background: #fafbff;
    color: var(--login-ink);
    font: inherit;
    font-size: 13px;
    transition: border-color .2s, box-shadow .2s;
}

.login-form input::placeholder {
    color: #a0a8b9;
}

.login-form input:focus {
    border-color: var(--login-blue);
    box-shadow: 0 0 0 3px rgba(36, 87, 214, .12);
    background: #fff;
}

.login-submit {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 100%;
    min-height: 47px;
    margin-top: 24px;
    padding: 12px 16px;
    border: 0;
    border-radius: 12px;
    background: var(--login-blue);
    color: #fff;
    font: inherit;
    font-size: 13px;
    font-weight: 800;
    cursor: pointer;
    transition: background .2s, transform .2s;
}

.login-submit:hover {
    background: var(--login-blue-dark);
    transform: translateY(-1px);
}

.login-divider {
    display: flex;
    align-items: center;
    gap: 12px;
    margin: 23px 0;
    color: #9299aa;
    font-size: 10px;
    font-weight: 800;
    letter-spacing: .5px;
}

.login-divider::before,
.login-divider::after {
    content: "";
    flex: 1;
    height: 1px;
    background: var(--login-border);
}

.login-register {
    margin: 0;
    color: var(--login-muted);
    font-size: 12px;
    line-height: 1.9;
    text-align: center;
}

.login-register a {
    color: var(--login-blue);
    font-weight: 800;
    text-decoration: none;
}

.login-register a:hover {
    color: var(--login-orange);
    text-decoration: underline;
}

.login-footer {
    padding: 14px 0 4px;
    color: var(--login-muted);
    font-size: 11px;
    text-align: center;
}

@media (max-width: 480px) {
    .login-page {
        padding: 18px;
    }

    .login-nav .logo {
        font-size: 21px;
    }

    .login-nav-link {
        font-size: 11px;
    }

    .login-layout {
        padding: 34px 0 25px;
        gap: 22px;
    }

    .login-intro h1 {
        font-size: 35px;
    }

    .login-intro > p {
        font-size: 12px;
    }

    .login-card {
        padding: 23px 19px;
        border-radius: 20px;
    }

    .login-card h2 {
        font-size: 22px;
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
        CampusMarket · Jajan dekat, teman makin erat 
    </footer>

</main>
</body>
</html>
