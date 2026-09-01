<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateCompanySettingsPageRequest;
use App\Models\Company;

// company_admin-only (see routes/web.php) — plain staff have no
// business changing payment rules or company contact details.
class CompanySettingsPageController extends Controller
{
    public function edit(Company $company)
    {
        return view('company.settings.edit', [
            'company' => $company,
            'settings' => $company->paymentSetting,
            'menu' => config('menus.company'),
            'contextLabel' => $company->name,
        ]);
    }

    public function update(UpdateCompanySettingsPageRequest $request, Company $company)
    {
        $company->update($request->safe()->only(['phone', 'email', 'address']));

        $company->paymentSetting->update(
            $request->safe()->only(['allow_partial_payments', 'require_full_payment', 'minimum_partial_percent'])
        );

        return redirect()->route('company.settings.edit', ['company' => $company->slug])
            ->with('status', 'Settings updated.');
    }
}
