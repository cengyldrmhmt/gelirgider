<?php
class Router {
    private $routes = [];
    private $auth;

    public function __construct() {
        $this->auth = new Auth();
    }

    public function add($method, $path, $controller, $action, $auth = false) {
        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'controller' => $controller,
            'action' => $action,
            'auth' => $auth
        ];
    }

    public function dispatch() {
        $method = $_SERVER['REQUEST_METHOD'];
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        
        // Remove base path from URL if exists
        $basePath = '/gelirgider';
        if (strpos($path, $basePath) === 0) {
            $path = substr($path, strlen($basePath));
        }
        
        // If path is empty, set it to root
        if (empty($path)) {
            $path = '/';
        }

        foreach ($this->routes as $route) {
            if ($route['method'] === $method && $this->matchPath($route['path'], $path)) {
                // Check if route requires authentication
                if ($route['auth'] && !$this->auth->isLoggedIn()) {
                    // Redirect to login page
                    header('Location: /gelirgider/auth/login');
                    exit;
                }
                
                $controller = new $route['controller']();
                $action = $route['action'];
                $params = $this->getPathParams($route['path'], $path);
                call_user_func_array([$controller, $action], $params);
                return;
            }
        }
        
        // If no route matches, redirect to login if not authenticated
        if (!$this->auth->isLoggedIn() && $path !== '/auth/login' && $path !== '/auth/register') {
            header('Location: /gelirgider/auth/login');
            exit;
        }
        
        // If still no route matches, show 404
        header("HTTP/1.0 404 Not Found");
        echo "404 Not Found";
    }

    private function matchPath($routePath, $requestPath) {
        $routeParts = explode('/', trim($routePath, '/'));
        $requestParts = explode('/', trim($requestPath, '/'));
        
        if (count($routeParts) !== count($requestParts)) {
            return false;
        }
        
        foreach ($routeParts as $index => $routePart) {
            if (strpos($routePart, ':') === 0) {
                continue;
            }
            if ($routePart !== $requestParts[$index]) {
                return false;
            }
        }
        
        return true;
    }

    private function getPathParams($routePath, $requestPath) {
        $routeParts = explode('/', trim($routePath, '/'));
        $requestParts = explode('/', trim($requestPath, '/'));
        $params = [];
        
        foreach ($routeParts as $index => $routePart) {
            if (strpos($routePart, ':') === 0) {
                $params[] = $requestParts[$index];
            }
        }
        
        return $params;
    }
} 