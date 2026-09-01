<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateCompanySettingsRequest;
use App\Http\Resources\CompanyPaymentSettingResource;
use App\Models\Company;

// Reached via /companies/{company}/settings — company admins manage
// their own company's settings, super admins manage any.
class CompanySettingsController extends Controller
{
    public function show(Company $company)
    {
        return new CompanyPaymentSettingResource($company->paymentSetting);
    }

    public function update(UpdateCompanySettingsRequest $request, Company $company)
    {
        $company->paymentSetting->update($request->validated());

        return new CompanyPaymentSettingResource($company->paymentSetting->fresh());
    }
}
