<?php

namespace App\Services\Chatbot\Ai;

class AiPromptBuilder
{
    private AiConfig $config;

    public function __construct()
    {
        $this->config = new AiConfig();
    }

    public function build(array $safeData): array
    {
        $promptVersion = $this->config->promptVersion();

        return [
            'prompt_version' => $promptVersion,
            'system_prompt' => $this->systemPrompt($promptVersion),
            'user_prompt' => $this->userPrompt($safeData),
            'safe_data' => $safeData,
        ];
    }

    private function systemPrompt(string $promptVersion): string
    {
        return implode("\n", [
            "You are Rabbit's controlled answer refiner.",
            "Prompt version: {$promptVersion}.",
            "",
            "You must use only the provided backend response data.",
            "Do not invent products, suppliers, prices, quantities, dates, relationships, statuses, or recommendations.",
            "Do not mention fields that are not present in the provided data.",
            "Do not add numeric values that are not present in the provided data.",
            "Do not claim any write action was executed.",
            "Do not change permissions or role visibility.",
            "Do not expose hidden/internal fields.",
            "If data is missing, say it is unavailable.",
            "Keep the answer concise and business-facing. Maximum 3 sentences. Do not use bullet points.",
            "Use plain ASCII punctuation only: normal spaces, normal hyphens, and normal apostrophes.",
            "Return only the refined answer text. Do not return JSON.",
        ]);
    }

    private function userPrompt(array $safeData): string
    {
        $json = json_encode(
            $safeData,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );

        return implode("\n", [
            "Refine the chatbot answer below using only this backend-approved response.",
            "",
            "Backend-approved response:",
            $json ?: '{}',
            "",
            "Task:",
            "Rewrite only the answer text to be clearer and more useful.",
            "Return at most 3 sentences.",
            "Do not exceed 700 characters.",
            "Do not use bullet points.",
            "Do not change the meaning.",
            "Do not add facts.",
            "Do not add unsupported recommendations.",
        ]);
    }
}