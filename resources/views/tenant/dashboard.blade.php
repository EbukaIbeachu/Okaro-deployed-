@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2" id="dashboard-title">My Dashboard</h1>
    <button class="btn btn-outline-info btn-sm" onclick="startTour()">
        <i class="bi bi-question-circle"></i> Help
    </button>
</div>

<div class="row">
    <!-- Current Lease Info -->
    <div class="col-md-6 mb-4" id="lease-card">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-gradient-primary text-white">
                <h5 class="mb-0">My Home</h5>
            </div>
            <div class="card-body">
                @if($rent)
                    <h3 class="card-title">{{ $rent->unit->building->name }}</h3>
                    <p class="card-text text-muted mb-2">
                        {{ $rent->unit->building->address }}
                    </p>
                    <p class="card-text text-muted mb-4">Unit {{ $rent->unit->unit_number }}</p>

                    <div class="row g-3">
                        <div class="col-6">
                            <label class="small text-muted text-uppercase">Annual Rent</label>
                            <div class="fs-5 fw-bold">₦{{ number_format($rent->annual_amount, 2) }}</div>
                        </div>
                        <div class="col-6">
                            <label class="small text-muted text-uppercase">Next Due Date</label>
                            <div class="fs-5">{{ $rent->next_due_date ? $rent->next_due_date->format('M d, Y') : 'N/A' }}</div>
                        </div>
                        <div class="col-6">
                            <label class="small text-muted text-uppercase">Lease Status</label>
                            <div><span class="badge bg-success">Active</span></div>
                        </div>
                        <div class="col-6">
                            <label class="small text-muted text-uppercase">Current Balance</label>
                            <div class="fs-5 {{ $rent->balance > 0 ? 'text-danger' : 'text-success' }}">
                                ₦{{ number_format($rent->balance, 2) }}
                            </div>
                        </div>
                    </div>
                @else
                    <p>No active rental agreement found.</p>
                @endif
            </div>
            @if($rent)
            <div class="card-footer d-grid gap-2">
                @if($rent->balance > 0)
                    <a href="{{ route('payments.create', ['rent_id' => $rent->id]) }}" class="btn btn-primary">Pay Now</a>
                @endif
                <a href="{{ route('rents.agreement', $rent) }}" class="btn btn-outline-secondary">
                    <i class="bi bi-file-text"></i> View Rental Agreement
                </a>
            </div>
            @endif
        </div>
    </div>

    <!-- Recent Payments -->
    <div class="col-md-6 mb-4" id="payment-history">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-light">
                <h5 class="mb-0">Payment History</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Amount</th>
                                <th>Due Date</th>
                                <th>Method</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($payments as $payment)
                            <tr>
                                <td>{{ $payment->payment_date->format('M d, Y') }}</td>
                                <td class="text-success fw-bold">₦{{ number_format($payment->amount, 2) }}</td>
                                <td>{{ $payment->due_date ? $payment->due_date->format('M d, Y') : 'N/A' }}</td>
                                <td>{{ $payment->method ?? 'N/A' }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="text-center py-3">No payments recorded.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12 mb-4" id="announcements">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="bi bi-megaphone me-1"></i> Announcements</h5>
            </div>
            <div class="card-body">
                <ul class="list-group">
                    @forelse($announcements as $ann)
                        <li class="list-group-item">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <strong>{{ $ann->title }}</strong>
                                    <div class="text-muted small">{{ $ann->created_at->format('M d, Y H:i') }}</div>
                                    <div>{{ $ann->content }}</div>
                                </div>
                                <form action="{{ route('buildings.announcements.dismiss', ['building' => $rent ? $rent->unit->building_id : optional(optional($tenant->unit))->building_id, 'announcement' => $ann->id]) }}" method="POST" onsubmit="return confirm('Remove this announcement from your page?');">
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
@endsection
