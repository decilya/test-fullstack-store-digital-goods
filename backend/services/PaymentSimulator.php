<?php
declare(strict_types=1);

namespace App\Services;

class PaymentSimulator {
    public function __construct(private WebhookHandler $webhookHandler) {}
    
    public function simulateSuccess(string $orderId, float $amount, string $currency = 'RUB'): array {
        $payload = new \App\DTO\WebhookPayload(
            'evt_' . bin2hex(random_bytes(8)),
            $orderId,
            'paid',
            $amount,
            $currency,
            date('c')
        );
        return $this->webhookHandler->handle($payload);
    }
    
    public function simulateFailure(string $orderId): array {
        $payload = new \App\DTO\WebhookPayload(
            'evt_' . bin2hex(random_bytes(8)),
            $orderId,
            'failed',
            0,
            'RUB',
            date('c')
        );
        return $this->webhookHandler->handle($payload);
    }
}