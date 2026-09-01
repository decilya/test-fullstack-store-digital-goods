<?php
declare(strict_types=1);

namespace App\DTO;

/**
 * DTO для webhook payload.
 */
final class WebhookPayload {
    public function __construct(
        public readonly string $eventId,
        public readonly string $orderId,
        public readonly string $status,
        public readonly float $amount,
        public readonly string $currency,
        public readonly string $createdAt
    ) {}
    
    public static function fromArray(array $data): self {
        // Обязательные поля
        $required = ['event_id', 'order_id', 'status'];
        foreach ($required as $field) {
            if (!isset($data[$field]) || !is_string($data[$field])) {
                throw new \InvalidArgumentException("Field '{$field}' is required");
            }
        }
        
        // Валидация event_id
        if (!preg_match('/^evt_[a-z0-9_\-]+$/', $data['event_id'])) {
            throw new \InvalidArgumentException('Invalid event_id format');
        }
        
        // Валидация order_id
        if (!preg_match('/^ord_[a-z0-9_]+$/', $data['order_id'])) {
            throw new \InvalidArgumentException('Invalid order_id format');
        }
        
        // Валидация status
        if (!in_array($data['status'], ['paid', 'failed'])) {
            throw new \InvalidArgumentException('Status must be paid or failed');
        }
        
        // Опциональные поля
        $amount = isset($data['amount']) ? (float)$data['amount'] : 0.0;
        $currency = isset($data['currency']) ? (string)$data['currency'] : 'RUB';
        $createdAt = $data['created_at'] ?? date('c');
        
        return new self(
            $data['event_id'],
            $data['order_id'],
            $data['status'],
            $amount,
            $currency,
            $createdAt
        );
    }
}