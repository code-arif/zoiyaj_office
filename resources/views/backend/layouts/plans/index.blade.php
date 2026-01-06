@extends('backend.app', ['title' => 'Subscription Plans'])

@section('title', 'Subscription Plans')

@section('content')
<div class="app-content main-content mt-0">
    <div class="side-app">
        <div class="main-container container-fluid">

            <!-- Page Header -->
            <div class="page-header mb-6">
                <div>
                    <h1 class="page-title text-2xl font-bold text-gray-800">Subscription Plans</h1>
                    <small class="text-muted">Manage your pricing plans</small>
                </div>
            </div>

            <!-- Flash Messages -->
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
                    <i class="fe fe-check-circle me-2"></i> {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
                    <i class="fe fe-alert-triangle me-2"></i> {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <!-- Plans Grid -->
            <div class="row row-cards">

                <!-- Add New Plan Card (if total plans < 3) -->
                @if($plans->count() < 3)
                    <div class="col-lg-4 col-md-6 mb-4">
                        <div class="card border-0 shadow-sm h-100 plan-card hover:-translate-y-1">
                            <div class="card-header border-0 text-white text-center"
                                style="background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);">
                                <div class="py-4">
                                    <h3 class="h4 mb-1 font-bold">Add New Plan</h3>
                                    <small class="opacity-80">Plan #{{ $plans->count() + 1 }}</small>
                                </div>
                            </div>
                            <div class="card-body p-4">
                                <form action="{{ route('admin.subscriptions-plans.store') }}" method="POST">
                                    @csrf
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Plan Name</label>
                                        <input type="text" name="name" class="form-control form-control-lg rounded-pill" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Price (USD)</label>
                                        <div class="input-group">
                                            <span class="input-group-text">$</span>
                                            <input type="number" step="0.01" name="price"
                                                   class="form-control form-control-lg rounded-end-pill" required>
                                        </div>
                                    </div>
                                    <div class="mb-4">
                                        <label class="form-label fw-semibold">Billing Interval</label>
                                        <select name="interval" class="form-select form-select-lg rounded-pill" required>
                                            <option value="day">Daily</option>
                                            <option value="week">Weekly</option>
                                            <option value="month">Monthly</option>
                                            <option value="year">Yearly</option>
                                        </select>
                                    </div>

                                    <!-- Optional initial features input -->
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Initial Features (optional)</label>
                                        <div id="new-features-container">
                                            <div class="d-flex gap-2 mb-2">
                                                <input type="text" name="features[0][text]" class="form-control" placeholder="Feature name">

                                            </div>
                                        </div>
                                        <button type="button" class="btn btn-outline-primary btn-sm mt-2" onclick="addNewFeature()">+ Add Feature</button>
                                    </div>

                                    <button type="submit" class="btn btn-success btn-lg w-100 rounded-pill">
                                        <i class="fe fe-plus me-2"></i> Add Plan
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                @endif

                <!-- Existing Plans Update Card -->
                @foreach($plans as $plan)
                    <div class="col-lg-4 col-md-6 mb-4">
                        <div class="card border-0 shadow-sm h-100 plan-card hover:-translate-y-1">
                            <div class="card-header border-0 text-white text-center position-relative overflow-hidden"
                                 style="background: linear-gradient(135deg, {{ $plan->interval == 'month' ? '#6366f1' : ($plan->interval == 'year' ? '#10b981' : '#f59e0b') }} 0%, {{ $plan->interval == 'month' ? '#4f46e5' : ($plan->interval == 'year' ? '#059669' : '#d97706') }} 100%);">
                                <div class="py-4">
                                    <h3 class="h4 mb-1 font-bold">{{ $plan->name }}</h3>
                                    <div class="d-flex align-items-center justify-content-center gap-1 text-sm opacity-90">
                                        <i class="fe fe-calendar"></i>
                                        <span>{{ ucfirst($plan->interval) }}ly</span>
                                    </div>
                                </div>
                                <div class="position-absolute top-0 end-0 p-3 opacity-20">
                                    <i class="fe fe-package" style="font-size: 3rem;"></i>
                                </div>
                            </div>

                            <div class="text-center py-4 bg-gray-50 border-bottom">
                                <sup class="text-muted fs-3">$</sup>
                                <span class="display-5 fw-bold text-dark">{{ number_format($plan->price, 2) }}</span>
                                <sub class="text-muted">/ {{ $plan->interval }}</sub>
                            </div>

                            <div class="card-body p-4">
                                <form action="{{ route('admin.subscriptions-plans.update', $plan->id) }}" method="POST">
                                    @csrf
                                    @method('PUT')

                                    <div class="mb-3">
                                        <label class="form-label fw-semibold text-gray-700">Plan Name</label>
                                        <input type="text" name="name" class="form-control form-control-lg rounded-pill"
                                               value="{{ $plan->name }}" required>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label fw-semibold text-gray-700">Price (USD)</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-white border-end-0">$</span>
                                            <input type="number" step="0.01" name="price"
                                                   class="form-control form-control-lg rounded-end-pill border-start-0"
                                                   value="{{ $plan->price }}" required>
                                        </div>
                                    </div>

                                    <div class="mb-4">
                                        <label class="form-label fw-semibold text-gray-700">Billing Interval</label>
                                        <select name="interval" class="form-select form-select-lg rounded-pill" required>
                                            <option value="day" {{ $plan->interval == 'day' ? 'selected' : '' }}>Daily</option>
                                            <option value="week" {{ $plan->interval == 'week' ? 'selected' : '' }}>Weekly</option>
                                            <option value="month" {{ $plan->interval == 'month' ? 'selected' : '' }}>Monthly</option>
                                            <option value="year" {{ $plan->interval == 'year' ? 'selected' : '' }}>Yearly</option>
                                        </select>
                                    </div>
                                    <hr>

                                    <!-- Features Management -->
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold text-gray-700">Plan Features</label>
                                        <div id="features-container-{{ $plan->id }}">
                                            @foreach($plan->features as $index => $feature)
                                                <div class="d-flex gap-2 mb-2">
                                                    <input type="text" name="features[{{ $index }}][text]"
                                                           class="form-control" value="{{ $feature->feature }}" required>

                                                    {{-- <select name="features[{{ $index }}][included]" class="form-select" style="width: 130px;">
                                                        <option value="1" {{ $feature->is_included ? 'selected' : '' }}>Included ✓</option>
                                                        <option value="0" {{ !$feature->is_included ? 'selected' : '' }}>Not Included ❌</option>
                                                    </select> --}}
                                                </div>
                                            @endforeach
                                        </div>
                                        <button type="button" class="btn btn-outline-primary btn-sm mt-2"
                                            onclick="addFeature({{ $plan->id }})">+ Add Feature</button>
                                    </div>

                                    <button type="submit" class="btn btn-primary btn-lg w-100 rounded-pill">
                                        <i class="fe fe-save me-2"></i> Update Plan
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                @endforeach

            </div>
        </div>
    </div>
</div>

<style>
    .plan-card { transition: all 0.3s ease; border-radius: 1.5rem !important; overflow: hidden; }
    .plan-card .card-header { border-radius: 1.5rem 1.5rem 0 0 !important; }
    .rounded-pill { border-radius: 50rem !important; }
    .hover\:-translate-y-1:hover { transform: translateY(-4px); }
    .transition-all { transition: all 0.3s ease; }
</style>

<script>
function addFeature(planId) {
    let container = document.getElementById('features-container-' + planId);
    let count = container.children.length;

    container.insertAdjacentHTML('beforeend', `
        <div class="d-flex gap-2 mb-2">
            <input type="text" name="features[${count}][text]" class="form-control" placeholder="Feature name" required>

        </div>
    `);
}

function addNewFeature() {
    let container = document.getElementById('new-features-container');
    let count = container.children.length;

    container.insertAdjacentHTML('beforeend', `
        <div class="d-flex gap-2 mb-2">
            <input type="text" name="features[${count}][text]" class="form-control" placeholder="Feature name">

        </div>
    `);
}
</script>

@endsection
