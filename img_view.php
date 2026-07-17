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
    header("Cache-Control: public, max-age=86400, immutable");
    header("Expires: " . gmdate('D, d M Y H:i:s \G\M\T', time() + 86400));
    echo $row["gambar"];
} else {
    header('Location: index.php');
}
$stmt->close();
