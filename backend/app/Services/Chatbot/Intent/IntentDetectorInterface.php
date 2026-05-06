<?php

namespace App\Services\Chatbot\Intent;

interface IntentDetectorInterface
{
    public function detect(string $text): ?array;
}