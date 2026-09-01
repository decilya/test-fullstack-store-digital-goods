<?php
declare(strict_types=1);

namespace App\Exceptions;

class OrderNotFoundException extends DomainException {
    protected int $httpCode = 404;
    
    public function __construct(string $orderId) {
        parent::__construct("Order not found: {$orderId}");
    }
}