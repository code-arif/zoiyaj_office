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
        $request->validate(['image' => 'required|image|mimes:jpeg,png,jpg|max:10240']);

        $imageFile = $request->file('image');
        $apiKey    = env('SKIN_API_KEY');

        if (!$apiKey) {
            return response()->json(['error' => 'SKIN_API_KEY is not configured in .env'], 500);
        }

        // ────────────────────────────────────────────────
        // Step 1: Initialize file upload to Perfect Corp
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
                'error'   => 'Failed to initialize file upload',
                'details' => $initResponse->body(),
            ], 500);
        }

        $fileData  = $initResponse->json()['data']['files'][0];
        $uploadUrl = $fileData['requests'][0]['url'];
        $fileId    = $fileData['file_id'];

        // ────────────────────────────────────────────────
        // Step 2: Upload image to pre-signed URL
        // ────────────────────────────────────────────────
        $uploadResponse = Http::withHeaders([
            'Content-Type'   => $imageFile->getMimeType(),
            'Content-Length' => $imageFile->getSize(),
        ])->withBody(file_get_contents($imageFile->path()), $imageFile->getMimeType())
          ->put($uploadUrl);

        if ($uploadResponse->failed()) {
            Log::error('Image upload to Perfect Corp failed', [
                'status' => $uploadResponse->status(),
                'body'   => $uploadResponse->body(),
            ]);
            return response()->json([
                'error'  => 'Failed to upload image',
                'status' => $uploadResponse->status(),
                'body'   => $uploadResponse->body(),
            ], 500);
        }

        // ────────────────────────────────────────────────
        // Step 3: Create skin analysis task
        // ────────────────────────────────────────────────
        $taskResponse = Http::withHeaders([
            'Authorization' => 'Bearer ' . $apiKey,
            'Content-Type'  => 'application/json',
        ])->post('https://yce-api-01.makeupar.com/s2s/v2.0/task/skin-analysis', [
            'src_file_id'     => $fileId,
            'dst_actions'     => [
                'acne', 'pore', 'texture', 'wrinkle',
                // Add more if you want (SD mode supports these)
                // 'redness', 'oiliness', 'radiance', 'moisture', 'age_spot', 'dark_circle', 'firmness'
            ],
            'miniserver_args' => [
                'enable_mask_overlay' => true,
            ],
            'format'          => 'json',
        ]);

        if ($taskResponse->failed()) {
            Log::error('Task creation failed', ['details' => $taskResponse->body()]);
            return response()->json([
                'error'   => 'Failed to create analysis task',
                'details' => $taskResponse->body(),
            ], 500);
        }

        $taskId = $taskResponse->json()['data']['task_id'];

        // ────────────────────────────────────────────────
        // Step 4: Poll for analysis result
        // ────────────────────────────────────────────────
        $maxAttempts = 60;
        $results = null;

        for ($i = 0; $i < $maxAttempts; $i++) {
            sleep(1);

            $statusResponse = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
            ])->get("https://yce-api-01.makeupar.com/s2s/v2.0/task/skin-analysis/{$taskId}");

            $json   = $statusResponse->json();
            $status = $json['data']['task_status'] ?? 'running';

            if ($status === 'success') {
                $results = $json['data']['results'] ?? [];
                break;
            } elseif ($status === 'error') {
                Log::error('Skin analysis failed', ['details' => $json]);
                return response()->json([
                    'error'   => 'Skin analysis failed',
                    'details' => $json,
                ], 500);
            }
        }

        if ($results === null) {
            return response()->json(['error' => 'Analysis timeout after polling'], 504);
        }

        // ────────────────────────────────────────────────
        // Step 5: Collect ALL concerns (not just low score)
        // ────────────────────────────────────────────────
        $allConcerns = [];
        foreach ($results['output'] ?? [] as $concern) {
            if (isset($concern['type']) &&
                $concern['type'] !== 'all' &&
                $concern['type'] !== 'skin_age' &&
                $concern['type'] !== 'resize_image')
            {
                $allConcerns[] = $concern['type'];
            }
        }

        // Get recommended products for ALL concerns
        $recommendedProducts = $this->getProductRecommendations($allConcerns);

        // ────────────────────────────────────────────────
        // Final Response
        // ────────────────────────────────────────────────
        return response()->json([
            'status'               => 'success',
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
        $products = [];
        $serpApiKey = env('SERPAPI_KEY');

        if (!$serpApiKey) {
            Log::warning('SERPAPI_KEY not set in .env - skipping product recommendations');
            return $products;
        }

        // Remove duplicates to avoid too many API calls
        $uniqueConcerns = array_unique($concerns);

        foreach ($uniqueConcerns as $concern) {
            $query = ucfirst($concern) . ' skincare product recommendation';

            try {
                $response = Http::get('https://serpapi.com/search', [
                    'engine'  => 'google_shopping',
                    'q'       => $query,
                    'api_key' => $serpApiKey,
                    'num'     => 3, // 3 products per concern
                ]);

                if ($response->successful()) {
                    $shoppingResults = $response->json()['shopping_results'] ?? [];
                    foreach ($shoppingResults as $item) {
                        $products[] = [
                            'title'     => $item['title'] ?? 'N/A',
                            'price'     => $item['price'] ?? 'N/A',
                            'link'      => $item['link'] ?? '#',
                            'thumbnail' => $item['thumbnail'] ?? null,
                            'concern'   => $concern,
                        ];
                    }
                }
            } catch (\Exception $e) {
                Log::warning("SerpApi failed for concern: {$concern}", ['error' => $e->getMessage()]);
            }
        }

        // Limit total products (avoid UI overload)
        return array_slice($products, 0, 12);
    }
}
