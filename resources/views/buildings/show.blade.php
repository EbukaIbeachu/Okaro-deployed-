@extends('layouts.app')

@section('content')
<style>
    @media (max-width: 767.98px) {
        /* Force background color on cells to override Bootstrap's striped/hover styles */
        .mobile-status-active, 
        .mobile-status-active > td, 
        .mobile-status-active > th,
        .mobile-status-completed,
        .mobile-status-completed > td,
        .mobile-status-completed > th,
        .mobile-row-occupied,
        .mobile-row-occupied > td,
        .mobile-row-occupied > th { 
            background-color: #d1e7dd !important; 
            --bs-table-accent-bg: #d1e7dd !important;
            --bs-table-bg: #d1e7dd !important;
        }
        .mobile-status-inactive, 
        .mobile-status-inactive > td, 
        .mobile-status-inactive > th,
        .mobile-row-available,
        .mobile-row-available > td,
        .mobile-row-available > th { 
            background-color: #e2e3e5 !important; 
            --bs-table-accent-bg: #e2e3e5 !important;
            --bs-table-bg: #e2e3e5 !important;
        }
        .mobile-status-pending,
        .mobile-status-pending > td,
        .mobile-status-pending > th,
        .mobile-row-maintenance,
        .mobile-row-maintenance > td,
        .mobile-row-maintenance > th {
            background-color: #fff3cd !important;
            --bs-table-accent-bg: #fff3cd !important;
            --bs-table-bg: #fff3cd !important;
        }
        .mobile-status-failed,
        .mobile-status-failed > td,
        .mobile-status-failed > th {
            background-color: #f8d7da !important;
            --bs-table-accent-bg: #f8d7da !important;
            --bs-table-bg: #f8d7da !important;
        }
        
        /* Table Compactness */
        .table-responsive .table td, 
        .table-responsive .table th {
            padding: 0.25rem 0.5rem !important;
            font-size: 0.85rem;
        }
        
        /* Reduce button padding in tables */
        .table-responsive .btn-sm {
            padding: 0.1rem 0.3rem;
            font-size: 0.75rem;
        }
        
        /* Adjust badges for mobile */
        .table-responsive .badge {
            font-size: 0.7rem;
            padding: 0.2em 0.4em;
        }

        /* Specific column adjustments */
        .table-responsive .fw-bold {
            font-weight: 600 !important;
        }
    }
