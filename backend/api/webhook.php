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

use App\DTO\WebhookPayload;
use App\Repositories\{WebhookRepository, OrderRepository, KeyPoolRepository, SupplierCacheRepository};
use App\Suppliers\{SupplierA, SupplierB};
use App\Services\{EventBus, SupplierService, WebhookHandler};

SecurityMiddleware::apply();
header('Content-Type: application/json; charset=utf-8');

try {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        echo json_encode(['success' => true, 'message' => 'Empty body']);
        exit;
    }
    
    $payload = WebhookPayload::fromArray($input);
    
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
    
    $result = $handler->handle($payload);
    echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG);
    
} catch (Exception $e) {
    error_log('Webhook error: ' . $e->getMessage());
    echo json_encode(['success' => true, 'message' => 'Accepted']);
}