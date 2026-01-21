<?php

namespace App\Http\Controllers\Web\Backend;

use App\Http\Controllers\Controller;
use App\Models\Preference;
use Illuminate\Http\Request;

class PreferenceController extends Controller
{
    public function index(Request $request)
    {
        $type = $request->type ?? 'skintype';

        $types = [
            'skintype',
            'hairtype',
            'hairtexture',
            'allergies',
            'ingredients',
            'ethical'
        ];

        $preferences = Preference::where('type', $type)->latest()->get();

        return view('backend.layouts.preferences.index', compact(
            'preferences',
            'types',
            'type'
        ));
    }

    public function create(Request $request)
    {
        $type = $request->type ?? 'skintype';

        return view('backend.layouts.preferences.create', compact('type'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'type' => 'required',
            'name' => 'required|string|max:255',
        ]);

        Preference::create($request->all());

        return redirect()
            ->route('admin.preferences.index', ['type' => $request->type])
            ->with('success', 'Preference Added Successfully');
    }

    public function edit($id)
    {
        $preference = Preference::findOrFail($id);

        return view('backend.layouts.preferences.edit', compact('preference'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $preference = Preference::findOrFail($id);
        $preference->update($request->only('name'));

        return redirect()
            ->route('admin.preferences.index', ['type' => $preference->type])
            ->with('success', 'Preference Updated Successfully');
    }

    public function destroy($id)
    {
        Preference::findOrFail($id)->delete();

        return back()->with('success', 'Preference Deleted Successfully');
    }
}