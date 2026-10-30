
<?php
require_once __DIR__ . '/product_helpers.php';
requireLogin();

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    http_response_code(400);
    exit('ID produk tidak valid.');
}

$stmt = $pdo->prepare(
    'SELECT * FROM products WHERE id = ? AND user_id = ?'
);
$stmt->execute([$id, (int) $_SESSION['user_id']]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    http_response_code(404);
    exit('Produk tidak ditemukan atau bukan milik akun kamu.');
}

$categories = $pdo->query(
    'SELECT id, name FROM categories ORDER BY name'
)->fetchAll(PDO::FETCH_ASSOC);

$error = '';

$name = $product['name'];
$description = $product['description'] ?? '';
$price = $product['price'];
$categoryId = $product['category_id'] ?? '';

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
    }

    $categoryValue = null;

    if ($error === '' && $categoryId !== '') {
        $check = $pdo->prepare('SELECT id FROM categories WHERE id = ?');
        $check->execute([$categoryId]);

        if (!$check->fetch()) {
            $error = 'Kategori tidak valid.';
        } else {
            $categoryValue = (int) $categoryId;
        }
    }

    if ($error === '') {
        $newImage = null;

        try {
            $newImage = uploadProductImage($_FILES['image'] ?? []);
            $imagePath = $newImage ?? $product['image_url'];

            $stmt = $pdo->prepare(
                'UPDATE products
                 SET name = ?, description = ?, price = ?,
                     category_id = ?, image_url = ?
                 WHERE id = ? AND user_id = ?'
            );

            $stmt->execute([
                $name,
                $description,
                $price,
                $categoryValue,
                $imagePath,
                $id,
                (int) $_SESSION['user_id']
            ]);

            if ($newImage !== null) {
                deleteProductImage($product['image_url']);
            }

            header('Location: my_products.php?updated=1');
            exit;
        } catch (Throwable $e) {
            if ($newImage !== null) {
                deleteProductImage($newImage);
            }

            error_log($e->getMessage());
            $error = $e instanceof RuntimeException
                ? $e->getMessage()
                : 'Produk gagal diperbarui. Periksa struktur tabel products.';
        }
    }

    $product['name'] = $name;
    $product['description'] = $description;
    $product['price'] = $price;
    $product['category_id'] = $categoryId;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Edit Produk - CampusMarket</title>
    <link rel="stylesheet" href="assets/style.css">

