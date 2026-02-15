<?php

namespace App\Http\Controllers\Api\Professional;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProfessionalListController extends Controller
{
    // List all professional users with pagination
    public function professional_list(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $search  = $request->input('search') ? trim($request->input('search')) : null;

        $query = User::where('role', 'professional')
            ->where('status', 'active')
            ->select([
                'id',
                'first_name',
                'last_name',
                'avatar',
                'gender',
            ]);

        // Hide filter (ONLY for logged-in client)
        if ($user = auth()->user()) {

            $query->whereNotIn('id', function ($sub) use ($user) {
                $sub->select('professional_id')
                    ->from('hidden_professionals')
                    ->where('user_id', $user->id);
            });
        }

        // Search
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->whereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$search}%"])
                    ->orWhere('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%");
            });
        }

        $favouriteIds = [];

        if ($user = auth()->user()) {
            $favouriteIds = DB::table('favourite_professionals')
                ->where('user_id', $user->id)
                ->pluck('professional_id')
                ->toArray();
        }


        $paginator = $query->paginate($perPage);

        $data = collect($paginator->items())->map(function ($pro) use ($favouriteIds) {
            return [
                'id'         => $pro->id,
                'name'       => trim("{$pro->first_name} {$pro->last_name}"),
                'avatar'     => $pro->avatar ? asset($pro->avatar) : asset('default/profile.jpg'),
                'profession' => $this->getProfessionTitle($pro),
                'rating'     => 4.9,
                'reviews'    => 234,
                'distance'   => '1.2 mi',

                // instant check (no DB hit)
                'is_favourite' => in_array($pro->id, $favouriteIds),
            ];
        });

        return response()->json([
            'success'    => true,
            'data'       => $data,
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
            ],
            'message'    => 'Professionals retrieved successfully.',
        ]);
    }


    // Helper — customize according to your real data
    private function getProfessionTitle($user)
    {
        // You can add a 'profession' or 'category' column later
        // For now — simple mapping example
        $map = [
            'male'   => 'Barber',
            'female' => 'Hair Stylist',
            // or better: add profession column to users table
        ];

        return $map[$user->gender] ?? 'Professional';
    }

    // Toggle hide/unhide a professional for the current user
    public function toggleHideProfessional(Request $request, $professionalId)
    {
        $user = Auth::user();

        if ($user->hiddenProfessionals()->where('professional_id', $professionalId)->exists()) {
            $user->hiddenProfessionals()->detach($professionalId);
            $message = 'Unhidden';
        } else {
            $user->hiddenProfessionals()->attach($professionalId);
            $message = 'Hidden';
        }

        return response()->json([
            'success' => true,
            'message' => $message,
        ]);
    }

    // Toggle favourite/unfavourite a professional for the current user
    public function toggleFavourite($professionalId)
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        if ($user->favouriteProfessionals()->where('professional_id', $professionalId)->exists()) {

            $user->favouriteProfessionals()->detach($professionalId);

            return response()->json([
                'success' => true,
                'message' => 'Removed from favourites'
            ]);
        }

        $user->favouriteProfessionals()->attach($professionalId, [
            'favourited_at' => now()
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Added to favourites'
        ]);
    }

    // Faourite professionals list (optional, can be in a separate controller)
    public function favouriteList(Request $request)
    {
        $user = auth()->user();

        $perPage = $request->input('per_page', 10);

        $query = $user->favouriteProfessionals()
            ->where('status', 'active')
            ->select([
                'users.id',
                'first_name',
                'last_name',
                'avatar',
                'gender',
            ]);

        $paginator = $query->paginate($perPage);

        $data = collect($paginator->items())->map(function ($pro) {
            return [
                'id'         => $pro->id,
                'name'       => trim("{$pro->first_name} {$pro->last_name}"),
                'avatar'     => $pro->avatar ? asset($pro->avatar) : asset('default/profile.jpg'),
                'profession' => $this->getProfessionTitle($pro),
                'rating'     => 4.9,
                'reviews'    => 234,
                'distance'   => '1.2 mi',
                'is_favourite' => true,
            ];
        });

        return response()->json([
            'success' => true,
            'data'    => $data,
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
            ],
        ]);
    }
}
