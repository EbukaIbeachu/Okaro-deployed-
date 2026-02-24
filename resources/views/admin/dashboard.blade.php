@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-2 pb-1 mb-2 mb-md-3 border-bottom">
    <h1 class="h2 d-none d-md-block" id="dashboard-title">{{ Auth::user()->isManager() ? 'Manager Dashboard' : 'Admin Dashboard' }}</h1>
    <div class="ms-auto">
        <button class="btn btn-sm btn-outline-info" onclick="startTour()">
            <i class="bi bi-question-circle"></i> Help
        </button>
    </div>
</div>

<!-- Stats Cards -->
<div class="row g-2 mb-3 mb-md-4" id="stats-cards">
    <div class="col-md-3">
        <div class="card text-white bg-gradient-primary mb-3 shadow border-0 h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title text-uppercase small opacity-75 fw-bold">Total Buildings</h6>
                        <p class="card-text display-6 fw-bold mb-0">{{ $stats['total_buildings'] }}</p>
                    </div>
                    <div class="bg-white bg-opacity-25 rounded-circle p-3 d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                        <i class="bi bi-building fs-3"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-white bg-gradient-success mb-3 shadow border-0 h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title text-uppercase small opacity-75 fw-bold">Active Tenants</h6>
                        <p class="card-text display-6 fw-bold mb-0">{{ $stats['total_tenants'] }}</p>
                    </div>
                    <div class="bg-white bg-opacity-25 rounded-circle p-3 d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                        <i class="bi bi-people-fill fs-3"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-white bg-gradient-info mb-3 shadow border-0 h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title text-uppercase small opacity-75 fw-bold">YTD Revenue</h6>
                        @php
                            $formattedRevenue = number_format($stats['paid_total'], 0);
                            $revLen = strlen($formattedRevenue);
                            // Base size display-6, reduces as length increases
                            $revClass = 'display-6';
                            if ($revLen > 9) $revClass = 'fs-2';
                        @endphp
                        <p class="card-text {{ $revClass }} fw-bold mb-0">₦{{ $formattedRevenue }}</p>
                    </div>
                    <div class="bg-white bg-opacity-25 rounded-circle p-3 d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                        <i class="bi bi-cash-stack fs-3"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-white bg-gradient-warning mb-3 shadow border-0 h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title text-uppercase small opacity-75 fw-bold">Overdue Rents</h6>
                        <p class="card-text display-6 fw-bold mb-0">{{ $stats['overdue_count'] }}</p>
                    </div>
                    <div class="bg-white bg-opacity-25 rounded-circle p-3 d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                        <i class="bi bi-exclamation-triangle-fill fs-3"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-2 mb-3 mb-md-4" id="revenue-section">
    <!-- Revenue Chart -->
    <div class="col-md-12">
        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <h5 class="mb-0">Revenue Overview (Last 6 Months)</h5>
            </div>
            <div class="card-body">
                <canvas id="revenueChart" height="100"></canvas>
            </div>
        </div>
    </div>
</div>

<div class="row g-2">
    <!-- Recent Payments -->
    <div class="col-md-6" id="recent-payments-section">
        <div class="card shadow-sm">
            <div class="card-header bg-light">
                <h5 class="mb-0">Recent Payments</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Tenant</th>
                                <th>Amount</th>
                                <th>Unit</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentPayments as $payment)
                            <tr>
                                <td>{{ $payment->payment_date->format('M d, Y') }}</td>
                                <td>{{ $payment->rent->tenant->full_name }}</td>
                                <td class="text-success">₦{{ number_format($payment->amount, 2) }}</td>
                                <td>
                                    @if($payment->rent->unit)
                                        {{ $payment->rent->unit->unit_number }}
                                    @else
                                        <span class="text-muted fst-italic">Unit Removed</span>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="text-center py-3">No recent payments found.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer text-end">
                <a href="{{ route('payments.index') }}" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
        </div>
    </div>

    <!-- Overdue Rents -->
    <div class="col-md-6" id="overdue-rents-section">
        <div class="card shadow-sm">
            <div class="card-header bg-light">
                <h5 class="mb-0">Overdue Rents</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Tenant</th>
                                <th>Unit</th>
                                <th>Due Amount</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($overdueRents as $rent)
                            <tr>
                                <td>{{ $rent->tenant->full_name }}</td>
                                <td>{{ $rent->unit->building->name }} - {{ $rent->unit->unit_number }}</td>
                                <td class="text-danger fw-bold">₦{{ number_format($rent->balance, 2) }}</td>
                                <td>
                                    <a href="{{ route('payments.create', ['rent_id' => $rent->id]) }}" class="btn btn-sm btn-primary">Pay</a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="text-center py-3">No overdue rents.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer text-end">
                <a href="{{ route('rents.index') }}" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
        </div>
    </div>
</div>

@if(Auth::user()->isManager())
<div class="row g-2 mt-3" id="manager-announcements-section">
    <div class="col-md-12">
        <div class="card shadow-sm">
            <div class="card-header bg-light">
                <h5 class="mb-0">Announcements For Your Buildings</h5>
            </div>
            <div class="card-body">
                <ul class="list-group">
                    @forelse($managerAnnouncements as $ann)
                        <li class="list-group-item">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <strong>{{ $ann->title }}</strong>
                                    <div class="text-muted small">
                                        {{ $ann->created_at->format('M d, Y H:i') }} 
                                        — {{ $ann->manager->name }} 
                                        — {{ $ann->building->name }}
                                    </div>
                                    <div>{{ $ann->content }}</div>
                                </div>
                                <form action="{{ route('buildings.announcements.dismiss', ['building' => $ann->building_id, 'announcement' => $ann->id]) }}" method="POST" onsubmit="return confirm('Remove this announcement from your view only?');">
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
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        var ctx = document.getElementById('revenueChart').getContext('2d');
        var revenueChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: @json($chartLabels),
                datasets: [{
                    label: 'Revenue Collected (₦)',
                    data: @json($chartData),
                    backgroundColor: 'rgba(124, 58, 237, 0.1)',
                    borderColor: '#7c3aed',
                    borderWidth: 2,
                    tension: 0.3,
                    fill: true,
                    pointBackgroundColor: '#fff',
                    pointBorderColor: '#7c3aed',
                    pointBorderWidth: 2,
                    pointRadius: 4,
                    pointHoverRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        padding: 12,
                        titleFont: {
                            size: 14,
                            family: "'Nunito', sans-serif"
                        },
                        bodyFont: {
                            size: 13,
                            family: "'Nunito', sans-serif"
                        },
                        displayColors: false,
                        callbacks: {
                            label: function(context) {
                                var label = context.dataset.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                if (context.parsed.y !== null) {
                                    label += new Intl.NumberFormat('en-NG', { style: 'currency', currency: 'NGN' }).format(context.parsed.y);
                                }
                                return label;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            borderDash: [2, 4],
                            color: '#f0f0f0',
                            drawBorder: false
                        },
                        ticks: {
                            font: {
                                family: "'Nunito', sans-serif"
                            },
                            callback: function(value, index, values) {
                                if (value >= 1000000) return '₦' + (value/1000000).toFixed(1) + 'M';
                                if (value >= 1000) return '₦' + (value/1000).toFixed(0) + 'k';
                                return '₦' + value;
                            }
                        }
                    },
                    x: {
                        grid: {
                            display: false,
                            drawBorder: false
                        },
                        ticks: {
                            font: {
                                family: "'Nunito', sans-serif"
                            }
                        }
                    }
                }
            }
        });
    });
</script>
@endpush
