@extends('backend.app', ['title' => 'User Preferences'])

@section('content')
<div class="app-content main-content mt-0">
    <div class="side-app">
        <div class="main-container container-fluid">

            <div class="page-header">
                <h1>User Preferences</h1>
                <a href="{{ route($url.'.create') }}" class="btn btn-primary">Add New</a>
            </div>

            <div class="card">
                <div class="card-body">
                    <table id="datatable" class="table table-bordered">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>User</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(function() {
    $('#datatable').DataTable({
        processing: true,
        serverSide: true,
        ajax: "{{ route($url.'.index') }}",
        columns: [
            { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable:false, searchable:false },
            { data: 'user', name: 'user' },
            { data: 'status', name: 'status', orderable:false, searchable:false },
            { data: 'action', name: 'action', orderable:false, searchable:false }
        ]
    });
});

function showDeleteConfirm(id) {
    event.preventDefault();
    Swal.fire({
        title: 'Are you sure?',
        text: "This will be deleted permanently!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, delete it!'
    }).then((result)=>{
        if(result.isConfirmed){
            let url = "{{ route($url.'.destroy', ':id') }}";
            $.ajax({
                type: "DELETE",
                url: url.replace(':id', id),
                headers: {'X-CSRF-TOKEN':'{{ csrf_token() }}'},
                success: function(resp){
                    $('#datatable').DataTable().ajax.reload();
                    toastr.success(resp.message);
                }
            });
        }
    });
}
</script>
@endpush
