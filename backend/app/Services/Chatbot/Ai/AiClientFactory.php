<?php

namespace App\Services\Chatbot\Ai;

class AiClientFactory
{
    public function make(): AiClientInterface
    {
        $config = new AiConfig();

        if (!$config->enabled()) {
            return new NullAiClient();
        }

        return match ($config->provider()) {
            'groq', 'openai_compatible', 'openai' => new OpenAiCompatibleClient(),
            default => new NullAiClient(),
        };
    }
}