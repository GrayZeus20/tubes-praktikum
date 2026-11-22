<?php
/**
 * Template Konfigurasi Koneksi Database
 * Salin file ini menjadi 'koneksi.php' lalu sesuaikan kredensial dengan database Anda.
 * 
 * JANGAN PERNAH meng-commit file 'koneksi.php' yang berisi kredensial asli ke repositori publik!
 */

$isLocal = in_array($_SERVER['SERVER_NAME'] ?? '', ['localhost', '127.0.0.1', '::1']);

if ($isLocal) {
    // Konfigurasi Database Lokal (Laragon / XAMPP)
    $host     = 'localhost';
    $username = 'root';
    $password = '';
    $dbname   = 'tubes';
} else {
    // Konfigurasi Database Server Produksi / Hosting
    $host     = getenv('DB_HOST') ?: 'YOUR_DB_HOST';
    $username = getenv('DB_USER') ?: 'YOUR_DB_USER';
    $password = getenv('DB_PASS') ?: 'YOUR_DB_PASSWORD';
    $dbname   = getenv('DB_NAME') ?: 'YOUR_DB_NAME';
}

$koneksi = new mysqli($host, $username, $password, $dbname);
if ($koneksi->connect_error) {
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Koneksi database gagal']);
    exit;
}
?>
