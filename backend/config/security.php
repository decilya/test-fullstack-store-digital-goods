<?php
declare(strict_types=1);

/**
 * Security configuration.
 * Защита от XSS, CSRF, injection атак.
 */
class Security {
    /**
     * Sanitize input - защита от XSS
     */
    public static function sanitize(string $input): string {
        return htmlspecialchars($input, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
    
    /**
     * Sanitize array рекурсивно
     */
    public static function sanitizeArray(array $data): array {
        $result = [];
        foreach ($data as $key => $value) {
            $cleanKey = is_string($key) ? self::sanitize($key) : $key;
            if (is_array($value)) {
                $result[$cleanKey] = self::sanitizeArray($value);
            } elseif (is_string($value)) {
                $result[$cleanKey] = self::sanitize($value);
            } else {
                $result[$cleanKey] = $value;
            }
        }
        return $result;
    }
    
    /**
     * Генерация CSRF токена
     */
    public static function generateCsrfToken(): string {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
    
    /**
     * Валидация CSRF токена
     */
    public static function validateCsrfToken(string $token): bool {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return hash_equals($_SESSION['csrf_token'] ?? '', $token);
    }
    
    /**
     * Request ID для трейсинга
     */
    public static function generateRequestId(): string {
        return bin2hex(random_bytes(16));
    }
}