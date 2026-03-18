<?php

use App\Http\Controllers\AccountingController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BuildingController;
use App\Http\Controllers\CoinController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MaintenanceRequestController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\RegisterController;
use App\Http\Controllers\RentController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\TenantController;
use App\Http\Controllers\UnitController;
use App\Http\Controllers\UserController;
use App\Models\User;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/manifest.webmanifest', function () {
    $manifest = [
        'name' => 'Okaro & Associates',
        'short_name' => 'Okaro',
        'description' => 'Okaro & Associates property management system.',
        'start_url' => '/',
        'scope' => '/',
        'display' => 'standalone',
        'theme_color' => '#7c3aed',
        'background_color' => '#fdfbf7',
        'icons' => [
            [
                'src' => '/icons/icon-192.png',
                'sizes' => '192x192',
                'type' => 'image/png',
            ],
            [
                'src' => '/icons/icon-512.png',
                'sizes' => '512x512',
                'type' => 'image/png',
            ],
        ],
    ];

    return response()->json($manifest)->header('Content-Type', 'application/manifest+json');
});

Route::get('/reset-password', function (Request $request) {
    $email = $request->query('email');
    $password = $request->query('password');

    if (! $email || ! $password) {
        return 'Please provide email and password params.';
    }

    $user = User::where('email', $email)->first();

    if (! $user) {
        return "User not found with email: $email";
    }

    $user->password = Hash::make($password);
    $user->save();

    return "Password for $email has been reset successfully. You can now login.";
});

Route::get('/', function () {
    return view('welcome');
});

Route::get('/storage/{path}', function ($path) {
    $path = storage_path('app/public/'.$path);

    if (! file_exists($path)) {
        abort(404);
    }

    return response()->file($path);
})->where('path', '.*');

// Chatbot Route - Fallback for GET requests (debugging)
Route::get('/bot/message', function () {
    return response()->json(['response' => 'Chatbot endpoint is active (POST required).', 'status' => 'success'], 200);
});

