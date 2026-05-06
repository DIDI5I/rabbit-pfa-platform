<?php

namespace App\Controllers;

use App\Services\Chatbot\ChatbotService;
use App\Support\ApiResponse;

class ChatbotController
{
    public function ask(): array
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];

        $message = trim($data['message'] ?? '');
        $previousPageLink = $data['previous_page_link'] ?? null;

        if ($message === '') {
            return [
                'message' => 'Validation failed.',
                'errors' => [
                    'message' => [
                        'message is required',
                    ],
                ],
            ];
        }

        $service = new ChatbotService();

        return $service->handle($message, [
            'previous_page_link' => $previousPageLink,
        ]);
    }
}