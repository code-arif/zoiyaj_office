<?php
namespace App\Http\Controllers\Api\Professional;

use App\Http\Controllers\Controller;
use App\Models\ProfessionalSpecialty;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ProfessionalProfileController extends Controller
{

    use ApiResponse;

    public function setup_basic(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'professional_name'  => 'required',
            'professional_phone' => 'required',
            'professional_email' => 'required',
            'address'            => 'required',
            'city'               => 'required',
            'state'              => 'required',
            'postal_code'        => 'required',
            'country'            => 'required',

        ]);

        if ($validator->fails()) {
            return $this->error([], $validator->errors()->first(), 200);
        }

        $user_prof_information = auth('api')->user();

        $user_prof_information->update([
            'professional_name'  => $request->professional_name,
            'professional_phone' => $request->professional_phone,
            'professional_email' => $request->professional_email,
            'address'            => $request->address,
            'city'               => $request->city,
            'state'              => $request->state,
            'postal_code'        => $request->postal_code,
            'country'            => $request->country,
        ]);

        // Only return the updated business profile fields
        $user_prof_information = [
            'id'                 => $user_prof_information->id,
            'role'               => $user_prof_information->role,
            'professional_name'  => $user_prof_information->professional_name,
            'professional_phone' => $user_prof_information->professional_phone,
            'professional_email' => $user_prof_information->professional_email,
            'address'            => $user_prof_information->address,
            'city'               => $user_prof_information->city,
            'state'              => $user_prof_information->state,
            'postal_code'        => $user_prof_information->postal_code,
            'country'            => $user_prof_information->country,
        ];

        return $this->success($user_prof_information, 'Professional profile updated successfully', 200);

    }

    public function preferences_info(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'specialty_id'            => 'required|array',
            'specialty_id.*'          => 'exists:specialties,id',
            'years_in_business'       => 'required|integer',
            'is_promo_participation'  => 'boolean',
            'accessibilties'          => 'required|array',
            'accessibilties.*'        => 'string|in:wheelchair,hijab_friendly',
            'is_sell_retail_products' => 'boolean',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors(), 'Validation failed', 422);
        }

        $user = auth('api')->user();

        // Prepare data for update
        $updateData = [
            'years_in_business'       => $request->input('years_in_business', 0),
            'is_promo_participation'  => $request->input('is_promo_participation', false),
            'is_sell_retail_products' => $request->input('is_sell_retail_products', false),
        ];

        // Handle accessibilties JSON
        if ($request->has('accessibilties')) {
            $updateData['accessibilties'] = json_encode($request->input('accessibilties'));
        }

        // Update user profile
        $user->update($updateData);

        // Sync specialties (delete old and insert new)
        $user->user_specialty()->delete();
        if (! empty($request->specialty_id)) {
            $specialties = collect($request->specialty_id)->map(function ($id) use ($user) {
                return [
                    'user_id'      => $user->id,
                    'specialty_id' => $id,
                    'created_at'   => now(),
                    'updated_at'   => now(),
                ];
            })->toArray();

            ProfessionalSpecialty::insert($specialties);
        }

        // Load updated specialties
        $user->load('user_specialty');

        return $this->success($user, 'Professional preferences updated successfully', 200);
    }

}
