<?php
namespace App\Http\Controllers\Api\Website;

use App\Models\User;
use App\Helper\Helper;
use App\Models\Company;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserManageController extends Controller
{
    use ApiResponse;

    private function getAuthenticatedCompany()
    {
        $user = auth('api')->user();
        return Company::where('user_id', $user->id)->first();
    }

    public function user_info(Request $request)
    {
        $user = User::find(auth('api')->id());

        if (! $user) {
            return $this->error([], 'User not found.', 404);
        }

        $subscription = $user->subscription('default');

        $plan         = null;

        if ($subscription) {
            $plan = \App\Models\Plan::where('stripe_price_id', $subscription->stripe_price)->with('features')->first();
        }

        $data = [
            'id'              => $user->id,
            'first_name'      => $user->first_name ?? null,
            'last_name'       => $user->last_name ?? null,
            'email'           => $user->email,
            'role'            => $user->role,
            'house'           => $user->house ?? null,
            'road'            => $user->road ?? null,
            'city'            => $user->city ?? null,
            'avatar'          => $user->avatar ? url($user->avatar) : null,
            'cover'           => $user->cover ? url($user->cover) : null,

            'total_books'     => $user->books()->count() ?? 0,
            'total_delivered' => $user->total_completed_deliveries() ?? 0,
            'total_ratings'   => $user->total_book_reviews()->count() ?? 0,
            'total_earned'    => $user->total_earned_amount() ?? 0.00,


            'is_subscribed'             => $user->subscribed('default'),
            'is_cancelled'              => $subscription ? $subscription->canceled() : false,
            'is_subscription_active'    => $subscription?->active() ?? false,
            // 'is_on_trial'               => $subscription?->onTrial() ?? false,
            // 'trial_ends_at'             => $subscription?->trial_ends_at?->format('Y-m-d H:i:s'),
            'subscription_ends_at'      => $subscription?->ends_at?->format('Y-m-d H:i:s'),
            'subscription_status'       => $subscription?->stripe_status,

            'subscription_plan_id'      => $plan?->id,
            'subscription_plan'         => $plan->name ?? null,
            'subscription_price'        => $plan->price ?? null,
            'plan_slug'                 => $plan->slug ?? null,
        ];

        return $this->success($data, 'User Information retrieved successfully.');

    }

    public function user_info_update(Request $request)
    {
        $user = User::find(auth('api')->id());

        if (! $user) {
            return $this->error([], 'User not found.', 404);
        }

        $user->first_name = $request->first_name;
        $user->last_name  = $request->last_name;
        $user->house      = $request->house;
        $user->road       = $request->road;
        $user->city       = $request->city;
        $user->save();

        $data = [
            'id'         => $user->id,
            'first_name' => $user->first_name ?? null,
            'last_name'  => $user->last_name ?? null,
            'email'      => $user->email,
            'role'       => $user->role,
            'house'      => $user->house ?? null,
            'road'       => $user->road ?? null,
            'city'       => $user->city ?? null,
        ];

        return $this->success($data, 'User Information updated successfully.');

    }

    public function user_avatar_update(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'avatar' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif', 'max:5120'],
                'cover'  => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif', 'max:5120'],
            ]);

            if ($validator->fails()) {
                return $this->error($validator->errors(), 'Validation Failed', 422);
            }

            $user = auth('api')->user();

            if (! $user) {
                return $this->error([], 'User not authenticated.', 401);
            }

            // ---- Handle Avatar Upload ----
            if ($request->hasFile('avatar')) {

                if ($user->avatar) {
                    Helper::deleteImage($user->avatar);
                }

                $user->avatar = Helper::uploadImage($request->file('avatar'), 'profile');
            }

            // ---- Handle Cover Upload ----
            if ($request->hasFile('cover')) {

                if ($user->cover) {
                    Helper::deleteImage($user->cover);
                }

                $user->cover = Helper::uploadImage($request->file('cover'), 'profile');
            }

            $user->save();

            $userData = [
                'id'         => $user->id,
                'first_name' => $user->first_name,
                'last_name'  => $user->last_name,
                'email'      => $user->email,
                'avatar'     => $user->avatar ? url($user->avatar) : null,
                'cover'      => $user->cover ? url($user->cover) : null,
            ];

            return $this->success($userData, 'Profile updated successfully.', 200);

        } catch (\Exception $e) {

            Log::error('Profile update failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->error([], 'An unexpected error occurred. Please try again.', 500);
        }
    }



    public function reset_password(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'current_password'      => 'required|string',
            'new_password'          => 'required|string|min:6|different:current_password',
            'confirm_new_password'  => 'required|string|same:new_password',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors(), 'Validation Failed', 422);
        }

        $user = auth('api')->user();

        if (! $user) {
            return $this->error([], 'User not authenticated.', 401);
        }

        if (! Hash::check($request->current_password, $user->password)) {
            return $this->error([], 'Current password is incorrect.', 400);
        }

        $user->password = Hash::make($request->new_password);
        $user->save();

        return $this->success([], 'Password reset successfully.', 200);
    }







}
