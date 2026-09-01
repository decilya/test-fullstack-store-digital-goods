<?php
declare(strict_types=1);

namespace App\Services;

use App\Repositories\{WebhookRepository, OrderRepository, KeyPoolRepository};
use App\DTO\WebhookPayload;
use PDO;

class WebhookHandler {
    public function __construct(
        private PDO $db,
        private WebhookRepository $webhookRepo,
        private OrderRepository $orderRepo,
        private KeyPoolRepository $keyPoolRepo,
        private SupplierService $supplierService,
        private EventBus $eventBus
    ) {}
    
    public function handle(WebhookPayload $payload): array {
        try {
            $orderExists = $this->orderRepo->exists($payload->orderId);
            
            // Идемпотентность: атомарная запись события
            $isNew = $this->webhookRepo->recordEvent(
                $payload->eventId,
                $payload->orderId,
                $payload->status,
                $orderExists
            );
            
            if (!$isNew) {
                return ['success' => true, 'message' => 'Already processed'];
            }
            
            if (!$orderExists) {
                error_log("Webhook {$payload->eventId} queued (order not exists)");
                return ['success' => true, 'message' => 'Queued'];
            }
            
            return $this->processWebhook($payload);
            
        } catch (\Throwable $e) {
            error_log("Webhook error: " . $e->getMessage());
            return ['success' => true, 'message' => 'Accepted with error'];
        }
    }
    
    private function processWebhook(WebhookPayload $payload): array {
        $this->db->beginTransaction();
        try {
            if ($payload->status === 'failed') {
                $this->orderRepo->transitionToPaymentFailed($payload->orderId);
                $this->db->commit();
                $this->eventBus->dispatch('payment.failed', ['order_id' => $payload->orderId]);
                return ['success' => true, 'status' => 'payment_failed'];
            }
            
            if (!$this->orderRepo->transitionToDelivering($payload->orderId)) {
                $this->db->commit();
                return ['success' => true, 'message' => 'Already processed'];
            }
            
            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
        
        $order = $this->orderRepo->findById($payload->orderId);
        if (!$order) {
            return ['success' => true, 'message' => 'Order gone'];
        }
        
        // Пробуем получить код от поставщиков
        $result = $this->supplierService->getCodeWithFallback($order['sku'], $payload->orderId);
        
        $this->db->beginTransaction();
        try {
            if ($result && !empty($result['code'])) {
                $this->orderRepo->transitionToDelivered($payload->orderId, $result['code']);
                $this->eventBus->dispatch('order.delivered', [
                    'order_id' => $payload->orderId,
                    'code' => $result['code']
                ]);
            } else {
                // Пробуем внутренний пул
                $code = $this->keyPoolRepo->acquireKey($order['sku'], $payload->orderId);
                if ($code) {
                    $this->orderRepo->transitionToDelivered($payload->orderId, $code);
                    $this->keyPoolRepo->confirmKey($payload->orderId);
                    $this->eventBus->dispatch('order.delivered', [
                        'order_id' => $payload->orderId,
                        'code' => $code
                    ]);
                } else {
                    $this->orderRepo->transitionToDeliveryFailed($payload->orderId);
                    $this->eventBus->dispatch('order.delivery_failed', ['order_id' => $payload->orderId]);
                }
            }
            $this->db->commit();
            
            return ['success' => true, 'status' => $result ? 'delivered' : 'delivery_failed'];
            
        } catch (\Throwable $e) {
            $this->db->rollBack();
            return ['success' => false, 'error' => 'Delivery failed'];
        }
    }
}