<?php

namespace App\Http\Controllers\Api\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Models\User;
use App\Traits\ApiResponse;

class WebHookController extends Controller
{
    use ApiResponse;

    public function handleWebhook(Request $request)
    {
        Log::info('=== RevenueCat Webhook Received ===');
        Log::info('Raw Request:', ['request' => $request->all()]);

        $authorizationHeader = $request->header('Authorization');
        $expectedToken = config('services.revenuecat.webhook_secret');

        Log::info('Authorization Header:', ['header' => $authorizationHeader]);
        Log::info('Expected Token:', ['token' => $expectedToken]);

        if (!$authorizationHeader || !hash_equals($expectedToken, $authorizationHeader)) {
            Log::error('Unauthorized RevenueCat webhook request.');
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $event = $request->input('event');
        $userId = $event['app_user_id'] ?? null;
        $eventType = $event['type'] ?? null;

        if (!$userId || !$eventType) {
            Log::error('Invalid payload: missing user_id or event type.');
            return response()->json(['message' => 'Invalid payload'], 400);
        }

        $userId = preg_replace('/\D/', '', $userId);
        $user = User::find($userId);

        if (!$user) {
            Log::warning("User not found for webhook: $userId");
            return response()->json(['message' => 'User not found'], 404);
        }

        $productId = $event['product_id'] ?? null;
        $newProductId = $event['new_product_id'] ?? null;

        $getPackage = function ($id) {
            if (!$id) return null;
            if (Str::contains($id, '0001_1m')) return 'monthly';
            if (Str::contains($id, '0001_1y')) return 'yearly';
            return null;
        };

        switch ($eventType) {
            case 'INITIAL_PURCHASE':
            case 'RENEWAL':
                $user->product_id = $productId;
                $user->package = $getPackage($productId);
                $user->is_subscribed = true;
                break;

            case 'PRODUCT_CHANGE':
                $user->product_id = $newProductId;
                $user->package = $getPackage($newProductId);
                $user->is_subscribed = true;
                break;

            case 'CANCELLATION':
            case 'EXPIRATION':
                $user->product_id = null;
                $user->package = null;
                $user->is_subscribed = false;
                break;

            case 'TRANSFER':
                Log::info("User {$userId} subscription transferred.");
                break;

            default:
                Log::info("Unhandled RevenueCat event type: $eventType");
                return response()->json(['message' => "Event type '$eventType' ignored"]);
        }

        $user->save();

        Log::info("✅ User {$userId} updated successfully", [
            'product_id' => $user->product_id,
            'package' => $user->package,
            'is_subscribed' => $user->is_subscribed,
        ]);

        return response()->json(['message' => 'Webhook processed successfully']);
    }

}
