<?php
session_start();
include 'koneksi.php';

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'login':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $stmt = $koneksi->prepare("SELECT * FROM admin WHERE username=?");
            $stmt->bind_param("s", $_POST['username']);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if ($row && password_verify($_POST['password'], $row['password'])) {
                $_SESSION['username'] = $_POST['username'];
            } else {
                $_SESSION['flash'] = 'Username atau password salah';
            }
        }
        break;
    case 'register':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $hash = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $stmt = $koneksi->prepare("INSERT INTO admin (username, password) VALUES (?, ?)");
            $stmt->bind_param("ss", $_POST['username'], $hash);
            if ($stmt->execute()) {
                $_SESSION['username'] = $_POST['username'];
            } else {
                $_SESSION['flash'] = 'Gagal daftar: ' . $stmt->error;
            }
            $stmt->close();
        }
        break;
    case 'logout':
        session_destroy();
        break;
}

$url = $_SERVER['HTTP_REFERER'] ?? 'index.php';
if (strpos($url, 'auth.php') !== false || strpos($url, 'login') !== false) {
    $url = 'index.php';
}
header("Location: $url");
exit;
