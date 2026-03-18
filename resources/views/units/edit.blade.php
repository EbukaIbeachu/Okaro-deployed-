@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Edit Unit</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="{{ route('units.show', $unit) }}" class="btn btn-sm btn-info text-white">
            <i class="bi bi-eye"></i> View Details
        </a>
    </div>
</div>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <h5 class="mb-0">Edit: {{ $unit->unit_number }} ({{ $unit->building->name }})</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('units.update', $unit) }}" method="POST">
                    @csrf
                    @method('PUT')
                    
                    <div class="mb-3">
                        <label for="building_id" class="form-label">Building <span class="text-danger">*</span></label>
                        <select class="form-select @error('building_id') is-invalid @enderror" id="building_id" name="building_id" required>
                            @foreach($buildings as $building)
                                <option value="{{ $building->id }}" {{ (old('building_id', $unit->building_id) == $building->id) ? 'selected' : '' }}>
                                    {{ $building->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('building_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="unit_number" class="form-label">Unit Number <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('unit_number') is-invalid @enderror" id="unit_number" name="unit_number" value="{{ old('unit_number', $unit->unit_number) }}" required maxlength="50">
                            @error('unit_number')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="floor" class="form-label">Floor</label>
                            <input type="text" class="form-control @error('floor') is-invalid @enderror" id="floor" name="floor" value="{{ old('floor', $unit->floor) }}" maxlength="20">
                            @error('floor')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="bedrooms" class="form-label">Bedrooms <span class="text-danger">*</span></label>
                            <input type="number" class="form-control @error('bedrooms') is-invalid @enderror" id="bedrooms" name="bedrooms" value="{{ old('bedrooms', $unit->bedrooms) }}" required min="0">
                            @error('bedrooms')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-4 mb-3">
                            <label for="bathrooms" class="form-label">Bathrooms <span class="text-danger">*</span></label>
                            <input type="number" class="form-control @error('bathrooms') is-invalid @enderror" id="bathrooms" name="bathrooms" value="{{ old('bathrooms', $unit->bathrooms) }}" required min="0">
                            @error('bathrooms')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-4 mb-3">
                            <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                            <select class="form-select @error('status') is-invalid @enderror" id="status" name="status" required>
                                <option value="AVAILABLE" {{ old('status', $unit->status) == 'AVAILABLE' ? 'selected' : '' }}>Available</option>
                                <option value="OCCUPIED" {{ old('status', $unit->status) == 'OCCUPIED' ? 'selected' : '' }}>Occupied</option>
                                <option value="MAINTENANCE" {{ old('status', $unit->status) == 'MAINTENANCE' ? 'selected' : '' }}>Maintenance</option>
                            </select>
                            @error('status')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                        <button type="submit" class="btn btn-primary">Update Unit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
