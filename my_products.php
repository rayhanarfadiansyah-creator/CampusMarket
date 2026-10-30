
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
:root {
    --ink: #202844;
    --orange: #ff7139;
    --yellow: #ffe58a;
    --blue: #2457d6;
    --blue-dark: #173b9b;
    --muted: #727b91;
    --border: #e5eaf5;
}

* {
    box-sizing: border-box;
}

body {
    margin: 0;
    background: #f6f8ff;
    color: var(--ink);
    font-family: "Plus Jakarta Sans", sans-serif;
}

.seller-page {
    width: min(1100px, calc(100% - 36px));
    margin: 24px auto 50px;
}

.seller-topbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 14px;
    flex-wrap: wrap;
    margin-bottom: 25px;
}

.seller-topbar .logo {
    color: var(--blue);
    font-size: 24px;
    font-weight: 800;
    letter-spacing: -1px;
    text-decoration: none;
}

.seller-topbar .logo span {
    color: var(--orange);
}

.seller-topbar nav {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 18px;
    font-size: 13px;
    font-weight: 800;
}

.seller-topbar nav a {
    color: var(--ink);
    text-decoration: none;
}

.seller-topbar nav a:hover {
    color: var(--blue);
}

.seller-hero {
    display: flex;
    justify-content: center;
    align-items: center;
    text-align: center;
    margin: 0 auto 28px;
    padding: 35px 24px;
    border: 1px solid var(--border);
    border-radius: 24px;
    background: linear-gradient(120deg, #e5ecff, #fff0e8);
}

.seller-hero h1 {
    margin: 12px 0;
    font-size: clamp(30px, 5vw, 43px);
    line-height: 1.15;
    letter-spacing: -1.5px;
}

.seller-hero p {
    margin: 0 auto;
    max-width: 480px;
    color: var(--muted);
    font-size: 13px;
    line-height: 1.8;
}

.seller-hero .eyebrow {
    color: var(--blue);
    font-size: 11px;
    font-weight: 800;
    letter-spacing: 1px;
}

.seller-hero-art {
    display: none;
}

.seller-section-heading {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 14px;
    margin: 30px 0 20px;
}

.seller-section-heading h2 {
    margin: 0 0 6px;
    font-size: 22px;
    letter-spacing: -.5px;
}

.seller-section-heading p {
    margin: 0;
    color: var(--muted);
    font-size: 12px;
}

.seller-section-heading > a.button,
.seller-empty > a.button {
    display: inline-flex;
    justify-content: center;
    align-items: center;
    min-height: 42px;
    padding: 11px 17px;
    border: 0;
    border-radius: 11px;
    background: var(--blue);
    color: #fff;
    font-size: 12px;
    font-weight: 800;
    text-decoration: none;
    box-shadow: none;
}

.seller-section-heading > a.button:hover,
.seller-empty > a.button:hover {
    background: var(--blue-dark);
}

.seller-product-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 20px;
}

.seller-product-card {
    min-width: 0;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    background: #fff;
    border: 1px solid var(--border);
    border-radius: 18px;
    box-shadow: 0 6px 22px rgba(35, 61, 117, .05);
    transition: transform .2s, box-shadow .2s;
}

.seller-product-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 12px 26px rgba(35, 61, 117, .1);
}

.seller-product-image,
.seller-product-placeholder {
    display: grid;
    place-items: center;
    width: 100%;
    height: 190px;
    object-fit: cover;
    background: #e8eeff;
}

.seller-product-placeholder {
    font-size: 48px;
}

.seller-product-body {
    display: flex;
    flex: 1;
    flex-direction: column;
    padding: 17px;
}

.seller-product-body .category {
    align-self: flex-start;
    padding: 6px 9px;
    border-radius: 7px;
    background: #edf2ff;
    color: var(--blue);
    font-size: 10px;
    font-weight: 800;
}

.seller-product-body h3 {
    margin: 12px 0 5px;
    font-size: 16px;
    line-height: 1.5;
    overflow-wrap: anywhere;
}

.seller-product-price {
    margin: 4px 0 10px;
    color: var(--blue);
    font-size: 18px;
    font-weight: 900;
}

.seller-product-description {
    margin: 0;
    color: var(--muted);
    font-size: 12px;
    line-height: 1.8;
    overflow-wrap: anywhere;
    white-space: normal;
}

.seller-product-date {
    margin-top: auto;
    padding-top: 15px;
    color: var(--muted);
    font-size: 10px;
}

.seller-product-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-top: 15px;
}

.seller-product-actions .button,
.seller-product-actions form {
    flex: 1;
    min-width: 0;
}

.seller-product-actions form {
    display: flex;
}

