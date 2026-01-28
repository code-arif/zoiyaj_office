<?php
namespace App\Http\Controllers\Api\User;

use App\Models\Wishlist;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Bookmark;
use Illuminate\Support\Facades\Validator;

class WishlistController extends Controller
{
    use ApiResponse;

    // index
    public function index(Request $request)
    {
        $user = auth('api')->user();

        $bookmarks = Bookmark::with('professional')
            ->where('client_id', $user->id)
            ->get();


        $data = $bookmarks->map(function ($bookmark) {
            $professional = $bookmark->professional;
            return [
                'id'            => $professional->id,
                'professional_name' => $professional->professional_name,
                'thumb'         => $professional->thumb,
                'location'      => $professional->address . ', ' . $professional->city . ', ' . $professional->state . ', ' . $professional->country,
                'added_at'      => $bookmark->added_at,
                'total_rating'        => $professional->professionalReviews->avg('rating') ?? 0,
                'no_of_reviews' => $professional->professionalReviews->count() ?? 0,
                'services'      => $professional->services()->first(),
                'working_hours' => $professional->working_hours()->first(),
            ];
        });

        return $this->success($data, 'Bookmark retrieved successfully.');
    }

    public function toggle(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'professional_id' => 'required|exists:users,id',
        ]);

        if ($validator->fails()) {
            return $this->error([], $validator->errors()->first(), 422);
        }

        $user    = auth('api')->user();


        $professional_id = $request->professional_id;

        $exists = Bookmark::where('client_id', $user->id)
            ->where('professional_id', $professional_id)
            ->exists();

        if ($exists) {
            Bookmark::where('client_id', $user->id)
                ->where('professional_id', $professional_id)
                ->delete();
            $message    = 'Removed from bookmark';
            $inWishlist = false;
        } else {
            Bookmark::create([
                'client_id' => $user->id,
                'professional_id' => $professional_id,
            ]);
            $message    = 'Added to bookmark';
            $inWishlist = true;
        }

        $count = $user->bookmarks()->count();

        $data = [
            'in_wishlist' => $inWishlist,
            'total_count' => $count,
        ];

        return $this->success($data, $message);
    }
}
