@extends('layouts.admin')

@section('title', 'Create Service Category')
@section('page-title', 'Create Service Category')

@section('content')
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="card-title mb-0"><i class="fas fa-plus mr-1"></i> New Service Category</h3>
            <a href="{{ route('admin.categories.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-arrow-left mr-1"></i> Back
            </a>
        </div>
        <form method="POST" action="{{ route('admin.categories.store') }}">
            @csrf
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Category Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" value="{{ old('name') }}" class="form-control @error('name') is-invalid @enderror" placeholder="e.g. Civil Records, Permits, Licenses...">
                            @error('name')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Government Office <span class="text-danger">*</span></label>
                            <select name="government_office_id" class="form-control select2-office @error('government_office_id') is-invalid @enderror">
                                <option value="">Select an office...</option>
                                @foreach ($offices as $office)
                                    <option value="{{ $office->id }}" {{ old('government_office_id') == $office->id ? 'selected' : '' }}>
                                        {{ $office->municipality?->name ? $office->municipality->name . ' - ' : '' }}{{ $office->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('government_office_id')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" rows="4" class="form-control @error('description') is-invalid @enderror" placeholder="Optional description for this category...">{{ old('description') }}</textarea>
                    @error('description')
                        <span class="invalid-feedback">{{ $message }}</span>
                    @enderror
                </div>
            </div>
            <div class="card-footer">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save mr-1"></i> Create Category
                </button>
                <a href="{{ route('admin.categories.index') }}" class="btn btn-outline-secondary ml-2">Cancel</a>
            </div>
        </form>
    </div>
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/adminlte/plugins/select2/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/adminlte/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css') }}">
@endpush

@push('scripts')
    <script src="{{ asset('assets/adminlte/plugins/select2/js/select2.full.min.js') }}"></script>
    <script>
        $(document).ready(function() {
            $('.select2-office').select2({
                theme: 'bootstrap4',
                placeholder: 'Select an office...',
                allowClear: true,
            });
        });
    </script>
@endpush
