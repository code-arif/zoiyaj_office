<?php

namespace App\Http\Controllers\Web\Backend\CMS;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserPreference;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class UserPreferenceController extends Controller
{
    // Index + DataTable
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = UserPreference::with('user')->latest();

            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('user', fn($row) => $row->user->name ?? '-')
                ->addColumn('status', fn($row) =>
                    '<span class="badge bg-success">Active</span>'
                )
                ->addColumn('action', function($row) {
                    return '
                        <a href="'.route("admin.cms.admin.user_preferences.edit", $row->id).'" class="btn btn-sm btn-info">Edit</a>
                        <button onclick="showDeleteConfirm('.$row->id.')" class="btn btn-sm btn-danger">Delete</button>
                    ';
                })
                ->rawColumns(['status','action'])
                ->make(true);
        }

        return view('backend.layouts.cms.user_preferences.index', [
            'name' => 'User Preferences',
            'section' => 'index',
            'url' => 'admin.cms.admin.user_preferences',
        ]);
    }

    // Create page
    public function create()
    {
        return view('backend.layouts.cms.user_preferences.create', [
            'users' => User::select('id','name')->get(),
            'name' => 'User Preferences',
            'section' => 'create',
            'url' => 'admin.cms.admin.user_preferences',
        ]);
    }

    // Store
    public function store(Request $request)
    {
        $data = $request->validate([
            'user_id' => 'required|exists:users,id',
            'allergies' => 'nullable|string',
            'ingredients_to_avoid' => 'nullable|string',
            'ethical_preferences' => 'nullable|string',
            'skin_type' => 'nullable|string',
            'hair_type' => 'nullable|string',
            'hair_texture' => 'nullable|string',
        ]);

        UserPreference::create($data);

        return redirect()
            ->route('admin.cms.admin.user_preferences.index')
            ->with('success','User Preference Created Successfully');
    }

    // Edit page
    public function edit($id)
    {
        return view('backend.layouts.cms.user_preferences.edit', [
            'preference' => UserPreference::findOrFail($id),
            'users' => User::select('id','name')->get(),
            'name' => 'User Preferences',
            'section' => 'edit',
            'url' => 'admin.cms.admin.user_preferences',
        ]);
    }

    // Update
    public function update(Request $request, $id)
    {
        $preference = UserPreference::findOrFail($id);

        $data = $request->validate([
            'user_id' => 'required|exists:users,id',
            'allergies' => 'nullable|string',
            'ingredients_to_avoid' => 'nullable|string',
            'ethical_preferences' => 'nullable|string',
            'skin_type' => 'nullable|string',
            'hair_type' => 'nullable|string',
            'hair_texture' => 'nullable|string',
        ]);

        $preference->update($data);

        return redirect()
            ->route('admin.cms.admin.user_preferences.index')
            ->with('success','User Preference Updated Successfully');
    }

    // Delete (AJAX)
    public function destroy($id)
    {
        UserPreference::findOrFail($id)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Deleted Successfully'
        ]);
    }

    // Status toggle (demo)
    public function status($id)
    {
        return response()->json([
            'success' => true,
            'message' => 'Status Updated'
        ]);
    }
}