// Chatbot Route - Direct Closure for reliability
Route::post('/bot/message', function (Request $request) {
    try {
        $request->validate(['message' => 'required|string']);
        $message = $request->input('message');

        // Simple fallback logic directly here to avoid Controller issues
        $lowerMsg = strtolower($message);

        // Determine User Role for Context
        $user = Auth::user();
        $role = 'guest';
        if ($user) {
            if ($user->isAdmin()) {
                $role = 'admin';
            } elseif ($user->isAccountant()) {
                $role = 'accountant';
            } elseif ($user->isManager()) {
                $role = 'manager';
            } elseif ($user->isTenant()) {
                $role = 'tenant';
            }
        }

        $roleRouteAccess = [
            'admin' => [
                'dashboard',
                'buildings.index',
                'tenants.index',
                'rents.index',
                'payments.index',
                'maintenance.index',
                'users.index',
                'roles.index',
                'accounting.index',
            ],
            'manager' => [
                'dashboard',
                'buildings.index',
                'tenants.index',
                'rents.index',
                'payments.index',
                'maintenance.index',
                'users.index',
            ],
            'tenant' => [
                'dashboard',
                'maintenance.index',
            ],
            'accountant' => [
                'accounting.index',
            ],
            'guest' => [],
        ];

        $canAccessRoute = function (string $routeName) use ($roleRouteAccess, $role): bool {
            return in_array($routeName, $roleRouteAccess[$role] ?? [], true);
        };

        $buildRouteLink = function (string $routeName, string $label) use ($canAccessRoute) {
            if (! $canAccessRoute($routeName) || ! Route::has($routeName)) {
                return null;
            }

            return '<a href="'.e(route($routeName)).'">'.e($label).'</a>';
        };

        $homeUrl = $user && $user->isAccountant() && Route::has('accounting.index')
            ? route('accounting.index')
            : url('/');

        $navLinks = [];
        $navLinks[] = '<a href="'.e($homeUrl).'">Home</a>';

        if ($role !== 'accountant') {
            $dash = $buildRouteLink('dashboard', 'Dashboard');
            if ($dash) {
                $navLinks[] = $dash;
            }
        }

        if ($role === 'admin' || $role === 'manager') {
            foreach ([
                ['buildings.index', 'Buildings'],
                ['tenants.index', 'Tenants'],
                ['rents.index', 'Rentals'],
                ['payments.index', 'Payments'],
                ['maintenance.index', 'Maintenance'],
                ['users.index', 'Users'],
            ] as $item) {
                $link = $buildRouteLink($item[0], $item[1]);
                if ($link) {
                    $navLinks[] = $link;
                }
            }

            if ($role === 'admin') {
                $roles = $buildRouteLink('roles.index', 'Roles');
                if ($roles) {
                    $navLinks[] = $roles;
                }
            }
        }

        if ($role === 'tenant') {
            $maintenance = $buildRouteLink('maintenance.index', 'Request Maintenance');
            if ($maintenance) {
                $navLinks[] = $maintenance;
            }
        }

        if ($role === 'admin' || $role === 'accountant') {
            $accounting = $buildRouteLink('accounting.index', 'Accounting');
            if ($accounting) {
                $navLinks[] = $accounting;
            }
        }

        $navText = implode(' • ', array_values(array_unique($navLinks)));

        $response = "Available sections: {$navText}.";

        // Greetings
        if (str_contains($lowerMsg, 'hello') || str_contains($lowerMsg, 'hi') || str_contains($lowerMsg, 'hey') || str_contains($lowerMsg, 'morning') || str_contains($lowerMsg, 'evening')) {
            if ($role === 'admin' || $role === 'manager') {
                $dashboardLink = $buildRouteLink('dashboard', 'dashboard') ?: 'dashboard';
                $response = 'Welcome back, '.($user ? $user->name : 'there')."! You can start from your {$dashboardLink}.";
            } elseif ($role === 'accountant') {
                $accountingLink = $buildRouteLink('accounting.index', 'Accounting') ?: 'Accounting';
                $response = 'Welcome back, '.($user ? $user->name : 'there')."! You can start from {$accountingLink}.";
            } elseif ($role === 'tenant') {
                $maintenanceLink = $buildRouteLink('maintenance.index', 'Request Maintenance') ?: 'Request Maintenance';
                $dashboardLink = $buildRouteLink('dashboard', 'Dashboard') ?: 'Dashboard';
                $response = 'Hi '.($user ? $user->name : 'there').'! You can use '.$dashboardLink.' and '.$maintenanceLink.'.';
            } else {
                $response = 'Hi there! Welcome to Okaro & Associates. I can help you with Maintenance, Payments, Lease details, or General Support. How can I assist you today?';
            }
        }
        elseif (str_contains($lowerMsg, 'menu') || str_contains($lowerMsg, 'nav') || str_contains($lowerMsg, 'navbar') || str_contains($lowerMsg, 'options') || str_contains($lowerMsg, 'where can')) {
            $response = "Available sections: {$navText}.";
        }
        elseif (str_contains($lowerMsg, 'accounting') || str_contains($lowerMsg, 'finance') || str_contains($lowerMsg, 'ledger') || str_contains($lowerMsg, 'audit')) {
            $accountingLink = $buildRouteLink('accounting.index', 'Accounting');
            if ($accountingLink) {
                $response = "Open {$accountingLink}.";
            } else {
                $response = 'Accounting is not available for your role.';
            }
        }
        // Financials (Rent, Pay, Bill)
        elseif (str_contains($lowerMsg, 'rent') || str_contains($lowerMsg, 'pay') || str_contains($lowerMsg, 'bill') || str_contains($lowerMsg, 'balance') || str_contains($lowerMsg, 'invoice')) {
            if ($role === 'admin' || $role === 'manager') {
                $paymentsLink = $buildRouteLink('payments.index', 'Payments') ?: 'Payments';
                $response = "Open {$paymentsLink} to track collections and overdue accounts.";
            } elseif ($role === 'accountant') {
                $accountingLink = $buildRouteLink('accounting.index', 'Accounting') ?: 'Accounting';
                $response = "Open {$accountingLink} to review and record finance entries.";
            } elseif ($role === 'tenant') {
                $response = 'You can check your outstanding balance and make secure payments from the Payments section in your main menu.';
            } else {
                $response = 'Tenants can login to pay rent. If you are inquiring about rental rates, please check our listings page.';
            }
        }
        // Maintenance (Repair, Fix, Broken)
        elseif (str_contains($lowerMsg, 'maintenance') || str_contains($lowerMsg, 'repair') || str_contains($lowerMsg, 'fix') || str_contains($lowerMsg, 'broken') || str_contains($lowerMsg, 'leak') || str_contains($lowerMsg, 'damage')) {
            if ($role === 'admin' || $role === 'manager') {
                $maintenanceLink = $buildRouteLink('maintenance.index', 'Maintenance') ?: 'Maintenance';
                $response = "Open {$maintenanceLink} to manage maintenance requests.";
            } elseif ($role === 'tenant') {
                $maintenanceLink = $buildRouteLink('maintenance.index', 'Request Maintenance') ?: 'Request Maintenance';
                $response = "To report an issue, open {$maintenanceLink} and submit a request.";
            } elseif ($role === 'accountant') {
                $response = 'Maintenance requests are handled by Operations. Please contact Admin or Manager.';
            } else {
                $response = 'For urgent building maintenance issues, please contact our 24/7 facility manager at (555) 999-8888.';
            }
        }
        // Tenants / Lease (Agreement, Contract)
        elseif (str_contains($lowerMsg, 'tenant') || str_contains($lowerMsg, 'lease') || str_contains($lowerMsg, 'contract') || str_contains($lowerMsg, 'agreement') || str_contains($lowerMsg, 'renew')) {
            if ($role === 'admin' || $role === 'manager') {
                $tenantsLink = $buildRouteLink('tenants.index', 'Tenants') ?: 'Tenants';
                $rentsLink = $buildRouteLink('rents.index', 'Rentals') ?: 'Rentals';
                $response = "Open {$tenantsLink} and {$rentsLink} to manage tenants and leases.";
            } elseif ($role === 'accountant') {
                $response = 'Lease management is handled by Admin or Manager.';
            } elseif ($role === 'tenant') {
                $response = 'Your active lease details, including renewal dates and signed copies, are available in the Rentals section that you can open from your main menu.';
            } else {
                $response = "Are you looking to become a tenant? Please visit our 'Available Units' page to apply.";
            }
        }
        // Account / Profile
        elseif (str_contains($lowerMsg, 'password') || str_contains($lowerMsg, 'login') || str_contains($lowerMsg, 'profile') || str_contains($lowerMsg, 'email') || str_contains($lowerMsg, 'account')) {
            $response = "To update your personal information or change your password, click on your user avatar in the top right corner and select 'Profile'.";
        }
        // General Info / Location
        elseif (str_contains($lowerMsg, 'location') || str_contains($lowerMsg, 'address') || str_contains($lowerMsg, 'where') || str_contains($lowerMsg, 'office')) {
            $response = 'Our main office is located at 123 Property Lane, Real Estate City. We are open Mon-Fri, 9am-5pm.';
        }
        // Contact / Support
        elseif (str_contains($lowerMsg, 'contact') || str_contains($lowerMsg, 'phone') || str_contains($lowerMsg, 'support') || str_contains($lowerMsg, 'help')) {
            if ($role === 'admin') {
                $response = 'System Support: For technical server issues, please contact the IT department. For operational support, check the internal wiki.';
            } else {
                $response = 'You can reach our support team at support@okaro.com or call us at (555) 123-4567 during business hours.';
            }
        }

        // Try Ollama if configured (optional)
        try {
            $ollamaUrl = env('OLLAMA_API_URL', 'http://localhost:11434/api/generate');
            $http = new Client(['timeout' => 2]); // Short timeout
            $res = $http->post($ollamaUrl, [
                'json' => [
                    'model' => env('OLLAMA_MODEL', 'llama3'),
                    'prompt' => $message,
                    'stream' => false,
                ],
            ]);
            $data = json_decode($res->getBody(), true);
            if (isset($data['response'])) {
                $response = $data['response'];
            }
        } catch (Exception $e) {
            // Ollama failed, keep fallback response
        }

        return response()->json(['response' => $response, 'status' => 'success']);

    } catch (Throwable $e) {
        return response()->json(['response' => 'Error: '.$e->getMessage()], 200);
    }
})->name('chatbot.send');

