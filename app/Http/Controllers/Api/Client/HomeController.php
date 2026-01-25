<?php
namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

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
                'id' => $professional->id,
                'professional_name' => $professional->professional_name,
                'location' => $professional->address . ', ' . $professional->city . ', ' . $professional->state . ', ' . $professional->country,
                'avatar' => $professional->avatar,
                'total_ratings' => "5.0",
                'working_hours' => $professional->working_hours()->first(),
                'services' => $professional->services()->first() ,
            ];
        });








        return $this->success($data, 'Professionals fetched successfully.', 200);

    }

}
