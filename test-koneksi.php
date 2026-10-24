
<?php

require_once __DIR__ . '/config/database.php';

try {
    // Pastikan koneksi mengarah ke database yang benar
    $database = $pdo->query('SELECT DATABASE()')->fetchColumn();

    // Cek apakah tabel products bisa dibaca
    $jumlahTabel = $pdo->query(
        "SELECT COUNT(*)
         FROM information_schema.tables
         WHERE table_schema = DATABASE()
         AND table_name IN ('users', 'categories', 'products')"
    )->fetchColumn();

    if ($database !== 'campusmarket_stupen' || (int) $jumlahTabel !== 3) {
        throw new RuntimeException('Database atau tabel belum sesuai.');
    }

    echo '<h1>Koneksi Berhasil!</h1>';
    echo '<p>PHP berhasil terhubung ke MySQL.</p>';
    echo '<p>Database: ' .
        htmlspecialchars($database, ENT_QUOTES, 'UTF-8') .
        '</p>';
    echo '<p>Tabel users, categories, dan products ditemukan.</p>';

} catch (Throwable $e) {
    error_log($e->getMessage());
    http_response_code(500);
    echo '<h1>Pengujian gagal</h1>';
    echo '<p>Periksa konfigurasi database dan tabel di phpMyAdmin.</p>';
}
