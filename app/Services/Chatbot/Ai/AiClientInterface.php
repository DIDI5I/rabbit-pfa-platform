<?php

namespace App\Services\Chatbot\Ai;

interface AiClientInterface
{
    public function refineAnswer(array $payload): AiClientResponse;
}