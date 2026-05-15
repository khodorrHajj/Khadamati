@extends('layouts.admin')

@section('title', 'Citizens')
@section('page-title', 'Manage Citizens')

@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title mb-0"><i class="fas fa-users mr-1"></i> Citizen Accounts</h3>
        </div>

        <div class="card-body p-0">
            <table id="citizensTable" class="table table-bordered table-striped mb-0">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Status</th>
                        <th>Created Date</th>
                        <th style="width: 180px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($citizens as $citizen)
                        <tr>
                            <td>{{ $citizen->id }}</td>
                            <td class="font-weight-bold">{{ $citizen->name }}</td>
                            <td>{{ $citizen->email }}</td>
                            <td>{{ $citizen->phone ?: '-' }}</td>
                            <td>
                                @if ($citizen->status === 'active')
                                    <span class="badge badge-success"><i class="fas fa-check-circle mr-1"></i>Active</span>
                                @else
                                    <span class="badge badge-secondary"><i class="fas fa-pause-circle mr-1"></i>Inactive</span>
                                @endif
                            </td>
                            <td>{{ $citizen->created_at ? $citizen->created_at->format('M d, Y') : '-' }}</td>
                            <td>
                                <a href="{{ route('admin.citizens.show', $citizen) }}" class="btn btn-info btn-sm" title="View Details">
                                    <i class="fas fa-eye"></i>
                                </a>

                                @if ($citizen->status === 'active')
                                    <button type="button" class="btn btn-outline-secondary btn-sm btn-toggle-citizen"
                                        data-url="{{ route('admin.citizens.deactivate', $citizen) }}"
                                        data-name="{{ $citizen->name }}"
                                        data-action="deactivate"
                                        title="Deactivate">
                                        <i class="fas fa-ban"></i>
                                    </button>
                                @else
                                    <button type="button" class="btn btn-outline-success btn-sm btn-toggle-citizen"
                                        data-url="{{ route('admin.citizens.activate', $citizen) }}"
                                        data-name="{{ $citizen->name }}"
                                        data-action="activate"
                                        title="Activate">
                                        <i class="fas fa-check"></i>
                                    </button>
                                @endif

                                <button type="button" class="btn btn-outline-danger btn-sm btn-delete-citizen"
                                    data-url="{{ route('admin.citizens.destroy', $citizen) }}"
                                    data-name="{{ $citizen->name }}"
                                    title="Delete">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">
                                <i class="fas fa-users fa-2x mb-2 d-block text-muted"></i>
                                No citizens found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/adminlte/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/adminlte/plugins/datatables-responsive/css/responsive.bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/adminlte/plugins/sweetalert2/sweetalert2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/adminlte/plugins/toastr/toastr.min.css') }}">
@endpush

@push('scripts')
    <script src="{{ asset('assets/adminlte/plugins/datatables/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('assets/adminlte/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('assets/adminlte/plugins/datatables-responsive/js/dataTables.responsive.min.js') }}"></script>
    <script src="{{ asset('assets/adminlte/plugins/datatables-responsive/js/responsive.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('assets/adminlte/plugins/sweetalert2/sweetalert2.min.js') }}"></script>
    <script src="{{ asset('assets/adminlte/plugins/toastr/toastr.min.js') }}"></script>

    <script>
        // Initialize DataTable
        $('#citizensTable').DataTable({
            responsive: true,
            autoWidth: false,
            lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "All"]],
            pageLength: 10,
            language: {
                search: '<i class="fas fa-search"></i>',
                searchPlaceholder: 'Search citizens...',
                lengthMenu: 'Show _MENU_ entries',
                info: 'Showing _START_ to _END_ of _TOTAL_ citizens',
                paginate: {
                    previous: '<i class="fas fa-chevron-left"></i>',
                    next: '<i class="fas fa-chevron-right"></i>'
                }
            },
            columnDefs: [
                { orderable: false, targets: -1 }
            ]
        });

        // Toastr config
        toastr.options = {
            closeButton: true,
            progressBar: true,
            positionClass: 'toast-top-right',
            timeOut: 4000,
            extendedTimeOut: 2000,
        };

        @if(session('success'))
            toastr.success('{{ session('success') }}');
        @endif
        @if(session('error'))
            toastr.error('{{ session('error') }}');
        @endif

        // SweetAlert2 activate/deactivate confirmation
        $(document).on('click', '.btn-toggle-citizen', function() {
            var url = $(this).data('url');
            var name = $(this).data('name');
            var action = $(this).data('action');

            var isActivate = action === 'activate';
            var title = isActivate ? 'Activate Citizen?' : 'Deactivate Citizen?';
            var html = isActivate
                ? 'Are you sure you want to activate <strong>' + name + '</strong>?'
                : 'Are you sure you want to deactivate <strong>' + name + '</strong>?';
            var icon = isActivate ? 'question' : 'warning';
            var confirmColor = isActivate ? '#28a745' : '#6c757d';
            var confirmText = isActivate ? 'Yes, activate' : 'Yes, deactivate';

            Swal.fire({
                title: title,
                html: html,
                icon: icon,
                showCancelButton: true,
                confirmButtonColor: confirmColor,
                cancelButtonColor: '#6c757d',
                confirmButtonText: confirmText,
                cancelButtonText: 'Cancel',
            }).then((result) => {
                if (result.isConfirmed) {
                    var form = $('<form>', {
                        method: 'POST',
                        action: url
                    });
                    form.append($('<input>', {
                        type: 'hidden',
                        name: '_token',
                        value: '{{ csrf_token() }}'
                    }));
                    form.append($('<input>', {
                        type: 'hidden',
                        name: '_method',
                        value: 'PATCH'
                    }));
                    $('body').append(form);
                    form.submit();
                }
            });
        });

        // SweetAlert2 delete confirmation
        $(document).on('click', '.btn-delete-citizen', function() {
            var url = $(this).data('url');
            var name = $(this).data('name');

            Swal.fire({
                title: 'Delete Citizen?',
                html: 'Are you sure you want to delete <strong>' + name + '</strong>?<br>This action cannot be undone.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: '<i class="fas fa-trash"></i> Yes, delete it!',
                cancelButtonText: 'Cancel',
            }).then((result) => {
                if (result.isConfirmed) {
                    var form = $('<form>', {
                        method: 'POST',
                        action: url
                    });
                    form.append($('<input>', {
                        type: 'hidden',
                        name: '_token',
                        value: '{{ csrf_token() }}'
                    }));
                    form.append($('<input>', {
                        type: 'hidden',
                        name: '_method',
                        value: 'DELETE'
                    }));
                    $('body').append(form);
                    form.submit();
                }
            });
        });
    </script>
@endpush
