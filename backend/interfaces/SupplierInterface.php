<?php
declare(strict_types=1);

namespace App\Interfaces;

interface SupplierInterface {
    public function getName(): string;
    public function issueCode(string $requestId, string $sku, string $orderId): array;
    public function isHealthy(): bool;
}