<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class TenantController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            if (!auth()->user()->isAdmin() && !auth()->user()->isManager()) {
                abort(403, 'Unauthorized action.');
            }
            return $next($request);
        });
    }

    public function index()
    {
        $query = Tenant::with(['unit.building', 'user', 'creator']);
        if (auth()->user()->isManager()) {
            $query->whereHas('unit.building', function ($q) {
                $q->where('manager_id', auth()->id());
            });
        }
        $tenants = $query->paginate(15);
        return view('tenants.index', compact('tenants'));
    }

    public function create(Request $request)
    {
        // Only show units that are available or currently have no active tenant?
        // For simplicity, show all units, but maybe mark occupied ones.
        $buildings = \App\Models\Building::all();
        $units = Unit::with('building')->orderBy('building_id')->orderBy('unit_number')->get();
        $selectedUnitId = $request->query('unit_id');
        return view('tenants.create', compact('units', 'buildings', 'selectedUnitId'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'full_name' => 'required|string|max:150',
            'email' => 'nullable|email|max:150',
            'phone' => 'nullable|string|max:50',
            'building_id' => 'required|exists:buildings,id',
            'unit_number' => 'required|string',
            'move_in_date' => 'required|date',
            'move_out_date' => 'nullable|date|after:move_in_date',
            'active' => 'boolean',
            'profile_image' => 'nullable|image|max:2048',
        ]);

        // Find unit by building_id and unit_number with flexible matching
        $inputUnitNumber = trim($validated['unit_number']);
        
        // Get all units for this building to perform flexible matching
        $buildingUnits = Unit::where('building_id', $validated['building_id'])->get();
        
        $unit = $buildingUnits->first(function ($u) use ($inputUnitNumber) {
            // Case-insensitive comparison of trimmed values
            return strcasecmp(trim($u->unit_number), $inputUnitNumber) === 0;
        });

        if (!$unit) {
            // Check if building has any units at all
            if ($buildingUnits->isEmpty()) {
                return back()->withInput()->withErrors(['unit_number' => 'This building has 0 units in the system. You must create the unit (e.g., "101") in the "Units" section before assigning a tenant to it.']);
            }
            
            // Construct a helpful error message
            $availableExamples = $buildingUnits->take(3)->pluck('unit_number')->implode(', ');
            $errorMsg = "Unit '$inputUnitNumber' not found in selected building. Available units include: $availableExamples" . ($buildingUnits->count() > 3 ? ", etc." : ".");
            
            return back()->withInput()->withErrors(['unit_number' => $errorMsg]);
        }
        
        $validated['unit_id'] = $unit->id;
        unset($validated['unit_number'], $validated['building_id']);

        if ($request->hasFile('profile_image')) {
            $path = $request->file('profile_image')->store('tenants', 'public');
            $validated['profile_image'] = $path;
        }

        // Check for existing placeholder tenant (registered user without unit)
        $email = $validated['email'] ?? null;
        $tenantToUpdate = null;

        if ($email) {
            $tenantToUpdate = Tenant::where('email', $email)->whereNull('unit_id')->first();
        }

        if ($tenantToUpdate) {
            $tenantToUpdate->update($validated);
            $tenant = $tenantToUpdate;
            $message = 'Existing tenant profile updated with lease details.';
        } else {
            // Link to user if exists
            if ($email) {
                $existingUser = User::where('email', $email)->first();
                if ($existingUser) {
                    $validated['user_id'] = $existingUser->id;
                }
            }
            $tenant = Tenant::create($validated);
            $message = 'Tenant registered successfully.';
        }

        // Update unit status to OCCUPIED if tenant is active
        if ($tenant->active) {
            $unit = Unit::find($validated['unit_id']);
            if ($unit) {
                $unit->update(['status' => 'OCCUPIED']);
            }
        }

        return redirect()->route('buildings.show', $request->building_id)->with('success', $message);
    }

    public function show(Tenant $tenant)
    {
        $tenant->load(['unit.building', 'rents', 'payments']);
        if (auth()->user()->isManager()) {
            $buildingManagerId = optional(optional($tenant->unit)->building)->manager_id;
            if ($buildingManagerId !== auth()->id()) {
                abort(403, 'Unauthorized action.');
            }
        }
        return view('tenants.show', compact('tenant'));
    }

    public function edit(Tenant $tenant)
    {
        $units = Unit::with('building')->get();
        return view('tenants.edit', compact('tenant', 'units'));
    }

    public function update(Request $request, Tenant $tenant)
    {
        $validated = $request->validate([
            'full_name' => 'required|string|max:150',
            'email' => 'nullable|email|max:150',
            'phone' => 'nullable|string|max:50',
            'unit_id' => 'required|exists:units,id',
            'move_in_date' => 'required|date',
            'move_out_date' => 'nullable|date|after:move_in_date',
            'active' => 'boolean',
            'profile_image' => 'nullable|image|max:2048',
        ]);

        if ($request->hasFile('profile_image')) {
            // Delete old image if exists
            if ($tenant->profile_image) {
                Storage::disk('public')->delete($tenant->profile_image);
            }
            $path = $request->file('profile_image')->store('tenants', 'public');
            $validated['profile_image'] = $path;
        }

        $oldUnitId = $tenant->unit_id;
        $tenant->update($validated);

        // Handle Unit status changes
        if ($oldUnitId != $tenant->unit_id) {
            // Make old unit AVAILABLE (if no other active tenants)
            // Ideally we check if anyone else is there, but usually 1 unit = 1 tenant/family
            Unit::where('id', $oldUnitId)->update(['status' => 'AVAILABLE']);
            
            // Make new unit OCCUPIED
            if ($tenant->active) {
                Unit::where('id', $tenant->unit_id)->update(['status' => 'OCCUPIED']);
            }
        } elseif (!$tenant->active) {
             // If tenant marked inactive, free up the unit
             Unit::where('id', $tenant->unit_id)->update(['status' => 'AVAILABLE']);
        }

        return redirect()->route('tenants.index')->with('success', 'Tenant updated successfully.');
    }

    public function destroy(Tenant $tenant)
    {
        if (!auth()->user()->isAdmin()) {
            return back()->with('error', 'Unauthorized action. Only admins can delete tenants.');
        }

        // Check for payments history
        if ($tenant->payments()->exists()) {
             return back()->with('error', 'Cannot delete tenant with payment history.');
        }

        $unitId = $tenant->unit_id;
        $tenant->delete();

        // Set unit to available
        Unit::where('id', $unitId)->update(['status' => 'AVAILABLE']);

        return redirect()->route('tenants.index')->with('success', 'Tenant deleted successfully.');
    }
}
