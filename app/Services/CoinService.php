<?php
namespace App\Services;

use App\Models\CoinTransaction;
use App\Models\CoinType;
use App\Models\User;

class CoinService
{
    public static function rewardUser($userId, $typeSlug)
    {
        // Get coin type by slug
        $coinType = CoinType::where('slug', $typeSlug)->first();
        if (! $coinType) {
            return false;
        }

        // Get type settings (relationship check)
        $setting = $coinType->setting;
        if (! $setting) {
            return false;
        }


        // Get user
        $user = User::find($userId);
        if (! $user) {
            return false;
        }

        // Add coins safely
        $currentCoin = $user->coin ?? 0;
        $totalCoin   = $currentCoin + $setting->coins;

        $user->coin = $totalCoin;
        $user->save();


        // Log transaction
        CoinTransaction::create([
            'user_id'      => $userId,
            'coin_type_id' => $coinType->id,
            'coins'        => $setting->coins,
            'description'  => ucfirst(str_replace('-', ' ', $typeSlug)) . ' reward',
        ]);

        return true;
    }
}
