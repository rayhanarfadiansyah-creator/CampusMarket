
<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config/database.php';

function requireLogin(): void
{
    if (!isset($_SESSION['user_id'])) {
        header('Location: login.php');
        exit;
    }
}

function h($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function getCsrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function verifyCsrfToken(): void
{
    $token = $_POST['csrf_token'] ?? '';

    if (
        !is_string($token) ||
        empty($_SESSION['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], $token)
    ) {
        http_response_code(403);
        exit('Permintaan tidak valid. Muat ulang halaman lalu coba lagi.');
    }
}

function uploadProductImage(array $file): ?string
{
    if (!isset($file['error']) || is_array($file['error'])) {
        throw new RuntimeException('Data foto tidak valid.');
    }

    if ($file['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Upload foto gagal.');
    }

    if ($file['size'] > 3 * 1024 * 1024) {
        throw new RuntimeException('Ukuran foto maksimal 3 MB.');
    }

    if (!is_uploaded_file($file['tmp_name'])) {
        throw new RuntimeException('File upload tidak valid.');
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);

    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    if (!isset($allowed[$mime]) ||
        @getimagesize($file['tmp_name']) === false) {
        throw new RuntimeException(
            'Foto harus JPG, PNG, atau WebP.'
        );
    }

    $directory = __DIR__ . '/uploads/products';

    if (!is_dir($directory) &&
        !mkdir($directory, 0755, true) &&
        !is_dir($directory)) {
        throw new RuntimeException('Folder upload gagal dibuat.');
    }

    $filename = bin2hex(random_bytes(16)) . '.' . $allowed[$mime];
    $destination = $directory . '/' . $filename;

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        throw new RuntimeException('Foto gagal disimpan.');
    }

    return 'uploads/products/' . $filename;
}


/** Upload beberapa foto produk (maksimal 5 file). */
function uploadMultipleProductImages(array $files, int $maxFiles = 5): array
{
    if (!isset($files['name']) || !is_array($files['name'])) {
        return [];
    }

    $count = count(array_filter($files['name'], static fn($name) => $name !== ''));
    if ($count > $maxFiles) {
        throw new RuntimeException("Maksimal {$maxFiles} foto untuk satu produk.");
    }

    $uploaded = [];
    try {
        foreach ($files['name'] as $index => $name) {
            if ($name === '') {
                continue;
            }
            $file = [
                'name' => $name,
                'type' => $files['type'][$index] ?? '',
                'tmp_name' => $files['tmp_name'][$index] ?? '',
                'error' => $files['error'][$index] ?? UPLOAD_ERR_NO_FILE,
                'size' => $files['size'][$index] ?? 0,
            ];
            $path = uploadProductImage($file);
            if ($path !== null) {
                $uploaded[] = $path;
            }
        }
    } catch (Throwable $e) {
        foreach ($uploaded as $path) {
            deleteProductImage($path);
        }
        throw $e;
    }

    return $uploaded;
}

function deleteProductImage(?string $path): void
{
    if (!$path || !preg_match(
        '~\Auploads/products/[a-f0-9]{32}\.(jpg|png|webp)\z~',
        $path
    )) {
        return;
    }

    $fullPath = __DIR__ . '/' . $path;

    if (is_file($fullPath)) {
        unlink($fullPath);
    }
}
