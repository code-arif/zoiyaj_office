@extends('backend.app', ['title' => 'Update Redeem Tier'])
@section('title', 'Redeem Tier')

@section('content')

    <!--app-content open-->
    <div class="app-content main-content mt-0">
        <div class="side-app">

            <!-- CONTAINER -->
            <div class="main-container container-fluid">

                <div class="page-header">
                    <div>
                        <h1 class="page-title">Redeem Tier</h1>
                    </div>
                    <div class="ms-auto pageheader-btn">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="javascript:void(0);">Redeem Tier</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Update</li>
                        </ol>
                    </div>
                </div>

                <div class="row" id="user-profile">
                    <div class="col-lg-12">

                        <div class="tab-content">
                            <div class="tab-pane active show" id="editProfile">
                                <div class="card">
                                    <div class="card-body border-0">
                                        <form class="form form-horizontal" method="post"
                                            action="{{ route('admin.redeem_tiers.update', $redeemTier->id) }}"
                                            enctype="multipart/form-data">
                                            @csrf
                                            @method('POST')
                                            <div class="row mb-4">


                                                <div class="form-group">
                                                    <label for="tier_name" class="form-label">Tier Name:</label>
                                                    <input type="text"
                                                        class="form-control @error('tier_name') is-invalid @enderror"
                                                        name="tier_name" placeholder="Tier Name" id=""
                                                        value="{{ old('tier_name', $redeemTier->tier_name) }}">
                                                    @error('tier_name')
                                                        <span class="text-danger">{{ $message }}</span>
                                                    @enderror
                                                </div>


                                                <div class="form-group">
                                                    <label for="points_required" class="form-label">Points Required:</label>
                                                    <input type="number"
                                                        class="form-control @error('points_required') is-invalid @enderror"
                                                        name="points_required" placeholder="Points Required" id=""
                                                        value="{{ old('points_required', $redeemTier->points_required) }}">
                                                    @error('points_required')
                                                        <span class="text-danger">{{ $message }}</span>
                                                    @enderror
                                                </div>

                                                <div class="form-group">
                                                    <label for="discount_amount" class="form-label">Discount Amount:</label>
                                                    <input type="number" step="0.01"
                                                        class="form-control @error('discount_amount') is-invalid @enderror"
                                                        name="discount_amount" placeholder="Discount Amount" id=""
                                                        value="{{ old('discount_amount', $redeemTier->discount_amount) }}">
                                                    @error('discount_amount')
                                                        <span class="text-danger">{{ $message }}</span>
                                                    @enderror
                                                </div>


                                                <div class="form-group">
                                                    <label for="description" class="form-label">Description:</label>
                                                    <textarea
                                                        class="form-control @error('description') is-invalid @enderror"
                                                        name="description" placeholder="Description"
                                                        id="">{{ old('description', $redeemTier->description) }}</textarea>
                                                    @error('description')
                                                        <span class="text-danger">{{ $message }}</span>
                                                    @enderror
                                                </div>







                                                <div class="form-group">
                                                    <button class="submit btn btn-danger" type="submit">Update</button>
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
