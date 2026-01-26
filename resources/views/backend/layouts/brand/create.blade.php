@extends('backend.app', ['title' => 'Cteate Brand'])

@section('content')

<!--app-content open-->
<div class="app-content main-content mt-0">
    <div class="side-app">

        <!-- CONTAINER -->
        <div class="main-container container-fluid">

            <div class="page-header">
                <div>
                    <h1 class="page-title">Brand</h1>
                </div>
                <div class="ms-auto pageheader-btn">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="javascript:void(0);">Brand</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Create</li>
                    </ol>
                </div>
            </div>

            <div class="row" id="user-profile">
                <div class="col-lg-12">

                    <div class="tab-content">
                        <div class="tab-pane active show" id="editProfile">
                            <div class="card">
                                <div class="card-body border-0">
                                    <form class="form form-horizontal" method="post" action="{{ route('admin.brand.store') }}" enctype="multipart/form-data">
                                        @csrf
                                        <div class="row mb-4">

                                            <div class="form-group">
                                                <label for="name" class="form-label">Name:</label>
                                                <input type="text" class="form-control @error('name') is-invalid @enderror" name="name" placeholder="Name" id="" value="{{ old('name') }}">
                                                @error('name')
                                                <span class="text-danger">{{ $message }}</span>
                                                @enderror
                                            </div>


                                            <div class="form-group">
                                                <label for="thumb" class="form-label">Thumb:</label>
                                                <input type="file" class="form-control @error('thumb') is-invalid @enderror" name="thumb" id="">
                                                @error('thumb')
                                                <span class="text-danger">{{ $message }}</span>
                                                @enderror
                                            </div>


                                            <div class="form-group">
                                                <label for="promo_code" class="form-label">Promo Code:</label>
                                                <input type="text" class="form-control @error('promo_code') is-invalid @enderror" name="promo_code" placeholder="Promo Code" id="" value="{{ old('promo_code') }}">
                                                @error('promo_code')
                                                <span class="text-danger">{{ $message }}</span>
                                                @enderror

                                            </div>


                                            <div class="form-group">
                                                <label for="redirect_url" class="form-label">Redirect URL:</label>
                                                <input type="url" class="form-control @error('redirect_url') is-invalid @enderror" name="redirect_url" placeholder="Redirect URL" id="" value="{{ old('redirect_url') }}">
                                                @error('redirect_url')
                                                <span class="text-danger">{{ $message }}</span>
                                                @enderror
                                            </div>



                                            <div class="form-group">
                                                <button class="submit btn btn-primary" type="submit">Submit</button>
                                            </div>

                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
<!-- CONTAINER CLOSED -->
@endsection
@push('scripts')

@endpush
