@extends('layouts.app')

@section('content')
@if(auth()->user()->isAccountant() && !auth()->user()->isAdmin())
<style>
    #reportRoot.report-protect {
        user-select: none;
        -webkit-user-select: none;
    }
    #reportRoot.report-protect::after {
        content: "CONFIDENTIAL • {{ auth()->user()->email }} • {{ now()->format('Y-m-d H:i') }}";
        position: fixed;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%) rotate(-20deg);
        font-size: 42px;
        font-weight: 700;
        color: rgba(0, 0, 0, 0.10);
        pointer-events: none;
        z-index: 9999;
        white-space: nowrap;
    }
    body.report-obscured #reportRoot {
        filter: blur(12px);
    }
    @media print {
        #reportRoot {
            display: none !important;
        }
        body::before {
            content: "Printing disabled";
            display: block;
            padding: 40px;
            font-size: 22px;
        }
    }
</style>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const root = document.getElementById('reportRoot');
        if (root) {
            root.classList.add('report-protect');
        }

        document.addEventListener('contextmenu', function (e) {
            if (root && root.contains(e.target)) {
                e.preventDefault();
            }
        }, true);

        document.addEventListener('keydown', function (e) {
            const key = (e.key || '').toLowerCase();
            if ((e.ctrlKey || e.metaKey) && (key === 'p' || key === 's')) {
                e.preventDefault();
            }
        }, true);

        document.addEventListener('visibilitychange', function () {
            if (document.visibilityState !== 'visible') {
                document.body.classList.add('report-obscured');
                return;
            }
            document.body.classList.remove('report-obscured');
        });
    });
</script>
@endif

