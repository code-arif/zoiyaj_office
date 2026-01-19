<?php

namespace App\Http\Controllers\Web\Backend\Professionals;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Yajra\DataTables\Facades\DataTables;

class ProfessionalsManageController extends Controller
{
    /**
     * Professional list page
     */
    public function index(Request $request)
    {
        // Get statistics for cards
        $totalProfessionals = User::where('role', 'professional')->count();
        $activeProfessionals = User::where('role', 'professional')
            ->where('status', 'active')
            ->count();
        $inactiveProfessionals = User::where('role', 'professional')
            ->where('status', 'inactive')
            ->count();
        $premiumProfessionals = User::where('role', 'professional')
            ->where('is_premium', true)
            ->count();

        return view('backend.layouts.professionals.professional-list', compact(
            'totalProfessionals',
            'activeProfessionals',
            'inactiveProfessionals',
            'premiumProfessionals'
        ));
    }

    /**
     * All professional list
     */
    public function getData(Request $request)
    {
        if ($request->ajax() && $request->wantsJson()) {
            $query = User::query()
                ->select([
                    'id',
                    'first_name',
                    'last_name',
                    'email',
                    'phone_number',
                    'professional_name',
                    'professional_email',
                    'professional_phone',
                    'avatar',
                    'city',
                    'state',
                    'status',
                    'is_premium',
                    'years_in_business',
                    'created_at'
                ])
                ->where('role', 'professional')
                ->with([
                    'specialties.specialty:id,name',
                    'user_brands.brand:id,name'
                ])
                ->orderBy('id', 'desc');

            // Status filter
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            // Premium filter
            if ($request->filled('premium_status')) {
                $query->where('is_premium', $request->premium_status === 'premium' ? 1 : 0);
            }

            // Date range filter
            if ($request->filled('date_from')) {
                $query->whereDate('created_at', '>=', $request->date_from);
            }
            if ($request->filled('date_to')) {
                $query->whereDate('created_at', '<=', $request->date_to);
            }

            return DataTables::eloquent($query)
                ->addIndexColumn()
                ->filterColumn('name', function ($query, $keyword) {
                    $query->where(function ($q) use ($keyword) {
                        $q->where(DB::raw("CONCAT(first_name, ' ', COALESCE(last_name, ''))"), 'like', "%{$keyword}%")
                            ->orWhere('professional_name', 'like', "%{$keyword}%");
                    });
                })
                ->filterColumn('email', function ($query, $keyword) {
                    $query->where('email', 'like', "%{$keyword}%")
                        ->orWhere('professional_email', 'like', "%{$keyword}%");
                })
                ->addColumn('name', function ($data) {
                    $fullName = trim(($data->first_name ?? '') . ' ' . ($data->last_name ?? ''));
                    $displayName = $fullName ?: ($data->professional_name ?? 'N/A');

                    $avatar = $data->avatar
                        ? asset($data->avatar)
                        : 'https://ui-avatars.com/api/?name=' . urlencode($displayName) . '&background=random';

                    $phone = $data->phone_number ?? $data->professional_phone ?? 'No phone';
                    $email = $data->professional_email ?? $data->email;

                    return '<div class="d-flex align-items-center">
                                <img src="' . $avatar . '" alt="avatar" class="rounded-circle me-3 flex-shrink-0" width="45" height="45" style="object-fit: cover;">
                                <div class="text-truncate">
                                    <div class="fw-semibold text-truncate" title="' . e($displayName) . '">' . e($displayName) . '</div>
                                    <small class="text-muted text-truncate d-block" title="' . e($phone) . '">' . e($phone) . '</small>
                                </div>
                            </div>';
                })
                ->addColumn('business_info', function ($data) {
                    $location = trim(($data->city ?? '') . ', ' . ($data->state ?? ''));
                    $location = $location === ', ' ? 'N/A' : $location;

                    return '<div class="text-truncate">
                                <div class="fw-semibold text-truncate" title="' . e($data->professional_name ?? 'N/A') . '">' . e($data->professional_name ?? 'N/A') . '</div>
                                <small class="text-muted text-truncate d-block" title="' . e($location) . '">
                                    <i class="fe fe-map-pin me-1"></i>' . e($location) . '
                                </small>
                            </div>';
                })
                ->addColumn('specialties', function ($data) {
                    $specialties = $data->specialties->pluck('specialty.name')->take(2);

                    if ($specialties->isEmpty()) {
                        return '<span class="text-muted">No specialties</span>';
                    }

                    $html = '';
                    foreach ($specialties as $specialty) {
                        $html .= '<span class="badge border border-info text-info bg-info p-3">' . e($specialty) . '</span>';
                    }


                    if ($data->specialties->count() > 2) {
                        $remaining = $data->specialties->count() - 2;

                        $html .= '<span class="badge border border-secondary text-secondary bg-transparent p-3">
                +' . $remaining . '
              </span>';
                    }


                    return $html;
                })
                ->addColumn('experience', function ($data) {
                    $years = $data->years_in_business ?? 0;
                    return '<div class="text-center">
                                <div class="fw-semibold">' . $years . ' Year' . ($years != 1 ? 's' : '') . '</div>
                                <small class="text-muted">Experience</small>
                            </div>';
                })
                ->addColumn('account_status', function ($data) {
                    $statusColors = [
                        'active' => 'success',
                        'inactive' => 'secondary'
                    ];

                    $color = $statusColors[$data->status] ?? 'secondary';
                    $premiumBadge = $data->is_premium
                        ? ' <span class="badge bg-warning ms-1 p-1"><i class="fe fe-star" style="font-size:14px"></i> Premium</span>'
                        : '';

                    return '<span class="badge p-3 bg-' . $color . '">' . e(ucfirst($data->status)) . '</span>' . $premiumBadge;
                })
                ->addColumn('action', function ($data) {
                    return '<div class="btn-group btn-group-sm" role="group">
                                <a href="' . route('professionals.show', $data->id) . '" class="btn btn-primary" title="View Details">
                                    <i class="fe fe-eye"></i>
                                </a>
                                <button type="button" onclick="toggleStatus(' . $data->id . ', \'' . $data->status . '\')" class="btn btn-' . ($data->status === 'active' ? 'warning' : 'success') . '" title="' . ($data->status === 'active' ? 'Deactivate' : 'Activate') . '">
                                    <i class="fe fe-' . ($data->status === 'active' ? 'x-circle' : 'check-circle') . '"></i>
                                </button>
                                <button type="button" onclick="togglePremium(' . $data->id . ', ' . ($data->is_premium ? 'true' : 'false') . ')" class="btn btn-info" title="Toggle Premium">
                                    <i class="fe fe-star"></i>
                                </button>
                            </div>';
                })
                ->rawColumns(['name', 'business_info', 'specialties', 'experience', 'account_status', 'action'])
                ->make(true);
        }
    }

    /**
     * Show professional details
     */
    public function show($id)
    {
        $professional = User::with([
            'specialties.specialty',
            'user_brands.brand',
            'services',
            'portfolios'
        ])->where('role', 'professional')
            ->findOrFail($id);

        // return $professional;exit();

        return view('backend.layouts.professionals.professional-details', compact('professional'));
    }

    /**
     * Toggle professional status
     */
    public function toggleStatus(Request $request, $id)
    {
        try {
            $professional = User::where('role', 'professional')->findOrFail($id);

            $newStatus = $professional->status === 'active' ? 'inactive' : 'active';
            $professional->update(['status' => $newStatus]);

            return response()->json([
                'success' => true,
                'message' => 'Professional status updated to ' . $newStatus,
                'status' => $newStatus
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update status: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Toggle premium status
     */
    public function togglePremium(Request $request, $id)
    {
        try {
            $professional = User::where('role', 'professional')->findOrFail($id);

            $professional->update(['is_premium' => !$professional->is_premium]);

            return response()->json([
                'success' => true,
                'message' => 'Premium status updated successfully',
                'is_premium' => $professional->is_premium
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update premium status: ' . $e->getMessage()
            ], 500);
        }
    }
}
