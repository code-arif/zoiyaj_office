@extends('backend.app', ['title' => 'Manage Preferences'])

@section('content')
<div class="app-content main-content mt-0">
    <div class="side-app">
        <div class="main-container container-fluid">

            <div class="page-header">
                <h1 class="page-title">Manage Preferences</h1>
            </div>

            <div class="row">
                <div class="col-lg-12">

                    <div class="card">
                        <div class="card-body border-0">

                            {{-- TYPE SELECT --}}
                            <form method="GET" class="mb-4">
                                <div class="row">
                                    <div class="col-md-4">
                                        <label><strong>Select Type</strong></label>
                                        <select name="type" onchange="this.form.submit()"
                                            class="form-control">
                                            @foreach($types as $t)
                                                <option value="{{ $t }}" {{ $type == $t ? 'selected' : '' }}>
                                                    {{ ucfirst($t) }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </form>

                            {{-- ADD FORM --}}
                            <form method="POST" action="{{ route('admin.preferences.store') }}">
                                @csrf

                                <input type="hidden" name="type" value="{{ $type }}">

                                <div class="row mb-4">
                                    <div class="col-md-6">
                                        <label><strong>Name</strong></label>
                                        <input type="text" name="name"
                                            class="form-control"
                                            placeholder="Enter {{ ucfirst($type) }} name"
                                            required>
                                    </div>

                                    <div class="col-md-2 mt-4">
                                        <button class="btn btn-primary mt-2">
                                            Add
                                        </button>
                                    </div>
                                </div>
                            </form>

                            {{-- LIST TABLE --}}
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th width="60">#</th>
                                        <th>Name</th>
                                        <th width="180">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($preferences as $key => $item)
                                        <tr>
                                            <td>{{ $key + 1 }}</td>
                                            <td>{{ $item->name }}</td>
                                            <td>
                                                <a href="{{ route('admin.preferences.edit',$item->id) }}"
                                                   class="btn btn-sm btn-info">
                                                    Edit
                                                </a>

                                                <form method="POST"
                                                      action="{{ route('admin.preferences.destroy',$item->id) }}"
                                                      class="d-inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button class="btn btn-sm btn-danger"
                                                            onclick="return confirm('Are you sure?')">
                                                        Delete
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="3" class="text-center text-muted">
                                                No data found for {{ ucfirst($type) }}
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>

                        </div>
                    </div>

                </div>
            </div>

        </div>
    </div>
</div>
@endsection
