<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\BuildingController;
use App\Http\Controllers\UnitController;
use App\Http\Controllers\TenantController;
use App\Http\Controllers\RentController;
use App\Http\Controllers\PaymentController;

use App\Http\Controllers\RegisterController;
use App\Http\Controllers\MaintenanceRequestController;
use App\Http\Controllers\CoinController;

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
        "name" => "Okaro & Associates",
        "short_name" => "Okaro",
        "description" => "Okaro & Associates property management system.",
        "start_url" => "/",
        "scope" => "/",
        "display" => "standalone",
        "theme_color" => "#7c3aed",
        "background_color" => "#fdfbf7",
        "icons" => [
            [
                "src" => "/icons/icon-192.png",
                "sizes" => "192x192",
                "type" => "image/png"
            ],
            [
                "src" => "/icons/icon-512.png",
                "sizes" => "512x512",
                "type" => "image/png"
            ]
        ]
    ];
    return response()->json($manifest)->header('Content-Type', 'application/manifest+json');
});

Route::get('/', function () {
    return view('welcome');
});

Route::get('/storage/{path}', function ($path) {
    $path = storage_path('app/public/' . $path);
    
    if (!file_exists($path)) {
        abort(404);
    }
    
    return response()->file($path);
})->where('path', '.*');

// Chatbot Route - Fallback for GET requests (debugging)
Route::get('/bot/message', function () {
    return response()->json(['response' => 'Chatbot endpoint is active (POST required).', 'status' => 'success'], 200);
});

