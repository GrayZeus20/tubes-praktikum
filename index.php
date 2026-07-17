<?php
session_start();
include "koneksi.php";

$username = $_SESSION['username'] ?? null;
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
$upload_error = null;

// Upload handler
if (isset($_POST['input']) && $username && isset($_FILES['gambar'])) {
    $f = $_FILES['gambar'];
    if ($f['error'] === UPLOAD_ERR_OK && $f['size'] < 2048000 && in_array($f['type'], ['image/jpeg', 'image/png'])) {
        $image = file_get_contents($f['tmp_name']);
        $stmt = $koneksi->prepare("INSERT INTO tb_gambar (gambar, username, nama_gambar, tipe_gambar, ukuran_gambar, keterangan, judul) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssiss", $image, $username, $f['name'], $f['type'], $f['size'], $_POST['keterangan'], $_POST['judul']);
        $stmt->execute();
        $stmt->close();
        header("Location: index.php");
        exit;
    } else {
        $upload_error = 'Ukuran maksimal 2MB, format JPEG/PNG';
    }
}

// Search
$search = $_GET['cari'] ?? '';
if ($search) {
    $like = "%$search%";
    $stmt = $koneksi->prepare("SELECT tb_gambar.*, (SELECT COUNT(*) FROM favorit WHERE id_gambar = tb_gambar.id) as fav_count FROM tb_gambar WHERE judul LIKE ? OR keterangan LIKE ?");
    $stmt->bind_param("ss", $like, $like);
    $stmt->execute();
    $images = $stmt->get_result();
    $stmt->close();
} else {
    $images = $koneksi->query("SELECT tb_gambar.*, (SELECT COUNT(*) FROM favorit WHERE id_gambar = tb_gambar.id) as fav_count FROM tb_gambar");
}

// Get favorited image IDs for current user
$favoritedIds = [];
if ($username) {
    $stmt = $koneksi->prepare("SELECT id_gambar FROM favorit WHERE id_user = (SELECT id_user FROM admin WHERE username = ?)");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $favResult = $stmt->get_result();
    while ($favRow = $favResult->fetch_assoc()) {
        $favoritedIds[] = $favRow['id_gambar'];
    }
    $stmt->close();
}

