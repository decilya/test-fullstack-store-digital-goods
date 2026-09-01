<?php
declare(strict_types=1);

require_once __DIR__ . '/../middleware/SecurityMiddleware.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../repositories/OrderRepository.php';
require_once __DIR__ . '/../repositories/WebhookRepository.php';
require_once __DIR__ . '/../repositories/KeyPoolRepository.php';
require_once __DIR__ . '/../repositories/PromoCodeRepository.php';
require_once __DIR__ . '/../repositories/SupplierCacheRepository.php';
require_once __DIR__ . '/../suppliers/SupplierA.php';
require_once __DIR__ . '/../suppliers/SupplierB.php';
require_once __DIR__ . '/../services/EventBus.php';
require_once __DIR__ . '/../services/SupplierService.php';
require_once __DIR__ . '/../services/OrderService.php';

use App\Repositories\{OrderRepository, WebhookRepository, KeyPoolRepository, PromoCodeRepository, SupplierCacheRepository};
use App\Suppliers\{SupplierA, SupplierB};
use App\Services\{EventBus, SupplierService, OrderService};

SecurityMiddleware::apply();
header('Content-Type: application/json; charset=utf-8');

try {
    $db = Database::getInstance();
    $method = $_SERVER['REQUEST_METHOD'];
    
    $eventBus = new EventBus();
    $supplierCache = new SupplierCacheRepository($db);
    $supplierService = new SupplierService([new SupplierA($supplierCache), new SupplierB($supplierCache)]);
    
    $orderService = new OrderService(
        $db,
        new OrderRepository($db),
        new PromoCodeRepository($db),
        new WebhookRepository($db),
        new KeyPoolRepository($db),
        $supplierService,
        $eventBus
    );
    
    $orderRepo = new OrderRepository($db);
    
    if ($method === 'GET') {
        $action = $_GET['action'] ?? 'problematic';
        
        if ($action === 'problematic') {
            $orders = $orderRepo->getProblematicOrders();
            echo json_encode(['success' => true, 'data' => $orders], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG);
            exit;
        }
    }
    
    if ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $orderId = $input['order_id'] ?? null;
        
        if (!$orderId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'order_id required']);
            exit;
        }
        
        $result = $orderService->retryDelivery($orderId);
        echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG);
        exit;
    }
    
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}