<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\UserPreference;

class UserPreferenceController extends Controller
{
    // Get preference for a user
    public function index(Request $request)
    {
        $userId = $request->user_id ?? 1;

        $preference = UserPreference::where('user_id', $userId)->first();

        return response()->json([
            'user_id' => $userId,
            'preference' => $preference
        ]);
    }

    // Insert preference (only once per user_id)
    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'allergies' => 'nullable|string',
            'ingredients_to_avoid' => 'nullable|string',
            'ethical_preferences' => 'nullable|string',
            'skin_type' => 'nullable|string',
            'hair_type' => 'nullable|string',
            'hair_texture' => 'nullable|string',
        ]);

        $existing = UserPreference::where('user_id', $request->user_id)->first();
        if ($existing) {
            return response()->json([
                'message' => 'Preference for this user already exists',
                'data' => $existing
            ], 409); // Conflict
        }

        $preference = UserPreference::create($request->only([
            'user_id',
            'allergies',
            'ingredients_to_avoid',
            'ethical_preferences',
            'skin_type',
            'hair_type',
            'hair_texture'
        ]));

        return response()->json([
            'message' => 'Preference created successfully',
            'data' => $preference
        ], 201);
    }

    // Update preference by user_id
    public function update(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'allergies' => 'nullable|string',
            'ingredients_to_avoid' => 'nullable|string',
            'ethical_preferences' => 'nullable|string',
            'skin_type' => 'nullable|string',
            'hair_type' => 'nullable|string',
            'hair_texture' => 'nullable|string',
        ]);

        $preference = UserPreference::where('user_id', $request->user_id)->firstOrFail();
        $preference->update($request->only([
            'allergies',
            'ingredients_to_avoid',
            'ethical_preferences',
            'skin_type',
            'hair_type',
            'hair_texture'
        ]));

        return response()->json([
            'message' => 'Preference updated successfully',
            'data' => $preference
        ]);
    }

    // Delete preference by user_id
    public function destroy(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id'
        ]);

        $preference = UserPreference::where('user_id', $request->user_id)->firstOrFail();
        $preference->delete();

        return response()->json([
            'message' => 'Preference deleted successfully'
        ]);
    }
}
