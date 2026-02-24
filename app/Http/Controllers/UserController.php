<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $user = auth()->user();
            if (!$user->isAdmin() && !$user->isManager()) {
                abort(403, 'Unauthorized action.');
            }
            return $next($request);
        });
    }

    public function index()
    {
        $query = User::with('role');
        
        if (auth()->user()->isManager()) {
            $query->whereHas('role', function($q) {
                $q->where('name', 'tenant');
            })->whereHas('tenant.unit.building', function ($q) {
                $q->where('manager_id', auth()->id());
            });
        }
        
        $users = $query->paginate(10);
        return view('users.index', compact('users'));
    }

    public function create()
    {
        $roles = Role::all();
        if (auth()->user()->isManager()) {
            $roles = $roles->where('name', 'tenant');
        }
        return view('users.create', compact('roles'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'role_id' => 'required|exists:roles,id',
            'phone' => 'nullable|string|max:20',
        ]);
        
        if (auth()->user()->isManager()) {
            $role = Role::find($validated['role_id']);
            if (!$role || $role->name !== 'tenant') {
                return back()->withInput()->withErrors(['role_id' => 'Managers can only create tenant users.']);
            }
        }

        $validated['password'] = Hash::make($validated['password']);
        $validated['is_active'] = true; // Admin/Manager created users are active by default

        User::create($validated);

        return redirect()->route('users.index')->with('success', 'User created successfully.');
    }

    public function show(User $user)
    {
        if (auth()->user()->isManager()) {
            if (!$user->isTenant()) {
                abort(403, 'Unauthorized action.');
            }
            $managerId = optional(optional(optional($user->tenant)->unit)->building)->manager_id;
            if ($managerId !== auth()->id()) {
                abort(403, 'Unauthorized action.');
            }
        }
        return view('users.show', compact('user'));
    }

    public function edit(User $user)
    {
        if (auth()->user()->isManager()) {
            if (!$user->isTenant()) {
                abort(403, 'Unauthorized action.');
            }
            $managerId = optional(optional(optional($user->tenant)->unit)->building)->manager_id;
            if ($managerId !== auth()->id()) {
                abort(403, 'Unauthorized action.');
            }
        }

        $roles = Role::all();
        if (auth()->user()->isManager()) {
            $roles = $roles->where('name', 'tenant');
        }
        return view('users.edit', compact('user', 'roles'));
    }

    public function update(Request $request, User $user)
    {
        if (auth()->user()->isManager()) {
            if (!$user->isTenant()) {
                abort(403, 'Unauthorized action.');
            }
            $managerId = optional(optional(optional($user->tenant)->unit)->building)->manager_id;
            if ($managerId !== auth()->id()) {
                abort(403, 'Unauthorized action.');
            }
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'role_id' => 'required|exists:roles,id',
            'phone' => 'nullable|string|max:20',
            'is_active' => 'boolean',
        ]);

        if (auth()->user()->isManager()) {
            $role = Role::find($validated['role_id']);
            if (!$role || $role->name !== 'tenant') {
                 return back()->withInput()->withErrors(['role_id' => 'Managers can only assign tenant role.']);
            }
        }

        if (!$request->has('is_active')) {
             $validated['is_active'] = false;
        }

        if ($request->filled('password')) {
            $request->validate([
                'password' => 'string|min:8|confirmed',
            ]);
            $validated['password'] = Hash::make($request->password);
        }

        $user->update($validated);

        return redirect()->route('users.index')->with('success', 'User updated successfully.');
    }

    public function destroy(User $user)
    {
        if (auth()->user()->isManager()) {
            if (!$user->isTenant()) {
                abort(403, 'Unauthorized action.');
            }
            $managerId = optional(optional(optional($user->tenant)->unit)->building)->manager_id;
            if ($managerId !== auth()->id()) {
                abort(403, 'Unauthorized action.');
            }
        }

        if ($user->id === auth()->id()) {
            return back()->with('error', 'You cannot delete yourself.');
        }
        
        // Check dependencies (e.g., if user is a tenant with active rent)
        if ($user->tenant && $user->tenant->rents()->active()->exists()) {
             return back()->with('error', 'Cannot delete user with active rental agreements.');
        }

        $user->delete();

        return redirect()->route('users.index')->with('success', 'User deleted successfully.');
    }

    public function toggleStatus(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'You cannot deactivate yourself.');
        }

        if (auth()->user()->isManager()) {
            if (!$user->isTenant()) {
                return back()->with('error', 'Managers can only approve/deactivate tenants.');
            }
            $managerId = optional(optional(optional($user->tenant)->unit)->building)->manager_id;
            if ($managerId !== auth()->id()) {
                return back()->with('error', 'Unauthorized action.');
            }
        }

        $user->update(['is_active' => !$user->is_active]);
        
        $status = $user->is_active ? 'activated' : 'deactivated';
        return back()->with('success', "User has been $status.");
    }
}
