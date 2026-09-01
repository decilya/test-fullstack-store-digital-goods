<?php
declare(strict_types=1);

require_once __DIR__ . '/../middleware/SecurityMiddleware.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../dto/CreateOrderRequest.php';
require_once __DIR__ . '/../repositories/OrderRepository.php';
require_once __DIR__ . '/../repositories/PromoCodeRepository.php';
require_once __DIR__ . '/../repositories/WebhookRepository.php';
require_once __DIR__ . '/../repositories/KeyPoolRepository.php';
require_once __DIR__ . '/../repositories/SupplierCacheRepository.php';
require_once __DIR__ . '/../suppliers/SupplierA.php';
require_once __DIR__ . '/../suppliers/SupplierB.php';
require_once __DIR__ . '/../services/EventBus.php';
require_once __DIR__ . '/../services/SupplierService.php';
require_once __DIR__ . '/../services/OrderService.php';

use App\DTO\CreateOrderRequest;
use App\Repositories\{OrderRepository, PromoCodeRepository, WebhookRepository, KeyPoolRepository, SupplierCacheRepository};
use App\Suppliers\{SupplierA, SupplierB};
use App\Services\{EventBus, SupplierService, OrderService};

SecurityMiddleware::apply();
header('Content-Type: application/json; charset=utf-8');

try {
    $db = Database::getInstance();
    
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
    
    $method = $_SERVER['REQUEST_METHOD'];
    
    if ($method === 'GET') {
        $id = $_GET['id'] ?? null;
        if (!$id) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'id required']);
            exit;
        }
        $order = (new OrderRepository($db))->findById($id);
        if (!$order) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Order not found']);
            exit;
        }
        echo json_encode(['success' => true, 'data' => $order], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG);
        exit;
    }
    
    if ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $input = \Security::sanitizeArray($input);
        
        $request = CreateOrderRequest::fromArray($input);
        $result = $orderService->create($request);
        
        http_response_code(201);
        echo json_encode(['success' => true, 'data' => $result], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG);
        exit;
    }
    
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    
} catch (InvalidArgumentException $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error'], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG);
}