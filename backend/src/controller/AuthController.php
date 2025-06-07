<?php

namespace App\controller;

use App\model\User;
use App\service\logger\Logger;

class AuthController
{    
    public function showLogin()
    {
        $logger = Logger::getInstance();
        $logger->info("Login page requested", [
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ]);
        
        $viewPath = dirname(dirname(__DIR__)) . '/views/auth/login/login.php';
        require_once $viewPath;
    }

    public function showRegister()
    {
        $logger = Logger::getInstance();
        $logger->info("Registration page requested", [
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ]);
        
        $viewPath = dirname(dirname(__DIR__)) . '/views/auth/register/register.php';
        require_once $viewPath;
    }    
    
    public function login()
    {
        $logger = Logger::getInstance();
        $logger->logRequest($_SERVER['REQUEST_METHOD'], '/login');
        // TODO: check if needed
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $error = '';
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = $_POST['email'] ?? '';
            $password = $_POST['password'] ?? '';
            
            $logger->info("Login attempt", [
                'email' => $email,
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ]);
            
            if (empty($email) || empty($password)) {
                $logger->warning("Login failed - missing credentials", ['email' => $email]);
                $error = 'Email and password are required.';
            } else {
                try {
                    $user = User::authenticate($email, $password);
                    if ($user) {
                        $_SESSION['user_id'] = $user->getId();
                        $logger->logAuth('login', $user->getUsername(), true);
                        $logger->logSession('started', $user->getId());
                        
                        $logger->info("Login successful, redirecting to feed", [
                            'user_id' => $user->getId(),
                            'username' => $user->getUsername()
                        ]);
                        
                        header('Location: /feed');
                        exit;
                    } else {
                        $logger->logAuth('login', $email, false);
                        $error = 'Invalid email or password.';
                    }
                } catch (\Exception $e) {
                    $logger->logException($e, 'User authentication failed');
                    $error = 'Login failed. Please try again.';
                }
            }
        }
        
        if ($error) {
            $logger->warning("Login page displayed with error", ['error' => $error]);
        }
        
        require __DIR__ . '/../../views/auth/login/login.php';
    }    
    
    public function register()
    {
        $logger = Logger::getInstance();
        $logger->logRequest($_SERVER['REQUEST_METHOD'], '/register');
        // TODO: check if needed
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $error = '';
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = $_POST['username'] ?? '';
            $email = $_POST['email'] ?? '';
            $password = $_POST['password'] ?? '';
            $confirm = $_POST['confirm'] ?? '';
            
            $logger->info("Registration attempt", [
                'username' => $username,
                'email' => $email,
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ]);
            
            if (empty($username) || empty($email) || empty($password)) {
                $logger->warning("Registration failed - missing required fields", [
                    'username' => $username,
                    'email' => $email
                ]);
                $error = 'All fields are required.';
            } elseif ($password !== $confirm) {
                $logger->warning("Registration failed - password mismatch", ['username' => $username]);
                $error = 'Passwords do not match.';
            } elseif (strlen($password) < 8) {
                $logger->warning("Registration failed - password too short", ['username' => $username]);
                $error = 'Password must be at least 8 characters long.';
            } else {
                try {
                    if (User::findByEmail($email)) {
                        $logger->warning("Registration failed - email already exists", [
                            'username' => $username,
                            'email' => $email
                        ]);
                        $error = 'Email already registered.';
                    } else {
                        $user = User::create($username, $email, $password);
                        $_SESSION['user_id'] = $user->getId();
                        
                        $logger->logAuth('register', $username, true);
                        $logger->logSession('started', $user->getId());
                        $logger->info("Registration successful, redirecting to feed", [
                            'user_id' => $user->getId(),
                            'username' => $username,
                            'email' => $email
                        ]);
                        
                        header('Location: /feed');
                        exit;
                    }
                } catch (\Exception $e) {
                    $logger->logException($e, 'User registration failed');
                    $error = 'Registration failed. Please try again.';
                }
            }
        }
        
        if ($error) {
            $logger->warning("Registration page displayed with error", ['error' => $error]);
        }
        
        require __DIR__ . '/../../views/auth/register/register.php';
    }    
    
    public function logout()
    {
        $logger = Logger::getInstance();
        $logger->logRequest($_SERVER['REQUEST_METHOD'], '/logout');
        // TODO: check if needed
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $userId = $_SESSION['user_id'] ?? null;
        
        if ($userId) {
            $logger->logAuth('logout', $userId, true);
            $logger->logSession('ended', $userId);
            $logger->info("User logged out successfully", ['user_id' => $userId]);
        } else {
            $logger->warning("Logout attempted with no active session");
        }
        
        session_destroy();
        
        $logger->info("Redirecting to home page after logout");
        header('Location: /');
        exit;
    }
}