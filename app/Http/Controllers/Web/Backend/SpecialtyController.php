<?php

namespace App\Http\Controllers\Web\Backend;

use Exception;
use App\Helper\Helper;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Models\Specialty;
use Yajra\DataTables\Facades\DataTables;


class SpecialtyController extends Controller
{

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = Specialty::all();
            return DataTables::of($data)
                ->addIndexColumn()

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
                ->rawColumns(['action'])
                ->make();
        }
        return view("backend.layouts.specialty.index");
    }


    public function create()
    {
        return view('backend.layouts.specialty.create');
    }


    public function store(Request $request)
    {

        $validate = $request->validate([
            'name' => 'required',
        ]);

        try {

            Specialty::create($validate);


            session()->put('t-success', 'Specialty created successfully');

        } catch (Exception $e) {
            session()->put('t-error', $e->getMessage());
        }

        return redirect()->route('admin.specialty.index')->with('success', 'Specialty created successfully');
    }


    public function show(Category $category, $id)
    {
        $category = Category::findOrFail($id);
        return view('backend.layouts.specialty.edit', compact('category'));
    }


    public function edit($id)
    {
        $specialty = Specialty::findOrFail($id);
        return view('backend.layouts.specialty.edit', compact('specialty'));
    }


    public function update(Request $request, $id)
    {
        $validate = $request->validate([
            'name' => 'required',

        ]);

        try {
            $Specialty = Specialty::findOrFail($id);



            $Specialty->update($validate);
            session()->put('t-success', 'Specialty updated successfully');
        } catch (Exception $e) {
            session()->put('t-error', $e->getMessage());
        }

        return redirect()->route('admin.specialty.index');
    }


    public function destroy(string $id)
    {

        $data = Specialty::findOrFail($id);
        if (empty($data)) {
            return response()->json([
                'success' => false,
                'message' => 'Specialty not found.',
            ], 404);
        }



        $data->delete();

        return response()->json([
            'success' => true,
            'message' => 'Specialty deleted successfully!',
        ],200);
    }

    public function status(int $id): JsonResponse
    {
        $data = Category::findOrFail($id);

        if (!$data) {
            return response()->json([
                'status' => 'error',
                'message' => 'Category not found.',
            ]);
        }
        $data->status = $data->status === 'active' ? 'inactive' : 'active';
        $data->save();
        return response()->json([
            'status' => 'success',
            'message' => 'Status Changed successful!',
        ]);
    }
}
