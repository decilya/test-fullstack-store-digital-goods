<?php
declare(strict_types=1);

namespace App\Interfaces;

interface KeyPoolInterface {
    public function acquireKey(string $sku, string $orderId): ?string;
    public function confirmKey(string $orderId): bool;
    public function releaseKey(string $orderId): bool;
    public function hasStock(string $sku): bool;
    public function getAvailableCount(string $sku): int;
}