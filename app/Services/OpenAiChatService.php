<?php

namespace App\Services;

use App\Models\Run;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class OpenAiChatService
{
    protected string $apiKey;
    protected string $endpoint;

    public function __construct()
    {
        $this->apiKey   = env('open_api_key');
        $this->endpoint = 'https://api.openai.com/v1/chat/completions';
    }

    /**
     * Get AI chat response with fitness tips based on latest run.
     */
    public function getChatResponse(int $userId, string $prompt, array $context = []): array
    {
        // Fetch latest run for the user and merge into context

        return $this->processRequest($prompt, null, $context);
    }

    /**
     * Get AI response analyzing an image (optional) + run context
     */
    public function getImageAnalysisResponse(int $userId, string $prompt, string $imageFullPath, array $context = []): array
    {
        // Fetch latest run


        return $this->processRequest($prompt, $imageFullPath, $context);
    }

    /**
     * Core request handler
     */
    protected function processRequest(string $prompt, ?string $imageFullPath = null, array $context = []): array
    {
        try {
            $messages = $this->buildMessages($prompt, $context, $imageFullPath);

            $model = $imageFullPath ? 'gpt-4o' : 'gpt-3.5-turbo';

            $response = $this->callOpenAi($messages, $model);
            return $this->handleApiResponse($response);
        } catch (\Exception $e) {
            Log::error('OpenAiChatService error: ' . $e->getMessage());
            return $this->errorResponse($e->getMessage(), 'AI request failed');
        }
    }

    /**
     * Build messages for OpenAI, including fitness/run context
     */protected function buildMessages(string $prompt, array $context = [], ?string $imageFullPath = null): array
{
    $systemPrompt = <<<SYSTEM
You are a cosmetic ingredient analysis expert.

Your responsibilities:
- Analyze skincare and cosmetic product ingredients
- Explain what key ingredients do
- Evaluate overall product quality and effectiveness
- Identify allergens, irritants, and sensitivities
- Indicate vegan or non-vegan suitability when possible
- Generate clear, consumer-friendly summaries

STRICT RULES:
- Do NOT give medical advice
- Do NOT diagnose or treat skin conditions
- Do NOT make clinical or pharmaceutical claims
- Base analysis only on provided ingredients or visible product information
- Summary must be in plain text, no HTML
- Include any restrictions, allergens, irritants, or warnings
- Limit summary to 150 words or less
- Return the summary as a single continuous paragraph, do NOT use newlines or \n characters
SYSTEM;

    $messages = [
        ['role' => 'system', 'content' => $systemPrompt],
    ];



    /**
     * USER PROMPT + OPTIONAL IMAGE
     */
    if ($imageFullPath) {
        $imageData = $this->processImage($imageFullPath);

        $messages[] = [
            'role' => 'user',
            'content' => [
                ['type' => 'text', 'text' => $prompt],
                ['type' => 'image_url', 'image_url' => $imageData],
            ],
        ];
    } else {
        $messages[] = [
            'role' => 'user',
            'content' => $prompt
        ];
    }

    return $messages;
}




    /**
     * Convert image to base64 for OpenAI
     */
    protected function processImage(string $imageFullPath): array
    {
        if (!file_exists($imageFullPath)) {
            throw new \Exception("Image file not found: $imageFullPath");
        }

        $mimeType = mime_content_type($imageFullPath);
        $imageContent = base64_encode(file_get_contents($imageFullPath));

        return ['url' => "data:$mimeType;base64,$imageContent"];
    }

    /**
     * Call OpenAI API
     */
    protected function callOpenAi(array $messages, string $model): array
    {
        $payload = [
            'model'       => $model,
            'messages'    => $messages,
            'temperature' => 0.6,
            'max_tokens'  => 1200,
        ];

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Content-Type'  => 'application/json',
        ])->timeout(120)->post($this->endpoint, $payload);

        if (!$response->successful()) {
            $error = $response->json()['error']['message'] ?? $response->body();
            throw new \Exception("OpenAI API Error ($model): " . $error);
        }

        return $response->json();
    }

    /**
     * Handle OpenAI response
     */
    protected function handleApiResponse(array $response): array
    {
        if (empty($response['choices'][0]['message']['content'])) {
            Log::error('OpenAI API empty content: ' . json_encode($response));
            return $this->errorResponse('No content in response', 'Invalid AI response structure');
        }

        $content = $response['choices'][0]['message']['content'];

        return [
            'success'       => true,
            'response'      => trim($content),
            'response_type' => 'text',
            'raw'           => $content,
        ];
    }

    /**
     * Standard error response
     */
    protected function errorResponse(string $error, string $message): array
    {
        return [
            'success' => false,
            'response' => $message,
            'error' => $error,
        ];
    }
}
