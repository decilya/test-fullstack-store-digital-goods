<?php
declare(strict_types=1);

namespace App\Repositories;

use PDO;

/**
 * Кэш для защиты от ловушки таймаута.
 * Повторный request_id возвращает ТОТ ЖЕ код.
 */
class SupplierCacheRepository {
    public function __construct(private PDO $db) {}
    
    public function getIssuedCode(string $supplierName, string $requestId): ?string {
        $stmt = $this->db->prepare("
            SELECT code FROM supplier_cache 
            WHERE supplier = :supplier AND request_id = :request_id
        ");
        $stmt->execute([
            ':supplier' => $supplierName,
            ':request_id' => $requestId,
        ]);
        $result = $stmt->fetchColumn();
        return $result !== false ? (string)$result : null;
    }
    
    public function storeIssuedCode(string $supplierName, string $requestId, string $code): void {
        $stmt = $this->db->prepare("
            INSERT OR IGNORE INTO supplier_cache (supplier, request_id, code, created_at)
            VALUES (:supplier, :request_id, :code, CURRENT_TIMESTAMP)
        ");
        $stmt->execute([
            ':supplier' => $supplierName,
            ':request_id' => $requestId,
            ':code' => $code,
        ]);
    }
}