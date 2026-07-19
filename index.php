<?php
session_start();
include "koneksi.php";

$username = $_SESSION['username'] ?? null;
$upload_error = null;

// Upload handler
if (isset($_POST['input']) && $username && isset($_FILES['gambar'])) {
    $f = $_FILES['gambar'];
    if ($f['error'] === UPLOAD_ERR_OK && $f['size'] < 2048000) {
        $check = getimagesize($f['tmp_name']);
        if ($check !== false && in_array($check['mime'], ['image/jpeg', 'image/png'])) {
            $image = file_get_contents($f['tmp_name']);
            $stmt = $koneksi->prepare("INSERT INTO tb_gambar (gambar, username, nama_gambar, tipe_gambar, ukuran_gambar, keterangan, judul) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssiss", $image, $username, $f['name'], $f['type'], $f['size'], $_POST['keterangan'], $_POST['judul']);
            $stmt->execute();
            $stmt->close();
            header("Location: index.php");
            exit;
        }
        $upload_error = 'Ukuran maksimal 2MB, format JPEG/PNG';
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

function renderCards($result, $type = 'home', $currentUser = null, $favoritedIds = []) {
    if ($result->num_rows === 0) {
        $msg = $type === 'favorite' ? 'Tidak ada gambar favorit' : 'Gambar tidak ditemukan';
        echo "<div class='col-span-full text-center py-12'><p class='text-on-surface-variant text-lg'>$msg</p></div>";
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
                $heartIcon = $isFavorited ? 'favorite' : 'favorite';
                $fill = $isFavorited ? "'FILL' 1" : "'FILL' 0";
                $colorClass = $isFavorited ? 'text-red-500' : 'text-white';
                $countHtml = $favCount > 0 ? "<span class='text-xs ml-0.5'>$favCount</span>" : '';
                $favClass = $isFavorited ? 'favorited' : '';
                $btn = "<button type='button' class='favorite-btn w-9 h-9 rounded-full bg-white/20 backdrop-blur-sm flex items-center justify-center hover:bg-white/40 transition-colors $favClass' data-id='$id'>
                    <span class='material-symbols-outlined text-sm $colorClass' style='font-variation-settings: $fill'>$heartIcon</span>$countHtml
                </button>";
            }
        } elseif ($type === 'profile') {
            $btn = "<div class='flex gap-2'>
                <button type='button' class='edit-gambar-btn w-9 h-9 rounded-full bg-white/20 backdrop-blur-sm flex items-center justify-center hover:bg-white/40 transition-colors' data-gambar-id='$id'>
                    <span class='material-symbols-outlined text-sm text-white'>edit</span>
                </button>
                <button type='button' class='delete-gambar-btn w-9 h-9 rounded-full bg-red-500/30 backdrop-blur-sm flex items-center justify-center hover:bg-red-500/50 transition-colors' data-gambar-id='$id'>
                    <span class='material-symbols-outlined text-sm text-white'>delete</span>
                </button>
            </div>";
        } elseif ($type === 'favorite') {
            $fid = htmlspecialchars($row['favorite_id']);
            $btn = "<button type='button' class='delete-favorite-btn w-9 h-9 rounded-full bg-red-500/30 backdrop-blur-sm flex items-center justify-center hover:bg-red-500/50 transition-colors' data-favorite-id='$fid'>
                <span class='material-symbols-outlined text-sm text-white'>favorite</span>
            </button>";
        }

        echo "<div class='masonry-item group relative bg-surface-container-lowest border border-outline-variant/50 rounded-xl overflow-hidden shadow-sm hover:shadow-2xl transition-all duration-500 ease-out'>
                <div class='img-wrap relative overflow-hidden cursor-pointer imageLink' data-id='$id'>
                    <img src='$imgSrc' class='w-full h-full object-cover transition-transform duration-700 group-hover:scale-110' loading='lazy'/>
                    <div class='absolute inset-0 bg-gradient-to-t from-black/70 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-500 flex flex-col justify-end p-5'>
                        <div class='flex justify-end transform translate-y-4 group-hover:translate-y-0 transition-transform duration-500'>
                            $btn
                        </div>
                    </div>
                </div>
                <div class='p-4 transform transition-all duration-500 group-hover:-translate-y-1'>
                    <h3 class='font-semibold text-sm text-on-surface truncate'>$judul</h3>
                    <p class='text-xs text-on-surface-variant mt-1 opacity-0 group-hover:opacity-100 transition-opacity duration-500'>@$owner</p>
                </div>
              </div>";
    }
}
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <meta name="theme-color" content="#0f172a"/>
    <title>Lumina - Gallery</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL@20..48,100..700,0..1&display=swap" rel="stylesheet"/>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Inter', 'sans-serif'] },
                    colors: {
                        primary: '#0f172a',
                        surface: '#f7f9fb',
                        'surface-dim': '#d8dadc',
                        'surface-container-lowest': '#ffffff',
                        'surface-container-low': '#f2f4f6',
                        'surface-container': '#eceef0',
                        'surface-container-high': '#e6e8ea',
                        'on-surface': '#0f172a',
                        'on-surface-variant': '#475569',
                        'outline': '#94a3b8',
                        'outline-variant': '#cbd5e1',
                    },
                    spacing: {
                        'margin-x': '64px',
                        'container-max': '1440px',
                    }
                }
            }
        }
    </script>
    <style>
        body { font-family: 'Inter', sans-serif; }
        .masonry-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 16px;
        }
        @media (min-width: 768px) { .masonry-grid { grid-template-columns: repeat(3, 1fr); gap: 20px; } }
        @media (min-width: 1024px) { .masonry-grid { grid-template-columns: repeat(4, 1fr); gap: 24px; } }
        .masonry-item { break-inside: avoid; }
        .masonry-item .img-wrap { aspect-ratio: 4/3; }
        .modal-backdrop { background: rgba(15,23,42,0.5); backdrop-filter: blur(4px); }
        .auth-card-blur { backdrop-filter: blur(16px); }
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
        .nav-active { border-bottom: 2px solid #0f172a; color: #0f172a !important; }
        .hidden-section { display: none; }
        .nav-link { transition: all 0.2s; }
        .toast-enter { animation: slideIn 0.35s ease-out; }
        .toast-exit { animation: slideOut 0.3s ease-in forwards; }
        @keyframes slideIn { from { transform: translateX(100%); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
        @keyframes slideOut { from { transform: translateX(0); opacity: 1; } to { transform: translateX(100%); opacity: 0; } }
        @keyframes clickPop { 0% { transform: scale(1); } 40% { transform: scale(0.96); } 100% { transform: scale(1); } }
        .click-effect { animation: clickPop 0.25s ease-out; }
        @keyframes skeleton { 0% { background-position: 200% 0; } 100% { background-position: -200% 0; } }
        .skeleton { background: linear-gradient(90deg, #e2e8f0 25%, #f1f5f9 50%, #e2e8f0 75%); background-size: 200% 100%; animation: skeleton 1.5s infinite; }
    </style>
</head>
<body class="bg-surface text-on-surface font-sans antialiased selection:bg-primary/10 selection:text-primary">

<!-- Header -->
<header class="fixed top-0 w-full h-16 bg-surface-container-lowest/80 backdrop-blur-md border-b border-outline-variant/30 z-50">
    <div class="flex items-center justify-between h-full px-4 md:px-margin-x max-w-container-max mx-auto">
        <a href="#" id="homeLink" class="flex items-center gap-2 nav-link">
            <span class="material-symbols-outlined text-primary text-2xl">grid_view</span>
            <span class="font-bold text-lg text-primary">Lumina</span>
        </a>
        <div class="hidden md:flex items-center gap-6">
            <a href="#" id="homeLink2" class="text-sm font-medium text-primary border-b-2 border-primary pb-0.5 nav-link">Explore</a>
            <a href="#" id="favoriteLinkNav" class="text-sm font-medium text-on-surface-variant hover:text-primary transition-colors nav-link">Favorites</a>
            <a href="#" id="profileLinkNav" class="text-sm font-medium text-on-surface-variant hover:text-primary transition-colors nav-link">Profile</a>
        </div>
        <div class="flex items-center gap-3">
            <form action="index.php" method="get" class="relative hidden sm:block" role="search">
                <label for="searchInput" class="sr-only">Search images</label>
                <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-outline text-lg pointer-events-none" aria-hidden="true">search</span>
                <input type="text" id="searchInput" name="cari" value="<?=htmlspecialchars($search)?>" placeholder="Search..." class="h-9 pl-9 pr-3 text-sm bg-surface-container-low border-none rounded-full focus:ring-2 focus:ring-primary/30 w-48 lg:w-64 transition-all"/>
            </form>
            <?php if ($username): ?>
                <button id="newLink" class="flex items-center justify-center w-9 h-9 rounded-full bg-primary text-white hover:opacity-90 transition-opacity">
                    <span class="material-symbols-outlined text-lg">add</span>
                </button>
                <button id="logoutBtn" class="flex items-center justify-center w-9 h-9 rounded-full bg-surface-container-high text-on-surface-variant hover:bg-red-500 hover:text-white transition-all" title="Logout">
                    <span class="material-symbols-outlined text-lg">logout</span>
                </button>
            <?php else: ?>
                <button onclick="openModal('loginModal')" class="text-sm font-medium text-primary px-4 py-1.5 hover:opacity-80 transition-opacity">Sign In</button>
                <button onclick="openModal('registerModal')" class="text-sm font-medium bg-primary text-white px-4 py-1.5 rounded-lg hover:opacity-90 transition-opacity">Register</button>
            <?php endif; ?>
        </div>
    </div>
</header>

<main class="pt-20 pb-24 md:pb-12 px-4 md:px-margin-x max-w-container-max mx-auto">

    <!-- Home Section -->
    <div id="homeSection">
        <div class="masonry-grid"><?php renderCards($images, 'home', $username, $favoritedIds); ?></div>
        <div id="loadingStatus" class="flex justify-center py-12">
            <div class="flex flex-col items-center gap-2">
                <div class="w-12 h-12 border-4 border-primary/10 border-t-primary rounded-full animate-spin"></div>
                <p class="text-sm text-on-surface-variant font-medium">Memuat karya seni...</p>
            </div>
        </div>
    </div>

    <!-- Profile Section -->
    <div id="profileSection" class="hidden">
        <div class="max-w-3xl mx-auto">
            <div class="flex flex-col items-center mb-6">
                <div class="w-20 h-20 rounded-full bg-surface-container-high flex items-center justify-center mb-3">
                    <span class="material-symbols-outlined text-4xl text-on-surface-variant">person</span>
                </div>
                <h2 class="text-xl font-bold"><?=htmlspecialchars($username)?></h2>
                <?php
                $countStmt = $koneksi->prepare("SELECT COUNT(*) as total FROM tb_gambar WHERE username = ?");
                $countStmt->bind_param("s", $username);
                $countStmt->execute();
                $countRow = $countStmt->get_result()->fetch_assoc();
                $countStmt->close();
                ?>
                <p class="text-sm text-on-surface-variant mt-1"><?=$countRow['total']?> uploads</p>
            </div>
            <div class="flex justify-center border-b border-outline-variant mb-6">
                <button class="pb-3 px-4 text-sm font-medium text-primary border-b-2 border-primary flex items-center gap-2">
                    <span class="material-symbols-outlined text-lg">grid_on</span>
                    My Uploads
                </button>
            </div>
            <div class="masonry-grid">
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
    </div>

    <!-- Favorite Section -->
    <div id="favoriteSection" class="hidden">
        <div class="text-center mb-6"><h2 class="text-xl font-bold">Favorites</h2></div>
        <div class="masonry-grid">
            <?php
            if ($username) {
                $stmt = $koneksi->prepare("SELECT g.id, g.judul, g.keterangan, f.id as favorite_id FROM tb_gambar g INNER JOIN favorit f ON g.id = f.id_gambar WHERE f.id_user = (SELECT id_user FROM admin WHERE username = ?)");
                $stmt->bind_param("s", $username);
                $stmt->execute();
                renderCards($stmt->get_result(), 'favorite', $username);
                $stmt->close();
            } else {
                echo "<div class='col-span-full text-center py-12'><p class='text-on-surface-variant text-lg'>Please sign in to view favorites</p></div>";
            }
            ?>
        </div>
    </div>

    <!-- Footer -->
    <footer class="hidden md:block w-full border-t border-outline-variant/30 bg-surface-container-lowest mt-12">
        <div class="px-4 md:px-margin-x max-w-container-max mx-auto py-8 flex justify-center items-center gap-6">
            <p class="text-sm text-on-surface-variant">&copy; 2024 Lumina. All rights reserved.</p>
            <div class="flex gap-4 text-sm text-on-surface-variant">
                <a href="#" class="hover:text-primary transition-colors">Privacy</a>
                <a href="#" class="hover:text-primary transition-colors">Terms</a>
                <a href="#" class="hover:text-primary transition-colors">Contact</a>
            </div>
        </div>
    </footer>

    <!-- Image Detail Section -->
    <div id="imageSection" class="hidden">
        <div class="max-w-4xl mx-auto">
            <div id="imageDetail" class="bg-surface-container-lowest border border-outline-variant/50 rounded-xl overflow-hidden"></div>
        </div>
    </div>

    <!-- Upload Section -->
    <div id="newSection" class="hidden">
        <div class="max-w-lg mx-auto">
            <div class="bg-surface-container-lowest border border-outline-variant/50 rounded-xl p-6">
                <h2 class="text-xl font-bold mb-4">Upload New Image</h2>
                <?php if (isset($upload_error)): ?>
                    <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-2 rounded-lg text-sm mb-4"><?=$upload_error?></div>
                <?php endif; ?>
                <form action="" method="POST" enctype="multipart/form-data" class="space-y-4">
                    <div>
                        <label for="uploadGambar" class="block text-sm font-medium text-on-surface mb-1">Image</label>
                        <input type="file" name="gambar" class="block w-full text-sm text-on-surface file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-primary file:text-white hover:file:opacity-90" id="uploadGambar" required onchange="previewImage(this)">
                        <div class="mt-2 preview-wrap hidden">
                            <img src="#" alt="Preview" class="max-h-48 rounded-lg border border-outline-variant"/>
                        </div>
                    </div>
                    <div>
                        <label for="uploadJudul" class="block text-sm font-medium text-on-surface mb-1">Title</label>
                        <input type="text" name="judul" id="uploadJudul" class="w-full h-10 px-3 text-sm border border-outline-variant rounded-lg focus:ring-2 focus:ring-primary/30 focus:border-primary outline-none" required>
                    </div>
                    <div>
                        <label for="uploadKet" class="block text-sm font-medium text-on-surface mb-1">Description</label>
                        <textarea name="keterangan" id="uploadKet" class="w-full px-3 py-2 text-sm border border-outline-variant rounded-lg focus:ring-2 focus:ring-primary/30 focus:border-primary outline-none" rows="3" required></textarea>
                    </div>
                    <button type="submit" name="input" class="w-full h-10 bg-primary text-white text-sm font-medium rounded-lg hover:opacity-90 transition-opacity">Upload</button>
                </form>
            </div>
        </div>
    </div>

</main>

<!-- Footer -->
    <!-- Footer -->
    <footer class="hidden md:block w-full border-t border-outline-variant/30 bg-surface-container-lowest">
        <div class="px-4 md:px-margin-x max-w-container-max mx-auto py-8 flex justify-center items-center gap-6">
            <p class="text-sm text-on-surface-variant">&copy; 2024 Lumina. All rights reserved.</p>
            <div class="flex gap-4 text-sm text-on-surface-variant">
                <a href="#" class="hover:text-primary transition-colors">Privacy</a>
                <a href="#" class="hover:text-primary transition-colors">Terms</a>
                <a href="#" class="hover:text-primary transition-colors">Contact</a>
            </div>
        </div>
    </footer>

<!-- Scroll to Top -->
<button id="scrollTopBtn" class="fixed bottom-6 right-6 w-11 h-11 bg-primary text-white rounded-xl shadow-lg flex items-center justify-center z-40 opacity-0 invisible transition-all hover:scale-110 active:scale-95">
    <span class="material-symbols-outlined">arrow_upward</span>
</button>

<!-- Bottom Nav (Mobile) -->
<nav class="fixed bottom-0 w-full flex justify-around py-2 bg-surface-container-lowest border-t border-outline-variant/30 md:hidden z-50">
    <button id="navHome" class="flex flex-col items-center p-1 text-primary">
        <span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 1">home</span>
        <span class="text-[10px] font-medium">Explore</span>
    </button>
    <button id="navFavorite" class="flex flex-col items-center p-1 text-on-surface-variant">
        <span class="material-symbols-outlined">favorite</span>
        <span class="text-[10px] font-medium">Favorites</span>
    </button>
    <button id="navUpload" class="flex flex-col items-center p-1 text-on-surface-variant">
        <span class="material-symbols-outlined">add_box</span>
        <span class="text-[10px] font-medium">Upload</span>
    </button>
    <button id="navProfile" class="flex flex-col items-center p-1 text-on-surface-variant">
        <span class="material-symbols-outlined">person</span>
        <span class="text-[10px] font-medium">Profile</span>
    </button>
</nav>

<!-- Login Modal -->
<div id="loginModal" class="fixed inset-0 z-50 hidden">
    <div class="modal-backdrop absolute inset-0" onclick="closeModal('loginModal')"></div>
    <div class="absolute inset-0 flex items-center justify-center p-4">
        <div class="w-full max-w-md bg-surface-container-lowest border border-outline-variant/50 rounded-xl shadow-xl p-6 auth-card-blur">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-lg font-bold">Sign In</h2>
                <button onclick="closeModal('loginModal')" class="w-8 h-8 flex items-center justify-center rounded-full hover:bg-surface-container-low transition-colors">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>
            <form id="loginForm" class="space-y-4">
                <div>
                    <label for="login-username" class="block text-sm font-medium text-on-surface mb-1">Username</label>
                    <input type="text" id="login-username" name="username" autocomplete="username" class="w-full h-10 px-3 text-sm border border-outline-variant rounded-lg focus:ring-2 focus:ring-primary/30 focus:border-primary outline-none" required>
                </div>
                <div>
                    <label for="loginPassword" class="block text-sm font-medium text-on-surface mb-1">Password</label>
                    <div class="relative">
                        <input type="password" name="password" autocomplete="current-password" class="w-full h-10 px-3 text-sm border border-outline-variant rounded-lg focus:ring-2 focus:ring-primary/30 focus:border-primary outline-none" id="loginPassword" required>
                        <button type="button" class="absolute right-2 top-1/2 -translate-y-1/2 p-1.5 flex items-center justify-center text-on-surface-variant hover:text-primary transition-colors" onclick="togglePassword('loginPassword', this)">
                            <svg class="w-5 h-5 show-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                            <svg class="w-5 h-5 hide-icon hidden" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line></svg>
                        </button>
                    </div>
                </div>
                <button type="submit" id="loginBtn" class="w-full h-10 bg-primary text-white text-sm font-medium rounded-lg hover:opacity-90 transition-opacity">Sign In</button>
            </form>
            <p class="text-center text-sm text-on-surface-variant mt-4">
                Don't have an account? <a href="javascript:void(0)" class="text-primary font-medium hover:underline" onclick="closeModal('loginModal');openModal('registerModal')">Register</a>
            </p>
        </div>
    </div>
</div>

<!-- Register Modal -->
<div id="registerModal" class="fixed inset-0 z-50 hidden">
    <div class="modal-backdrop absolute inset-0" onclick="closeModal('registerModal')"></div>
    <div class="absolute inset-0 flex items-center justify-center p-4">
        <div class="w-full max-w-md bg-surface-container-lowest border border-outline-variant/50 rounded-xl shadow-xl p-6 auth-card-blur">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-lg font-bold">Register</h2>
                <button onclick="closeModal('registerModal')" class="w-8 h-8 flex items-center justify-center rounded-full hover:bg-surface-container-low transition-colors">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>
            <form id="registerForm" class="space-y-4">
                <div>
                    <label for="reg-username" class="block text-sm font-medium text-on-surface mb-1">Username</label>
                    <input type="text" id="reg-username" name="username" autocomplete="username" class="w-full h-10 px-3 text-sm border border-outline-variant rounded-lg focus:ring-2 focus:ring-primary/30 focus:border-primary outline-none" required>
                </div>
                <div>
                    <label for="registerPassword" class="block text-sm font-medium text-on-surface mb-1">Password</label>
                    <div class="relative">
                        <input type="password" name="password" autocomplete="new-password" class="w-full h-10 px-3 text-sm border border-outline-variant rounded-lg focus:ring-2 focus:ring-primary/30 focus:border-primary outline-none" id="registerPassword" required>
                        <button type="button" class="absolute right-2 top-1/2 -translate-y-1/2 p-1.5 flex items-center justify-center text-on-surface-variant hover:text-primary transition-colors" onclick="togglePassword('registerPassword', this)">
                            <svg class="w-5 h-5 show-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                            <svg class="w-5 h-5 hide-icon hidden" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line></svg>
                        </button>
                    </div>
                </div>
                <button type="submit" id="registerBtn" class="w-full h-10 bg-primary text-white text-sm font-medium rounded-lg hover:opacity-90 transition-opacity">Register</button>
            </form>
            <p class="text-center text-sm text-on-surface-variant mt-4">
                Already have an account? <a href="javascript:void(0)" class="text-primary font-medium hover:underline" onclick="closeModal('registerModal');openModal('loginModal')">Sign In</a>
            </p>
        </div>
    </div>
</div>

<!-- Edit Modal -->
<div id="editModal" class="fixed inset-0 z-50 hidden">
    <div class="modal-backdrop absolute inset-0" onclick="closeModal('editModal')"></div>
    <div class="absolute inset-0 flex items-center justify-center p-4">
        <div class="w-full max-w-lg bg-surface-container-lowest border border-outline-variant/50 rounded-xl shadow-xl p-6">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-lg font-bold">Edit Image</h2>
                <button onclick="closeModal('editModal')" class="w-8 h-8 flex items-center justify-center rounded-full hover:bg-surface-container-low transition-colors">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>
            <form id="editForm" enctype="multipart/form-data" class="space-y-4">
                <input type="hidden" name="id" id="editId">
                <div class="text-center">
                    <img id="editCurrentImage" src="#" alt="Current" class="max-h-40 rounded-lg border border-outline-variant hidden mx-auto"/>
                </div>
                <div>
                    <label for="editJudul" class="block text-sm font-medium text-on-surface mb-1">Title</label>
                    <input type="text" class="w-full h-10 px-3 text-sm border border-outline-variant rounded-lg focus:ring-2 focus:ring-primary/30 focus:border-primary outline-none" id="editJudul" name="judul" required>
                </div>
                <div>
                    <label for="editKeterangan" class="block text-sm font-medium text-on-surface mb-1">Description</label>
                    <textarea class="w-full px-3 py-2 text-sm border border-outline-variant rounded-lg focus:ring-2 focus:ring-primary/30 focus:border-primary outline-none" id="editKeterangan" name="keterangan" rows="3" required></textarea>
                </div>
                <div>
                    <label for="editGambar" class="block text-sm font-medium text-on-surface mb-1">New Image (optional)</label>
                    <input type="file" id="editGambar" class="block w-full text-sm text-on-surface file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-primary file:text-white hover:file:opacity-90" name="gambar" onchange="previewImage(this)">
                    <div class="mt-2 previewContainerEdit hidden">
                        <img id="previewImgEdit" src="#" alt="Preview" class="max-h-40 rounded-lg border border-outline-variant"/>
                    </div>
                </div>
                <div class="flex gap-3">
                    <button type="button" class="flex-1 h-10 border border-outline-variant text-sm font-medium rounded-lg hover:bg-surface-container-low transition-colors" onclick="closeModal('editModal')">Cancel</button>
                    <button type="submit" class="flex-1 h-10 bg-primary text-white text-sm font-medium rounded-lg hover:opacity-90 transition-opacity">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div id="toastContainer" class="fixed top-20 right-4 z-[100] flex flex-col gap-2 pointer-events-none"></div>

<script>
// Toast notification system
function showToast(message, type) {
    type = type || 'info';
    var container = document.getElementById('toastContainer');
    var toast = document.createElement('div');
    var colors = { success: 'bg-green-600', error: 'bg-red-600', info: 'bg-primary' };
    var icons = { success: 'check_circle', error: 'error', info: 'info' };
    toast.className = 'toast-enter pointer-events-auto flex items-center gap-3 px-4 py-3 rounded-xl shadow-lg text-white text-sm font-medium ' + (colors[type] || colors.info);
    toast.innerHTML = '<span class="material-symbols-outlined text-lg">' + (icons[type] || icons.info) + '</span><span>' + message + '</span><button class="ml-2 hover:opacity-70 transition-opacity" onclick="this.closest(\'div\').classList.add(\'toast-exit\');setTimeout(function(){this.closest(\'div\').remove()}.bind(this),300)"><span class="material-symbols-outlined text-base">close</span></button>';
    container.appendChild(toast);
    setTimeout(function() {
        if (toast.parentNode) {
            toast.classList.remove('toast-enter');
            toast.classList.add('toast-exit');
            setTimeout(function() { if (toast.parentNode) toast.remove(); }, 300);
        }
    }, 3500);
}

// Section navigation
function showSection(id) {
    var allSections = ['#homeSection', '#profileSection', '#favoriteSection', '#imageSection', '#newSection'];
    allSections.forEach(function(sel) {
        var el = document.querySelector(sel);
        if (!el) return;
        if (sel.replace('#', '') === id) {
            el.classList.remove('hidden');
        } else {
            el.classList.add('hidden');
        }
    });
}

// Ensure homeSection is visible on every page load
document.addEventListener('DOMContentLoaded', function() {
    showSection('homeSection');
    setActiveMobile(document.getElementById('navHome'));
});

function setActiveMobile(el) {
    document.querySelectorAll('nav button').forEach(function(b) {
        b.classList.remove('text-primary');
        b.classList.add('text-on-surface-variant');
        var icon = b.querySelector('.material-symbols-outlined');
        if (icon) icon.style.fontVariationSettings = "'FILL' 0";
    });
    el.classList.remove('text-on-surface-variant');
    el.classList.add('text-primary');
    var icon = el.querySelector('.material-symbols-outlined');
    if (icon) icon.style.fontVariationSettings = "'FILL' 1";
}

// Modal controls
function openModal(id) { document.getElementById(id).classList.remove('hidden'); }
function closeModal(id) { document.getElementById(id).classList.add('hidden'); }

// Password toggle with SVG icons
function togglePassword(inputId, btn) {
    var input = document.getElementById(inputId);
    var showIcon = btn.querySelector('.show-icon');
    var hideIcon = btn.querySelector('.hide-icon');
    if (input.type === 'password') {
        input.type = 'text';
        showIcon.classList.add('hidden');
        hideIcon.classList.remove('hidden');
    } else {
        input.type = 'password';
        showIcon.classList.remove('hidden');
        hideIcon.classList.add('hidden');
    }
}

// Image preview
function previewImage(input) {
    var container = input.closest('div').querySelector('.preview-wrap, .previewContainerEdit');
    if (!container) return;
    var img = container.querySelector('img');
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) { img.src = e.target.result; container.classList.remove('hidden'); };
        reader.readAsDataURL(input.files[0]);
    } else { container.classList.add('hidden'); img.src = '#'; }
}

// Scroll to top
var scrollBtn = document.getElementById('scrollTopBtn');
window.addEventListener('scroll', function() {
    if (window.scrollY > 300) {
        scrollBtn.classList.remove('opacity-0', 'invisible');
        scrollBtn.classList.add('opacity-100', 'visible');
    } else {
        scrollBtn.classList.add('opacity-0', 'invisible');
        scrollBtn.classList.remove('opacity-100', 'visible');
    }
});
scrollBtn.addEventListener('click', function() { window.scrollTo({ top: 0, behavior: 'smooth' }); });

// Updated Nav Clicks
function navigateTo(sectionId, el) {
    showSection(sectionId);
    document.querySelectorAll('.nav-link').forEach(link => {
        link.classList.remove('nav-active', 'border-b-2', 'border-primary', 'text-primary');
        link.classList.add('text-on-surface-variant');
    });
    el.classList.add('nav-active', 'border-b-2', 'border-primary', 'text-primary');
    el.classList.remove('text-on-surface-variant');
}

document.getElementById('homeLink').addEventListener('click', function(e) { e.preventDefault(); navigateTo('homeSection', document.getElementById('homeLink2')); });
document.getElementById('homeLink2').addEventListener('click', function(e) { e.preventDefault(); navigateTo('homeSection', this); });

document.getElementById('profileLinkNav').addEventListener('click', function(e) { 
    e.preventDefault(); 
    <?php if ($username): ?> navigateTo('profileSection', this);
    <?php else: ?> openModal('loginModal'); <?php endif; ?>
});

document.getElementById('favoriteLinkNav').addEventListener('click', function(e) { 
    e.preventDefault(); 
    <?php if ($username): ?> navigateTo('favoriteSection', this);
    <?php else: ?> openModal('loginModal'); <?php endif; ?>
});

document.getElementById('newLink')?.addEventListener('click', function(e) { 
    e.preventDefault(); 
    <?php if ($username): ?> navigateTo('newSection', this);
    <?php else: ?> openModal('loginModal'); <?php endif; ?>
});

// Add nav click listeners for mobile
document.getElementById('navHome').addEventListener('click', function(e) { navigateTo('homeSection', document.getElementById('homeLink2')); setActiveMobile(this); });
document.getElementById('navFavorite').addEventListener('click', function(e) { 
    <?php if ($username): ?> navigateTo('favoriteSection', document.getElementById('favoriteLinkNav')); setActiveMobile(this);
    <?php else: ?> openModal('loginModal'); <?php endif; ?>
});
document.getElementById('navUpload').addEventListener('click', function(e) { 
    <?php if ($username): ?> navigateTo('newSection', document.getElementById('newLink')); setActiveMobile(this);
    <?php else: ?> openModal('loginModal'); <?php endif; ?>
});
document.getElementById('navProfile').addEventListener('click', function(e) { 
    <?php if ($username): ?> navigateTo('profileSection', document.getElementById('profileLinkNav')); setActiveMobile(this);
    <?php else: ?> openModal('loginModal'); <?php endif; ?>
});
// Favorite toggle
document.addEventListener('click', function(e) {
    var btn = e.target.closest('.favorite-btn');
    if (!btn) return;
    var id = btn.getAttribute('data-id');
    fetch('api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'action=favorit&id=' + id
    })
    .then(function(r) { return r.json(); })
    .then(function(r) {
        if (r.status === 'success') {
            var icon = btn.querySelector('.material-symbols-outlined');
            var countEl = btn.querySelector('span:not(.material-symbols-outlined)');
            if (r.action === 'added') {
                icon.style.fontVariationSettings = "'FILL' 1";
                icon.classList.add('text-red-500');
                icon.classList.remove('text-white');
                btn.classList.add('favorited');
            } else {
                icon.style.fontVariationSettings = "'FILL' 0";
                icon.classList.remove('text-red-500');
                icon.classList.add('text-white');
                btn.classList.remove('favorited');
            }
        }
        showToast(r.message, r.status === 'success' ? 'success' : 'error');
    })
    .catch(function() { showToast('Terjadi kesalahan', 'error'); });
});

// Delete gambar
document.addEventListener('click', function(e) {
    var btn = e.target.closest('.delete-gambar-btn');
    if (!btn) return;
    if (!confirm('Hapus gambar ini?')) return;
    var id = btn.getAttribute('data-gambar-id');
    fetch('api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'action=delete_gambar&id_gambar=' + id
    })
    .then(function(r) { return r.json(); })
    .then(function(r) {
        showToast(r.message || (r.status === 'success' ? 'Gambar dihapus!' : 'Gagal menghapus'), r.status === 'success' ? 'success' : 'error');
        if (r.status === 'success') setTimeout(function(){location.reload()}, 1500);
    })
    .catch(function() { showToast('Terjadi kesalahan', 'error'); });
});

// Delete favorite
document.addEventListener('click', function(e) {
    var btn = e.target.closest('.delete-favorite-btn');
    if (!btn) return;
    if (!confirm('Hapus dari favorit?')) return;
    var id = btn.getAttribute('data-favorite-id');
    fetch('api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'action=delete_favorit&id_favorite=' + id
    })
    .then(function(r) { return r.json(); })
    .then(function(r) {
        showToast(r.message || (r.status === 'success' ? 'Favorit dihapus!' : 'Gagal menghapus'), r.status === 'success' ? 'success' : 'error');
        if (r.status === 'success') setTimeout(function(){location.reload()}, 1500);
    })
    .catch(function() { showToast('Terjadi kesalahan', 'error'); });
});

