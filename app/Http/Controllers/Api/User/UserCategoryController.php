<?php

namespace App\Http\Controllers\Api\User;

use Exception;
use App\Traits\ApiResponse;
use App\Models\UserCategory;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class UserCategoryController extends Controller
{
    use ApiResponse;

    public function store(Request $request)
    {
        $request->validate([
            'category_ids'   => 'nullable|array',
            'category_ids.*' => 'exists:categories,id',
        ]);

        try {
            $user = auth('api')->user();

            // Remove old categories
            UserCategory::where('user_id', $user->id)->delete();

            // Insert new ones

            $user_categories = [];
            if (!empty($request->category_ids)) {
                foreach ($request->category_ids as $catId) {
                    $user_categories[] =   UserCategory::create([
                        'user_id'     => $user->id,
                        'category_id' => $catId,
                    ]);
                }
            }

            return $this->success($user_categories, 'Categories updated successfully.');

        } catch (Exception $e) {
            return $this->error('Something went wrong, please try again later.', 500, [
                'error' => $e->getMessage()
            ]);
        }
    }
}
