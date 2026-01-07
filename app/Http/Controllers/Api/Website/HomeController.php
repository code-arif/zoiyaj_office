<?php
namespace App\Http\Controllers\Api\Website;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\Category;
use App\Models\Order;
use App\Models\Plan;
use App\Models\Specialty;
use App\Models\Wishlist;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HomeController extends Controller
{
    use ApiResponse;

    private function mapBookData($books)
    {
        return $books->map(function ($book) {

            if (auth('api')->user()) {

                $user = auth('api')->user();
            }

            return [
                'id'                  => $book->id,
                'title'               => $book->title,
                'price'               => $book->price,
                'shopping_cost'       => $book->shopping_cost,
                'stock'               => $book->stock,
                'slug'                => $book->slug,
                'author_name'         => $book->author,
                'isbn'                => $book->isbn,
                'type'                => $book->type,
                'description'         => $book->description,
                'published_at'        => $book->published_at,
                'cover_image'         => $book->cover_image ? url($book->cover_image) : null,
                'pdf_file'            => $book->pdf_file ? url($book->pdf_file) : null,
                'is_premium'          => $book->is_premium,
                'total_sales'         => $book->orders->count(),
                'is_already_purchase' => auth('api')->user() ? Order::where('buyer_id', $user->id)->where('book_id', $book->id)->exists() : false,
                'is_bookmarked'       => auth('api')->user() ? auth('api')->user()->wishlistedBooks()->where('book_id', $book->id)->exists() : false,
                'rating'              => $book->book_reviews->average('rating') ?? 0,
                'no_of_reviews'       => $book->book_reviews->count() ?? 0,

                // Categories mapping
                'categories'          => $book->book_categories->map(function ($cat) {
                    return [
                        'id'    => $cat->category->id ?? null,
                        'title' => $cat->category->title ?? null,
                    ];
                })->values(),

                // Images mapping
                'images'              => $book->book_images->map(function ($img) {
                    return [
                        'id'  => $img->id,
                        'url' => $img->image_url ? url($img->image_url) : null,
                    ];
                }),
            ];
        });
    }

    public function book_list(Request $request)
    {
        $perPage      = $request->get('per_page', 10);
        $current_page = $request->get('current_page', 1);

        // Force Laravel paginator to use custom "current_page"
        $request->merge(['page' => $current_page]);

        // Start query
        // Start query without latest()
        $books = Book::with(['book_images', 'book_categories.category']);

        // Apply order dynamically
        if ($request->filled('order_by') && in_array(strtolower($request->order_by), ['asc', 'desc'])) {
            $books->orderBy('created_at', $request->order_by);
        } else {
            // Default ordering
            $books->latest();
        }
        if ($request->has('category_ids')) {
            $books->whereHas('book_categories', function ($q) use ($request) {
                $q->whereIn('category_id', $request->category_ids);
            });
        }

        // // order by asc or desc
        // if ($request->filled('order_by')) {
        //     $orderBy = $request->order_by;
        //     $books->orderBy('created_at', $orderBy);
        // }

        if ($request->filled('type')) {

            if ($request->type === 'premium') {
                $books->where('type', 'ebook')->where('is_premium', true);
            } else if ($request->type == "ebook") {
                $books->where('type', 'ebook')->where('is_premium', false);
            } else {
                $books->where('type', $request->type);
            }
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $books->where(function ($q) use ($search) {
                $q->where('title', 'LIKE', "%{$search}%")
                    ->orWhere('author', 'LIKE', "%{$search}%")
                    ->orWhere('isbn', 'LIKE', "%{$search}%");
            });
        }

        // Paginate
        $paginatedBooks = $books->paginate($perPage);

        $mappedBooks = $this->mapBookData($paginatedBooks->getCollection());

        $paginatedBooks->setCollection($mappedBooks);

        return response()->json([
            'success' => true,
            'message' => 'Books retrieved successfully',
            'data'    => [
                'books'      => $paginatedBooks->items(),
                'pagination' => [
                    'current_page' => $paginatedBooks->currentPage(),
                    'last_page'    => $paginatedBooks->lastPage(),
                    'per_page'     => $paginatedBooks->perPage(),
                    'total'        => $paginatedBooks->total(),
                ],
            ],
            'code'    => 200,
        ]);
    }

    public function book_details(Request $request, $slug)
    {
        $data = Book::with(['book_images', 'book_categories.category'])->where('slug', $slug)->first();

        $data['total_rating_avg']  = $data->book_reviews->average('rating') ?? 0;
        $data['no_of_reviews']     = $data->book_reviews->count() ?? 0;
        $data['is_bookmarked']     = auth('api')->user() ? auth('api')->user()->wishlistedBooks()->where('book_id', $data->id)->exists() : false;
        $data['is_read_completed'] = auth('api')->user() ? auth('api')->user()->book_completions()->where('book_id', $data->id)->exists() : false;

        return $this->success($data, 'Book Details  retrive successfully');
    }

    public function category_list(Request $request)
    {
        $userId = auth('api')->id();

        $categories = Category::select('id', 'title', 'slug', 'image')
            ->get()
            ->map(function ($category) use ($userId) {
                $category->is_selected = DB::table('user_categories')
                    ->where('user_id', $userId)
                    ->where('category_id', $category->id)
                    ->exists();
                return $category;
            });

        $data = [
            'categories' => $categories,
        ];

        return $this->success($data, 'Category list retrive successfully');
    }


    public function specialty_list(Request $request)
    {
        $specialty = Specialty::all();

        if ($specialty->isEmpty()) {
            return $this->error([], 'No Specialty found');
        }

        return $this->success($specialty, 'Specialty list retrive successfully');
    }

    public function plan_list(Request $request)
    {
        $plans = Plan::all();

        if ($plans->isEmpty()) {
            return $this->error([], 'No Plan found');
        }

        return $this->success($plans, 'Plan list retrive successfully');
    }

    // book review list
    public function book_review_list(Request $request)
    {
        $bookReviews = DB::table('book_reviews')
            ->join('users', 'book_reviews.user_id', '=', 'users.id')
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

        return $this->success($bookReviews, 'Book reviews retrieved successfully');

    }

    // recommended book list
    public function recommended_book_list(Request $request)
    {
        $user = auth('api')->user();

        // If user is NOT logged in → return general latest books
        if (! $user) {
            $books = Book::with(['book_images', 'book_categories.category'])
                ->latest()
                ->take(8)
                ->get();

            return $this->success($this->mapBookData($books), 'Recommended Books retrieved successfully');
        }

        // If user is logged in → return recommended based on categories
        $user_category_ids = $user->user_categories()->pluck('category_id')->toArray();

        $books = Book::with(['book_images', 'book_categories.category'])
            ->whereHas('book_categories', function ($q) use ($user_category_ids) {
                $q->whereIn('category_id', $user_category_ids);
            })
            ->take(8)
            ->get();

        return $this->success($this->mapBookData($books), 'Recommended Books retrieved successfully');
    }

    // top reviewed book list
    public function top_review_book_list(Request $request)
    {
        // Fetch books with average rating and review count
        $books = Book::with(['book_images', 'book_categories.category'])
            ->withAvg('book_reviews', 'rating')
            ->withCount('book_reviews')
            ->having('book_reviews_count', '>', 0)
            ->orderByDesc('book_reviews_avg_rating')
            ->take(10)
            ->get();

        $topReviewedBooks = $this->mapBookData($books);

        return $this->success($topReviewedBooks, 'Top Reviewed Books retrieved successfully');
    }

    // top selling book list
    public function top_selling_book_list(Request $request)
    {

        // Fetch top selling books based on order count
        $books = Book::with(['book_images', 'book_categories.category'])
            ->whereHas('orders')
            ->withCount('orders')
            ->orderByDesc('orders_count')
            ->take(10)
            ->get();

        $topSellingBooks = $this->mapBookData($books);

        return $this->success($topSellingBooks, 'Top Selling Books retrieved successfully');

    }

    // related book list
    public function related_book_list(Request $request, $category_ids)
    {
        $category_id = explode(',', $category_ids) ?? null;

        $books = Book::with(['book_images', 'book_categories.category'])
            ->whereHas('book_categories', function ($q) use ($category_id) {
                $q->whereIn('category_id', $category_id);
            })
            ->take(10)
            ->get();

        $relatedBooks = $this->mapBookData($books);

        return $this->success($relatedBooks, 'Related Books retrieved successfully');

    }

    // like book list
    public function like_book_list(Request $request)
    {
        $perPage     = $request->get('per_page', 10);
        $currentPage = $request->get('current_page', 1);

        // Force Laravel paginator to use custom "current_page"
        $request->merge(['page' => $currentPage]);

        $paginatedBooks = Book::with(['book_images', 'book_categories.category'])
            ->inRandomOrder()
            ->paginate($perPage);

        $mappedBooks = $this->mapBookData($paginatedBooks->getCollection());
        $paginatedBooks->setCollection($mappedBooks);

        return response()->json([
            'success' => true,
            'message' => 'You may like Books retrieved successfully',
            'data'    => [
                'books'      => $paginatedBooks->items(),
                'pagination' => [
                    'current_page' => $paginatedBooks->currentPage(),
                    'last_page'    => $paginatedBooks->lastPage(),
                    'per_page'     => $paginatedBooks->perPage(),
                    'total'        => $paginatedBooks->total(),
                ],
            ],
            'code'    => 200,
        ]);
    }

    // similar book list
    public function similar_book_list(Request $request)
    {
        $user        = auth('api')->user();
        $perPage     = $request->get('per_page', 10);
        $currentPage = $request->get('current_page', 1);

        // Force Laravel paginator to use custom "current_page"
        $request->merge(['page' => $currentPage]);

        //  wishlist book IDs
        $wishlistBookIds = Wishlist::where('user_id', $user->id)
            ->pluck('book_id')
            ->toArray();

        //  once (avoid N+1)
        $wishlistBooks = Book::whereIn('id', $wishlistBookIds)->get();

        $paginatedBooks = Book::with(['book_images', 'book_categories.category'])
            ->whereNotIn('id', $wishlistBookIds)
            ->where(function ($query) use ($wishlistBooks) {
                foreach ($wishlistBooks as $book) {
                    $query->orWhere('title', 'LIKE', '%' . $book->title . '%')
                        ->orWhere('author', 'LIKE', '%' . $book->author . '%')
                        ->orWhere('description', 'LIKE', '%' . $book->description . '%');
                }
            })
            ->paginate($perPage);

        $mappedBooks = $this->mapBookData($paginatedBooks->getCollection());
        $paginatedBooks->setCollection($mappedBooks);

        return response()->json([
            'success' => true,
            'message' => 'Similar Books retrieved successfully',
            'data'    => [
                'books'      => $paginatedBooks->items(),
                'pagination' => [
                    'current_page' => $paginatedBooks->currentPage(),
                    'last_page'    => $paginatedBooks->lastPage(),
                    'per_page'     => $paginatedBooks->perPage(),
                    'total'        => $paginatedBooks->total(),
                ],
            ],
            'code'    => 200,
        ]);
    }

}
