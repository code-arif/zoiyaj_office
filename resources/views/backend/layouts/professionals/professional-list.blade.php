@extends('backend.app')

@section('title', 'Professional Management')

@section('content')
    <div class="app-content main-content mt-0">
        <div class="side-app" style="margin-bottom: 50px">
            <div class="main-container container-fluid">

                <!-- PAGE-HEADER -->
                <div class="page-header">
                    <div>
                        <h1 class="page-title">Professionals</h1>
                        <p class="text-muted mb-0">Manage all professional service providers and their portfolios</p>
                    </div>
                    <div class="ms-auto pageheader-btn">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Professionals</li>
                        </ol>
                    </div>
                </div>
                <!-- PAGE-HEADER END -->

                <!-- STATISTICS ROW -->
                <div class="row mb-4">
                    <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12">
                        <div class="card stats-card" style="border-left: 4px solid #007bff;">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-muted mb-1">Total Professionals</h6>
                                        <h3 class="mb-0">{{ $totalProfessionals }}</h3>
                                    </div>
                                    <div class="icon-service bg-primary-transparent text-primary p-3 rounded-3">
                                        <i class="fe fe-briefcase fs-20"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12">
                        <div class="card stats-card" style="border-left: 4px solid #28a745;"
                            onclick="filterByStatus('active')">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-muted mb-1">Active Professionals</h6>
                                        <h3 class="mb-0">{{ $activeProfessionals }}</h3>
                                    </div>
                                    <div class="icon-service bg-success-transparent text-success p-3 rounded-3">
                                        <i class="fe fe-check-circle fs-20"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12">
                        <div class="card stats-card" style="border-left: 4px solid #ffc107;"
                            onclick="filterByPremium('premium')">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-muted mb-1">Premium Members</h6>
                                        <h3 class="mb-0">{{ $premiumProfessionals }}</h3>
                                    </div>
                                    <div class="icon-service bg-warning-transparent text-warning p-3 rounded-3">
                                        <i class="fe fe-star fs-20"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12">
                        <div class="card stats-card" style="border-left: 4px solid #6c757d;"
                            onclick="filterByStatus('inactive')">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-muted mb-1">Inactive</h6>
                                        <h3 class="mb-0">{{ $inactiveProfessionals }}</h3>
                                    </div>
                                    <div class="icon-service bg-secondary-transparent text-secondary p-3 rounded-3">
                                        <i class="fe fe-user-x fs-20"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- FILTERS -->
                <div class="row">
                    <div class="col-12">
                        <div class="filter-card">
                            <div class="row align-items-end g-3">
                                <div class="col-md-3">
                                    <label class="form-label">Account Status</label>
                                    <select class="form-select" id="statusFilter">
                                        <option value="">All Status</option>
                                        <option value="active">Active</option>
                                        <option value="inactive">Inactive</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Membership</label>
                                    <select class="form-select" id="premiumFilter">
                                        <option value="">All Members</option>
                                        <option value="premium">Premium Only</option>
                                        <option value="regular">Regular Only</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Date From</label>
                                    <input type="date" class="form-control" id="dateFrom">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Date To</label>
                                    <input type="date" class="form-control" id="dateTo">
                                </div>
                            </div>
                            <div class="row mt-3">
                                <div class="col-12">
                                    <button type="button" class="btn btn-primary me-2" onclick="applyFilters()">
                                        <i class="fe fe-filter me-1"></i> Apply Filters
                                    </button>
                                    <button type="button" class="btn btn-secondary" onclick="resetFilters()">
                                        <i class="fe fe-refresh-cw me-1"></i> Reset
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- PROFESSIONAL LIST TABLE -->
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header border-bottom d-flex justify-content-between align-items-center">
                                <h3 class="card-title mb-0">Professional List</h3>
                                <div class="card-options d-flex align-items-center">
                                    <button class="btn btn-sm btn-outline-primary d-inline-flex align-items-center"
                                        onclick="exportProfessionals()">
                                        <i class="fe fe-download me-1"></i>
                                        Export
                                    </button>
                                </div>
                            </div>

                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-bordered text-nowrap border-bottom" id="datatable">
                                        <thead>
                                            <tr>
                                                <th class="bg-transparent border-bottom-0" style="width: 50px;">ID</th>
                                                <th class="bg-transparent border-bottom-0" style="width: 220px;">Name</th>
                                                <th class="bg-transparent border-bottom-0" style="width: 220px;">Business
                                                    Info</th>
                                                <th class="bg-transparent border-bottom-0" style="width: 200px;">
                                                    Specialties</th>
                                                <th class="bg-transparent border-bottom-0 text-center"
                                                    style="width: 120px;">Experience</th>
                                                <th class="bg-transparent border-bottom-0 text-center"
                                                    style="width: 150px;">Status</th>
                                                <th class="bg-transparent border-bottom-0 text-center"
                                                    style="width: 150px;">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody></tbody>
                                    </table>
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
        let dataTable;

        $(document).ready(function() {
            $.ajaxSetup({
                headers: {
                    "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
                    "X-Requested-With": "XMLHttpRequest"
                }
            });

            initializeDataTable();
        });

        function initializeDataTable() {
            if ($.fn.DataTable.isDataTable('#datatable')) {
                $('#datatable').DataTable().destroy();
            }

            dataTable = $('#datatable').DataTable({
                order: [
                    [0, 'desc']
                ],
                lengthMenu: [
                    [20, 50, 100, 200],
                    [20, 50, 100, 200]
                ],
                processing: true,
                responsive: true,
                serverSide: true,
                language: {
                    processing: `<div class="text-center">
                        <img src="{{ asset('default/loader.gif') }}" alt="Loader" style="width: 50px;">
                    </div>`
                },
                pagingType: "full_numbers",
                dom: "<'row justify-content-between table-topbar'<'col-md-4 col-sm-3'l><'col-md-5 col-sm-5 px-0'f>>tipr",
                ajax: {
                    url: "{{ route('professionals.get.data') }}",
                    type: "GET",
                    dataType: 'json',
                    data: function(d) {
                        d.status = $('#statusFilter').val();
                        d.premium_status = $('#premiumFilter').val();
                        d.date_from = $('#dateFrom').val();
                        d.date_to = $('#dateTo').val();
                    }
                },
                columns: [{
                        data: 'DT_RowIndex',
                        name: 'DT_RowIndex',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'name',
                        name: 'name',
                        orderable: true,
                        searchable: true
                    },
                    {
                        data: 'business_info',
                        name: 'business_info',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'specialties',
                        name: 'specialties',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'experience',
                        name: 'experience',
                        orderable: false,
                        searchable: false,
                        className: 'text-center'
                    },
                    {
                        data: 'account_status',
                        name: 'status',
                        orderable: true,
                        searchable: false,
                        className: 'text-center'
                    },
                    {
                        data: 'action',
                        name: 'action',
                        orderable: false,
                        searchable: false,
                        className: 'text-center'
                    }
                ]
            });
        }

        function applyFilters() {
            dataTable.ajax.reload();
        }

        function resetFilters() {
            $('#statusFilter, #premiumFilter, #dateFrom, #dateTo').val('');
            dataTable.ajax.reload();
        }

        function filterByStatus(status) {
            $('#statusFilter').val(status);
            $('#premiumFilter').val('');
            applyFilters();
        }

        function filterByPremium(type) {
            $('#premiumFilter').val(type);
            $('#statusFilter').val('');
            applyFilters();
        }

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
                        success: function(resp) {
                            NProgress.done();
                            if (resp.success) {
                                toastr.success(resp.message);
                                dataTable.ajax.reload();
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
                        success: function(resp) {
                            NProgress.done();
                            if (resp.success) {
                                toastr.success(resp.message);
                                dataTable.ajax.reload();
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

        function exportProfessionals() {
            toastr.info('Export functionality coming soon!');
        }
    </script>
@endpush

@push('styles')
    <link href="{{ asset('default/datatable.css') }}" rel="stylesheet" />
    <style>
        .filter-card {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            border: 1px solid #e9ecef;
        }

        .filter-card .form-label {
            font-weight: 600;
            font-size: 13px;
            color: #495057;
            margin-bottom: 8px;
        }

        .stats-card {
            transition: transform 0.2s, box-shadow 0.2s;
            cursor: pointer;
            border: 1px solid #e9ecef;
        }

        .stats-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .icon-service {
            width: 60px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .table th {
            font-weight: 600;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .table td {
            max-width: 0;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .text-truncate {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .btn-group-sm>.btn {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
        }

        .card-options .btn {
            font-size: 13px;
        }

        .badge {
            padding: 0.35em 0.65em;
            font-weight: 500;
        }
    </style>
@endpush
