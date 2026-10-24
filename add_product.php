
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

    <style>
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

        .add-product-nav-links {
            display: flex;
            flex-wrap: wrap;
            gap: 16px;
            font-weight: 700;
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
            background: #fff;
            border: 2px solid var(--ink);
            border-radius: 24px;
            padding: clamp(20px, 4vw, 34px);
            box-shadow: 5px 5px 0 var(--ink);
        }

        .add-product-heading {
            margin-bottom: 24px;
        }

        .add-product-heading h1 {
            font-size: clamp(28px, 4vw, 40px);
            line-height: 1.1;
            margin: 16px 0 10px;
        }

        .add-product-heading p {
            color: #6b6258;
            line-height: 1.7;
            margin: 0;
        }

        .add-product-form label {
            display: block;
            font-weight: 800;
            margin: 19px 0 8px;
        }

        .add-product-form input:not([type="hidden"]):not([type="file"]),
        .add-product-form textarea,
        .add-product-form select {
            display: block;
            box-sizing: border-box;
            width: 100%;
            padding: 13px 14px;
            border: 2px solid #e5ded4;
            border-radius: 12px;
            background: #fffdf9;
            color: var(--ink);
            font: inherit;
            outline: none;
            transition: border-color .18s, box-shadow .18s;
        }

        .add-product-form input:focus,
        .add-product-form textarea:focus,
        .add-product-form select:focus {
            border-color: var(--orange);
            box-shadow: 0 0 0 3px #ff6b3525;
        }

        .add-product-form textarea {
            min-height: 125px;
            resize: vertical;
            line-height: 1.6;
        }

        .add-product-form input[type="file"] {
            display: block;
            box-sizing: border-box;
            width: 100%;
            padding: 12px;
            border: 2px dashed #d8c9b7;
            border-radius: 12px;
            background: #fff9f0;
            font: inherit;
            font-size: 13px;
        }

        .add-product-help {
            display: block;
            color: #746b60;
            font-size: 12px;
            line-height: 1.6;
            margin-top: 8px;
        }

        .add-product-error {
            background: #ffe5e0;
            color: #922d22;
            border: 2px solid #922d22;
            border-radius: 12px;
            padding: 13px 15px;
            margin-bottom: 20px;
            line-height: 1.6;
        }

        .add-product-submit {
            width: 100%;
            margin-top: 24px;
            font-size: 15px;
        }

        .add-product-aside {
            display: grid;
            gap: 18px;
        }

        .add-product-promo {
            background: var(--yellow);
            border: 2px solid var(--ink);
            border-radius: 22px;
            padding: 24px;
            box-shadow: 4px 4px 0 var(--ink);
        }

        .add-product-promo-icon {
            font-size: 56px;
            margin-bottom: 12px;
        }

        .add-product-promo h2 {
            font-size: 23px;
            line-height: 1.2;
            margin: 0 0 10px;
        }

        .add-product-promo p {
            line-height: 1.7;
            margin: 0;
        }

        .add-product-tips {
            background: #e5f4db;
            border: 2px solid var(--ink);
            border-radius: 22px;
            padding: 22px;
        }

        .add-product-tips h3 {
            margin: 0 0 14px;
        }

        .add-product-tips ul {
            margin: 0;
            padding-left: 20px;
        }

        .add-product-tips li {
            line-height: 1.7;
            margin: 9px 0;
        }

        .add-product-footer {
            text-align: center;
            margin-top: 34px;
            color: #746b60;
            font-size: 13px;
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
                width: calc(100% - 28px);
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
                border-radius: 18px;
                padding: 20px 16px;
                box-shadow: 4px 4px 0 var(--ink);
            }

            .add-product-aside {
                grid-template-columns: 1fr;
            }

            .add-product-form input[type="file"] {
                font-size: 12px;
            }
        }
    </style>
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
                <h1>Tambah produkmu! 🍜</h1>
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
                <div class="add-product-promo-icon" aria-hidden="true">🧋</div>
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
        <p>CampusMarket · Jajan dekat, teman makin erat 💛</p>
    </footer>

</main>
</body>
</html>
