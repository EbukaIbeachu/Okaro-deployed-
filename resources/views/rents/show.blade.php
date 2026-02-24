@extends('layouts.app')

@section('content')
<style>
    @media (max-width: 767.98px) {
        .mobile-status-completed,
        .mobile-status-completed > td,
        .mobile-status-completed > th {
            background-color: #d1e7dd !important;
            --bs-table-accent-bg: #d1e7dd !important;
            --bs-table-bg: #d1e7dd !important;
        }
        .mobile-status-pending,
        .mobile-status-pending > td,
        .mobile-status-pending > th {
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
        .payment-history-table th:nth-child(5),
        .payment-history-table td:nth-child(5) {
            display: none;
        }
    }
</style>
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Rental Agreement Details</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        @if(auth()->user()->isAdmin() || auth()->user()->isManager())
        <a href="{{ route('rents.edit', $rent) }}" class="btn btn-sm btn-primary">
            <i class="bi bi-pencil"></i> Edit Agreement
        </a>
        @endif
    </div>
</div>

<div class="row">
    <div class="col-md-6 mb-4">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white fw-bold">
                Agreement Summary
            </div>
            <div class="card-body">
                @if(auth()->user()->isAdmin() && $rent->creator)
                    <div class="mb-3 p-2 bg-light rounded text-muted small">
                        <i class="bi bi-person-check me-1"></i> Agreement created by: <strong>{{ $rent->creator->name }}</strong>
                    </div>
                @endif
                <table class="table table-borderless">
                    <tbody>
                        <tr>
                            <th scope="row" class="text-muted" style="width: 40%">Status</th>
                            <td>
                                @if($rent->status === 'ACTIVE')
                                    <span class="badge bg-success">Active</span>
                                @else
                                    <span class="badge bg-secondary">{{ ucfirst(strtolower($rent->status)) }}</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th scope="row" class="text-muted">Annual Rent</th>
                            <td class="fs-5 fw-bold">₦{{ number_format($rent->annual_amount, 2) }}</td>
                        </tr>
                        @php
                            $n = $rent->due_day;
                            $suffix = 'th';
                            if (!in_array($n % 100, [11, 12, 13])) {
                                $last = $n % 10;
                                if ($last === 1) {
                                    $suffix = 'st';
                                } elseif ($last === 2) {
                                    $suffix = 'nd';
                                } elseif ($last === 3) {
                                    $suffix = 'rd';
                                }
                            }
                        @endphp
                        <tr>
                            <th scope="row" class="text-muted">Due Day</th>
                            <td>{{ $n }}{{ $suffix }} of every month</td>
                        </tr>
                        <tr>
                            <th scope="row" class="text-muted">Lease Period</th>
                            <td>
                                {{ $rent->start_date }} 
                                <i class="bi bi-arrow-right mx-1 text-muted"></i>
                                {{ $rent->end_date ?? 'Indefinite' }}
                            </td>
                        </tr>
                        <tr>
                            <th scope="row" class="text-muted">Signed Agreement</th>
                            <td>
                                @if($rent->signed_agreement_path)
                                    <a href="{{ Storage::url($rent->signed_agreement_path) }}" target="_blank" class="btn btn-sm btn-outline-primary mb-2">
                                        <i class="bi bi-file-earmark-pdf"></i> View Signed Copy
                                    </a>
                                @else
                                    <span class="text-muted fst-italic">Not uploaded</span>
                                @endif

                                <form action="{{ route('rents.upload-agreement', $rent) }}" method="POST" enctype="multipart/form-data" class="mt-2">
                                    @csrf
                                    <div class="input-group input-group-sm">
                                        <input type="file" class="form-control" name="signed_agreement" accept=".pdf,.jpg,.jpeg,.png" required>
                                        <button class="btn btn-primary" type="submit">Upload</button>
                                    </div>
                                    <div class="form-text small">Max 10MB (PDF/Image)</div>
                                </form>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-md-6 mb-4">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white fw-bold">
                Parties Involved
            </div>
            <div class="card-body">
                <h6 class="text-muted mb-2">Tenant</h6>
                <div class="d-flex align-items-center mb-3">
                    <div class="bg-light rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                        <i class="bi bi-person"></i>
                    </div>
                    <div>
                        <a href="{{ route('tenants.show', $rent->tenant) }}" class="text-decoration-none fw-bold">
                            {{ $rent->tenant->full_name }}
                        </a>
                        <div class="text-muted small">{{ $rent->tenant->email }}</div>
                    </div>
                </div>

                <hr class="my-3">

                <h6 class="text-muted mb-2">Property</h6>
                <div class="d-flex align-items-center">
                    <div class="bg-light rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                        <i class="bi bi-house"></i>
                    </div>
                    <div>
                        <a href="{{ route('units.show', $rent->unit) }}" class="text-decoration-none fw-bold">
                            Unit {{ $rent->unit->unit_number }}
                        </a>
                        <div class="text-muted small">{{ $rent->unit->building->name }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <span class="fw-bold">Payment History</span>
        <a href="{{ route('payments.create', ['rent_id' => $rent->id]) }}" class="btn btn-sm btn-success">
            <i class="bi bi-plus-lg"></i> Record Payment
        </a>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 payment-history-table">
                <thead class="table-light">
                    <tr>
                        <th>Date</th>
                        <th>Amount</th>
                        <th>Method</th>
                        <th class="text-end">Actions</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rent->payments->sortByDesc('payment_date') as $payment)
                    <tr class="
                        @if($payment->status === 'COMPLETED') mobile-status-completed
                        @elseif($payment->status === 'PENDING') mobile-status-pending
                        @else mobile-status-failed
                        @endif
                    ">
                        <td>{{ $payment->payment_date }}</td>
                        <td>₦{{ number_format($payment->amount, 2) }}</td>
                        <td>{{ ucfirst(str_replace('_', ' ', $payment->payment_method)) }}</td>
                        <td class="text-end">
                            <a href="{{ route('payments.show', $payment) }}" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-eye"></i>
                            </a>
                        </td>
                        <td>
                            @if($payment->status === 'COMPLETED')
                                <span class="badge bg-success">Completed</span>
                            @elseif($payment->status === 'PENDING')
                                <span class="badge bg-warning text-dark">Pending</span>
                            @else
                                <span class="badge bg-danger">Failed</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center py-3 text-muted">
                            No payments recorded for this agreement.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
