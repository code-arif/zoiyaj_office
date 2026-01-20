<?php
namespace App\Http\Controllers\Api\Client;

use App\Helper\Helper;
use App\Http\Controllers\Controller;
use App\Models\Preference;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Throwable;

class ProfileSetupController extends Controller
{

    use ApiResponse;

    public function setup_basic(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'avatar'                      => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
                'age'                         => 'nullable|integer',
                'is_wheelchair_accessibility' => 'nullable|boolean',
                'is_hijab_friendly'           => 'nullable|boolean',
                'is_prone'                    => 'nullable|boolean',
            ]);

            if ($validator->fails()) {
                return $this->error($validator->errors(), 'Validation failed', 422);
            }

            $user = auth('api')->user();

            DB::beginTransaction();

            // Handle avatar upload
            if ($request->hasFile('avatar')) {
                if ($user->avatar) {
                    Helper::deleteImage($user->avatar);
                }
                $user->avatar = Helper::uploadImage($request->file('avatar'), 'profile');
            }

            // Update other fields
            $user->age                         = $request->age ?? $user->age;
            $user->is_wheelchair_accessibility = $request->is_wheelchair_accessibility ?? $user->is_wheelchair_accessibility;
            $user->is_hijab_friendly           = $request->is_hijab_friendly ?? $user->is_hijab_friendly;
            $user->is_prone                    = $request->is_prone ?? $user->is_prone;

            $user->save();

            DB::commit();

            $data = [
                'id'                          => $user->id,
                'role'                        => $user->role,
                'avatar'                      => $user->avatar,
                'age'                         => $user->age,
                'is_wheelchair_accessibility' => $user->is_wheelchair_accessibility,
                'is_hijab_friendly'           => $user->is_hijab_friendly,
                'is_prone'                    => $user->is_prone,
            ];

            return $this->success($data, 'Profile & preferences added successfully', 200);

        } catch (Throwable $e) {
            DB::rollBack();

            return $this->error(
                ['error' => $e->getMessage()],
                'Something went wrong while updating profile',
                500
            );
        }
    }

    public function preferences_info(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'preference_id'   => 'required|array',
                'preference_id.*' => 'exists:preferences,id',
            ]);

            if ($validator->fails()) {
                return $this->error($validator->errors(), 'Validation failed', 422);
            }

            $user = auth('api')->user();

            DB::beginTransaction();

            // Delete old preferences
            $user->preferences()->delete();

            foreach ($request->preference_id as $preferenceId) {
                $preference = Preference::findOrFail($preferenceId);

                $user->preferences()->create([
                    'preference_id' => $preferenceId,
                    'type'          => $preference->type,
                ]);
            }

            DB::commit();

            $data = [
                'id'          => $user->id,
                'role'        => $user->role,
                'preferences' => $user->preferences,
            ];

            return $this->success($data, 'Preferences information saved successfully', 200);

        } catch (Throwable $e) {

            DB::rollBack();

            return $this->error(
                ['error' => $e->getMessage()],
                'Something went wrong while saving preferences',
                500
            );
        }
    }



    public function about_me(Request $request)
    {
        $user = auth('api')->user();

        $data = [
            'id'                          => $user->id,
            'role'                        => $user->role,
            'avatar'                      => $user->avatar,
            'age'                         => $user->age,
            'is_wheelchair_accessibility' => $user->is_wheelchair_accessibility,
            'is_hijab_friendly'           => $user->is_hijab_friendly,
            'is_prone'                    => $user->is_prone,
            'preferences'                 => $user->preferences->load('preference'),
        ];

        return $this->success($data, 'About me retrieved successfully', 200);
    }





}
