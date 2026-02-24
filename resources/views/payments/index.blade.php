@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2" id="payments-header">Payments</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <button class="btn btn-sm btn-outline-info me-2" onclick="startTour()">
            <i class="bi bi-question-circle"></i> Help
        </button>
        <a href="{{ route('payments.create') }}" class="btn btn-sm btn-primary" id="add-payment-btn">
            <i class="bi bi-plus-lg"></i> Record Payment
        </a>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <div class="mb-3">
            <input type="text" id="paymentSearch" class="form-control" placeholder="Search payments...">
        </div>
        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle" id="paymentsTable">
                <thead>
                    <tr>
                        <th class="d-none d-md-table-cell">Date</th>
                        <th>Tenant</th>
                        @if(Auth::user()->isAdmin())
                        <th>Created By</th>
                        @endif
                        <th>Amount</th>
                        <th class="d-none d-md-table-cell">Method</th>
                        <th class="d-none d-md-table-cell">Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($payments as $payment)
                    <tr class="@if($payment->status === 'COMPLETED') table-success @elseif($payment->status === 'PENDING') table-warning @else table-danger @endif">
                        <td class="d-none d-md-table-cell">{{ $payment->payment_date }}</td>
                        <td>
                            <a href="{{ route('tenants.show', $payment->rent->tenant) }}" class="text-decoration-none fw-bold">
                                {{ $payment->rent->tenant->full_name }}
                            </a>
                            <br>
                            <small class="text-muted">
                                @if($payment->rent->unit)
                                    Unit {{ $payment->rent->unit->unit_number }}
                                @else
                                    <span class="fst-italic">Unit Removed</span>
                                @endif
                            </small>
                        </td>
                        @if(Auth::user()->isAdmin())
                        <td>
                            @if($payment->creator)
                                <div class="d-flex align-items-center">
                                    <div class="ms-2">
                                        <div class="fw-bold small">{{ $payment->creator->name }}</div>
                                        <div class="text-muted" style="font-size: 0.75rem;">{{ $payment->creator->role->name ?? 'User' }}</div>
                                    </div>
                                </div>
                            @else
                                <span class="text-muted small fst-italic">System</span>
                            @endif
                        </td>
                        @endif
                        <td>₦{{ number_format($payment->amount, 2) }}</td>
                        <td class="d-none d-md-table-cell">{{ ucfirst(str_replace('_', ' ', $payment->payment_method)) }}</td>
                        <td class="d-none d-md-table-cell">
                            @if($payment->status === 'COMPLETED')
                                <span class="badge bg-success">Completed</span>
                            @elseif($payment->status === 'PENDING')
                                <span class="badge bg-warning text-dark">Pending</span>
                            @else
                                <span class="badge bg-danger">Failed</span>
                            @endif
                        </td>
                        <td class="text-end">
                            <div class="btn-group">
                                <a href="{{ route('payments.show', $payment) }}" class="btn btn-sm btn-outline-secondary" title="View">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="{{ route('payments.edit', $payment) }}" class="btn btn-sm btn-outline-primary" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                @if(Auth::user()->isAdmin())
                                <form action="{{ route('payments.destroy', $payment) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this payment?');">
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
                            <i class="bi bi-cash-stack fs-1 d-block mb-2"></i>
                            No payments found. Click "Record Payment" to add one.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <div class="mt-3">
            {{ $payments->links() }}
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        setupTableSearch('paymentSearch', 'paymentsTable');
    });
</script>
@endpush
@endsection
