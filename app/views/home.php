<?php
// Generate CSRF token if not exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Validate and sanitize input
$page = isset($_GET['page']) ? htmlspecialchars($_GET['page'], ENT_QUOTES, 'UTF-8') : 'home';

// Mock data for playlists
$playlists = [
    [
        'id' => 1, 
        'name' => 'Bài Hát Đã Thích', 
        'image' => '/uploads/playlists/liked-songs.jpg',
        'count' => 128,
        'type' => 'Playlist',
        'url' => '/uploads/songs/chung-ta-cua-hien-tai.mp3'
    ],
    [
        'id' => 2, 
        'name' => 'Khám Phá Hàng Tuần', 
        'image' => '/uploads/playlists/discover-weekly.jpg',
        'count' => 30,
        'type' => 'Playlist của Spotify'
    ],
    [
        'id' => 3, 
        'name' => 'Top Hits 2024', 
        'image' => '/uploads/playlists/top-hits-2024.jpg',
        'count' => 50,
        'type' => 'Playlist • Spotify'
    ],
    [
        'id' => 4, 
        'name' => 'V-Pop Chill', 
        'image' => '/uploads/playlists/vpop-chill.jpg',
        'count' => 45,
        'type' => 'Playlist • Spotify'
    ],
    [
        'id' => 5, 
        'name' => 'Workout Motivation', 
        'image' => '/uploads/playlists/workout.jpg',
        'count' => 32,
        'type' => 'Playlist'
    ],
    [
        'id' => 6, 
        'name' => 'Nhạc Vàng Bất Hủ', 
        'image' => '/uploads/playlists/nhac-vang.jpg',
        'count' => 67,
        'type' => 'Playlist • Việt Nam'
    ],
];

// Mock data for songs
$songs = [
    [
        'id' => 1, 
        'title' => 'Chúng Ta Của Hiện Tại', 
        'artist' => 'Sơn Tùng M-TP', 
        'album' => 'Sky Tour Movie', 
        'duration' => '4:45', 
        'image' => '/uploads/artists/son-tung.jpg',
        'url' => '/uploads/songs/chung-ta-cua-hien-tai.mp3'
    ],
    [
        'id' => 2,
        'title' => 'Waiting For You',
        'artist' => 'MONO',
        'album' => '22',
        'duration' => '4:05',
        'image' => '/uploads/artists/mono.jpg',
        'url' => '/uploads/songs/waiting-for-you.mp3'
    ]
];

// Mock data for artists
$artists = [
    [
        'id' => 1,
        'name' => 'Sơn Tùng M-TP',
        'image' => '/uploads/artists/son-tung.jpg',
        'followers' => '2.5M'
    ],
    [
        'id' => 2,
        'name' => 'Hòa Minzy',
        'image' => '/uploads/artists/hoa-minzy.jpg',
        'followers' => '1.2M'
    ],
    [
        'id' => 3,
        'name' => 'Jack',
        'image' => '/uploads/artists/jack.jpg',
        'followers' => '1.8M'
    ]
];

// Current song
$currentSong = [
    'title' => 'Chọn một bài hát',
    'artist' => 'Spotify',
    'image' => '/uploads/artists/son-tung.jpg',
    'duration' => '0:00'
];

// Error handling
function handleError($message) {
    return "<div role='alert' class='bg-red-500 text-white p-4 mb-4 rounded'>{$message}</div>";
}

// Validate playlist ID
$playlistId = isset($_GET['id']) ? (int)$_GET['id'] : 1;
if ($playlistId < 1 || $playlistId > count($playlists)) {
    echo handleError('Playlist không tồn tại');
    $playlistId = 1;
}

// Pagination logic
$totalItems = count($playlists);
$itemsPerPage = 10;
$totalPages = ceil($totalItems / $itemsPerPage);
$currentPage = isset($_GET['page_num']) ? max(1, min((int)$_GET['page_num'], $totalPages)) : 1;
$offset = ($currentPage - 1) * $itemsPerPage;

// Get items for current page
$currentItems = array_slice($playlists, $offset, $itemsPerPage);

// XSS protection when displaying data
function safeDisplay($data) {
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}

// Thêm CSRF token cho các form (nếu có)
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="description" content="Music streaming website">
    <title>Trang chủ - Spotify Clone</title>
    
    <!-- Preload quan trọng resources -->
    <link rel="preload" href="https://cdn.tailwindcss.com" as="script">
    <link rel="preload" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" as="style">
    
    <!-- Thêm nonce cho script -->
    <?php $nonce = base64_encode(random_bytes(16)); ?>
    <script nonce="<?= $nonce ?>" src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        spotify: {
                            green: '#1DB954',
                            black: '#191414',
                            darkgray: '#121212',
                            lightgray: '#282828',
                            white: '#FFFFFF',
                        }
                    }
                }
            }
        }
    </script>
    <style>
        body {
            font-family: 'Circular', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif;
        }
        .progress-bar {
            -webkit-appearance: none;
            appearance: none;
            height: 4px;
            width: 100%;
        }
        .progress-bar::-webkit-slider-thumb {
            -webkit-appearance: none;
            appearance: none;
            width: 12px;
            height: 12px;
            background: white;
            border-radius: 50%;
            cursor: pointer;
            opacity: 0;
            transition: opacity 0.2s;
        }
        .progress-container:hover .progress-bar::-webkit-slider-thumb {
            opacity: 1;
        }
    </style>
