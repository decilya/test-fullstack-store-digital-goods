<?php
declare(strict_types=1);

/**
 * Тест на гонки - 50 параллельных webhook-ов.
 * Проверяет идемпотентность и защиту от race conditions.
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../repositories/KeyPoolRepository.php';
require_once __DIR__ . '/../repositories/WebhookRepository.php';
require_once __DIR__ . '/../repositories/OrderRepository.php';
require_once __DIR__ . '/../repositories/SupplierCacheRepository.php';
require_once __DIR__ . '/../suppliers/SupplierA.php';
require_once __DIR__ . '/../suppliers/SupplierB.php';
require_once __DIR__ . '/../services/EventBus.php';
require_once __DIR__ . '/../services/SupplierService.php';
require_once __DIR__ . '/../services/WebhookHandler.php';

use App\Repositories\{KeyPoolRepository, WebhookRepository, OrderRepository, SupplierCacheRepository};
use App\Suppliers\{SupplierA, SupplierB};
use App\Services\{EventBus, SupplierService, WebhookHandler};
use App\DTO\WebhookPayload;

echo " Тест на гонки: 50 параллельных webhook-ов на один заказ\n\n";

try {
    // Создаем тестовую БД в памяти
    $testDb = new PDO('sqlite::memory:');
    $testDb->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $testDb->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    $schema = file_get_contents(__DIR__ . '/../database/schema.sql');
    $testDb->exec($schema);
    
    $testDb->exec("INSERT INTO products (sku, name, type, price) VALUES ('TEST-SKU', 'Test', 'key', 1000)");
    $testDb->exec("INSERT INTO keys_pool (sku, code) VALUES ('TEST-SKU', 'KEY-001'), ('TEST-SKU', 'KEY-002')");
    $testDb->exec("INSERT INTO orders (id, sku, amount, final_amount, status) VALUES ('ORDER-001', 'TEST-SKU', 1000, 1000, 'created')");
    
    $supplierCache = new SupplierCacheRepository($testDb);
    $supplierA = new SupplierA($supplierCache);
    $supplierA->setErrorRate(0.0, 0.0);
    $supplierB = new SupplierB($supplierCache);
    $eventBus = new EventBus();
    $supplierService = new SupplierService([$supplierA, $supplierB]);
    
    $webhookRepo = new WebhookRepository($testDb);
    $orderRepo = new OrderRepository($testDb);
    $keyPoolRepo = new KeyPoolRepository($testDb);
    
    $handler = new WebhookHandler($testDb, $webhookRepo, $orderRepo, $keyPoolRepo, $supplierService, $eventBus);
    
    $results = [];
    
    echo "📡 Отправка 50 webhook-ов с ОДНИМ event_id...\n";
    for ($i = 0; $i < 50; $i++) {
        $payload = new WebhookPayload(
            'EVT-TEST-001',
            'ORDER-001',
            'paid',
            1000,
            'RUB',
            date('c')
        );
        $results[] = $handler->handle($payload);
    }
    
    $successCount = count(array_filter($results, fn($r) => $r['success']));
    
    $stmt = $testDb->prepare("SELECT COUNT(*) FROM keys_pool WHERE order_id = 'ORDER-001' AND status = 'used'");
    $stmt->execute();
    $usedKeysCount = (int)$stmt->fetchColumn();
    
    $stmt = $testDb->prepare("SELECT status, issued_key FROM orders WHERE id = 'ORDER-001'");
    $stmt->execute();
    $order = $stmt->fetch();
    
    $stmt = $testDb->query("SELECT COUNT(*) FROM webhook_events WHERE order_id = 'ORDER-001'");
    $eventsCount = (int)$stmt->fetchColumn();
    
    echo "\n📊 Результаты:\n";
    echo "  - Успешных ответов: {$successCount}/50\n";
    echo "  - Использовано ключей: {$usedKeysCount}\n";
    echo "  - Webhook событий записано: {$eventsCount}\n";
    echo "  - Статус заказа: {$order['status']}\n";
    echo "  - Выданный ключ: " . ($order['issued_key'] ?? 'null') . "\n";
    
    $passed = true;
    
    if ($usedKeysCount !== 1) {
        echo "\n❌ ПРОВАЛ: Ключей использовано не 1 (а {$usedKeysCount})\n";
        $passed = false;
    }
    
    if ($eventsCount !== 1) {
        echo "\n❌ ПРОВАЛ: Webhook событий записано не 1 (а {$eventsCount})\n";
        $passed = false;
    }
    
    if ($order['status'] !== 'delivered') {
        echo "\n❌ ПРОВАЛ: Статус заказа не 'delivered'\n";
        $passed = false;
    }
    
    if ($passed) {
        echo "\n✅ ТЕСТ ПРОЙДЕН: Идемпотентность и защита от гонок работают!\n";
        exit(0);
    } else {
        exit(1);
    }
    
} catch (Exception $e) {
    echo "\n ОШИБКА: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}