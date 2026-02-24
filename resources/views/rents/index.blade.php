@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2" id="rents-header">Rental Agreements</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <button class="btn btn-sm btn-outline-info me-2" onclick="startTour()">
            <i class="bi bi-question-circle"></i> Help
        </button>
        <a href="{{ route('rents.create') }}" class="btn btn-sm btn-primary" id="add-rent-btn">
            <i class="bi bi-plus-lg"></i> Create Agreement
        </a>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-3 gap-2">
            <div class="btn-group w-100 w-md-auto">
                <a href="{{ route('rents.index', ['status' => 'ACTIVE']) }}" class="btn btn-sm {{ $status === 'ACTIVE' ? 'btn-primary' : 'btn-outline-primary' }}">Active</a>
                <a href="{{ route('rents.index', ['status' => 'EXPIRED']) }}" class="btn btn-sm {{ $status === 'EXPIRED' ? 'btn-primary' : 'btn-outline-primary' }}">Expired</a>
                <a href="{{ route('rents.index', ['status' => 'TERMINATED']) }}" class="btn btn-sm {{ $status === 'TERMINATED' ? 'btn-primary' : 'btn-outline-primary' }}">Terminated</a>
                <a href="{{ route('rents.index', ['status' => 'ALL']) }}" class="btn btn-sm {{ $status === 'ALL' ? 'btn-primary' : 'btn-outline-primary' }}">All</a>
            </div>
            <input type="text" id="rentSearch" class="form-control w-100 w-md-auto" placeholder="Search agreements...">
        </div>
        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle" id="rentsTable">
                <thead>
                    <tr>
                        <th>Tenant</th>
                        <th class="d-none d-md-table-cell">Unit</th>
                        <th class="d-none d-md-table-cell">Period</th>
                        <th>Amount</th>
                        @if(Auth::user()->isAdmin())
                        <th class="d-none d-md-table-cell">Created By</th>
                        @endif
                        <th class="d-none d-md-table-cell">Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rents as $rent)
                    <tr class="clickable-row @if($rent->status === 'ACTIVE') table-success @elseif($rent->status === 'EXPIRED') table-warning @else table-secondary @endif" data-href="{{ route('rents.agreement', $rent) }}" style="cursor: pointer;">
                        <td>
                            <a href="{{ route('rents.show', $rent) }}" class="text-decoration-none fw-bold">
                                {{ $rent->tenant->full_name }}
                            </a>
                        </td>
                        <td class="d-none d-md-table-cell">
                            @if($rent->unit)
                                <a href="{{ route('units.show', $rent->unit) }}" class="text-decoration-none text-dark">
                                    {{ $rent->unit->unit_number }}
                                </a>
                                <br>
                                <small class="text-muted">{{ $rent->unit->building->name }}</small>
                            @else
                                <span class="fst-italic text-muted">Unit Removed</span>
                            @endif
                        </td>
                        <td class="d-none d-md-table-cell">
                            {{ $rent->start_date }} <br>
                            <small class="text-muted">to {{ $rent->end_date ?? 'Indefinite' }}</small>
                        </td>
                        <td>₦{{ number_format($rent->annual_amount, 2) }}</td>
                        @if(Auth::user()->isAdmin())
                        <td class="d-none d-md-table-cell">
                            @if($rent->creator)
                                <div class="d-flex align-items-center">
                                    <div class="ms-2">
                                        <div class="fw-bold small">{{ $rent->creator->name }}</div>
                                        <div class="text-muted" style="font-size: 0.75rem;">{{ $rent->creator->role->name ?? 'User' }}</div>
                                    </div>
                                </div>
                            @else
                                <span class="text-muted small fst-italic">System</span>
                            @endif
                        </td>
                        @endif
                        <td class="d-none d-md-table-cell">
                            @if($rent->status === 'ACTIVE')
                                <span class="badge bg-success">Active</span>
                            @else
                                <span class="badge bg-secondary">{{ ucfirst(strtolower($rent->status)) }}</span>
                            @endif
                        </td>
                        <td class="text-end">
                            <div class="btn-group">
                                <a href="{{ route('rents.agreement', $rent) }}" class="btn btn-sm btn-outline-dark" title="Download Agreement" target="_blank">
                                    <i class="bi bi-file-earmark-pdf"></i>
                                </a>
                                <a href="{{ route('rents.show', $rent) }}" class="btn btn-sm btn-outline-secondary" title="View">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="{{ route('rents.edit', $rent) }}" class="btn btn-sm btn-outline-primary" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                @if(Auth::user()->isAdmin())
                                <form action="{{ route('rents.destroy', $rent) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this agreement?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center py-4 text-muted">
                            <i class="bi bi-file-earmark-text fs-1 d-block mb-2"></i>
                            No rental agreements found. Click "Create Agreement" to add one.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <div class="mt-3">
            {{ $rents->links() }}
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Search functionality
        if (typeof setupTableSearch === 'function') {
            setupTableSearch('rentSearch', 'rentsTable');
        }

        // Clickable Row Logic
        const rows = document.querySelectorAll('.clickable-row');
        rows.forEach(row => {
            row.addEventListener('click', function(e) {
                // Prevent navigation if clicking on interactive elements
                if (e.target.closest('a') || e.target.closest('button') || e.target.closest('form') || e.target.closest('input')) {
                    return;
                }
                
                const url = this.dataset.href;
                if (url) {
                    window.location.href = url;
                }
            });
        });
    });
</script>
@endpush
@endsection
