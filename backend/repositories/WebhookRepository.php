<?php
declare(strict_types=1);

namespace App\Repositories;

use PDO;

class WebhookRepository {
    public function __construct(private PDO $db) {}
    
    public function isEventProcessed(string $eventId): bool {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) FROM webhook_events WHERE event_id = :event_id
        ");
        $stmt->execute([':event_id' => $eventId]);
        return (int)$stmt->fetchColumn() > 0;
    }
    
    public function recordEvent(string $eventId, string $orderId, string $status, bool $orderExists): bool {
        $stmt = $this->db->prepare("
            INSERT OR IGNORE INTO webhook_events 
            (event_id, order_id, status, order_existed_at_receive, processed_at) 
            VALUES (:event_id, :order_id, :status, :order_existed, CURRENT_TIMESTAMP)
        ");
        $stmt->execute([
            ':event_id' => $eventId,
            ':order_id' => $orderId,
            ':status' => $status,
            ':order_existed' => $orderExists ? 1 : 0,
        ]);
        return $stmt->rowCount() > 0;
    }
    
    public function getPendingEventsForOrder(string $orderId): array {
        $stmt = $this->db->prepare("
            SELECT event_id, status 
            FROM webhook_events 
            WHERE order_id = :order_id 
              AND order_existed_at_receive = 0 
              AND applied_at IS NULL
            ORDER BY processed_at ASC
        ");
        $stmt->execute([':order_id' => $orderId]);
        return $stmt->fetchAll();
    }
    
    public function markApplied(string $eventId): void {
        $stmt = $this->db->prepare("
            UPDATE webhook_events SET applied_at = CURRENT_TIMESTAMP 
            WHERE event_id = :event_id
        ");
        $stmt->execute([':event_id' => $eventId]);
    }
}