</style>
<div class="container-fluid px-0">
    <!-- Header Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm border-0 bg-white">
                <div class="card-body p-4">
                    <div class="d-flex flex-wrap flex-md-nowrap justify-content-between align-items-start gap-3">
                        <div class="d-flex align-items-center w-100">
                            @if($building->image_path)
                                <img src="{{ Storage::url($building->image_path) }}" alt="{{ $building->name }}" class="rounded me-3 object-fit-cover building-image" style="width: 100px; height: 100px;">
                            @else
                                <div class="bg-light rounded me-3 d-flex align-items-center justify-content-center text-muted building-image" style="width: 100px; height: 100px;">
                                    <i class="bi bi-building fs-1"></i>
                                </div>
                            @endif
                            <div class="flex-grow-1">
                                <h1 class="h2 mb-1 building-title">{{ $building->name }}</h1>
                                <p class="text-muted mb-2 building-address">
                                    <i class="bi bi-geo-alt me-1"></i> {{ $building->address }}
                                </p>
                                <div class="mb-2">
                                    @if($building->manager)
                                        <span class="badge bg-secondary"><i class="bi bi-person-badge me-1"></i> Manager: {{ $building->manager->name }}</span>
                                    @else
                                        <span class="badge bg-secondary"><i class="bi bi-person-badge me-1"></i> Manager: Unassigned</span>
                                    @endif
                                </div>
                                @if(auth()->user()->isAdmin() && $building->creator)
                                    <div class="mb-2 text-muted small d-none d-md-block">
                                        <i class="bi bi-person-check me-1"></i> Registered by: {{ $building->creator->name }}
                                    </div>
                                @endif
                                <div class="d-flex gap-2 flex-wrap">
                                    <span class="badge bg-primary">{{ $stats['total_units'] }} Units</span>
                                    <span class="badge bg-success">{{ $stats['occupied_units'] }} Occupied</span>
                                    <span class="badge bg-info text-dark">{{ $stats['total_tenants'] }} Tenants</span>
                                </div>
                            </div>
                        </div>
                        <div class="btn-group w-100 w-md-auto mt-2 mt-md-0">
                            <a href="{{ route('buildings.edit', $building) }}" class="btn btn-outline-primary flex-fill">
                                <i class="bi bi-pencil"></i> <span class="d-md-inline">Edit</span>
                            </a>
                            <a href="{{ route('buildings.index') }}" class="btn btn-outline-secondary flex-fill">
                                <i class="bi bi-arrow-left"></i> <span class="d-md-inline">Back</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @php
        $canPostAnnouncement = Auth::user()->isAdmin() || (Auth::user()->isManager() && $building->manager_id === Auth::id());
        $isTenantOfBuilding = Auth::user()->isTenant() && optional(optional(Auth::user()->tenant)->unit)->building_id === $building->id;
    @endphp
    @if($canPostAnnouncement || $isTenantOfBuilding)
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="bi bi-megaphone me-1"></i> Announcements</h5>
                </div>
                <div class="card-body">
                    @if($canPostAnnouncement)
                        <form action="{{ route('buildings.announce', $building) }}" method="POST" class="mb-3">
                            @csrf
                            <div class="row g-2">
                                <div class="col-md-4">
                                    <input type="text" name="title" class="form-control" placeholder="Title" required maxlength="150">
                                </div>
                                <div class="col-md-6">
                                    <input type="text" name="content" class="form-control" placeholder="Message to tenants..." required maxlength="5000">
                                </div>
                                <div class="col-md-2 d-grid">
                                    <button type="submit" class="btn btn-primary"><i class="bi bi-send"></i> Post</button>
                                </div>
                            </div>
                        </form>
                    @endif
                    <ul class="list-group">
                        @forelse($announcements as $ann)
                            <li class="list-group-item">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <strong>{{ $ann->title }}</strong>
                                        <div class="text-muted small">{{ $ann->created_at->format('M d, Y H:i') }} — {{ $ann->manager->name }}</div>
                                        <div>{{ $ann->content }}</div>
                                    </div>
                                    <form action="{{ route('buildings.announcements.dismiss', ['building' => $building->id, 'announcement' => $ann->id]) }}" method="POST" onsubmit="return confirm('Remove this announcement from your view only?');">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                            <i class="bi bi-x-circle"></i> Remove
                                        </button>
                                    </form>
                                </div>
                            </li>
                        @empty
                            <li class="list-group-item text-muted">No announcements yet.</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Quick Stats Row -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100 border-start border-4 border-primary">
                <div class="card-body p-3 p-md-4">
                    <h6 class="text-muted text-uppercase small fw-bold mb-1">Total Revenue</h6>
                    <h3 class="mb-0 fs-4 fs-md-3">₦{{ number_format($stats['total_revenue'], 2) }}</h3>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100 border-start border-4 border-success">
                <div class="card-body p-3 p-md-4">
                    <h6 class="text-muted text-uppercase small fw-bold mb-1">Occupancy</h6>
                    <h3 class="mb-0 fs-4 fs-md-3">
                        {{ $stats['total_units'] > 0 ? round(($stats['occupied_units'] / $stats['total_units']) * 100) : 0 }}%
                    </h3>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100 border-start border-4 border-warning">
                <div class="card-body p-3 p-md-4">
                    <h6 class="text-muted text-uppercase small fw-bold mb-1">Maintenance</h6>
                    <h3 class="mb-0 fs-4 fs-md-3">{{ $stats['maintenance_units'] }} Units</h3>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100 border-start border-4 border-info">
                <div class="card-body p-3 p-md-4">
                    <h6 class="text-muted text-uppercase small fw-bold mb-1">Available</h6>
                    <h3 class="mb-0 fs-4 fs-md-3">{{ $stats['available_units'] }} Units</h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content Tabs -->
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white border-bottom">
            <ul class="nav nav-tabs card-header-tabs" id="buildingTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="units-tab" data-bs-toggle="tab" data-bs-target="#units" type="button" role="tab">
                        <i class="bi bi-door-closed me-1"></i> Units
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="tenants-tab" data-bs-toggle="tab" data-bs-target="#tenants" type="button" role="tab">
                        <i class="bi bi-people me-1"></i> Tenants
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="rents-tab" data-bs-toggle="tab" data-bs-target="#rents" type="button" role="tab">
                        <i class="bi bi-file-text me-1"></i> Leases <span class="d-none d-md-inline">& Agreements</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="payments-tab" data-bs-toggle="tab" data-bs-target="#payments" type="button" role="tab">
                        <i class="bi bi-cash-stack me-1"></i> Financials
                    </button>
                </li>
            </ul>
        </div>
        <div class="card-body">
            <div class="tab-content" id="buildingTabsContent">
                
                <!-- Units Tab -->
                <div class="tab-pane fade show active" id="units" role="tabpanel">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0">Unit Directory</h5>
                        <a href="{{ route('units.create', ['building_id' => $building->id]) }}" class="btn btn-sm btn-primary">
                            <i class="bi bi-plus-lg"></i> <span class="d-none d-md-inline">Add New Unit</span><span class="d-inline d-md-none">Add</span>
                        </a>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Unit #</th>
                                    <th>Type</th>
                                    <th class="d-none d-md-table-cell">Status</th>
                                    <th>Current Tenant</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($building->units as $unit)
                                <tr class="@if($unit->status === 'OCCUPIED') mobile-row-occupied @elseif($unit->status === 'MAINTENANCE') mobile-row-maintenance @else mobile-row-available @endif">
                                    <td class="fw-bold text-nowrap">{{ $unit->unit_number }}</td>
                                    <td>
                                        <div class="small">{{ $unit->bedrooms }} Bed, {{ $unit->bathrooms }} Bath</div>
                                        <div class="text-muted small">Floor {{ $unit->floor ?? 'G' }}</div>
                                    </td>
                                    <td class="d-none d-md-table-cell">
                                        @if($unit->status === 'OCCUPIED')
                                            <span class="badge bg-success">Occupied</span>
                                        @elseif($unit->status === 'MAINTENANCE')
                                            <span class="badge bg-warning text-dark">Maintenance</span>
                                        @else
                                            <span class="badge bg-secondary">Available</span>
                                        @endif
                                    </td>
                                    <td>
                                        @php
                                            $tenant = $unit->currentTenant;
                                            if (!$tenant) {
                                                // Fallback: check building's loaded rents for this unit
                                                $activeRent = $building->rents->where('unit_id', $unit->id)->where('status', 'ACTIVE')->first();
                                                if ($activeRent) {
                                                    $tenant = $activeRent->tenant;
                                                }
                                            }
                                        @endphp
                                        @if($tenant)
                                            <a href="{{ route('tenants.show', $tenant) }}" class="text-decoration-none fw-bold">
                                                {{ $tenant->full_name }}
                                            </a>
                                        @else
                                            <span class="text-muted fst-italic">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="{{ route('units.edit', $unit) }}" class="btn btn-outline-secondary" title="Edit Unit">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <form action="{{ route('units.destroy', $unit) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure?');">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="btn btn-outline-danger" title="Delete">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-muted">No units found in this building.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Tenants Tab -->
                <div class="tab-pane fade" id="tenants" role="tabpanel">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0">Active Tenants</h5>
                        <a href="{{ route('tenants.create', ['building_id' => $building->id]) }}" class="btn btn-sm btn-primary">
                            <i class="bi bi-plus-lg"></i> <span class="d-none d-md-inline">Register Tenant</span><span class="d-inline d-md-none">Register</span>
                        </a>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Name</th>
                                    <th>Unit</th>
                                    <th class="d-none d-md-table-cell">Contact</th>
                                    <th class="d-none d-md-table-cell">Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($building->tenants->unique('id') as $tenant)
                                <tr class="{{ $tenant->active ? 'mobile-status-active' : 'mobile-status-inactive' }}">
                                    <td>
                                        <div class="fw-bold">{{ $tenant->full_name }}</div>
                                        <div class="small text-muted">{{ $tenant->email }}</div>
                                    </td>
                                    <td>
                                        <!-- Find tenant's unit in this building -->
                                        @php
                                            $tenantUnit = $building->units->first(function($u) use ($tenant) {
                                                return $u->currentTenant && $u->currentTenant->id === $tenant->id;
                                            });
                                        @endphp
                                        <span class="badge bg-light text-dark border">
                                            {{ $tenantUnit ? $tenantUnit->unit_number : 'History' }}
                                        </span>
                                    </td>
                                    <td class="d-none d-md-table-cell">{{ $tenant->phone_number }}</td>
                                    <td class="d-none d-md-table-cell">
                                        @if($tenant->active)
                                            <span class="badge bg-success">Active</span>
                                        @else
                                            <span class="badge bg-secondary">Inactive</span>
                                        @endif
                                    </td>
                                    <td>
                                        <a href="{{ route('tenants.show', $tenant) }}" class="btn btn-sm btn-outline-primary">
                                            <span class="d-none d-md-inline">View Profile</span><span class="d-inline d-md-none">View</span>
                                        </a>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-muted">No tenants found.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Leases Tab -->
                <div class="tab-pane fade" id="rents" role="tabpanel">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0">Rental Agreements</h5>
                        <a href="{{ route('rents.create', ['building_id' => $building->id]) }}" class="btn btn-sm btn-primary">
                            <i class="bi bi-plus-lg"></i> <span class="d-none d-md-inline">Create Lease</span><span class="d-inline d-md-none">Create</span>
                        </a>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Tenant</th>
                                    <th>Unit</th>
                                    <th class="d-none d-md-table-cell">Duration</th>
                                    <th>Amount (Yearly)</th>
                                    <th class="d-none d-md-table-cell">Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($building->rents->sortByDesc('start_date') as $rent)
                                <tr class="{{ $rent->status === 'ACTIVE' ? 'mobile-status-active' : 'mobile-status-inactive' }}" 
                                    style="cursor: pointer;" 
                                    onclick="window.open('{{ route('rents.agreement', $rent) }}', '_blank')"
                                    title="Click to view PDF Agreement">
                                    <td>
                                        {{ $rent->tenant->full_name }}
                                        <i class="bi bi-file-earmark-pdf text-danger ms-1 small" title="PDF Agreement"></i>
                                    </td>
                                    <td>{{ $rent->unit->unit_number }}</td>
                                    <td class="d-none d-md-table-cell">
                                        <div class="small">{{ $rent->start_date->format('M d, Y') }}</div>
                                        <div class="small text-muted">to {{ $rent->end_date ? $rent->end_date->format('M d, Y') : 'Indefinite' }}</div>
                                    </td>
                                    <td class="fw-bold text-nowrap">₦{{ number_format($rent->annual_amount, 2) }}</td>
                                    <td class="d-none d-md-table-cell">
                                        @if($rent->status === 'ACTIVE')
                                            <span class="badge bg-success">Active</span>
                                        @else
                                            <span class="badge bg-secondary">{{ ucfirst(strtolower($rent->status)) }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm" onclick="event.stopPropagation()">
                                            <a href="{{ route('rents.show', $rent) }}" class="btn btn-outline-primary" title="Details">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <a href="{{ route('rents.agreement', $rent) }}" class="btn btn-outline-dark" title="PDF Agreement">
                                                <i class="bi bi-file-pdf"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">No agreements found.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Financials Tab -->
                <div class="tab-pane fade" id="payments" role="tabpanel">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0">Payment History</h5>
                        <a href="{{ route('payments.create', ['building_id' => $building->id]) }}" class="btn btn-sm btn-success">
                            <i class="bi bi-plus-lg"></i> Record Payment
                        </a>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Date</th>
                                    <th>Tenant</th>
                                    <th class="d-none d-md-table-cell">Reference</th>
                                    <th>Amount</th>
                                    <th class="d-none d-md-table-cell">Method</th>
                                    <th class="d-none d-md-table-cell">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $payments = $building->rents->flatMap->payments->sortByDesc('payment_date');
                                @endphp
                                @forelse($payments as $payment)
                                <tr class="{{ $payment->status === 'COMPLETED' ? 'mobile-status-completed' : ($payment->status === 'PENDING' ? 'mobile-status-pending' : ($payment->status === 'FAILED' ? 'mobile-status-failed' : 'mobile-status-inactive')) }}">
                                    <td>{{ $payment->payment_date->format('Y-m-d') }}</td>
                                    <td>{{ $payment->rent->tenant->full_name }}</td>
                                    <td class="font-monospace small d-none d-md-table-cell">{{ Str::limit($payment->rent->unit->unit_number . '-' . $payment->id, 15) }}</td>
                                    <td class="fw-bold text-success">+₦{{ number_format($payment->amount, 2) }}</td>
                                    <td class="d-none d-md-table-cell">{{ ucfirst($payment->payment_method) }}</td>
                                    <td class="d-none d-md-table-cell">
                                        @if($payment->status === 'COMPLETED')
                                            <span class="badge bg-success">Completed</span>
                                        @elseif($payment->status === 'PENDING')
                                            <span class="badge bg-warning text-dark">Pending</span>
                                        @elseif($payment->status === 'FAILED')
                                            <span class="badge bg-danger">Failed</span>
                                        @else
                                            <span class="badge bg-secondary">{{ ucfirst(strtolower($payment->status)) }}</span>
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">No payments recorded yet.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>
@endsection
