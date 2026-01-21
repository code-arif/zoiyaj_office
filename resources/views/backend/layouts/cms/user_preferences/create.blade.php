@extends('backend.app', ['title' => 'Add User Preference'])

@section('content')
<div class="container">
    <h1>Create User Preference</h1>
    <form action="{{ route($url.'.store') }}" method="POST">
        @csrf
        <div class="mb-3">
            <label>User</label>
            <select name="user_id" class="form-control" required>
                <option value="">Select User</option>
                @foreach($users as $user)
                    <option value="{{ $user->id }}">{{ $user->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="mb-3">
            <label>Allergies</label>
            <textarea name="allergies" class="form-control"></textarea>
        </div>

        <div class="mb-3">
            <label>Ingredients to Avoid</label>
            <textarea name="ingredients_to_avoid" class="form-control"></textarea>
        </div>

        <div class="mb-3">
            <label>Ethical Preferences</label>
            <textarea name="ethical_preferences" class="form-control"></textarea>
        </div>

        <div class="mb-3">
            <label>Skin Type</label>
            <input type="text" name="skin_type" class="form-control">
        </div>

        <div class="mb-3">
            <label>Hair Type</label>
            <input type="text" name="hair_type" class="form-control">
        </div>

        <div class="mb-3">
            <label>Hair Texture</label>
            <input type="text" name="hair_texture" class="form-control">
        </div>

        <button type="submit" class="btn btn-success">Save</button>
        <a href="{{ route($url.'.index') }}" class="btn btn-secondary">Cancel</a>
    </form>
</div>
@endsection
