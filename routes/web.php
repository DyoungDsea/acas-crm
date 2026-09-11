<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\CompanyAdminPageController;
use App\Http\Controllers\CompanyDashboardController;
use App\Http\Controllers\CompanyPageController;
use App\Http\Controllers\CompanySettingsPageController;
use App\Http\Controllers\ContactPageController;
use App\Http\Controllers\PharmacyBatchPageController;
use App\Http\Controllers\ProductPageController;
use App\Http\Controllers\Settings\PasswordController;
use App\Http\Controllers\StaffPageController;
use App\Http\Controllers\SuperAdminDashboardController;
use App\Http\Controllers\SuperAdminPageController;
use App\Http\Controllers\SuperAdminReportPageController;
use App\Http\Controllers\ProductCategoryPageController;
use App\Http\Controllers\PipelinePageController;
use App\Http\Controllers\DealPageController;
use App\Http\Controllers\OrderPageController;
use App\Http\Controllers\PaymentPageController;
use App\Http\Controllers\ReceiptPageController;
use App\Http\Controllers\CropCyclePageController;
use App\Http\Controllers\LivestockBatchPageController;
use App\Http\Controllers\EngagementPageController;
use App\Http\Controllers\SolarProjectPageController;
use App\Http\Controllers\LeasePageController;
use App\Http\Controllers\PropertyPageController;
use App\Http\Controllers\VehicleListingPageController;
use App\Http\Controllers\VehicleTransactionPageController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// Route::get('/', function () {
//     return auth()->check()
//         ? redirect(auth()->user()->dashboardUrl())
//         : redirect()->route('login');
// });

Route::get('/', function () {
    $user = Auth::user();

    if (! $user || ! method_exists($user, 'dashboardUrl')) {
        return redirect()->route('login');
    }

    return redirect($user->dashboardUrl());
});

/*
|--------------------------------------------------------------------------
| Guest routes — login + forgot/reset password
|--------------------------------------------------------------------------
| One login form for every role. Forgot-password is available to
| company admins and staff; super admin accounts are silently
| excluded (see PasswordResetLinkController) — their only recovery
| path is `php artisan admin:reset-password {email}`.
*/
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store']);

    Route::get('/forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetLinkController::class, 'store'])->name('password.email');

    Route::get('/reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('/reset-password', [NewPasswordController::class, 'store'])->name('password.store');
});

