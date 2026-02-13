<?php
namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Bookmark;
use App\Models\PointTransaction;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
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
                'id'                => $professional->id,
                'professional_name' => $professional->professional_name,
                'thumb'             => $professional->thumb,
                'location'          => $professional->address . ', ' . $professional->city . ', ' . $professional->state . ', ' . $professional->country,
                'added_at'          => $bookmark->added_at,
                'total_rating'      => $professional->professionalReviews->avg('rating') ?? 0,
                'no_of_reviews'     => $professional->professionalReviews->count() ?? 0,
                'services'          => $professional->services()->first(),
                'working_hours'     => $professional->working_hours()->first(),
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

        $user = auth('api')->user();

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
                'client_id'       => $user->id,
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

    public function getPointHistory(Request $request)
    {
        $user = auth('api')->user();

        $history = PointTransaction::where('user_id', $user->id)
            ->where('user_type', $user->is_professional ? 'professional' : 'client')
            ->orderBy('created_at', 'desc')
        // ->with(['booking.serviceBookings.service'])
            ->get();

        // Format response nicely
        $formatted = $history->map(function ($transaction) {

            $booking = Booking::find($transaction->booking_id);

            $description =  $this->getDefaultDescription($transaction->action);

            return [
                'id'             => $transaction->id,
                'points'         => (int) $transaction->points,
                'action'         => $transaction->action,
                'description'    => $description,
                'booking_number' => $booking ? $booking->booking_number : null,
                'booking_id'     => $booking ? $booking->id : null,
                'date'           => $transaction->created_at->format('Y-m-d H:i:s'),
                'human_date'     => $transaction->created_at->diffForHumans(), // e.g., "2 hours ago"
                'type'           => $transaction->points > 0 ? 'Earned' : 'Redeemed',
                'sign'           => $transaction->points > 0 ? '+' : '-',
                'amount'         => abs($transaction->points), // positive number for display
            ];
        });

        return $this->success([

            'history' => $formatted,
        ], 'Point history fetched successfully', 200);
    }

    private function getDefaultDescription(string $action): string
    {
        $map = [
            'checkin_confirm' => 'Earned points for confirming check-in',
            'booking_confirm' => 'Earned points for appointment confirmation',
            'redeem'          => 'Redeemed points for service discount',
            'earn_service'    => 'Earned points from completed service',
            'referral'        => 'Earned referral bonus points',

        ];

        return $map[$action] ?? ucfirst(str_replace('_', ' ', $action));
    }
}
