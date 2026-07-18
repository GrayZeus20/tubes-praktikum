<?php
session_start();
include 'koneksi.php';
header('Content-Type: application/json');

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
                echo json_encode(['status' => 'success', 'message' => 'Login berhasil!']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Username atau password salah']);
            }
        }
        exit;

    case 'register':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $hash = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $stmt = $koneksi->prepare("INSERT INTO admin (username, password) VALUES (?, ?)");
            $stmt->bind_param("ss", $_POST['username'], $hash);
            if ($stmt->execute()) {
                $_SESSION['username'] = $_POST['username'];
                echo json_encode(['status' => 'success', 'message' => 'Akun berhasil dibuat!']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Gagal daftar: ' . $stmt->error]);
            }
            $stmt->close();
        }
        exit;

    case 'logout':
        session_destroy();
        echo json_encode(['status' => 'success', 'message' => 'Logout berhasil']);
        exit;
}

echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
exit;
