<?php
// LOCAL TEST ONLY — XAMPP. Do not upload this file to InfinityFree/live hosting.
$host = 'localhost';
$dbname = 'campusmarket_stupen';
$username = 'root';
$password = '';

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $e) {
    error_log($e->getMessage());
    http_response_code(500);
    exit('Koneksi database lokal gagal. Pastikan MySQL XAMPP aktif dan database campusmarket_stupen sudah diimpor.');
}
