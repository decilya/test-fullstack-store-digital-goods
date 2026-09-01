<?php
declare(strict_types=1);

require_once __DIR__ . '/../middleware/SecurityMiddleware.php';
require_once __DIR__ . '/../config/database.php';

SecurityMiddleware::apply();
header('Content-Type: application/json; charset=utf-8');

try {
    $db = Database::getInstance();
    $stmt = $db->query("SELECT sku, name, type, price, currency, image FROM products ORDER BY created_at DESC");
    $products = $stmt->fetchAll();
    
    echo json_encode(['success' => true, 'data' => $products], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error']);
}