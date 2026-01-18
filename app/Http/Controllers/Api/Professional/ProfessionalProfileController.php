<?php
namespace App\Http\Controllers\Api\Professional;

use App\Helper\Helper;
use App\Http\Controllers\Controller;
use App\Models\ProfessionalBrand;
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
            'latitude'           => 'required',
            'longitude'          => 'required',
            'city'               => 'required',
            'state'              => 'required',
            'postal_code'        => 'required',
            'country'            => 'required',
            'bio'                => 'required',

        ]);

        if ($validator->fails()) {
            return $this->error([], $validator->errors()->first(), 200);
        }

        $prof_info = auth('api')->user();

        $prof_info->update([
            'professional_name'  => $request->professional_name,
            'professional_phone' => $request->professional_phone,
            'professional_email' => $request->professional_email,
            'address'            => $request->address,
            'latitude'           => $request->latitude,
            'longitude'          => $request->longitude,
            'city'               => $request->city,
            'state'              => $request->state,
            'postal_code'        => $request->postal_code,
            'country'            => $request->country,
            'bio'                => $request->bio,
        ]);

        // Only return the updated business profile fields
        $prof_info = [
            'id'                 => $prof_info->id,
            'role'               => $prof_info->role,
            'professional_name'  => $prof_info->professional_name,
            'professional_phone' => $prof_info->professional_phone,
            'professional_email' => $prof_info->professional_email,
            'address'            => $prof_info->address,
            'latitude'           => $prof_info->latitude,
            'longitude'          => $prof_info->longitude,
            'city'               => $prof_info->city,
            'state'              => $prof_info->state,
            'postal_code'        => $prof_info->postal_code,
            'country'            => $prof_info->country,
            'bio'                => $prof_info->bio,
        ];

        return $this->success($prof_info, 'Professional profile updated successfully', 200);

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

    /**
     * Update Weekly Working Hours
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */

    public function working_hours(Request $request)
    {

        // dd($request->all());

        $validator = Validator::make($request->all(), [
            'working_hours'              => 'required|array|size:7',
            'working_hours.*.day'        => 'required|in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
            'working_hours.*.is_closed'  => 'required|boolean',
            'working_hours.*.open_time'  => 'nullable|required_if:working_hours.*.is_closed,false|date_format:H:i',
            'working_hours.*.close_time' => 'nullable|required_if:working_hours.*.is_closed,false|date_format:H:i|after:working_hours.*.open_time',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors(), 'Validation failed', 422);
        }

        $user = auth('api')->user();

        // $user->working_hours()->delete();
        // foreach ($request->input('working_hours') as $hour) {
        //     $user->working_hours()->create([
        //         'day'        => $hour['day'],
        //         'is_closed'  => $hour['is_closed'],
        //         'open_time'  => $hour['is_closed'] ? null : $hour['open_time'],
        //         'close_time' => $hour['is_closed'] ? null : $hour['close_time'],
        //     ]);
        // }

        //  sync/updateOrCreate

        foreach ($request->input('working_hours') as $hour) {
            $user->working_hours()->updateOrCreate(
                ['day' => $hour['day']],
                [
                    'is_closed'  => $hour['is_closed'],
                    'open_time'  => $hour['is_closed'] ? null : $hour['open_time'],
                    'close_time' => $hour['is_closed'] ? null : $hour['close_time'],
                ]
            );
        }

        $user->load('working_hours');

        return $this->success(
            $user->working_hours,
            'Working hours updated successfully',
            200
        );
    }

    public function setup_brand(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'brand_id'   => 'required|array',
            'brand_id.*' => 'exists:brands,id',

        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors(), 'Validation failed', 422);
        }

        $user = auth('api')->user();

        // Sync brands (delete old and insert new)
        $user->user_brands()->delete();
        if (! empty($request->brand_id)) {
            $brands = collect($request->brand_id)->map(function ($id) use ($user) {
                return [
                    'user_id'    => $user->id,
                    'brand_id'   => $id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            })->toArray();

            ProfessionalBrand::insert($brands);
        }

        // Load updated brands
        $user->load('user_brands');

        return $this->success($user, 'Professional brands updated successfully', 200);
    }

    public function services(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'logo'                      => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'certificate'               => 'nullable|file|mimes:pdf,jpeg,jpg,png|max:5120',

            'services'                  => 'required|array|min:1',
            'services.*.name'           => 'required|string|max:100',
            'services.*.starting_price' => 'required|numeric|min:0',
            'services.*.duration'       => 'nullable|string|max:50',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors(), 'Validation failed', 422);
        }

        $user = auth('api')->user();

        $logo        = null;
        $certificate = null;

        if ($request->hasFile('logo')) {
            if ($user->logo) {
                Helper::deleteImage($user->logo);
            }
            $logo = Helper::uploadImage($request->file('logo'), 'profile');

        }

        $user->logo_path = $logo;

        if ($request->hasFile('certificate')) {
            if ($user->certificate) {
                Helper::deleteImage($user->certificate);
            }
            $certificate = Helper::uploadImage($request->file('certificate'), 'profile');

        }

        $user->certificate_path = $certificate;

        $user->save();

        $user->services()->delete();

        foreach ($request->input('services', []) as $serviceData) {
            $user->services()->create([
                'name'           => $serviceData['name'],
                'starting_price' => $serviceData['starting_price'],
                'duration'       => $serviceData['duration'] ?? null,
            ]);
        }

        $user = [
            'id'          => $user->id,
            'role'        => $user->role,
            'logo'        => $user->logo_path,
            'certificate' => $user->certificate_path,
            'services'    => $user->services,

        ];

        return $this->success($user, 'Profile & services added successfully', 200);
    }

    public function about_me(Request $request)
    {
        $user = auth('api')->user();

        if (! $user) {
            return $this->error([], 'User not found.', 404);
        }

        $data = [

            'id'                 => $user->id,
            'avatar'             => $user->avatar ?? null,
            'first_name'         => $user->first_name ?? null,
            'last_name'          => $user->last_name ?? null,
            'professional_name'  => $user->professional_name ?? null,
            'professional_phone' => $user->professional_phone ?? null,
            'professional_email' => $user->professional_email ?? null,
            'address'            => $user->address ?? null,
            'city'               => $user->city ?? null,
            'state'              => $user->state ?? null,
            'postal_code'        => $user->postal_code ?? null,
            'country'            => $user->country ?? null,
            'bio'                => $user->bio ?? null,
            'total_ratings'      => "0'0",
            'total_reviews'      => "0'0",
            'total_followers'    => "0'0",

            'working_hours'      => $user->working_hours,
            'accessibilties'     => json_decode($user->accessibilties),
            'services'           => $user->services,
            'brands' => $user->user_brands->load('brand')

        ];

        return $this->success($data, 'Professional profile information retrive  successfully', 200);
    }

}