// Chatbot Route - Direct Closure for reliability
Route::post('/bot/message', function (Illuminate\Http\Request $request) {
    try {
        $request->validate(['message' => 'required|string']);
        $message = $request->input('message');
        
        // Simple fallback logic directly here to avoid Controller issues
        $lowerMsg = strtolower($message);
        
        // Determine User Role for Context
        $user = Auth::user();
        $role = 'guest';
        if ($user) {
            if ($user->isAdmin()) $role = 'admin';
            elseif ($user->isManager()) $role = 'manager';
            elseif ($user->isTenant()) $role = 'tenant';
        }

        $rolePermissions = [
            'admin' => [
                'dashboard',
                'buildings.index',
                'tenants.index',
                'rents.index',
                'payments.index',
                'maintenance.index',
                'users.index',
                'roles.index',
            ],
            'manager' => [
                'dashboard',
                'buildings.index',
                'tenants.index',
                'rents.index',
                'payments.index',
                'maintenance.index',
            ],
            'tenant' => [],
            'guest' => [],
        ];

        $canAccessRoute = function (string $routeName) use ($rolePermissions, $role): bool {
            return in_array($routeName, $rolePermissions[$role] ?? [], true);
        };

        $buildSecureLink = function (string $routeName, string $label, ?string $requiredRoleLabel = null) use ($canAccessRoute, $role, $user) {
            if (!$canAccessRoute($routeName)) {
                $required = $requiredRoleLabel ?: 'additional privileges';
                Log::warning('Chatbot blocked unauthorized link generation', [
                    'route' => $routeName,
                    'role' => $role,
                    'user_id' => $user ? $user->id : null,
                ]);

                if ($role === 'guest') {
                    return 'Please log in to access this section.';
                }

                return 'Access requires ' . $required . '.';
            }

            $url = route($routeName);

            return '<a href="' . e($url) . '">' . e($label) . '</a>';
        };

        $response = "I'm currently running in offline mode. I can help with Payments, Maintenance, Lease Info, and General Questions.";

        // Greetings
        if (str_contains($lowerMsg, 'hello') || str_contains($lowerMsg, 'hi') || str_contains($lowerMsg, 'hey') || str_contains($lowerMsg, 'morning') || str_contains($lowerMsg, 'evening')) {
            if ($role === 'admin' || $role === 'manager') {
                $dashboardLink = $buildSecureLink('dashboard', 'dashboard', 'Admin or Manager privileges');
                $response = "Welcome back, " . ($user ? $user->name : 'there') . "! Ready to manage the properties? You can start from your {$dashboardLink}, check overdue rents, review pending maintenance, or look at tenant stats.";
            } elseif ($role === 'tenant') {
                $response = "Hi " . ($user ? $user->name : 'there') . "! Welcome home. You can use the main menu to get to your rent, payments, and maintenance requests.";
            } else {
                $response = "Hi there! Welcome to Okaro & Associates. I can help you with Maintenance, Payments, Lease details, or General Support. How can I assist you today?";
            }
        } 
        // Financials (Rent, Pay, Bill)
        elseif (str_contains($lowerMsg, 'rent') || str_contains($lowerMsg, 'pay') || str_contains($lowerMsg, 'bill') || str_contains($lowerMsg, 'balance') || str_contains($lowerMsg, 'invoice')) {
            if ($role === 'admin' || $role === 'manager') {
                $paymentsLink = $buildSecureLink('payments.index', 'Payments dashboard', 'Admin or Manager privileges');
                $response = "For financial oversight, open the {$paymentsLink}. You can track collected rent, view overdue accounts, and generate monthly revenue reports.";
            } elseif ($role === 'tenant') {
                $response = "You can check your outstanding balance and make secure payments from the Payments section in your main menu.";
            } else {
                $response = "Tenants can login to pay rent. If you are inquiring about rental rates, please check our listings page.";
            }
        } 
        // Maintenance (Repair, Fix, Broken)
        elseif (str_contains($lowerMsg, 'maintenance') || str_contains($lowerMsg, 'repair') || str_contains($lowerMsg, 'fix') || str_contains($lowerMsg, 'broken') || str_contains($lowerMsg, 'leak') || str_contains($lowerMsg, 'damage')) {
            if ($role === 'admin' || $role === 'manager') {
                $maintenanceLink = $buildSecureLink('maintenance.index', 'Maintenance module', 'Admin or Manager privileges');
                $response = "You have access to the full maintenance log from the {$maintenanceLink}. There you can assign contractors, update ticket status, or approve repair budgets.";
            } elseif ($role === 'tenant') {
                $response = "Oh no! To report an issue, please go to the Maintenance section from your main menu and log a request so we can track it. For flooding or fire, call the emergency hotline immediately.";
            } else {
                $response = "For urgent building maintenance issues, please contact our 24/7 facility manager at (555) 999-8888.";
            }
        }
        // Tenants / Lease (Agreement, Contract)
        elseif (str_contains($lowerMsg, 'tenant') || str_contains($lowerMsg, 'lease') || str_contains($lowerMsg, 'contract') || str_contains($lowerMsg, 'agreement') || str_contains($lowerMsg, 'renew')) {
            if ($role === 'admin' || $role === 'manager') {
                $tenantsLink = $buildSecureLink('tenants.index', 'Tenants module', 'Admin or Manager privileges');
                $rentsLink = $buildSecureLink('rents.index', 'Rentals section', 'Admin or Manager privileges');
                $response = "You can manage tenant profiles, draft new lease agreements, and handle evictions from the {$tenantsLink} and {$rentsLink}.";
            } elseif ($role === 'tenant') {
                $response = "Your active lease details, including renewal dates and signed copies, are available in the Rentals section that you can open from your main menu.";
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
            $response = "Our main office is located at 123 Property Lane, Real Estate City. We are open Mon-Fri, 9am-5pm.";
        }
        // Contact / Support
        elseif (str_contains($lowerMsg, 'contact') || str_contains($lowerMsg, 'phone') || str_contains($lowerMsg, 'support') || str_contains($lowerMsg, 'help')) {
            if ($role === 'admin') {
                $response = "System Support: For technical server issues, please contact the IT department. For operational support, check the internal wiki.";
            } else {
                $response = "You can reach our support team at support@okaro.com or call us at (555) 123-4567 during business hours.";
            }
        }

        // Try Ollama if configured (optional)
        try {
            $ollamaUrl = env('OLLAMA_API_URL', 'http://localhost:11434/api/generate');
            $http = new \GuzzleHttp\Client(['timeout' => 2]); // Short timeout
            $res = $http->post($ollamaUrl, [
                'json' => [
                    'model' => env('OLLAMA_MODEL', 'llama3'),
                    'prompt' => $message,
                    'stream' => false
                ]
            ]);
            $data = json_decode($res->getBody(), true);
            if (isset($data['response'])) {
                $response = $data['response'];
            }
        } catch (\Exception $e) {
            // Ollama failed, keep fallback response
        }

        return response()->json(['response' => $response, 'status' => 'success']);

    } catch (\Throwable $e) {
        return response()->json(['response' => 'Error: ' . $e->getMessage()], 200);
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
Route::middleware(['auth'])->group(function () {
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
});
