<?php

namespace App\Http\Controllers;

use App\Models\ApiHit;
use App\Models\ChatHistory;
use App\Services\OpenAiChatService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Log;
use Illuminate\Support\Facades\Validator;

class OpenAiChatController extends Controller
{
    protected $openAiChatService;

    public function __construct(OpenAiChatService $openAiChatService)
    {
        $this->openAiChatService = $openAiChatService;
    }

   public function openAiChat(Request $request)
{
    try {
        DB::beginTransaction();

        $user = auth('api')->user();
        if (!$user) {
            return response()->json(['error' => 'User not authenticated'], 401);
        }

        $validator = Validator::make($request->all(), [
            'prompt' => 'required|string|max:2000',
            'image' => 'nullable|mimetypes:image/*|max:10240',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 422);
        }

        $prompt = $request->input('prompt');
        $imagePath = null;
        $imageDescription = null;

        if ($request->hasFile('image')) {
            $publicPath = public_path('images/chat');
            if (!File::exists($publicPath)) {
                File::makeDirectory($publicPath, 0755, true);
            }

            $imageName = 'chat_' . time() . '_' . $user->id . '_' .rand(5) . '.' . $request->image->extension();
            $request->image->move($publicPath, $imageName);

            $imagePath = 'images/chat/' . $imageName;
        }

        $chatResponse = $imagePath
            ? $this->openAiChatService->getImageAnalysisResponse($user->id, $prompt, public_path($imagePath))
            : $this->openAiChatService->getChatResponse($user->id, $prompt);

        if (!$chatResponse['success']) {
            // Get exact error
            $errorMessage = $chatResponse['error'] ?? ($chatResponse['response'] ?? 'Unknown error');

            // Log detailed error for debugging
            Log::error('OpenAI API failed', [
                'user_id' => $user->id,
                'prompt' => $prompt,
                'image_path' => $imagePath,
                'api_error' => $errorMessage,
                'raw_response' => $chatResponse
            ]);

            DB::rollBack();
            return response()->json([
                'error' => 'OpenAI API request failed',
                'details' => $errorMessage,
                'raw_response' => $chatResponse // optional, can remove if too sensitive
            ], 500);
        }

        $imageDescription = $imagePath ? $chatResponse['response'] : null;



        DB::commit();

        return response()->json([
            'prompt' => $prompt,
            'response' => $chatResponse['response'],
            'response_type' => $chatResponse['response_type'] ?? 'text',
            'table_data' => $chatResponse['table_data'] ?? null,
            'list_data' => $chatResponse['list_data'] ?? null,
            'image_url' => $imagePath ? asset($imagePath) : null,
            'image_description' => $imageDescription,
        ], 200);

    } catch (\Exception $e) {
        DB::rollBack();
        Log::error('OpenAiChatController@openAiChat Exception', [
            'user_id' => $user->id ?? null,
            'prompt' => $request->input('prompt') ?? null,
            'exception_message' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);

        return response()->json([
            'error' => 'An exception occurred while processing your request.',
            'details' => $e->getMessage(),
        ], 500);
    }
}

}
