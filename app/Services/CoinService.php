<?php
namespace App\Services;

use App\Models\CoinTransaction;
use App\Models\CoinType;
use App\Models\PointTransaction;
use App\Models\User;

use function Symfony\Component\Clock\now;

class CoinService
{
    public static function rewardUser($user_id, $user_type,  $booking_id, $points, $action)
    {

        // Get user
        $user = User::find($user_id);
        if (! $user) {
            return false;
        }

        // Add coins safely
        $currentCoin = $user->total_redeem_points ?? 0;
        $totalCoin   = $currentCoin + $points;

        $user->total_redeem_points = $totalCoin;
        $user->save();


        // Log transaction
        PointTransaction::create([
            'user_id'      => $user_id,
            'booking_id' => $booking_id,
            'user_type' => $user_type,
            'points' => $points,
            'action' => $action,
            'confirm_date' => now()
        ]);

        return true;
    }
}
