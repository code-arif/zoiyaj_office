@extends('backend.app', ['title' => 'Bookings'])

@section('title', 'Dashboard || Bookings')

@push('styles')
    <link href="{{ asset('default/datatable.css') }}" rel="stylesheet" />
@endpush

@section('content')
    <!-- app-content open -->
    <div class="app-content main-content mt-0">
        <div class="side-app">

            <!-- CONTAINER -->
            <div class="main-container container-fluid">

                <!-- PAGE-HEADER -->
                <div class="page-header">
                    <div>
                        <h1 class="page-title">Bookings</h1>
                    </div>
                    <div class="ms-auto pageheader-btn">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="javascript:void(0);">Bookings</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Index</li>
                        </ol>
                    </div>
                </div>
                <!-- PAGE-HEADER END -->

                <!-- ROW-4 -->
                <div class="row">
                    <div class="col-12 col-sm-12">
                        <div class="card product-sales-main">
                            <div class="card-header border-bottom">
                                <h3 class="card-title mb-0">Bookings List</h3>
                                
                            </div>
                            <div class="card-body">
                                <table class="table text-nowrap mb-0 table-bordered" id="datatable">
                                    <thead>
                                        <tr>
                                            <th class="bg-transparent border-bottom-0">ID</th>
                                            <th class="bg-transparent border-bottom-0">Date</th>
                                            <th class="bg-transparent border-bottom-0">Client</th>
                                            <th class="bg-transparent border-bottom-0">Professional</th>
                                            <th class="bg-transparent border-bottom-0">Status</th>
                                            <th class="bg-transparent border-bottom-0">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <!-- DataTable content will be injected here via AJAX -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div><!-- COL END -->
                </div>
                <!-- ROW-4 END -->

            </div>
        </div>
    </div>
    <!-- CONTAINER CLOSED -->
<div class="modal fade" id="bookingDetailsModal" tabindex="-1" aria-labelledby="bookingDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="bookingDetailsModalLabel">
                    <i class="fa fa-calendar-check me-2"></i>Booking Details
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row">
                    <!-- Left Column - Booking Info -->
                    <div class="col-md-6 mb-4">
                        <div class="card shadow-sm border-0 h-100">
                            <div class="card-header bg-light">
                                <h6 class="mb-0 fw-bold"><i class="fa fa-info-circle me-2"></i>Booking Information</h6>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="text-muted small">Booking ID</label>
                                    <span class="fw-bold mb-0" id="booking-id">#-</sp>
                                </div>
                                <div class="mb-3">
                                    <label class="text-muted small">Booking Date</label>
                                    <p class="mb-0" id="booking-date">-</p>
                                </div>
                                <div class="mb-3">
                                    <label class="text-muted small">Status</label>
                                    <p class="mb-0" id="booking-status">-</p>
                                </div>
                                <div class="mb-3">
                                    <label class="text-muted small">Points</label>
                                    <p class="mb-0" id="booking-points">-</p>
                                </div>
                                <div class="mb-0">
                                    <label class="text-muted small">Notes</label>
                                    <p class="mb-0" id="booking-notes">-</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column - Client & Professional -->
                    <div class="col-md-6 mb-4">
                        <!-- Client Info -->
                        <div class="card shadow-sm border-0 mb-3">
                            <div class="card-header bg-light">
                                <h6 class="mb-0 fw-bold"><i class="fa fa-user me-2"></i>Client Information</h6>
                            </div>
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="flex-shrink-0">
                                        <div class="avatar avatar-lg rounded-circle bg-primary text-white d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                                            <i class="fa fa-user fa-lg"></i>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <h6 class="mb-1" id="client-name">-</h6>
                                        <p class="text-muted mb-0 small" id="client-email">-</p>
                                        <p class="text-muted mb-0 small" id="client-phone">-</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Professional Info -->
                        <div class="card shadow-sm border-0">
                            <div class="card-header bg-light">
                                <h6 class="mb-0 fw-bold"><i class="fa fa-user-tie me-2"></i>Professional Information</h6>
                            </div>
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="flex-shrink-0">
                                        <div class="avatar avatar-lg rounded-circle bg-success text-white d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                                            <i class="fa fa-user-tie fa-lg"></i>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <h6 class="mb-1" id="professional-name">-</h6>
                                        <p class="text-muted mb-0 small" id="professional-email">-</p>
                                        <p class="text-muted mb-0 small" id="professional-phone">-</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Service Details -->
                <div class="row">
                    <div class="col-12">
                        <div class="card shadow-sm border-0">
                            <div class="card-header bg-light">
                                <h6 class="mb-0 fw-bold"><i class="fa fa-concierge-bell me-2"></i>Service Details</h6>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Service Name</th>
                                                <th>Scheduled Date</th>
                                                <th>Scheduled Time</th>
                                                <th>Price</th>
                                            </tr>
                                        </thead>
                                        <tbody id="service-details-tbody">
                                            <tr>
                                                <td colspan="4" class="text-center text-muted">No services found</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fa fa-times me-1"></i>Close
                </button>
            </div>
        </div>
    </div>
