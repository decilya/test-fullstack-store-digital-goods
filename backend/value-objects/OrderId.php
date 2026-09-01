<?php
declare(strict_types=1);

namespace App\ValueObjects;

/**
 * Value Object для ID заказа.
 * Генерирует уникальные ID в формате ord_{timestamp}_{random}
 */
final class OrderId {
    private string $value;
    
    public function __construct(string $value) {
        if (!preg_match('/^ord_[a-z0-9_]+$/', $value)) {
            throw new \InvalidArgumentException('Invalid order ID format');
        }
        $this->value = $value;
    }
    
    public static function generate(): self {
        $timestamp = time();
        $random = bin2hex(random_bytes(4));
        return new self("ord_{$timestamp}_{$random}");
    }
    
    public function getValue(): string { return $this->value; }
    public function __toString(): string { return $this->value; }
}