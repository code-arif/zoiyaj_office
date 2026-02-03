<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FollowerController extends Controller
{
    /**
     * Follow a professional
     */
    public function follow(Request $request, $professionalId)
    {
        $follower     = auth('api')->user();
        $professional = User::where('id', $professionalId)
            ->where('role', 'professional') // only professionals can be followed
            ->firstOrFail();

        // Prevent self-follow
        if ($follower->id === $professional->id) {
            return response()->json(['message' => 'Cannot follow yourself'], 400);
        }

        // Already following?
        if ($professional->isFollowedBy($follower)) {
            return response()->json(['message' => 'Already following'], 409);
        }

        DB::transaction(function () use ($follower, $professional) {
            $professional->followers()->attach($follower->id);
            $professional->incrementFollowersCount();
        });

        return response()->json([
            'message'         => 'Followed successfully',
            // 'followers_count' => $professional->followers_count,
        ], 200);
    }
}
