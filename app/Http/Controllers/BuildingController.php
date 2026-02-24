<?php

namespace App\Http\Controllers;

use App\Models\Building;
use App\Models\Unit;
use App\Models\Announcement;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class BuildingController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $user = auth()->user();
            if (!$user->isAdmin() && !$user->isManager()) {
                if ($request->routeIs('buildings.show') || $request->routeIs('buildings.announcements.dismiss')) {
                    $building = $request->route('building');
                    $tenant = optional($user)->tenant;
                    $tenantBuildingId = optional(optional($tenant)->unit)->building_id;
                    if ($building && $tenantBuildingId === optional($building)->id) {
                        return $next($request);
                    }
                }
                abort(403, 'Unauthorized action.');
            }
            return $next($request);
        });
    }

    public function index()
    {
        if (auth()->user()->isManager() && Schema::hasColumn('buildings', 'manager_id')) {
            $buildings = Building::with(['creator'])->where('manager_id', auth()->id())->withCount(['units', 'activeUnits'])->paginate(10);
        } else {
            $buildings = Building::with(['creator'])->withCount(['units', 'activeUnits'])->paginate(10);
        }
        return view('buildings.index', compact('buildings'));
    }

    public function create()
    {
        return view('buildings.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'address_line1' => 'required|string|max:200',
            'address_line2' => 'nullable|string|max:200',
            'city' => 'required|string|max:100',
            'state' => 'required|string|max:100',
            'postal_code' => 'required|string|max:20',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'total_units' => 'required|integer|min:1',
            'total_floors' => 'required|integer|min:1',
        ]);

        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('buildings', 'public');
            $validated['image_path'] = $imagePath;
        }

        $building = Building::create($validated);

        return redirect()->route('buildings.index')->with('success', 'Building created successfully.');
    }

    public function show(Building $building)
    {
        if (auth()->user()->isManager() && Schema::hasColumn('buildings', 'manager_id') && $building->manager_id !== auth()->id()) {
            abort(403);
        }
        $user = auth()->user();
        $building->load(['units.currentTenant', 'tenants', 'rents.payments', 'rents.tenant', 'rents.unit', 'manager']);
        $announcements = Announcement::where('building_id', $building->id)
            ->whereDoesntHave('dismissedBy', function ($q) use ($user) {
                $q->where('users.id', $user->id);
            })
            ->latest()
            ->take(5)
            ->get();
        
        // Calculate stats
        $occupied_count = $building->units->where('status', 'OCCUPIED')->count();
        $maintenance_count = $building->units->where('status', 'MAINTENANCE')->count();
        
        $stats = [
            'total_units' => $building->total_units, // Use capacity instead of created units count
            'created_units' => $building->units->count(),
            'occupied_units' => $occupied_count,
            'maintenance_units' => $maintenance_count,
            'available_units' => max(0, $building->total_units - $occupied_count - $maintenance_count),
            'total_tenants' => $building->tenants->count(),
            'total_revenue' => $building->rents->flatMap->payments->sum('amount'),
        ];

        return view('buildings.show', compact('building', 'stats', 'announcements'));
    }

    public function edit(Building $building)
    {
        if (auth()->user()->isManager() && Schema::hasColumn('buildings', 'manager_id') && $building->manager_id !== auth()->id()) {
            abort(403);
        }
        $managers = \App\Models\User::whereHas('role', function ($q) {
            $q->where('name', 'manager');
        })->where('is_active', true)->orderBy('name')->get();
        return view('buildings.edit', compact('building', 'managers'));
    }

    public function update(Request $request, Building $building)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'address_line1' => 'required|string|max:200',
            'address_line2' => 'nullable|string|max:200',
            'city' => 'required|string|max:100',
            'state' => 'required|string|max:100',
            'postal_code' => 'required|string|max:20',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'total_units' => 'required|integer|min:1',
            'total_floors' => 'required|integer|min:1',
            'manager_id' => 'nullable|exists:users,id',
        ]);

        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('buildings', 'public');
            $validated['image_path'] = $imagePath;
        }

        if (auth()->user()->isAdmin()) {
            if (!Schema::hasColumn('buildings', 'manager_id')) {
                unset($validated['manager_id']);
            }
            $building->update($validated);
        } else {
            unset($validated['manager_id']);
            $building->update($validated);
        }

        return redirect()->route('buildings.index')->with('success', 'Building updated successfully.');
    }

    public function announce(Request $request, Building $building)
    {
        if (!auth()->user()->isManager() && !auth()->user()->isAdmin()) {
            abort(403);
        }
        if (auth()->user()->isManager() && Schema::hasColumn('buildings', 'manager_id') && $building->manager_id !== auth()->id()) {
            abort(403);
        }
        $validated = $request->validate([
            'title' => 'required|string|max:150',
            'content' => 'required|string|max:5000',
        ]);
        Announcement::create([
            'building_id' => $building->id,
            'manager_id' => auth()->id(),
            'title' => $validated['title'],
            'content' => $validated['content'],
        ]);
        return back()->with('success', 'Announcement posted.');
    }

    public function dismissAnnouncement(Building $building, Announcement $announcement)
    {
        $user = auth()->user();
        if ($announcement->building_id !== $building->id) {
            abort(404);
        }

        $allowed = false;

        if ($user->isTenant()) {
            $tenant = optional($user)->tenant;
            $tenantBuildingId = optional(optional($tenant)->unit)->building_id;
            if ($tenantBuildingId === $building->id) {
                $allowed = true;
            }
        } elseif ($user->isManager()) {
            if (Schema::hasColumn('buildings', 'manager_id')) {
                if ($building->manager_id === $user->id) {
                    $allowed = true;
                }
            } else {
                $allowed = true;
            }
        } elseif ($user->isAdmin()) {
            $allowed = true;
        }

        if (!$allowed) {
            abort(403);
        }

        DB::table('announcement_dismissals')->updateOrInsert(
            ['announcement_id' => $announcement->id, 'user_id' => $user->id],
            ['updated_at' => now(), 'created_at' => now()]
        );
        return back()->with('success', 'Announcement removed from your view.');
    }

    public function destroyAnnouncement(Building $building, Announcement $announcement)
    {
        $user = auth()->user();
        if (!($user->isAdmin() || ($user->isManager() && \Illuminate\Support\Facades\Schema::hasColumn('buildings', 'manager_id') && $building->manager_id === $user->id))) {
            abort(403);
        }
        if ($announcement->building_id !== $building->id) {
            abort(404);
        }
        $announcement->delete();
        return back()->with('success', 'Announcement deleted.');
    }

    public function destroy(Building $building)
    {
        if ($building->units()->exists()) {
            return back()->with('error', 'Cannot delete building with existing units. Delete units first.');
        }

        $building->delete();

        return redirect()->route('buildings.index')->with('success', 'Building deleted successfully.');
    }
}