.seller-product-actions .button,
.seller-product-actions button {
    display: flex;
    justify-content: center;
    align-items: center;
    width: 100%;
    min-height: 39px;
    padding: 9px 10px;
    border: 1px solid var(--border);
    border-radius: 9px;
    background: #f1f4fb;
    color: var(--ink);
    font-family: inherit;
    font-size: 11px;
    font-weight: 800;
    text-align: center;
    text-decoration: none;
    cursor: pointer;
    box-shadow: none;
}

.seller-product-actions .button:hover {
    background: #e4ebff;
}

.seller-product-actions .seller-delete-button {
    border-color: #ffdfda;
    background: #fff0ed;
    color: #a52d24;
}

.seller-product-actions .seller-delete-button:hover {
    background: #ffe0db;
}

.seller-notice {
    padding: 13px 16px;
    border: 1px solid;
    border-radius: 12px;
    margin: 18px 0;
    font-size: 13px;
    font-weight: 700;
    line-height: 1.6;
}

.seller-notice.success {
    background: #edf9e9;
    border-color: #cdebc3;
    color: #245b2d;
}

.seller-notice.error {
    background: #fff0ed;
    border-color: #ffd1c9;
    color: #8d2019;
}

.seller-empty {
    padding: 38px 20px;
    text-align: center;
    background: #fff;
    border: 1px dashed #d5ddec;
    border-radius: 20px;
}

.seller-empty-icon {
    font-size: 48px;
    margin-bottom: 12px;
}

.seller-empty h2 {
    margin: 0 0 10px;
    font-size: 21px;
}

.seller-empty p {
    max-width: 420px;
    margin: 0 auto 22px;
    color: var(--muted);
    font-size: 13px;
    line-height: 1.8;
}

.seller-footer {
    text-align: center;
    color: var(--muted);
    font-size: 11px;
    line-height: 1.8;
    margin-top: 35px;
}

.seller-footer a {
    color: var(--blue);
    font-weight: 800;
    text-decoration: none;
}

@media (max-width: 850px) {
    .seller-product-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 16px;
    }
}

@media (max-width: 560px) {
    .seller-page {
        width: calc(100% - 28px);
        margin-top: 16px;
    }

    .seller-topbar {
        margin-bottom: 20px;
    }

    .seller-topbar .logo {
        font-size: 21px;
    }

    .seller-topbar nav {
        gap: 12px;
        font-size: 11px;
    }

    .seller-hero {
        padding: 27px 17px;
        border-radius: 19px;
    }

    .seller-hero h1 {
        font-size: 30px;
    }

    .seller-section-heading {
        align-items: flex-start;
    }

    .seller-section-heading h2 {
        font-size: 19px;
    }

    .seller-section-heading > a.button {
        padding: 10px 12px;
        font-size: 11px;
    }

    .seller-product-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 12px;
    }

    .seller-product-card {
        border-radius: 14px;
    }

    .seller-product-image,
    .seller-product-placeholder {
        height: 135px;
    }

    .seller-product-placeholder {
        font-size: 36px;
    }

    .seller-product-body {
        padding: 12px;
    }

    .seller-product-body .category {
        font-size: 9px;
        padding: 5px 7px;
    }

    .seller-product-body h3 {
        font-size: 13px;
        margin-top: 10px;
    }

    .seller-product-price {
        font-size: 15px;
    }

    .seller-product-description {
        font-size: 11px;
    }

    .seller-product-date {
        font-size: 9px;
    }

    .seller-product-actions {
        flex-direction: column;
        gap: 7px;
    }

    .seller-product-actions .button,
    .seller-product-actions button {
        min-height: 35px;
        font-size: 10px;
        padding: 8px 5px;
    }

    .seller-empty {
        padding: 30px 15px;
    }
}

@media (max-width: 350px) {
    .seller-product-grid {
        grid-template-columns: 1fr;
    }

    .seller-product-image,
    .seller-product-placeholder {
        height: 190px;
    }

    .seller-product-actions {
        flex-direction: row;
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

        <div class="seller-hero-art" aria-hidden="true"></div>
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
                <div class="seller-empty-icon" aria-hidden="true"></div>
                <h2>Lapakmu masih kosong, cuy!</h2>
                <p>
                    Saatnya pamerin makanan atau produk andalanmu.
                    Tambahkan produk pertama dan mulai jualan di kampus.
                </p>
                <a class="button" href="add_product.php">
                    Tambah Produk Pertama
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
        <p>CampusMarket · Jajan dekat, teman makin erat </p>
        <a href="index.php">Kembali ke katalog</a>
    </footer>

</main>
</body>
</html>
