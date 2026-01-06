<?php

namespace App\Http\Controllers\Api\Seller;

use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;

class DashboardController extends Controller
{
    use ApiResponse;


    public function review_list(Request $request)
    {
        $user = auth('api')->user();


        $bookReviews = DB::table('book_reviews')
            ->join('users', 'book_reviews.user_id', '=', 'users.id')
            ->join('books', 'book_reviews.book_id', '=', 'books.id')
            ->where('books.uploaded_by', $user->id)
            ->select('book_reviews.*', 'users.first_name', 'users.last_name', 'users.avatar')
            ->get();

        if ($bookReviews->isEmpty()) {
            return $this->error([], 'No Book reviews found');
        }

        $bookReviews = $bookReviews->map(function ($review) {
            return [
                'id'         => $review->id,
                'book_id'    => $review->book_id,
                'user_id'    => $review->user_id,
                'rating'     => $review->rating,
                'review'     => $review->review,
                'first_name' => $review->first_name,
                'last_name'  => $review->last_name,
                'avatar'     => $review->avatar ? url($review->avatar) : null,
                'created_at' => $review->created_at,
                'updated_at' => $review->updated_at,
            ];
        });

        return $this->success($bookReviews, 'Seller Book reviews retrieved successfully');

    }











}
