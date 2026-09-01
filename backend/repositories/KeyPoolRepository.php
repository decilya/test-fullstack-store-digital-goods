<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Interfaces\KeyPoolInterface;
use PDO;

/**
 * Репозиторий пула ключей.
 * 
 * КРИТИЧЕСКАЯ ЗАЩИТА:
 * - SELECT FOR UPDATE блокирует строки в транзакции
 * - Prepared statements защищают от SQL injection
 * - WAL режим обеспечивает конкурентный доступ
 */
class KeyPoolRepository implements KeyPoolInterface {
    public function __construct(private PDO $db) {}
    
    public function acquireKey(string $sku, string $orderId): ?string {
        try {
            $this->db->beginTransaction();
            
            // FOR UPDATE блокирует строку для других транзакций
            $stmt = $this->db->prepare("
                SELECT id, code FROM keys_pool 
                WHERE sku = :sku AND status = 'available'
                LIMIT 1 
                FOR UPDATE
            ");
            $stmt->execute([':sku' => $sku]);
            $key = $stmt->fetch();
            
            if (!$key) {
                $this->db->rollBack();
                return null;
            }
            
            $update = $this->db->prepare("
                UPDATE keys_pool 
                SET status = 'reserved', 
                    order_id = :order_id,
                    reserved_at = CURRENT_TIMESTAMP
                WHERE id = :id
            ");
            $update->execute([
                ':order_id' => $orderId,
                ':id' => $key['id']
            ]);
            
            $this->db->commit();
            return $key['code'];
            
        } catch (\PDOException $e) {
            $this->db->rollBack();
            error_log("KeyPool acquireKey error: " . $e->getMessage());
            return null;
        }
    }
    
    public function confirmKey(string $orderId): bool {
        $stmt = $this->db->prepare("
            UPDATE keys_pool 
            SET status = 'used', used_at = CURRENT_TIMESTAMP
            WHERE order_id = :order_id AND status = 'reserved'
        ");
        return $stmt->execute([':order_id' => $orderId]);
    }
    
    public function releaseKey(string $orderId): bool {
        $stmt = $this->db->prepare("
            UPDATE keys_pool 
            SET status = 'available', order_id = NULL, reserved_at = NULL
            WHERE order_id = :order_id AND status = 'reserved'
        ");
        return $stmt->execute([':order_id' => $orderId]);
    }
    
    public function hasStock(string $sku): bool {
        return $this->getAvailableCount($sku) > 0;
    }
    
    public function getAvailableCount(string $sku): int {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) FROM keys_pool 
            WHERE sku = :sku AND status = 'available'
        ");
        $stmt->execute([':sku' => $sku]);
        return (int)$stmt->fetchColumn();
    }
}