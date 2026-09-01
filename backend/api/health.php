<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $db = Database::getInstance();
    $db->query('SELECT 1');
    
    echo json_encode([
        'status' => 'ok',
        'timestamp' => date('c'),
        'version' => '1.0.0',
        'checks' => [
            'database' => 'ok',
        ]
    ]);
} catch (Exception $e) {
    http_response_code(503);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}