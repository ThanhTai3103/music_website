<?php
// Kiểm tra quyền admin
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ?page=login');
    exit;
}

// Tạo thư mục admin nếu chưa tồn tại
$adminPath = __DIR__ . '/admin';
if (!file_exists($adminPath)) {
    mkdir($adminPath, 0777, true);
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard - Spotify Clone</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100">
    <div class="flex h-screen">
        <!-- Sidebar -->
        <div class="w-64 bg-gray-800 text-white">
            <div class="p-4">
                <h1 class="text-2xl font-bold mb-6">Admin Dashboard</h1>
                <nav>
                    <ul class="space-y-2">
                        <li>
                            <a href="?page=admin&section=songs" class="flex items-center p-2 hover:bg-gray-700 rounded">
                                <i class="fas fa-music mr-3"></i>
                                Quản lý Bài hát
                            </a>
                        </li>
                        <li>
                            <a href="?page=admin&section=artists" class="flex items-center p-2 hover:bg-gray-700 rounded">
                                <i class="fas fa-user mr-3"></i>
                                Quản lý Nghệ sĩ
                            </a>
                        </li>
                        <li>
                            <a href="?page=admin&section=playlists" class="flex items-center p-2 hover:bg-gray-700 rounded">
                                <i class="fas fa-list mr-3"></i>
                                Quản lý Playlist
                            </a>
                        </li>
                        <li>
                            <a href="?page=admin&section=users" class="flex items-center p-2 hover:bg-gray-700 rounded">
                                <i class="fas fa-users mr-3"></i>
                                Quản lý Users
                            </a>
                        </li>
                        <li class="mt-8">
                            <a href="?page=home" class="flex items-center p-2 hover:bg-gray-700 rounded text-gray-300 hover:text-white">
                                <i class="fas fa-home mr-3"></i>
                                Về trang chủ
                            </a>
                        </li>
                    </ul>
                </nav>
            </div>
        </div>

        <!-- Main Content -->
        <div class="flex-1 overflow-auto">
            <header class="bg-white shadow">
                <div class="px-6 py-4 flex justify-between items-center">
                    <h2 class="text-xl font-semibold">
                        <?php 
                        $section = $_GET['section'] ?? 'dashboard';
                        echo ucfirst($section);
                        ?>
                    </h2>
                    <div class="flex items-center">
                        <span class="mr-4"><?= htmlspecialchars($_SESSION['user_name']) ?></span>
                        <a href="?page=logout" class="text-red-600 hover:text-red-800">
                            <i class="fas fa-sign-out-alt"></i> Đăng xuất
                        </a>
                    </div>
                </div>
            </header>

            <main class="p-6">
                <?php
                $section = $_GET['section'] ?? 'dashboard';
                $adminPath = __DIR__ . '/admin/';
                
                // Kiểm tra và include file tương ứng
                $validSections = ['dashboard', 'songs', 'artists', 'playlists', 'users'];
                if (in_array($section, $validSections)) {
                    $filePath = $adminPath . $section . '.php';
                    if (file_exists($filePath)) {
                        include $filePath;
                    } else {
                        echo "<div class='text-gray-600'>Trang đang được phát triển...</div>";
                    }
                } else {
                    echo "<div class='text-gray-600'>Trang không tồn tại</div>";
                }
                ?>
            </main>
        </div>
    </div>
</body>
</html> 