<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCompanyRequest;
use App\Http\Resources\CompanyResource;
use App\Models\Company;
use Illuminate\Http\Request;

// Route-level EnsureSuperAdmin middleware — only super admins reach
// this controller. Company records are never company-scoped (a
// company can't scope itself), so no BelongsToCompany trait applies.
class CompanyController extends Controller
{
    public function index(Request $request)
    {
        return CompanyResource::collection(
            Company::query()->paginate($request->integer('per_page', 20))
        );
    }

    public function store(StoreCompanyRequest $request)
    {
        $company = Company::create($request->validated());
        $company->paymentSetting()->create([
            'allow_partial_payments' => true,
            'require_full_payment' => false,
        ]);

        return new CompanyResource($company);
    }

    public function show(Company $company)
    {
        return new CompanyResource($company->load('paymentSetting'));
    }

    public function update(StoreCompanyRequest $request, Company $company)
    {
        $company->update($request->validated());

        return new CompanyResource($company);
    }

    public function destroy(Company $company)
    {
        // Deactivate rather than hard-delete — preserves history for
        // every record still pointing at this company.
        $company->update(['is_active' => false]);

        return response()->noContent();
    }
}
