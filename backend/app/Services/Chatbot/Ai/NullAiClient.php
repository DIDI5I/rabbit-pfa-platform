<?php

namespace App\Services\Chatbot\Ai;

class NullAiClient implements AiClientInterface
{
    public function refineAnswer(array $payload): AiClientResponse
    {
        return AiClientResponse::failure('ai_client_not_configured', [
            'provider' => null,
            'model' => null,
        ]);
    }
}