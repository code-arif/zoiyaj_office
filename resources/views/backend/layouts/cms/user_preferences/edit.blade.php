@extends('backend.app', ['title' => 'Edit User Preference'])

@section('content')
<div class="container">
    <h1>Edit User Preference</h1>
    <form action="{{ route($url.'.update', $preference->id) }}" method="POST">
        @csrf
        @method('PATCH')

        <div class="mb-3">
            <label>User</label>
            <select name="user_id" class="form-control" required>
                <option value="">Select User</option>
                @foreach($users as $user)
                    <option value="{{ $user->id }}" @if($user->id == $preference->user_id) selected @endif>{{ $user->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="mb-3">
            <label>Allergies</label>
            <textarea name="allergies" class="form-control">{{ $preference->allergies }}</textarea>
        </div>

        <div class="mb-3">
            <label>Ingredients to Avoid</label>
            <textarea name="ingredients_to_avoid" class="form-control">{{ $preference->ingredients_to_avoid }}</textarea>
        </div>

        <div class="mb-3">
            <label>Ethical Preferences</label>
            <textarea name="ethical_preferences" class="form-control">{{ $preference->ethical_preferences }}</textarea>
        </div>

        <div class="mb-3">
            <label>Skin Type</label>
            <input type="text" name="skin_type" class="form-control" value="{{ $preference->skin_type }}">
        </div>

        <div class="mb-3">
            <label>Hair Type</label>
            <input type="text" name="hair_type" class="form-control" value="{{ $preference->hair_type }}">
        </div>

        <div class="mb-3">
            <label>Hair Texture</label>
            <input type="text" name="hair_texture" class="form-control" value="{{ $preference->hair_texture }}">
        </div>

        <button type="submit" class="btn btn-success">Update</button>
        <a href="{{ route($url.'.index') }}" class="btn btn-secondary">Cancel</a>
    </form>
</div>
@endsection
