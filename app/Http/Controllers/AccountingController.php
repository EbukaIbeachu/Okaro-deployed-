<?php

namespace App\Http\Controllers;

use App\Models\AccountingEntry;
use App\Models\AuditLog;
use App\Models\Building;
use App\Models\EditRequest;
use App\Models\Payment;
use App\Models\Rent;
use App\Traits\AccountingAuditable;
use Barryvdh\DomPDF\Facade\Pdf as PdfFacade;
use Barryvdh\DomPDF\ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AccountingController extends Controller
{
    use AccountingAuditable;

    public function __construct()
    {
        $this->middleware('auth');

        $this->middleware(function ($request, $next) {
            $user = auth()->user();
            if (! $user->isAdmin() && ! $user->isAccountant()) {
                abort(403, 'Unauthorized access to Accounting Module.');
            }

            return $next($request);
        });

        // Desktop only for input/edit actions
        $this->middleware('desktop.only')->only(['storeIncome', 'storeExpense', 'update', 'finalize', 'requestEdit']);
    }

    /**
     * Display the accounting spreadsheet view
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        $query = AccountingEntry::with(['building', 'creator']);

        // Filter by building if provided
        if ($request->has('building_id') && $request->building_id) {
            $query->where('building_id', $request->building_id);
        }

        // Search functionality
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                    ->orWhere('category', 'like', "%{$search}%")
                    ->orWhere('extra_details', 'like', "%{$search}%");
            });
        }

        $entries = $query->latest('entry_date')->latest('id')->paginate(50);
        $buildings = Building::all();

        // Accurate Building-level summaries (Including Rent Payments)
        // 1. Get Accounting Entries Summaries
        $accountingSummaries = AccountingEntry::select('building_id', 'type', DB::raw('SUM(amount) as total'))
            ->groupBy('building_id', 'type')
            ->get();

        // 2. Get Payment Summaries (Rent Income)
        $paymentSummaries = Payment::where('payments.status', 'COMPLETED')
            ->join('rents', 'payments.rent_id', '=', 'rents.id')
            ->join('units', 'rents.unit_id', '=', 'units.id')
            ->select('units.building_id', DB::raw('SUM(payments.amount) as total'))
            ->groupBy('units.building_id')
            ->pluck('total', 'building_id');

        // 3. Merge into a structure keyed by building_id
        $summaries = $buildings->mapWithKeys(function ($building) use ($accountingSummaries, $paymentSummaries) {
            $accIncome = $accountingSummaries->where('building_id', $building->id)->where('type', 'income')->sum('total');
            $accExpense = $accountingSummaries->where('building_id', $building->id)->where('type', 'expense')->sum('total');
            $rentIncome = $paymentSummaries[$building->id] ?? 0;

            return [$building->id => [
                'income' => $accIncome + $rentIncome,
                'expense' => $accExpense,
                'cashflow' => ($accIncome + $rentIncome) - $accExpense,
            ]];
        });

        // Fetch pending edit requests for Admins
        $pendingEditRequests = collect();
        if ($user->isAdmin()) {
            $pendingEditRequests = EditRequest::where('status', 'pending')
                ->with(['user', 'accountingEntry.building'])
                ->latest()
                ->get();
        }

        // Fetch recent requests for Reviewers/Managers to show status
        $myEditRequests = collect();
        if (! $user->isAdmin()) {
            $myEditRequests = EditRequest::where('user_id', $user->id)
                ->with(['accountingEntry.building'])
                ->latest()
                ->take(5)
                ->get();
        }

        return view('accounting.index', compact('entries', 'buildings', 'summaries', 'pendingEditRequests', 'myEditRequests'));
    }

    public function report(Request $request)
    {
        $user = auth()->user();
        $buildingId = $request->query('building_id');

        $query = Building::with(['units', 'rents.payments', 'rents.tenant']);
        if ($buildingId) {
            $query->where('id', $buildingId);
        }

        $buildings = $query->get();

        $accountingSummaries = AccountingEntry::select('building_id', 'type', DB::raw('SUM(amount) as total'))
            ->groupBy('building_id', 'type')
            ->get();

        $paymentSummaries = Payment::where('payments.status', 'COMPLETED')
            ->join('rents', 'payments.rent_id', '=', 'rents.id')
            ->join('units', 'rents.unit_id', '=', 'units.id')
            ->select('units.building_id', DB::raw('SUM(payments.amount) as total'))
            ->groupBy('units.building_id')
            ->pluck('total', 'building_id');

        $singleBuilding = $buildingId && $buildings->count() === 1;

        if ($singleBuilding) {
            $building = $buildings->first();

            $expenses = AccountingEntry::where('building_id', $building->id)
                ->where('type', 'expense')
                ->orderBy('entry_date', 'desc')
                ->get();

            $rents = [];
            foreach ($building->rents as $rent) {
                if ($rent->status !== 'ACTIVE') {
                    continue;
                }

                $unit = $building->units->firstWhere('id', $rent->unit_id);
                $unitLabel = $unit
                    ? $unit->unit_number.($unit->floor ? ' (Floor '.$unit->floor.')' : '')
                    : 'Unknown Unit';

                $rents[] = [
                    'unit' => $unitLabel,
                    'tenant' => $rent->tenant ? $rent->tenant->full_name : 'No Tenant Assigned',
                    'annual_rent' => $rent->annual_amount,
                    'paid' => $rent->total_paid,
                    'outstanding' => $rent->balance,
                    'start_date' => $rent->start_date,
                ];
            }

            $accIncome = $accountingSummaries->where('building_id', $building->id)->where('type', 'income')->sum('total');
            $accExpense = $accountingSummaries->where('building_id', $building->id)->where('type', 'expense')->sum('total');
            $rentIncome = $paymentSummaries[$building->id] ?? 0;
            $totalIncome = $accIncome + $rentIncome;
            $cashflow = $totalIncome - $accExpense;

            $totalOutstanding = 0;
            foreach ($rents as $r) {
                if ($r['outstanding'] > 0) {
                    $totalOutstanding += $r['outstanding'];
                }
            }

            $summary = [
                'income' => $totalIncome,
                'expense' => $accExpense,
                'cashflow' => $cashflow,
                'outstanding' => $totalOutstanding,
            ];

            return response()
                ->view('accounting.view-report', [
                    'mode' => 'single',
                    'building' => $building,
                    'rents' => $rents,
                    'expenses' => $expenses,
                    'summary' => $summary,
                    'date' => now()->format('F d, Y'),
                    'generated_by' => $user ? $user->name : 'System',
                ])
                ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
                ->header('Pragma', 'no-cache')
                ->header('X-Frame-Options', 'DENY')
                ->header('X-Content-Type-Options', 'nosniff')
                ->header('Referrer-Policy', 'no-referrer');
        }

        $reportData = [];
        foreach ($buildings as $building) {
            $accIncome = $accountingSummaries->where('building_id', $building->id)->where('type', 'income')->sum('total');
            $accExpense = $accountingSummaries->where('building_id', $building->id)->where('type', 'expense')->sum('total');
            $rentIncome = $paymentSummaries[$building->id] ?? 0;

            $totalIncome = $accIncome + $rentIncome;
            $cashflow = $totalIncome - $accExpense;

            $outstanding = 0;
            $tenantsWithArrears = [];

            foreach ($building->rents as $rent) {
                if ($rent->status === 'ACTIVE' && $rent->balance > 0) {
                    $outstanding += $rent->balance;

                    $unitLabel = 'Unknown Unit';
                    $unit = $building->units->firstWhere('id', $rent->unit_id);
                    if ($unit) {
                        $unitLabel = $unit->unit_number.($unit->floor ? ' (Floor '.$unit->floor.')' : '');
                    }

                    $tenantsWithArrears[] = [
                        'unit' => $unitLabel,
                        'tenant' => $rent->tenant ? $rent->tenant->full_name : 'Unknown',
                        'amount' => $rent->balance,
                    ];
                }
            }

            $reportData[] = [
                'building' => $building,
                'financials' => [
                    'income' => $totalIncome,
                    'expense' => $accExpense,
                    'cashflow' => $cashflow,
                    'outstanding' => $outstanding,
                ],
                'arrears_details' => $tenantsWithArrears,
            ];
        }

        return response()
            ->view('accounting.view-report', [
                'mode' => 'summary',
                'reportData' => $reportData,
                'date' => now()->format('F d, Y'),
                'generated_by' => $user ? $user->name : 'System',
            ])
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache')
            ->header('X-Frame-Options', 'DENY')
            ->header('X-Content-Type-Options', 'nosniff')
            ->header('Referrer-Policy', 'no-referrer');
    }

    /**
     * Update an existing entry (Admin or Approved Edit Request)
     */
    public function update(Request $request, AccountingEntry $entry)
    {
        $user = auth()->user();

        // Check permissions
        $canEdit = $user->isAdmin();

        if (! $canEdit) {
            // Check for active approved edit request
            $activeRequest = EditRequest::where('accounting_entry_id', $entry->id)
                ->where('user_id', $user->id)
                ->where('status', 'approved')
                ->where('expires_at', '>', now())
                ->exists();

            if ($activeRequest) {
                $canEdit = true;
            }
        }

        if (! $canEdit) {
            abort(403, 'You do not have permission to edit this record.');
        }

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0',
            'category' => 'required|string|max:100',
            'entry_date' => 'required|date',
            'description' => 'nullable|string',
            'tenant_name' => 'nullable|string|max:200',
            'property_id' => 'nullable|string|max:100',
        ]);

        $oldValues = $entry->toArray();

        $updateData = [
            'amount' => $validated['amount'],
            'category' => $validated['category'],
            'entry_date' => $validated['entry_date'],
            'description' => $validated['description'],
            'updated_by' => $user->id,
        ];

        // Update extra details if income
        if ($entry->type === 'income') {
            $extraDetails = $entry->extra_details ?? [];
            if (isset($validated['tenant_name'])) {
                $extraDetails['tenant_name'] = $validated['tenant_name'];
            }
            if (isset($validated['property_id'])) {
                $extraDetails['property_id'] = $validated['property_id'];
            }
            $updateData['extra_details'] = $extraDetails;
        }

        $entry->update($updateData);

        self::logAccountingAction('update', $entry->id, 'AccountingEntry', $entry->building_id, $oldValues, $entry->toArray());

        return redirect()->back()->with('success', 'Entry updated successfully.');
    }

    /**
     * Store a new income entry (Manager/Admin only)
     */
    public function storeIncome(Request $request)
    {
        $user = auth()->user();
        if (! $user->isAdmin() && ! $user->isAccountant()) {
            abort(403, 'Only Accountants or Admins can record income.');
        }

        $validated = $request->validate([
            'building_id' => 'required|exists:buildings,id',
            'amount' => 'required|numeric|min:0',
            'category' => 'required|string|max:100',
            'entry_date' => 'required|date',
            'description' => 'nullable|string',
            'tenant_name' => 'required|string|max:200',
            'property_id' => 'required|string|max:100',
        ]);

        $entry = AccountingEntry::create([
            'building_id' => $validated['building_id'],
            'type' => 'income',
            'category' => $validated['category'],
            'description' => $validated['description'],
            'amount' => $validated['amount'],
            'entry_date' => $validated['entry_date'],
            'created_by' => $user->id,
            'extra_details' => [
                'tenant_name' => $validated['tenant_name'],
                'property_id' => $validated['property_id'],
            ],
        ]);

        self::logAccountingAction('create', $entry->id, 'AccountingEntry', $entry->building_id, null, $entry->toArray());

        return redirect()->back()->with('success', 'Income entry recorded successfully.');
    }

    /**
     * Store a new expense entry (Reviewer/Admin only)
     */
    public function storeExpense(Request $request)
    {
        $user = auth()->user();
        if (! $user->isAdmin() && ! $user->isAccountant()) {
            abort(403, 'Only Accountants or Admins can record expenses.');
        }

        $validated = $request->validate([
            'building_id' => 'required|exists:buildings,id',
            'amount' => 'required|numeric|min:0',
            'category' => 'required|string|max:100',
            'entry_date' => 'required|date',
            'description' => 'nullable|string',
        ]);

        $entry = AccountingEntry::create([
            'building_id' => $validated['building_id'],
            'type' => 'expense',
            'category' => $validated['category'],
            'description' => $validated['description'],
            'amount' => $validated['amount'],
            'entry_date' => $validated['entry_date'],
            'created_by' => $user->id,
        ]);

        self::logAccountingAction('create', $entry->id, 'AccountingEntry', $entry->building_id, null, $entry->toArray());

        return redirect()->back()->with('success', 'Expense entry recorded successfully.');
    }

    /**
     * Finalize an entry (Lock it)
     */
    public function finalize(AccountingEntry $entry)
    {
        $user = auth()->user();

        if (! $user->isAdmin() && ! $user->isAccountant()) {
            abort(403);
        }

        $oldValues = $entry->toArray();
        $entry->update([
            'status' => 'finalized',
            'is_locked' => true,
            'finalized_at' => now(),
        ]);

        self::logAccountingAction('finalize', $entry->id, 'AccountingEntry', $entry->building_id, $oldValues, $entry->toArray());

        return redirect()->back()->with('success', 'Entry finalized and locked.');
    }

    /**
     * Unlock an entry (Admin only)
     */
    public function unlock(AccountingEntry $entry)
    {
        if (! auth()->user()->isAdmin()) {
            abort(403);
        }

        $oldValues = $entry->toArray();
        $entry->update(['is_locked' => false]);

        self::logAccountingAction('unlock', $entry->id, 'AccountingEntry', $entry->building_id, $oldValues, $entry->toArray());

        return redirect()->back()->with('success', 'Entry unlocked successfully.');
    }

    /**
     * Request edit permission for a locked record (Reviewer/Manager)
     */
    public function requestEdit(Request $request, AccountingEntry $entry)
    {
        $user = auth()->user();

        $validated = $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $editRequest = EditRequest::create([
            'accounting_entry_id' => $entry->id,
            'user_id' => $user->id,
            'reason' => $validated['reason'],
        ]);

        self::logAccountingAction('request_edit', $entry->id, 'AccountingEntry', $entry->building_id, null, ['request_id' => $editRequest->id]);

        return redirect()->back()->with('success', 'Edit request submitted for Admin approval.');
    }

    /**
     * Approve or Reject an edit request (Admin only)
     */
    public function handleEditRequest(Request $request, EditRequest $editRequest)
    {
        if (! auth()->user()->isAdmin()) {
            abort(403);
        }

        $validated = $request->validate([
            'action' => 'required|in:approve,reject',
        ]);

        if ($validated['action'] === 'approve') {
            $editRequest->update([
                'status' => 'approved',
                'admin_id' => auth()->id(),
                'approved_at' => now(),
                'expires_at' => now()->addHours(2), // Temporary 2-hour permission
            ]);
            self::logAccountingAction('approve_edit', $editRequest->accounting_entry_id, 'AccountingEntry', null, null, ['request_id' => $editRequest->id]);
        } else {
            $editRequest->update([
                'status' => 'rejected',
                'admin_id' => auth()->id(),
            ]);
            self::logAccountingAction('reject_edit', $editRequest->accounting_entry_id, 'AccountingEntry', null, null, ['request_id' => $editRequest->id]);
        }

        return redirect()->back()->with('success', 'Edit request '.$validated['action'].'ed.');
    }

    /**
     * Display the audit trail (Admin only)
     */
    public function auditTrail()
    {
        if (! auth()->user()->isAdmin()) {
            abort(403);
        }

        $logs = AuditLog::with(['user', 'building'])->latest()->paginate(100);

        return view('accounting.audit-trail', compact('logs'));
    }

    /**
     * Export Financial Report (CSV)
     */
    public function export()
    {
        $user = auth()->user();
        if (! $user || ! $user->isAdmin()) {
            return response('Access denied. Only administrators can export reports.', 403);
        }

        try {
            // Clean buffer to prevent "headers already sent" or garbage data
            if (ob_get_level()) {
                ob_end_clean();
            }

            $filename = 'financial-summary-'.date('Y-m-d-His').'.csv';
            $headers = [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => "attachment; filename=\"$filename\"",
                'Pragma' => 'no-cache',
                'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
                'Expires' => '0',
            ];

            $columns = [
                'Building Name',
                'Total Income (Rent + Other)',
                'Total Expenses',
                'Net Cash Flow',
                'Outstanding Rent (Arrears)',
                'Occupancy Rate (%)',
            ];

            $callback = function () use ($columns) {
                try {
                    $file = fopen('php://output', 'w');

                    // Add UTF-8 BOM for Excel compatibility
                    fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

                    fputcsv($file, $columns);

                    $query = Building::with(['units', 'rents.payments', 'rents.tenant']);

                    if (request()->has('building_id') && request()->building_id) {
                        $query->where('id', request()->building_id);
                        $singleBuilding = true;
                    } else {
                        $singleBuilding = false;
                    }

                    $buildings = $query->get();

                    // Get Accounting Data
                    $accountingSummaries = AccountingEntry::select('building_id', 'type', DB::raw('SUM(amount) as total'))
                        ->groupBy('building_id', 'type')
                        ->get();

                    // Get Payment Data (Rent Income)
                    $paymentSummaries = Payment::where('payments.status', 'COMPLETED')
                        ->join('rents', 'payments.rent_id', '=', 'rents.id')
                        ->join('units', 'rents.unit_id', '=', 'units.id')
                        ->select('units.building_id', DB::raw('SUM(payments.amount) as total'))
                        ->groupBy('units.building_id')
                        ->pluck('total', 'building_id');

                    foreach ($buildings as $building) {
                        // Calculate Financials
                        $accIncome = $accountingSummaries->where('building_id', $building->id)->where('type', 'income')->sum('total');
                        $accExpense = $accountingSummaries->where('building_id', $building->id)->where('type', 'expense')->sum('total');
                        $rentIncome = $paymentSummaries[$building->id] ?? 0;

                        $totalIncome = $accIncome + $rentIncome;
                        $cashflow = $totalIncome - $accExpense;

                        // Calculate Outstanding (Arrears)
                        // We sum the balance of all ACTIVE rents for this building
                        $outstanding = 0;
                        foreach ($building->rents as $rent) {
                            if ($rent->status === 'ACTIVE' && $rent->balance > 0) {
                                $outstanding += $rent->balance;
                            }
                        }

                        // Calculate Occupancy
                        $totalUnits = $building->units->count();
                        $occupiedUnits = $building->units->where('status', 'OCCUPIED')->count();
                        $occupancy = $totalUnits > 0 ? round(($occupiedUnits / $totalUnits) * 100, 1) : 0;

                        fputcsv($file, [
                            $building->name,
                            number_format($totalIncome, 2, '.', ''),
                            number_format($accExpense, 2, '.', ''),
                            number_format($cashflow, 2, '.', ''),
                            number_format($outstanding, 2, '.', ''),
                            $occupancy.'%',
                        ]);

                        // If generating a report for a SINGLE building, output detailed sections below the summary row
                        if ($singleBuilding) {
                            fputcsv($file, []); // Spacer
                            fputcsv($file, ['TENANT RECORDS']);
                            fputcsv($file, ['Unit', 'Tenant Name', 'Start Date', 'Annual Rent', 'Total Paid', 'Outstanding Balance']);

                            foreach ($building->rents as $rent) {
                                if ($rent->status === 'ACTIVE') {
                                    $unit = $building->units->firstWhere('id', $rent->unit_id);
                                    // Get tenant directly from Rent relationship
                                    $tenantName = $rent->tenant ? $rent->tenant->full_name : 'No Tenant Assigned';

                                    fputcsv($file, [
                                        $unit ? $unit->name : 'Unknown Unit',
                                        $tenantName,
                                        $rent->start_date ? $rent->start_date->format('Y-m-d') : 'N/A',
                                        number_format($rent->annual_amount, 2, '.', ''),
                                        number_format($rent->total_paid, 2, '.', ''),
                                        number_format($rent->balance, 2, '.', ''),
                                    ]);
                                }
                            }

                            fputcsv($file, []); // Spacer
                            fputcsv($file, ['EXPENSES BREAKDOWN']);
                            fputcsv($file, ['Date', 'Category', 'Description', 'Amount']);

                            $expenses = AccountingEntry::where('building_id', $building->id)
                                ->where('type', 'expense')
                                ->orderBy('entry_date', 'desc')
                                ->get();

                            foreach ($expenses as $expense) {
                                fputcsv($file, [
                                    $expense->entry_date->format('Y-m-d'),
                                    $expense->category,
                                    $expense->description,
                                    number_format($expense->amount, 2, '.', ''),
                                ]);
                            }
                        }

                    }

                    fclose($file);
                } catch (\Throwable $e) {
                    error_log('CSV export failed: '.$e->getMessage());
                    echo 'Export failed. Please try again later.';
                }
            };

            return response()->stream($callback, 200, $headers);
        } catch (\Throwable $e) {
            try {
                \Log::error('CSV export failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            } catch (\Throwable $ignored) {
                error_log('CSV export failed (logger unavailable): '.$e->getMessage());
            }

            return response('Export failed. Please try again later.', 500);
        }
    }

    /**
     * Export Financial Report (PDF)
     */
    public function exportPdf()
    {
        $user = auth()->user();
        if (! $user || ! $user->isAdmin()) {
            return response('Access denied. Only administrators can export reports.', 403);
        }

        try {
            if (! app()->bound('dompdf.wrapper')) {
                try {
                    app()->register(ServiceProvider::class);
                } catch (\Throwable $e) {
                    error_log('DomPDF provider registration failed: '.$e->getMessage());
                }
            }

            if (! app()->bound('dompdf.wrapper')) {
                return response('PDF export is temporarily unavailable on this server.', 500);
            }

            $query = Building::with(['units', 'rents.payments', 'rents.tenant']);

            if (request()->has('building_id') && request()->building_id) {
                $query->where('id', request()->building_id);
                $singleBuilding = true;
            } else {
                $singleBuilding = false;
            }

            $buildings = $query->get();

            // Get Accounting Data
            $accountingSummaries = AccountingEntry::select('building_id', 'type', DB::raw('SUM(amount) as total'))
                ->groupBy('building_id', 'type')
                ->get();

            // Get Payment Data (Rent Income)
            $paymentSummaries = Payment::where('payments.status', 'COMPLETED')
                ->join('rents', 'payments.rent_id', '=', 'rents.id')
                ->join('units', 'rents.unit_id', '=', 'units.id')
                ->select('units.building_id', DB::raw('SUM(payments.amount) as total'))
                ->groupBy('units.building_id')
                ->pluck('total', 'building_id');

            // IF SINGLE BUILDING: Generate Detailed Report
            if ($singleBuilding && $buildings->count() === 1) {
                $building = $buildings->first();

                // Get Detailed Expenses
                $expenses = AccountingEntry::where('building_id', $building->id)
                    ->where('type', 'expense')
                    ->orderBy('entry_date', 'desc')
                    ->get();

                // Get All Active Rents
                $rents = [];
                foreach ($building->rents as $rent) {
                    if ($rent->status === 'ACTIVE') {
                        $unit = $building->units->firstWhere('id', $rent->unit_id);
                        // Get tenant directly from Rent relationship
                        $tenantName = $rent->tenant ? $rent->tenant->full_name : 'No Tenant Assigned';

                        $rents[] = [
                            'unit' => $unit ? $unit->name : 'Unknown Unit',
                            'tenant' => $tenantName,
                            'annual_rent' => $rent->annual_amount,
                            'paid' => $rent->total_paid,
                            'outstanding' => $rent->balance,
                            'start_date' => $rent->start_date,
                        ];
                    }
                }

                // Calculate Summary
                $accIncome = $accountingSummaries->where('building_id', $building->id)->where('type', 'income')->sum('total');
                $accExpense = $accountingSummaries->where('building_id', $building->id)->where('type', 'expense')->sum('total');
                $rentIncome = $paymentSummaries[$building->id] ?? 0;
                $totalIncome = $accIncome + $rentIncome;
                $cashflow = $totalIncome - $accExpense;

                // Calculate Total Outstanding
                $totalOutstanding = 0;
                foreach ($rents as $r) {
                    if ($r['outstanding'] > 0) {
                        $totalOutstanding += $r['outstanding'];
                    }
                }

                $summary = [
                    'income' => $totalIncome,
                    'expense' => $accExpense,
                    'cashflow' => $cashflow,
                    'outstanding' => $totalOutstanding,
                ];

                $pdf = PdfFacade::loadView('accounting.pdf-detailed-report', [
                    'building' => $building,
                    'rents' => $rents,
                    'expenses' => $expenses,
                    'summary' => $summary,
                    'date' => now()->format('F d, Y'),
                    'generated_by' => $user->name,
                ]);

                return $pdf->download('building-report-'.$building->id.'-'.now()->format('Y-m-d').'.pdf');
            }

            // ELSE: Generate Summary Report (Existing Logic)
            $reportData = [];

            foreach ($buildings as $building) {
                $accIncome = $accountingSummaries->where('building_id', $building->id)->where('type', 'income')->sum('total');
                $accExpense = $accountingSummaries->where('building_id', $building->id)->where('type', 'expense')->sum('total');
                $rentIncome = $paymentSummaries[$building->id] ?? 0;

                $totalIncome = $accIncome + $rentIncome;
                $cashflow = $totalIncome - $accExpense;

                // Calculate Outstanding (Arrears) & Get Tenant Details
                $outstanding = 0;
                $tenantsWithArrears = [];

                foreach ($building->rents as $rent) {
                    if ($rent->status === 'ACTIVE' && $rent->balance > 0) {
                        $outstanding += $rent->balance;

                        // Find tenant name for this rent
                        $tenantName = 'Unknown';
                        $unitName = 'Unknown';

                        $unit = $building->units->firstWhere('id', $rent->unit_id);
                        if ($unit) {
                            $unitName = $unit->name;
                        }

                        // Get tenant directly from Rent relationship
                        if ($rent->tenant) {
                            $tenantName = $rent->tenant->full_name;
                        }

                        $tenantsWithArrears[] = [
                            'unit' => $unitName,
                            'tenant' => $tenantName,
                            'amount' => $rent->balance,
                        ];
                    }
                }

                $reportData[] = [
                    'building' => $building,
                    'financials' => [
                        'income' => $totalIncome,
                        'expense' => $accExpense,
                        'cashflow' => $cashflow,
                        'outstanding' => $outstanding,
                    ],
                    'arrears_details' => $tenantsWithArrears,
                ];
            }

            $pdf = PdfFacade::loadView('accounting.pdf-report', [
                'reportData' => $reportData,
                'date' => now()->format('F d, Y'),
                'generated_by' => $user->name,
            ]);

            return $pdf->download('financial-report-'.now()->format('Y-m-d').'.pdf');
        } catch (\Throwable $e) {
            try {
                \Log::error('PDF export failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            } catch (\Throwable $ignored) {
                error_log('PDF export failed (logger unavailable): '.$e->getMessage());
            }

            return response('Export failed. Please try again later.', 500);
        }
    }
}
