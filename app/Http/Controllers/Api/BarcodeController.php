<?php

namespace App\Http\Controllers\api;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Http\Controllers\OpenAiChatController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Services\OpenAiChatService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Log;
use Illuminate\Support\Facades\Validator;

class BarcodeController extends Controller
{
    protected $openAiChatService;


 public function getProduct() {
    $barcode = request()->query('barcode');

    $response = Http::get("https://world.openbeautyfacts.org/api/v2/product/{$barcode}");

    if (!$response->successful()) {
        return response()->json([
            'status' => false,
            'message' => 'Product not found or API error'
        ], 404);
    }

    $data = $response->json();
    $product = $data['product'] ?? null;

    if (!$product) {
        return response()->json([
            'status' => false,
            'message' => 'Product not found'
        ], 404);
    }

    // Get AI suggestions
    $des = null;
    try {
        $aiResponse = Helper::suggestService($product['ingredients_text'] ?? '', []);

        Log::info('AI Response from Helper:', ['response' => $aiResponse]);

        // Decode the JSON string
        if ($aiResponse) {
            $des = json_decode($aiResponse, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::error('JSON decode error:', [
                    'error' => json_last_error_msg(),
                    'raw' => $aiResponse
                ]);
                $des = ['error' => 'Failed to parse AI response'];
            }
        } else {
            $des = ['error' => 'No AI response'];
        }

    } catch (\Exception $e) {
        Log::error('AI suggestion error:', ['error' => $e->getMessage()]);
        $des = ['error' => 'AI service unavailable'];
    }

    // Extract ingredients details
    $ingredients = [];
    if (!empty($product['ingredients'])) {
        foreach ($product['ingredients'] as $ingredient) {
            $ingredients[] = [
                'name' => $ingredient['text'] ?? 'Unknown',
                'hazard' => $ingredient['hazard_level'] ?? 'Safe',
                'function' => $ingredient['function'] ?? null
            ];
        }
    }

    // Extract image
    $image = $product['selected_images']['front']['display']['ar']
             ?? $product['image_front_small_url']
             ?? null;

    $result = [
        'name' => $product['product_name'] ?? 'Unknown',
        'brands' => $product['brands'] ?? 'Unknown',
        'image' => $image,
        'ai_suggestions' => $des,
    ];

    return response()->json([
        'status' => true,
        'message' => "Beauty Product Info Retrieved Successfully",
        'data' => $result,
    ]);
}
}
