@extends('backend.app', ['title' => 'Edit Preference'])

@section('content')
<div class="app-content main-content mt-0">
    <div class="side-app">
        <div class="main-container container-fluid">

            <div class="page-header">
                <h1 class="page-title">Edit Preference</h1>
            </div>

            <div class="row">
                <div class="col-lg-12">

                    <div class="card">
                        <div class="card-body border-0">

                            {{-- ERROR MESSAGE --}}
                            @if ($errors->any())
                                <div class="alert alert-danger">
                                    <ul class="mb-0">
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            <form method="POST"
                                action="{{ route('admin.preferences.update', $preference->id) }}">
                                @csrf

                                {{-- TYPE (READ ONLY) --}}
                                <div class="form-group mb-3">
                                    <label><strong>Type</strong></label>
                                    <input type="text"
                                           class="form-control"
                                           value="{{ ucfirst($preference->type) }}"
                                           readonly>
                                </div>

                                {{-- NAME --}}
                                <div class="form-group mb-3">
                                    <label><strong>Name</strong></label>
                                    <input type="text"
                                           name="name"
                                           value="{{ old('name', $preference->name) }}"
                                           class="form-control"
                                           required>
                                </div>

                                {{-- BUTTON --}}
                                <div class="mt-4">
                                    <button type="submit" class="btn btn-primary">
                                        Update
                                    </button>

                                    <a href="{{ route('admin.preferences.index', ['type' => $preference->type]) }}"
                                       class="btn btn-secondary">
                                        Back
                                    </a>
                                </div>

                            </form>

                        </div>
                    </div>

                </div>
            </div>

        </div>
    </div>
</div>
@endsection
