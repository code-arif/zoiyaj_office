<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class BarcodeController extends Controller
{

public function getProduct()
{
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

    // Extract warnings / preference alerts
    $alerts = [];
    if (!empty($product['allergens'])) {
        $alerts[] = [
            'type' => 'Allergens',
            'message' => implode(', ', $product['allergens'])
        ];
    }
    if (!empty($product['preference'])) {
        $alerts[] = [
            'type' => 'Preference',
            'message' => $product['preference']
        ];
    }

    // Extract image
    $image = $product['selected_images']['front']['display']['ar'] ?? $product['image_front_small_url'] ?? null;

    // Optional: similar products
    $similarProducts = [];
    if (!empty($product['similar_products'])) {
        foreach ($product['similar_products'] as $similar) {
            $similarProducts[] = [
                'name' => $similar['product_name'] ?? 'Unknown',
                'image' => $similar['image_front_small_url'] ?? null,
                'price' => $similar['price'] ?? null,
                'rating' => $similar['rating'] ?? null,
            ];
        }
    }

    $result = [
        'name' => $product['product_name'] ?? 'Unknown',
        'brands' => $product['brands'] ?? 'Unknown',
        'categories' => $product['categories'] ?? [],
        'ingredients_text' => $product['ingredients_text'] ?? 'Not listed',
        'ingredients' => $ingredients,
        'image' => $image,
        'alerts' => $alerts,
        'description' => $product['description'] ?? $product['generic_name'] ?? null,
        'reviews' => $product['reviews'] ?? [], // if available
        'similar_products' => $similarProducts
    ];

    return response()->json([
        'status' => true,
        'message' => "Beauty Product Info Retrieved Successfully",
        'data' => $result,
    ]);
}

}