// Image detail
document.addEventListener('click', function(e) {
    var el = e.target.closest('.imageLink');
    if (!el) return;
    var id = el.getAttribute('data-id');
    showSection('imageSection');
    fetch('api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'action=get_image_details&id=' + id
    })
    .then(function(r) { return r.json(); })
    .then(function(r) {
        var container = document.getElementById('imageDetail');
        if (r.error) {
            container.innerHTML = '<div class="p-6 text-center"><p class="text-red-500">' + r.error.replace(/</g, "&lt;") + '</p></div>';
        } else {
            var favHtml = parseInt(r.fav_count) > 0 ? ' <span class="text-red-500 text-sm">♥ ' + r.fav_count + '</span>' : '';
            var safeJudul = r.judul.replace(/</g, "&lt;");
            var safeKet = r.keterangan.replace(/</g, "&lt;");
            var safeUser = r.username.replace(/</g, "&lt;");
            container.innerHTML =
                '<div class="p-4 border-b border-outline-variant/30">' +
                    '<button class="text-sm text-on-surface-variant hover:text-primary transition-colors flex items-center gap-1" onclick="showSection(\'homeSection\')">' +
                        '<span class="material-symbols-outlined text-lg">arrow_back</span> Back' +
                    '</button>' +
                '</div>' +
                '<div class="p-6 text-center">' +
                    '<img src="img_view.php?id=' + r.id + '" class="max-h-[70vh] mx-auto rounded-lg object-contain"/>' +
                    '<h3 class="text-xl font-bold mt-4">' + safeJudul + favHtml + '</h3>' +
                    '<p class="text-on-surface-variant mt-2">' + safeKet + '</p>' +
                    '<p class="text-sm text-on-surface-variant mt-3">by <span class="text-primary font-medium">' + safeUser + '</span></p>' +
                '</div>';
        }
    })
    .catch(function() { document.getElementById('imageDetail').innerHTML = '<div class="p-6 text-center"><p class="text-red-500">Gagal memuat gambar</p></div>'; });
});

