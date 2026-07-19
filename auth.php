<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

try {
    session_start();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    include 'koneksi.php';
    header('Content-Type: application/json');

    $action = $_GET['action'] ?? '';

    switch ($action) {
        case 'login':
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $stmt = $koneksi->prepare("SELECT * FROM admin WHERE username=?");
                if (!$stmt) throw new Exception($koneksi->error);
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
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
            }
            exit;

        case 'register':
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $hash = password_hash($_POST['password'], PASSWORD_DEFAULT);
                if ($hash === false) throw new Exception('password_hash gagal');
                $stmt = $koneksi->prepare("INSERT INTO admin (username, password) VALUES (?, ?)");
                if (!$stmt) throw new Exception($koneksi->error);
                $stmt->bind_param("ss", $_POST['username'], $hash);
                if ($stmt->execute()) {
                    $_SESSION['username'] = $_POST['username'];
                    echo json_encode(['status' => 'success', 'message' => 'Akun berhasil dibuat!']);
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'Gagal daftar: ' . $stmt->error]);
                }
                $stmt->close();
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
            }
            exit;

        case 'check_session':
            echo json_encode(['status' => 'success', 'username' => $_SESSION['username'] ?? null]);
            exit;

        case 'logout':
            session_destroy();
            echo json_encode(['status' => 'success', 'message' => 'Logout berhasil']);
            exit;
    }

    echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
    exit;
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    exit;
}
