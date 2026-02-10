<?php
namespace App\Http\Controllers\Api\Professional;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Category;
use App\Models\ProfessionalBrand;
use App\Models\ProfessionalSpecialty;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
            'latitude'           => 'nullable',
            'longitude'          => 'nullable',
            'city'               => 'required',
            'state'              => 'required',
            'postal_code'        => 'required',
            'country'            => 'required',
            'bio'                => 'required',
            'thumb'              => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',

        ]);

        if ($validator->fails()) {
            return $this->error([], $validator->errors()->first(), 422);
        }

        $prof_info = auth('api')->user();

        if ($request->hasFile('thumb')) {
            if ($prof_info->thumb) {
                Helper::deleteImage($prof_info->thumb);
            }
            $thumb = Helper::uploadImage($request->file('thumb'), 'profile');

            $prof_info->thumb = $thumb;
            $prof_info->save();
        }

        $prof_info->update([
            'professional_name'  => $request->professional_name ?? null,
            'professional_phone' => $request->professional_phone ?? null,
            'professional_email' => $request->professional_email ?? null,
            'address'            => $request->address ?? null,
            'latitude'           => $request->latitude ?? null,
            'longitude'          => $request->longitude ?? null,
            'city'               => $request->city ?? null,
            'state'              => $request->state ?? null,
            'postal_code'        => $request->postal_code ?? null,
            'country'            => $request->country ?? null,
            'bio'                => $request->bio ?? null,

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
            'thumb'              => $prof_info->thumb,
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
            'working_hours.*.open_time'  => 'nullable|string',
            'working_hours.*.close_time' => 'nullable|string',
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

    // public function setup_category(Request $request)
    // {
    //     $validator = Validator::make($request->all(), [
    //         'category_id'   => 'required|array',
    //         'category_id.*' => 'exists:categories,id',
    //     ]);

    //     if ($validator->fails()) {
    //         return $this->error($validator->errors(), 'Validation failed', 422);
    //     }

    //     $user = auth('api')->user();

    //     // Sync categories (delete old and insert new)
    //     $user->user_categories()->delete();
    //     if (! empty($request->category_id)) {
    //         $categories = collect($request->category_id)->map(function ($id) use ($user) {
    //             return [
    //                 'user_id'     => $user->id,
    //                 'category_id' => $id,
    //                 'created_at'  => now(),
    //                 'updated_at'  => now(),
    //             ];
    //         })->toArray();

    //         ProfessionalCategory::insert($categories);
    //     }

    //     // Load updated categories
    //     $user->load('user_categories');
    //     return $this->success($user, 'Professional categories updated successfully', 200);
    // }

    public function services(Request $request)
    {
        // dd($request->all());

        $validator = Validator::make($request->all(), [
            'logo'                      => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'certificate'               => 'nullable|file|mimes:pdf,jpeg,jpg,png|max:5120',

            'services'                  => 'required|array|min:1',
            // 'services.*.name'           => 'required|string|max:100',
            'services.*.starting_price' => 'required|numeric|min:0',
            'services.*.duration'       => 'nullable|string|max:50',
            'services.*.category_id'    => 'required|integer|exists:categories,id',
            // 'services.*.image'          => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
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

        foreach ($request->services as $index => $serviceData) {

            // $image = null;

            // if ($request->hasFile("services.$index.image")) {
            //     $imageFile = $request->file("services.$index.image");
            //     $image     = Helper::uploadImage($imageFile, 'services');
            // }

            $category = Category::find($serviceData['category_id']);

            $user->services()->create([
                'name'           => $category ? $category->title : 'Service',
                'category_id'    => $category ? $category->id : null,
                'starting_price' => $serviceData['starting_price'],
                'duration'       => $serviceData['duration'] ?? null,
                'image'          => $category && $category->image ? $category->image : null,
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
            'profile_completion' => "20%",
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
            'brands'             => $user->user_brands->load('brand'),
            // 'categories'         => $user->user_categories->load('category'),

        ];

        return $this->success($data, 'Professional profile information retrive  successfully', 200);
    }

    public function analytics(Request $request)
    {
        $user = auth('api')->user();

        if (! $user) {
            return $this->error([], 'User not found.', 404);
        }

        $topServices = DB::table('service_bookings')
            ->join('professinal_services', 'service_bookings.service_id', '=', 'professinal_services.id')
            ->where('professinal_services.user_id', $user->id)
            ->select(
                'professinal_services.id',
                'professinal_services.name',
                'professinal_services.starting_price',
                DB::raw('COUNT(service_bookings.id) as total_bookings')
            )
            ->groupBy(
                'professinal_services.id',
                'professinal_services.name',
                'professinal_services.starting_price'
            )
            ->orderByDesc('total_bookings')
            ->limit(5)
            ->get();

        $data = [
            'id'                    => $user->id,
            'profile_completion'    => "20%",
            'total_points'          => $user->total_redeem_points ?? 0,
            'total_bookings'        => Booking::where('owner_id', $user->id)->count(),
            'total_followers'       => $user->followers->count(),
            'total_portfolio_views' => 0,

            'top_services'          => $topServices,
        ];

        return $this->success(
            $data,
            'Professional profile information retrieved successfully',
            200
        );
    }

    public function earning_analytics(Request $request)
    {
        $user = auth('api')->user();

        if (! $user) {
            return $this->error([], 'User not found.', 404);
        }

        $months = (int) $request->get('months', 6);
        $months = max(1, min($months, 24));

        $startDate = now()->subMonths($months - 1)->startOfMonth();
        $endDate   = now()->endOfMonth();

        $earnings = DB::table('service_bookings')
            ->join('bookings', 'service_bookings.booking_id', '=', 'bookings.id')
            ->join('professinal_services', 'service_bookings.service_id', '=', 'professinal_services.id')
            ->where('bookings.owner_id', $user->id)
            ->where('bookings.status', 'completed')
            ->whereBetween('service_bookings.created_at', [$startDate, $endDate])
            ->select(
                DB::raw('YEAR(service_bookings.created_at) as year'),
                DB::raw('MONTH(service_bookings.created_at) as month'),
                DB::raw('SUM(professinal_services.starting_price) as total')
            )
            ->groupBy('year', 'month')
            ->orderBy('year')
            ->orderBy('month')
            ->get()
            ->mapWithKeys(function ($item) {
                return [
                    $item->year . '-' . str_pad($item->month, 2, '0', STR_PAD_LEFT) => $item->total,
                ];
            });

        $data = [];

        for ($i = $months - 1; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $key  = $date->format('Y-m');

            $data[] = [
                'month'  => $date->format('M'),
                'year'   => $date->format('Y'),
                'amount' => (float) ($earnings[$key] ?? 0),
            ];
        }

        return $this->success(
            [
                'range' => "Last {$months} months",
                'data' => $data,
            ],
            'Earning analytics retrieved successfully',
            200
        );
    }

}
