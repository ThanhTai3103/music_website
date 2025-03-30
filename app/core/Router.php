<?php
class Router {
    private $routes = [];
    private $publicRoutes = ['login', 'register', 'home'];
    private $protectedRoutes = ['profile', 'edit_profile'];
    private $adminRoutes = ['admin', 'admin/songs'];

    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $this->initRoutes();
    }

    private function initRoutes() {
        $this->routes = [
            'login' => 'C:/xampp/htdocs/music_website/app/views/login.php',
            'register' => 'C:/xampp/htdocs/music_website/app/views/register.php',
            'home' => 'C:/xampp/htdocs/music_website/app/views/home.php',
            'profile' => 'C:/xampp/htdocs/music_website/app/views/profile.php',
            'edit_profile' => 'C:/xampp/htdocs/music_website/app/views/edit_profile.php',
            'admin' => 'C:/xampp/htdocs/music_website/app/views/admin.php',
            'admin/songs' => [
                'controller' => 'AdminController',
                'action' => 'songs'
            ],
            'admin/songs/get/(\d+)' => [
                'controller' => 'AdminController',
                'action' => 'getSong',
                'pattern' => '/^admin\/songs\/get\/(\d+)$/'
            ],
            'admin/users/get/(\d+)' => [
                'controller' => 'AdminController',
                'action' => 'getUser',
                'pattern' => '/^admin\/users\/get\/(\d+)$/'
            ]
        ];
    }

    public function run() {
        $page = $_GET['page'] ?? 'home';

        // Xử lý logout
        if ($page === 'logout') {
            $_SESSION = array();
            if (isset($_COOKIE[session_name()])) {
                setcookie(session_name(), '', time()-3600, '/');
            }
            session_destroy();
            header('Location: ?page=home');
            exit();
        }

        // Kiểm tra route động
        foreach ($this->routes as $route => $config) {
            if (is_array($config) && isset($config['pattern'])) {
                if (preg_match($config['pattern'], $page, $matches)) {
                    $this->handleController($config['controller'], $config['action'], array_slice($matches, 1));
                    return;
                }
            }
        }

        // Xử lý các route thông thường
        if (isset($this->routes[$page])) {
            if (is_array($this->routes[$page])) {
                $this->handleController($this->routes[$page]['controller'], $this->routes[$page]['action']);
            } else {
                require $this->routes[$page];
            }
        } else {
            require 'C:/xampp/htdocs/music_website/app/views/home.php';
        }
    }

    private function handleController($controllerName, $actionName, $params = []) {
        require_once "C:/xampp/htdocs/music_website/app/controllers/{$controllerName}.php";
        $controller = new $controllerName();
        call_user_func_array([$controller, $actionName], $params);
    }
} 