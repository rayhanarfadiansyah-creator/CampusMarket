
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
        c.name AS category_name,
        u.name AS seller_name
    FROM products p
    LEFT JOIN categories c ON c.id = p.category_id
    LEFT JOIN users u ON u.id = p.user_id
    ORDER BY p.created_at DESC
";

$stmt = $pdo->query($sql);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Ambil galeri foto untuk setiap produk; image_url lama tetap menjadi fallback.
$imageGalleryStmt = $pdo->prepare(
    'SELECT image_url FROM product_images WHERE product_id = ? ORDER BY sort_order, id'
);
foreach ($products as &$product) {
    $imageGalleryStmt->execute([(int) $product['id']]);
    $gallery = $imageGalleryStmt->fetchAll(PDO::FETCH_COLUMN);
    if (!$gallery && !empty($product['image_url'])) {
        $gallery = [$product['image_url']];
    }
    $product['gallery_images'] = $gallery;
}
unset($product);

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

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/style.css">

    
<style>
/* ===== CAMPUSMARKET - REDESAIN RESPONSIVE ===== */
:root {
    --cm-blue: #2457d6;
    --cm-blue-dark: #173b9b;
    --cm-orange: #ff7139;
    --cm-yellow: #ffe58a;
    --cm-bg: #f5f7fc;
    --cm-ink: #202844;
    --cm-muted: #727b91;
    --cm-border: #e7eaf2;
    --ink: #202844;
    --orange: #ff7139;
    --yellow: #ffe58a;
}

* {
    box-sizing: border-box;
}

html {
    scroll-behavior: smooth;
    scroll-padding-top: 90px;
}

body {
    margin: 0;
    background: var(--cm-bg);
    color: var(--cm-ink);
    font-family: "Plus Jakarta Sans", sans-serif;
}

a {
    text-decoration: none;
}

button,
input {
    font: inherit;
}

.navbar {
    position: sticky;
    top: 0;
    z-index: 100;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 22px;
    padding: 16px 6%;
    background: rgba(255,255,255,.96);
    border-bottom: 1px solid var(--cm-border);
    backdrop-filter: blur(12px);
}

.logo {
    flex-shrink: 0;
    color: var(--cm-blue);
    font-size: 23px;
    font-weight: 800;
    letter-spacing: -1px;
}

.logo span {
    color: var(--cm-orange);
}

.navbar nav {
    display: flex;
    align-items: center;
    justify-content: center;
    flex-wrap: wrap;
    gap: 20px;
}

.navbar nav a {
    color: var(--cm-ink);
    font-size: 13px;
    font-weight: 700;
}

.navbar nav a:hover {
    color: var(--cm-blue);
}

.button {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    min-height: 44px;
    padding: 12px 19px;
    border: 0;
    border-radius: 12px;
    background: var(--cm-blue);
    color: #fff;
    font-weight: 800;
    cursor: pointer;
    transition: .2s ease;
}

.button:hover {
    background: var(--cm-blue-dark);
    transform: translateY(-2px);
}

.button-small {
    min-height: 39px;
    padding: 10px 14px;
    font-size: 12px;
}

.button-secondary {
    background: #fff;
    color: var(--cm-blue);
    border: 1px solid #dce4fb;
}

.button-secondary:hover {
    background: #eef3ff;
    color: var(--cm-blue-dark);
}

.hero {
    overflow: hidden;
    padding: 64px 6%;
    background: linear-gradient(125deg, #eaf0ff 0%, #f7f8ff 58%, #fff0e6 100%);
}

.hero-inner {
    display: grid;
    grid-template-columns: 1.1fr .9fr;
    align-items: center;
    gap: 38px;
    max-width: 1240px;
    margin: auto;
}

.eyebrow {
    margin: 0 0 13px;
    color: var(--cm-blue);
    font-size: 11px;
    font-weight: 800;
    letter-spacing: 1.5px;
}

.hero h1 {
    margin: 0;
    color: var(--cm-ink);
    font-size: clamp(38px, 5vw, 66px);
    line-height: 1.12;
    letter-spacing: -2.8px;
}

.hero h1 span {
    color: var(--cm-blue);
}

.hero-description {
    max-width: 510px;
    margin: 20px 0 25px;
    color: var(--cm-muted);
    font-size: 15px;
    line-height: 1.9;
}

.hero-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
}

