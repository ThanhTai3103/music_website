<?php
// Định nghĩa đường dẫn gốc
define('ROOT_PATH', dirname(__DIR__));

// Bắt đầu session
if (!isset($_SESSION)) {
    session_start();
}

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Load các file cần thiết theo thứ tự
require_once ROOT_PATH . '/config/config.php';
require_once ROOT_PATH . '/app/core/Database.php';
require_once ROOT_PATH . '/app/core/Auth.php';
require_once ROOT_PATH . '/app/core/Router.php';

// Load Models
require_once ROOT_PATH . '/app/models/UserModel.php';



try {
    // Khởi tạo và chạy router
    $router = new Router();
    $router->run();
} catch (Exception $e) {
    error_log($e->getMessage());
    // Hiển thị trang lỗi
    require ROOT_PATH . '/app/views/error.php';
}
?>