// Edit gambar
document.addEventListener('click', function(e) {
    var btn = e.target.closest('.edit-gambar-btn');
    if (!btn) return;
    var id = btn.getAttribute('data-gambar-id');
    fetch('api.php?action=get_image_data&id=' + id)
    .then(function(r) { return r.json(); })
    .then(function(r) {
        if (r.error) { showToast(r.error, 'error'); return; }
        document.getElementById('editId').value = r.id;
        document.getElementById('editJudul').value = r.judul;
        document.getElementById('editKeterangan').value = r.keterangan;
        var img = document.getElementById('editCurrentImage');
        img.src = 'img_view.php?id=' + r.id;
        img.classList.remove('hidden');
        document.querySelector('.previewContainerEdit')?.classList.add('hidden');
        openModal('editModal');
    })
    .catch(function() { showToast('Gagal memuat data gambar', 'error'); });
});

// AJAX Login & Register
function checkSessionAndReload() {
    fetch('auth.php?action=check_session').then(r => r.json()).then(r => {
        if (r.username) location.reload();
        else { showToast('Session tidak tersimpan, coba lagi', 'error'); }
    }).catch(() => { location.reload(); });
}

document.getElementById('loginForm').addEventListener('submit', function(e) {
    e.preventDefault();
    fetch('auth.php?action=login', { method: 'POST', body: new FormData(this) })
    .then(r => r.json())
    .then(r => {
        showToast(r.message, r.status);
        if (r.status === 'success') setTimeout(checkSessionAndReload, 1000);
    })
    .catch(function() { showToast('Gagal menghubungi server', 'error'); });
});

