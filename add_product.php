
<?php
require_once __DIR__ . '/product_helpers.php';
requireLogin();

$error = '';
$name = '';
$description = '';
$price = '';
$categoryId = '';

$categories = $pdo->query(
    'SELECT id, name FROM categories ORDER BY name'
)->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken();

    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = trim($_POST['price'] ?? '');
    $categoryId = $_POST['category_id'] ?? '';

    if ($name === '' || mb_strlen($name) > 180) {
        $error = 'Nama produk wajib diisi (maksimal 180 karakter).';
    } elseif (!is_numeric($price) || (float) $price <= 0 ||
              (float) $price > 999999999) {
        $error = 'Harga harus lebih dari 0 dan tidak terlalu besar.';
    } elseif (mb_strlen($description) > 5000) {
        $error = 'Deskripsi maksimal 5000 karakter.';
    } else {
        $categoryValue = null;

        if ($categoryId !== '') {
            $stmt = $pdo->prepare('SELECT id FROM categories WHERE id = ?');
            $stmt->execute([$categoryId]);
            $category = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$category) {
                $error = 'Kategori yang dipilih tidak valid.';
            } else {
                $categoryValue = (int) $category['id'];
            }
        }

        if ($error === '') {
            $imagePath = null;

            try {
                $imagePath = uploadProductImage($_FILES['image'] ?? []);

                $stmt = $pdo->prepare(
                    'INSERT INTO products
                        (user_id, category_id, name, description,
                         price, condition_type, image_url)
                     VALUES (?, ?, ?, ?, ?, ?, ?)'
                );

                $stmt->execute([
                    (int) $_SESSION['user_id'],
                    $categoryValue,
                    $name,
                    $description,
                    $price,
                    'Baru',
                    $imagePath
                ]);

                header('Location: my_products.php?added=1');
                exit;
            } catch (Throwable $e) {
                if ($imagePath !== null) {
                    deleteProductImage($imagePath);
                }

                error_log($e->getMessage());
                $error = $e instanceof RuntimeException
                    ? $e->getMessage()
                    : 'Produk gagal disimpan. Periksa struktur tabel products.';
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
    <title>Tambah Produk - CampusMarket</title>
    <link rel="stylesheet" href="assets/style.css">
    ```html
<style>
    :root {
        --blue: #1769aa;
        --blue-dark: #104c80;
        --orange: #ff773d;
        --bg: #f3f7fc;
        --white: #ffffff;
        --text: #1d2939;
        --muted: #667085;
        --border: #dce5f0;
    }

    * {
        box-sizing: border-box;
    }

    body {
        margin: 0;
        background: var(--bg);
        color: var(--text);
        font-family: Arial, sans-serif;
    }

    .add-product-page {
        width: min(1080px, calc(100% - 32px));
        margin: 24px auto 60px;
    }

    .add-product-nav {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 14px;
        margin-bottom: 28px;
    }

    .add-product-nav .logo {
        color: var(--blue-dark);
        font-size: 24px;
        font-weight: 900;
        text-decoration: none;
    }

    .add-product-nav .logo span {
        color: var(--orange);
    }

    .add-product-nav-links {
        display: flex;
        flex-wrap: wrap;
        gap: 18px;
        font-weight: 700;
    }

    .add-product-nav-links a {
        color: var(--blue-dark);
        text-decoration: none;
        transition: color .2s;
    }

    .add-product-nav-links a:hover {
        color: var(--orange);
    }

    .add-product-layout {
        display: grid;
        grid-template-columns: minmax(0, 1.5fr) minmax(220px, .8fr);
        gap: 28px;
        align-items: start;
    }

    .add-product-card {
        min-width: 0;
        padding: clamp(20px, 4vw, 34px);
        background: var(--white);
        border: 1px solid var(--border);
        border-radius: 22px;
        box-shadow: 0 8px 28px rgba(26, 65, 110, .07);
    }

    .add-product-heading {
        margin-bottom: 24px;
    }

    .eyebrow {
        display: inline-block;
        padding: 8px 12px;
        background: #e5f1ff;
        color: var(--blue);
        border-radius: 8px;
        font-size: 11px;
        font-weight: 900;
        letter-spacing: 1px;
    }

    .add-product-heading h1 {
        margin: 16px 0 10px;
        color: var(--blue-dark);
        font-size: clamp(28px, 4vw, 40px);
        line-height: 1.15;
        letter-spacing: -1px;
    }

    .add-product-heading p {
        margin: 0;
        color: var(--muted);
        line-height: 1.7;
    }

    .add-product-form label {
        display: block;
        margin: 19px 0 8px;
        color: var(--text);
        font-size: 14px;
        font-weight: 800;
    }

    .add-product-form input:not([type="hidden"]):not([type="file"]),
    .add-product-form textarea,
    .add-product-form select {
        display: block;
        width: 100%;
        padding: 13px 14px;
        border: 1px solid #d5dfeb;
        border-radius: 11px;
        background: #fbfdff;
        color: var(--text);
        font: inherit;
        outline: none;
        transition: border-color .2s, box-shadow .2s;
    }

    .add-product-form input:focus,
    .add-product-form textarea:focus,
    .add-product-form select:focus {
        border-color: var(--blue);
        box-shadow: 0 0 0 3px rgba(23, 105, 170, .12);
    }

    .add-product-form textarea {
        min-height: 125px;
        resize: vertical;
        line-height: 1.6;
    }

    .add-product-form input[type="file"] {
        display: block;
        width: 100%;
        padding: 12px;
        border: 1px dashed #a8bfd8;
        border-radius: 11px;
        background: #f6f9fd;
        font: inherit;
        font-size: 13px;
    }

    .add-product-help {
        display: block;
        margin-top: 8px;
        color: var(--muted);
        font-size: 12px;
        line-height: 1.6;
    }

    .add-product-error {
        margin-bottom: 20px;
        padding: 14px 16px;
        background: #fff0ef;
        color: #a12d26;
        border: 1px solid #ffc9c4;
        border-radius: 11px;
        line-height: 1.6;
    }

    .add-product-submit {
        display: block;
        width: 100%;
        margin-top: 24px;
        padding: 14px 18px;
        background: var(--orange);
        color: #fff;
        border: none;
        border-radius: 11px;
        font-size: 15px;
        font-weight: 800;
        cursor: pointer;
        transition: background .2s, transform .2s;
    }

    .add-product-submit:hover {
        background: #ef632c;
        transform: translateY(-1px);
    }

    .add-product-aside {
        display: grid;
        gap: 18px;
    }

    .add-product-promo {
        padding: 25px;
        background: var(--blue);
        color: #fff;
        border-radius: 20px;
        box-shadow: 0 8px 20px rgba(23, 105, 170, .15);
    }

    .add-product-promo-icon {
        margin-bottom: 14px;
        font-size: 52px;
    }

    .add-product-promo h2 {
        margin: 0 0 12px;
        font-size: 23px;
        line-height: 1.3;
    }

    .add-product-promo p {
        margin: 0;
        color: #e8f3ff;
        line-height: 1.8;
        font-size: 14px;
    }

    .add-product-tips {
        padding: 22px;
        background: var(--white);
        border: 1px solid var(--border);
        border-radius: 18px;
    }

    .add-product-tips h3 {
        margin: 0 0 14px;
        color: var(--blue-dark);
    }

    .add-product-tips ul {
        margin: 0;
        padding-left: 20px;
    }

    .add-product-tips li {
        margin: 10px 0;
        color: var(--muted);
        font-size: 14px;
        line-height: 1.7;
    }

    .add-product-tips li::marker {
        color: var(--orange);
    }

    .add-product-footer {
        margin-top: 34px;
        color: var(--muted);
        font-size: 13px;
        text-align: center;
    }

    @media (max-width: 760px) {
        .add-product-layout {
            grid-template-columns: 1fr;
        }

        .add-product-aside {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 520px) {
        .add-product-page {
            width: calc(100% - 24px);
            margin-top: 16px;
        }

        .add-product-nav {
            margin-bottom: 20px;
        }

        .add-product-nav-links {
            gap: 12px;
            font-size: 13px;
        }

        .add-product-card {
            padding: 20px 16px;
            border-radius: 16px;
        }

        .add-product-aside {
            grid-template-columns: 1fr;
        }

        .add-product-form input[type="file"] {
            font-size: 12px;
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
<main class="add-product-page">

    <header class="add-product-nav">
        <a href="index.php" class="logo">
            Campus<span>Market</span>
        </a>

        <nav class="add-product-nav-links" aria-label="Navigasi">
            <a href="index.php">← Beranda</a>
            <a href="my_products.php">Jualan Saya</a>
            <a href="profile.php">Profil</a>
        </nav>
    </header>

    <div class="add-product-layout">

        <section class="add-product-card">
            <div class="add-product-heading">
                <span class="eyebrow">✦ SAATNYA MULAI JUALAN</span>
                <h1>Tambah produkmu! </h1>
                <p>
                    Ceritakan produk andalanmu supaya teman-teman kampus
                    tahu apa yang kamu jual.
                </p>
            </div>

            <?php if ($error !== ''): ?>
                <div class="add-product-error" role="alert">
                    <strong>Yah, ada yang perlu diperbaiki.</strong><br>
                    <?= h($error) ?>
                </div>
            <?php endif; ?>

            <form
                class="add-product-form"
                method="POST"
                enctype="multipart/form-data"
            >
                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= h(getCsrfToken()) ?>"
                >

                <label for="name">Nama produk *</label>
                <input
                    id="name"
                    name="name"
                    type="text"
                    maxlength="180"
                    required
                    value="<?= h($name) ?>"
                    placeholder="Contoh: Dimsum ayam isi 5"
                >
                <small class="add-product-help">
                    Gunakan nama yang jelas dan gampang diingat.
                </small>

                <label for="category_id">Kategori produk</label>
                <select id="category_id" name="category_id">
                    <option value="">-- Pilih kategori (opsional) --</option>
                    <?php foreach ($categories as $category): ?>
                        <option
                            value="<?= (int) $category['id'] ?>"
                            <?= (string) $categoryId === (string) $category['id']
                                ? 'selected'
                                : '' ?>
                        >
                            <?= h($category['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <label for="price">Harga produk (Rp) *</label>
                <input
                    id="price"
                    name="price"
                    type="number"
                    min="1"
                    max="999999999"
                    step="1"
                    required
                    value="<?= h($price) ?>"
                    placeholder="Contoh: 15000"
                >
                <small class="add-product-help">
                    Masukkan angka saja, tanpa titik atau simbol Rp.
                </small>

                <label for="description">Deskripsi produk</label>
                <textarea
                    id="description"
                    name="description"
                    maxlength="5000"
                    placeholder="Contoh: Dimsum ayam homemade, isi 5 pcs, tersedia saus mentai."
                ><?= h($description) ?></textarea>
                <small class="add-product-help">
                    Maksimal 5.000 karakter. Kamu bisa menjelaskan rasa,
                    ukuran, varian, atau jumlah per porsi.
                </small>

                <label for="image">Foto produk (opsional)</label>
                <input
                    id="image"
                    name="image"
                    type="file"
                    accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                >
                <small class="add-product-help">
                    Format JPG, PNG, atau WebP. Ukuran maksimal 3 MB.
                    Foto yang jelas bisa bikin produkmu lebih menarik!
                </small>

                <button class="button add-product-submit" type="submit">
                    🚀 Simpan Produk
                </button>
            </form>
        </section>

        <aside class="add-product-aside">
            <section class="add-product-promo">
                <div class="add-product-promo-icon" aria-hidden="true"></div>
                <h2>Lapak kecil, mimpi besar!</h2>
                <p>
                    Mulai kenalkan produkmu ke komunitas kampus.
                    Tulis detail yang jujur agar calon pembeli lebih yakin.
                </p>
            </section>

            <section class="add-product-tips">
                <h3>✨ Tips produk menarik</h3>
                <ul>
                    <li>Pakai nama produk yang spesifik.</li>
                    <li>Tambahkan foto asli yang terang dan jelas.</li>
                    <li>Jelaskan isi, varian, atau ukuran produk.</li>
                    <li>Pastikan harga sesuai dengan produk yang dijual.</li>
                </ul>
            </section>
        </aside>

    </div>

    <footer class="add-product-footer">
        <p>CampusMarket · Jajan dekat, teman makin erat </p>
    </footer>

</main>
</body>
</html>
