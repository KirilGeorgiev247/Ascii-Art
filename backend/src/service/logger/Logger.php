<?php

namespace App\service\logger;

use DateTime;
use Throwable;

class Logger
{
    private static $instance = null;
    private $logFile;
    private $logLevel;
    private $context = [];

    const LEVEL_DEBUG = 100;
    const LEVEL_INFO = 200;
    const LEVEL_WARNING = 300;
    const LEVEL_ERROR = 400;
    const LEVEL_CRITICAL = 500;

    private static $levelNames = [
        self::LEVEL_DEBUG => 'DEBUG',
        self::LEVEL_INFO => 'INFO',
        self::LEVEL_WARNING => 'WARNING',
        self::LEVEL_ERROR => 'ERROR',
        self::LEVEL_CRITICAL => 'CRITICAL'
    ];

    private function __construct()
    {
        $this->logFile = dirname(__DIR__, 2) . '/logs/app.log';
        $this->logLevel = self::LEVEL_DEBUG;
        $this->ensureLogDirectory();
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function ensureLogDirectory(): void
    {
        $logDir = dirname($this->logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
    }

    public function setContext(array $context): void
    {
        $this->context = array_merge($this->context, $context);
    }

    public function clearContext(): void
    {
        $this->context = [];
    }

    public function setLogLevel(int $level): void
    {
        $this->logLevel = $level;
    }

    private function shouldLog(int $level): bool
    {
        return $level >= $this->logLevel;
    }

    private function log(int $level, string $message, array $context = []): void
    {
        if (!$this->shouldLog($level)) {
            return;
        }

        $datetime = new DateTime();
        $timestamp = $datetime->format('Y-m-d H:i:s.u');
        $levelName = self::$levelNames[$level] ?? 'UNKNOWN';

        $fullContext = array_merge($this->context, $context);

        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3);
        $caller = $backtrace[2] ?? $backtrace[1] ?? [];
        $file = basename($caller['file'] ?? 'unknown');
        $line = $caller['line'] ?? 0;
        $function = $caller['function'] ?? 'unknown';
        $class = $caller['class'] ?? '';

        $callerInfo = $class ? "{$class}::{$function}" : $function;

        $contextStr = '';
        if (!empty($fullContext)) {
            $contextStr = ' | Context: ' . json_encode($fullContext);
        }

        $logEntry = sprintf(
            "[%s] %s: %s | %s:%d in %s%s%s",
            $timestamp,
            $levelName,
            $message,
            $file,
            $line,
            $callerInfo,
            $contextStr,
            PHP_EOL
        );

        file_put_contents($this->logFile, $logEntry, FILE_APPEND | LOCK_EX);

        if ($level >= self::LEVEL_ERROR) {
            error_log("ASCII-ART APP [{$levelName}]: {$message}");
        }
    }

    public function debug(string $message, array $context = []): void
    {
        $this->log(self::LEVEL_DEBUG, $message, $context);
    }

    public function info(string $message, array $context = []): void
    {
        $this->log(self::LEVEL_INFO, $message, $context);
    }

    public function warning(string $message, array $context = []): void
    {
        $this->log(self::LEVEL_WARNING, $message, $context);
    }

    public function error(string $message, array $context = []): void
    {
        $this->log(self::LEVEL_ERROR, $message, $context);
    }

    public function critical(string $message, array $context = []): void
    {
        $this->log(self::LEVEL_CRITICAL, $message, $context);
    }

    public function logRequest(string $method, string $uri, array $data = []): void
    {
        $this->info("HTTP Request: {$method} {$uri}", array_merge([
            'method' => $method,
            'uri' => $uri,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ], $data));
    }
    public function logResponse(int $statusCode, string $message = ''): void
    {
        $level = $statusCode >= 500 ? self::LEVEL_ERROR :
            ($statusCode >= 400 ? self::LEVEL_WARNING : self::LEVEL_INFO);

        $this->log($level, "HTTP Response: {$statusCode} {$message}", [
            'status_code' => $statusCode,
            'response_message' => $message
        ]);
    }

    public function logDatabase(string $operation, string $table, array $data = []): void
    {
        $this->debug("Database operation: {$operation} on {$table}", [
            'operation' => $operation,
            'table' => $table,
            'data' => $data
        ]);
    }

    public function logAuth(string $action, string $username, bool $success = true): void
    {
        $level = $success ? self::LEVEL_INFO : self::LEVEL_WARNING;
        $status = $success ? 'SUCCESS' : 'FAILED';

        $this->log($level, "Authentication {$action}: {$status} for user '{$username}'", [
            'action' => $action,
            'username' => $username,
            'success' => $success,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ]);
    }

    public function logSession(string $action, ?int $userId = null): void
    {
        $this->info("Session {$action}", [
            'action' => $action,
            'user_id' => $userId,
            'session_id' => session_id(),
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ]);
    }

    public function logWebSocket(string $action, array $data = []): void
    {
        $this->debug("WebSocket {$action}", array_merge([
            'action' => $action
        ], $data));
    }

    public function logFileOperation(string $operation, string $filename, bool $success = true): void
    {
        $level = $success ? self::LEVEL_INFO : self::LEVEL_ERROR;
        $status = $success ? 'SUCCESS' : 'FAILED';

        $this->log($level, "File {$operation}: {$status} for '{$filename}'", [
            'operation' => $operation,
            'filename' => $filename,
            'success' => $success
        ]);
    }
    public function logException(Throwable $exception, string $context = ''): void
    {
        $this->error("Exception: {$exception->getMessage()}", [
            'exception_class' => get_class($exception),
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
            'context' => $context
        ]);
    }
    public function logPerformance(string $operation, float $duration, array $metadata = []): void
    {
        $this->info("Performance: {$operation} took {$duration}ms", array_merge([
            'operation' => $operation,
            'duration_ms' => $duration
        ], $metadata));
    }
}