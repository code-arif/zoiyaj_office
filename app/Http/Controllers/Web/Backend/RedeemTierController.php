<?php
namespace App\Http\Controllers\Web\Backend;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\RedeemTier;
use Exception;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class RedeemTierController extends Controller
{

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = RedeemTier::all();
            return DataTables::of($data)
                ->addIndexColumn()

                ->addColumn('thumb', function ($data) {
                    $url = ! empty($data->thumb) ? asset($data->thumb) : asset('uploads/default.png');
                    return '<img src="' . $url . '" alt="' . $data->name . '" width="50" height="50"/>';
                })



                ->addColumn('is_active', function ($data) {
                    $backgroundColor = $data->is_active ? '#4CAF50' : '#ccc';
                    $sliderTranslateX = $data->is_active ? '26px' : '2px';
                    $sliderStyles = "position: absolute; top: 2px; left: 2px; width: 20px; height: 20px; background-color: white; border-radius: 50%; transition: transform 0.3s ease; transform: translateX($sliderTranslateX);";

                    $status = '<div class="form-check form-switch" style="margin-left:40px; position: relative; width: 50px; height: 24px; background-color: ' . $backgroundColor . '; border-radius: 12px; transition: background-color 0.3s ease; cursor: pointer;">';
                    $status .= '<input onclick="showStatusChangeAlert(' . $data->id . ')" type="checkbox" class="form-check-input" id="customSwitch' . $data->id . '" getAreaid="' . $data->id . '" name="status" style="position: absolute; width: 100%; height: 100%; opacity: 0; z-index: 2; cursor: pointer;">';
                    $status .= '<span style="' . $sliderStyles . '"></span>';
                    $status .= '<label for="customSwitch' . $data->id . '" class="form-check-label" style="margin-left: 10px;"></label>';
                    $status .= '</div>';

                    return $status;
                })



                ->addColumn('action', function ($data) {
                    return '<div class="btn-group btn-group-sm" role="group" aria-label="Basic example">

                                <a href="#" type="button" onclick="goToEdit(' . $data->id . ')" class="btn btn-primary fs-14 text-white delete-icn" title="Edit">
                                    <i class="fe fe-edit"></i>
                                </a>

                                <a href="#" type="button" onclick="showDeleteConfirm(' . $data->id . ')" class="btn btn-danger fs-14 text-white delete-icn" title="Delete">
                                    <i class="fe fe-trash"></i>
                                </a>
                            </div>';
                })
                ->rawColumns(['thumb','is_active', 'action'])
                ->make();
        }
        return view("backend.layouts.redeem_tier.index");
    }

    public function create()
    {
        return view('backend.layouts.redeem_tier.create');
    }

    public function store(Request $request)
    {

        $validate = $request->validate([
            'tier_name'       => 'required',
            'points_required' => 'required|integer',
            'discount_amount' => 'required|numeric',
            'description'     => 'nullable|string',
        ]);

        try {

            RedeemTier::create($validate);

            session()->put('t-success', 'RedeemTier created successfully');

        } catch (Exception $e) {
            session()->put('t-error', $e->getMessage());
        }

        return redirect()->route('admin.redeem_tiers.index')->with('success', 'RedeemTier created successfully');
    }

    public function edit($id)
    {
        $redeemTier = RedeemTier::findOrFail($id);
        return view('backend.layouts.redeem_tier.edit', compact('redeemTier'));
    }

    public function update(Request $request, $id)
    {
        $validate = $request->validate([
            'tier_name'       => 'required',
            'points_required' => 'required|integer',
            'discount_amount' => 'required|numeric',
            'description'     => 'nullable|string',
        ]);

        try {


            $redeemTier = RedeemTier::findOrFail($id);

            $redeemTier->update($validate);
            session()->put('t-success', 'Redeem Tier updated successfully');
        } catch (Exception $e) {
            session()->put('t-error', $e->getMessage());
        }

        return redirect()->route('admin.redeem_tiers.index');
    }

    public function destroy(string $id)
    {

        $data = RedeemTier::findOrFail($id);
        if (empty($data)) {
            return response()->json([
                'success' => false,
                'message' => 'Redeem Tier not found.',
            ], 404);
        }

        $data->delete();

        return response()->json([
            'success' => true,
            'message' => 'Redeem Tier deleted successfully!',
        ], 200);
    }


   public function status( $id)
    {
        $data = RedeemTier::findOrFail($id);

        if (!$data) {
            return response()->json([
                'status' => 'error',
                'message' => 'Redeem Tier not found.',
            ]);
        }
        $data->is_active = $data->is_active === 1 ? 0 : 1;

        $data->save();
        return response()->json([
            'status' => 'success',
            'message' => 'Status Changed successful!',
        ]);
    }

}
