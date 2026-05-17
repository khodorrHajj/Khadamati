@extends('layouts.admin')

@section('title', 'Service Categories')
@section('page-title', 'Service Categories Management')

@section('content')
    {{-- Stats Row --}}
    <div class="row">
        <div class="col-lg-4 col-6">
            <div class="small-box bg-info">
                <div class="inner">
                    <h3>{{ $stats['total'] }}</h3>
                    <p>Total Categories</p>
                </div>
                <div class="icon"><i class="fas fa-tags"></i></div>
            </div>
        </div>
        <div class="col-lg-4 col-6">
            <div class="small-box bg-success">
                <div class="inner">
                    <h3>{{ $stats['offices_with_categories'] }}</h3>
                    <p>Offices with Categories</p>
                </div>
                <div class="icon"><i class="fas fa-building"></i></div>
            </div>
        </div>
        <div class="col-lg-4 col-6">
            <div class="small-box bg-warning">
                <div class="inner">
                    <h3>{{ $stats['total_services'] }}</h3>
                    <p>Services in Categories</p>
                </div>
                <div class="icon"><i class="fas fa-concierge-bell"></i></div>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="card-title mb-0"><i class="fas fa-filter mr-1"></i> Filters</h3>
            <div class="card-tools">
                <button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-minus"></i></button>
            </div>
        </div>
        <form method="GET" action="{{ route('admin.categories.index') }}">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Municipality</label>
                            <select name="municipality" class="form-control select2-filter">
                                <option value="">All</option>
                                @foreach ($municipalities as $municipality)
                                    <option value="{{ $municipality->id }}" {{ request('municipality') == $municipality->id ? 'selected' : '' }}>{{ $municipality->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Office</label>
                            <select name="office" class="form-control select2-filter">
                                <option value="">All</option>
                                @foreach ($offices as $office)
                                    <option value="{{ $office->id }}" {{ request('office') == $office->id ? 'selected' : '' }}>{{ $office->municipality?->name ? $office->municipality->name . ' - ' : '' }}{{ $office->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Search</label>
                            <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Search by name or description...">
                        </div>
                    </div>
                    <div class="col-md-3 d-flex align-items-end pb-3">
                        <div class="btn-group w-100">
                            <button type="submit" class="btn btn-primary"><i class="fas fa-search mr-1"></i> Filter</button>
                            @if(request()->hasAny(['municipality', 'office', 'search']))
                                <a href="{{ route('admin.categories.index') }}" class="btn btn-outline-secondary"><i class="fas fa-times"></i></a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>

    {{-- Categories Table --}}
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="card-title mb-0"><i class="fas fa-tags mr-1"></i> Service Categories</h3>
            <a href="{{ route('admin.categories.create') }}" class="btn btn-primary btn-sm">
                <i class="fas fa-plus mr-1"></i> Add Category
            </a>
        </div>
        <div class="card-body p-0">
            <table class="table table-bordered table-striped mb-0">
                <thead>
                    <tr>
                        <th style="width: 50px;">#</th>
                        <th>Category Name</th>
                        <th>Government Office</th>
                        <th>Municipality</th>
                        <th style="width: 100px;">Services</th>
                        <th>Description</th>
                        <th style="width: 180px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($categories as $category)
                        <tr>
                            <td class="text-muted">{{ $category->id }}</td>
                            <td class="font-weight-bold">{{ $category->name }}</td>
                            <td>{{ $category->governmentOffice?->name ?? 'N/A' }}</td>
                            <td>{{ $category->governmentOffice?->municipality?->name ?? 'N/A' }}</td>
                            <td>
                                <span class="badge badge-info">{{ $category->services_count }}</span>
                            </td>
                            <td>
                                <div class="text-truncate" style="max-width: 200px;" title="{{ $category->description }}">
                                    {{ Str::limit($category->description, 60) ?? '-' }}
                                </div>
                            </td>
                            <td>
                                <a href="{{ route('admin.categories.show', $category) }}" class="btn btn-outline-info btn-sm" title="View">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="{{ route('admin.categories.edit', $category) }}" class="btn btn-outline-warning btn-sm" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <button type="button" class="btn btn-outline-danger btn-sm btn-delete-category"
                                    data-url="{{ route('admin.categories.destroy', $category) }}"
                                    data-name="{{ $category->name }}"
                                    data-has-services="{{ $category->services_count > 0 ? 'true' : 'false' }}"
                                    title="Delete">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">
                                <i class="fas fa-tags fa-2x mb-2 d-block text-muted"></i>
                                No service categories found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($categories->hasPages())
            <div class="card-footer">
                {{ $categories->links() }}
            </div>
        @endif
    </div>
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/adminlte/plugins/select2/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/adminlte/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/adminlte/plugins/sweetalert2/sweetalert2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/adminlte/plugins/toastr/toastr.min.css') }}">
@endpush

@push('scripts')
    <script src="{{ asset('assets/adminlte/plugins/select2/js/select2.full.min.js') }}"></script>
    <script src="{{ asset('assets/adminlte/plugins/sweetalert2/sweetalert2.min.js') }}"></script>
    <script src="{{ asset('assets/adminlte/plugins/toastr/toastr.min.js') }}"></script>

    <script>
        $(document).ready(function() {
            $('.select2-filter').select2({
                theme: 'bootstrap4',
                placeholder: 'Select...',
                allowClear: true,
            });

            // Delete category
            $(document).on('click', '.btn-delete-category', function() {
                const btn = $(this);
                const url = btn.data('url');
                const name = btn.data('name');
                const hasServices = btn.data('has-services') === 'true';

                if (hasServices) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Cannot Delete',
                        text: `The category "${name}" has services assigned to it. Please reassign or delete the services first.`,
                        confirmButtonColor: '#3085d6',
                        confirmButtonText: 'Understood'
                    });
                    return;
                }

                Swal.fire({
                    title: 'Delete Category?',
                    text: `Are you sure you want to delete "${name}"? This action cannot be undone.`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Yes, delete it!',
                }).then((result) => {
                    if (result.isConfirmed) {
                        const form = $('<form>', {
                            method: 'POST',
                            action: url,
                        });
                        form.append($('<input>', { type: 'hidden', name: '_method', value: 'DELETE' }));
                        form.append($('<input>', { type: 'hidden', name: '_token', value: '{{ csrf_token() }}' }));
                        form.appendTo('body').submit();
                    }
                });
            });
        });

        toastr.options = {
            closeButton: true,
            progressBar: true,
            positionClass: 'toast-top-right',
            timeOut: 4000,
        };
        @if(session('success'))
            toastr.success('{{ session('success') }}');
        @endif
        @if(session('error'))
            toastr.error('{{ session('error') }}');
        @endif
    </script>
@endpush
