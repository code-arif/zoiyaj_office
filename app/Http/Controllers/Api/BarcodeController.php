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




        return response()->json([
            'status' => true,
            'message' => "Beauty Product Info Retrieved Successfully",
            'data' => $result,
        ]);
    }
}
