<?php
namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\UserBookCompletion;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BookCompletionController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $user = auth('api')->user();

        $book_completions = UserBookCompletion::with('book')
            ->where('user_id', $user->id)
            ->get();

        $data = $book_completions->map(function ($wishlist) {
            $book = $wishlist->book;
            return [
                'id'            => $book->id,
                'title'         => $book->title ?? null,
                'type' => $book->type,

                'author'        => $book->author ?? null,
                'cover_image'   => $book->cover_image ? url($book->cover_image) : null,
                'description'   => $book->description ?? null,
                'added_at'      => $wishlist->added_at,
                'rating'        => $book->book_reviews->average('rating') ?? 0,
                'no_of_reviews' => $book->book_reviews->count() ?? 0,
                'is_completed' => $wishlist ? true : false,

            ];
        });

        return $this->success($data, 'Book Completion list retrieved successfully.');
    }

    /**
     * Toggle Read Complete for eBook
     */
    public function toggle(Request $request)
    {
        $request->validate([
            'book_id' => 'required|exists:books,id',
            'note'    => 'nullable|string|max:500',
        ]);

        $user = Auth::guard('api')->user();
        $book = Book::findOrFail($request->book_id);

        // eBook
        if ($book->type !== 'ebook') {
            return $this->error([], 'You can only mark eBooks as read', 403);
        }

        // (soft delete)
        $completion = UserBookCompletion::withTrashed()
            ->where('user_id', $user->id)
            ->where('book_id', $book->id)
            ->first();

        if ($completion) {
            if ($completion->trashed()) {
                //  uncomplete  →  complete
                $completion->restore();
                $completion->update([
                    'completed_at' => now(),
                ]);
                $action      = 'completed_again';
                $isCompleted = true;
                $message     = 'You have marked this book as read again';
            } else {
                                       //  complete  →  uncomplete
                $completion->delete(); // soft delete
                $action      = 'uncompleted';
                $isCompleted = false;
                $message     = 'Read completion mark has been removed';
            }
        } else {
            //  complete
            UserBookCompletion::create([
                'user_id'      => $user->id,
                'book_id'      => $book->id,
                'completed_at' => now(),

            ]);
            $action      = 'completed';
            $isCompleted = true;
            $message     = 'Congratulations! You have marked this book as read.';
        }

        $message = match (true) {
            $action === 'completed'       => 'Congratulations! You have marked this book as read.',
            $action === 'completed_again' => 'You have marked this book as read again.',
            $action === 'uncompleted'     => 'Read completion mark has been removed.',
            default                       => 'Operation completed successfully.'
        };

        return $this->success([
            'book_id'      => $book->id,
            'is_completed' => $isCompleted,
            'action'       => $action,
            'completed_at' => $isCompleted ? now()->toDateTimeString() : null,
        ], $message);
    }

}