document.getElementById('registerForm').addEventListener('submit', function(e) {
    e.preventDefault();
    fetch('auth.php?action=register', { method: 'POST', body: new FormData(this) })
    .then(r => r.json())
    .then(r => {
        showToast(r.message, r.status);
        if (r.status === 'success') setTimeout(checkSessionAndReload, 1000);
    })
    .catch(function() { showToast('Gagal menghubungi server', 'error'); });
});

// Logout handler
document.getElementById('logoutBtn')?.addEventListener('click', function() {
    if (!confirm('Logout?')) return;
    fetch('auth.php?action=logout')
    .then(r => r.json())
    .then(r => {
        showToast(r.message, 'success');
        if (r.status === 'success') setTimeout(() => location.reload(), 1500);
    })
    .catch(function() { showToast('Gagal logout', 'error'); });
});

// Edit form submit
document.getElementById('editForm').addEventListener('submit', function(e) {
    e.preventDefault();
    var fd = new FormData(this);
    fd.append('action', 'update');
    fetch('api.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(r => {
        showToast(r.message, r.status);
        if (r.status === 'success') {
            closeModal('editModal');
            setTimeout(() => location.reload(), 1500);
        }
    })
    .catch(() => showToast('Gagal mengedit gambar', 'error'));
});

// Loading status simulation
(function() {
    var status = document.getElementById('loadingStatus');
    if (!status) return;
    var count = 0;
    var interval = setInterval(function() {
        count++;
        if (count === 3) {
            clearInterval(interval);
            status.innerHTML = '<div class="flex flex-col items-center gap-2 opacity-50"><span class="material-symbols-outlined text-4xl">image_not_supported</span><p class="text-sm text-on-surface-variant font-medium">Sudah tidak ada gambar lagi</p></div>';
        }
    }, 1500);
})();
</script>
</body>
</html>
