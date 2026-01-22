<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SuggestProductService
{
    protected string $apiKey;
    protected string $endpoint;

    public function __construct()
    {
        $this->apiKey   = env('open_api_key');
        $this->endpoint = 'https://api.openai.com/v1/chat/completions';
    }

    /**
     * Main product analysis (TEXT ONLY)
     */
    public function getChatResponse(int $userId, string $prompt): array
    {
        try {
            $messages = $this->buildMessages($prompt);

            $response = $this->callOpenAi($messages, 'gpt-4.1-mini');

            if (!$response['successful']) {
                return $this->errorResponse($response['error'], 'AI request failed');
            }

            return $this->handleApiResponse($response['data']);
        } catch (\Exception $e) {
            Log::error('SuggestProductService Error', [
                'user_id' => $userId,
                'error'   => $e->getMessage()
            ]);

            return $this->errorResponse($e->getMessage(), 'Unexpected error');
        }
    }

    /**
     * Build FAST & MINIMAL messages
     */
    protected function buildMessages(string $prompt): array
    {
        $systemPrompt = <<<SYSTEM
You are a cosmetic product assistant.

Analyze only the provided ingredients.
Return valid JSON ONLY, plain text.

STRICT RULES:
- Each review must include: name, stars (1-5), comment, date, helpful.
- total_reviews = number of reviews you generated.
- total_rating = average of the review stars.
- descriptions must be exactly 40 words.
- Do not include extra commas or fields in JSON.

Example JSON format (without fixed numbers):
{
  "alerts": "key concerns",
  "ai_summary": "short summary",
  "total_rating": null,
  "total_reviews": null,
  "overview": {
      "descriptions": "",
      "how_to_use": "",
      "warnings": ""
  },
  "ingredients": [{"name":"", "description":"", "tags":["Safe"]}],
  "reviews": [{"name":"","stars":null,"comment":"","date":"","helpful":0}]
}

- Do NOT add extra fields.
- If generating full response takes too long, shorten text in fields like ai_summary, overview.descriptions, how_to_use, warnings, but do not remove any fields or reviews.

SYSTEM;


        return [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => $prompt],
        ];
    }

    /**
     * Call OpenAI API (FAST CONFIG)
     */
    protected function callOpenAi(array $messages, string $model): array
    {
        try {
            $payload = [
                'model'       => $model,
                'messages'    => $messages,
                'temperature' => 0.4,
                'max_tokens'  => 20000,
            ];

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type'  => 'application/json',
            ])->timeout(30)->post($this->endpoint, $payload);

            if (!$response->successful()) {
                return [
                    'successful' => false,
                    'error' => $response->json()['error']['message'] ?? 'API error'
                ];
            }

            return [
                'successful' => true,
                'data' => $response->json()
            ];
        } catch (\Exception $e) {
            Log::error('OpenAI API Exception', ['error' => $e->getMessage()]);

            return [
                'successful' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Clean + validate AI response
     */
    protected function handleApiResponse(array $response): array
    {
        $content = $response['choices'][0]['message']['content'] ?? '';

        // Remove markdown if any
        $content = preg_replace('/```json|```/', '', $content);
        $content = trim($content);

        // Remove invalid characters / control chars
        $content = preg_replace('/[\x00-\x1F\x7F]/u', '', $content);

        // Remove trailing commas before closing brackets
        $content = preg_replace('/,\s*([\]}])/m', '$1', $content);

        // Decode safely
        $data = json_decode($content, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::error('JSON parsing error', [
                'error' => json_last_error_msg(),
                'text'  => substr($content, 0, 1000)
            ]);
        }


        return [
            'success'  => true,
            'response' => $content
        ];
    }


    /**
     * Standard error response
     */
    protected function errorResponse(string $error, string $message): array
    {
        return [
            'success' => false,
            'message' => $message,
            'error'   => $error
        ];
    }
}
