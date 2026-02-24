<?php

namespace App\Http\Controllers;

use App\Models\Rent;
use App\Models\Tenant;
use App\Models\Unit;
use Illuminate\Http\Request;

class RentController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status', 'ACTIVE'); // Default to ACTIVE
        
        $query = Rent::with(['tenant', 'unit.building', 'creator']);

        if ($status && $status !== 'ALL') {
            $query->where('status', $status);
        }

        if (auth()->user()->isManager()) {
            $query->whereHas('unit.building', function ($q) {
                $q->where('manager_id', auth()->id());
            });
        }

        $rents = $query->paginate(15);
        return view('rents.index', compact('rents', 'status'));
    }

    public function create(Request $request)
    {
        $tenants = Tenant::where('active', true)->with('unit')->get();
        $buildings = \App\Models\Building::all(); // Fetch all buildings for the filter
        $units = Unit::where('status', '!=', 'MAINTENANCE')->with('building')->get(); // Eager load building
        $selectedTenantId = $request->query('tenant_id');
        $selectedUnitId = $request->query('unit_id');
        return view('rents.create', compact('tenants', 'units', 'buildings', 'selectedTenantId', 'selectedUnitId'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'tenant_id' => 'required|exists:tenants,id',
            'unit_id' => 'required|exists:units,id',
            'annual_amount' => 'required|numeric|min:0',
            'due_day' => 'required|integer|min:1|max:31',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after:start_date',
            'status' => 'nullable|in:ACTIVE,TERMINATED,EXPIRED',
        ]);

        if (isset($validated['end_date'])) {
            $validated['due_day'] = \Carbon\Carbon::parse($validated['end_date'])->day;
        }

        // Check for existing active lease for this tenant
        $existingRent = Rent::where('tenant_id', $validated['tenant_id'])
                            ->where('status', 'ACTIVE')
                            ->first();

        if ($existingRent) {
            // Mark the old lease as EXPIRED
            $existingRent->update(['status' => 'EXPIRED']);
            
            // If the tenant is moving to a new unit, free up the old unit
            if ($existingRent->unit_id != $validated['unit_id']) {
                $existingRent->unit->update(['status' => 'AVAILABLE']);
            }
        }

        $rent = Rent::create($validated);

        // Update Unit status and Tenant's current unit
        if ($rent->status === 'ACTIVE') {
            $unit = Unit::find($validated['unit_id']);
            if ($unit) {
                $unit->update(['status' => 'OCCUPIED']);
            }

            $tenant = Tenant::find($validated['tenant_id']);
            if ($tenant) {
                $tenant->update(['unit_id' => $validated['unit_id']]);
            }
        }

        return redirect()->route('buildings.show', Unit::find($validated['unit_id'])->building_id)->with('success', 'Rental agreement created successfully.');
    }

    public function show(Rent $rent)
    {
        $rent->load(['tenant', 'unit.building', 'payments']);
        
        $user = auth()->user();
        if ($user->isAdmin()) {
        } elseif ($user->isManager()) {
            if (!$rent->unit || !$rent->unit->building || $rent->unit->building->manager_id !== $user->id) {
                abort(403, 'Unauthorized action.');
            }
        } else {
            if (!$rent->tenant || $rent->tenant->user_id !== $user->id) {
                abort(403, 'Unauthorized action.');
            }
        }

        return view('rents.show', compact('rent'));
    }

    public function edit(Rent $rent)
    {
        $tenants = Tenant::all();
        $buildings = \App\Models\Building::all();
        $units = Unit::with('building')->get();
        return view('rents.edit', compact('rent', 'tenants', 'units', 'buildings'));
    }

    public function update(Request $request, Rent $rent)
    {
        $validated = $request->validate([
            'tenant_id' => 'required|exists:tenants,id',
            'unit_id' => 'required|exists:units,id',
            'annual_amount' => 'required|numeric|min:0',
            'due_day' => 'required|integer|min:1|max:31',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after:start_date',
            'status' => 'required|in:ACTIVE,TERMINATED,EXPIRED',
        ]);

        if (isset($validated['end_date'])) {
            $validated['due_day'] = \Carbon\Carbon::parse($validated['end_date'])->day;
        }

        $originalStatus = $rent->status;
        $originalUnitId = $rent->unit_id;
        $originalTenantId = $rent->tenant_id;

        $rent->update($validated);

        // 1. Handle Unit Changes
        if ($originalUnitId != $rent->unit_id) {
            // Old unit becomes available if it was occupied
            $oldUnit = Unit::find($originalUnitId);
            if ($oldUnit && $oldUnit->status === 'OCCUPIED') {
                 // Only set to AVAILABLE if we assume this rent was the one occupying it.
                 // Ideally we should check if there are other active rents, but simpler logic:
                 $oldUnit->update(['status' => 'AVAILABLE']);
            }
        }

        // 2. Handle Tenant Changes
        if ($originalTenantId != $rent->tenant_id) {
             $oldTenant = Tenant::find($originalTenantId);
             if ($oldTenant) {
                 $oldTenant->update(['unit_id' => null]);
             }
        }

        // 3. Apply Status Logic to Current (New) Unit/Tenant
        if ($rent->status === 'ACTIVE') {
            $rent->unit->update(['status' => 'OCCUPIED']);
            $rent->tenant->update(['unit_id' => $rent->unit_id]);
        } elseif (in_array($rent->status, ['TERMINATED', 'EXPIRED'])) {
            // If status is not active, free the current unit/tenant
            // But only if we didn't just switch FROM an active state on a different unit (already handled above somewhat)
            // Actually, simply:
            $rent->unit->update(['status' => 'AVAILABLE']);
            $rent->tenant->update(['unit_id' => null]);
        }

        return redirect()->route('rents.index')->with('success', 'Rental agreement updated successfully.');
    }

    public function destroy(Rent $rent)
    {
        if (!auth()->user()->isAdmin()) {
            return back()->with('error', 'Unauthorized action. Only admins can delete rental agreements.');
        }

        if ($rent->payments()->exists()) {
            return back()->with('error', 'Cannot delete rental agreement with recorded payments.');
        }

        $rent->delete();

        return redirect()->route('rents.index')->with('success', 'Rental agreement deleted successfully.');
    }

    public function agreement(Rent $rent)
    {
        $rent->load(['tenant', 'unit.building']);

        $user = auth()->user();
        if ($user->isAdmin()) {
        } elseif ($user->isManager()) {
            if (!$rent->unit || !$rent->unit->building || $rent->unit->building->manager_id !== $user->id) {
                abort(403, 'Unauthorized action.');
            }
        } else {
            if (!$rent->tenant || $rent->tenant->user_id !== $user->id) {
                abort(403, 'Unauthorized action.');
            }
        }

        return view('rents.agreement', compact('rent'));
    }

    public function uploadAgreement(Request $request, Rent $rent)
    {
        $user = auth()->user();
        if ($user->isAdmin()) {
        } elseif ($user->isManager()) {
            if (!$rent->unit || !$rent->unit->building || $rent->unit->building->manager_id !== $user->id) {
                abort(403, 'Unauthorized action.');
            }
        } else {
            if (!$rent->tenant || $rent->tenant->user_id !== $user->id) {
                abort(403, 'Unauthorized action.');
            }
        }

        $request->validate([
            'signed_agreement' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240', // 10MB max
        ]);

        if ($request->hasFile('signed_agreement')) {
            // Delete old file if exists
            if ($rent->signed_agreement_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($rent->signed_agreement_path)) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($rent->signed_agreement_path);
            }

            $path = $request->file('signed_agreement')->store('agreements', 'public');
            $rent->update(['signed_agreement_path' => $path]);

            return back()->with('success', 'Signed agreement uploaded successfully.');
        }

        return back()->with('error', 'No file uploaded.');
    }

    public function downloadAgreement(Rent $rent)
    {
        $user = auth()->user();
        if ($user->isAdmin()) {
        } elseif ($user->isManager()) {
            if (!$rent->unit || !$rent->unit->building || $rent->unit->building->manager_id !== $user->id) {
                abort(403, 'Unauthorized action.');
            }
        } else {
            if (!$rent->tenant || $rent->tenant->user_id !== $user->id) {
                abort(403, 'Unauthorized action.');
            }
        }

        if (!$rent->signed_agreement_path || !\Illuminate\Support\Facades\Storage::disk('public')->exists($rent->signed_agreement_path)) {
            return back()->with('error', 'No signed agreement found.');
        }

        $path = $rent->signed_agreement_path;
        $extension = pathinfo($path, PATHINFO_EXTENSION);
        $filename = 'Tenancy_Agreement_' . str_replace(' ', '_', $rent->tenant->full_name) . '.' . $extension;
        
        return \Illuminate\Support\Facades\Storage::disk('public')->download($path, $filename);
    }
}
