<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Role;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class RegisterController extends Controller
{
    public function showRegistrationForm()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'phone' => ['nullable', 'string', 'max:20'],
        ]);

        $role = Role::where('name', 'manager')->first();
        if (!$role) {
            return back()->withErrors(['role' => 'Manager role does not exist.']);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role_id' => $role->id,
            'phone' => $request->phone,
            'is_active' => false,
        ]);

        return redirect()->route('login')->with('success', 'Manager registration successful. Please wait for admin approval.');
    }

    public function showTenantRegistrationForm()
    {
        return view('auth.register-tenant');
    }

    public function registerTenant(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'phone' => ['nullable', 'string', 'max:20'],
        ]);

        $role = Role::where('name', 'tenant')->first();
        if (!$role) {
            return back()->withErrors(['role' => 'Tenant role configuration error.']);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role_id' => $role->id,
            'phone' => $request->phone,
            'is_active' => false,
        ]);

        // Check for existing tenant profile by email
        $existingTenant = Tenant::where('email', $user->email)->first();

        if ($existingTenant) {
            // Link existing tenant to this user
            $existingTenant->update(['user_id' => $user->id]);
        }

        return redirect()->route('login')->with('success', 'Tenant registration successful. Please wait for admin approval.');
    }

    public function showAdminRegistrationForm()
    {
        return view('auth.register-admin');
    }

    public function registerAdmin(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'phone' => ['nullable', 'string', 'max:20'],
        ]);

        $role = Role::where('name', 'admin')->first();
        if (!$role) {
             $role = Role::create(['name' => 'admin', 'description' => 'Administrator']);
        }

        // Check if this is the first user, if so, make active. Otherwise, require approval?
        // Or just make all admins from this page active as it's a special page.
        // I'll make them active to ensure at least one admin can get in.
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role_id' => $role->id,
            'phone' => $request->phone,
            'is_active' => true, 
        ]);

        return redirect()->route('login')->with('success', 'Admin registration successful. You can now login.');
    }
}