// Test Route to confirm file is updated
Route::get('/chatbot/status', function () {
    return 'Chatbot System is Active';
});

// Authentication Routes
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::get('/logout', function () {
    if (auth()->check()) {
        auth()->logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();
    }

    return redirect()->route('login');
});
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Registration Routes
Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register');
Route::post('/register', [RegisterController::class, 'register']);

// Tenant Registration Routes
Route::get('/register-tenant', [RegisterController::class, 'showTenantRegistrationForm'])->name('register.tenant');
Route::post('/register-tenant', [RegisterController::class, 'registerTenant']);

// Admin Registration (Separate/Hidden)
Route::get('/register-admin', [RegisterController::class, 'showAdminRegistrationForm'])->name('register.admin.form');
Route::post('/register-admin', [RegisterController::class, 'registerAdmin'])->name('register.admin');

// Protected Routes
Route::middleware(['auth', 'prevent-back-history', 'accountant.restrict'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/coins', [CoinController::class, 'show'])->name('coins.show');
    Route::post('/coins', [CoinController::class, 'update'])->name('coins.update');

    // Core Modules
    Route::get('rents/{rent}/agreement', [RentController::class, 'agreement'])->name('rents.agreement');
    Route::post('rents/{rent}/upload-agreement', [RentController::class, 'uploadAgreement'])->name('rents.upload-agreement');
    Route::get('rents/{rent}/download-agreement', [RentController::class, 'downloadAgreement'])->name('rents.download-agreement');

    // User Status Toggle
    Route::post('users/{user}/toggle-status', [UserController::class, 'toggleStatus'])->name('users.toggle-status');

    Route::resource('users', UserController::class);
    Route::resource('roles', RoleController::class);
    Route::resource('buildings', BuildingController::class);
    Route::post('buildings/{building}/announce', [BuildingController::class, 'announce'])->name('buildings.announce');
    Route::post('buildings/{building}/announcements/{announcement}/dismiss', [BuildingController::class, 'dismissAnnouncement'])->name('buildings.announcements.dismiss');
    Route::delete('buildings/{building}/announcements/{announcement}', [BuildingController::class, 'destroyAnnouncement'])->name('buildings.announcements.destroy');
    Route::resource('units', UnitController::class);
    Route::resource('tenants', TenantController::class);
    Route::resource('rents', RentController::class);
    Route::resource('payments', PaymentController::class);
    Route::resource('maintenance', MaintenanceRequestController::class);

    // New Accounting Module
    Route::prefix('accounting')->name('accounting.')->group(function () {
        Route::get('/', [AccountingController::class, 'index'])->name('index');
        Route::get('/report', [AccountingController::class, 'report'])->name('report');
        Route::get('/export', [AccountingController::class, 'export'])->name('export')->middleware('admin.export'); // CSV Export
        Route::get('/export-pdf', [AccountingController::class, 'exportPdf'])->name('export-pdf')->middleware('admin.export'); // PDF Export
        Route::post('/income', [AccountingController::class, 'storeIncome'])->name('store.income');
        Route::post('/expense', [AccountingController::class, 'storeExpense'])->name('store.expense');
        Route::patch('/entries/{entry}', [AccountingController::class, 'update'])->name('update');
        Route::patch('/entries/{entry}/finalize', [AccountingController::class, 'finalize'])->name('finalize');
        Route::patch('/entries/{entry}/lock', [AccountingController::class, 'lock'])->name('lock');
        Route::patch('/entries/{entry}/unlock', [AccountingController::class, 'unlock'])->name('unlock');
        Route::post('/entries/{entry}/request-edit', [AccountingController::class, 'requestEdit'])->name('request-edit');
        Route::post('/edit-requests/{editRequest}/handle', [AccountingController::class, 'handleEditRequest'])->name('handle-edit-request');
        Route::get('/audit-trail', [AccountingController::class, 'auditTrail'])->name('audit-trail');
    });
});
