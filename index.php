
<?php
require_once __DIR__ . '/product_helpers.php';

$isLoggedIn = isset($_SESSION['user_id']);
$currentUserId = $isLoggedIn ? (int) $_SESSION['user_id'] : 0;

$sql = "
    SELECT
        p.id,
        p.user_id,
        p.name,
        p.description,
        p.price,
        p.condition_type,
        p.image_url,
        c.name AS category_name
    FROM products p
    LEFT JOIN categories c ON c.id = p.category_id
    ORDER BY p.created_at DESC
";

$stmt = $pdo->query($sql);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

$categoryStmt = $pdo->query(
    "SELECT id, name FROM categories ORDER BY name ASC"
);
$categories = $categoryStmt->fetchAll(PDO::FETCH_ASSOC);

function e($value): string
{
    return htmlspecialchars(
        (string) ($value ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );
}

function rupiah($price): string
{
    return 'Rp ' . number_format((float) $price, 0, ',', '.');
}

$csrfToken = getCsrfToken();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>CampusMarket — Jajan Dekat Kampus</title>
    <meta
        name="description"
        content="Temukan makanan, camilan, dan kebutuhan mahasiswa di CampusMarket."
    >

    <link rel="stylesheet" href="assets/style.css">

    <style>
        .order-button {
            display: block;
            width: 100%;
            margin-top: 12px;
            padding: 12px 14px;
            border: 2px solid var(--ink, #242424);
            border-radius: 12px;
            background: var(--orange, #ff6b35);
            color: #fff;
            font: inherit;
            font-weight: 900;
            cursor: pointer;
            box-shadow: 0 3px 0 var(--ink, #242424);
            transition: transform .15s, box-shadow .15s;
        }

        .order-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 0 var(--ink, #242424);
        }

        .order-button:focus-visible,
        .order-login:focus-visible {
            outline: 3px solid #242424;
            outline-offset: 3px;
        }

        .order-login {
            display: block;
            margin-top: 12px;
            padding: 12px;
            text-align: center;
            border: 2px solid var(--ink, #242424);
            border-radius: 12px;
            background: var(--yellow, #ffe66d);
            color: var(--ink, #242424);
            font-weight: 900;
        }

        .order-unavailable {
            display: block;
            margin-top: 12px;
            padding: 10px;
            border-radius: 10px;
            background: #f0eee9;
            color: #686258;
            text-align: center;
            font-size: 12px;
            font-weight: 800;
        }

        .product-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
            display: block;
        }

        .product-card[hidden] {
            display: none !important;
        }
    </style>
</head>
<body>

<header class="navbar">
    <a href="index.php" class="logo" aria-label="CampusMarket Beranda">
        Campus<span>Market</span>
    </a>

    <nav aria-label="Navigasi utama">
        <a href="index.php">Beranda</a>
        <a href="#katalog">Jelajahi</a>

        <?php if ($isLoggedIn): ?>
            <a href="cart.php">🛒 Keranjang</a>
            <a href="my_products.php">Jualan Saya</a>
            <a href="profile.php">Profil Saya</a>
            <a href="seller_dashboard.php">Dashboard Penjual</a>
            <a href="logout.php">Logout</a>
        <?php else: ?>
            <a href="login.php">Login</a>
            <a href="register.php">Daftar</a>
        <?php endif; ?>

        <a href="#tentang">Tentang</a>
    </nav>

    <a
        class="button button-small"
        href="<?= $isLoggedIn ? 'add_product.php' : 'login.php' ?>"
    >
        + Mulai Jualan
    </a>
</header>

<main>
    <section class="hero">
        <div class="hero-inner">
            <div class="hero-copy">
                <p class="eyebrow">✦ MARKETPLACE ANAK KAMPUS</p>

                <h1>
                    Jajan enak,<br>
                    <span>dekat kampus.</span>
                </h1>

                <p class="hero-description">
                    Temukan produk dari teman-teman kampusmu.
                    Dukung usaha mahasiswa, temukan kebutuhanmu,
                    dan bikin aktivitas kuliah makin seru!
                </p>

                <div class="hero-actions">
                    <a href="#katalog" class="button">
                        Jelajahi Produk <span aria-hidden="true">↗</span>
                    </a>

                    <a
                        href="<?= $isLoggedIn ? 'add_product.php' : 'register.php' ?>"
                        class="button button-secondary"
                    >
                        Aku Mau Jualan ✦
                    </a>
                </div>

                <div class="hero-stats">
                    <div>
                        <strong><?= count($products) ?></strong>
                        <span>Produk tersedia</span>
                    </div>
                    <div>
                        <strong><?= count($categories) ?></strong>
                        <span>Kategori produk</span>
                    </div>
                    <div>
                        <strong>Bareng</strong>
                        <span>Komunitas kampus</span>
                    </div>
                </div>
            </div>

            <div class="hero-art" aria-label="Ilustrasi makanan dan camilan">
                <div class="hero-blob"></div>

                <div class="hero-sticker">
                    DARI KAMPUS<br>
                    DENGAN ♥
                </div>

                <div class="food-grid">
                    <div class="food-tile tile-orange">
                        <span>🥟</span>
                        <strong>DIMSUM</strong>
                    </div>

                    <div class="food-tile tile-pink">
                        <span>🍢</span>
                        <strong>SIOMAY</strong>
                    </div>

                    <div class="food-tile tile-green">
                        <span>🍡</span>
                        <strong>CAMILAN</strong>
                    </div>

                    <div class="food-tile tile-blue">
                        <span>🧋</span>
                        <strong>MINUMAN</strong>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="catalog" id="katalog">
        <div class="section-heading">
            <div>
                <p class="eyebrow">TEMUKAN FAVORITMU</p>
                <h2>Cari apa hari ini? 😋</h2>
                <p class="muted">
                    Cari produk dan temukan barang yang cocok buat kamu.
                </p>
            </div>

            <span class="count">
                <?= count($products) ?> produk
            </span>
        </div>

        <form class="searchbar" id="searchForm" role="search">
            <span class="search-icon" aria-hidden="true">⌕</span>

            <input
                type="search"
                id="productSearch"
                placeholder="Cari makanan, camilan, atau barang..."
                aria-label="Cari produk"
                autocomplete="off"
            >

            <button class="button" type="submit">Cari</button>
        </form>

        <div class="category-chips" id="categoryChips">
            <button
                class="chip active"
                type="button"
                data-category="all"
                aria-pressed="true"
            >
                ✨ Semua
            </button>

            <?php foreach ($categories as $category): ?>
                <button
                    class="chip"
                    type="button"
                    data-category="<?= e($category['name']) ?>"
                    aria-pressed="false"
                >
                    <?= e($category['name']) ?>
                </button>
            <?php endforeach; ?>
        </div>

        <?php if (count($products) > 0): ?>
            <div class="product-grid" id="productGrid">
                <?php foreach ($products as $product): ?>
                    <?php
                    $categoryName = $product['category_name'] ?: 'Lainnya';

                    $searchText = strtolower(
                        $product['name'] . ' ' .
                        $product['description'] . ' ' .
                        $categoryName
                    );

                    $sellerId = (int) ($product['user_id'] ?? 0);
                    $isOwnProduct = $isLoggedIn
                        && $sellerId === $currentUserId
                        && $sellerId > 0;

                    $hasSeller = $sellerId > 0;
                    ?>

                    <article
                        class="product-card"
                        data-category="<?= e($categoryName) ?>"
                        data-search="<?= e($searchText) ?>"
                    >
                        <?php if (!empty($product['image_url'])): ?>
                            <img
                                class="product-image"
                                src="<?= e($product['image_url']) ?>"
                                alt="<?= e($product['name']) ?>"
                                loading="lazy"
                            >
                        <?php else: ?>
                            <div class="product-placeholder">
                                <span>✦ CAMPUS PICK ✦</span>
                                <strong><?= e($categoryName) ?></strong>
                                <span aria-hidden="true">🛍️</span>
                            </div>
                        <?php endif; ?>

                        <div class="product-content">
                            <div class="product-meta">
                                <span class="category">
                                    <?= e($categoryName) ?>
                                </span>

                                <?php if (!empty($product['condition_type'])): ?>
                                    <span class="condition">
                                        <?= e($product['condition_type']) ?>
                                    </span>
                                <?php endif; ?>
                            </div>

                            <h3><?= e($product['name']) ?></h3>

                            <p class="description">
                                <?= e(
                                    $product['description']
                                    ?: 'Belum ada deskripsi produk.'
                                ) ?>
                            </p>

                            <div class="catalog-product-foot">
                                <p class="price">
                                    <?= e(rupiah($product['price'])) ?>
                                </p>

                                <span class="product-sparkle" aria-hidden="true">
                                    ✦
                                </span>
                            </div>

                            <?php if (!$isLoggedIn): ?>
                                <a class="order-login" href="login.php">
                                    🔐 Login untuk Memesan
                                </a>

                            <?php elseif (!$hasSeller): ?>
                                <span class="order-unavailable">
                                    Penjual belum terdaftar
                                </span>

                            <?php elseif ($isOwnProduct): ?>
                                <a class="order-login" href="edit_product.php?id=<?= (int) $product['id'] ?>">
                                    ✏️ Produk Kamu
                                </a>

                            <?php else: ?>
                                <form action="add_to_cart.php" method="POST">
                                    <input
                                        type="hidden"
                                        name="csrf_token"
                                        value="<?= e($csrfToken) ?>"
                                    >

                                    <input
                                        type="hidden"
                                        name="product_id"
                                        value="<?= (int) $product['id'] ?>"
                                    >

                                    <button
                                        type="submit"
                                        class="order-button"
                                    >
                                        🛒 Pesan Sekarang
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>

            <div class="empty-state" id="noSearchResults" hidden>
                <div class="empty-icon">🔎</div>
                <h3>Produknya belum ketemu!</h3>
                <p>
                    Coba kata kunci lain atau pilih kategori yang berbeda, ya.
                </p>

                <button
                    class="button button-secondary"
                    type="button"
                    id="resetSearch"
                >
                    Tampilkan Semua Produk
                </button>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <div class="empty-icon">🛍️</div>
                <h3>Belum ada produk nih, cuy!</h3>
                <p>
                    Jadilah yang pertama meramaikan CampusMarket
                    dengan menambahkan produkmu.
                </p>

                <a
                    class="button"
                    href="<?= $isLoggedIn ? 'add_product.php' : 'register.php' ?>"
                >
                    + Tambah Produk
                </a>
            </div>
        <?php endif; ?>
    </section>

    <section class="about" id="tentang">
        <p class="eyebrow">DARI MAHASISWA, UNTUK MAHASISWA</p>
        <h2>Usaha kecil, semangat besar. 💛</h2>
        <p>
            CampusMarket jadi tempat mahasiswa mengenalkan produknya
            dan menemukan kebutuhan dari komunitas kampus.
            Yuk, dukung usaha teman sendiri!
        </p>

        <a
            class="button"
            href="<?= $isLoggedIn ? 'add_product.php' : 'register.php' ?>"
        >
            Gabung CampusMarket ↗
        </a>
    </section>
</main>

<footer>
    <a href="index.php" class="logo">
        Campus<span>Market</span>
    </a>

    <p>
        Marketplace mahasiswa · Jajan dekat, teman makin erat.
    </p>
</footer>

<script>
(() => {
    const searchInput = document.getElementById('productSearch');
    const searchForm = document.getElementById('searchForm');
    const chips = document.getElementById('categoryChips');
    const cards = Array.from(document.querySelectorAll('.product-card'));
    const emptyState = document.getElementById('noSearchResults');
    const resetButton = document.getElementById('resetSearch');

    let activeCategory = 'all';

    function filterProducts() {
        const keyword = (searchInput?.value || '')
            .trim()
            .toLocaleLowerCase('id');

        let visibleCount = 0;

        cards.forEach((card) => {
            const searchText = (
                card.dataset.search || ''
            ).toLocaleLowerCase('id');

            const category = card.dataset.category || '';

            const matchesKeyword = searchText.includes(keyword);

            const matchesCategory =
                activeCategory === 'all' ||
                category === activeCategory;

            const visible = matchesKeyword && matchesCategory;

            card.hidden = !visible;

            if (visible) {
                visibleCount++;
            }
        });

        if (emptyState) {
            emptyState.hidden = visibleCount !== 0;
        }
    }

    searchForm?.addEventListener('submit', (event) => {
        event.preventDefault();
        filterProducts();
    });

    searchInput?.addEventListener('input', filterProducts);

    chips?.querySelectorAll('[data-category]').forEach((chip) => {
        chip.addEventListener('click', () => {
            activeCategory = chip.dataset.category;

            chips.querySelectorAll('[data-category]').forEach((item) => {
                const active = item === chip;

                item.classList.toggle('active', active);
                item.setAttribute('aria-pressed', String(active));
            });

            filterProducts();
        });
    });

    resetButton?.addEventListener('click', () => {
        if (searchInput) {
            searchInput.value = '';
        }

        activeCategory = 'all';

        chips?.querySelectorAll('[data-category]').forEach((item) => {
            const active = item.dataset.category === 'all';

            item.classList.toggle('active', active);
            item.setAttribute('aria-pressed', String(active));
        });

        filterProducts();
    });
})();
</script>

</body>
</html>