// Render cards helper
function renderCards($result, $type = 'home', $currentUser = null, $favoritedIds = []) {
    if ($result->num_rows === 0) {
        $msg = $type === 'favorite' ? 'Tidak ada gambar favorit' : 'Gambar tidak ditemukan';
        echo "<div class='text-center mb-4'><h3>$msg</h3></div>";
        return;
    }
    while ($row = $result->fetch_assoc()) {
        $id = htmlspecialchars($row['id']);
        $judul = htmlspecialchars($row['judul']);
        $ket = htmlspecialchars($row['keterangan']);
        $owner = htmlspecialchars($row['username'] ?? '');
        $imgSrc = "img_view.php?id=$id";
        $isOwner = $currentUser && $owner === $currentUser;
        $isFavorited = in_array($row['id'], $favoritedIds);
        $favCount = htmlspecialchars($row['fav_count'] ?? 0);
        $btn = '';

        if ($type === 'home') {
            if ($currentUser) {
                $heartClass = $isFavorited ? 'favorited' : '';
                $icon = $isFavorited ? '♥' : '♡';
                $countHtml = $favCount > 0 ? " $favCount" : '';
                $btn = "<button type='button' class='favorite-btn btn btn-sm $heartClass' data-id='$id' title='Favorit'>$icon$countHtml</button>";
            }
        } elseif ($type === 'profile') {
            $btn = "<button type='button' class='delete-gambar-btn btn btn-outline-info btn-sm' data-gambar-id='$id' title='Hapus'>H</button>
                    <button type='button' class='edit-gambar-btn btn btn-outline-info btn-sm ml-1' data-gambar-id='$id' title='Edit'>E</button>";
        } elseif ($type === 'favorite') {
            $fid = htmlspecialchars($row['favorite_id']);
            $btn = "<button type='button' class='delete-favorite-btn btn btn-outline-danger btn-sm' data-favorite-id='$fid' title='Hapus'>♥</button>";
        }

        echo "<div class='col-sm-6 col-md-4 col-lg-3 mb-4 col-pin'>
                <div class='card'>
                    <img src='$imgSrc' class='imageLink' data-id='$id' loading='lazy'/>
                    <div class='card-body'>
                        <h6 class='mb-1 font-weight-bold'>$judul</h6>
                        <small class='text-muted'>$ket</small>
                        <div class='mt-2'><small class='text-primary font-weight-bold'>@$owner</small></div>
                    </div>
                    <div class='card-footer bg-white border-0 text-right p-2'>$btn</div>
                </div>
              </div>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <meta name="description" content="Gallery aplikasi praktikum">
    <meta name="theme-color" content="#4F46E5">
    <title>Kelompok3 - Gallery</title>
    <link rel="preconnect" href="https://stackpath.bootstrapcdn.com">
    <link rel="preconnect" href="https://code.jquery.com">
    <link rel="preconnect" href="https://cdn.jsdelivr.net">
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet"/>
    <style>
        :root { --primary: #4F46E5; --bg: #F8FAFC; --surface: #FFFFFF; --text: #1E293B; --radius: 12px; }
        body { font-family: 'Segoe UI', system-ui, sans-serif; background: var(--bg); color: var(--text); }
        .card { border: none; border-radius: var(--radius); box-shadow: 0 1px 3px rgba(0,0,0,.1); transition: 0.3s; overflow: hidden; }
        .card:hover { box-shadow: 0 10px 20px rgba(0,0,0,.15); transform: translateY(-4px); }
        .pin img { width: 100%; height: 240px; object-fit: cover; cursor: pointer; }
        header { background: linear-gradient(135deg, #4F46E5, #7C3AED); padding: 1.5rem 0; box-shadow: 0 4px 6px -1px rgba(0,0,0,.1); color: white; }
        .nav-item { color: rgba(255,255,255,.9); padding: 0.5rem 1rem; border-radius: 8px; cursor: pointer; transition: 0.2s; display: flex; align-items: center; gap: 8px; }
        .nav-item:hover { background: rgba(255,255,255,.2); color: white; }
        footer { padding: 2rem; text-align: center; color: var(--text-muted); font-size: 0.9rem; margin-top: 2rem; background: #fff; }
        .search-bar input { border-radius: 20px; padding-left: 35px; background: #F1F5F9; border: none; }
        #scrollTopBtn { position: fixed; bottom: 2rem; right: 2rem; width: 3rem; height: 3rem; border-radius: 50%; background: var(--primary); color: white; border: none; box-shadow: 0 4px 10px rgba(0,0,0,.2); display: flex; align-items: center; justify-content: center; cursor: pointer; visibility: hidden; opacity: 0; transition: 0.3s; }
        #scrollTopBtn.show { visibility: visible; opacity: 1; }
        .col-pin { content-visibility: auto; contain-intrinsic-size: 0 350px; }
        @media(max-width: 576px) { .pin img { height: 180px; } }
    </style>
</head>
<body>

<header class="p-3 mb-4">
    <div class="container d-flex justify-content-between align-items-center">
        <a href="#" id="homeLink" class="brand"><h3><i>Kelompok 3</i></h3></a>
        <form action="index.php" method="get" class="search-bar">
            <input type="text" class="form-control" placeholder="Cari gambar..." name="cari" value="<?=htmlspecialchars($search)?>"/>
        </form>
        <nav class="d-flex align-items-center" style="gap:4px">
            <?php if ($username): ?>
                <div class="nav-item" id="profileLink" title="Profile">
                    <img src="user.svg" alt="Profile"/>
                    <span class="nav-label">Profile</span>
                </div>
                <div class="nav-item" id="favoriteLink" title="Favorit">
                    <img src="heart.svg" alt="Favorit"/>
                    <span class="nav-label">Favorit</span>
                </div>
                <div class="nav-item" id="newLink" title="Tambah">
                    <img src="plus-square.svg" alt="Tambah"/>
                    <span class="nav-label">Tambah</span>
                </div>
                <a href="auth.php?action=logout" class="nav-item-logout ml-2" onclick="return confirm('Logout?')">
                    <img src="log-out.svg" alt="Logout"/> Logout
                </a>
            <?php else: ?>
                <button class="btn btn-login ml-2" data-toggle="modal" data-target="#loginModal">Login</button>
                <button class="btn btn-register ml-2" data-toggle="modal" data-target="#registerModal">Register</button>
            <?php endif; ?>
        </nav>
    </div>
</header>

<main class="container">
    <!-- Home -->
    <div id="homeSection" class="section">
        <div class="row"><?php renderCards($images, 'home', $username, $favoritedIds); ?></div>
    </div>

    <!-- Profile -->
    <div id="profileSection" class="section" style="display:none">
        <div class="text-center mb-4">
            <h3>Selamat Datang <?=htmlspecialchars($username)?></h3>
            <?php
            $countStmt = $koneksi->prepare("SELECT COUNT(*) as total FROM tb_gambar WHERE username = ?");
            $countStmt->bind_param("s", $username);
            $countStmt->execute();
            $countRow = $countStmt->get_result()->fetch_assoc();
            $countStmt->close();
            echo "<p class='text-muted'>Total gambar: " . $countRow['total'] . "</p>";
            ?>
        </div>
        <div class="row">
            <?php
            if ($username) {
                $stmt = $koneksi->prepare("SELECT * FROM tb_gambar WHERE username = ?");
                $stmt->bind_param("s", $username);
                $stmt->execute();
                renderCards($stmt->get_result(), 'profile', $username);
                $stmt->close();
            }
            ?>
        </div>
    </div>

    <!-- Favorite -->
    <div id="favoriteSection" class="section" style="display:none">
        <div class="text-center mb-4"><h3>Favorite</h3></div>
        <div class="row">
            <?php
            if ($username) {
                $stmt = $koneksi->prepare("SELECT g.id, g.judul, g.keterangan, f.id as favorite_id FROM tb_gambar g INNER JOIN favorit f ON g.id = f.id_gambar WHERE f.id_user = (SELECT id_user FROM admin WHERE username = ?)");
                $stmt->bind_param("s", $username);
                $stmt->execute();
                renderCards($stmt->get_result(), 'favorite', $username);
                $stmt->close();
            } else {
                echo "<div class='text-center mb-4'><h3>Silakan login</h3></div>";
            }
            ?>
        </div>
    </div>

    <!-- Image Detail -->
    <div id="imageSection" class="section" style="display:none">
        <div class="col-md-8 m-auto">
            <div class="card text-left mb-5">
                <div class="card-body text-center" id="imageDetail"></div>
            </div>
        </div>
    </div>

    <!-- New Upload -->
    <div id="newSection" class="section" style="display:none">
        <div class="col-md-8 m-auto">
            <div class="card text-left mb-5">
                <div class="card-header text-center"><h3>Upload Baru</h3></div>
                <div class="card-body">
                    <?php if (isset($upload_error)): ?>
                        <div class="alert alert-danger"><?=$upload_error?></div>
                    <?php endif; ?>
                    <form action="" method="POST" enctype="multipart/form-data">
                        <div class="form-group">
                            <label>Gambar</label>
                            <input type="file" name="gambar" class="form-control-file" id="uploadGambar" required onchange="previewImage(this)">
                            <div class="mt-2 preview-wrap" style="display:none">
                                <img src="#" alt="Preview" class="img-fluid rounded border" style="max-height:250px"/>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Judul</label>
                            <input type="text" name="judul" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Keterangan</label>
                            <textarea name="keterangan" class="form-control" rows="3" required></textarea>
                        </div>
                        <button type="submit" name="input" class="btn btn-primary">Upload</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- Login Modal -->
<div class="modal fade" id="loginModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5>Login</h5><button type="button" class="close" data-dismiss="modal">&times;</button></div>
            <form action="auth.php?action=login" method="POST">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Username</label>
                        <input type="text" name="username" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Password</label>
                        <div class="input-group">
                            <input type="password" name="password" class="form-control" id="loginPassword" required>
                            <div class="input-group-append">
                                <button class="btn btn-outline-secondary" type="button" id="toggleLoginPass" tabindex="-1">
                                    <span id="loginEyeIcon">Tampilkan</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Login</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Register Modal -->
<div class="modal fade" id="registerModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5>Register</h5><button type="button" class="close" data-dismiss="modal">&times;</button></div>
            <form action="auth.php?action=register" method="POST">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Username</label>
                        <input type="text" name="username" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Password</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Register</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="editForm" enctype="multipart/form-data">
                <div class="modal-header"><h5>Edit Gambar</h5><button type="button" class="close" data-dismiss="modal">&times;</button></div>
                <div class="modal-body">
                    <input type="hidden" name="id" id="editId">
                    <div class="form-group text-center">
                        <label>Gambar Saat Ini</label><br>
                        <img id="editCurrentImage" src="#" alt="Current" class="img-fluid rounded border" style="max-height:200px;display:none"/>
                    </div>
                    <div class="form-group">
                        <label>Judul</label>
                        <input type="text" class="form-control" id="editJudul" name="judul" required>
                    </div>
                    <div class="form-group">
                        <label>Keterangan</label>
                        <textarea class="form-control" id="editKeterangan" name="keterangan" required></textarea>
                    </div>
                    <div class="form-group">
                        <label>Gambar Baru (kosongkan jika tidak diubah)</label>
                        <input type="file" class="form-control-file" name="gambar" onchange="previewImage(this)">
                        <div class="mt-2" id="previewContainerEdit" style="display:none">
                            <img id="previewImgEdit" src="#" alt="Preview" class="img-fluid rounded border" style="max-height:200px"/>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<button id="scrollTopBtn" title="Ke atas">&#8593;</button>

<footer>&copy; 2024 Kelompok 3. All rights reserved.</footer>

<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
<script>
$(document).ready(function() {
    // Scroll to top
    var scrollBtn = $('#scrollTopBtn');
    $(window).scroll(function() {
        $(this).scrollTop() > 300 ? scrollBtn.addClass('show') : scrollBtn.removeClass('show');
    });
    scrollBtn.click(function() {
        $('html,body').animate({ scrollTop: 0 }, 500, 'swing');
    });

    function showSection(id) { $('.section').hide(); $('#' + id).show(); }

    function setActiveNav(el) {
        $('.nav-item').removeClass('active');
        if (el) el.addClass('active');
    }

    $('#toggleLoginPass').click(function() {
        var input = $('#loginPassword');
        var icon = $('#loginEyeIcon');
        if (input.attr('type') === 'password') {
            input.attr('type', 'text');
            icon.text('Sembunyikan');
        } else {
            input.attr('type', 'password');
            icon.text('Tampilkan');
        }
    });

    $('#homeLink').click(function(e) { e.preventDefault(); showSection('homeSection'); setActiveNav(null); });
    $('#profileLink').click(function(e) { e.preventDefault(); showSection('profileSection'); setActiveNav($(this)); });
    $('#favoriteLink').click(function(e) { e.preventDefault(); showSection('favoriteSection'); setActiveNav($(this)); });
    $('#newLink').click(function(e) { e.preventDefault(); showSection('newSection'); setActiveNav($(this)); });

    $(document).on('click', '.favorite-btn', function() {
        var btn = $(this);
        $.post('api.php', { action: 'favorit', id: btn.data('id') })
            .done(function(r) {
                if (r.status === 'success') {
                    if (r.action === 'added') {
                        btn.addClass('favorited').html('♥');
                    } else {
                        btn.removeClass('favorited').html('♡');
                    }
                }
                alert(r.message);
            })
            .fail(function() { alert('Terjadi kesalahan'); });
    });

    $(document).on('click', '.delete-gambar-btn', function() {
        if (!confirm('Hapus gambar ini?')) return;
        $.post('api.php', { action: 'delete_gambar', id_gambar: $(this).data('gambar-id') })
            .done(function(r) { alert(r.message || (r.status === 'success' ? 'Gambar dihapus!' : 'Terjadi kesalahan')); if (r.status === 'success') location.reload(); })
            .fail(function() { alert('Terjadi kesalahan'); });
    });

    $(document).on('click', '.delete-favorite-btn', function() {
        if (!confirm('Hapus dari favorit?')) return;
        $.post('api.php', { action: 'delete_favorit', id_favorite: $(this).data('favorite-id') })
            .done(function(r) { alert(r.message || (r.status === 'success' ? 'Favorit dihapus!' : 'Terjadi kesalahan')); if (r.status === 'success') location.reload(); })
            .fail(function() { alert('Terjadi kesalahan'); });
    });

    $(document).on('click', '.imageLink', function() {
        var id = $(this).data('id');
        showSection('imageSection');
        $.post('api.php', { action: 'get_image_details', id: id }, function(r) {
            if (r.error) { $('#imageDetail').html('<div class="alert alert-danger">' + r.error + '</div>'); }
            else {
                var favHtml = parseInt(r.fav_count) > 0 ? '<span class="badge badge-danger">♥ ' + r.fav_count + '</span>' : '';
                $('#imageDetail').html(
                    '<button class="btn btn-outline-secondary mb-3" onclick="$(\'#homeLink\').click()">← Kembali</button>' +
                    '<img src="img_view.php?id=' + r.id + '" class="img-fluid"/>' +
                    '<h3 class="mt-3">' + r.judul + ' ' + favHtml + '</h3>' +
                    '<p class="text-muted">' + r.keterangan + '</p>' +
                    '<small class="text-primary">by ' + r.username + '</small>'
                );
            }
        }, 'json').fail(function() { $('#imageDetail').html('<div class="alert alert-danger">Gagal memuat gambar</div>'); });
    });

    $(document).on('click', '.edit-gambar-btn', function() {
        var btn = $(this);
        $.get('api.php', { action: 'get_image_data', id: btn.data('gambar-id') }, function(r) {
            if (r.error) { alert(r.error); }
            else {
                $('#editId').val(r.id);
                $('#editJudul').val(r.judul);
                $('#editKeterangan').val(r.keterangan);
                $('#editCurrentImage').attr('src', 'img_view.php?id=' + r.id).show();
                $('#previewContainerEdit').hide();
                $('#editModal').modal('show');
            }
        }).fail(function() { alert('Gagal memuat data gambar'); });
    });

    function previewImage(input) {
        var wrap = $(input).closest('.form-group').find('.preview-wrap, #previewContainerEdit').first();
        var img = wrap.find('img');
        if (!img.length) img = wrap.find('#previewImgEdit');
        if (input.files && input.files[0]) {
            var reader = new FileReader();
            reader.onload = function(e) { img.attr('src', e.target.result); wrap.show(); };
            reader.readAsDataURL(input.files[0]);
        } else { wrap.hide(); img.attr('src', '#'); }
    }

    $('#editForm').on('submit', function(e) {
        e.preventDefault();
        var fd = new FormData(this);
        fd.append('action', 'update');
        $.ajax({
            url: 'api.php', type: 'POST', data: fd, contentType: false, processData: false,
            success: function(r) { alert(r.message); if (r.status === 'success') location.reload(); },
            error: function() { alert('Error'); }
        });
    });
});
</script>
</body>
</html>
