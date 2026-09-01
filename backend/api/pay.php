<?php
declare(strict_types=1);

require_once __DIR__ . '/../middleware/SecurityMiddleware.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../dto/WebhookPayload.php';
require_once __DIR__ . '/../repositories/WebhookRepository.php';
require_once __DIR__ . '/../repositories/OrderRepository.php';
require_once __DIR__ . '/../repositories/KeyPoolRepository.php';
require_once __DIR__ . '/../repositories/SupplierCacheRepository.php';
require_once __DIR__ . '/../suppliers/SupplierA.php';
require_once __DIR__ . '/../suppliers/SupplierB.php';
require_once __DIR__ . '/../services/EventBus.php';
require_once __DIR__ . '/../services/SupplierService.php';
require_once __DIR__ . '/../services/WebhookHandler.php';
require_once __DIR__ . '/../services/PaymentSimulator.php';

use App\Repositories\{WebhookRepository, OrderRepository, KeyPoolRepository, SupplierCacheRepository};
use App\Suppliers\{SupplierA, SupplierB};
use App\Services\{EventBus, SupplierService, WebhookHandler, PaymentSimulator};

SecurityMiddleware::apply();
header('Content-Type: application/json; charset=utf-8');

try {
    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    $input = \Security::sanitizeArray($input);
    
    $orderId = $input['order_id'] ?? null;
    $success = $input['success'] ?? true;
    $amount = (float)($input['amount'] ?? 100);
    
    if (!$orderId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'order_id required']);
        exit;
    }
    
    $db = Database::getInstance();
    $eventBus = new EventBus();
    $supplierCache = new SupplierCacheRepository($db);
    $supplierService = new SupplierService([new SupplierA($supplierCache), new SupplierB($supplierCache)]);
    
    $handler = new WebhookHandler(
        $db,
        new WebhookRepository($db),
        new OrderRepository($db),
        new KeyPoolRepository($db),
        $supplierService,
        $eventBus
    );
    
    $simulator = new PaymentSimulator($handler);
    $result = $success ? $simulator->simulateSuccess($orderId, $amount) : $simulator->simulateFailure($orderId);
    
    echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}