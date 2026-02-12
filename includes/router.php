<?php
/**
 * CMS Router - URL Routing System
 */

class Router {
    private $path;
    private $routes = [];
    private $notFoundCallback;

    public function __construct($path = '') {
        $this->path = $path ?: '/';
    }

    public function get($route, $file) {
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $this->registerRoute($route, $file);
        }
    }

    public function post($route, $file) {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->registerRoute($route, $file);
        }
    }

    public function any($route, $file) {
        $this->registerRoute($route, $file);
    }

    private function registerRoute($route, $file) {
        $this->routes[] = [
            'pattern' => $route,
            'file' => $file,
            'regex' => $this->patternToRegex($route),
        ];
    }

    public function notFound($file) {
        $this->notFoundCallback = $file;
    }

    public function execute() {
        // Remove query string
        $path = parse_url($this->path, PHP_URL_PATH);
        $path = trim($path, '/');

        foreach ($this->routes as $route) {
            if (preg_match($route['regex'], $path, $matches)) {
                $this->loadFile($route['file'], $matches);
                return;
            }
        }

        // 404
        if ($this->notFoundCallback) {
            $this->loadFile($this->notFoundCallback);
        } else {
            http_response_code(404);
            echo '404 Not Found';
        }
    }

    private function patternToRegex($pattern) {
        $regex = str_replace('/', '\/', $pattern);
        $regex = preg_replace('/\([^)]+\)/', '([^/]+)', $regex);
        return '/^' . $regex . '$/';
    }

    private function loadFile($file, $matches = []) {
        $file = CMS_ROOT . '/' . $file;
        if (file_exists($file)) {
            require $file;
        } else {
            http_response_code(404);
            echo 'File not found: ' . $file;
        }
    }
}
