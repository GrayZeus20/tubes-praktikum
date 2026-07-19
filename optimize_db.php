<?php
include 'koneksi.php';

$queries = [
    "CREATE INDEX idx_admin_username ON admin(username)",
    "CREATE INDEX idx_tb_gambar_username ON tb_gambar(username)",
    "CREATE INDEX idx_favorit_id_user ON favorit(id_user)",
    "CREATE INDEX idx_favorit_id_gambar ON favorit(id_gambar)"
];

echo "<h2>Optimizing Database Indexes</h2>";
foreach ($queries as $q) {
    if ($koneksi->query($q)) {
        echo "<p style='color:green'>Berhasil: $q</p>";
    } else {
        echo "<p style='color:orange'>Info: " . $koneksi->error . " (Mungkin index sudah ada)</p>";
    }
}
echo "<p style='color:blue'>Optimasi selesai!</p>";
?>
