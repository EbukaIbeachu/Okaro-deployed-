<?php

namespace App\Http\Controllers;

use App\Models\MaintenanceRequest;
use App\Models\Unit;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MaintenanceRequestController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        
        if ($user->isAdmin() || $user->isManager()) {
            $requests = MaintenanceRequest::with(['unit.building', 'tenant', 'creator'])->latest()->paginate(10);
        } else {
            // Assume tenant user
            $tenant = Tenant::where('user_id', $user->id)->first();
            if ($tenant) {
                $requests = MaintenanceRequest::with(['unit.building', 'tenant'])
                    ->where('tenant_id', $tenant->id)
                    ->latest()
                    ->paginate(10);
            } else {
                $requests = collect(); // Empty collection if no tenant record found
            }
        }

        return view('maintenance.index', compact('requests'));
    }

    public function create()
    {
        $user = Auth::user();
        $units = [];
        $tenants = [];

        if ($user->isAdmin() || $user->isManager()) {
            $units = Unit::with('building')->get(); // Ideally filter by occupied
            $tenants = Tenant::active()->get();
        }

        return view('maintenance.create', compact('units', 'tenants'));
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        $rules = [
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'type' => 'required|in:PLUMBING,ELECTRICAL,HVAC,STRUCTURAL,APPLIANCE,OTHER',
            'priority' => 'required|in:LOW,MEDIUM,HIGH,EMERGENCY',
        ];

        if ($user->isAdmin() || $user->isManager()) {
            $rules['unit_id'] = 'required|exists:units,id';
            $rules['tenant_id'] = 'required|exists:tenants,id';
        }

        $validated = $request->validate($rules);

        if (!($user->isAdmin() || $user->isManager())) {
            $tenant = Tenant::where('user_id', $user->id)->firstOrFail();
            $validated['tenant_id'] = $tenant->id;
            $validated['unit_id'] = $tenant->unit_id; // Assuming tenant is assigned to a unit
            if (!$validated['unit_id']) {
                 // Fallback if tenant not currently in a unit (edge case)
                 return back()->with('error', 'You are not currently assigned to a unit.');
            }
        }

        $validated['status'] = 'PENDING';

        MaintenanceRequest::create($validated);

        return redirect()->route('maintenance.index')->with('success', 'Maintenance request submitted successfully.');
    }

    public function show(MaintenanceRequest $maintenance)
    {
        $maintenance->load(['unit.building', 'tenant']);
        return view('maintenance.show', compact('maintenance'));
    }

    public function edit(MaintenanceRequest $maintenance)
    {
        // Only admin/manager usually edits, or maybe tenant can edit if pending?
        // For simplicity, let's allow admin/manager to edit details/status.
        
        $units = Unit::with('building')->get();
        $tenants = Tenant::active()->get();
        
        return view('maintenance.edit', compact('maintenance', 'units', 'tenants'));
    }

    public function update(Request $request, MaintenanceRequest $maintenance)
    {
        $validated = $request->validate([
            'status' => 'required|in:PENDING,IN_PROGRESS,RESOLVED,CANCELLED',
            'priority' => 'required|in:LOW,MEDIUM,HIGH,EMERGENCY',
            'type' => 'required|in:PLUMBING,ELECTRICAL,HVAC,STRUCTURAL,APPLIANCE,OTHER',
            'title' => 'required|string|max:255',
            'description' => 'required|string',
        ]);

        if ($validated['status'] === 'RESOLVED' && $maintenance->status !== 'RESOLVED') {
            $validated['resolved_at'] = now();
        } elseif ($validated['status'] !== 'RESOLVED') {
            $validated['resolved_at'] = null;
        }

        $maintenance->update($validated);

        return redirect()->route('maintenance.index')->with('success', 'Maintenance request updated successfully.');
    }

    public function destroy(MaintenanceRequest $maintenance)
    {
        $maintenance->delete();
        return redirect()->route('maintenance.index')->with('success', 'Maintenance request deleted successfully.');
    }
}
