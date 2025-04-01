<?php
// Thêm require cho ArtistModel
require_once __DIR__ . '/../models/ArtistModel.php';

// Các require khác nếu có
require_once __DIR__ . '/../models/SongModel.php';

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
    // [
    //     'id' => 2, 
    //     'name' => 'Khám Phá Hàng Tuần', 
    //     'image' => '/uploads/playlists/discover-weekly.jpg',
    //     'count' => 30,
    //     'type' => 'Playlist của Spotify'
    // ],
    // [
    //     'id' => 3, 
    //     'name' => 'Top Hits 2024', 
    //     'image' => '/uploads/playlists/top-hits-2024.jpg',
    //     'count' => 50,
    //     'type' => 'Playlist • Spotify'
    // ],
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
    'image' => '/uploads/artists/placeholder.jpg',
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

// Khởi tạo models
$songModel = new SongModel();
$artistModel = new ArtistModel();

// Xử lý like/unlike
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle_like') {
    if (isset($_SESSION['user_id'])) {
        $songId = $_POST['song_id'] ?? 0;
        if ($songId) {
            try {
                $songModel->toggleLike($songId);
                $_SESSION['success_message'] = 'Đã cập nhật trạng thái yêu thích';
            } catch (Exception $e) {
                $_SESSION['error_message'] = $e->getMessage();
            }
        }
    }
    header("Location: ?page=home");
    exit;
}

// Lấy tất cả bài hát
$songs = $songModel->getAllSongs();

// Helper function để format thời gian
function formatDuration($seconds) {
    if (!is_numeric($seconds)) return '0:00';
    $minutes = floor($seconds / 60);
    $seconds = $seconds % 60;
    return sprintf("%d:%02d", $minutes, $seconds);
}

// XSS protection when displaying data
function safeDisplay($data) {
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}

// Xử lý tìm kiếm
if ($page === 'home') {
    $searchQuery = isset($_GET['search']) ? trim($_GET['search']) : '';
    if ($searchQuery !== '') {
        $songs = $songModel->searchSongs($searchQuery);
    }
}

// Lấy danh sách nghệ sĩ
$artists = $artistModel->getAllArtists();

// Cập nhật phần phân trang
$itemsPerPage = 10; // Số bài hát mỗi trang
$currentPage = isset($_GET['p']) ? (int)$_GET['p'] : 1;
$totalSongs = count($songs);
$totalPages = ceil($totalSongs / $itemsPerPage);

// Đảm bảo currentPage không vượt quá totalPages
$currentPage = max(1, min($currentPage, $totalPages));

// Tính offset cho trang hiện tại
$offset = ($currentPage - 1) * $itemsPerPage;

