<?php
namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Nette\Utils\Random;

class HomeController extends Controller
{
    use ApiResponse;

    public function salon_category_list(Request $request, $category_id)
    {

        $category = Category::find($category_id);

        if (! $category) {
            return $this->error(null, 'Category not found.', 404);
        }

        $professionals = User::where('role', 'professional')
            ->whereHas('user_categories', function ($query) use ($category_id) {
                $query->where('category_id', $category_id);
            })
            ->with(['user_categories.category'])
            ->get();

        $data = $professionals->map(function ($professional) {
            return [
                'id'                => $professional->id,
                'professional_name' => $professional->professional_name,
                'location'          => $professional->address . ', ' . $professional->city . ', ' . $professional->state . ', ' . $professional->country,
                'thumb'             => $professional->thumb,
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
        $categories = Category::orderBy('created_at', 'desc')->take(10)->get();

        return $this->success($categories, 'Popular categories fetched successfully.', 200);
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
                'total_ratings'     => "5.0",
                'working_hours'     => $professional->working_hours()->first(),
                'services'          => $professional->services()->first(),
            ];
        });

        return $this->success($data, 'Nearby Professionals salon fetched successfully.', 200);

    }

    public function top_stylist_salon_list(Request $request)
    {

        $professionals = User::where('role', 'professional')
            ->with(['user_categories.category'])
            ->orderBy('created_at', 'desc')
            ->get();



        $data = $professionals->map(function ($professional) {
            return [
                'id'                => $professional->id,
                'professional_name' => $professional->professional_name,
                'location'          => $professional->address . ', ' . $professional->city . ', ' . $professional->state . ', ' . $professional->country,
                'thumb'             => $professional->thumb,
                'total_ratings'     => "5.0",
                'category_type'         => $professional->user_categories()->first() ? $professional->user_categories()->first()->category->title : "All Stylist",
                'total_reviews'     => Random::generate(2, '0-9'),
                'working_hours'     => $professional->working_hours()->first(),
                'services'          => $professional->services()->first(),
            ];
        });



        return $this->success($data, 'Top Stylist salon fetched successfully.', 200);

    }

    // public function top_stylist_salon_list(Request $request)
    // {
    //     $professionals = User::where('role', 'professional')
    //         ->with(['user_categories.category'])
    //         ->withAvg('reviews as avg_rating', 'rating')
    //         ->withCount('reviews as total_reviews')
    //         ->having('total_reviews', '>', 0) // যাদের review আছে শুধু তারা
    //         ->orderByDesc('avg_rating')       // highest rating first
    //         ->limit(10)                       // top 10
    //         ->get();

    //     $data = $professionals->map(function ($professional) {
    //         return [
    //             'id'                => $professional->id,
    //             'professional_name' => $professional->professional_name,
    //             'location'          => $professional->address . ', ' . $professional->city . ', ' . $professional->state . ', ' . $professional->country,
    //             'avatar'            => $professional->avatar,
    //             'total_ratings'     => round($professional->avg_rating, 1),
    //             'total_reviews'     => $professional->total_reviews,
    //             'working_hours'     => $professional->working_hours()->first(),
    //             'services'          => $professional->services()->first(),
    //         ];
    //     });

    //     return $this->success($data, 'Top Stylist salon fetched successfully.', 200);
    // }




    public function salon_detail(Request $request, $professional_id)
    {
        $professional = User::where('role', 'professional')
            ->where('id', $professional_id)
            ->with(['user_categories.category'])
            ->first();

        if (! $professional) {
            return $this->error(null, 'Professional not found.', 404);
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
            'portfolio'  => $professional->portfolios,

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
