<?php
namespace App\Http\Controllers\Api\Professional;


use App\Helper\Helper;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\ProfessionalPortfolio;
use Illuminate\Support\Facades\Validator;

class PortfolioController extends Controller
{
    use ApiResponse;

    public function list(Request $request)
    {

        $user = auth('api')->user();

        $userData = [
            'id'        => $user->id,
            'role'      => $user->role,
            'portfolio' => $user->portfolios,
        ];

        return $this->success($userData, 'Portfolio retrive successfully', 200);
    }

    public function update(Request $request)
    {
        //  Basic validation
        $validator = Validator::make($request->all(), [
            'name'  => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'video' => 'nullable|file|mimes:mp4,mov,avi,webm|max:102400', // 100MB
            'type'  => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors(), 'Validation failed', 422);
        }

        $hasImage = $request->hasFile('image');
        $hasVideo = $request->hasFile('video');
        $name     = $request->input('name');

        // At least one media must be uploaded
        if (! $hasImage && ! $hasVideo) {
            return $this->error([], 'You must upload either an image or a video', 422);
        }

        // Cannot upload both
        if ($hasImage && $hasVideo) {
            return $this->error([], 'You cannot upload both image and video together', 422);
        }

        // Name is required if image is uploaded
        if ($hasImage && empty($name)) {
            return $this->error([], 'Name is required when uploading an image', 422);
        }

        $user = auth('api')->user();

        $image         = null;
        $video         = null;
        $portfolioName = null;

        //  Handle image upload
        if ($hasImage) {
            if ($user->image) {
                Helper::deleteImage($user->image);
            }
            $image         = Helper::uploadImage($request->file('image'), 'portfolio');
            $portfolioName = $name; // only store name if image is uploaded
        }

        //  Handle video upload
        if ($hasVideo) {
            if ($user->video) {
                Helper::deleteImage($user->video);
            }
            $video         = Helper::uploadImage($request->file('video'), 'portfolio');
            $portfolioName = null; // don't store name if video is uploaded
        }

        //  Create new portfolio entry
        $portfolio = ProfessionalPortfolio::create([
            'user_id' => $user->id,
            'name'    => $portfolioName,
            'type'    => $request->type,
            'image'   => $image,
            'video'   => $video,
        ]);

        // Step 7: Prepare response
        $userData = [
            'id'        => $user->id,
            'role'      => $user->role,
            'portfolio' => $user->portfolios,
        ];

        return $this->success($userData, 'Portfolio added successfully', 200);
    }

}