// Lấy danh sách bài hát cho trang hiện tại
$currentPageSongs = array_slice($songs, $offset, $itemsPerPage);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="description" content="Music streaming website">
    <title>Trang chủ - Music Chill</title>
    
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
        input[type="range"] {
            -webkit-appearance: none;
            appearance: none;
            height: 4px;
            border-radius: 2px;
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
                    </ul>
                </div>
                <div class="mb-8">
                    <div class="flex items-center mb-4">
                        <div class="w-8 h-8 bg-spotify-green flex items-center justify-center rounded-sm mr-3">
                            <i class="fas fa-plus text-black"></i>
                        </div>
                        <span class="font-semibold">Tạo Danh Sách</span>
                    </div>
                    <div class="flex items-center">
                        <div class="w-8 h-8 bg-gradient-to-br from-purple-700 to-gray-400 flex items-center justify-center rounded-sm mr-3">
                            <i class="fas fa-heart text-white"></i>
                        </div>
                        <span class="font-semibold">Liked Songs</span>
                    </div>
                </div>
                <div class="border-t border-gray-800 pt-4">
                    <!-- Có thể để trống hoặc thêm nội dung khác sau này -->
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
                    <!-- Phần hiển thị thông báo -->
                    <?php if (isset($_SESSION['success_message'])): ?>
                        <div class="bg-green-500 text-white px-4 py-2 rounded mb-4">
                            <?= htmlspecialchars($_SESSION['success_message']) ?>
                        </div>
                        <?php unset($_SESSION['success_message']); ?>
                    <?php endif; ?>

                    <?php if (isset($_SESSION['error_message'])): ?>
                        <div class="bg-red-500 text-white px-4 py-2 rounded mb-4">
                            <?= htmlspecialchars($_SESSION['error_message']) ?>
                        </div>
                        <?php unset($_SESSION['error_message']); ?>
                    <?php endif; ?>

                    <!-- Form tìm kiếm đơn giản -->
                    <form method="GET" action="" class="mb-6">
                        <input type="hidden" name="page" value="home">
                        <div class="relative max-w-xl">
                            <input type="text" 
                                   name="search" 
                                   value="<?= htmlspecialchars($searchQuery) ?>"
                                   placeholder="Tìm kiếm nghệ sĩ hoặc bài hát" 
                                   class="w-full bg-white text-black py-2 px-4 pl-10 rounded-full focus:outline-none text-sm">
                            <i class="fas fa-search absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-400 text-sm"></i>
                        </div>
                    </form>

                    <?php if (empty($searchQuery) && isset($_SESSION['user_id'])): ?>
                        <!-- Phần Danh sách đã thích -->
                        <section class="mb-8">
                            <div class="flex items-center gap-3 mb-6">
                                <h2 class="text-2xl font-bold">Danh sách đã thích</h2>
                                <i class="fas fa-heart text-red-500"></i>
                            </div>
                            <div class="bg-[#170f23] rounded-lg p-4">
                                <?php
                                $likedSongs = $songModel->getLikedSongs();
                                if (empty($likedSongs)): 
                                ?>
                                    <div class="text-center py-8">
                                        <div class="text-gray-400 mb-2">Chưa có bài hát yêu thích</div>
                                        <div class="text-sm text-gray-500">Hãy thêm bài hát vào danh sách yêu thích của bạn</div>
                                    </div>
                                <?php else: ?>
                                    <table class="w-full">
                                        <thead>
                                            <tr class="text-gray-400 text-sm border-b border-gray-700">
                                                <th class="pb-3 w-[5%] font-normal text-left">#</th>
                                                <th class="pb-3 w-[45%] font-normal text-left">Bài hát</th>
                                                <th class="pb-3 w-[25%] font-normal text-left">Album</th>
                                                <th class="pb-3 w-[15%] font-normal text-right">Thời gian</th>
                                                <th class="pb-3 w-[10%] font-normal text-center">Yêu thích</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($likedSongs as $index => $song): ?>
                                                <tr class="group hover:bg-[#2f2739] text-gray-400">
                                                    <td class="py-[10px] px-2 relative">
                                                        <div class="flex items-center">
                                                            <span class="group-hover:hidden"><?= $index + 1 ?></span>
                                                            <button type="button" 
                                                                    class="hidden group-hover:block text-white hover:scale-110 transition-transform absolute left-1/2 -translate-x-1/2"
                                                                    onclick="playSong(
                                                                        '<?= htmlspecialchars($song['file_path']) ?>', 
                                                                        '<?= htmlspecialchars($song['title']) ?>', 
                                                                        '<?= htmlspecialchars($song['artist']) ?>', 
                                                                        '<?= htmlspecialchars($song['cover_image']) ?>'
                                                                    )">
                                                                <i class="fas fa-play text-lg"></i>
                                                            </button>
                                                        </div>
                                                    </td>
                                                    <td class="py-[10px]">
                                                        <div class="flex items-center cursor-pointer group/title" 
                                                             onclick="playSong(
                                                                '<?= htmlspecialchars($song['file_path']) ?>', 
                                                                '<?= htmlspecialchars($song['title']) ?>', 
                                                                '<?= htmlspecialchars($song['artist']) ?>', 
                                                                '<?= htmlspecialchars($song['cover_image']) ?>'
                                                             )">
                                                            <div class="relative w-10 h-10 mr-3 flex-shrink-0">
                                                                <img src="<?= htmlspecialchars($song['cover_image']) ?>" 
                                                                     alt="<?= htmlspecialchars($song['title']) ?>" 
                                                                     class="w-full h-full rounded object-cover">
                                                            </div>
                                                            <div class="flex flex-col">
                                                                <div class="text-white text-sm font-medium group-hover/title:text-[#1DB954]">
                                                                    <?= htmlspecialchars($song['title']) ?>
                                                                </div>
                                                                <div class="text-gray-400 text-xs hover:underline">
                                                                    <?= htmlspecialchars($song['artist']) ?>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td class="py-[10px] text-sm"><?= htmlspecialchars($song['album']) ?></td>
                                                    <td class="py-[10px] text-right text-sm text-gray-400">
                                                        <?= formatDuration($song['duration']) ?>
                                                    </td>
                                                    <td class="py-[10px] text-center">
                                                        <form method="POST" action="" style="display: inline;">
                                                            <input type="hidden" name="action" value="toggle_like">
                                                            <input type="hidden" name="song_id" value="<?= $song['id'] ?>">
                                                            <button type="submit" class="like-button text-red-500">
                                                                <i class="fas fa-heart"></i>
                                                            </button>
                                                        </form>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                <?php endif; ?>
                            </div>
                        </section>

                        <!-- Phần danh sách nghệ sĩ -->
                        <section class="mb-8">
                            <h2 class="text-2xl font-bold mb-6">Danh Sách Nghệ Sĩ</h2>
                            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4">
                                <?php 
                                if (empty($artists)): ?>
                                    <p class="text-gray-400">Chưa có nghệ sĩ nào</p>
                                <?php else:
                                    foreach ($artists as $artist): 
                                        // Chỉ hiển thị nghệ sĩ có hình ảnh
                                        if (!empty($artist['image'])): 
                                    ?>
                                        <div class="bg-[#170f23] p-4 rounded-lg hover:bg-[#2f2739] transition-colors group cursor-pointer"
                                             onclick="showArtistSongs(<?= $artist['id'] ?>, '<?= htmlspecialchars($artist['name'], ENT_QUOTES) ?>')">
                                            <div class="block">
                                                <div class="relative mb-4 aspect-square">
                                                    <img src="<?= htmlspecialchars($artist['image']) ?>" 
                                                         alt="<?= htmlspecialchars($artist['name']) ?>" 
                                                         class="w-full h-full object-cover rounded-full shadow-lg"
                                                         onerror="this.src='/uploads/artists/placeholder.jpg'">
                                                    <div class="absolute bottom-2 right-2 bg-[#1DB954] rounded-full p-3 opacity-0 group-hover:opacity-100 transform group-hover:translate-y-0 translate-y-2 transition-all shadow-xl">
                                                        <i class="fas fa-play text-white"></i>
                                                    </div>
                                                </div>
                                                <div class="text-center">
                                                    <h3 class="text-white font-bold mb-1 truncate">
                                                        <?= htmlspecialchars($artist['name']) ?>
                                                    </h3>
                                                    <p class="text-gray-400 text-sm">
                                                        <?= $artist['song_count'] ?> bài hát
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    <?php 
                                        endif;
                                    endforeach; 
                                endif; ?>
                            </div>
                        </section>
                    <?php endif; ?>

                    <!-- Phần Danh sách bài hát -->
                    <section id="songs" class="mb-8">
                        <div class="flex justify-between items-center mb-6">
                            <h2 class="text-2xl font-bold">
                                <?php if (!empty($searchQuery)): ?>
                                    Kết quả tìm kiếm cho "<?= htmlspecialchars($searchQuery) ?>"
                                <?php else: ?>
                                    Danh sách bài hát
                                <?php endif; ?>
                            </h2>
                        </div>

                        <div class="bg-[#170f23] rounded-lg p-4">
                            <?php if (empty($songs)): ?>
                                <div class="text-center py-8">
                                    <div class="text-gray-400 mb-2">Chưa có bài hát nào</div>
                                </div>
                            <?php else: ?>
                                <table class="w-full">
                                    <thead>
                                        <tr class="text-gray-400 text-sm border-b border-gray-700">
                                            <th class="pb-3 w-[5%] font-normal text-left">#</th>
                                            <th class="pb-3 w-[45%] font-normal text-left">Bài hát</th>
                                            <th class="pb-3 w-[25%] font-normal text-left">Album</th>
                                            <th class="pb-3 w-[15%] font-normal text-right">Thời gian</th>
                                            <th class="pb-3 w-[10%] font-normal text-center">Yêu thích</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        // Hiển thị số thứ tự chính xác cho mỗi trang
                                        foreach ($currentPageSongs as $index => $song): 
                                            $realIndex = $offset + $index + 1;
                                        ?>
                                            <tr class="group hover:bg-[#2f2739] text-gray-400">
                                                <td class="py-[10px] px-2 relative">
                                                    <div class="flex items-center">
                                                        <span class="group-hover:hidden"><?= $realIndex ?></span>
                                                        <button type="button" 
                                                                class="hidden group-hover:block text-white hover:scale-110 transition-transform absolute left-1/2 -translate-x-1/2"
                                                                onclick="playSong(
                                                                    '<?= htmlspecialchars($song['file_path']) ?>', 
                                                                    '<?= htmlspecialchars($song['title']) ?>', 
                                                                    '<?= htmlspecialchars($song['artist']) ?>', 
                                                                    '<?= htmlspecialchars($song['cover_image']) ?>'
                                                                )">
                                                            <i class="fas fa-play text-lg"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                                <td class="py-[10px]">
                                                    <div class="flex items-center cursor-pointer group/title" 
                                                         onclick="playSong(
                                                            '<?= htmlspecialchars($song['file_path']) ?>', 
                                                            '<?= htmlspecialchars($song['title']) ?>', 
                                                            '<?= htmlspecialchars($song['artist']) ?>', 
                                                            '<?= htmlspecialchars($song['cover_image']) ?>'
                                                         )">
                                                        <div class="relative w-10 h-10 mr-3 flex-shrink-0">
                                                            <img src="<?= htmlspecialchars($song['cover_image']) ?>" 
                                                                 alt="<?= htmlspecialchars($song['title']) ?>" 
                                                                 class="w-full h-full rounded object-cover">
                                                        </div>
                                                        <div class="flex flex-col">
                                                            <div class="text-white text-sm font-medium group-hover/title:text-[#1DB954]">
                                                                <?= htmlspecialchars($song['title']) ?>
                                                            </div>
                                                            <div class="text-gray-400 text-xs hover:underline">
                                                                <?= htmlspecialchars($song['artist']) ?>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="py-[10px] text-sm"><?= htmlspecialchars($song['album']) ?></td>
                                                <td class="py-[10px] text-right text-sm text-gray-400">
                                                    <?= formatDuration($song['duration']) ?>
                                                </td>
                                                <td class="py-[10px] text-center">
                                                    <form method="POST" action="" style="display: inline;">
                                                        <input type="hidden" name="action" value="toggle_like">
                                                        <input type="hidden" name="song_id" value="<?= $song['id'] ?>">
                                                        <button type="submit" class="like-button <?= $song['is_liked'] ? 'text-red-500' : 'text-gray-400 hover:text-white' ?>">
                                                            <i class="<?= $song['is_liked'] ? 'fas' : 'far' ?> fa-heart"></i>
                                                        </button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>

                                <!-- Hiển thị phân trang chỉ khi có nhiều hơn 1 trang -->
                                <?php if ($totalPages > 1): ?>
                                <div class="mt-6 flex justify-center">
                                    <div class="flex items-center space-x-2">
                                        <?php if ($currentPage > 1): ?>
                                            <a href="?page=home&p=<?= $currentPage - 1 ?>" 
                                               class="px-3 py-1 rounded text-gray-400 hover:text-white transition-colors">
                                                <i class="fas fa-chevron-left"></i>
                                            </a>
                                        <?php endif; ?>

                                        <span class="text-gray-400">
                                            Trang <?= $currentPage ?> / <?= $totalPages ?>
                                        </span>

                                        <?php if ($currentPage < $totalPages): ?>
                                            <a href="?page=home&p=<?= $currentPage + 1 ?>" 
                                               class="px-3 py-1 rounded text-gray-400 hover:text-white transition-colors">
                                                <i class="fas fa-chevron-right"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php endif; ?>
                            <?php endif; ?>
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
                                        <td class="text-right pr-4"><?= formatDuration($song['duration']) ?></td>
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
    <div class="fixed bottom-0 left-0 right-0 bg-[#181818] border-t border-[#282828] p-4">
        <div class="flex justify-between items-center max-w-screen-2xl mx-auto">
            <!-- Currently playing -->
            <div class="flex items-center w-1/4">
                <img id="player-image" 
                     src="<?= $currentSong['image'] ?>" 
                     alt="Album art" 
                     class="w-14 h-14 rounded mr-3 object-cover flex-shrink-0"
                     onerror="this.src='/uploads/artists/placeholder.jpg'">
                <div class="flex-grow min-w-0">
                    <h4 id="player-title" class="text-white text-sm font-semibold truncate">
                        <?= htmlspecialchars($currentSong['title']) ?>
                    </h4>
                    <p id="player-artist" class="text-xs text-gray-400 truncate">
                        <?= htmlspecialchars($currentSong['artist']) ?>
                    </p>
                </div>
            </div>

            <!-- Player Controls -->
            <div class="flex flex-col items-center w-2/4">
                <div class="flex items-center gap-4 mb-2">
                    <!-- Nút phát ngẫu nhiên -->
                    <button id="shuffle-button" class="text-gray-400 hover:text-white transition-colors">
                        <i class="fas fa-random"></i>
                    </button>
                    <!-- Nút previous -->
                    <button id="prev-button" class="text-gray-400 hover:text-white transition-colors">
                        <i class="fas fa-step-backward"></i>
                    </button>
                    <!-- Nút play/pause -->
                    <button id="play-pause-button" class="text-white hover:scale-110 transition-transform">
                        <i id="play-pause-icon" class="fas fa-play"></i>
                    </button>
                    <!-- Nút next -->
                    <button id="next-button" class="text-gray-400 hover:text-white transition-colors">
                        <i class="fas fa-step-forward"></i>
                    </button>
                    <!-- Nút lặp lại -->
                    <button id="repeat-button" class="text-gray-400 hover:text-white transition-colors">
                        <i class="fas fa-redo"></i>
                    </button>
                </div>
                <input type="range" id="progress" class="w-full" value="0" step="0.1" min="0" max="100">
            </div>

            <!-- Volume Control -->
            <div class="w-1/4 flex items-center justify-end">
                <button id="volume-button" class="text-gray-400 hover:text-white mr-2">
                    <i id="volume-icon" class="fas fa-volume-up"></i>
                </button>
                <div class="w-24 relative group">
                    <input type="range" 
                           id="volume-slider" 
                           class="w-full h-1 bg-gray-600 rounded-lg appearance-none cursor-pointer"
                           min="0" 
                           max="100" 
                           value="100"
                           style="background: linear-gradient(to right, #1DB954 100%, #4d4d4d 100%)">
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

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const searchForm = document.getElementById('searchForm');
        const searchInput = document.getElementById('searchInput');

        if (searchForm && searchInput) {
            // Xử lý submit form
            searchForm.addEventListener('submit', function(e) {
                if (!searchInput.value.trim()) {
                    e.preventDefault();
                }
            });

            // Xử lý live search với debounce
            let timeout = null;
            searchInput.addEventListener('input', function() {
                clearTimeout(timeout);
                timeout = setTimeout(() => {
                    if (this.value.trim().length > 0) {
                        searchForm.submit();
                    }
                }, 500);
            });
        }

        // Xử lý nút play
        const playButtons = document.querySelectorAll('.play-song');
        if (playButtons.length > 0) {
            playButtons.forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    const url = this.dataset.url;
                    const title = this.dataset.title;
                    const artist = this.dataset.artist;
                    const image = this.dataset.image;
                    
                    const playerTitle = document.getElementById('player-title');
                    const playerArtist = document.getElementById('player-artist');
                    const playerImage = document.getElementById('player-image');
                    
                    if (playerTitle) playerTitle.textContent = title;
                    if (playerArtist) playerArtist.textContent = artist;
                    if (playerImage) playerImage.src = image;
                    
                    if (url) {
                        const audio = new Audio(url);
                        audio.play();
                    }
                });
            });
        }
    });
    </script>

    <script>
    let currentSongIndex = 0;
    const playlist = <?= json_encode($songs) ?>;

    // Hàm chuyển bài tiếp theo
    function nextSong() {
        if (playlist.length === 0) return;
        
        currentSongIndex = (currentSongIndex + 1) % playlist.length;
        const song = playlist[currentSongIndex];
        if (song) {
            playSong(
                song.file_path, // Thay url bằng file_path
                song.title,
                song.artist,
                song.cover_image // Thay image bằng cover_image
            );
        }
    }

    // Hàm chuyển bài trước
    function previousSong() {
        if (playlist.length === 0) return;
        
        currentSongIndex = (currentSongIndex - 1 + playlist.length) % playlist.length;
        const song = playlist[currentSongIndex];
        if (song) {
            playSong(
                song.file_path, // Thay url bằng file_path  
                song.title,
                song.artist, 
                song.cover_image // Thay image bằng cover_image
            );
        }
    }

    // Giữ nguyên các phần khác
    function playSong(url, title, artist, image) {
        const playerLayer = document.getElementById('audioPlayerLayer');
        playerLayer.classList.remove('hidden');
        
        // Cập nhật thông tin bài hát
        document.getElementById('currentSongTitle').textContent = title;
        document.getElementById('currentSongArtist').textContent = artist;
        document.getElementById('currentSongImage').src = image;
        
        // Xử lý audio
        if (currentSong !== url) {
            currentSong = url;
            audioPlayer.src = url;
        }
        
        audioPlayer.play();
        isPlaying = true;
        updatePlayPauseIcon();
    }

    // ... (các hàm khác giữ nguyên)
    </script>

    <script>
    let lastVolume = 1; // Lưu trữ mức âm lượng trước khi tắt tiếng

    function updateVolumeSlider(volume) {
        const volumeSlider = document.getElementById('volume-slider');
        const volumeIcon = document.getElementById('volume-icon');
        const percentage = volume * 100;
        
        volumeSlider.value = percentage;
        volumeSlider.style.background = `linear-gradient(to right, #1DB954 ${percentage}%, #4d4d4d ${percentage}%)`;

        // Cập nhật icon dựa trên mức âm lượng
        volumeIcon.className = 'fas';
        if (volume === 0) {
            volumeIcon.classList.add('fa-volume-mute');
        } else if (volume < 0.5) {
            volumeIcon.classList.add('fa-volume-down');
        } else {
            volumeIcon.classList.add('fa-volume-up');
        }
    }

    function toggleMute() {
        if (!currentAudio) return;

        if (currentAudio.volume > 0) {
            lastVolume = currentAudio.volume;
            currentAudio.volume = 0;
        } else {
            currentAudio.volume = lastVolume;
        }
        updateVolumeSlider(currentAudio.volume);
    }

    // Thêm event listeners cho điều khiển âm lượng
    document.getElementById('volume-slider')?.addEventListener('input', (e) => {
        const volume = e.target.value / 100;
        if (currentAudio) {
            currentAudio.volume = volume;
            lastVolume = volume;
        }
        updateVolumeSlider(volume);
    });

    document.getElementById('volume-button')?.addEventListener('click', toggleMute);
    </script>

    <style>
    /* Tùy chỉnh thanh volume slider */
    input[type="range"] {
        -webkit-appearance: none;
        appearance: none;
        height: 4px;
        border-radius: 2px;
    }

    input[type="range"]::-webkit-slider-thumb {
        -webkit-appearance: none;
        width: 12px;
        height: 12px;
        border-radius: 50%;
        background: white;
        cursor: pointer;
        opacity: 0;
        transition: opacity 0.2s;
    }

    input[type="range"]:hover::-webkit-slider-thumb,
    input[type="range"]:active::-webkit-slider-thumb {
        opacity: 1;
    }

    .group:hover input[type="range"]::-webkit-slider-thumb {
        opacity: 1;
    }
    </style>

    <script>
    // Hàm cập nhật thanh progress
    function updateProgress() {
        if (!currentAudio) return;
        
        const progressBar = document.getElementById('song-progress');
        const currentTimeSpan = document.getElementById('current-time');
        const totalTimeSpan = document.getElementById('total-time');
        
        // Cập nhật thanh progress
        const progress = (currentAudio.currentTime / currentAudio.duration) * 100;
        if (progressBar) {
            progressBar.value = progress;
            progressBar.style.background = `linear-gradient(to right, #1DB954 ${progress}%, #4d4d4d ${progress}%)`;
        }
        
        // Cập nhật thời gian
        if (currentTimeSpan) {
            currentTimeSpan.textContent = formatTime(currentAudio.currentTime);
        }
        if (totalTimeSpan && !isNaN(currentAudio.duration)) {
            totalTimeSpan.textContent = formatTime(currentAudio.duration);
        }
    }

    // Hàm format thời gian từ giây sang mm:ss
    function formatTime(seconds) {
        if (isNaN(seconds)) return "0:00";
        
        const minutes = Math.floor(seconds / 60);
        const remainingSeconds = Math.floor(seconds % 60);
        return `${minutes}:${remainingSeconds.toString().padStart(2, '0')}`;
    }

    // Thêm event listener cho thanh progress
    document.getElementById('song-progress')?.addEventListener('input', (e) => {
        if (!currentAudio) return;
        
        const time = (e.target.value / 100) * currentAudio.duration;
        currentAudio.currentTime = time;
    });
    </script>

    <!-- Modal hiển thị danh sách bài hát -->
    <div id="songListModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
        <div class="bg-[#170f23] rounded-lg p-6 max-w-4xl w-full mx-4 max-h-[80vh] overflow-y-auto">
            <div class="flex justify-between items-center mb-6">
                <h3 id="modalArtistName" class="text-2xl font-bold text-white"></h3>
                <button onclick="closeSongList()" class="text-gray-400 hover:text-white">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <div id="songList" class="space-y-4">
                <!-- Danh sách bài hát sẽ được thêm vào đây bằng JavaScript -->
            </div>
        </div>
    </div>

    <script>
    function showArtistSongs(artistId, artistName) {
        const modal = document.getElementById('songListModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        
        document.getElementById('modalArtistName').textContent = artistName;
        
        // Lấy tất cả bài hát của nghệ sĩ mà không phân trang
        fetch(`/api/artist-songs.php?artist_id=${artistId}`)
            .then(response => response.json())
            .then(songs => {
                const songList = document.getElementById('songList');
                songList.innerHTML = '';
                
                if (songs.length === 0) {
                    songList.innerHTML = '<p class="text-gray-400">Nghệ sĩ này chưa có bài hát</p>';
                    return;
                }
                
                // Hiển thị tất cả bài hát không phân trang
                songs.forEach((song, index) => {
                    const songElement = `
                        <div class="flex items-center justify-between p-3 hover:bg-[#2f2739] rounded-lg group">
                            <div class="flex items-center space-x-4 flex-1">
                                <span class="text-gray-400 w-4">${index + 1}</span>
                                <img src="${song.image}" 
                                     alt="${song.title}" 
                                     class="w-12 h-12 rounded object-cover">
                                <div class="min-w-0">
                                    <h4 class="text-white font-medium truncate">${song.title}</h4>
                                    <p class="text-gray-400 text-sm truncate">${song.album}</p>
                                </div>
                            </div>
                            <div class="flex items-center space-x-4">
                                <span class="text-gray-400 text-sm">${song.duration}</span>
                                <button onclick="playSong('${song.url}', '${song.title}', '${artistName}', '${song.image}')"
                                        class="text-gray-400 hover:text-white px-4">
                                    <i class="fas fa-play"></i>
                                </button>
                            </div>
                        </div>
                    `;
                    songList.innerHTML += songElement;
                });
            })
            .catch(error => {
                console.error('Error:', error);
                songList.innerHTML = '<p class="text-red-500">Lỗi tải danh sách bài hát</p>';
            });
    }

    function closeSongList() {
        const modal = document.getElementById('songListModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    // Đóng modal khi click bên ngoài
    document.getElementById('songListModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeSongList();
        }
    });
    </script>

    <!-- Audio Player Layer -->
    <div id="audioPlayerLayer" class="fixed bottom-0 left-0 right-0 bg-[#170f23] border-t border-gray-700 p-4 hidden">
        <div class="container mx-auto">
            <div class="flex items-center justify-between">
                <!-- Thông tin bài hát đang phát -->
                <div class="flex items-center space-x-4 flex-1">
                    <img id="currentSongImage" src="/uploads/artists/placeholder.jpg" 
                         alt="Song cover" 
                         class="w-16 h-16 rounded object-cover">
                    <div>
                        <h4 id="currentSongTitle" class="text-white font-medium"></h4>
                        <p id="currentSongArtist" class="text-gray-400 text-sm"></p>
                    </div>
                </div>

                <!-- Controls -->
                <div class="flex items-center space-x-6 flex-1 justify-center">
                    <button onclick="previousSong()" class="text-gray-400 hover:text-white">
                        <i class="fas fa-step-backward"></i>
                    </button>
                    <button id="playPauseBtn" onclick="togglePlay()" class="text-white bg-[#1DB954] rounded-full p-3 hover:bg-[#1ed760]">
                        <i class="fas fa-play" id="playPauseIcon"></i>
                    </button>
                    <button onclick="nextSong()" class="text-gray-400 hover:text-white">
                        <i class="fas fa-step-forward"></i>
                    </button>
                </div>

                <!-- Progress bar và volume -->
                <div class="flex items-center space-x-4 flex-1 justify-end">
                    <span id="currentTime" class="text-gray-400 text-sm">0:00</span>
                    <div class="w-48 bg-gray-600 rounded-full h-1 cursor-pointer" id="progressBar">
                        <div class="bg-white h-1 rounded-full" id="progress" style="width: 0%"></div>
                    </div>
                    <span id="duration" class="text-gray-400 text-sm">0:00</span>
                    
                    <div class="flex items-center space-x-2">
                        <i class="fas fa-volume-up text-gray-400"></i>
                        <input type="range" id="volumeSlider" 
                               class="w-24 accent-[#1DB954]" 
                               min="0" max="100" value="100">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Audio element -->
    <audio id="audioPlayer"></audio>

    <script>
    let currentSong = null;
    let audioPlayer = document.getElementById('audioPlayer');
    let isPlaying = false;

    function playSong(url, title, artist, image) {
        const playerLayer = document.getElementById('audioPlayerLayer');
        playerLayer.classList.remove('hidden');
        
        // Cập nhật thông tin bài hát
        document.getElementById('currentSongTitle').textContent = title;
        document.getElementById('currentSongArtist').textContent = artist;
        document.getElementById('currentSongImage').src = image;
        
        // Xử lý audio
        if (currentSong !== url) {
            currentSong = url;
            audioPlayer.src = url;
        }
        
        audioPlayer.play();
        isPlaying = true;
        updatePlayPauseIcon();
    }

    function togglePlay() {
        if (!audioPlayer.src) return;
        
        if (isPlaying) {
            audioPlayer.pause();
        } else {
            audioPlayer.play();
        }
        
        isPlaying = !isPlaying;
        updatePlayPauseIcon();
    }

    function updatePlayPauseIcon() {
        const icon = document.getElementById('playPauseIcon');
        icon.className = isPlaying ? 'fas fa-pause' : 'fas fa-play';
    }

    // Xử lý progress bar
    audioPlayer.addEventListener('timeupdate', () => {
        const progress = (audioPlayer.currentTime / audioPlayer.duration) * 100;
        document.getElementById('progress').style.width = `${progress}%`;
        
        document.getElementById('currentTime').textContent = formatTime(audioPlayer.currentTime);
        document.getElementById('duration').textContent = formatTime(audioPlayer.duration);
    });

    // Click vào progress bar để tua
    document.getElementById('progressBar').addEventListener('click', (e) => {
        const progressBar = e.currentTarget;
        const clickPosition = (e.pageX - progressBar.offsetLeft) / progressBar.offsetWidth;
        audioPlayer.currentTime = clickPosition * audioPlayer.duration;
    });

    // Xử lý volume
    document.getElementById('volumeSlider').addEventListener('input', (e) => {
        audioPlayer.volume = e.target.value / 100;
    });

    function formatTime(seconds) {
        if (isNaN(seconds)) return "0:00";
        const minutes = Math.floor(seconds / 60);
        seconds = Math.floor(seconds % 60);
        return `${minutes}:${seconds.toString().padStart(2, '0')}`;
    }

    // Xử lý khi bài hát kết thúc
    audioPlayer.addEventListener('ended', () => {
        isPlaying = false;
        updatePlayPauseIcon();
    });
    </script>
</body>
</html>