</head>
<body class="bg-spotify-black text-spotify-white">
    <div class="flex h-screen overflow-hidden">
        <!-- Sidebar -->
        <div class="w-64 bg-black flex-shrink-0 hidden md:block">
            <div class="p-6">
                <div class="mb-8">
                    <h1 class="text-2xl font-bold text-white mb-6">
                        <i class="fab fa-spotify mr-2"></i>Spotify
                    </h1>
                    <ul class="space-y-4">
                        <li class="flex items-center text-white font-semibold">
                            <i class="fas fa-home mr-4"></i>
                            <a href="?page=home">Trang chủ</a>
                        </li>
                        <li class="flex items-center text-gray-400 hover:text-white">
                            <i class="fas fa-search mr-4"></i>
                            <a href="?page=search">Tìm Kiếm</a>
                        </li>
                        <li class="flex items-center text-gray-400 hover:text-white">
                            <i class="fas fa-book mr-4"></i>
                            <a href="?page=library">Thư Viện</a>
                        </li>
                    </ul>
                </div>
                <div class="mb-8">
                    <div class="flex items-center mb-4">
                        <div class="w-8 h-8 bg-spotify-green flex items-center justify-center rounded-sm mr-3">
                            <i class="fas fa-plus text-black"></i>
                        </div>
                        <span class="font-semibold">Create Playlist</span>
                    </div>
                    <div class="flex items-center">
                        <div class="w-8 h-8 bg-gradient-to-br from-purple-700 to-gray-400 flex items-center justify-center rounded-sm mr-3">
                            <i class="fas fa-heart text-white"></i>
                        </div>
                        <span class="font-semibold">Liked Songs</span>
                    </div>
                </div>
                <div class="border-t border-gray-800 pt-4">
                    <ul class="text-sm text-gray-400">
                        <?php foreach($currentItems as $playlist): ?>
                        <li class="mb-2 hover:text-white">
                            <a href="?page=playlist&id=<?= $playlist['id'] ?>"><?= safeDisplay($playlist['name']) ?></a>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Top Navigation -->
            <div role="banner" class="bg-spotify-darkgray p-4 flex items-center justify-between">
                <div class="flex items-center">
                    <button class="w-8 h-8 rounded-full bg-black flex items-center justify-center mr-4 md:hidden">
                        <i class="fas fa-bars text-white"></i>
                    </button>
                    <div class="flex space-x-2">
                        <button class="w-8 h-8 rounded-full bg-black flex items-center justify-center">
                            <i class="fas fa-chevron-left text-white"></i>
                        </button>
                        <button class="w-8 h-8 rounded-full bg-black flex items-center justify-center">
                            <i class="fas fa-chevron-right text-white"></i>
                        </button>
                    </div>
                </div>
                <div class="flex items-center">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <div class="flex items-center mr-4 relative">
                            <button onclick="toggleDropdown()" class="flex items-center gap-2 p-2 rounded-full hover:bg-[#282828]">
                                <img src="<?= $_SESSION['user_avatar'] ?? '/placeholder.svg?height=32&width=32' ?>" 
                                     alt="Profile" 
                                     class="w-8 h-8 rounded-full object-cover">
                                <span class="text-sm text-gray-300">
                                    <?= htmlspecialchars($_SESSION['user_name'] ?? $_SESSION['user_email']) ?>
                                </span>
                                <i class="fas fa-chevron-down text-sm" id="dropdown-arrow"></i>
                            </button>

                            <!-- Dropdown Menu -->
                            <div id="dropdown-menu" class="hidden absolute right-0 top-full mt-2 w-48 bg-[#282828] rounded-md shadow-lg py-1">
                                <a href="?page=profile" class="block px-4 py-2 text-sm text-gray-300 hover:bg-[#333333]">
                                    <i class="fas fa-user mr-2"></i>
                                    Hồ sơ
                                </a>
                                <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] == 'admin'): ?>
                                    <a href="?page=admin" class="block px-4 py-2 text-sm text-gray-300 hover:bg-[#333333]">
                                        <i class="fas fa-cog mr-2"></i>
                                        Quản lý Admin
                                    </a>
                                <?php endif; ?>
                                <div class="border-t border-gray-700 my-1"></div>
                                <a href="?page=logout" class="block px-4 py-2 text-sm text-gray-300 hover:bg-[#333333]">
                                    <i class="fas fa-sign-out-alt mr-2"></i>
                                    Đăng xuất
                                </a>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="space-x-4">
                            <a href="?page=register" class="text-gray-400 hover:text-white">
                                Đăng ký
                            </a>
                            <a href="?page=login" class="bg-white text-black font-bold py-2 px-4 rounded-full">
                                Đăng nhập
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Content Area -->
            <main role="main" id="main-content" class="flex-1 overflow-y-auto bg-gradient-to-b from-[#3333aa]/30 to-spotify-darkgray p-8">
                <?php if (isset($_SESSION['success_message'])): ?>
                    <div class="fixed top-4 right-4 bg-green-500 text-white px-6 py-3 rounded-md shadow-lg z-50" id="success-message">
                        <?= htmlspecialchars($_SESSION['success_message']) ?>
                    </div>
                    <?php unset($_SESSION['success_message']); ?>
                    <script>
                        setTimeout(() => {
                            document.getElementById('success-message').style.display = 'none';
                        }, 3000);
                    </script>
                <?php endif; ?>
                <?php if($page === 'home'): ?>
                    <h1 class="text-2xl font-bold mb-6">
                        <?php 
                            $hour = date('H');
                            if($hour < 12) echo "Chào buổi sáng";
                            else if($hour < 18) echo "Chào buổi chiều";
                            else echo "Chào buổi tối";
                        ?>
                    </h1>
                    
                    <!-- Recently Played -->
                    <div class="grid grid-cols-2 lg:grid-cols-3 gap-4 mb-8">
                        <?php foreach (array_slice($playlists, 0, 6) as $playlist): ?>
                            <div class="bg-spotify-lightgray hover:bg-[#282828] transition-colors p-4 rounded-lg flex items-center gap-4 group">
                                <img src="<?= htmlspecialchars($playlist['image']) ?>"
                                     alt="<?= htmlspecialchars($playlist['name']) ?>" 
                                     class="w-[4.5rem] h-[4.5rem] rounded shadow-lg object-cover">
                                <div class="flex-1">
                                    <h2 class="font-bold truncate"><?= htmlspecialchars($playlist['name']) ?></h2>
                                    <p class="text-sm text-gray-400"><?= htmlspecialchars($playlist['type']) ?></p>
                                </div>
                                <button class="play-song w-12 h-12 rounded-full bg-spotify-green text-black flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity shadow-lg transform hover:scale-105"
                                        data-url="<?= htmlspecialchars($songs[0]['url']) ?>"
                                        data-title="<?= htmlspecialchars($songs[0]['title']) ?>"
                                        data-artist="<?= htmlspecialchars($songs[0]['artist']) ?>"
                                        data-image="<?= htmlspecialchars($songs[0]['image']) ?>">
                                    <i class="fas fa-play text-lg"></i>
                                </button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- Nghệ Sĩ Thịnh Hành -->
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-2xl font-bold">Nghệ Sĩ Thịnh Hành</h2>
                        <a href="?page=artists" class="text-sm font-bold text-gray-400 hover:text-white uppercase tracking-wider">Xem tất cả</a>
                    </div>
                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 xl:grid-cols-7 gap-6 mb-12">
                        <?php foreach ($artists as $artist): ?>
                            <div class="bg-spotify-lightgray p-4 rounded-lg hover:bg-[#282828] transition-colors group cursor-pointer relative">
                                <div class="relative mb-4">
                                    <a href="?page=artist&id=<?= $artist['id'] ?>" class="block">
                                        <img src="<?= htmlspecialchars($artist['image']) ?>" 
                                             alt="<?= htmlspecialchars($artist['name']) ?>" 
                                             class="w-full aspect-square rounded-full object-cover shadow-lg group-hover:shadow-xl transition-shadow">
                                    </a>
                                    <button class="play-song absolute bottom-2 right-2 w-10 h-10 rounded-full bg-spotify-green text-black flex items-center justify-center opacity-0 group-hover:opacity-100 transition-all transform translate-y-2 group-hover:translate-y-0 shadow-lg hover:scale-105"
                                            data-url="<?= htmlspecialchars($songs[0]['url']) ?>"
                                            data-title="<?= htmlspecialchars($songs[0]['title']) ?>"
                                            data-artist="<?= htmlspecialchars($artist['name']) ?>"
                                            data-image="<?= htmlspecialchars($artist['image']) ?>">
                                        <i class="fas fa-play"></i>
                                    </button>
                                </div>
                                <a href="?page=artist&id=<?= $artist['id'] ?>" class="block">
                                    <h3 class="font-bold truncate text-center"><?= htmlspecialchars($artist['name']) ?></h3>
                                    <p class="text-sm text-gray-400 text-center">Nghệ sĩ</p>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- Dành Cho Bạn -->
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-2xl font-bold">Dành Cho Bạn</h2>
                        <a href="?page=made-for-you" class="text-sm font-bold text-gray-400 hover:text-white uppercase tracking-wider">Xem tất cả</a>
                    </div>
                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 xl:grid-cols-7 gap-6">
                        <?php foreach ($playlists as $playlist): ?>
                            <div class="bg-spotify-lightgray p-4 rounded-lg hover:bg-[#282828] transition-all group cursor-pointer hover:shadow-xl relative">
                                <div class="relative mb-4">
                                    <a href="?page=playlist&id=<?= $playlist['id'] ?>" class="block">
                                        <img src="<?= htmlspecialchars($playlist['image']) ?>" 
                                             alt="<?= htmlspecialchars($playlist['name']) ?>" 
                                             class="w-full aspect-square rounded shadow-lg group-hover:shadow-xl transition-shadow">
                                    </a>
                                    <button class="play-song absolute bottom-4 right-4 w-12 h-12 rounded-full bg-spotify-green text-black flex items-center justify-center opacity-0 group-hover:opacity-100 transition-all transform translate-y-2 group-hover:translate-y-0 shadow-lg hover:scale-105"
                                            data-url="<?= htmlspecialchars($songs[0]['url']) ?>"
                                            data-title="<?= htmlspecialchars($playlist['name']) ?>"
                                            data-artist="Spotify"
                                            data-image="<?= htmlspecialchars($playlist['image']) ?>">
                                        <i class="fas fa-play text-lg"></i>
                                    </button>
                                </div>
                                <a href="?page=playlist&id=<?= $playlist['id'] ?>" class="block">
                                    <h3 class="font-bold truncate mb-1"><?= htmlspecialchars($playlist['name']) ?></h3>
                                    <p class="text-sm text-gray-400 line-clamp-2"><?= htmlspecialchars($playlist['type']) ?></p>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <section id="songs" class="mb-8">
                        <div class="flex justify-between items-center mb-6">
                            <h2 class="text-2xl font-bold">Danh sách bài hát</h2>
                            <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
                                <a href="?page=admin&section=songs" class="text-[#1DB954] hover:text-[#1ed760] transition-colors">
                                    <i class="fas fa-plus mr-2"></i>Thêm bài hát
                                </a>
                            <?php endif; ?>
                        </div>

                        <div class="bg-[#170f23] rounded-lg p-4">
                            <table class="w-full">
                                <thead>
                                    <tr class="text-gray-400 text-sm border-b border-gray-700">
                                        <th class="pb-3 w-[5%] font-normal text-left">#</th>
                                        <th class="pb-3 w-[45%] font-normal text-left">Tên bài hát</th>
                                        <th class="pb-3 w-[30%] font-normal text-left">Nghệ sĩ</th>
                                        <th class="pb-3 w-[10%] font-normal text-right">Lượt nghe</th>
                                        <th class="pb-3 w-[10%] font-normal text-right pr-8">Thời gian</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    require_once __DIR__ . '/../models/SongModel.php';
                                    $songModel = new SongModel();
                                    $songs = $songModel->getAllSongs();

                                    foreach ($songs as $index => $song):
                                    ?>
                                    <tr class="group hover:bg-[#2f2739] text-gray-400">
                                        <td class="py-[10px] px-2">
                                            <div class="flex items-center relative">
                                                <span class="text-gray-500 text-sm group-hover:hidden"><?= $index + 1 ?></span>
                                                <button class="play-song absolute left-0 hidden group-hover:block"
                                                        data-url="<?= htmlspecialchars($song['file_path']) ?>"
                                                        data-title="<?= htmlspecialchars($song['title']) ?>"
                                                        data-artist="<?= htmlspecialchars($song['artist']) ?>"
                                                        data-image="<?= htmlspecialchars($song['cover_image']) ?>">
                                                    <i class="fas fa-play text-white text-sm"></i>
                                                </button>
                                            </div>
                                        </td>
                                        <td class="py-[10px]">
                                            <div class="flex items-center">
                                                <img src="<?= htmlspecialchars($song['cover_image']) ?>" 
                                                     alt="<?= htmlspecialchars($song['title']) ?>" 
                                                     class="w-10 h-10 rounded mr-3">
                                                <div>
                                                    <div class="text-white text-sm font-medium">
                                                        <?= htmlspecialchars($song['title']) ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="py-[10px] text-sm"><?= htmlspecialchars($song['artist']) ?></td>
                                        <td class="py-[10px] text-right text-sm"><?= number_format($song['plays'] ?? 0) ?></td>
                                        <td class="py-[10px] text-right pr-8 text-sm">
                                            <div class="flex items-center justify-end space-x-3 opacity-0 group-hover:opacity-100 transition-opacity">
                                                <button class="text-gray-400 hover:text-white">
                                                    <i class="fas fa-microphone"></i>
                                                </button>
                                                <button class="text-gray-400 hover:text-white">
                                                    <i class="far fa-heart"></i>
                                                </button>
                                                <button class="text-gray-400 hover:text-white">
                                                    <i class="fas fa-ellipsis-h"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </section>
                <?php elseif($page == 'playlist'): ?>
                    <?php 
                    $playlistId = isset($_GET['id']) ? (int)$_GET['id'] : 1;
                    $playlist = $playlists[$playlistId - 1] ?? $playlists[0];
                    ?>
                    <div class="flex flex-col md:flex-row items-center md:items-end gap-6 mb-6">
                        <img src="<?= $playlist['image'] ?>" alt="Ảnh bìa playlist" class="w-48 h-48 md:w-60 md:h-60 shadow-2xl">
                        <div>
                            <div class="text-sm uppercase font-bold">Playlist</div>
                            <h1 class="text-3xl md:text-5xl font-bold mt-2 mb-4"><?= safeDisplay($playlist['name']) ?></h1>
                            <div class="text-sm text-gray-300">
                                <span class="font-semibold">Spotify</span> • <?= $playlist['count'] ?> bài hát, about 3 hr 45 min
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-[#121212]/30 py-4">
                        <div class="flex items-center gap-8 mb-6">
                            <button class="w-14 h-14 rounded-full bg-spotify-green text-black flex items-center justify-center shadow-lg">
                                <i class="fas fa-play text-2xl"></i>
                            </button>
                            <button class="text-3xl text-gray-400 hover:text-white">
                                <i class="far fa-heart"></i>
                            </button>
                            <button class="text-2xl text-gray-400 hover:text-white">
                                <i class="fas fa-ellipsis-h"></i>
                            </button>
                        </div>
                        
                        <div class="overflow-x-auto md:overflow-visible">
                            <table class="w-full text-left text-sm text-gray-400">
                                <thead class="border-b border-gray-700">
                                    <tr>
                                        <th class="pb-2 w-12">#</th>
                                        <th class="pb-2">Title</th>
                                        <th class="pb-2 hidden md:table-cell">Album</th>
                                        <th class="pb-2 hidden md:table-cell">Date added</th>
                                        <th class="pb-2 text-right pr-4">
                                            <i class="far fa-clock"></i>
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($songs as $index => $song): ?>
                                    <tr class="hover:bg-white/10 group">
                                        <td class="py-3 px-2">
                                            <span class="group-hover:hidden"><?= $index + 1 ?></span>
                                            <span class="hidden group-hover:inline">
                                                <i class="fas fa-play text-white"></i>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="flex items-center">
                                                <img src="<?= $song['image'] ?>" alt="Ảnh bìa bài hát" class="w-10 h-10 mr-3">
                                                <div>
                                                    <div class="text-white font-medium"><?= safeDisplay($song['title']) ?></div>
                                                    <div><?= safeDisplay($song['artist']) ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="hidden md:table-cell"><?= safeDisplay($song['album']) ?></td>
                                        <td class="hidden md:table-cell">2 days ago</td>
                                        <td class="text-right pr-4"><?= safeDisplay($song['duration']) ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php elseif($page == 'search'): ?>
                    <div class="mb-6">
                        <div class="relative">
                            <label for="search-input" class="sr-only">Search</label>
                            <input id="search-input" type="text" placeholder="What do you want to listen to?" class="w-full bg-white text-black py-3 px-12 rounded-full">
                            <i class="fas fa-search absolute left-4 top-1/2 transform -translate-y-1/2 text-black"></i>
                        </div>
                    </div>
                    
                    <h2 class="text-2xl font-bold mb-4">Browse all</h2>
                    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-6">
                        <?php 
                        $categories = [
                            ['name' => 'Podcasts', 'color' => 'from-blue-500 to-blue-800'],
                            ['name' => 'Live Events', 'color' => 'from-purple-500 to-purple-800'],
                            ['name' => 'Made For You', 'color' => 'from-green-500 to-green-800'],
                            ['name' => 'New Releases', 'color' => 'from-pink-500 to-pink-800'],
                            ['name' => 'Pop', 'color' => 'from-red-500 to-red-800'],
                            ['name' => 'Hip-Hop', 'color' => 'from-yellow-500 to-yellow-800'],
                            ['name' => 'Rock', 'color' => 'from-indigo-500 to-indigo-800'],
                            ['name' => 'Latin', 'color' => 'from-orange-500 to-orange-800'],
                            ['name' => 'Workout', 'color' => 'from-teal-500 to-teal-800'],
                            ['name' => 'Electronic', 'color' => 'from-cyan-500 to-cyan-800'],
                        ];
                        
                        foreach($categories as $category):
                        ?>
                        <div class="bg-gradient-to-br <?= $category['color'] ?> rounded-lg overflow-hidden h-48 relative">
                            <div class="p-4 font-bold text-xl"><?= safeDisplay($category['name']) ?></div>
                            <div class="absolute -bottom-2 -right-2 w-24 h-24 rotate-25 shadow-xl">
                                <img src="/placeholder.svg?height=100&width=100" alt="Ảnh bìa thể loại" class="w-full h-full object-cover">
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php elseif($page == 'library'): ?>
                    <div class="flex items-center justify-between mb-6">
                        <div class="flex items-center gap-4">
                            <button class="bg-[#333333] hover:bg-[#444444] px-4 py-2 rounded-full">
                                Playlists
                            </button>
                            <button class="bg-[#333333] hover:bg-[#444444] px-4 py-2 rounded-full">
                                Artists
                            </button>
                            <button class="bg-[#333333] hover:bg-[#444444] px-4 py-2 rounded-full">
                                Albums
                            </button>
                        </div>
                        <button class="text-xl">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                    
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-2">
                            <button class="text-xl">
                                <i class="fas fa-sort"></i>
                            </button>
                            <span>Recents</span>
                        </div>
                        <button class="text-xl">
                            <i class="fas fa-list"></i>
                        </button>
                    </div>
                    
                    <div class="grid gap-4">
                        <?php foreach($playlists as $playlist): ?>
                        <a href="?page=playlist&id=<?= $playlist['id'] ?>" class="flex items-center gap-4 hover:bg-[#333333] p-2 rounded-md">
                            <img src="<?= $playlist['image'] ?>" alt="Ảnh bìa playlist" class="w-12 h-12 rounded">
                            <div>
                                <div class="font-medium"><?= safeDisplay($playlist['name']) ?></div>
                                <div class="text-sm text-gray-400">Playlist • <?= $playlist['count'] ?> bài hát</div>
                            </div>
                        </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </main>
        </div>
    </div>

    <!-- Player -->
    <div class="fixed bottom-0 left-0 right-0 bg-[#181818] border-t border-[#282828] px-4 py-3">
        <div class="flex items-center justify-between">
            <!-- Thông tin bài hát -->
            <div class="flex items-center min-w-[180px] w-[30%]">
                <?php if (isset($song['cover_image']) && $song['cover_image']): ?>
                    <img id="player-image" 
                         src="<?= htmlspecialchars($song['cover_image']) ?>" 
                         alt="" 
                         class="w-14 h-14 rounded mr-3 object-cover">
                <?php else: ?>
                    <div id="player-image-placeholder" class="w-14 h-14 rounded mr-3 bg-gray-800 flex items-center justify-center">
                        <i class="fas fa-music text-gray-400 text-xl"></i>
                    </div>
                <?php endif; ?>
                <div>
                    <div id="player-title" class="text-sm text-white font-medium">Chọn một bài hát</div>
                    <div id="player-artist" class="text-xs text-gray-400">Nghệ sĩ</div>
                </div>
            </div>

            <!-- Điều khiển phát nhạc -->
            <div class="flex flex-col items-center max-w-[45%] w-[40%]">
                <div class="flex items-center gap-4 mb-1">
                    <button class="text-gray-400 hover:text-white">
                        <i class="fas fa-random"></i>
                    </button>
                    <button class="text-gray-400 hover:text-white">
                        <i class="fas fa-step-backward"></i>
                    </button>
                    <button id="play-button" class="w-8 h-8 rounded-full bg-white flex items-center justify-center hover:scale-105">
                        <i class="fas fa-play text-black"></i>
                    </button>
                    <button class="text-gray-400 hover:text-white">
                        <i class="fas fa-step-forward"></i>
                    </button>
                    <button class="text-gray-400 hover:text-white">
                        <i class="fas fa-redo"></i>
                    </button>
                </div>
                <div class="flex items-center gap-2 w-full">
                    <span id="current-time" class="text-xs text-gray-400">0:00</span>
                    <div class="flex-1 progress-container">
                        <input type="range" id="progress-bar" class="progress-bar" value="0" step="0.1">
                    </div>
                    <span id="duration" class="text-xs text-gray-400">0:00</span>
                </div>
            </div>

            <!-- Điều khiển âm lượng -->
            <div class="flex items-center justify-end min-w-[180px] w-[30%]">
                <button class="text-gray-400 hover:text-white mr-2">
                    <i class="fas fa-volume-up"></i>
                </button>
                <div class="w-24 progress-container">
                    <input type="range" id="volume-bar" class="progress-bar" value="100">
                </div>
            </div>
        </div>
    </div>

    <!-- Thêm loading indicator -->
    <div id="loading" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="animate-spin rounded-full h-32 w-32 border-t-2 border-b-2 border-spotify-green"></div>
    </div>

    <script nonce="<?= $nonce ?>">
    // Debounce function for performance
    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    document.addEventListener('DOMContentLoaded', function() {
        // Cache DOM elements
        const playButtons = document.querySelectorAll('.fa-play');
        const mainPlayButton = document.querySelector('.w-8.h-8.rounded-full.bg-white .fa-play');
        const progressBar = document.querySelector('.progress-bar');
        const currentTimeDisplay = document.querySelector('.text-xs.text-gray-400');
        
        // Optimized progress update
        const updateProgress = debounce(() => {
            if (mainPlayButton.classList.contains('fa-pause')) {
                progress = (progress + 1) % 101;
                progressBar.value = progress;
                
                const minutes = Math.floor((progress * 3.2) / 100);
                const seconds = Math.floor(((progress * 3.2) / 100 - minutes) * 60);
                currentTimeDisplay.textContent = `${minutes}:${seconds.toString().padStart(2, '0')}`;
            }
        }, 1000);
    });

    // Show loading when changing pages
    document.querySelectorAll('a[href*="?page="]').forEach(link => {
        link.addEventListener('click', () => {
            document.getElementById('loading').classList.remove('hidden');
        });
    });
    </script>

    <!-- Thêm pagination controls -->
    <div class="flex justify-center mt-8">
        <?php if($currentPage > 1): ?>
            <a href="?page=<?= $currentPage - 1 ?>" class="px-4 py-2 bg-spotify-lightgray rounded-l">Trước</a>
        <?php endif; ?>
        
        <span class="px-4 py-2 bg-spotify-lightgray">Trang <?= $currentPage ?></span>
        
        <?php if($currentPage * $itemsPerPage < count($playlists)): ?>
            <a href="?page=<?= $currentPage + 1 ?>" class="px-4 py-2 bg-spotify-lightgray rounded-r">Tiếp</a>
        <?php endif; ?>
    </div>

    <!-- Thêm skip link cho người dùng bàn phím -->
    <a href="#main-content" class="sr-only focus:not-sr-only">Skip to main content</a>

    <!-- Add audio.js -->
    <script src="/js/audio.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const player = new AudioPlayer();
        const loading = document.getElementById('loading');
        let currentPlayingButton = null;
        let isRepeat = false; // Trạng thái lặp lại
        let isRandom = false; // Trạng thái phát ngẫu nhiên
        let currentSongIndex = 0; // Vị trí bài hát hiện tại

        // Lấy danh sách tất cả các bài hát
        const songs = Array.from(document.querySelectorAll('.play-song')).map(button => ({
            element: button,
            url: button.dataset.url,
            title: button.dataset.title,
            artist: button.dataset.artist,
            image: button.dataset.image
        }));

        // Xử lý click vào nút play trong danh sách bài hát 
        document.querySelectorAll('.play-song').forEach(button => {
            button.addEventListener('click', async function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                const songData = {
                    url: this.dataset.url,
                    title: this.dataset.title,
                    artist: this.dataset.artist,
                    image: this.dataset.image
                };

                try {
                    const isCurrentSong = player.currentSong && player.currentSong.url === songData.url;
                    
                    if (isCurrentSong) {
                        if (player.audio.paused) {
                            await player.audio.play(); // Sử dụng trực tiếp audio.play()
                            this.querySelector('i').className = 'fas fa-pause text-white text-sm';
                            document.querySelector('#play-button i').className = 'fas fa-pause text-black';
                        } else {
                            player.audio.pause(); // Sử dụng trực tiếp audio.pause()
                            this.querySelector('i').className = 'fas fa-play text-white text-sm';
                            document.querySelector('#play-button i').className = 'fas fa-play text-black';
                        }
                        currentPlayingButton = this;
                    } else {
                        loading.classList.remove('hidden');
                        // Reset tất cả icon về play
                        document.querySelectorAll('.play-song i').forEach(icon => {
                            icon.className = 'fas fa-play text-white text-sm';
                        });

                        await player.loadAndPlay(songData);
                        
                        // Cập nhật UI
                        this.querySelector('i').className = 'fas fa-pause text-white text-sm';
                        document.querySelector('#play-button i').className = 'fas fa-pause text-black';
                        currentPlayingButton = this;
                        
                        // Cập nhật thông tin bài hát
                        document.querySelector('#player-title').textContent = songData.title || 'Chọn một bài hát';
                        document.querySelector('#player-artist').textContent = songData.artist || 'Nghệ sĩ';
                        if (songData.image) {
                            document.querySelector('#player-image').src = songData.image;
                        }
                    }
                } catch (error) {
                    console.error('Lỗi phát nhạc:', error);
                    alert('Không thể phát bài hát này');
                } finally {
                    loading.classList.add('hidden');
                }
            });
        });

        // Xử lý nút play/pause chính
        document.querySelector('#play-button').addEventListener('click', async () => {
            if (!player.currentSong || !currentPlayingButton) return;

            try {
                if (player.audio.paused) {
                    await player.audio.play(); // Sử dụng trực tiếp audio.play()
                    document.querySelector('#play-button i').className = 'fas fa-pause text-black';
                    currentPlayingButton.querySelector('i').className = 'fas fa-pause text-white text-sm';
                } else {
                    player.audio.pause(); // Sử dụng trực tiếp audio.pause()
                    document.querySelector('#play-button i').className = 'fas fa-play text-black';
                    currentPlayingButton.querySelector('i').className = 'fas fa-play text-white text-sm';
                }
            } catch (error) {
                console.error('Lỗi phát/dừng nhạc:', error);
            }
        });

        // Xử lý khi bài hát kết thúc
        player.audio.addEventListener('ended', () => {
            if (currentPlayingButton) {
                currentPlayingButton.querySelector('i').className = 'fas fa-play text-white text-sm';
            }
            document.querySelector('#play-button i').className = 'fas fa-play text-black';
        });

        // Xử lý nút next
        document.querySelector('#next-button').addEventListener('click', async () => {
            const songButtons = document.querySelectorAll('.play-song');
            if (!songButtons.length) return;

            try {
                // Tìm bài hát đang phát
                const currentButton = document.querySelector('.play-song i.fa-pause')?.closest('.play-song');
                if (!currentButton) {
                    // Nếu chưa có bài nào phát, phát bài đầu tiên
                    songButtons[0].click();
                    return;
                }

                // Tìm bài tiếp theo
                const nextButton = currentButton.closest('tr').nextElementSibling?.querySelector('.play-song');
                if (nextButton) {
                    nextButton.click();
                } else {
                    // Nếu là bài cuối cùng, quay lại bài đầu
                    songButtons[0].click();
                }
            } catch (error) {
                console.error('Lỗi chuyển bài:', error);
            }
        });

        // Xử lý nút previous
        document.querySelector('#prev-button').addEventListener('click', async () => {
            const songButtons = document.querySelectorAll('.play-song');
            if (!songButtons.length) return;

            try {
                // Tìm bài hát đang phát
                const currentButton = document.querySelector('.play-song i.fa-pause')?.closest('.play-song');
                if (!currentButton) {
                    // Nếu chưa có bài nào phát, phát bài cuối cùng
                    songButtons[songButtons.length - 1].click();
                    return;
                }

                // Kiểm tra thời gian phát
                if (player.audio.currentTime > 3) {
                    // Nếu đã phát hơn 3 giây, quay về đầu bài hát
                    player.audio.currentTime = 0;
                    return;
                }

                // Tìm bài trước đó
                const prevButton = currentButton.closest('tr').previousElementSibling?.querySelector('.play-song');
                if (prevButton) {
                    prevButton.click();
                } else {
                    // Nếu là bài đầu tiên, chuyển đến bài cuối
                    songButtons[songButtons.length - 1].click();
                }
            } catch (error) {
                console.error('Lỗi chuyển bài:', error);
            }
        });

        // Xử lý nút repeat
        document.querySelector('#repeat-button').addEventListener('click', () => {
            isRepeat = !isRepeat;
            const repeatButton = document.querySelector('#repeat-button');
            if (isRepeat) {
                repeatButton.classList.add('text-spotify-green');
                player.audio.loop = true;
            } else {
                repeatButton.classList.remove('text-spotify-green');
                player.audio.loop = false;
            }
        });

        // Xử lý nút random
        document.querySelector('#shuffle-button').addEventListener('click', () => {
            isRandom = !isRandom;
            const shuffleButton = document.querySelector('#shuffle-button');
            if (isRandom) {
                shuffleButton.classList.add('text-spotify-green');
            } else {
                shuffleButton.classList.remove('text-spotify-green');
            }
        });

        // Xử lý khi bài hát kết thúc - tự động chuyển bài tiếp theo
        player.audio.addEventListener('ended', () => {
            if (!isRepeat) {
                document.querySelector('#next-button').click();
            }
        });
    });
    </script>

    <script>
    function toggleDropdown() {
        const dropdownMenu = document.getElementById('dropdown-menu');
        const dropdownArrow = document.getElementById('dropdown-arrow');
        
        // Toggle dropdown visibility
        if (dropdownMenu.classList.contains('hidden')) {
            dropdownMenu.classList.remove('hidden');
            dropdownArrow.style.transform = 'rotate(180deg)';
        } else {
            dropdownMenu.classList.add('hidden');
            dropdownArrow.style.transform = 'rotate(0deg)';
        }
    }

    // Close dropdown when clicking outside
    document.addEventListener('click', function(event) {
        const dropdown = document.getElementById('dropdown-menu');
        const dropdownButton = event.target.closest('button');
        const dropdownArrow = document.getElementById('dropdown-arrow');
        
        if (!dropdownButton && !dropdown.classList.contains('hidden')) {
            dropdown.classList.add('hidden');
            dropdownArrow.style.transform = 'rotate(0deg)';
        }
    });
    </script>
</body>
</html>