.hero-stats {
    display: flex;
    flex-wrap: wrap;
    gap: 28px;
    margin-top: 35px;
}

.hero-stats div {
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.hero-stats strong {
    color: var(--cm-blue);
    font-size: 20px;
    font-weight: 800;
}

.hero-stats span {
    color: var(--cm-muted);
    font-size: 11px;
}

.hero-art {
    position: relative;
    display: grid;
    place-items: center;
    min-height: 340px;
    isolation: isolate;
}

.hero-blob {
    position: absolute;
    z-index: -1;
    width: min(100%, 370px);
    aspect-ratio: 1;
    border-radius: 44% 56% 62% 38% / 42% 43% 57% 58%;
    background: #dce6ff;
    transform: rotate(-8deg);
}

.hero-sticker {
    position: absolute;
    z-index: 2;
    top: 15px;
    right: 4%;
    padding: 13px 16px;
    border-radius: 14px;
    background: var(--cm-orange);
    color: white;
    font-size: 11px;
    font-weight: 800;
    line-height: 1.6;
    text-align: center;
    transform: rotate(7deg);
    box-shadow: 0 8px 20px #ff713933;
}

.food-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(120px, 160px));
    gap: 14px;
    transform: rotate(-3deg);
}

.food-tile {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 9px;
    min-height: 145px;
    border: 3px solid white;
    border-radius: 23px;
    box-shadow: 0 12px 28px #263c7312;
}

.food-tile span {
    font-size: 47px;
}

.food-tile strong {
    font-size: 11px;
    font-weight: 800;
}

