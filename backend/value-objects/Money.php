<?php
declare(strict_types=1);

namespace App\ValueObjects;

/**
 * Value Object для денег.
 * Immutable, типобезопасный.
 */
final class Money {
    private float $amount;
    private string $currency;
    
    public function __construct(float $amount, string $currency = 'RUB') {
        if ($amount < 0) {
            throw new \InvalidArgumentException('Amount cannot be negative');
        }
        if (!in_array($currency, ['RUB', 'USD', 'EUR', 'KZT'])) {
            throw new \InvalidArgumentException('Invalid currency');
        }
        $this->amount = round($amount, 2);
        $this->currency = $currency;
    }
    
    public function getAmount(): float { return $this->amount; }
    public function getCurrency(): string { return $this->currency; }
    
    public function subtract(Money $other): self {
        if ($this->currency !== $other->currency) {
            throw new \InvalidArgumentException('Currency mismatch');
        }
        return new self($this->amount - $other->amount, $this->currency);
    }
    
    public function applyPercentDiscount(float $percent): self {
        return new self($this->amount * (1 - $percent / 100), $this->currency);
    }
    
    public function format(): string {
        return number_format($this->amount, 2, '.', ' ') . ' ' . $this->currency;
    }
}