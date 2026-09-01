<?php
declare(strict_types=1);

namespace App\Services;

use App\Repositories\{OrderRepository, PromoCodeRepository, WebhookRepository, KeyPoolRepository};
use App\DTO\CreateOrderRequest;
use App\Exceptions\PromoCodeException;
use PDO;

class OrderService {
    public function __construct(
        private PDO $db,
        private OrderRepository $orderRepo,
        private PromoCodeRepository $promoRepo,
        private WebhookRepository $webhookRepo,
        private KeyPoolRepository $keyPoolRepo,
        private SupplierService $supplierService,
        private EventBus $eventBus
    ) {}
    
    public function create(CreateOrderRequest $request): array {
        $this->db->beginTransaction();
        try {
            $discount = 0.0;
            $validatedPromoCode = null;
            
            if ($request->promoCode) {
                $promo = $this->promoRepo->findByCode($request->promoCode);
                if (!$promo) {
                    throw new PromoCodeException("Промокод не найден");
                }
                
                if ($promo['current_uses'] >= $promo['max_uses']) {
                    throw new PromoCodeException("Лимит использований исчерпан");
                }
                
                if ($promo['type'] === 'percent') {
                    $discount = round($request->amount * ($promo['value'] / 100), 2);
                } else {
                    $discount = min($promo['value'], $request->amount);
                }
                
                if (!$this->promoRepo->atomicIncrementUsage($request->promoCode)) {
                    throw new PromoCodeException("Не удалось применить промокод");
                }
                
                $validatedPromoCode = $request->promoCode;
            }
            
            $finalAmount = max(0, $request->amount - $discount);
            $orderId = $this->orderRepo->create(
                $request->sku,
                $request->amount,
                $finalAmount,
                $validatedPromoCode,
                $discount
            );
            
            $this->db->commit();
            
            $this->eventBus->dispatch('order.created', ['order_id' => $orderId]);
            
            // Применяем pending webhook-и
            $this->applyPendingWebhooks($orderId);
            
            if ($validatedPromoCode) {
                $this->promoRepo->recordUsage($validatedPromoCode, $orderId);
            }
            
            return [
                'order_id' => $orderId,
                'amount' => $request->amount,
                'discount' => $discount,
                'final_amount' => $finalAmount,
                'promo_code' => $validatedPromoCode,
            ];
            
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
    
    private function applyPendingWebhooks(string $orderId): void {
        $pending = $this->webhookRepo->getPendingEventsForOrder($orderId);
        
        foreach ($pending as $event) {
            $payload = new \App\DTO\WebhookPayload(
                $event['event_id'],
                $orderId,
                $event['status'],
                0,
                'RUB',
                date('c')
            );
            
            $handler = new WebhookHandler(
                $this->db,
                $this->webhookRepo,
                $this->orderRepo,
                $this->keyPoolRepo,
                $this->supplierService,
                $this->eventBus
            );
            
            $handler->handle($payload);
            $this->webhookRepo->markApplied($event['event_id']);
        }
    }
    
    public function retryDelivery(string $orderId): array {
        $order = $this->orderRepo->findById($orderId);
        
        if (!$order) {
            return ['success' => false, 'error' => 'Заказ не найден'];
        }
        
        if ($order['status'] === 'delivered') {
            return [
                'success' => true,
                'message' => 'Уже доставлен',
                'code' => $order['issued_key'],
                'idempotent' => true,
            ];
        }
        
        if (!in_array($order['status'], ['out_of_stock', 'delivery_failed'])) {
            return ['success' => false, 'error' => "Неверный статус: {$order['status']}"];
        }
        
        $this->orderRepo->transitionToDelivering($orderId);
        
        $result = $this->supplierService->getCodeWithFallback($order['sku'], $orderId);
        
        if ($result && !empty($result['code'])) {
            $this->orderRepo->transitionToDelivered($orderId, $result['code']);
            return ['success' => true, 'code' => $result['code']];
        }
        
        $code = $this->keyPoolRepo->acquireKey($order['sku'], $orderId);
        if ($code) {
            $this->orderRepo->transitionToDelivered($orderId, $code);
            $this->keyPoolRepo->confirmKey($orderId);
            return ['success' => true, 'code' => $code];
        }
        
        $this->orderRepo->transitionToOutOfStock($orderId);
        return ['success' => false, 'error' => 'Ключей нет'];
    }
}