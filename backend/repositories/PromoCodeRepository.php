<?php
declare(strict_types=1);

namespace App\Repositories;

use PDO;

class PromoCodeRepository {
    public function __construct(private PDO $db) {}
    
    public function findByCode(string $code): ?array {
        $stmt = $this->db->prepare("SELECT * FROM promo_codes WHERE code = :code AND is_active = 1");
        $stmt->execute([':code' => $code]);
        $result = $stmt->fetch();
        return $result ?: null;
    }
    
    public function atomicIncrementUsage(string $code): bool {
        // Атомарная операция: UPDATE с условием в WHERE
        // 1000 параллельных запросов не смогут превысить лимит
        $stmt = $this->db->prepare("
            UPDATE promo_codes 
            SET current_uses = current_uses + 1 
            WHERE code = :code AND is_active = 1 AND current_uses < max_uses
        ");
        $stmt->execute([':code' => $code]);
        return $stmt->rowCount() > 0;
    }
    
    public function decrementUsage(string $code): void {
        $stmt = $this->db->prepare("
            UPDATE promo_codes 
            SET current_uses = MAX(0, current_uses - 1) 
            WHERE code = :code
        ");
        $stmt->execute([':code' => $code]);
    }
    
    public function isUsedForOrder(string $code, string $orderId): bool {
        $stmt = $this->db->prepare("
            SELECT 1 FROM promo_code_uses 
            WHERE promo_code = :code AND order_id = :order_id
        ");
        $stmt->execute([':code' => $code, ':order_id' => $orderId]);
        return (bool)$stmt->fetchColumn();
    }
    
    public function recordUsage(string $code, string $orderId): void {
        $stmt = $this->db->prepare("
            INSERT OR IGNORE INTO promo_code_uses (promo_code, order_id, used_at)
            VALUES (:code, :order_id, CURRENT_TIMESTAMP)
        ");
        $stmt->execute([':code' => $code, ':order_id' => $orderId]);
    }
}