<style>
    .edit-page {
        width: min(1080px, calc(100% - 32px));
        margin: 24px auto 60px;
        color: #172554;
    }

    .edit-nav {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 14px;
        margin-bottom: 28px;
    }

    .edit-nav-links {
        display: flex;
        flex-wrap: wrap;
        gap: 16px;
        font-weight: 700;
    }

    .edit-nav-links a {
        color: #1e3a8a;
        text-decoration: none;
        transition: color .2s;
    }

    .edit-nav-links a:hover {
        color: #f97316;
    }

    .edit-layout {
        display: grid;
        grid-template-columns: minmax(0, 1.5fr) minmax(220px, .8fr);
        align-items: start;
        gap: 28px;
    }

    .edit-card {
        background: #fff;
        border: 1px solid #dbeafe;
        border-radius: 24px;
        padding: clamp(20px, 4vw, 34px);
        box-shadow: 0 12px 35px rgba(30, 64, 175, .09);
    }

    .edit-heading {
        margin-bottom: 24px;
    }

    .edit-heading h1 {
        color: #1e3a8a;
        font-size: clamp(28px, 4vw, 40px);
        line-height: 1.1;
        margin: 16px 0 10px;
        letter-spacing: -1px;
    }

    .edit-heading p {
        color: #64748b;
        line-height: 1.7;
        margin: 0;
    }

    .edit-form label {
        display: block;
        color: #1e3a8a;
        font-weight: 700;
        margin: 19px 0 8px;
    }

    .edit-form input:not([type="hidden"]):not([type="file"]),
    .edit-form textarea,
    .edit-form select {
        display: block;
        box-sizing: border-box;
        width: 100%;
        padding: 13px 14px;
        border: 1.5px solid #cbd5e1;
        border-radius: 12px;
        background: #f8fafc;
        color: #172554;
        font: inherit;
        outline: none;
        transition: border-color .18s, box-shadow .18s, background .18s;
    }

    .edit-form input:focus,
    .edit-form textarea:focus,
    .edit-form select:focus {
        border-color: #2563eb;
        background: #fff;
        box-shadow: 0 0 0 4px rgba(37, 99, 235, .1);
    }

    .edit-form textarea {
        min-height: 125px;
        resize: vertical;
        line-height: 1.6;
    }

    .edit-form input[type="file"] {
        display: block;
        box-sizing: border-box;
        width: 100%;
        padding: 12px;
        border: 1.5px dashed #93c5fd;
        border-radius: 12px;
        background: #eff6ff;
        color: #1e3a8a;
        font: inherit;
        font-size: 13px;
    }

    .edit-help {
        display: block;
        color: #64748b;
        font-size: 12px;
        line-height: 1.6;
        margin-top: 8px;
    }

    .edit-error {
        background: #fff1f2;
        color: #be123c;
        border: 1px solid #fecdd3;
        border-radius: 12px;
        padding: 13px 15px;
        margin-bottom: 20px;
        line-height: 1.6;
    }

    .current-photo {
        background: #eff6ff;
        border: 1px solid #dbeafe;
        border-radius: 16px;
        padding: 12px;
        margin-top: 10px;
    }

    .current-photo img {
        display: block;
        width: 100%;
        max-width: 300px;
        max-height: 260px;
        object-fit: contain;
        border-radius: 10px;
        margin: 0 auto;
    }

    .no-current-photo {
        min-height: 120px;
        display: grid;
        place-items: center;
        text-align: center;
        font-size: 42px;
        color: #2563eb;
    }

    .edit-submit {
        width: 100%;
        margin-top: 24px;
        padding: 15px 20px;
        border: none;
        border-radius: 12px;
        background: #2563eb;
        color: #fff;
        font-size: 15px;
        font-weight: 800;
        cursor: pointer;
        box-shadow: 0 5px 0 #1e40af;
        transition: transform .18s, background .18s, box-shadow .18s;
    }

    .edit-submit:hover {
        background: #1d4ed8;
        transform: translateY(-2px);
        box-shadow: 0 7px 0 #1e40af;
    }

    .edit-submit:active {
        transform: translateY(2px);
        box-shadow: 0 2px 0 #1e40af;
    }

    .edit-aside {
        display: grid;
        gap: 18px;
    }

    .edit-promo {
        background: #dbeafe;
        border: 1px solid #bfdbfe;
        border-radius: 22px;
        padding: 24px;
        box-shadow: 0 8px 24px rgba(37, 99, 235, .08);
    }

    .edit-promo-icon {
        font-size: 54px;
        margin-bottom: 12px;
    }

    .edit-promo h2 {
        color: #1e3a8a;
        font-size: 23px;
        line-height: 1.3;
        margin: 0 0 10px;
    }

    .edit-promo p {
        color: #475569;
        line-height: 1.7;
        margin: 0;
    }

    .edit-tips {
        background: #fff7ed;
        border: 1px solid #fed7aa;
        border-radius: 22px;
        padding: 22px;
    }

    .edit-tips h3 {
        color: #9a3412;
        margin: 0 0 12px;
    }

    .edit-tips p {
        color: #7c2d12;
        line-height: 1.7;
        margin: 0;
    }

    .edit-footer {
        text-align: center;
        margin-top: 34px;
        color: #64748b;
        font-size: 13px;
    }

    .edit-footer a {
        color: #2563eb;
        font-weight: 700;
        text-decoration: none;
    }

    .edit-footer a:hover {
        color: #f97316;
    }

    @media (max-width: 760px) {
        .edit-layout {
            grid-template-columns: 1fr;
        }

        .edit-aside {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 520px) {
        .edit-page {
            width: calc(100% - 28px);
            margin-top: 16px;
        }

        .edit-nav {
            margin-bottom: 20px;
        }

        .edit-nav-links {
            gap: 12px;
            font-size: 13px;
        }

        .edit-card {
            border-radius: 18px;
            padding: 20px 16px;
        }

        .edit-aside {
            grid-template-columns: 1fr;
        }

        .edit-form input[type="file"] {
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
<main class="edit-page">

    <header class="edit-nav">
        <a href="index.php" class="logo">
            Campus<span>Market</span>
        </a>

        <nav class="edit-nav-links" aria-label="Navigasi">
            <a href="index.php">← Beranda</a>
            <a href="my_products.php">Jualan Saya</a>
            <a href="profile.php">Profil</a>
        </nav>
    </header>

    <div class="edit-layout">

        <section class="edit-card">
            <div class="edit-heading">
                <span class="eyebrow">✦ PERBARUI LAPAKMU</span>
                <h1>Edit produk ✏️</h1>
                <p>
                    Ada yang berubah dari produkmu? Perbarui informasinya
                    supaya calon pembeli mendapatkan detail terbaru.
                </p>
            </div>

            <?php if ($error !== ''): ?>
                <div class="edit-error" role="alert">
                    <strong>Yah, ada yang perlu diperbaiki.</strong><br>
                    <?= h($error) ?>
                </div>
            <?php endif; ?>

            <form class="edit-form" method="POST" enctype="multipart/form-data">
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
                    value="<?= h($product['name']) ?>"
                    placeholder="Contoh: Dimsum ayam isi 5"
                >

                <label for="category_id">Kategori produk</label>
                <select id="category_id" name="category_id">
                    <option value="">-- Tanpa kategori --</option>
                    <?php foreach ($categories as $category): ?>
                        <option
                            value="<?= (int) $category['id'] ?>"
                            <?= (string) ($product['category_id'] ?? '') === (string) $category['id']
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
                    value="<?= h($product['price']) ?>"
                    placeholder="Contoh: 15000"
                >
                <small class="edit-help">
                    Masukkan angka saja, tanpa titik atau simbol Rp.
                </small>

                <label for="description">Deskripsi produk</label>
                <textarea
                    id="description"
                    name="description"
                    maxlength="5000"
                    placeholder="Jelaskan rasa, varian, ukuran, atau jumlah per porsi."
                ><?= h($product['description'] ?? '') ?></textarea>

                <label>Foto produk saat ini</label>
                <div class="current-photo">
                    <?php if (!empty($product['image_url'])): ?>
                        <img
                            src="<?= h($product['image_url']) ?>"
                            alt="Foto <?= h($product['name']) ?>"
                        >
                    <?php else: ?>
                        <div class="no-current-photo">
                            <span>
                                🍱
                                <small style="display:block;font-size:13px">
                                    Belum ada foto produk
                                </small>
                            </span>
                        </div>
                    <?php endif; ?>
                </div>

                <label for="image">Ganti foto (opsional)</label>
                <input
                    id="image"
                    name="image"
                    type="file"
                    accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                >
                <small class="edit-help">
                    JPG, PNG, atau WebP; maksimal 3 MB.
                    Kosongkan pilihan ini jika ingin mempertahankan foto saat ini.
                </small>

                <button class="button edit-submit" type="submit">
                    💾 Simpan Perubahan
                </button>
            </form>
        </section>

        <aside class="edit-aside">
            <section class="edit-promo">
                <div class="edit-promo-icon" aria-hidden="true">🎨</div>
                <h2>Bikin produk makin menarik!</h2>
                <p>
                    Nama yang jelas, harga yang tepat, dan foto yang bagus
                    membantu teman kampus memahami produkmu.
                </p>
            </section>

            <section class="edit-tips">
                <h3>💡 Sebelum menyimpan</h3>
                <p>
                    Periksa kembali nama, kategori, harga, dan deskripsi.
                    Kalau foto tidak diganti, foto lama akan tetap digunakan.
                </p>
            </section>
        </aside>

    </div>

    <footer class="edit-footer">
        <p>CampusMarket · Jajan dekat, teman makin erat 💛</p>
        <a href="my_products.php">Kembali ke Jualan Saya</a>
    </footer>

</main>
</body>
</html>
