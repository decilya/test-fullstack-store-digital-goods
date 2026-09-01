<?php
declare(strict_types=1);

namespace App\Exceptions;

/**
 * Базовое доменное исключение.
 */
class DomainException extends \Exception {
    protected int $httpCode = 400;
    
    public function getHttpCode(): int {
        return $this->httpCode;
    }
}