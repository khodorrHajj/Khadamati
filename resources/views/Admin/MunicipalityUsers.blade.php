@extends('layouts.admin')

@section('title', 'Municipality Users')
@section('page-title', 'Manage Municipality Users')

@section('content')
    {{-- Single Unified Card --}}
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="card-title mb-0"><i class="fas fa-users-cog mr-1"></i> Municipality Users</h3>
            <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#createUserModal">
                <i class="fas fa-plus"></i> Add Municipality User
            </button>
        </div>

        <div class="card-body p-0">
            <table id="municipalityUsersTable" class="table table-bordered table-striped mb-0">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Office / Municipality</th>
                        <th>Status</th>
                        <th style="width: 120px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($users as $user)
                        <tr>
                            <td>
                                <div class="font-weight-bold">{{ $user->name }}</div>
                                @if ($user->job_title)
                                    <div class="text-muted small">{{ $user->job_title }}</div>
                                @endif
                                @if ($user->phone)
                                    <div class="text-muted small"><i class="fas fa-phone fa-xs mr-1"></i>{{ $user->phone }}</div>
                                @endif
                            </td>
                            <td>{{ $user->email }}</td>
                            <td>
                                @if ($user->governmentOffice)
                                    <div>{{ $user->governmentOffice->name }}</div>
                                    @if ($user->governmentOffice->municipality)
                                        <div class="text-muted small">{{ $user->governmentOffice->municipality->name }}</div>
                                    @endif
                                @else
                                    <span class="text-muted">No Office Assigned</span>
                                @endif
                            </td>
                            <td>
                                @if ($user->status === 'active')
                                    <span class="badge badge-success"><i class="fas fa-check-circle mr-1"></i>Active</span>
                                @else
                                    <span class="badge badge-secondary"><i class="fas fa-pause-circle mr-1"></i>Inactive</span>
                                @endif
                            </td>
                            <td>
                                @if (Route::has('admin.municipality.users.toggle-status'))
                                    <button type="button" class="btn btn-outline-{{ $user->status === 'active' ? 'secondary' : 'success' }} btn-sm btn-toggle-user"
                                        data-url="{{ route('admin.municipality.users.toggle-status', $user) }}"
                                        data-name="{{ $user->name }}"
                                        data-action="{{ $user->status === 'active' ? 'deactivate' : 'activate' }}"
                                        title="{{ $user->status === 'active' ? 'Deactivate' : 'Activate' }} user">
                                        <i class="fas fa-{{ $user->status === 'active' ? 'ban' : 'check' }}"></i>
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">
                                <i class="fas fa-users fa-2x mb-2 d-block text-muted"></i>
                                No municipality users found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Create User Modal --}}
    <div class="modal fade" id="createUserModal" tabindex="-1" role="dialog" aria-labelledby="createUserModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <form method="POST" action="{{ route('admin.municipality.users.store') }}">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" id="createUserModalLabel">
                            <i class="fas fa-user-plus mr-2"></i>Add Municipality User
                        </h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        {{-- Assignment --}}
                        <div class="form-group">
                            <label>Government Office <span class="text-danger">*</span></label>
                            <select name="government_office_id" class="custom-select @error('government_office_id') is-invalid @enderror">
                                <option value="">Select Government Office</option>
                                @foreach ($offices as $office)
                                    <option value="{{ $office->id }}" {{ old('government_office_id') == $office->id ? 'selected' : '' }}>
                                        {{ $office->municipality ? $office->municipality->name . ' - ' : '' }}{{ $office->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('government_office_id')
                                <span class="invalid-feedback d-block">{{ $message }}</span>
                            @enderror
                        </div>

                        <hr>

                        {{-- Personal Info --}}
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Full Name <span class="text-danger">*</span></label>
                                    <input type="text" name="name" value="{{ old('name') }}" class="form-control @error('name') is-invalid @enderror" placeholder="Enter full name">
                                    @error('name')
                                        <span class="invalid-feedback d-block">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Email <span class="text-danger">*</span></label>
                                    <input type="email" name="email" value="{{ old('email') }}" class="form-control @error('email') is-invalid @enderror" placeholder="Enter email address">
                                    @error('email')
                                        <span class="invalid-feedback d-block">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Phone Number</label>
                                    <input type="text" name="phone" value="{{ old('phone') }}" class="form-control @error('phone') is-invalid @enderror" placeholder="Enter phone number">
                                    @error('phone')
                                        <span class="invalid-feedback d-block">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Position</label>
                                    <select name="job_title" class="custom-select @error('job_title') is-invalid @enderror">
                                        <option value="">Select Position</option>
                                        @foreach ($positions as $position)
                                            <option value="{{ $position }}" {{ old('job_title') === $position ? 'selected' : '' }}>{{ $position }}</option>
                                        @endforeach
                                    </select>
                                    @error('job_title')
                                        <span class="invalid-feedback d-block">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <hr>

                        {{-- Security --}}
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Password <span class="text-danger">*</span></label>
                                    <input type="password" name="password" class="form-control @error('password') is-invalid @enderror" placeholder="Create a password">
                                    @error('password')
                                        <span class="invalid-feedback d-block">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Confirm Password <span class="text-danger">*</span></label>
                                    <input type="password" name="password_confirmation" class="form-control" placeholder="Confirm password">
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Status</label>
                            <select name="status" class="custom-select @error('status') is-invalid @enderror">
                                <option value="active" {{ old('status', 'active') === 'active' ? 'selected' : '' }}>Active</option>
                                <option value="inactive" {{ old('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                            </select>
                            @error('status')
                                <span class="invalid-feedback d-block">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                    <div class="modal-footer justify-content-between">
                        <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-user-plus mr-1"></i> Create User
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/adminlte/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/adminlte/plugins/datatables-responsive/css/responsive.bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/adminlte/plugins/sweetalert2/sweetalert2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/adminlte/plugins/toastr/toastr.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/adminlte/plugins/select2/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/adminlte/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css') }}">
@endpush

@push('scripts')
    <script src="{{ asset('assets/adminlte/plugins/datatables/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('assets/adminlte/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('assets/adminlte/plugins/datatables-responsive/js/dataTables.responsive.min.js') }}"></script>
    <script src="{{ asset('assets/adminlte/plugins/datatables-responsive/js/responsive.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('assets/adminlte/plugins/sweetalert2/sweetalert2.min.js') }}"></script>
    <script src="{{ asset('assets/adminlte/plugins/toastr/toastr.min.js') }}"></script>
    <script src="{{ asset('assets/adminlte/plugins/select2/js/select2.full.min.js') }}"></script>

    <script>
        // Initialize DataTable
        $('#municipalityUsersTable').DataTable({
            responsive: true,
            autoWidth: false,
            lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "All"]],
            pageLength: 10,
            language: {
                search: '<i class="fas fa-search"></i>',
                searchPlaceholder: 'Search municipality users...',
                lengthMenu: 'Show _MENU_ entries',
                info: 'Showing _START_ to _END_ of _TOTAL_ users',
                paginate: {
                    previous: '<i class="fas fa-chevron-left"></i>',
                    next: '<i class="fas fa-chevron-right"></i>'
                }
            },
            columnDefs: [
                { orderable: false, targets: -1 }
            ]
        });

        // Initialize Select2 on modal dropdowns
        $('#createUserModal select').select2({
            theme: 'bootstrap4',
            dropdownParent: $('#createUserModal'),
            width: '100%'
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

        // Re-open modal if there were validation errors on create
        @if ($errors->any() && old('_token'))
            $(function () {
                $('#createUserModal').modal('show');
            });
        @endif

        // SweetAlert2 toggle status confirmation
        $(document).on('click', '.btn-toggle-user', function() {
            var url = $(this).data('url');
            var name = $(this).data('name');
            var action = $(this).data('action');

            var isActivate = action === 'activate';
            var title = isActivate ? 'Activate User?' : 'Deactivate User?';
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
    </script>
@endpush
