<?php
namespace App\Http\Controllers\Api\User;

use App\Models\Wishlist;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;

class WishlistController extends Controller
{
    use ApiResponse;





    // index
    public function index(Request $request)
    {
        $user = auth('api')->user();

        $wishlistedBooks = Wishlist::with('book')
            ->where('user_id', $user->id)
            ->get();



        $data = $wishlistedBooks->map(function ($wishlist) {
            $book = $wishlist->book;
            return [
                'id'          => $book->id,
                'title'       => $book->title ?? null,
                'type' => $book->type,
                'author'      => $book->author ?? null,
                'cover_image' => $book->cover_image ? url($book->cover_image) : null,
                'description' => $book->description ?? null,
                'added_at'    => $wishlist->added_at,
                'rating'      => $book->book_reviews->average('rating') ?? 0,
                'no_of_reviews' => $book->book_reviews->count() ?? 0,
                'is_bookmarked' => $wishlist ? true : false,

            ];
        });

        return $this->success($data, 'Wishlist retrieved successfully.');
    }










    public function toggle(Request $request)
    {

        $user = auth('api')->user();
        $book_id = $request->book_id;

        $exists = Wishlist::where('user_id', $user->id)
            ->where('book_id', $book_id)
            ->exists();

        if ($exists) {
            Wishlist::where('user_id', $user->id)
                ->where('book_id', $book_id)
                ->delete();
            $message    = 'Removed from wishlist';
            $inWishlist = false;
        } else {
            Wishlist::create([
                'user_id' => $user->id,
                'book_id' => $book_id,
            ]);
            $message    = 'Added to wishlist';
            $inWishlist = true;
        }

        $count = $user->wishlistedBooks()->count();


        $data = [
            'in_wishlist' => $inWishlist,
            'total_count' => $count
        ];

        return $this->success($data, $message);
    }
}
