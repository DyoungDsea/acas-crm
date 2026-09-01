<?php

use App\Http\Controllers\Api\ActivityController;
use App\Http\Controllers\Api\CompanyController;
use App\Http\Controllers\Api\CompanySettingsController;
use App\Http\Controllers\Api\ContactController;
use App\Http\Controllers\Api\CropCycleController;
use App\Http\Controllers\Api\DealController;
use App\Http\Controllers\Api\DocumentController;
use App\Http\Controllers\Api\EngagementController;
use App\Http\Controllers\Api\LeaseController;
use App\Http\Controllers\Api\LivestockBatchController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\PharmacyBatchController;
use App\Http\Controllers\Api\PipelineController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\PropertyController;
use App\Http\Controllers\Api\PropertyUnitController;
use App\Http\Controllers\Api\ReceiptController;
use App\Http\Controllers\Api\SiteSurveyController;
use App\Http\Controllers\Api\SolarProjectController;
use App\Http\Controllers\Api\TaskController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\VehicleBookingController;
use App\Http\Controllers\Api\VehicleController;
use Illuminate\Support\Facades\Route;

// Every route below sits behind 'auth:sanctum' + 'active.user' —
// see bootstrap/app.php for how these aliases are registered.
Route::middleware(['auth:sanctum', 'active.user'])->group(function () {

    // Super-admin-only: managing the companies themselves.
    Route::middleware('super_admin')->group(function () {
        Route::apiResource('companies', CompanyController::class);
    });

    // Everything below is scoped to /companies/{company:slug}/... —
    // ResolveCompanyTenant checks the acting user belongs to that
    // company (or is super admin) before anything else runs. The
    // CompanyScope global scope on each model is the second, deeper
    // layer of the same rule.
    Route::prefix('companies/{company:slug}')->middleware('company.tenant')->group(function () {

        Route::get('settings', [CompanySettingsController::class, 'show']);
        Route::middleware('company_admin')->put('settings', [CompanySettingsController::class, 'update']);

        Route::middleware('company_admin')->group(function () {
            Route::apiResource('users', UserController::class);
        });

        Route::apiResource('contacts', ContactController::class);

        Route::apiResource('pipelines', PipelineController::class);
        Route::post('pipelines/{pipeline}/stages', [PipelineController::class, 'addStage']);
        Route::delete('pipelines/{pipeline}/stages/{stage}', [PipelineController::class, 'removeStage']);

        Route::apiResource('deals', DealController::class);
        Route::apiResource('activities', ActivityController::class)->except('update');
        Route::apiResource('tasks', TaskController::class);
        Route::apiResource('documents', DocumentController::class)->except('update');
        Route::apiResource('products', ProductController::class);

        Route::apiResource('orders', OrderController::class)->except(['update']);
        Route::post('orders/{order}/payments', [PaymentController::class, 'store']);

        Route::apiResource('receipts', ReceiptController::class)->only(['index', 'show']);
        Route::post('receipts/{receipt}/void', [ReceiptController::class, 'void']);

        // ACAS Homes & Properties
        Route::apiResource('properties', PropertyController::class);
        Route::apiResource('properties.units', PropertyUnitController::class)->shallow();
        Route::apiResource('leases', LeaseController::class);

        // T&T Prime Venture
        Route::apiResource('vehicles', VehicleController::class);
        Route::apiResource('vehicles.bookings', VehicleBookingController::class)->shallow();

        // ACAS Pharmacy
        Route::apiResource('pharmacy-batches', PharmacyBatchController::class);

        // ACAS Farms
        Route::apiResource('crop-cycles', CropCycleController::class);
        Route::apiResource('livestock-batches', LivestockBatchController::class);

        // Helion Dynamics
        Route::apiResource('solar-projects', SolarProjectController::class);
        Route::apiResource('solar-projects.surveys', SiteSurveyController::class)->shallow();

        // UDFDB Agric Services
        Route::apiResource('engagements', EngagementController::class);
    });
});
