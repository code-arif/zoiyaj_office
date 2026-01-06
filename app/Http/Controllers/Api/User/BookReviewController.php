<?php
namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BookReviewController extends Controller
{
    use ApiResponse;

    public function store(Request $request)
    {
        $user = auth('api')->user();

        $validator = Validator::make($request->all(), [
            'book_id' => 'required|exists:books,id',
            'rating'  => 'required|integer|min:1|max:5',
            'review'  => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->error([], $validator->errors()->first(), 404);
        }

        // Check if the user has already reviewed the book
        $existingReview = $user->book_reviews()->where('book_id', $request->book_id)->first();

        if ($existingReview) {
            // Update existing review
            $existingReview->update([
                'rating' => $request->rating,
                'review' => $request->review,
            ]);

            return $this->success($existingReview, 'Book review updated successfully.');
        } else {
            // Create new review
            $newReview = $user->book_reviews()->create([
                'book_id' => $request->book_id,
                'rating'  => $request->rating,
                'review'  => $request->review,
            ]);

            return $this->success($newReview, 'Book review created successfully.');
        }
    }

}
