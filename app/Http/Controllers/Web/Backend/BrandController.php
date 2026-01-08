<?php

namespace App\Http\Controllers\Web\Backend;

use Exception;
use App\Helper\Helper;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Specialty;
use Yajra\DataTables\Facades\DataTables;



class BrandController extends Controller
{

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = Brand::all();
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
        return view("backend.layouts.brand.index");
    }


    public function create()
    {
        return view('backend.layouts.brand.create');
    }


    public function store(Request $request)
    {

        $validate = $request->validate([
            'name' => 'required',
        ]);

        try {

            Brand::create($validate);


            session()->put('t-success', 'brand created successfully');

        } catch (Exception $e) {
            session()->put('t-error', $e->getMessage());
        }

        return redirect()->route('admin.brand.index')->with('success', 'brand created successfully');
    }




    public function edit($id)
    {
        $brand = Brand::findOrFail($id);
        return view('backend.layouts.brand.edit', compact('brand'));
    }


    public function update(Request $request, $id)
    {
        $validate = $request->validate([
            'name' => 'required',

        ]);

        try {
            $Brand = Brand::findOrFail($id);



            $Brand->update($validate);
            session()->put('t-success', 'Brand updated successfully');
        } catch (Exception $e) {
            session()->put('t-error', $e->getMessage());
        }

        return redirect()->route('admin.brand.index');
    }


    public function destroy(string $id)
    {

        $data = Brand::findOrFail($id);
        if (empty($data)) {
            return response()->json([
                'success' => false,
                'message' => 'Brand not found.',
            ], 404);
        }



        $data->delete();

        return response()->json([
            'success' => true,
            'message' => 'Brand deleted successfully!',
        ],200);
    }


}