.tile-orange { background: #ffe5d6; }
.tile-pink { background: #ffe2ec; }
.tile-green { background: #e0f5e8; }
.tile-blue { background: #dfeaff; }

.catalog {
    max-width: 1320px;
    margin: auto;
    padding: 58px 6%;
}

.section-heading {
    display: flex;
    align-items: end;
    justify-content: space-between;
    gap: 20px;
    margin-bottom: 24px;
}

.section-heading h2,
.about h2 {
    margin: 0;
    font-size: clamp(25px, 3vw, 35px);
    letter-spacing: -1px;
}

.muted {
    color: var(--cm-muted);
    font-size: 13px;
    line-height: 1.7;
}

.count {
    flex-shrink: 0;
    padding: 9px 13px;
    border-radius: 10px;
    background: #e8edff;
    color: var(--cm-blue);
    font-size: 12px;
    font-weight: 800;
}

.searchbar {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 9px;
    border: 1px solid var(--cm-border);
    border-radius: 16px;
    background: #fff;
    box-shadow: 0 8px 30px #243d7510;
}

.search-icon {
    padding-left: 8px;
    color: var(--cm-blue);
    font-size: 25px;
}

.searchbar input {
    width: 100%;
    min-width: 0;
    padding: 10px 0;
    border: 0;
    outline: 0;
    background: transparent;
    color: var(--cm-ink);
    font-size: 14px;
}

.searchbar input::placeholder {
    color: #9ba2b3;
}

.category-chips {
    display: flex;
    gap: 10px;
    margin: 20px 0 26px;
    padding-bottom: 5px;
    overflow-x: auto;
    scrollbar-width: thin;
}

.chip {
    flex-shrink: 0;
    padding: 10px 16px;
    border: 1px solid var(--cm-border);
    border-radius: 999px;
    background: #fff;
    color: #626c83;
    font-size: 12px;
    font-weight: 700;
    cursor: pointer;
    transition: .2s;
}

.chip:hover,
.chip.active {
    border-color: var(--cm-blue);
    background: var(--cm-blue);
    color: white;
}

.product-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 20px;
}

.product-card {
    min-width: 0;
    overflow: hidden;
    border: 1px solid var(--cm-border);
    border-radius: 19px;
    background: #fff;
    box-shadow: 0 6px 22px #23386608;
    transition: transform .2s, box-shadow .2s;
}

.product-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 14px 32px #23386616;
}

.product-image,
.product-placeholder {
    display: block;
    width: 100%;
    height: 190px;
    object-fit: cover;
}

.product-gallery {
    position: relative;
    width: 100%;
    height: 190px;
    background: #f1f5f9;
}
.product-gallery .product-image { height: 100%; }
.gallery-arrow {
    position: absolute; top: 50%; transform: translateY(-50%);
    width: 30px; height: 30px; border: 0; border-radius: 50%;
    background: rgba(15, 23, 42, .72); color: #fff; font-size: 22px;
    line-height: 1; cursor: pointer;
}
.gallery-prev { left: 8px; }
.gallery-next { right: 8px; }
.gallery-count {
    position: absolute; right: 8px; bottom: 8px; padding: 3px 8px;
    border-radius: 20px; background: rgba(15, 23, 42, .72);
    color: #fff; font-size: 11px;
}

.product-placeholder {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 12px;
    background: linear-gradient(135deg, #eaf0ff, #fff0e6);
    color: var(--cm-blue);
    text-align: center;
}

.product-placeholder span {
    font-size: 11px;
    font-weight: 800;
}

.product-placeholder strong {
    padding: 0 10px;
    font-size: 17px;
}

.product-placeholder span:last-child {
    font-size: 28px;
}

.product-content {
    padding: 16px;
}

.product-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    margin-bottom: 10px;
}

.category,
.condition {
    padding: 5px 8px;
    border-radius: 7px;
    background: #edf1ff;
    color: var(--cm-blue);
    font-size: 10px;
    font-weight: 800;
}

.condition {
    background: #f3f4f7;
    color: #6d7484;
}

.product-content h3 {
    display: -webkit-box;
    overflow: hidden;
    min-height: 42px;
    margin: 0 0 8px;
    color: var(--cm-ink);
    font-size: 15px;
    line-height: 1.45;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
}

.seller-label {
    margin: 10px 0 0;
    color: var(--cm-muted);
    font-size: 11px;
}
.seller-label strong { color: var(--cm-ink); }

.description {
    display: -webkit-box;
    overflow: hidden;
    min-height: 36px;
    margin: 0;
    color: var(--cm-muted);
    font-size: 11px;
    line-height: 1.7;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
}

.catalog-product-foot {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    margin-top: 15px;
}

.price {
    margin: 0;
    color: var(--cm-orange);
    font-size: 16px;
    font-weight: 800;
    overflow-wrap: anywhere;
}

.product-sparkle {
    color: #f5b544;
}

.order-button {
    display: block;
    width: 100%;
    margin-top: 12px;
    padding: 11px 9px;
    border: 0;
    border-radius: 10px;
    background: var(--cm-blue);
    color: white;
    font-size: 12px;
    font-weight: 800;
    cursor: pointer;
    transition: .2s;
}

.order-button:hover {
    background: var(--cm-blue-dark);
}

.order-login,
.order-unavailable {
    display: block;
    margin-top: 12px;
    padding: 11px 8px;
    border-radius: 10px;
    background: #fff0e6;
    color: #bd4d20;
    font-size: 11px;
    font-weight: 800;
    text-align: center;
}

.order-unavailable {
    background: #f0f1f5;
    color: #686f80;
}

.product-card[hidden],
.empty-state[hidden] {
    display: none !important;
}

.empty-state {
    padding: 45px 20px;
    border: 1px dashed #cbd4ed;
    border-radius: 18px;
    background: #fff;
    text-align: center;
}

.empty-icon {
    font-size: 38px;
}

.empty-state h3 {
    margin-bottom: 8px;
}

.empty-state p {
    margin-bottom: 20px;
    color: var(--cm-muted);
    font-size: 13px;
}

.about {
    margin: 0 6% 55px;
    padding: 48px 25px;
    border-radius: 25px;
    background: linear-gradient(120deg, #e8eeff, #fff0e6);
    text-align: center;
}

.about h2 {
    margin-bottom: 14px;
}

.about > p:not(.eyebrow) {
    max-width: 600px;
    margin: 0 auto 23px;
    color: var(--cm-muted);
    font-size: 14px;
    line-height: 1.8;
}

footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 15px;
    padding: 25px 6%;
    border-top: 1px solid var(--cm-border);
    background: white;
}

footer p {
    color: var(--cm-muted);
    font-size: 12px;
}

.mobile-bottom-nav {
    display: none;
}

/* TABLET */
@media (max-width: 1000px) {
    .navbar {
        padding-right: 4%;
        padding-left: 4%;
    }

    .navbar nav {
        gap: 12px;
    }

    .navbar nav a {
        font-size: 11px;
    }

    .hero,
    .catalog {
        padding-right: 4%;
        padding-left: 4%;
    }

    .product-grid {
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 15px;
    }

    .product-image,
    .product-placeholder {
        height: 170px;
    }
}

/* MOBILE */
@media (max-width: 650px) {
    body {
        padding-bottom: 76px;
        background: #f7f8fc;
    }

    .navbar {
        position: relative;
        padding: 17px 5%;
        border-bottom: 1px solid var(--cm-border);
    }

    .navbar .logo {
        font-size: 22px;
    }

    .navbar nav {
        display: none;
    }

    .navbar > .button {
        min-height: 36px;
        padding: 9px 11px;
        border-radius: 10px;
        font-size: 11px;
    }

    .hero {
        padding: 30px 5% 24px;
    }

    .hero-inner {
        grid-template-columns: 1fr;
        gap: 12px;
    }

    .hero-copy {
        min-width: 0;
    }

    .hero .eyebrow {
        margin-bottom: 10px;
        font-size: 10px;
    }

    .hero h1 {
        font-size: clamp(35px, 10vw, 48px);
        letter-spacing: -1.8px;
    }

    .hero-description {
        margin: 13px 0 17px;
        font-size: 12px;
        line-height: 1.8;
    }

    .hero-actions {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 9px;
    }

    .hero-actions .button {
        min-height: 42px;
        padding: 10px 7px;
        font-size: 11px;
    }

    .hero-stats {
        justify-content: space-between;
        gap: 10px;
        margin-top: 22px;
    }

    .hero-stats strong {
        font-size: 16px;
    }

    .hero-stats span {
        font-size: 9px;
    }

    .hero-art {
        min-height: 220px;
        margin-top: 6px;
    }

    .hero-blob {
        width: 220px;
    }

    .food-grid {
        grid-template-columns: repeat(2, 112px);
        gap: 9px;
    }

    .food-tile {
        min-height: 95px;
        gap: 4px;
        border-width: 2px;
        border-radius: 17px;
    }

    .food-tile span {
        font-size: 31px;
    }

    .food-tile strong {
        font-size: 9px;
    }

    .hero-sticker {
        top: 0;
        right: 2%;
        padding: 9px 11px;
        font-size: 9px;
    }

    .catalog {
        padding: 28px 5%;
    }

    .section-heading {
        align-items: center;
        gap: 8px;
        margin-bottom: 17px;
    }

    .section-heading h2 {
        font-size: 22px;
        letter-spacing: -.7px;
    }

    .section-heading .muted {
        margin-bottom: 0;
        font-size: 11px;
    }

    .section-heading .eyebrow {
        margin-bottom: 7px;
        font-size: 9px;
    }

    .count {
        padding: 8px;
        font-size: 10px;
    }

    .searchbar {
        gap: 7px;
        padding: 6px;
        border-radius: 13px;
    }

    .search-icon {
        padding-left: 5px;
        font-size: 21px;
    }

    .searchbar input {
        font-size: 12px;
    }

    .searchbar .button {
        min-height: 37px;
        padding: 9px 12px;
        font-size: 11px;
    }

    .category-chips {
        gap: 7px;
        margin: 15px 0 19px;
    }

    .chip {
        padding: 9px 12px;
        font-size: 10px;
    }

    .product-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 11px;
    }

    .product-card {
        border-radius: 14px;
    }

    .product-image,
    .product-placeholder {
        height: 135px;
    }

    .product-content {
        padding: 10px;
    }

    .product-meta {
        gap: 4px;
        margin-bottom: 7px;
    }

    .category,
    .condition {
        padding: 4px 6px;
        font-size: 8px;
    }

    .product-content h3 {
        min-height: 36px;
        margin-bottom: 5px;
        font-size: 12px;
    }

    .description {
        min-height: 30px;
        font-size: 10px;
        line-height: 1.5;
    }

    .catalog-product-foot {
        margin-top: 10px;
    }

    .price {
        font-size: 13px;
    }

    .product-sparkle {
        font-size: 12px;
    }

    .order-button,
    .order-login,
    .order-unavailable {
        margin-top: 9px;
        padding: 9px 5px;
        border-radius: 8px;
        font-size: 9px;
        line-height: 1.5;
    }

    .about {
        margin: 0 5% 28px;
        padding: 30px 17px;
        border-radius: 19px;
    }

    .about h2 {
        font-size: 23px;
    }

    .about > p:not(.eyebrow) {
        font-size: 12px;
    }

    footer {
        flex-direction: column;
        gap: 7px;
        padding: 22px 5%;
        text-align: center;
    }

    footer p {
        margin: 0;
        font-size: 10px;
    }

    .mobile-bottom-nav {
        position: fixed;
        z-index: 999;
        right: 0;
        bottom: 0;
        left: 0;
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        padding: 9px 6px calc(9px + env(safe-area-inset-bottom));
        border-top: 1px solid #e7eaf2;
        background: rgba(255,255,255,.97);
        box-shadow: 0 -5px 24px #1c32640d;
        backdrop-filter: blur(12px);
    }

    .mobile-nav-item {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 3px;
        color: #8991a5;
    }

    .mobile-nav-item span {
        font-size: 23px;
        line-height: 1.2;
    }

    .mobile-nav-item small {
        font-size: 9px;
        font-weight: 700;
    }

    .mobile-nav-item.active,
    .mobile-nav-item:hover {
        color: var(--cm-blue);
    }
}

@media (prefers-reduced-motion: reduce) {
    *,
    *::before,
    *::after {
        scroll-behavior: auto !important;
        transition: none !important;
    }
}

/* Hilangkan ilustrasi makanan di hero */
.hero-art {
    display: none !important;
}

/* Pusatkan tulisan hero */
.hero-inner {
    display: flex !important;
    justify-content: center !important;
    text-align: center !important;
}

.hero-copy {
    width: 100%;
    max-width: 760px;
    margin: 0 auto;
}

.hero .eyebrow {
    text-align: center;
}

.hero h1 {
    text-align: center;
}

.hero-description {
    max-width: 600px;
    margin: 18px auto 24px;
    text-align: center;
}

.hero-actions {
    justify-content: center;
}

.hero-stats {
    justify-content: center;
}

@media (max-width: 650px) {
    .hero {
        padding-top: 42px;
        padding-bottom: 35px;
    }

    .hero h1 {
        font-size: 38px;
    }

    .hero-description {
        font-size: 13px;
    }

    .hero-stats {
        gap: 20px;
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
                    Kuliner Pilihan Mahasiswa,<br>
                    <span>Di Kampus.</span>
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

            </div>
        </div>
    </section>

    <section class="catalog" id="katalog">
        <div class="section-heading">
            <div>
                <p class="eyebrow">TEMUKAN FAVORITMU</p>
                <h2>Cari apa hari ini?</h2>
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

            <button
                class="chip"
                type="button"
                data-category="makanan"
                aria-pressed="false"
            >
                🍽️ Makanan
            </button>

            <button
                class="chip"
                type="button"
                data-category="lainnya"
                aria-pressed="false"
            >
                📦 Lainnya
            </button>
        </div>

        <?php if (count($products) > 0): ?>
            <div class="product-grid" id="productGrid">
                <?php foreach ($products as $product): ?>
                    <?php
                    $categoryName = $product['category_name'] ?: 'Lainnya';

                    // Kelompok filter katalog: hanya Makanan dan Lainnya.
                    // Kategori kuliner lama tetap dikenali sebagai Makanan.
                    $normalizedCategory = mb_strtolower(trim($categoryName), 'UTF-8');
                    $isFoodCategory = (bool) preg_match(
                        '/makanan|minuman|kuliner|camilan|cemilan|snack|dessert|\bkue\b|\broti\b|\bfood\b|beverage/i',
                        $normalizedCategory
                    );
                    $filterCategory = $isFoodCategory ? 'makanan' : 'lainnya';

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
                        data-category="<?= e($filterCategory) ?>"
                        data-search="<?= e($searchText) ?>"
                    >
                        <?php if (!empty($product['gallery_images'])): ?>
                            <div class="product-gallery" data-gallery>
                                <img
                                    class="product-image"
                                    src="<?= e($product['gallery_images'][0]) ?>"
                                    alt="<?= e($product['name']) ?>"
                                    loading="lazy"
                                >
                                <?php if (count($product['gallery_images']) > 1): ?>
                                    <button class="gallery-arrow gallery-prev" type="button" aria-label="Foto sebelumnya" data-gallery-prev>‹</button>
                                    <button class="gallery-arrow gallery-next" type="button" aria-label="Foto berikutnya" data-gallery-next>›</button>
                                    <span class="gallery-count" data-gallery-count>1 / <?= count($product['gallery_images']) ?></span>
                                    <script type="application/json" class="gallery-data"><?= json_encode(array_values($product['gallery_images']), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?></script>
                                <?php endif; ?>
                            </div>
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

                            <p class="seller-label">Dijual oleh <strong><?= e($product['seller_name'] ?: 'Penjual CampusMarket') ?></strong></p>

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
        <h2>Usaha kecil, semangat besar. </h2>
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

<nav class="mobile-bottom-nav" aria-label="Navigasi mobile">
    <a class="mobile-nav-item active" href="index.php"><span aria-hidden="true">⌂</span><small>Beranda</small></a>
    <a class="mobile-nav-item" href="#katalog"><span aria-hidden="true">⌕</span><small>Cari</small></a>
    <a
    class="mobile-nav-item cart-nav-item"
    href="<?= $isLoggedIn ? 'cart.php' : 'login.php' ?>"
    aria-label="Buka keranjang belanja"
>
    <svg
        class="cart-nav-icon"
        xmlns="http://www.w3.org/2000/svg"
        width="26"
        height="26"
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        stroke-width="2"
        stroke-linecap="round"
        stroke-linejoin="round"
        aria-hidden="true"
    >
        <circle cx="9" cy="21" r="1"/>
        <circle cx="20" cy="21" r="1"/>
        <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>
    </svg>
    <small>Keranjang</small>
</a>
    <a class="mobile-nav-item" href="<?= $isLoggedIn ? 'profile.php' : 'login.php' ?>"><span aria-hidden="true">◉</span><small>Profil</small></a>
</nav>

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

<script>
document.querySelectorAll('[data-gallery]').forEach((gallery) => {
    const data = gallery.querySelector('.gallery-data');
    if (!data) return;
    let images;
    try { images = JSON.parse(data.textContent); } catch (_) { return; }
    if (!Array.isArray(images) || images.length < 2) return;
    const image = gallery.querySelector('img');
    const count = gallery.querySelector('[data-gallery-count]');
    let current = 0;
    const show = (step) => {
        current = (current + step + images.length) % images.length;
        image.src = images[current];
        count.textContent = `${current + 1} / ${images.length}`;
    };
    gallery.querySelector('[data-gallery-prev]').addEventListener('click', () => show(-1));
    gallery.querySelector('[data-gallery-next]').addEventListener('click', () => show(1));
});
</script>
</body>
</html>
