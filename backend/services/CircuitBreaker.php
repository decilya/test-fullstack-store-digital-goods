<?php
declare(strict_types=1);

namespace App\Services;

/**
 * Circuit Breaker pattern для внешних API.
 * Состояния: CLOSED (работает), OPEN (упал), HALF_OPEN (проверка)
 */
class CircuitBreaker {
    private const STATE_CLOSED = 'closed';
    private const STATE_OPEN = 'open';
    private const STATE_HALF_OPEN = 'half_open';
    
    private string $state = self::STATE_CLOSED;
    private int $failureCount = 0;
    private int $lastFailureTime = 0;
    
    public function __construct(
        private int $failureThreshold = 3,
        private int $recoveryTimeout = 30
    ) {}
    
    public function call(callable $operation): mixed {
        if ($this->state === self::STATE_OPEN) {
            if (time() - $this->lastFailureTime >= $this->recoveryTimeout) {
                $this->state = self::STATE_HALF_OPEN;
            } else {
                throw new \RuntimeException('Circuit breaker is OPEN');
            }
        }
        
        try {
            $result = $operation();
            $this->onSuccess();
            return $result;
        } catch (\Throwable $e) {
            $this->onFailure();
            throw $e;
        }
    }
    
    private function onSuccess(): void {
        $this->failureCount = 0;
        $this->state = self::STATE_CLOSED;
    }
    
    private function onFailure(): void {
        $this->failureCount++;
        $this->lastFailureTime = time();
        
        if ($this->failureCount >= $this->failureThreshold) {
            $this->state = self::STATE_OPEN;
        }
    }
    
    public function getState(): string { return $this->state; }
}