/*
|--------------------------------------------------------------------------
| Authenticated routes
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'active.user'])->group(function () {

    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    // Change password — available to EVERY authenticated role,
    // including super admin, since it requires the current password
    // and isn't reachable by anyone who only has access to an inbox.
    // Change password is a modal in the shared header (see
    // layouts/app.blade.php) — no standalone edit page/route needed,
    // just the submission target.
    Route::put('/settings/password', [PasswordController::class, 'update'])->name('password.update');

    // Super admin: one dashboard, cross-company visibility.
    Route::middleware('super_admin')->prefix('super-admin')->name('super-admin.')->group(function () {
        Route::get('/dashboard', [SuperAdminDashboardController::class, 'index'])->name('dashboard');

        Route::get('/companies', [CompanyPageController::class, 'index'])->name('companies.index');
        Route::post('/companies', [CompanyPageController::class, 'store'])->name('companies.store');
        Route::put('/companies/{company}', [CompanyPageController::class, 'update'])->name('companies.update');
        Route::delete('/companies/{company}', [CompanyPageController::class, 'destroy'])->name('companies.destroy');
        Route::patch('/companies/{company}/activate', [CompanyPageController::class, 'activate'])->name('companies.activate');

        Route::get('/company-admins', [CompanyAdminPageController::class, 'index'])->name('company-admins.index');
        Route::post('/company-admins', [CompanyAdminPageController::class, 'store'])->name('company-admins.store');
        Route::put('/company-admins/{company_admin}', [CompanyAdminPageController::class, 'update'])->name('company-admins.update');
        Route::delete('/company-admins/{company_admin}', [CompanyAdminPageController::class, 'destroy'])->name('company-admins.destroy');
        Route::patch('/company-admins/{company_admin}/activate', [CompanyAdminPageController::class, 'activate'])->name('company-admins.activate');

        Route::get('/super-admins', [SuperAdminPageController::class, 'index'])->name('super-admins.index');
        Route::post('/super-admins', [SuperAdminPageController::class, 'store'])->name('super-admins.store');
        Route::put('/super-admins/{admin}', [SuperAdminPageController::class, 'update'])->name('super-admins.update');
        Route::delete('/super-admins/{admin}', [SuperAdminPageController::class, 'destroy'])->name('super-admins.destroy');
        Route::patch('/super-admins/{admin}/activate', [SuperAdminPageController::class, 'activate'])->name('super-admins.activate');
        Route::get('/reports', [SuperAdminReportPageController::class, 'index'])->name('reports.index');
    });

    // Company dashboard — /{company-slug}/..., themed per company via
    // $company->themeKey(). ResolveCompanyTenant confirms the acting
    // user belongs to this company (or is super admin) before
    // anything below runs.
    Route::prefix('{company:slug}')->middleware('company.tenant')->name('company.')->group(function () {
        Route::get('/dashboard', [CompanyDashboardController::class, 'index'])->name('dashboard');

        Route::get('/contacts', [ContactPageController::class, 'index'])->name('contacts.index');
        Route::post('/contacts', [ContactPageController::class, 'store'])->name('contacts.store');
        Route::put('/contacts/{contact}', [ContactPageController::class, 'update'])->name('contacts.update');
        Route::delete('/contacts/{contact}', [ContactPageController::class, 'destroy'])->name('contacts.destroy');
        Route::patch('/contacts/{contact}/restore', [ContactPageController::class, 'restore'])
            ->name('contacts.restore')
            ->withTrashed();

        Route::get('/pipelines', [PipelinePageController::class, 'index'])->name('pipelines.index');
        Route::post('/pipelines', [PipelinePageController::class, 'store'])->name('pipelines.store');
        Route::put('/pipelines/{pipeline}', [PipelinePageController::class, 'update'])->name('pipelines.update');
        Route::delete('/pipelines/{pipeline}', [PipelinePageController::class, 'destroy'])->name('pipelines.destroy');
        Route::post('/pipelines/{pipeline}/stages', [PipelinePageController::class, 'storeStage'])->name('pipelines.stages.store');
        Route::put('/pipelines/{pipeline}/stages/{stage}', [PipelinePageController::class, 'updateStage'])->name('pipelines.stages.update');
        Route::delete('/pipelines/{pipeline}/stages/{stage}', [PipelinePageController::class, 'destroyStage'])->name('pipelines.stages.destroy');
        Route::patch('/pipelines/{pipeline}/stages/{stage}/move', [PipelinePageController::class, 'moveStage'])->name('pipelines.stages.move');

        Route::get('/deals', [DealPageController::class, 'index'])->name('deals.index');
        Route::post('/deals', [DealPageController::class, 'store'])->name('deals.store');
        Route::put('/deals/{deal}', [DealPageController::class, 'update'])->name('deals.update');

        Route::get('/orders', [OrderPageController::class, 'index'])->name('orders.index');
        Route::post('/orders', [OrderPageController::class, 'store'])->name('orders.store');
        Route::put('/orders/{order}', [OrderPageController::class, 'update'])->name('orders.update');

        Route::get('/payments', [PaymentPageController::class, 'index'])->name('payments.index');
        Route::post('/payments', [PaymentPageController::class, 'store'])->name('payments.store');
        Route::patch('/payments/{payment}/void', [PaymentPageController::class, 'void'])->name('payments.void');

        Route::get('/products', [ProductPageController::class, 'index'])->name('products.index');
        Route::post('/products', [ProductPageController::class, 'store'])->name('products.store');
        Route::put('/products/{product}', [ProductPageController::class, 'update'])->name('products.update');
        Route::delete('/products/{product}', [ProductPageController::class, 'destroy'])->name('products.destroy');
        Route::patch('/products/{product}/activate', [ProductPageController::class, 'activate'])->name('products.activate');

        Route::get('/product-categories', [ProductCategoryPageController::class, 'index'])->name('product-categories.index');
        Route::post('/product-categories', [ProductCategoryPageController::class, 'store'])->name('product-categories.store');
        Route::put('/product-categories/{product_category}', [ProductCategoryPageController::class, 'update'])->name('product-categories.update');
        Route::delete('/product-categories/{product_category}', [ProductCategoryPageController::class, 'destroy'])->name('product-categories.destroy');


        Route::get('/pharmacy-batches', [PharmacyBatchPageController::class, 'index'])->name('pharmacy-batches.index');
        Route::post('/pharmacy-batches', [PharmacyBatchPageController::class, 'store'])->name('pharmacy-batches.store');
        Route::put('/pharmacy-batches/{pharmacy_batch}', [PharmacyBatchPageController::class, 'update'])->name('pharmacy-batches.update');

        Route::get('/receipts', [ReceiptPageController::class, 'index'])->name('receipts.index');


        Route::get('/crop-cycles', [CropCyclePageController::class, 'index'])->name('crop-cycles.index');
        Route::post('/crop-cycles', [CropCyclePageController::class, 'store'])->name('crop-cycles.store');
        Route::put('/crop-cycles/{crop_cycle}', [CropCyclePageController::class, 'update'])->name('crop-cycles.update');

        Route::get('/livestock-batches', [LivestockBatchPageController::class, 'index'])->name('livestock-batches.index');
        Route::post('/livestock-batches', [LivestockBatchPageController::class, 'store'])->name('livestock-batches.store');
        Route::put('/livestock-batches/{livestock_batch}', [LivestockBatchPageController::class, 'update'])->name('livestock-batches.update');


        Route::get('/engagements', [EngagementPageController::class, 'index'])->name('engagements.index');
        Route::post('/engagements', [EngagementPageController::class, 'store'])->name('engagements.store');
        Route::put('/engagements/{engagement}', [EngagementPageController::class, 'update'])->name('engagements.update');


        Route::get('/solar-projects', [SolarProjectPageController::class, 'index'])->name('solar-projects.index');
        Route::post('/solar-projects', [SolarProjectPageController::class, 'store'])->name('solar-projects.store');
        Route::put('/solar-projects/{solar_project}', [SolarProjectPageController::class, 'update'])->name('solar-projects.update');
        Route::post('/solar-projects/{solar_project}/surveys', [SolarProjectPageController::class, 'storeSurvey'])->name('solar-projects.surveys.store');
        Route::put('/solar-projects/{solar_project}/surveys/{survey}', [SolarProjectPageController::class, 'updateSurvey'])->name('solar-projects.surveys.update');

        Route::get('/properties', [PropertyPageController::class, 'index'])->name('properties.index');
        Route::post('/properties', [PropertyPageController::class, 'store'])->name('properties.store');
        Route::put('/properties/{property}', [PropertyPageController::class, 'update'])->name('properties.update');
        Route::post('/properties/{property}/units', [PropertyPageController::class, 'storeUnit'])->name('properties.units.store');
        Route::put('/properties/{property}/units/{unit}', [PropertyPageController::class, 'updateUnit'])->name('properties.units.update');

        Route::get('/leases', [LeasePageController::class, 'index'])->name('leases.index');
        Route::post('/leases', [LeasePageController::class, 'store'])->name('leases.store');
        Route::put('/leases/{lease}', [LeasePageController::class, 'update'])->name('leases.update');

        Route::get('/vehicle-listings', [VehicleListingPageController::class, 'index'])->name('vehicle-listings.index');
        Route::post('/vehicle-listings', [VehicleListingPageController::class, 'store'])->name('vehicle-listings.store');
        Route::put('/vehicle-listings/{vehicle_listing}', [VehicleListingPageController::class, 'update'])->name('vehicle-listings.update');

        Route::get('/vehicle-transactions', [VehicleTransactionPageController::class, 'index'])->name('vehicle-transactions.index');
        Route::post('/vehicle-transactions', [VehicleTransactionPageController::class, 'store'])->name('vehicle-transactions.store');
        Route::put('/vehicle-transactions/{order}', [VehicleTransactionPageController::class, 'update'])->name('vehicle-transactions.update');
        Route::patch('/vehicle-transactions/{order}/return', [VehicleTransactionPageController::class, 'markReturned'])->name('vehicle-transactions.return');

        // Admin-only within this company — plain staff manage their
        // own work (contacts, deals, etc.) but not the team roster or
        // company-wide settings like payment rules.
        Route::middleware('company_admin')->group(function () {
            Route::get('/staff', [StaffPageController::class, 'index'])->name('staff.index');
            Route::post('/staff', [StaffPageController::class, 'store'])->name('staff.store');
            Route::put('/staff/{staff}', [StaffPageController::class, 'update'])->name('staff.update');
            Route::delete('/staff/{staff}', [StaffPageController::class, 'destroy'])->name('staff.destroy');
            Route::patch('/staff/{staff}/activate', [StaffPageController::class, 'activate'])->name('staff.activate');

            Route::get('/settings', [CompanySettingsPageController::class, 'edit'])->name('settings.edit');
            Route::put('/settings', [CompanySettingsPageController::class, 'update'])->name('settings.update');
        });

        // Not yet built — added one at a time as each template is
        // designed. Listed here so the eventual route names/URLs are
        // already decided and nothing has to be renamed later:
        //
        // Route::get('/deals', ...)->name('deals.index');
        // Route::get('/pipelines', ...)->name('pipelines.index');
        // Route::get('/orders', ...)->name('orders.index');
        // Route::get('/orders/{order}', ...)->name('orders.show');
        // Route::get('/receipts', ...)->name('receipts.index');
        // Route::get('/users', ...)->name('users.index');        // company_admin only
        //
        // Vertical-specific (present only for the relevant company):
        // Route::get('/properties', ...)->name('properties.index');
        // Route::get('/leases', ...)->name('leases.index');
        // Route::get('/vehicles', ...)->name('vehicles.index');
        // Route::get('/crop-cycles', ...)->name('crop-cycles.index');
        // Route::get('/livestock-batches', ...)->name('livestock-batches.index');
        // Route::get('/solar-projects', ...)->name('solar-projects.index');
        // Route::get('/engagements', ...)->name('engagements.index');
    });
});
