@extends('backend.app', ['title' => 'Books'])

@section('title', 'Dashboard || Books')

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
                        <h1 class="page-title">Books</h1>
                    </div>
                    <div class="ms-auto pageheader-btn">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="javascript:void(0);">Books</a></li>
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
                                <h3 class="card-title mb-0">Product Model List</h3>
                                <div class="card-options ms-auto">
                                    <a href="{{ route('admin.book.create') }}" class="btn btn-primary btn-sm">Add
                                        Book </a>

                                </div>
                            </div>
                            <div class="card-body">
                                <table class="table text-nowrap mb-0 table-bordered" id="datatable">
                                    <thead>
                                        <tr>
                                            <th class="bg-transparent border-bottom-0">ID</th>
                                            <th class="bg-transparent border-bottom-0">Title</th>
                                            <th class="bg-transparent border-bottom-0">Author Name</th>
                                            <th class="bg-transparent border-bottom-0">Category List</th>
                                            <th class="bg-transparent border-bottom-0">ISBN</th>
                                            <th class="bg-transparent border-bottom-0">Cover Image</th>
                                            <th class="bg-transparent border-bottom-0">Ebook File</th>

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
                        url: "{{ route('admin.book.index') }}",
                        type: "GET",
                    },
                    columns: [{
                            data: 'DT_RowIndex',
                            name: 'DT_RowIndex',
                            orderable: false,
                            searchable: false
                        },
                        {
                            data: 'title',
                            name: 'title',
                            orderable: true,
                            searchable: true
                        },

                        {
                            data: 'author',
                            name: 'author',
                            orderable: true,
                            searchable: true
                        },
                        {
                            data: 'category',
                            name: 'category',
                            orderable: true,
                            searchable: true
                        },

                         {
                            data: 'isbn',
                            name: 'isbn',
                            orderable: true,
                            searchable: true
                        },

                        {
                            data: 'cover_image',
                            name: 'cover_image',
                            orderable: true,
                            searchable: true
                        },

                        {
                            data: 'ebook',
                            name: 'ebook',
                            orderable: true,
                            searchable: true
                        },


                        {
                            data: 'action',
                            name: 'action',
                            orderable: false,
                            searchable: false,
                            className: 'dt-center text-center'
                        },
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





    </script>
@endpush
