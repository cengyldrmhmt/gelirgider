<?php
require_once __DIR__ . '/../app/core/ErrorHandler.php';
require_once __DIR__ . '/../app/core/Session.php';
require_once __DIR__ . '/../app/helpers/functions.php';
Session::start();

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Basit router
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = str_replace('/gelirgider/public', '', $uri); // local dizin için

// Public routes that don't require authentication
$publicRoutes = ['/login', '/register', '/forgot-password', '/reset-password'];

// Redirect to login if not authenticated and trying to access protected route
if (!in_array($uri, $publicRoutes) && !isLoggedIn()) {
    header('Location: /gelirgider/public/login');
    exit;
}

switch ($uri) {
    case '/':
    case '':
        require_once __DIR__ . '/../app/controllers/DashboardController.php';
        $controller = new DashboardController();
        $controller->index();
        break;
    case '/login':
        require_once __DIR__ . '/../app/controllers/AuthController.php';
        $controller = new AuthController();
        $controller->login();
        break;
    case '/register':
        require_once __DIR__ . '/../app/controllers/AuthController.php';
        $controller = new AuthController();
        $controller->register();
        break;
    case '/forgot-password':
        require_once __DIR__ . '/../app/controllers/AuthController.php';
        $controller = new AuthController();
        $controller->forgotPassword();
        break;
    case '/reset-password':
        require_once __DIR__ . '/../app/controllers/AuthController.php';
        $controller = new AuthController();
        $controller->resetPassword();
        break;
    // Diğer route'lar buraya eklenir
    default:
        http_response_code(404);
        echo '404 Not Found';
        break;
}
