<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../config/rate-limit.php';

/**
 * Middleware для защиты API.
 * Применяется ко всем запросам.
 */
class SecurityMiddleware {
    /**
     * Применить все проверки
     */
    public static function apply(): void {
        // 1. Rate limiting по IP
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        if (!RateLimiter::check("ip:{$ip}", 100, 60)) {
            http_response_code(429);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Too many requests', 'retry_after' => 60]);
            exit;
        }
        
        // 2. Request size limit (1MB)
        if (isset($_SERVER['CONTENT_LENGTH']) && (int)$_SERVER['CONTENT_LENGTH'] > 1048576) {
            http_response_code(413);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Request too large']);
            exit;
        }
        
        // 3. Security headers
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('X-XSS-Protection: 1; mode=block');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
        
        // 4. Request ID для трейсинга
        $requestId = Security::generateRequestId();
        header("X-Request-ID: {$requestId}");
        
        // 5. Rate limit headers
        $remaining = RateLimiter::getRemaining("ip:{$ip}", 100, 60);
        header("X-RateLimit-Limit: 100");
        header("X-RateLimit-Remaining: {$remaining}");
        header("X-RateLimit-Reset: " . (time() + 60));
    }
    
    /**
     * Проверка CSRF для POST запросов
     */
    public static function validateCsrf(): bool {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return true;
        }
        
        // Для API используем токен из header
        $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (empty($token)) {
            // Для webhook и внутренних запросов пропускаем
            return true;
        }
        
        return Security::validateCsrfToken($token);
    }
}