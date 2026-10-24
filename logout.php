
<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Kosongkan semua data sesi.
$_SESSION = [];

// Hapus cookie sesi jika menggunakan cookie.
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();

    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}

// Akhiri sesi.
session_destroy();

// Kembali ke halaman login.
header('Location: login.php');
exit;
