<?php
declare(strict_types=1);

namespace App\Services;

use App\Repositories\PromoCodeRepository;

class PromoCodeService {
    public function __construct(private PromoCodeRepository $promoRepo) {}
    
    public function calculateDiscount(string $code, float $amount): array {
        $promo = $this->promoRepo->findByCode($code);
        
        if (!$promo) {
            return ['valid' => false, 'error' => 'Промокод не найден'];
        }
        
        if ($promo['current_uses'] >= $promo['max_uses']) {
            return ['valid' => false, 'error' => 'Лимит исчерпан'];
        }
        
        $discount = 0.0;
        if ($promo['type'] === 'percent') {
            $discount = round($amount * ($promo['value'] / 100), 2);
        } else {
            $discount = min($promo['value'], $amount);
        }
        
        return [
            'valid' => true,
            'discount' => $discount,
            'final_amount' => max(0, $amount - $discount),
        ];
    }
}