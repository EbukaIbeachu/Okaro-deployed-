<?php

namespace App\Http\Controllers;

use App\Models\Unit;
use App\Models\Building;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UnitController extends Controller
{
    public function index()
    {
        $units = Unit::with('building')->paginate(15);
        return view('units.index', compact('units'));
    }

    public function create(Request $request)
    {
        $buildings = Building::all();
        $selectedBuildingId = $request->query('building_id');
        return view('units.create', compact('buildings', 'selectedBuildingId'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'building_id' => 'required|exists:buildings,id',
            'unit_number' => 'required|string|max:50',
            'floor' => 'nullable|string|max:20',
            'bedrooms' => 'required|integer|min:0',
            'bathrooms' => 'required|integer|min:0',
            'status' => ['required', Rule::in(['AVAILABLE', 'OCCUPIED', 'MAINTENANCE'])],
        ]);

        // Check for duplicate unit in same building
        $exists = Unit::where('building_id', $request->building_id)
                      ->where('unit_number', $request->unit_number)
                      ->where('floor', $request->floor)
                      ->exists();
        
        if ($exists) {
            return back()->withErrors(['unit_number' => 'This unit already exists in the building.'])->withInput();
        }

        // Check if building has reached its total unit capacity
        $building = Building::find($request->building_id);
        if ($building && $building->total_units > 0) {
            $currentUnitCount = Unit::where('building_id', $building->id)->count();
            if ($currentUnitCount >= $building->total_units) {
                return back()->withErrors(['building_id' => "This building has reached its capacity of {$building->total_units} units."])->withInput();
            }
        }

        Unit::create($validated);

        return redirect()->route('buildings.show', $validated['building_id'])->with('success', 'Unit created successfully.');
    }

    public function show(Unit $unit)
    {
        $unit->load(['building', 'rents.tenant']);
        return view('units.show', compact('unit'));
    }

    public function edit(Unit $unit)
    {
        $buildings = Building::all();
        return view('units.edit', compact('unit', 'buildings'));
    }

    public function update(Request $request, Unit $unit)
    {
        $validated = $request->validate([
            'building_id' => 'required|exists:buildings,id',
            'unit_number' => 'required|string|max:50',
            'floor' => 'nullable|string|max:20',
            'bedrooms' => 'required|integer|min:0',
            'bathrooms' => 'required|integer|min:0',
            'status' => ['required', Rule::in(['AVAILABLE', 'OCCUPIED', 'MAINTENANCE'])],
        ]);

        // Check uniqueness excluding current unit
         $exists = Unit::where('building_id', $request->building_id)
                      ->where('unit_number', $request->unit_number)
                      ->where('floor', $request->floor)
                      ->where('id', '!=', $unit->id)
                      ->exists();

        if ($exists) {
            return back()->withErrors(['unit_number' => 'This unit already exists in the building.'])->withInput();
        }

        $unit->update($validated);

        return redirect()->route('units.index')->with('success', 'Unit updated successfully.');
    }

    public function destroy(Unit $unit)
    {
        if (!auth()->user()->isAdmin()) {
            return back()->with('error', 'Unauthorized action. Only admins can delete units.');
        }

        if ($unit->status === 'OCCUPIED') {
            return back()->with('error', 'Cannot delete an occupied unit.');
        }

        if ($unit->rents()->exists()) {
             return back()->with('error', 'Cannot delete unit with rental history.');
        }

        $unit->delete();

        return redirect()->route('units.index')->with('success', 'Unit deleted successfully.');
    }
}
