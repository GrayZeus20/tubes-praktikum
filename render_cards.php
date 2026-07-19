<?php
session_start();
include 'koneksi.php';
$username = $_SESSION['username'] ?? null;

function renderCards($result, $type, $currentUser) {
    while ($row = $result->fetch_assoc()) {
        $id = $row['id'];
        $judul = htmlspecialchars($row['judul']);
        $ket = htmlspecialchars($row['keterangan']);
        $owner = htmlspecialchars($row['username'] ?? '');
        $imgSrc = "img_view.php?id=$id";
        $isOwner = $currentUser && $owner === $currentUser;
        $btn = '';

        if ($type === 'profile' && $isOwner) {
            $btn = "<div class='flex gap-2 mt-2'>
                <button type='button' class='edit-gambar-btn w-8 h-8 rounded-full bg-white/20 flex items-center justify-center' data-gambar-id='$id'>
                    <span class='material-symbols-outlined text-xs text-white'>edit</span>
                </button>
                <button type='button' class='delete-gambar-btn w-8 h-8 rounded-full bg-red-500/30 flex items-center justify-center' data-gambar-id='$id'>
                    <span class='material-symbols-outlined text-xs text-white'>delete</span>
                </button>
            </div>";
        } elseif ($type === 'favorite') {
             $fid = htmlspecialchars($row['favorite_id']);
             $btn = "<button type='button' class='delete-favorite-btn w-8 h-8 rounded-full bg-red-500/30 flex items-center justify-center mt-2' data-favorite-id='$fid'>
                 <span class='material-symbols-outlined text-xs text-white'>favorite</span>
             </button>";
        }

        echo "<div class='masonry-item bg-surface-container-lowest border border-outline-variant/50 rounded-xl overflow-hidden shadow-sm'>
                <div class='img-wrap relative cursor-pointer imageLink' data-id='$id'>
                    <img src='$imgSrc' class='w-full h-full object-cover' loading='lazy'/>
                </div>
                <div class='p-3'>
                    <h3 class='font-semibold text-xs text-on-surface truncate'>$judul</h3>
                    $btn
                </div>
              </div>";
    }
}

// Handle AJAX
if (isset($_GET['action'])) {
    header('Content-Type: text/html');
    if ($_GET['action'] === 'get_profile_html') {
        if ($username) {
            $stmt = $koneksi->prepare("SELECT * FROM tb_gambar WHERE username = ?");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($res->num_rows === 0) {
                echo "<div class='col-span-full text-center py-12'><p class='text-on-surface-variant text-lg'>Tidak ada gambar</p></div>";
            } else {
                renderCards($res, 'profile', $username);
            }
            $stmt->close();
        }
    } elseif ($_GET['action'] === 'get_favorites_html' && $username) {
        $stmt = $koneksi->prepare("SELECT g.id, g.judul, g.keterangan, f.id as favorite_id FROM tb_gambar g INNER JOIN favorit f ON g.id = f.id_gambar WHERE f.id_user = (SELECT id_user FROM admin WHERE username = ?)");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res->num_rows === 0) {
            echo "<div class='col-span-full text-center py-12'><p class='text-on-surface-variant text-lg'>Tidak ada favorit</p></div>";
        } else {
            renderCards($res, 'favorite', $username);
        }
        $stmt->close();
    }
    exit;
}
?>
