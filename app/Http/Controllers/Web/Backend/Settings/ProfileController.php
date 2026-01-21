<?php

namespace App\Http\Controllers\Web\Backend\Settings;

use Exception;
use App\Models\User;
use App\Helper\Helper;
use Illuminate\View\View;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Validator;

class ProfileController extends Controller
{
    public function index(Request $request)
    {
        $user = User::find($request->id);
        return view('backend.layouts.settings.profile_settings', compact('user'));
    }

public function UpdateProfile(Request $request)
{
    // Validate request
    $validator = Validator::make($request->all(), [
        'name'  => 'nullable|string|min:2|max:100',
        'email' => 'nullable|email|unique:users,email,' . auth()->id(),
    ]);

   

    try {
        $user = auth()->user();

        if ($request->filled('name')) {
            $user->name = $request->name;
        }

        if ($request->filled('email')) {
            $user->email = $request->email;
        }

        $user->save();

        session()->put('t-success', 'Profile updated successfully');
    } catch (Exception $e) {
        // Log the error
        Log::error('Profile update error: '.$e->getMessage());

        // Return exact error message to the user
        return redirect()->back()->with('t-error', $e->getMessage());
    }

    return redirect()->back();
}
    public function UpdatePassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'old_password' => 'required',
            'password'     => 'required|confirmed|min:8',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }
        try {
            $user = Auth::user();
            if (Hash::check($request->old_password, $user->password)) {
                $user->password = Hash::make($request->password);
                $user->save();

                return redirect()->back()->with('t-success', 'Password updated successfully');
            } else {
                return redirect()->back()->with('t-error', 'Current password is incorrect');
            }
        } catch (Exception) {
            return redirect()->back()->with('t-error', 'Something went wrong');
        }
    }
    public function UpdateProfilePicture(Request $request)
    {
        $request->validate([
            'avatar' => 'required|image|mimes:jpeg,png,jpg,gif|max:10240',
        ]);

        try {
            $user      = Auth::user();
            $image     = $request->file('avatar');
            $imageName = time() . '.' . $image->getClientOriginalExtension();

            //? Check if there's an existing profile picture
            if ($user->avatar && file_exists(public_path($user->avatar))) {
                Helper::deleteImage(public_path($user->avatar));
            }

            //* Use the Helper class to handle the file upload
            $imagePath = Helper::uploadImage($image, 'profile', $imageName);

            if ($imagePath === null) {
                throw new Exception('Failed to upload image.');
            }

            //! Update user's avatar with the new image path
            $user->avatar = $imagePath;
            $user->save();

            return response()->json([
                'success'   => true,
                'image_url' => asset($imagePath),
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }
}
