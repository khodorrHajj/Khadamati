@extends('layouts.admin')

@section('title', 'Category Details')
@section('page-title', 'Category Details')

@section('content')
    <div class="row">
        {{-- Category Info --}}
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title mb-0"><i class="fas fa-tag mr-1"></i> {{ $category->name }}</h3>
                    <div class="btn-group">
                        <a href="{{ route('admin.categories.edit', $category) }}" class="btn btn-warning btn-sm">
                            <i class="fas fa-edit mr-1"></i> Edit
                        </a>
                        <a href="{{ route('admin.categories.index') }}" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-arrow-left mr-1"></i> Back
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    {{-- Office Info --}}
                    <div class="mb-3">
                        <label class="text-muted small font-weight-bold">GOVERNMENT OFFICE</label>
                        <p class="mb-0">
                            <i class="fas fa-building mr-1 text-muted"></i>
                            {{ $category->governmentOffice?->name ?? 'N/A' }}
                        </p>
                        <p class="text-muted small mb-0">
                            <i class="fas fa-city mr-1"></i>
                            {{ $category->governmentOffice?->municipality?->name ?? 'N/A' }}
                        </p>
                    </div>

                    {{-- Description --}}
                    <div class="mb-3">
                        <label class="text-muted small font-weight-bold">DESCRIPTION</label>
                        <div class="p-3 bg-light rounded">
                            {{ $category->description ?? 'No description provided.' }}
                        </div>
                    </div>

                    {{-- Created / Updated --}}
                    <div class="row">
                        <div class="col-md-6">
                            <label class="text-muted small font-weight-bold">CREATED</label>
                            <p class="mb-0"><i class="fas fa-calendar-plus mr-1 text-muted"></i> {{ $category->created_at->format('F d, Y \a\t H:i') }}</p>
                        </div>
                        <div class="col-md-6">
                            <label class="text-muted small font-weight-bold">LAST UPDATED</label>
                            <p class="mb-0"><i class="fas fa-calendar-check mr-1 text-muted"></i> {{ $category->updated_at->format('F d, Y \a\t H:i') }}</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Services in this Category --}}
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title mb-0"><i class="fas fa-concierge-bell mr-1"></i> Services in this Category</h3>
                    <span class="badge badge-info">{{ $category->services->count() }} services</span>
                </div>
                <div class="card-body p-0">
                    @if($category->services->isNotEmpty())
                        <table class="table table-bordered table-striped mb-0">
                            <thead>
                                <tr>
                                    <th>Service Name</th>
                                    <th>Price</th>
                                    <th>Duration</th>
                                    <th>Status</th>
                                    <th style="width: 80px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($category->services as $service)
                                    <tr>
                                        <td class="font-weight-bold">{{ $service->name }}</td>
                                        <td>{{ $service->formattedPrice() }}</td>
                                        <td>{{ $service->durationLabel() }}</td>
                                        <td>
                                            @if($service->is_active)
                                                <span class="badge badge-success">Active</span>
                                            @else
                                                <span class="badge badge-secondary">Inactive</span>
                                            @endif
                                        </td>
                                        <td>
                                            <a href="{{ route('admin.services.show', $service) }}" class="btn btn-outline-info btn-sm" title="View">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @else
                        <div class="text-center text-muted py-4">
                            <i class="fas fa-concierge-bell fa-2x mb-2 d-block text-muted"></i>
                            No services in this category yet.
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Sidebar --}}
        <div class="col-lg-4">
            {{-- Quick Stats --}}
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-chart-pie mr-1"></i> Quick Stats</h3>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom">
                        <span class="text-muted">Total Services</span>
                        <span class="font-weight-bold badge badge-info px-3 py-1">{{ $category->services->count() }}</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom">
                        <span class="text-muted">Active Services</span>
                        <span class="font-weight-bold badge badge-success px-3 py-1">{{ $category->services->where('is_active', true)->count() }}</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom">
                        <span class="text-muted">Inactive Services</span>
                        <span class="font-weight-bold badge badge-secondary px-3 py-1">{{ $category->services->where('is_active', false)->count() }}</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="text-muted">Total Revenue</span>
                        @php
                            $totalRevenue = $category->services->sum('price');
                        @endphp
                        <span class="font-weight-bold">LBP {{ number_format($totalRevenue, 0) }}</span>
                    </div>
                </div>
            </div>

            {{-- Danger Zone --}}
            <div class="card card-danger card-outline">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-exclamation-triangle mr-1"></i> Danger Zone</h3>
                </div>
                <div class="card-body">
                    @if($category->services()->exists())
                        <p class="text-muted small mb-2">
                            <i class="fas fa-info-circle mr-1"></i>
                            This category has <strong>{{ $category->services->count() }} service(s)</strong> assigned. You must reassign or delete them before deleting this category.
                        </p>
                        <button type="button" class="btn btn-outline-danger btn-sm" disabled>
                            <i class="fas fa-trash mr-1"></i> Delete Category
                        </button>
                    @else
                        <p class="text-muted small mb-2">This category has no services. It can be safely deleted.</p>
                        <button type="button" class="btn btn-outline-danger btn-sm btn-delete-category"
                            data-url="{{ route('admin.categories.destroy', $category) }}"
                            data-name="{{ $category->name }}">
                            <i class="fas fa-trash mr-1"></i> Delete Category
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/adminlte/plugins/sweetalert2/sweetalert2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/adminlte/plugins/toastr/toastr.min.css') }}">
@endpush

@push('scripts')
    <script src="{{ asset('assets/adminlte/plugins/sweetalert2/sweetalert2.min.js') }}"></script>
    <script src="{{ asset('assets/adminlte/plugins/toastr/toastr.min.js') }}"></script>

    <script>
        $(document).on('click', '.btn-delete-category', function() {
            const btn = $(this);
            const url = btn.data('url');
            const name = btn.data('name');

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
