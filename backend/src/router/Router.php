<?php

namespace App\router;

use App\service\logger\Logger;

class Router {
    private array $routes = [];
    private string $basePath = '';
    private Logger $logger;    public function __construct(string $basePath = '') {
        $this->basePath = rtrim($basePath, '/');
        $this->logger = Logger::getInstance();
        
        $this->logger->info("Router initialized", [
            'base_path' => $this->basePath
        ]);
    }    public function get(string $path, callable $handler): void {
        $this->addRoute('GET', $path, $handler);
    }

    public function post(string $path, callable $handler): void {
        $this->addRoute('POST', $path, $handler);
    }

    public function put(string $path, callable $handler): void {
        $this->addRoute('PUT', $path, $handler);
    }

    public function delete(string $path, callable $handler): void {
        $this->addRoute('DELETE', $path, $handler);
    }

    private function addRoute(string $method, string $path, callable $handler): void {
        $path = $this->basePath . $path;
        $this->routes[$method][$path] = $handler;
        
        $this->logger->debug("Route registered", [
            'method' => $method,
            'path' => $path,
            'handler_type' => is_string($handler) ? 'string' : 'callable'
        ]);
    }    public function dispatch(): void {
        $method = $_SERVER['REQUEST_METHOD'];
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $originalPath = $path;
        
        $this->logger->info("Route dispatch started", [
            'method' => $method,
            'original_path' => $originalPath,
            'base_path' => $this->basePath,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? null
        ]);
        
        if ($this->basePath && strpos($path, $this->basePath) === 0) {
            $path = substr($path, strlen($this->basePath));
            $this->logger->debug("Base path removed from URL", [
                'original_path' => $originalPath,
                'processed_path' => $path
            ]);
        }
        
        $path = $path ?: '/';

        $startTime = microtime(true);

        if (isset($this->routes[$method][$path])) {
            $this->logger->info("Exact route match found", [
                'method' => $method,
                'path' => $path
            ]);
            
            call_user_func($this->routes[$method][$path]);
            
            $duration = (microtime(true) - $startTime) * 1000;
            $this->logger->logPerformance('route_dispatch', $duration, [
                'method' => $method,
                'path' => $path,
                'match_type' => 'exact'
            ]);
            
            return;
        }

        foreach ($this->routes[$method] ?? [] as $route => $handler) {
            if ($this->matchRoute($route, $path)) {
                $this->logger->info("Parameter route match found", [
                    'method' => $method,
                    'path' => $path,
                    'route_pattern' => $route
                ]);
                
                $params = $this->extractParams($route, $path);
                
                $this->logger->debug("Route parameters extracted", [
                    'route' => $route,
                    'path' => $path,
                    'params' => $params
                ]);
                
                call_user_func_array($handler, $params);
                
                $duration = (microtime(true) - $startTime) * 1000;
                $this->logger->logPerformance('route_dispatch', $duration, [
                    'method' => $method,
                    'path' => $path,
                    'route_pattern' => $route,
                    'match_type' => 'parameter',
                    'params_count' => count($params)
                ]);
                
                return;
            }
        }

        $duration = (microtime(true) - $startTime) * 1000;
        $this->logger->warning("No route found - 404", [
            'method' => $method,
            'path' => $path,
            'original_path' => $originalPath,
            'available_routes' => array_keys($this->routes[$method] ?? []),
            'search_time_ms' => round($duration, 2)
        ]);
        
        $this->show404();
    }    private function matchRoute(string $route, string $path): bool {
        $routePattern = preg_replace('/\{[^}]+\}/', '([^/]+)', $route);
        $routePattern = '#^' . $routePattern . '$#';
        $matches = preg_match($routePattern, $path);
        
        $this->logger->debug("Route pattern matching", [
            'route' => $route,
            'path' => $path,
            'pattern' => $routePattern,
            'matches' => (bool)$matches
        ]);
        
        return (bool)$matches;
    }

    private function extractParams(string $route, string $path): array {
        $routePattern = preg_replace('/\{[^}]+\}/', '([^/]+)', $route);
        $routePattern = '#^' . $routePattern . '$#';
        
        preg_match($routePattern, $path, $matches);
        array_shift($matches);
        
        $this->logger->debug("Parameters extracted from route", [
            'route' => $route,
            'path' => $path,
            'params' => $matches
        ]);
        
        return $matches;
    }

    private function show404(): void {
        $this->logger->warning("Displaying 404 page", [
            'method' => $_SERVER['REQUEST_METHOD'],
            'uri' => $_SERVER['REQUEST_URI'],
            'has_custom_404' => file_exists(__DIR__ . '/../../views/errors/404.php')
        ]);
        
        http_response_code(404);
        if (file_exists(__DIR__ . '/../../views/errors/404.php')) {
            require_once __DIR__ . '/../../views/errors/404.php';
        } else {
            echo '<h1>404 - Page Not Found</h1>';
        }
    }

    public function redirect(string $path): void {
        $fullPath = $this->basePath . $path;
        
        $this->logger->info("Router redirect", [
            'from_path' => $_SERVER['REQUEST_URI'] ?? 'unknown',
            'to_path' => $fullPath,
            'base_path' => $this->basePath
        ]);
        
        header('Location: ' . $fullPath);
        exit;
    }
}