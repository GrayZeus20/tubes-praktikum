<?php
session_start();
include 'koneksi.php';
header('Content-Type: application/json');

function jsonError($msg) {
    echo json_encode(['status' => 'error', 'message' => $msg]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'favorit':
            if (!isset($_SESSION['username'])) jsonError('Silakan login terlebih dahulu');
            if (!isset($_POST['id'])) jsonError('ID gambar tidak ditemukan');

            $gambarId = intval($_POST['id']);
            $userId = null;
            $stmt = $koneksi->prepare("SELECT id_user FROM admin WHERE username = ?");
            $stmt->bind_param("s", $_SESSION['username']);
            $stmt->execute();
            $userRow = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if (!$userRow) jsonError('User tidak ditemukan');
            $userId = $userRow['id_user'];

            // Check if already favorited
            $stmt = $koneksi->prepare("SELECT id FROM favorit WHERE id_gambar = ? AND id_user = ?");
            $stmt->bind_param("ii", $gambarId, $userId);
            $stmt->execute();
            $existing = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if ($existing) {
                $stmt = $koneksi->prepare("DELETE FROM favorit WHERE id = ?");
                $stmt->bind_param("i", $existing['id']);
                $stmt->execute();
                $stmt->close();
                echo json_encode(['status' => 'success', 'action' => 'removed', 'message' => 'Dihapus dari favorit!']);
            } else {
                $stmt = $koneksi->prepare("INSERT INTO favorit (id_gambar, id_user) VALUES (?, ?)");
                $stmt->bind_param("ii", $gambarId, $userId);
                $stmt->execute();
                $stmt->close();
                echo json_encode(['status' => 'success', 'action' => 'added', 'message' => 'Ditambahkan ke favorit!']);
            }
            exit;

        case 'delete_favorit':
            if (!isset($_SESSION['username'])) jsonError('Silakan login terlebih dahulu');
            if (!isset($_POST['id_favorite'])) jsonError('ID favorit tidak ditemukan');

            $stmt = $koneksi->prepare("DELETE FROM favorit WHERE id = ?");
            $stmt->bind_param("i", $_POST['id_favorite']);
            $stmt->execute();
            $ok = $stmt->affected_rows > 0;
            $stmt->close();
            echo json_encode($ok
                ? ['status' => 'success']
                : ['status' => 'error', 'message' => 'Gagal menghapus favorit']);
            exit;

        case 'delete_gambar':
            if (!isset($_SESSION['username'])) jsonError('Silakan login terlebih dahulu');
            if (!isset($_POST['id_gambar'])) jsonError('ID gambar tidak ditemukan');

            $stmt = $koneksi->prepare("SELECT username FROM tb_gambar WHERE id = ?");
            $stmt->bind_param("i", $_POST['id_gambar']);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if (!$row) jsonError('Gambar tidak ditemukan');
            if ($row['username'] !== $_SESSION['username']) jsonError('Anda tidak memiliki akses untuk menghapus gambar ini');

            $koneksi->begin_transaction();
            try {
                $stmt = $koneksi->prepare("DELETE FROM favorit WHERE id_gambar = ?");
                $stmt->bind_param("i", $_POST['id_gambar']);
                $stmt->execute();
                $stmt->close();

                $stmt = $koneksi->prepare("DELETE FROM tb_gambar WHERE id = ?");
                $stmt->bind_param("i", $_POST['id_gambar']);
                $stmt->execute();
                $ok = $stmt->affected_rows > 0;
                $stmt->close();

                $ok ? $koneksi->commit() : $koneksi->rollback();
                echo json_encode($ok
                    ? ['status' => 'success']
                    : ['status' => 'error', 'message' => 'Gagal menghapus gambar']);
            } catch (Exception $e) {
                $koneksi->rollback();
                jsonError($e->getMessage());
            }
            exit;

        case 'update':
            if (!isset($_SESSION['username'])) jsonError('Silakan login terlebih dahulu');
            if (!isset($_POST['id'], $_POST['judul'], $_POST['keterangan'])) jsonError('Data tidak lengkap');

            $stmt = $koneksi->prepare("SELECT username FROM tb_gambar WHERE id = ?");
            $stmt->bind_param("i", $_POST['id']);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if (!$row) jsonError('Gambar tidak ditemukan');
            if ($row['username'] !== $_SESSION['username']) jsonError('Anda tidak memiliki akses untuk mengedit gambar ini');

            $id = intval($_POST['id']);
            $judul = $_POST['judul'];
            $keterangan = $_POST['keterangan'];

            if (!empty($_FILES['gambar']['name'])) {
                $f = $_FILES['gambar'];
                if ($f['size'] >= 2048000 || !in_array($f['type'], ['image/jpeg', 'image/png']))
                    jsonError('Ukuran / Tipe file tidak sesuai (max 2MB, JPEG/PNG)');

                $image = file_get_contents($f['tmp_name']);
                $stmt = $koneksi->prepare("UPDATE tb_gambar SET judul=?, keterangan=?, nama_gambar=?, tipe_gambar=?, ukuran_gambar=?, gambar=? WHERE id=?");
                $stmt->bind_param("ssssssi", $judul, $keterangan, $f['name'], $f['type'], $f['size'], $image, $id);
            } else {
                $stmt = $koneksi->prepare("UPDATE tb_gambar SET judul=?, keterangan=? WHERE id=?");
                $stmt->bind_param("ssi", $judul, $keterangan, $id);
            }

            $ok = $stmt->execute();
            $stmt->close();
            echo json_encode($ok
                ? ['status' => 'success', 'message' => 'Gambar berhasil diperbarui']
                : ['status' => 'error', 'message' => 'Gagal memperbarui gambar']);
            exit;

        case 'get_image_details':
            if (!isset($_POST['id'])) jsonError('ID gambar tidak ditemukan');

            $stmt = $koneksi->prepare("SELECT tb_gambar.*, (SELECT COUNT(*) FROM favorit WHERE id_gambar = tb_gambar.id) as fav_count FROM tb_gambar WHERE id = ?");
            $stmt->bind_param("i", $_POST['id']);
            $stmt->execute();
            $result = $stmt->get_result();
            $data = $result->fetch_assoc();
            $stmt->close();
            if ($data) {
                unset($data['gambar'], $data['nama_gambar'], $data['tipe_gambar'], $data['ukuran_gambar']);
            }
            echo json_encode($data ?: ['error' => 'Gambar tidak ditemukan']);
            exit;
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action'])) {
    switch ($_GET['action']) {
        case 'get_image_data':
            if (!isset($_GET['id'])) jsonError('ID gambar tidak ditemukan');

            $stmt = $koneksi->prepare("SELECT id, judul, keterangan FROM tb_gambar WHERE id = ?");
            $stmt->bind_param("i", $_GET['id']);
            $stmt->execute();
            $result = $stmt->get_result();
            $data = $result->fetch_assoc();
            $stmt->close();
            echo json_encode($data ?: ['error' => 'Gambar tidak ditemukan']);
            exit;
    }
}

jsonError('Invalid request');
