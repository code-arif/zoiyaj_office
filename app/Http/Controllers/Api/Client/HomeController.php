<?php
namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use App\Models\Bookmark;
use App\Models\Category;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Nette\Utils\Random;

class HomeController extends Controller
{
    use ApiResponse;

    public function salon_category_list(Request $request, $category_id)
    {

        $category = Category::find($category_id);

        $user = auth('api')->user();

        if (! $category) {
            return $this->error(null, 'Category not found.', 404);
        }

        $professionals = User::where('role', 'professional')
            ->whereHas('professionalServices', function ($query) use ($category_id) {
                $query->where('category_id', $category_id);
            })
            ->with(['professionalServices.category'])
            ->get();

        $data = $professionals->map(function ($professional) use ($user) {

            $isBookmarked = false;

            if ($user) {
                $isBookmarked = Bookmark::where('client_id', $user->id)
                    ->where('professional_id', $professional->id)
                    ->exists();
            }

            return [
                'id'                => $professional->id,
                'professional_name' => $professional->professional_name,
                'location'          => trim(
                    $professional->address . ', ' .
                    $professional->city . ', ' .
                    $professional->state . ', ' .
                    $professional->country
                ),
                'thumb'             => $professional->thumb,
                'is_bookmark'       => $isBookmarked,
                'total_ratings'     => "5.0",
                'working_hours'     => $professional->working_hours()->first(),
                'services'          => $professional->services()->first(),
            ];
        });

        return $this->success($data, 'Professionals fetched successfully.', 200);

    }

    // popular categories
    public function popular_categories(Request $request)
    {
        $categories = Category::withCount([
            // Distinct users who created services
            'professionalServices as user_count' => function ($query) {
                $query->select(DB::raw('COUNT(DISTINCT user_id)'));
            },

        ])
            ->whereHas('serviceBookings')

            ->take(10)
            ->get();

        $data = $categories->map(function ($category) {
            return [
                'id'             => $category->id,
                'title'          => $category->title,
                'image'          => $category->image,
                'user_count'     => $category->user_count,
                'total_services' => $category->professionalServices()->count(),
            ];
        });

        return $this->success($data, 'Popular categories fetched successfully.', 200);
    }

    public function nearby_salon_list(Request $request)
    {

        $professionals = User::where('role', 'professional')
            ->with(['user_categories.category'])
            ->get();

        $data = $professionals->map(function ($professional) {
            return [
                'id'                => $professional->id,
                'professional_name' => $professional->professional_name,
                'location'          => $professional->address . ', ' . $professional->city . ', ' . $professional->state . ', ' . $professional->country,
                'thumb'             => $professional->thumb,
                'total_ratings'     => $professional->professionalReviews->avg('rating') ?? 0,
                'total_reviews'     => $professional->professionalReviews->count() ?? 0,
                'working_hours'     => $professional->working_hours()->first(),
                'services'          => $professional->services()->first(),
            ];
        });

        return $this->success($data, 'Nearby Professionals salon fetched successfully.', 200);

    }

    // public function top_stylist_salon_list(Request $request)
    // {

    //     $professionals = User::where('role', 'professional')
    //         ->with(['user_categories.category'])
    //         ->orderBy('created_at', 'desc')
    //         ->get();

    //     $data = $professionals->map(function ($professional) {
    //         return [
    //             'id'                => $professional->id,
    //             'professional_name' => $professional->professional_name,
    //             'location'          => $professional->address . ', ' . $professional->city . ', ' . $professional->state . ', ' . $professional->country,
    //             'thumb'             => $professional->thumb,
    //             'total_ratings'     => "5.0",
    //             'category_type'     => $professional->user_categories()->first() ? $professional->user_categories()->first()->category->title : "All Stylist",
    //             'total_reviews'     => Random::generate(2, '0-9'),
    //             'working_hours'     => $professional->working_hours()->first(),
    //             'services'          => $professional->services()->first(),
    //         ];
    //     });

    //     return $this->success($data, 'Top Stylist salon fetched successfully.', 200);

    // }

    public function top_stylist_salon_list(Request $request)
    {
        $professionals = User::where('role', 'professional')
            ->with(['user_categories.category'])
            ->withAvg('ProfessionalReviews as avg_rating', 'rating')
            ->withCount('ProfessionalReviews as total_reviews')
            ->having('total_reviews', '>', 0)
            ->orderByDesc('avg_rating')
            ->limit(10)
            ->get();

        if ($professionals->isEmpty()) {
            return $this->error(null, 'No top stylists found.', 404);
        }

        $data = $professionals->map(function ($professional) {
            return [
                'id'                => $professional->id,
                'professional_name' => $professional->professional_name,
                'location'          => $professional->address . ', ' . $professional->city . ', ' . $professional->state . ', ' . $professional->country,
                'avatar'            => $professional->avatar,
                'total_ratings'     => round($professional->avg_rating, 1),
                'total_reviews'     => $professional->total_reviews,
                'working_hours'     => $professional->working_hours()->first(),
                'services'          => $professional->services()->first(),
            ];
        });

        return $this->success($data, 'Top Stylist salon fetched successfully.', 200);
    }

    public function salon_detail(Request $request, $professional_id)
    {
        $professional = User::where('role', 'professional')
            ->where('id', $professional_id)
            ->with(['user_categories.category'])
            ->first();

        $user = auth('api')->user();

        if (! $professional) {
            return $this->error(null, 'Professional not found.', 404);
        }

        $isBookmarked = false;

        if ($user) {
            $isBookmarked = Bookmark::where('client_id', $user->id)
                ->where('professional_id', $professional->id)
                ->exists();
        }

        $data = [
            'id'                => $professional->id,
            'first_name'        => $professional->first_name,
            'last_name'         => $professional->last_name,
            'avatar'            => $professional->avatar,

            'professional_name' => $professional->professional_name,
            'bio'               => $professional->bio,
            'location'          => $professional->address . ', ' . $professional->city . ', ' . $professional->state . ', ' . $professional->country,
            'thumb'             => $professional->thumb,
            'total_ratings'     => round($professional->avg_rating, 1),
            'total_reviews'     => Random::generate(2, '0-9'),
            'total_followers'   => Random::generate(3, '0-9'),
            'portfolio'         => $professional->portfolios,
            'is_bookmark'       => $isBookmarked,

            'categories'        => $professional->user_categories->map(function ($user_category) {
                return [
                    'id'    => $user_category->category->id,
                    'title' => $user_category->category->title,
                ];
            }),
            'working_hours'     => $professional->working_hours,
            'services'          => $professional->services,

        ];

        return $this->success($data, 'Professional details fetched successfully.', 200);
    }

}
