<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FollowerController extends Controller
{
    use ApiResponse;
    /**
     * Follow a professional
     */
    public function follow(Request $request, $professionalId)
    {
        $follower = auth('api')->user();

        $professional = User::where('id', $professionalId)->where('role', 'professional')->firstOrFail();

        // Prevent self-follow
        if ($follower->id === $professional->id) {
            // return response()->json(['message' => 'Cannot follow yourself'], 400);
            return $this->error([], 'You cannot follow yourself', 400);
        }

        // Already following?
        if ($professional->isFollowedBy($follower)) {

            return $this->success([], 'Already following');
        }

        DB::transaction(function () use ($follower, $professional) {
            $professional->followers()->attach($follower->id);
            $professional->incrementFollowersCount();
        });

        return $this->success($professional->followers(), 'Followed successfully');
    }

    public function unfollow(Request $request, $professionalId)
    {

        $follower = auth('api')->user();

        $professional = User::where('id', $professionalId)
            ->where('role', 'professional')
            ->firstOrFail();

        // Prevent self-unfollow (optional safety)
        if ($follower->id === $professional->id) {
            return response()->json(['message' => 'Cannot unfollow yourself'], 400);
        }

        // Not following?
        if (! $professional->isFollowedBy($follower)) {
            return $this->error([], 'You are not following this professional', 409);
        }

        DB::transaction(function () use ($follower, $professional) {
            $professional->followers()->detach($follower->id);
            $professional->decrementFollowersCount();
        });

        return $this->success(null, 'Unfollowed successfully');
    }

}
