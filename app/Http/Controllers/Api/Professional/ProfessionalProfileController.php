<?php
namespace App\Http\Controllers\Api\Professional;

use App\Http\Controllers\Controller;
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
}
