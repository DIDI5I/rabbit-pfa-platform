<?php

namespace App\Services\Chatbot\Intent;

class PromotionIntentDetector implements IntentDetectorInterface
{
    public function detect(string $text): ?array
    {
        if (TextIntentUtils::containsAny($text, [
            'promotion',
            'promotions',
            'promo',
            'discount',
            'discounts',
            'remise',
            'on sale',
            'offer',
            'offers',
            'anything on promotion',
        ])) {
            return TextIntentUtils::result('active_promotions');
        }

        return null;
    }
}