</div>

<style>
    #bookingDetailsModal .card {
        transition: transform 0.2s;
    }
    #bookingDetailsModal .avatar {
        font-size: 1.5rem;
    }
    #bookingDetailsModal .table th {
        font-weight: 600;
        font-size: 0.875rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    #bookingDetailsModal label.small {
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        font-weight: 600;
    }
</style>

@endsection

@push('scripts')
    <script>
        $(document).ready(function() {
            $.ajaxSetup({
                headers: {
                    "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
                }
            });

            // DataTables initialization
            if (!$.fn.DataTable.isDataTable('#datatable')) {
                let dTable = $('#datatable').DataTable({
                    order: [],
                    lengthMenu: [
                        [10, 25, 50, 100, -1],
                        [10, 25, 50, 100, "All"]
                    ],
                    processing: true,
                    responsive: true,
                    serverSide: true,
                    language: {
                        processing: `<div class="text-center"><img src="{{ asset('default/loader.gif') }}" alt="Loader" style="width: 50px;"></div>`
                    },
                    scroller: {
                        loadingIndicator: false
                    },
                    pagingType: "full_numbers",
                    dom: "<'row justify-content-between table-topbar'<'col-md-4 col-sm-3'l><'col-md-5 col-sm-5 px-0'f>>tipr",
                    ajax: {
                        url: "{{ route('admin.bookings.data') }}",
                        type: "GET",
                    },
                    columns: [{data: 'DT_RowIndex',name: 'DT_RowIndex',orderable: false,searchable: false},
                        {data: 'date',name: 'date',orderable: true,searchable: true},
                        {data: 'client',name: 'client',orderable: true,searchable: true},
                        {data: 'professional',name: 'professional',orderable: true,searchable: true},                         
                        {data: 'status',name: 'status',orderable: true,searchable: true},
                        {data: 'action',name: 'action',orderable: false,searchable: false,className: 'dt-center text-center'},
                    ],
                });
            }

            // Show status change confirmation
            function showStatusChangeAlert(id) {
                event.preventDefault();
                Swal.fire({
                    title: 'Are you sure?',
                    text: 'You want to update the status?',
                    icon: 'info',
                    showCancelButton: true,
                    confirmButtonText: 'Yes',
                    cancelButtonText: 'No',
                }).then((result) => {
                    if (result.isConfirmed) {
                        statusChange(id);
                    }
                });
            }

            // Status change action
            function statusChange(id) {
                NProgress.start();
                let url = "{{ route('admin.product.status', ':id') }}";
                $.ajax({
                    type: "POST",
                    url: url.replace(':id', id),
                    success: function(resp) {
                        NProgress.done();
                        toastr.success(resp.message);
                        $('#datatable').DataTable().ajax.reload();
                    },
                    error: function(error) {
                        NProgress.done();
                        toastr.error(error.responseJSON.message);
                    }
                });
            }
        });

        // delete Confirm
        function showDeleteConfirm(id) {
            event.preventDefault();
            Swal.fire({
                title: 'Are you sure you want to delete this record?',
                text: 'If you delete this, it will be gone forever.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, delete it!',
            }).then((result) => {
                if (result.isConfirmed) {
                    deleteItem(id);
                }
            });
        }

        // Delete Button
        function deleteItem(id) {
            NProgress.start();
            let url = "{{ route('admin.book.destroy', ':id') }}";
            let csrfToken = '{{ csrf_token() }}';
            $.ajax({
                type: "GET",
                url: url.replace(':id', id),
                headers: {
                    'X-CSRF-TOKEN': csrfToken
                },
                success: function(resp) {
                    NProgress.done();
                    toastr.success(resp.message);
                    $('#datatable').DataTable().ajax.reload();
                },
                error: function(error) {
                    NProgress.done();
                    toastr.error(error.message);
                }
            });
        }


        //edit
        function goToEdit(id) {
            let url = "{{ route('admin.book.edit', ':id') }}";
            window.location.href = url.replace(':id', id);
        }


        function showDetails(id) {
            let url = "{{ route('admin.book.show', ':id') }}";
            $.ajax({
                type: "GET",
                url: url.replace(':id', id),
                success: function(resp) {
                    console.log(resp.data);
                    let booking = resp.data;
                    
                    // Get status badge
                    let statusBadge = '';
                    switch(booking.status) {
                        case 'pending':
                            statusBadge = '<span class="badge bg-warning">Pending</span>';
                            break;
                        case 'confirmed':
                            statusBadge = '<span class="badge bg-info">Confirmed</span>';
                            break;
                        case 'completed':
                            statusBadge = '<span class="badge bg-success">Completed</span>';
                            break;
                        case 'cancelled':
                            statusBadge = '<span class="badge bg-danger">Cancelled</span>';
                            break;
                        default:
                            statusBadge = '<span class="badge bg-secondary">' + booking.status + '</span>';
                    }
                    
                    // Populate Booking Information
                    $('#booking-id').text('#' + booking.id);
                    $('#booking-date').text(booking.date || 'N/A');
                    $('#booking-status').html(statusBadge);
                    $('#booking-points').text(booking.points ? booking.points + ' points' : 'N/A');
                    $('#booking-notes').text(booking.notes || 'No notes');
                    
                    // Populate Client Information
                    $('#client-name').text((booking.user.first_name || '') + ' ' + (booking.user.last_name || ''));
                    $('#client-email').html('<i class="fa fa-envelope me-1"></i>' + (booking.user.email || 'N/A'));
                    $('#client-phone').html('<i class="fa fa-phone me-1"></i>' + (booking.user.phone_number || 'N/A'));
                    
                    // Populate Professional Information
                    $('#professional-name').text((booking.owner.first_name || '') + ' ' + (booking.owner.last_name || ''));
                    $('#professional-email').html('<i class="fa fa-envelope me-1"></i>' + (booking.owner.email || 'N/A'));
                    $('#professional-phone').html('<i class="fa fa-phone me-1"></i>' + (booking.owner.phone_number || 'N/A'));
                    
                    // Populate Service Details
                    let serviceRows = '';
                    if(booking.service_bookings ) {
                        booking.service_bookings.forEach(function(service) {
                            serviceRows += `
                                <tr>
                                    <td><strong>${service.service.name || 'N/A'}</strong><br><small class="text-muted">${service.service.description || ''}</small></td>
                                    <td>${service.scheduled_date || 'N/A'}</td>
                                    <td>${service.scheduled_time || 'N/A'}</td>
                                    <td><strong>$${service.service.price || '0.00'}</strong></td>
                                </tr>
                            `;
                        });
                    } else {
                        serviceRows = '<tr><td colspan="4" class="text-center text-muted">No services found</td></tr>';
                    }
                    $('#service-details-tbody').html(serviceRows);
                    
                    // Show modal
                    $('#bookingDetailsModal').modal('show');

                },
                error: function(error) {
                    toastr.error('Failed to fetch booking details.');
                }
            });
        }




    </script>
@endpush
