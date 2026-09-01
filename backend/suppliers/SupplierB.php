<?php
declare(strict_types=1);

namespace App\Suppliers;

use App\Interfaces\SupplierInterface;
use App\Repositories\SupplierCacheRepository;

class SupplierB implements SupplierInterface {
    public function __construct(private SupplierCacheRepository $cache) {}
    
    public function getName(): string {
        return 'SupplierB';
    }
    
    public function issueCode(string $requestId, string $sku, string $orderId): array {
        $cachedCode = $this->cache->getIssuedCode($this->getName(), $requestId);
        if ($cachedCode !== null) {
            return ['status' => 'ok', 'request_id' => $requestId, 'code' => $cachedCode];
        }
        
        usleep(random_int(100, 300) * 1000);
        
        $rand = random_int(1, 100) / 100;
        if ($rand < 0.05) {
            sleep(8);
            throw new \RuntimeException('Supplier timeout');
        }
        
        if ($rand < 0.15) {
            return ['status' => 'error', 'reason' => 'internal_error', 'request_id' => $requestId];
        }
        
        $code = strtoupper(bin2hex(random_bytes(6)));
        $this->cache->storeIssuedCode($this->getName(), $requestId, $code);
        
        return ['status' => 'ok', 'request_id' => $requestId, 'code' => $code];
    }
    
    public function isHealthy(): bool {
        return true;
    }
}