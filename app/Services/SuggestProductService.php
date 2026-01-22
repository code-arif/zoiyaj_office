<?php

namespace App\Services;

use App\Models\Run;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

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
     * Get AI chat response with beauty product analysis.
     */
    public function getChatResponse($userId, $prompt, $context = [])
    {
        try {
            $messages = $this->buildMessages($prompt, $context);

            $response = $this->callOpenAi($messages, 'gpt-4');

            if (!$response['successful']) {
                return [
                    'success' => false,
                    'error' => $response['error'] ?? 'API request failed'
                ];
            }

            $data = $response['data'];

            // Extract text from OpenAI response
            $text = $data['choices'][0]['message']['content'] ?? '';

            if (empty($text)) {
                Log::error('Empty response from OpenAI API', ['data' => $data]);
                return [
                    'success' => false,
                    'error' => 'Empty response from API'
                ];
            }

            // Clean up markdown code blocks if present
            $text = preg_replace('/```json\s*/', '', $text);
            $text = preg_replace('/\s*```/', '', $text);
            $text = trim($text);

            // Validate it's valid JSON
            $testDecode = json_decode($text, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::error('Invalid JSON from OpenAI', [
                    'json_error' => json_last_error_msg(),
                    'text' => substr($text, 0, 500)
                ]);
                return [
                    'success' => false,
                    'error' => 'Invalid JSON response: ' . json_last_error_msg()
                ];
            }

            // Return the clean JSON string
            return [
                'success' => true,
                'response' => $text
            ];

        } catch (\Exception $e) {
            Log::error('SuggestProductService error:', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get AI response analyzing an image (optional) + run context
     */
    public function getImageAnalysisResponse(int $userId, string $prompt, string $imageFullPath, array $context = []): array
    {
        return $this->processRequest($prompt, $imageFullPath, $context);
    }

    /**
     * Core request handler
     */
    protected function processRequest(string $prompt, ?string $imageFullPath = null, array $context = []): array
    {
        try {
            $messages = $this->buildMessages($prompt, $context, $imageFullPath);

            $model = $imageFullPath ? 'gpt-4o' : 'gpt-4';

            $response = $this->callOpenAi($messages, $model);

            if (!$response['successful']) {
                return $this->errorResponse($response['error'] ?? 'Unknown error', 'AI request failed');
            }

            return $this->handleApiResponse($response['data']);
        } catch (\Exception $e) {
            Log::error('OpenAiChatService error: ' . $e->getMessage());
            return $this->errorResponse($e->getMessage(), 'AI request failed');
        }
    }

    /**
     * Build messages for OpenAI, including beauty product context
     */
    protected function buildMessages(string $prompt, array $context = [], ?string $imageFullPath = null): array
    {
        $systemPrompt = <<<SYSTEM
You are a beauty product suggestion and cosmetic analysis assistant.

Your responsibilities:
- Analyze skincare and cosmetic product ingredients provided by the user or visible in the image (e.g., ingredients like Hyaluronic Acid: A powerful humectant that can hold up to 1000x its weight in water, providing intense hydration. Safe, Hydrating.; Vitamin C: An antioxidant that brightens skin and helps with collagen production. Safe, Hydrating.; Niacinamide: Helps minimize pores, regulate oil production, and improve skin texture. Safe, Hydrating.)
- The ingredients details come directly from the system prompt or user input—use them as the primary source for analysis, treating them as authoritative descriptions from the product page.
- Structure the ingredients in the response as an array of objects, each with name, description, and tags (e.g., ["Safe", "Hydrating"])
- Generate 3 realistic customer reviews based on the product's description and ingredients
- Each review should include a realistic name, star rating (1-5 stars), short comment, date (e.g., "1 week ago"), and helpful count (e.g., 2)
- Provide how-to-use instructions for the product
- Identify possible allergens, irritants, sensitivities, and dietary restrictions (e.g., vegan or non-vegan)
- Generate a short, consumer-friendly AI summary
- Compile a warnings section highlighting key allergens, irritants, and safety notes based on ingredients
- Generate a concise AI summary (50 words max) as a catchy overview highlighting key benefits, rating, and star ingredients

STRICT RULES:
- Do NOT give medical advice
- Do NOT diagnose or treat skin conditions
- Do NOT make clinical or pharmaceutical claims
- Base analysis only on provided ingredients
- Summary must be plain text, no HTML
- Limit summary to 150 words or less
- Limit how_to_use to 100 words or less
- Limit warnings to 100 words or less, formatted as plain text
- Limit ai_summary to 50 words or less
- Return ALL output in valid JSON ONLY
- Do NOT include markdown, explanations, or extra text
- Use the exact JSON structure defined below
- Do NOT send any null values, always provide data in the specified format

REQUIRED JSON FORMAT:
{
  "ai_summary": "string (150 words max, single paragraph)",
  "summary": "string (50 words max, catchy overview)",
  "ingredients": [
    {
      "name": "string",
      "description": "string",
      "tags": ["string"]
    }
  ],
  "reviews": [
    {
      "name": "string (e.g., Emily R.)",
      "stars": integer (1-5),
      "comment": "string (short review text)",
      "date": "string (e.g., 1 week ago)",
      "helpful": integer (e.g., 2)
    }
  ],
  "how_to_use": "string (100 words max, step-by-step instructions)",
  "warnings": "string (100 words max, plain text with key allergens and safety notes)"
}

- JSON must contain exactly 3 review objects in the array
- No fields should be missing
- Always reference the provided ingredients in your summary and analysis
- Ingredients array must include name, description, and tags
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

        // Append context if provided
        if (!empty($context)) {
            $messages[] = ['role' => 'system', 'content' => json_encode($context)];
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
        try {
            $payload = [
                'model'       => $model,
                'messages'    => $messages,
                'temperature' => 0.6,
                'max_tokens'  => 2000,
            ];

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type'  => 'application/json',
            ])->timeout(120)->post($this->endpoint, $payload);

            if (!$response->successful()) {
                $error = $response->json()['error']['message'] ?? $response->body();
                Log::error("OpenAI API Error ($model)", ['error' => $error]);

                return [
                    'successful' => false,
                    'error' => $error
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
     * Handle OpenAI response
     */
    protected function handleApiResponse(array $response): array
    {
        if (empty($response['choices'][0]['message']['content'])) {
            Log::error('OpenAI API empty content: ' . json_encode($response));
            return $this->errorResponse('No content in response', 'Invalid AI response structure');
        }

        $content = $response['choices'][0]['message']['content'];

        // Clean up markdown code blocks if present
        $content = preg_replace('/```json\s*/', '', $content);
        $content = preg_replace('/\s*```/', '', $content);
        $content = trim($content);

        return [
            'success'       => true,
            'response'      => $content,
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
