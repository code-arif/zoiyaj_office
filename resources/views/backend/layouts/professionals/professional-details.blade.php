@extends('backend.app')

@section('title', 'Professional Details')

@section('content')
    <div class="app-content main-content mt-0">
        <div class="side-app">
            <div class="main-container container-fluid">

                <!-- PAGE-HEADER -->
                <div class="page-header">
                    <div>
                        <h1 class="page-title">Professional Details</h1>
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('professionals.index') }}">Professionals</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Details</li>
                        </ol>
                    </div>
                    <div class="ms-auto pageheader-btn">
                        <a href="{{ route('professionals.index') }}" class="btn btn-light">
                            <i class="fe fe-arrow-left me-2"></i> Back to List
                        </a>
                    </div>
                </div>
                <!-- PAGE-HEADER END -->

                @php
                    $fullName = trim(($professional->first_name ?? '') . ' ' . ($professional->last_name ?? ''));
                    $displayName = $fullName ?: $professional->professional_name ?? 'N/A';
                    $avatar = $professional->avatar
                        ? asset($professional->avatar)
                        : 'https://ui-avatars.com/api/?name=' . urlencode($displayName) . '&background=random';
                @endphp

                <!-- PROFESSIONAL HEADER CARD -->
                <div class="row">
                    <div class="col-12">
                        <div class="card professional-header-card">
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col-md-8">
                                        <div class="d-flex align-items-start">
                                            <div class="professional-avatar-wrapper">
                                                <img src="{{ $avatar }}" alt="avatar" class="professional-avatar">
                                                @if ($professional->is_premium)
                                                    <div class="premium-badge">
                                                        <i class="fe fe-star"></i>
                                                    </div>
                                                @endif
                                            </div>
                                            <div class="ms-4 flex-grow-1">
                                                <h2 class="mb-2">{{ $displayName }}</h2>
                                                <div class="professional-meta mb-3">
                                                    @if ($professional->professional_name)
                                                        <div class="meta-item">
                                                            <i class="fe fe-briefcase text-warning"></i>
                                                            <span>{{ $professional->professional_name }}</span>
                                                        </div>
                                                    @endif
                                                    <div class="meta-item">
                                                        <i class="fe fe-mail text-warning"></i>
                                                        <span>{{ $professional->professional_email ?? $professional->email }}</span>
                                                    </div>
                                                    @if ($professional->professional_phone || $professional->phone_number)
                                                        <div class="meta-item">
                                                            <i class="fe fe-phone text-warning"></i>
                                                            <span>{{ $professional->professional_phone ?? $professional->phone_number }}</span>
                                                        </div>
                                                    @endif
                                                    @if ($professional->city || $professional->state)
                                                        <div class="meta-item">
                                                            <i class="fe fe-map-pin text-warning"></i>
                                                            <span>{{ trim(($professional->city ?? '') . ', ' . ($professional->state ?? '')) }}</span>
                                                        </div>
                                                    @endif>
                                                </div>
                                                <div class="professional-stats">
                                                    <div class="stat-item">
                                                        <div class="stat-value">{{ $professional->years_in_business ?? 0 }}
                                                        </div>
                                                        <div class="stat-label">Years in Business</div>
                                                    </div>
                                                    <div class="stat-item">
                                                        <div class="stat-value">{{ $professional->specialties->count() }}
                                                        </div>
                                                        <div class="stat-label">Specialties</div>
                                                    </div>
                                                    <div class="stat-item">
                                                        <div class="stat-value">{{ $professional->services->count() }}
                                                        </div>
                                                        <div class="stat-label">Services</div>
                                                    </div>
                                                    <div class="stat-item">
                                                        <div class="stat-value">{{ $professional->portfolios->count() }}
                                                        </div>
                                                        <div class="stat-label">Portfolio Items</div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="status-card">
                                            <div class="status-item">
                                                <span class="status-label">Account Status</span>
                                                <span
                                                    class="badge p-3 badge-lg bg-{{ $professional->status === 'active' ? 'success' : 'secondary' }}">
                                                    {{ ucfirst($professional->status) }}
                                                </span>
                                            </div>
                                            <div class="status-item">
                                                <span class="status-label">Member Since</span>
                                                <span
                                                    class="status-value">{{ $professional->created_at->format('M d, Y') }}</span>
                                            </div>
                                            <div class="status-item">
                                                <span class="status-label">Membership Type</span>
                                                <span
                                                    class="badge p-3 badge-lg bg-{{ $professional->is_premium ? 'warning' : 'info' }}">
                                                    {{ $professional->is_premium ? 'Premium' : 'Regular' }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- LEFT COLUMN -->
                    <div class="col-xl-8">

                        <!-- BIO SECTION -->
                        @if ($professional->bio)
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="card-title"><i class="fe fe-file-text me-2"></i> About</h4>
                                </div>
                                <div class="card-body">
                                    <p class="bio-text">{{ $professional->bio }}</p>
                                </div>
                            </div>
                        @endif

                        <!-- SPECIALTIES SECTION -->
                        <div class="card">
                            <div class="card-header">
                                <h4 class="card-title"><i class="fe fe-award me-2"></i> Specialties</h4>
                            </div>
                            <div class="card-body">
                                @if ($professional->specialties->count() > 0)
                                    <div class="specialty-grid">
                                        @foreach ($professional->specialties as $specialty)
                                            <div class="specialty-badge">
                                                <i class="fe fe-check-circle text-success me-2"></i>
                                                {{ $specialty->name }}
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <div class="text-center text-muted py-4">
                                        <i class="fe fe-alert-circle fs-3 mb-2"></i>
                                        <p class="mb-0">No specialties added yet</p>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <!-- BRANDS SECTION -->
                        <div class="card">
                            <div class="card-header">
                                <h4 class="card-title"><i class="fe fe-package me-2"></i> Brands We Work With</h4>
                            </div>
                            <div class="card-body">
                                @if ($professional->user_brands->count() > 0)
                                    <div class="brands-grid">
                                        @foreach ($professional->user_brands as $brand)
                                            <div class="brand-item">
                                                <div class="brand-icon">
                                                    <i class="fe fe-box"></i>
                                                </div>
                                                <div class="brand-name">{{ $brand->brand->name }}</div>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <div class="text-center text-muted py-4">
                                        <i class="fe fe-alert-circle fs-3 mb-2"></i>
                                        <p class="mb-0">No brands added yet</p>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <!-- SERVICES SECTION -->
                        <div class="card">
                            <div class="card-header">
                                <h4 class="card-title"><i class="fe fe-tool me-2"></i> Services Offered</h4>
                            </div>
                            <div class="card-body">
                                @if ($professional->services->count() > 0)
                                    <div class="table-responsive">
                                        <table class="table table-hover services-table">
                                            <thead>
                                                <tr>
                                                    <th>Service Name</th>
                                                    <th>Starting Price</th>
                                                    <th>Duration</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($professional->services as $service)
                                                    <tr>
                                                        <td>
                                                            <div class="service-name">
                                                                <i class="fe fe-chevron-right text-primary me-2"></i>
                                                                {{ $service->name }}
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <span
                                                                class="service-price">${{ number_format($service->starting_price, 2) }}</span>
                                                        </td>
                                                        <td>
                                                            <span
                                                                class="text-muted">{{ $service->duration ?? 'N/A' }}</span>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @else
                                    <div class="text-center text-muted py-4">
                                        <i class="fe fe-alert-circle fs-3 mb-2"></i>
                                        <p class="mb-0">No services added yet</p>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <!-- PORTFOLIO SECTION -->
                        <div class="card">
                            <div class="card-header">
                                <h4 class="card-title"><i class="fe fe-image me-2"></i> Portfolio</h4>
                            </div>
                            <div class="card-body">
                                @if ($professional->portfolios->count() > 0)
                                    <div class="portfolio-grid">
                                        @foreach ($professional->portfolios as $portfolio)
                                            <div class="portfolio-item">
                                                @if ($portfolio->type === 'image' && $portfolio->image)
                                                    <div class="portfolio-image">
                                                        <img src="{{ asset($portfolio->image) }}"
                                                            alt="{{ $portfolio->name ?? 'Portfolio' }}">
                                                        <div class="portfolio-overlay">
                                                            <i class="fe fe-maximize-2"></i>
                                                        </div>
                                                    </div>
                                                @elseif($portfolio->type === 'video' && $portfolio->video)
                                                    <div class="portfolio-video">
                                                        <video controls>
                                                            <source src="{{ asset($portfolio->video) }}"
                                                                type="video/mp4">
                                                        </video>
                                                        <div class="portfolio-type-badge">
                                                            <i class="fe fe-video"></i>
                                                        </div>
                                                    </div>
                                                @endif
                                                @if ($portfolio->name)
                                                    <div class="portfolio-name">{{ $portfolio->name }}</div>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <div class="text-center text-muted py-5">
                                        <i class="fe fe-image fs-1 mb-3"></i>
                                        <p class="mb-0">No portfolio items added yet</p>
                                    </div>
                                @endif
                            </div>
                        </div>

                    </div>

                    <!-- RIGHT COLUMN -->
                    <div class="col-xl-4">

                        <!-- CONTACT INFO CARD -->
                        <div class="card">
                            <div class="card-header">
                                <h4 class="card-title"><i class="fe fe-info me-2"></i> Contact Information</h4>
                            </div>
                            <div class="card-body">
                                <div class="info-list">
                                    <div class="info-item">
                                        <div class="info-label">Email</div>
                                        <div class="info-value">
                                            {{ $professional->professional_email ?? $professional->email }}</div>
                                    </div>
                                    @if ($professional->professional_phone || $professional->phone_number)
                                        <div class="info-item">
                                            <div class="info-label">Phone</div>
                                            <div class="info-value">
                                                {{ $professional->professional_phone ?? $professional->phone_number }}
                                            </div>
                                        </div>
                                    @endif
                                    @if ($professional->address)
                                        <div class="info-item">
                                            <div class="info-label">Address</div>
                                            <div class="info-value">{{ $professional->address }}</div>
                                        </div>
                                    @endif
                                    @if ($professional->city || $professional->state || $professional->postal_code)
                                        <div class="info-item">
                                            <div class="info-label">Location</div>
                                            <div class="info-value">
                                                {{ $professional->city ? $professional->city . ', ' : '' }}
                                                {{ $professional->state ? $professional->state . ' ' : '' }}
                                                {{ $professional->postal_code ?? '' }}
                                            </div>
                                        </div>
                                    @endif
                                    @if ($professional->country)
                                        <div class="info-item">
                                            <div class="info-label">Country</div>
                                            <div class="info-value">{{ $professional->country }}</div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- BUSINESS INFO CARD -->
                        <div class="card">
                            <div class="card-header">
                                <h4 class="card-title"><i class="fe fe-briefcase me-2"></i> Business Information</h4>
                            </div>
                            <div class="card-body">
                                <div class="info-list">
                                    <div class="info-item">
                                        <div class="info-label">Years in Business</div>
                                        <div class="info-value fw-bold text-primary">
                                            {{ $professional->years_in_business ?? 0 }} Years</div>
                                    </div>
                                    <div class="info-item">
                                        <div class="info-label">Promo Participation</div>
                                        <div class="info-value">
                                            <span
                                                class="badge bg-{{ $professional->is_promo_participation ? 'success' : 'secondary' }}">
                                                {{ $professional->is_promo_participation ? 'Yes' : 'No' }}
                                            </span>
                                        </div>
                                    </div>
                                    <div class="info-item">
                                        <div class="info-label">Sell Retail Products</div>
                                        <div class="info-value">
                                            <span
                                                class="badge bg-{{ $professional->is_sell_retail_products ? 'success' : 'secondary' }}">
                                                {{ $professional->is_sell_retail_products ? 'Yes' : 'No' }}
                                            </span>
                                        </div>
                                    </div>
                                    @if ($professional->logo_path)
                                        <div class="info-item">
                                            <div class="info-label">Business Logo</div>
                                            <div class="info-value">
                                                <img src="{{ asset($professional->logo_path) }}" alt="Logo"
                                                    style="max-height: 60px;">
                                            </div>
                                        </div>
                                    @endif
                                    @if ($professional->certificate_path)
                                        <div class="info-item">
                                            <div class="info-label">Certificate</div>
                                            <div class="info-value">
                                                <a href="{{ asset($professional->certificate_path) }}" target="_blank"
                                                    class="btn btn-sm btn-outline-primary">
                                                    <i class="fe fe-file me-1"></i> View Certificate
                                                </a>
                                            </div>
                                        </div>
                                    @endif>
                                </div>
                            </div>
                        </div>

                        <!-- ACCOUNT ACTIONS CARD -->
                        <div class="card">
                            <div class="card-header">
                                <h4 class="card-title"><i class="fe fe-settings me-2"></i> Account Actions</h4>
                            </div>
                            <div class="card-body">
                                <div class="row g-2">
                                    <div class="col-6">
                                        <button
                                            class="btn w-100 d-flex align-items-center justify-content-center gap-2
                btn-{{ $professional->status === 'active' ? 'warning' : 'success' }}"
                                            onclick="toggleStatus({{ $professional->id }}, '{{ $professional->status }}')">

                                            <i
                                                class="fe fe-{{ $professional->status === 'active' ? 'x-circle' : 'check-circle' }}"></i>
                                            <span>
                                                {{ $professional->status === 'active' ? 'Deactivate Account' : 'Activate Account' }}
                                            </span>
                                        </button>
                                    </div>

                                    <div class="col-6">
                                        <button
                                            class="btn w-100 d-flex align-items-center justify-content-center gap-2
                btn-{{ $professional->is_premium ? 'outline-warning' : 'warning' }}"
                                            onclick="togglePremium({{ $professional->id }}, {{ $professional->is_premium ? 'true' : 'false' }})">

                                            <i class="fe fe-star"></i>
                                            <span>
                                                {{ $professional->is_premium ? 'Remove Premium' : 'Make Premium' }}
                                            </span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        function toggleStatus(id, currentStatus) {
            const newStatus = currentStatus === 'active' ? 'inactive' : 'active';
            const action = newStatus === 'active' ? 'activate' : 'deactivate';

            Swal.fire({
                title: 'Are you sure?',
                text: `Do you want to ${action} this professional?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: currentStatus === 'active' ? '#dc3545' : '#28a745',
                cancelButtonColor: '#6c757d',
                confirmButtonText: `Yes, ${action}!`,
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    NProgress.start();
                    $.ajax({
                        type: "POST",
                        url: "{{ route('professionals.toggle.status', '') }}/" + id,
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                        success: function(resp) {
                            NProgress.done();
                            if (resp.success) {
                                toastr.success(resp.message);
                                setTimeout(() => location.reload(), 1500);
                            }
                        },
                        error: function(error) {
                            NProgress.done();
                            toastr.error('Failed to update status!');
                        }
                    });
                }
            });
        }

        function togglePremium(id, isPremium) {
            const action = isPremium ? 'remove premium status from' : 'make premium';

            Swal.fire({
                title: 'Are you sure?',
                text: `Do you want to ${action} this professional?`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#ffc107',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, update!',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    NProgress.start();
                    $.ajax({
                        type: "POST",
                        url: "{{ route('professionals.toggle.premium', '') }}/" + id,
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                        success: function(resp) {
                            NProgress.done();
                            if (resp.success) {
                                toastr.success(resp.message);
                                setTimeout(() => location.reload(), 1500);
                            }
                        },
                        error: function(error) {
                            NProgress.done();
                            toastr.error('Failed to update premium status!');
                        }
                    });
                }
            });
        }
    </script>
@endpush

@push('styles')
    <style>
        /* Professional Header Card */
        .professional-header-card {
            background: linear-gradient(135deg, #8fbd56 0%, #7c9c55 100%);
            /* border: 2px solid linear-gradient(135deg, #667eea 0%, #764ba2 100%); */
            color: white;
            margin-bottom: 25px;
        }

        .professional-avatar-wrapper {
            position: relative;
        }

        .professional-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            border: 5px solid rgba(255, 255, 255, 0.3);
            object-fit: cover;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
        }

        .premium-badge {
            position: absolute;
            bottom: 5px;
            right: 5px;
            background: #ffc107;
            color: white;
            width: 35px;
            height: 35px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 3px solid white;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
        }

        .professional-header-card h2 {
            color: white;
            font-weight: 700;
            font-size: 28px;
        }

        .professional-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 8px;
            color: rgba(255, 255, 255, 0.9);
            font-size: 14px;
        }

        .meta-item i {
            font-size: 16px;
        }

        .professional-stats {
            display: flex;
            gap: 30px;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.2);
        }

        .stat-item {
            text-align: center;
        }

        .stat-value {
            font-size: 24px;
            font-weight: 700;
            color: white;
        }

        .stat-label {
            font-size: 12px;
            color: rgba(255, 255, 255, 0.8);
            margin-top: 5px;
        }

        .status-card {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            padding: 20px;
        }

        .status-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .status-item:last-child {
            margin-bottom: 0;
        }

        .status-label {
            color: rgba(255, 255, 255, 0.8);
            font-size: 13px;
        }

        .status-value {
            color: white;
            font-weight: 600;
        }

        .badge-lg {
            padding: 8px 16px;
            font-size: 13px;
        }

        /* Card Styling */
        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            margin-bottom: 25px;
        }

        .card-header {
            background: #f8f9fa;
            border-bottom: 2px solid #e9ecef;
            padding: 20px 25px;
            border-radius: 12px 12px 0 0 !important;
        }

        .card-title {
            font-weight: 600;
            font-size: 18px;
            color: #2c3e50;
            margin: 0;
        }

        .card-body {
            padding: 25px;
        }

        /* Bio Text */
        .bio-text {
            font-size: 15px;
            line-height: 1.8;
            color: #555;
        }

        /* Specialty Grid */
        .specialty-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 12px;
        }

        .specialty-badge {
            background: #f8f9fa;
            padding: 12px 16px;
            border-radius: 8px;
            border: 1px dashed #28a745;
            font-weight: 500;
            transition: all 0.3s;
        }

        .specialty-badge:hover {
            background: #e9ecef;
            transform: translateX(5px);
        }

        /* Brands Grid */
        .brands-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 15px;
        }

        .brand-item {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            transition: all 0.3s;
            border: 2px solid transparent;
        }

        .brand-item:hover {
            border-color: #667eea;
            transform: translateY(-3px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.15);
        }

        .brand-icon {
            width: 50px;
            height: 50px;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 10px;
            color: #667eea;
            font-size: 24px;
        }

        .brand-name {
            font-weight: 600;
            font-size: 14px;
            color: #2c3e50;
        }

        /* Services Table */
        .services-table {
            margin-bottom: 0;
        }

        .services-table thead th {
            background: #f8f9fa;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 12px;
            letter-spacing: 0.5px;
            color: #6c757d;
            border-bottom: 2px solid #e9ecef;
        }

        .service-name {
            font-weight: 500;
            color: #2c3e50;
        }

        .service-price {
            font-weight: 700;
            color: #28a745;
            font-size: 16px;
        }

        /* Portfolio Grid */
        .portfolio-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
        }

        .portfolio-item {
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            transition: all 0.3s;
        }

        .portfolio-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
        }

        .portfolio-image {
            position: relative;
            overflow: hidden;
            padding-top: 75%;
        }

        .portfolio-image img {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .portfolio-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.3s;
            color: white;
            font-size: 30px;
        }

        .portfolio-image:hover .portfolio-overlay {
            opacity: 1;
        }

        .portfolio-video {
            position: relative;
        }

        .portfolio-video video {
            width: 100%;
            height: auto;
            display: block;
        }

        .portfolio-type-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            background: rgba(0, 0, 0, 0.7);
            color: white;
            padding: 8px 12px;
            border-radius: 6px;
        }

        .portfolio-name {
            padding: 12px;
            background: #f8f9fa;
            font-weight: 500;
            color: #2c3e50;
            text-align: center;
        }

        /* Info List */
        .info-list {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .info-item {
            padding-bottom: 15px;
            border-bottom: 1px solid #f0f0f0;
        }

        .info-item:last-child {
            padding-bottom: 0;
            border-bottom: none;
        }

        .info-label {
            font-size: 12px;
            text-transform: uppercase;
            color: #6c757d;
            font-weight: 600;
            letter-spacing: 0.5px;
            margin-bottom: 5px;
        }

        .info-value {
            font-size: 14px;
            color: #2c3e50;
            font-weight: 500;
        }

        /* Responsive */
        @media (max-width: 991px) {
            .professional-stats {
                flex-wrap: wrap;
                gap: 20px;
            }

            .portfolio-grid {
                grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            }
        }

        @media (max-width: 767px) {
            .professional-avatar {
                width: 90px;
                height: 90px;
            }

            .professional-header-card h2 {
                font-size: 22px;
            }

            .professional-meta {
                flex-direction: column;
                gap: 10px;
            }

            .specialty-grid {
                grid-template-columns: 1fr;
            }

            .brands-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
    </style>
@endpush
