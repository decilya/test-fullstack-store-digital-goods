<?php
declare(strict_types=1);

namespace App\Services;

use App\Interfaces\SupplierInterface;

/**
 * Сервис поставщиков с failover и circuit breaker.
 */
class SupplierService {
    private array $circuitBreakers = [];
    
    public function __construct(private array $suppliers) {
        foreach ($suppliers as $supplier) {
            $this->circuitBreakers[$supplier->getName()] = new CircuitBreaker();
        }
    }
    
    public function getCodeWithFallback(string $sku, string $orderId, int $maxAttempts = 2): ?array {
        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            $requestId = "{$orderId}-att{$attempt}";
            
            foreach ($this->suppliers as $supplier) {
                $cb = $this->circuitBreakers[$supplier->getName()];
                
                try {
                    $result = $cb->call(fn() => $supplier->issueCode($requestId, $sku, $orderId));
                    
                    if ($result['status'] === 'ok' && !empty($result['code'])) {
                        return [
                            'code' => $result['code'],
                            'supplier' => $supplier->getName(),
                            'request_id' => $requestId,
                            'attempt' => $attempt,
                        ];
                    }
                    
                    if ($result['reason'] === 'out_of_stock') {
                        continue;
                    }
                    
                } catch (\Throwable $e) {
                    error_log("Supplier {$supplier->getName()} error: " . $e->getMessage());
                    continue;
                }
            }
        }
        
        return null;
    }
}