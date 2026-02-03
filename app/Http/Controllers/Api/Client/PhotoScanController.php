<?php
namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PhotoScanController extends Controller
{
    public function analyze(Request $request)
    {
        // Step 0: Enhanced validation with custom rules
        $request->validate([
            'image' => [
                'required',
                'image',
                'mimes:jpeg,png,jpg',
                'max:10240', // 10MB in KB (Perfect Corp limit <10MB)
                function ($attribute, $value, $fail) {
                    // Get image dimensions
                    list($width, $height) = getimagesize($value->path());

                    if ($width < 100 || $height < 100) {
                        $fail('Image resolution must be at least 100x100 pixels.');
                    }

                    $longSide = max($width, $height);
                    if ($longSide > 4096) {
                        $fail('Image long side must not exceed 4096 pixels.');
                    }

                                                  // Optional: Aspect ratio check (portrait preferred, but not strict)
                    if ($width > $height * 1.5) { // too wide (landscape)
                                                      // Not failing, just warning in log
                        Log::warning('Uploaded image is landscape-oriented; portrait recommended for better face detection.');
                    }
                },
            ],
        ]);

        $imageFile = $request->file('image');
        $apiKey    = env('SKIN_API_KEY');

        if (! $apiKey) {
            return response()->json([
                'status'  => 'error',
                'message' => 'SKIN_API_KEY is not configured in .env',
                'code'    => 500,
            ], 500);
        }

        // ────────────────────────────────────────────────
        // Step 1: Initialize file upload
        // ────────────────────────────────────────────────
        $initResponse = Http::withHeaders([
            'Authorization' => 'Bearer ' . $apiKey,
            'Content-Type'  => 'application/json',
        ])->post('https://yce-api-01.makeupar.com/s2s/v2.0/file/skin-analysis', [
            'files' => [
                [
                    'content_type' => $imageFile->getMimeType(),
                    'file_name'    => $imageFile->getClientOriginalName(),
                    'file_size'    => $imageFile->getSize(),
                ],
            ],
        ]);

        if ($initResponse->failed()) {
            Log::error('File init failed', ['response' => $initResponse->body()]);
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to initialize file upload with Perfect Corp',
                'details' => $initResponse->json() ?? $initResponse->body(),
                'code'    => $initResponse->status(),
            ], $initResponse->status() ?: 500);
        }

        $fileData = $initResponse->json()['data']['files'][0] ?? null;
        if (! $fileData || empty($fileData['requests'][0]['url'])) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Invalid response from file initialization',
                'details' => $initResponse->json(),
            ], 500);
        }

        $uploadUrl = $fileData['requests'][0]['url'];
        $fileId    = $fileData['file_id'];

        // ────────────────────────────────────────────────
        // Step 2: Upload image
        // ────────────────────────────────────────────────
        $uploadResponse = Http::withHeaders([
            'Content-Type'   => $imageFile->getMimeType(),
            'Content-Length' => $imageFile->getSize(),
        ])->withBody(file_get_contents($imageFile->path()), $imageFile->getMimeType())
            ->put($uploadUrl);

        if ($uploadResponse->failed()) {
            Log::error('Image upload failed', [
                'status' => $uploadResponse->status(),
                'body'   => $uploadResponse->body(),
            ]);
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to upload image to Perfect Corp storage',
                'details' => $uploadResponse->json() ?? $uploadResponse->body(),
                'code'    => $uploadResponse->status(),
            ], $uploadResponse->status() ?: 500);
        }

        // ────────────────────────────────────────────────
        // Step 3: Create task
        // ────────────────────────────────────────────────
        $taskResponse = Http::withHeaders([
            'Authorization' => 'Bearer ' . $apiKey,
            'Content-Type'  => 'application/json',
        ])->post('https://yce-api-01.makeupar.com/s2s/v2.0/task/skin-analysis', [
            'src_file_id'     => $fileId,
            'dst_actions'     => ['acne', 'pore', 'texture', 'wrinkle'],
            'miniserver_args' => ['enable_mask_overlay' => true],
            'format'          => 'json',
        ]);

        if ($taskResponse->failed()) {
            Log::error('Task creation failed', ['details' => $taskResponse->body()]);
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to create skin analysis task',
                'details' => $taskResponse->json() ?? $taskResponse->body(),
                'code'    => $taskResponse->status(),
            ], $taskResponse->status() ?: 500);
        }

        $taskId = $taskResponse->json()['data']['task_id'] ?? null;
        if (! $taskId) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Task ID not received from API',
                'details' => $taskResponse->json(),
            ], 500);
        }

                           // ────────────────────────────────────────────────
                           // Step 4: Poll for result (with timeout handling)
                           // ────────────────────────────────────────────────
        $maxAttempts = 90; // Increased slightly for safety (1.5 min)
        $results     = null;

        for ($i = 0; $i < $maxAttempts; $i++) {
            sleep(1);

            $statusResponse = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
            ])->get("https://yce-api-01.makeupar.com/s2s/v2.0/task/skin-analysis/{$taskId}");

            if ($statusResponse->failed()) {
                Log::error('Status check failed', ['body' => $statusResponse->body()]);
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Failed to poll task status',
                    'details' => $statusResponse->json() ?? $statusResponse->body(),
                ], 500);
            }

            $json   = $statusResponse->json();
            $status = $json['data']['task_status'] ?? 'unknown';

            if ($status === 'success') {
                $results = $json['data']['results'] ?? [];
                break;
            } elseif ($status === 'error') {
                $apiError = $json['data']['error'] ?? $json;
                Log::error('Skin analysis task failed', ['details' => $apiError]);
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Skin analysis failed: ' . ($apiError['message'] ?? 'Unknown error'),
                    'code'    => 404,
                    'details' => $apiError,
                ], 422); // 422 for validation-like API errors
            }
        }

        if ($results === null) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Analysis timeout after ' . $maxAttempts . ' seconds',
                'code'    => 'timeout',
            ], 504);
        }

        // ────────────────────────────────────────────────
        // Step 5: Extract concerns
        // ────────────────────────────────────────────────
        $allConcerns = [];
        foreach ($results['output'] ?? [] as $concern) {
            if (isset($concern['type']) &&
                ! in_array($concern['type'], ['all', 'skin_age', 'resize_image']) &&
                isset($concern['ui_score']) || isset($concern['score'])) {
                $allConcerns[] = $concern['type'];
            }
        }

        $recommendedProducts = $this->getProductRecommendations($allConcerns);

        // ────────────────────────────────────────────────
        // Final Success Response (consistent format)
        // ────────────────────────────────────────────────
        return response()->json([
            'status'               => 'success',
            'message'              => 'Skin analysis completed successfully',
            'results'              => $results,
            'recommended_products' => $recommendedProducts,
        ]);
    }

    /**
     * Fetch skincare product recommendations from Google Shopping via SerpApi
     * for ALL detected concerns (pore, texture, wrinkle, acne, etc.)
     */
    private function getProductRecommendations(array $concerns)
    {
        $products   = [];
        $serpApiKey = env('SERPAPI_KEY');

        if (! $serpApiKey) {
            Log::warning('SERPAPI_KEY not set - skipping products');
            return $products;
        }

        foreach ($concerns as $concern) {
            $query = ucfirst($concern) . ' skincare product recommendation';

            try {
                $response = Http::get('https://serpapi.com/search', [
                    'engine'   => 'google_shopping',
                    'q'        => $query,
                    'api_key'  => $serpApiKey,
                    'num'      => 3,
                    'location' => 'United States', // optional - better results
                    'gl'       => 'us',            // country code
                    'hl'       => 'en',            // language
                    'device'   => 'mobile',        // mobile results (better for app)
                ]);

                if ($response->successful()) {
                    $shoppingResults = $response->json()['shopping_results'] ?? [];
                    foreach ($shoppingResults as $item) {
                        // Full direct purchase link (not shortened)
                        $directLink = $item['link'] ?? $item['source_link'] ?? $item['product_link'] ?? '#';

                        $products[] = [
                            'title'     => $item['title'] ?? 'N/A',
                            'price'     => $item['price'] ?? $item['extracted_price'] ?? 'N/A',
                            'store'     => $item['source'] ?? 'Unknown Store',
                            'link'      => $directLink,
                            'thumbnail' => $item['thumbnail'] ?? null,
                            'concern'   => $concern,
                        ];
                    }
                }
            } catch (\Exception $e) {
                Log::warning("SerpApi failed for {$concern}", ['error' => $e->getMessage()]);
            }
        }

        return array_slice($products, 0, 12);
    }
}
