@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Edit Rental Agreement</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="{{ route('rents.show', $rent) }}" class="btn btn-sm btn-info text-white">
            <i class="bi bi-eye"></i> View Details
        </a>
    </div>
</div>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <h5 class="mb-0">Agreement Details</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('rents.update', $rent) }}" method="POST">
                    @csrf
                    @method('PUT')
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="tenant_id" class="form-label">Tenant <span class="text-danger">*</span></label>
                            <select class="form-select @error('tenant_id') is-invalid @enderror" id="tenant_id" name="tenant_id" required>
                                <option value="">Select Tenant</option>
                                @foreach($tenants as $tenant)
                                    <option value="{{ $tenant->id }}" {{ (old('tenant_id', $rent->tenant_id) == $tenant->id) ? 'selected' : '' }}>
                                        {{ $tenant->full_name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('tenant_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="unit_id" class="form-label">Unit <span class="text-danger">*</span></label>
                            <select class="form-select @error('unit_id') is-invalid @enderror" id="unit_id" name="unit_id" required>
                                <option value="">Select Unit</option>
                                @foreach($units as $unit)
                                    <option value="{{ $unit->id }}" {{ (old('unit_id', $rent->unit_id) == $unit->id) ? 'selected' : '' }}>
                                        {{ $unit->building->name }} - Unit {{ $unit->unit_number }}
                                    </option>
                                @endforeach
                            </select>
                            @error('unit_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="annual_amount" class="form-label">Annual Rent (₦) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">₦</span>
                                <input type="number" step="0.01" class="form-control @error('annual_amount') is-invalid @enderror" id="annual_amount" name="annual_amount" value="{{ old('annual_amount', $rent->annual_amount) }}" required min="0">
                            </div>
                            @error('annual_amount')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="due_day" class="form-label">Due Day (1-31) <span class="text-danger">*</span></label>
                            <input type="number" class="form-control @error('due_day') is-invalid @enderror" id="due_day" name="due_day" value="{{ old('due_day', $rent->due_day) }}" required min="1" max="31">
                            <div class="form-text">Day of the month when rent is due. If an End Date is set, this will be automatically updated to match it.</div>
                            @error('due_day')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="start_date" class="form-label">Start Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control @error('start_date') is-invalid @enderror" id="start_date" name="start_date" value="{{ old('start_date', $rent->start_date ? $rent->start_date->format('Y-m-d') : '') }}" required>
                            @error('start_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="end_date" class="form-label">End Date</label>
                            <input type="date" class="form-control @error('end_date') is-invalid @enderror" id="end_date" name="end_date" value="{{ old('end_date', $rent->end_date ? $rent->end_date->format('Y-m-d') : '') }}">
                            <div class="form-text">Leave blank for indefinite/month-to-month.</div>
                            @error('end_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    
                    <div class="mb-3">
                         <label for="status" class="form-label">Status</label>
                         <select class="form-select @error('status') is-invalid @enderror" id="status" name="status">
                             <option value="ACTIVE" {{ (old('status', $rent->status) == 'ACTIVE') ? 'selected' : '' }}>Active</option>
                             <option value="TERMINATED" {{ (old('status', $rent->status) == 'TERMINATED') ? 'selected' : '' }}>Terminated</option>
                             <option value="EXPIRED" {{ (old('status', $rent->status) == 'EXPIRED') ? 'selected' : '' }}>Expired</option>
                         </select>
                         @error('status')
                             <div class="invalid-feedback">{{ $message }}</div>
                         @enderror
                    </div>

                    <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                        <button type="submit" class="btn btn-primary">Update Agreement</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Building Filter Logic
        const buildingSelect = document.getElementById('building_filter');
        const unitSelect = document.getElementById('unit_id');
        const unitOptions = Array.from(unitSelect.options);

        function filterUnits(resetValue = true) {
            const selectedBuildingId = buildingSelect.value;
            
            if (resetValue) {
                unitSelect.value = "";
            }
            
            unitOptions.forEach(option => {
                if (option.value === "") return; // Skip placeholder
                
                const buildingId = option.getAttribute('data-building-id');
                if (!selectedBuildingId || buildingId === selectedBuildingId) {
                    option.style.display = "";
                    option.disabled = false;
                } else {
                    option.style.display = "none";
                    option.disabled = true;
                }
            });
        }

        buildingSelect.addEventListener('change', function() {
            filterUnits(true);
        });

        // Initialize state
        // In Edit mode, we don't want to reset the value, just filter the list to match the current selection's building
        // Or actually, if we already pre-selected the building (which we did in blade), we just need to hide non-matching units.
        // We passed $rent->unit->building_id to selected, so buildingSelect.value should be set.
        if (buildingSelect.value) {
            filterUnits(false);
        }

        // Date Logic
        const endDateInput = document.getElementById('end_date');
        const dueDayInput = document.getElementById('due_day');

        endDateInput.addEventListener('change', function() {
            if (this.value) {
                const date = new Date(this.value);
                if (!isNaN(date.getTime())) {
                    dueDayInput.value = date.getDate();
                }
            }
        });
    });
</script>
@endpush
