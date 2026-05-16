@extends('layouts.municipality')

@section('title', 'Edit Service')
@section('page-title', 'Edit Service')

@section('content')
    @if ($errors->any())
        <div class="alert alert-danger">
            @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="card-title mb-0">Edit Service for {{ $office->name }}</h3>
            <a href="{{ route('municipality.services') }}" class="btn btn-secondary btn-sm">
                <i class="fas fa-arrow-left"></i> Back to Services
            </a>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('municipality.services.update', $service) }}">
                @csrf
                @method('PUT')

                <div class="form-group">
                    <label>Category</label>
                    <select name="service_category_id" class="custom-select @error('service_category_id') is-invalid @enderror">
                        <option value="">Select Category</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" {{ (string) old('service_category_id', $service->service_category_id) === (string) $category->id ? 'selected' : '' }}>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('service_category_id')
                        <span class="invalid-feedback d-block">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label>Service Name</label>
                    <input type="text" name="name" value="{{ old('name', $service->name) }}" class="form-control @error('name') is-invalid @enderror">
                    @error('name')
                        <span class="invalid-feedback d-block">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" class="form-control @error('description') is-invalid @enderror" rows="3">{{ old('description', $service->description) }}</textarea>
                    @error('description')
                        <span class="invalid-feedback d-block">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label>Price (LBP)</label>
                        <input type="number" step="1000" min="0" name="price" value="{{ old('price', $service->price) }}" class="form-control @error('price') is-invalid @enderror">
                        @error('price')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-group col-md-6">
                        <label>Expected Duration From (Days)</label>
                        <input type="number" min="1" name="duration_days" value="{{ old('duration_days', $service->duration_days) }}" class="form-control @error('duration_days') is-invalid @enderror">
                        @error('duration_days')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                <div class="form-group">
                    <label>Expected Duration To (Days)</label>
                    <input type="number" min="{{ old('duration_days', $service->durationFromDays()) }}" name="duration_days_max" value="{{ old('duration_days_max', $service->durationToDays()) }}" class="form-control @error('duration_days_max') is-invalid @enderror">
                    @error('duration_days_max')
                        <span class="invalid-feedback d-block">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    @include('shared.document-picker', [
                        'pickerId' => 'service-required-documents-edit',
                        'inputName' => 'required_documents_list',
                        'label' => 'Required Documents',
                        'placeholder' => 'Search or type a required document',
                        'presetDocuments' => $requiredDocumentPresets,
                        'selectedDocuments' => old('required_documents_list', $service->requiredDocumentList()),
                        'legacyInputName' => 'required_documents',
                        'helpText' => 'Search from the preloaded document catalog, add the needed documents, and remove outdated ones.',
                    ])
                    @error('required_documents')
                        <span class="invalid-feedback d-block">{{ $message }}</span>
                    @enderror
                    @error('required_documents_list')
                        <span class="invalid-feedback d-block">{{ $message }}</span>
                    @enderror
                    @error('required_documents_list.*')
                        <span class="invalid-feedback d-block">{{ $message }}</span>
                    @enderror
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Update Service
                </button>
                <a href="{{ route('municipality.services') }}" class="btn btn-secondary ml-2">
                    Cancel
                </a>
            </form>
        </div>
    </div>
@endsection