<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Exceptions\OrderNotFoundException;
use App\ValueObjects\OrderId;
use PDO;

class OrderRepository {
    public function __construct(private PDO $db) {}
    
    public function create(string $sku, float $amount, float $finalAmount, ?string $promoCode, float $discount): string {
        $orderId = OrderId::generate()->getValue();
        
        $stmt = $this->db->prepare("
            INSERT INTO orders (id, sku, amount, final_amount, promo_code, discount_amount, status, created_at) 
            VALUES (:id, :sku, :amount, :final_amount, :promo_code, :discount, 'created', CURRENT_TIMESTAMP)
        ");
        $stmt->execute([
            ':id' => $orderId,
            ':sku' => $sku,
            ':amount' => $amount,
            ':final_amount' => $finalAmount,
            ':promo_code' => $promoCode,
            ':discount' => $discount,
        ]);
        
        return $orderId;
    }
    
    public function findById(string $id): ?array {
        $stmt = $this->db->prepare("SELECT * FROM orders WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $result = $stmt->fetch();
        return $result ?: null;
    }
    
    public function exists(string $id): bool {
        $stmt = $this->db->prepare("SELECT 1 FROM orders WHERE id = :id");
        $stmt->execute([':id' => $id]);
        return (bool)$stmt->fetchColumn();
    }
    
    public function transitionToDelivering(string $orderId): bool {
        $stmt = $this->db->prepare("
            UPDATE orders SET status = 'delivering', paid_at = CURRENT_TIMESTAMP
            WHERE id = :id AND status = 'created'
        ");
        $stmt->execute([':id' => $orderId]);
        return $stmt->rowCount() > 0;
    }
    
    public function transitionToDelivered(string $orderId, string $issuedCode): bool {
        $stmt = $this->db->prepare("
            UPDATE orders 
            SET status = 'delivered', issued_key = :key, delivered_at = CURRENT_TIMESTAMP
            WHERE id = :id AND status IN ('delivering', 'out_of_stock', 'delivery_failed')
        ");
        $stmt->execute([':id' => $orderId, ':key' => $issuedCode]);
        return $stmt->rowCount() > 0;
    }
    
    public function transitionToOutOfStock(string $orderId): bool {
        $stmt = $this->db->prepare("
            UPDATE orders SET status = 'out_of_stock'
            WHERE id = :id AND status = 'delivering'
        ");
        $stmt->execute([':id' => $orderId]);
        return $stmt->rowCount() > 0;
    }
    
    public function transitionToDeliveryFailed(string $orderId): bool {
        $stmt = $this->db->prepare("
            UPDATE orders SET status = 'delivery_failed'
            WHERE id = :id AND status = 'delivering'
        ");
        $stmt->execute([':id' => $orderId]);
        return $stmt->rowCount() > 0;
    }
    
    public function transitionToPaymentFailed(string $orderId): bool {
        $stmt = $this->db->prepare("
            UPDATE orders SET status = 'payment_failed'
            WHERE id = :id AND status = 'created'
        ");
        $stmt->execute([':id' => $orderId]);
        return $stmt->rowCount() > 0;
    }
    
    public function getProblematicOrders(): array {
        $stmt = $this->db->query("
            SELECT o.*, p.name as product_name 
            FROM orders o
            LEFT JOIN products p ON o.sku = p.sku
            WHERE o.status IN ('out_of_stock', 'delivery_failed')
            ORDER BY o.paid_at DESC
        ");
        return $stmt->fetchAll();
    }
}