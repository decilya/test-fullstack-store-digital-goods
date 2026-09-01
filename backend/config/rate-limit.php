<?php
declare(strict_types=1);

/**
 * Rate limiter - защита от DDoS и brute force.
 * Использует файловое хранилище (можно заменить на Redis).
 */
class RateLimiter {
    private static string $storagePath = __DIR__ . '/../storage/rate-limit/';
    
    /**
     * Проверка лимита запросов
     * 
     * @param string $key Идентификатор (IP, user_id, etc)
     * @param int $maxRequests Максимум запросов
     * @param int $windowSeconds Окно времени в секундах
     * @return bool true если лимит не превышен
     */
    public static function check(string $key, int $maxRequests = 60, int $windowSeconds = 60): bool {
        if (!is_dir(self::$storagePath)) {
            mkdir(self::$storagePath, 0755, true);
        }
        
        $file = self::$storagePath . md5($key) . '.json';
        $now = time();
        $windowStart = $now - $windowSeconds;
        
        $requests = [];
        if (file_exists($file)) {
            $data = json_decode(file_get_contents($file), true) ?? [];
            // Фильтруем только запросы в окне
            $requests = array_filter($data, fn($t) => $t > $windowStart);
        }
        
        if (count($requests) >= $maxRequests) {
            return false;
        }
        
        $requests[] = $now;
        file_put_contents($file, json_encode(array_values($requests)));
        
        return true;
    }
    
    /**
     * Получить remaining requests
     */
    public static function getRemaining(string $key, int $maxRequests = 60, int $windowSeconds = 60): int {
        $file = self::$storagePath . md5($key) . '.json';
        $now = time();
        $windowStart = $now - $windowSeconds;
        
        if (!file_exists($file)) {
            return $maxRequests;
        }
        
        $data = json_decode(file_get_contents($file), true) ?? [];
        $requests = array_filter($data, fn($t) => $t > $windowStart);
        
        return max(0, $maxRequests - count($requests));
    }
}