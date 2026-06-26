<?php
include 'koneksi.php';

if (!isset($_GET['id'])) {
    header('Location: index.php');
    exit;
}

$stmt = $koneksi->prepare("SELECT gambar, tipe_gambar FROM tb_gambar WHERE id = ?");
$stmt->bind_param("i", $_GET['id']);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    header("Content-type: " . $row["tipe_gambar"]);
    echo $row["gambar"];
} else {
    header('Location: index.php');
}
$stmt->close();
