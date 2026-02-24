<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Building;
use App\Models\Tenant;
use App\Models\Rent;
use App\Models\Payment;
use App\Models\Unit;
use App\Models\Announcement;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        if ($user->isAdmin() || $user->isManager()) {
            return $this->adminDashboard();
        }

        if ($user->isTenant()) {
            return $this->tenantDashboard();
        }

        return redirect()->route('login');
    }

    private function adminDashboard()
    {
        $user = \Illuminate\Support\Facades\Auth::user();
        $isManager = $user->isManager();
        $buildingIds = $isManager ? Building::where('manager_id', $user->id)->pluck('id') : null;
        $managerAnnouncements = collect();

        // Statistics
        if ($isManager) {
            $stats = [
                'total_buildings' => Building::whereIn('id', $buildingIds)->count(),
                'total_tenants' => Tenant::where('active', true)
                    ->whereHas('unit.building', function ($q) use ($buildingIds) {
                        $q->whereIn('id', $buildingIds);
                    })->count(),
                'paid_total' => Payment::where('status', 'COMPLETED')
                    ->whereYear('payment_date', now()->year)
                    ->whereHas('rent.unit.building', function ($q) use ($buildingIds) {
                        $q->whereIn('id', $buildingIds);
                    })->sum('amount'),
                'overdue_count' => Rent::active()
                    ->whereHas('unit.building', function ($q) use ($buildingIds) {
                        $q->whereIn('id', $buildingIds);
                    })->get()->filter(function ($rent) {
                        return $rent->balance > 0;
                    })->count(),
            ];
            $managerAnnouncements = Announcement::whereIn('building_id', $buildingIds)
                ->whereDoesntHave('dismissedBy', function ($q) use ($user) {
                    $q->where('users.id', $user->id);
                })
                ->latest()
                ->take(5)
                ->get();
        } else {
            $stats = [
                'total_buildings' => Building::count(),
                'total_tenants' => Tenant::where('active', true)->count(),
                'paid_total' => Payment::where('status', 'COMPLETED')->whereYear('payment_date', now()->year)->sum('amount'),
                'overdue_count' => Rent::active()->get()->filter(function ($rent) {
                    return $rent->balance > 0;
                })->count(),
            ];
        }

        // Monthly Revenue (Last 6 months)
        $monthlyPaymentsQuery = Payment::where('status', 'COMPLETED')
            ->where('payment_date', '>=', now()->subMonths(6)->startOfMonth());
        if ($isManager) {
            $monthlyPaymentsQuery->whereHas('rent.unit.building', function ($q) use ($buildingIds) {
                $q->whereIn('id', $buildingIds);
            });
        }
        $monthlyRevenue = $monthlyPaymentsQuery
            ->selectRaw('DATE_FORMAT(payment_date, "%Y-%m") as month, SUM(amount) as total')
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('total', 'month')
            ->all();
        
        // Fill missing months with 0
        $chartData = [];
        $chartLabels = [];
        for ($i = 5; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $monthKey = $date->format('Y-m');
            $chartLabels[] = $date->format('M Y');
            $chartData[] = $monthlyRevenue[$monthKey] ?? 0;
        }

        // Recent Payments
        $recentPaymentsQuery = Payment::with(['rent.tenant', 'rent.unit.building'])
            ->latest('payment_date')
            ->take(10);
        if ($isManager) {
            $recentPaymentsQuery->whereHas('rent.unit.building', function ($q) use ($buildingIds) {
                $q->whereIn('id', $buildingIds);
            });
        }
        $recentPayments = $recentPaymentsQuery->get();

        // Overdue Rents (Simplified logic for example)
        $overdueRentsQuery = Rent::active()
            ->with(['tenant', 'unit.building']);
        if ($isManager) {
            $overdueRentsQuery->whereHas('unit.building', function ($q) use ($buildingIds) {
                $q->whereIn('id', $buildingIds);
            });
        }
        $overdueRents = $overdueRentsQuery->get()->filter(function ($rent) {
            return $rent->balance > 0;
        });

        // Pending Requests (Placeholder)
        $requests = []; 

        return view('admin.dashboard', compact('stats', 'recentPayments', 'overdueRents', 'requests', 'chartData', 'chartLabels', 'managerAnnouncements'));
    }

    private function tenantDashboard()
    {
        $user = Auth::user();
        $tenant = $user->tenant;
        
        if (!$tenant) {
            // Check for existing tenant profile by email first (in case it wasn't linked during registration)
            if ($user->isTenant()) {
                 $existingTenant = Tenant::where('email', $user->email)->first();
                 
                 if ($existingTenant) {
                     $existingTenant->update(['user_id' => $user->id]);
                     $tenant = $existingTenant;
                 }
            }
        }

        if ($tenant) {
            $rent = $tenant->currentRent ?? $tenant->rents()->active()->latest()->first(); 
            $payments = $tenant->payments()->latest('payment_date')->take(5)->get();
            $buildingId = $rent ? optional($rent->unit)->building_id : optional(optional($tenant->unit))->building_id;
            if ($buildingId) {
                $announcements = Announcement::where('building_id', $buildingId)
                    ->whereDoesntHave('dismissedBy', function ($q) use ($user) {
                        $q->where('users.id', $user->id);
                    })
                    ->latest()
                    ->take(5)
                    ->get();
            } else {
                $announcements = collect([]);
            }
        } else {
            $rent = null;
            $payments = collect([]);
            $announcements = collect([]);
        }
        
        return view('tenant.dashboard', compact('tenant', 'rent', 'payments', 'announcements'));
    }
}
