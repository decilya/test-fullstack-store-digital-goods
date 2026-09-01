<?php
declare(strict_types=1);

namespace App\Exceptions;

/**
 * Исключение для дубликата webhook.
 * Возвращаем HTTP 200 (идемпотентность).
 */
class DuplicateWebhookException extends DomainException {
    protected int $httpCode = 200;
    
    public function __construct(string $eventId) {
        parent::__construct("Webhook already processed: {$eventId}");
    }
}