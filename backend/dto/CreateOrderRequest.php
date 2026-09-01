<?php
declare(strict_types=1);

namespace App\DTO;

/**
 * DTO для запроса создания заказа.
 * Валидация на уровне типа.
 */
final class CreateOrderRequest {
    public function __construct(
        public readonly string $sku,
        public readonly float $amount,
        public readonly ?string $promoCode = null
    ) {}
    
    public static function fromArray(array $data): self {
        // Валидация SKU
        if (!isset($data['sku']) || !is_string($data['sku'])) {
            throw new \InvalidArgumentException('SKU is required and must be string');
        }
        if (!preg_match('/^[A-Z0-9\-_]+$/', $data['sku'])) {
            throw new \InvalidArgumentException('Invalid SKU format');
        }
        
        // Валидация суммы
        if (!isset($data['amount']) || !is_numeric($data['amount'])) {
            throw new \InvalidArgumentException('Amount is required and must be numeric');
        }
        $amount = (float)$data['amount'];
        if ($amount <= 0 || $amount > 1000000) {
            throw new \InvalidArgumentException('Amount must be between 0 and 1,000,000');
        }
        
        // Валидация промокода (опционально)
        $promoCode = null;
        if (isset($data['promo_code']) && is_string($data['promo_code'])) {
            $promoCode = trim($data['promo_code']);
            if (strlen($promoCode) > 50) {
                throw new \InvalidArgumentException('Promo code too long');
            }
            if (!preg_match('/^[A-Z0-9]+$/', $promoCode)) {
                throw new \InvalidArgumentException('Invalid promo code format');
            }
        }
        
        return new self($data['sku'], $amount, $promoCode);
    }
}