<div class="container-fluid py-4" id="reportRoot">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h2 mb-0">Accounting Module</h1>
            @if(auth()->user()->isAccountant())
                <p class="text-muted small mb-0">Accountant Dashboard</p>
            @endif
        </div>
        <div class="btn-toolbar">
            @if(auth()->user()->isAdmin())
            <a href="{{ route('accounting.audit-trail') }}" class="btn btn-outline-secondary btn-sm me-2">
                <i class="bi bi-journal-text me-1"></i> Audit Trail
            </a>
            <a href="{{ route('accounting.export') }}" target="_blank" class="btn btn-success btn-sm me-2">
                <i class="bi bi-file-earmark-spreadsheet me-1"></i> Export CSV
            </a>
            <a href="{{ route('accounting.export-pdf') }}" target="_blank" class="btn btn-danger btn-sm">
                <i class="bi bi-file-earmark-pdf me-1"></i> Export PDF
            </a>
            @endif
            @if(auth()->user()->isAccountant() && !auth()->user()->isAdmin())
            <a href="{{ route('accounting.report') }}" class="btn btn-outline-primary btn-sm">
                <i class="bi bi-eye me-1"></i> View Report
            </a>
            @endif
        </div>
    </div>

    <!-- Admin: Pending Edit Requests -->
    @if(isset($pendingEditRequests) && $pendingEditRequests->count() > 0)
    <div class="alert alert-warning shadow-sm border-warning mb-4">
        <h5 class="alert-heading h6 fw-bold"><i class="bi bi-exclamation-circle-fill me-2"></i> Pending Edit Requests ({{ $pendingEditRequests->count() }})</h5>
        <div class="table-responsive">
            <table class="table table-sm table-borderless mb-0">
                <thead>
                    <tr class="text-muted small text-uppercase">
                        <th>User</th>
                        <th>Record</th>
                        <th>Reason</th>
                        <th>Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($pendingEditRequests as $req)
                    <tr>
                        <td class="fw-bold">{{ $req->user->name }}</td>
                        <td>
                            <span class="badge bg-secondary">{{ $req->accountingEntry->building->name }}</span>
                            <span class="small text-muted">#{{ $req->accountingEntry->id }}</span>
                        </td>
                        <td class="text-wrap" style="max-width: 300px;">{{ $req->reason }}</td>
                        <td class="small">{{ $req->created_at->diffForHumans() }}</td>
                        <td>
                            <form action="{{ route('accounting.handle-edit-request', $req->id) }}" method="POST" class="d-inline">
                                @csrf
                                <input type="hidden" name="action" value="approve">
                                <button type="submit" class="btn btn-success btn-sm py-0 px-2">Approve</button>
                            </form>
                            <form action="{{ route('accounting.handle-edit-request', $req->id) }}" method="POST" class="d-inline ms-1">
                                @csrf
                                <input type="hidden" name="action" value="reject">
                                <button type="submit" class="btn btn-outline-danger btn-sm py-0 px-2">Reject</button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    <!-- Reviewer: My Recent Requests -->
    @if(isset($myEditRequests) && $myEditRequests->count() > 0)
    <div class="card mb-4 border-0 shadow-sm bg-light">
        <div class="card-body py-2">
            <div class="d-flex align-items-center justify-content-between" data-bs-toggle="collapse" data-bs-target="#myRequestsCollapse" style="cursor: pointer;">
                <h6 class="mb-0 text-muted"><i class="bi bi-clock-history me-2"></i> My Recent Edit Requests</h6>
                <i class="bi bi-chevron-down"></i>
            </div>
            <div class="collapse mt-2" id="myRequestsCollapse">
                <table class="table table-sm mb-0">
                    <tbody>
                        @foreach($myEditRequests as $req)
                        <tr>
                            <td>Record #{{ $req->accountingEntry->id }}</td>
                            <td class="text-muted small">{{ Str::limit($req->reason, 40) }}</td>
                            <td>
                                @if($req->status === 'pending')
                                    <span class="badge bg-warning text-dark">Pending</span>
                                @elseif($req->status === 'approved')
                                    <span class="badge bg-success">Approved</span>
                                    @if($req->expires_at > now())
                                        <small class="text-success ms-1">(Expires {{ $req->expires_at->diffForHumans() }})</small>
                                    @else
                                        <small class="text-danger ms-1">(Expired)</small>
                                    @endif
                                @else
                                    <span class="badge bg-danger">Rejected</span>
                                @endif
                            </td>
                            <td class="text-end small">{{ $req->created_at->diffForHumans() }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif

    <!-- Summary Cards (Exact Figures) -->
    <div class="row g-3 mb-4">
        @foreach($buildings as $building)
        @php
            $stats = $summaries->get($building->id);
            $income = $stats['income'] ?? 0;
            $expense = $stats['expense'] ?? 0;
            $cashflow = $stats['cashflow'] ?? 0;
        @endphp
        <div class="col-md-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0 text-primary">{{ $building->name }}</h5>
                        @if(auth()->user()->isAdmin())
                    <div class="dropdown">
                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-download"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><h6 class="dropdown-header">Export {{ $building->name }}</h6></li>
                            <li>
                                <a class="dropdown-item" href="{{ route('accounting.export', ['building_id' => $building->id]) }}" target="_blank">
                                    <i class="bi bi-file-earmark-spreadsheet me-2 text-success"></i> CSV Report
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="{{ route('accounting.export-pdf', ['building_id' => $building->id]) }}" target="_blank">
                                    <i class="bi bi-file-earmark-pdf me-2 text-danger"></i> PDF Report
                                </a>
                            </li>
                        </ul>
                    </div>
                        @endif
                        @if(auth()->user()->isAccountant() && !auth()->user()->isAdmin())
                        <a href="{{ route('accounting.report', ['building_id' => $building->id]) }}" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-eye"></i>
                        </a>
                        @endif
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted small">Total Income:</span>
                        <span class="fw-bold text-success">₦{{ number_format($income, 2) }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted small">Total Expenses:</span>
                        <span class="fw-bold text-danger">₦{{ number_format($expense, 2) }}</span>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="text-muted small">Net Cash Flow:</span>
                        <span class="fs-5 fw-bold {{ $cashflow >= 0 ? 'text-success' : 'text-danger' }}">
                            ₦{{ number_format($cashflow, 2) }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    <!-- Spreadsheet Interface -->
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white p-0">
            <ul class="nav nav-tabs border-bottom-0" id="accountingTabs" role="tablist">
                <!-- Logic to set active tab based on role -->
                @php
                    $defaultTab = 'all';
                @endphp

                <li class="nav-item" role="presentation">
                    <button class="nav-link {{ $defaultTab === 'all' ? 'active' : '' }} px-4 py-3" id="all-tab" data-bs-toggle="tab" data-bs-target="#all" type="button" role="tab">
                        <i class="bi bi-table me-1"></i> All Records
                    </button>
                </li>
                @if(auth()->user()->isAdmin() || auth()->user()->isAccountant())
                <li class="nav-item" role="presentation">
                    <button class="nav-link px-4 py-3 text-success" id="income-tab" data-bs-toggle="tab" data-bs-target="#income" type="button" role="tab">
                        <i class="bi bi-plus-circle me-1"></i> Record Income
                    </button>
                </li>
                @endif
                @if(auth()->user()->isAdmin() || auth()->user()->isAccountant())
                <li class="nav-item" role="presentation">
                    <button class="nav-link {{ $defaultTab === 'expense' ? 'active' : '' }} px-4 py-3 text-danger" id="expense-tab" data-bs-toggle="tab" data-bs-target="#expense" type="button" role="tab">
                        <i class="bi bi-dash-circle me-1"></i> Record Expense
                    </button>
                </li>
                @endif
            </ul>
        </div>
        <div class="card-body p-0">
            <div class="tab-content" id="accountingTabsContent">
                
                <!-- All Records Tab -->
                <div class="tab-pane fade {{ $defaultTab === 'all' ? 'show active' : '' }} p-3" id="all" role="tabpanel">
                    <!-- Search & Filter Bar -->
                    <form action="{{ route('accounting.index') }}" method="GET" class="mb-3">
                        <div class="row g-2">
        <div class="col-md-4">
            <input type="text" name="search" id="accountingSearch" class="form-control" placeholder="Search description, category, building..." value="{{ request('search') }}">
        </div>
        <div class="col-md-3">
            <select name="building_id" class="form-select" onchange="this.form.submit()">
                                    <option value="">All Buildings</option>
                                    @foreach($buildings as $building)
                                        <option value="{{ $building->id }}" {{ request('building_id') == $building->id ? 'selected' : '' }}>
                                            {{ $building->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-primary w-100">Filter</button>
                            </div>
                            <div class="col-md-2">
                                <a href="{{ route('accounting.index') }}" class="btn btn-outline-secondary w-100">Reset</a>
                            </div>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" style="min-width: 1000px;">
                            <thead class="bg-light">
                                <tr>
                                    <th>Date</th>
                                    <th>Building</th>
                                    <th>Type</th>
                                    <th>Category</th>
                                    <th>Details</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($entries as $entry)
                                <tr>
                                    <td>{{ $entry->entry_date->format('d M Y') }}</td>
                                    <td>{{ $entry->building->name }}</td>
                                    <td>
                                        <span class="badge {{ $entry->type === 'income' ? 'bg-success' : 'bg-danger' }}">
                                            {{ ucfirst($entry->type) }}
                                        </span>
                                    </td>
                                    <td>{{ $entry->category }}</td>
                                    <td>
                                        <div class="small text-wrap" style="max-width: 300px;">
                                            @if($entry->type === 'income')
                                                <strong>Tenant:</strong> {{ $entry->extra_details['tenant_name'] ?? 'N/A' }}<br>
                                                <strong>Unit:</strong> {{ $entry->extra_details['property_id'] ?? 'N/A' }}
                                            @else
                                                {{ $entry->description ?? 'No description' }}
                                            @endif
                                        </div>
                                    </td>
                                    <td class="fw-bold {{ $entry->type === 'income' ? 'text-success' : 'text-danger' }}">
                                        {{ $entry->type === 'income' ? '+' : '-' }}₦{{ number_format($entry->amount, 2) }}
                                    </td>
                                    <td>
                                        @if($entry->status === 'finalized')
                                            <span class="badge bg-primary"><i class="bi bi-lock-fill me-1"></i> Finalized</span>
                                        @else
                                            <span class="badge bg-warning text-dark">Pending</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            @if(!$entry->isLocked())
                                                <form action="{{ route('accounting.finalize', $entry) }}" method="POST" onsubmit="return confirm('Are you sure? This will lock the record.')">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit" class="btn btn-outline-primary" title="Finalize & Lock">
                                                        <i class="bi bi-check-lg"></i>
                                                    </button>
                                                </form>
                                                <button type="button" class="btn btn-outline-secondary ms-1" data-bs-toggle="modal" data-bs-target="#editEntryModal{{ $entry->id }}" title="Edit">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                            @elseif(auth()->user()->isAdmin())
                                                <form action="{{ route('accounting.unlock', $entry) }}" method="POST" onsubmit="return confirm('Unlock this record?')">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit" class="btn btn-outline-warning" title="Unlock">
                                                        <i class="bi bi-unlock-fill"></i>
                                                    </button>
                                                </form>
                                                <button type="button" class="btn btn-outline-secondary ms-1" data-bs-toggle="modal" data-bs-target="#editEntryModal{{ $entry->id }}" title="Edit">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                            @else
                                                @php
                                                    $activeRequest = \App\Models\EditRequest::where('accounting_entry_id', $entry->id)
                                                        ->where('user_id', auth()->id())
                                                        ->where('status', 'approved')
                                                        ->where('expires_at', '>', now())
                                                        ->exists();
                                                @endphp
                                                
                                                @if($activeRequest)
                                                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#editEntryModal{{ $entry->id }}">
                                                        <i class="bi bi-pencil-fill"></i> Edit Now
                                                    </button>
                                                @else
                                                    <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#editRequestModal{{ $entry->id }}">
                                                        <i class="bi bi-pencil-square"></i> Request Edit
                                                    </button>
                                                @endif
                                            @endif
                                        </div>

                                        <!-- Edit Request Modal -->
                                        @if($entry->isLocked() && !auth()->user()->isAdmin())
                                        <div class="modal fade" id="editRequestModal{{ $entry->id }}" tabindex="-1" aria-hidden="true">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <form action="{{ route('accounting.request-edit', $entry) }}" method="POST">
                                                        @csrf
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">Request Edit Access</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <div class="modal-body text-start">
                                                            <div class="mb-3">
                                                                <label class="form-label">Reason for edit</label>
                                                                <textarea name="reason" class="form-control" rows="3" required placeholder="Why do you need to edit this?"></textarea>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                            <button type="submit" class="btn btn-primary">Submit Request</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                        @endif

                                        <!-- Edit Entry Modal -->
                                        <div class="modal fade" id="editEntryModal{{ $entry->id }}" tabindex="-1" aria-hidden="true">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <form action="{{ route('accounting.update', $entry) }}" method="POST">
                                                        @csrf
                                                        @method('PATCH')
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">Edit Entry #{{ $entry->id }}</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <div class="modal-body text-start">
                                                            <div class="mb-3">
                                                                <label class="form-label">Amount</label>
                                                                <input type="number" step="0.01" name="amount" class="form-control" value="{{ $entry->amount }}" required>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-label">Category</label>
                                                                <input type="text" name="category" class="form-control" value="{{ $entry->category }}" required>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-label">Date</label>
                                                                <input type="date" name="entry_date" class="form-control" value="{{ $entry->entry_date->format('Y-m-d') }}" required>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-label">Description</label>
                                                                <textarea name="description" class="form-control" rows="2">{{ $entry->description }}</textarea>
                                                            </div>
                                                            @if($entry->type === 'income')
                                                            <div class="mb-3">
                                                                <label class="form-label">Tenant Name</label>
                                                                <input type="text" name="tenant_name" class="form-control" value="{{ $entry->extra_details['tenant_name'] ?? '' }}">
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-label">Unit / Property ID</label>
                                                                <input type="text" name="property_id" class="form-control" value="{{ $entry->extra_details['property_id'] ?? '' }}">
                                                            </div>
                                                            @endif
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                            <button type="submit" class="btn btn-primary">Save Changes</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="8" class="text-center py-5 text-muted">
                                        <i class="bi bi-inbox fs-1 d-block mb-3"></i>
                                        No accounting records found.
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-4">
                        {{ $entries->withQueryString()->links() }}
                    </div>
                </div>

                <!-- Record Income Tab -->
                @if(auth()->user()->isAdmin() || auth()->user()->isAccountant())
                <div class="tab-pane fade p-4" id="income" role="tabpanel">
                    <div class="row justify-content-center">
                        <div class="col-md-8">
                            <h4 class="mb-4 text-success"><i class="bi bi-plus-circle me-2"></i> Record New Income</h4>
                            <form action="{{ route('accounting.store.income') }}" method="POST" class="row g-3 shadow-sm p-4 bg-light rounded border">
                                @csrf
                                <div class="col-md-6">
                                    <label class="form-label">Building <span class="text-danger">*</span></label>
                                    <select name="building_id" class="form-select" required>
                                        <option value="">Select Building</option>
                                        @foreach($buildings as $building)
                                            <option value="{{ $building->id }}">{{ $building->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Amount (₦) <span class="text-danger">*</span></label>
                                    <input type="number" step="0.01" name="amount" class="form-control" required placeholder="0.00">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Category <span class="text-danger">*</span></label>
                                    <select name="category" class="form-select" required>
                                        <option value="Rent">Rent</option>
                                        <option value="Service Charge">Service Charge</option>
                                        <option value="Utility Reimbursement">Utility Reimbursement</option>
                                        <option value="Penalty/Fee">Penalty/Fee</option>
                                        <option value="Other">Other</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Entry Date <span class="text-danger">*</span></label>
                                    <input type="date" name="entry_date" class="form-control" required value="{{ date('Y-m-d') }}">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Tenant Name <span class="text-danger">*</span></label>
                                    <input type="text" name="tenant_name" class="form-control" required placeholder="Enter full name">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Unit / Property ID <span class="text-danger">*</span></label>
                                    <input type="text" name="property_id" class="form-control" required placeholder="e.g. APT-101">
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Description / Notes</label>
                                    <textarea name="description" class="form-control" rows="2" placeholder="Additional details..."></textarea>
                                </div>
                                <div class="col-12 mt-4 text-center d-none d-md-block">
                                    <button type="submit" class="btn btn-success px-5 py-2">
                                        <i class="bi bi-save me-2"></i> Save Income Record
                                    </button>
                                </div>
                                <div class="col-12 mt-4 d-md-none text-center">
                                    <div class="alert alert-warning py-2 small">
                                        <i class="bi bi-exclamation-triangle-fill me-1"></i> 
                                        Desktop Only
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                @endif

                <!-- Record Expense Tab -->
                @if(auth()->user()->isAdmin() || auth()->user()->isAccountant())
                <div class="tab-pane fade {{ $defaultTab === 'expense' ? 'show active' : '' }} p-4" id="expense" role="tabpanel">
                    <div class="row justify-content-center">
                        <div class="col-md-8">
                            <h4 class="mb-4 text-danger"><i class="bi bi-dash-circle me-2"></i> Record New Expense</h4>
                            <form action="{{ route('accounting.store.expense') }}" method="POST" class="row g-3 shadow-sm p-4 bg-light rounded border">
                                @csrf
                                <div class="col-md-6">
                                    <label class="form-label">Building <span class="text-danger">*</span></label>
                                    <select name="building_id" class="form-select" required>
                                        <option value="">Select Building</option>
                                        @foreach($buildings as $building)
                                            <option value="{{ $building->id }}">{{ $building->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Amount (₦) <span class="text-danger">*</span></label>
                                    <input type="number" step="0.01" name="amount" class="form-control" required placeholder="0.00">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Category <span class="text-danger">*</span></label>
                                    <select name="category" class="form-select" required>
                                        <option value="Maintenance">Maintenance</option>
                                        <option value="Utilities">Utilities</option>
                                        <option value="Cleaning">Cleaning</option>
                                        <option value="Security">Security</option>
                                        <option value="Management Fee">Management Fee</option>
                                        <option value="Taxes">Taxes</option>
                                        <option value="Insurance">Insurance</option>
                                        <option value="Other">Other</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Entry Date <span class="text-danger">*</span></label>
                                    <input type="date" name="entry_date" class="form-control" required value="{{ date('Y-m-d') }}">
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Description / Notes <span class="text-danger">*</span></label>
                                    <textarea name="description" class="form-control" rows="3" required placeholder="Detailed description of the expense..."></textarea>
                                </div>
                                <div class="col-12 mt-4 text-center d-none d-md-block">
                                    <button type="submit" class="btn btn-danger px-5 py-2">
                                        <i class="bi bi-save me-2"></i> Save Expense Record
                                    </button>
                                </div>
                                <div class="col-12 mt-4 d-md-none text-center">
                                    <div class="alert alert-warning py-2 small">
                                        <i class="bi bi-exclamation-triangle-fill me-1"></i> 
                                        Desktop Only
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

<style>
    .nav-tabs .nav-link {
        color: #6c757d;
        border: none;
        border-bottom: 3px solid transparent;
        font-weight: 600;
    }
    .nav-tabs .nav-link.active {
        color: #0d6efd;
        background: transparent;
        border-bottom-color: #0d6efd;
    }
    .nav-tabs #income-tab.active {
        color: #198754;
        border-bottom-color: #198754;
    }
    .nav-tabs #expense-tab.active {
        color: #dc3545;
        border-bottom-color: #dc3545;
    }
</style>

<style>
.report-protect-overlay{position:fixed;inset:0;background:rgba(0,0,0,.25);backdrop-filter:blur(4px);display:none;z-index:9999}
.report-blurred #reportRoot{filter:blur(6px);pointer-events:none}
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('accountingSearch');
        const table = document.querySelector('.table-responsive table');
        const rows = table.querySelectorAll('tbody tr');
        const noResultsRow = document.createElement('tr');
        
        // Setup No Results Row
        noResultsRow.innerHTML = '<td colspan="8" class="text-center py-4 text-muted"><i class="bi bi-search fs-1 d-block mb-2"></i>No matching records found.</td>';
        noResultsRow.style.display = 'none';
        table.querySelector('tbody').appendChild(noResultsRow);

        searchInput.addEventListener('keyup', function() {
            const term = this.value.toLowerCase().trim();
            let visibleCount = 0;

            rows.forEach(row => {
                if(row === noResultsRow) return;

                const text = row.textContent.toLowerCase();
                if(text.includes(term)) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });

            noResultsRow.style.display = visibleCount === 0 ? '' : 'none';
        });

        const root = document.getElementById('reportRoot') || document.querySelector('.container-fluid');
        const overlay = document.createElement('div');
        overlay.className = 'report-protect-overlay';
        document.body.appendChild(overlay);
        function applyBlur(){document.body.classList.add('report-blurred');overlay.style.display='block'}
        function removeBlur(){document.body.classList.remove('report-blurred');overlay.style.display='none'}
        window.addEventListener('blur', applyBlur);
        document.addEventListener('visibilitychange', function(){if(document.hidden){applyBlur()}else{removeBlur()}});
        window.addEventListener('focus', removeBlur);
        document.addEventListener('contextmenu', function(e){if(root && root.contains(e.target)) e.preventDefault()});
        document.addEventListener('keydown', function(e){
            const k = e.key.toLowerCase();
            if(k==='printscreen') e.preventDefault();
            if(e.ctrlKey && k==='p') e.preventDefault();
            if(e.ctrlKey && e.shiftKey && (k==='s' || k==='i')) e.preventDefault();
        });
    });
</script>
@endsection
