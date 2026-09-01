<?php
declare(strict_types=1);

namespace App\Suppliers;

use App\Interfaces\SupplierInterface;
use App\Repositories\SupplierCacheRepository;

class SupplierA implements SupplierInterface {
    private float $errorProbability = 0.2;
    private float $timeoutProbability = 0.1;
    
    public function __construct(private SupplierCacheRepository $cache) {}
    
    public function getName(): string {
        return 'SupplierA';
    }
    
    public function issueCode(string $requestId, string $sku, string $orderId): array {
        // Идемпотентность: возвращаем тот же код для того же request_id
        $cachedCode = $this->cache->getIssuedCode($this->getName(), $requestId);
        if ($cachedCode !== null) {
            return ['status' => 'ok', 'request_id' => $requestId, 'code' => $cachedCode];
        }
        
        usleep(random_int(100, 500) * 1000);
        
        $rand = random_int(1, 100) / 100;
        if ($rand < $this->timeoutProbability) {
            sleep(8);
            throw new \RuntimeException('Supplier timeout');
        }
        
        if ($rand < $this->timeoutProbability + $this->errorProbability) {
            return ['status' => 'error', 'reason' => 'internal_error', 'request_id' => $requestId];
        }
        
        $code = strtoupper(bin2hex(random_bytes(6)));
        $this->cache->storeIssuedCode($this->getName(), $requestId, $code);
        
        return ['status' => 'ok', 'request_id' => $requestId, 'code' => $code];
    }
    
    public function isHealthy(): bool {
        return true;
    }
    
    public function setErrorRate(float $errorProb, float $timeoutProb): void {
        $this->errorProbability = $errorProb;
        $this->timeoutProbability = $timeoutProb;
    }
}