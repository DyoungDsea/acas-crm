<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCompanyRequest;
use App\Models\Company;

// Plain server-rendered CRUD — no JSON API involved, no AJAX for
// the list itself. Standard Laravel form submissions: on failure,
// StoreCompanyRequest's default behavior redirects back with
// $errors + old() input automatically (no extra code needed for
// that here) — the same conventional pattern used everywhere else
// in a normal Laravel app, so anyone can read this without needing
// to understand a separate JS data layer.
class CompanyPageController extends Controller
{
    public function index()
    {
        return view('super-admin.companies', [
            'companies' => Company::orderBy('name')->get(),
            'menu' => config('menus.super_admin'),
            'contextLabel' => 'Companies',
        ]);
    }

    public function store(StoreCompanyRequest $request)
    {
        $company = Company::create($request->validated());

        $company->paymentSetting()->create([
            'allow_partial_payments' => true,
            'require_full_payment' => false,
        ]);

        return redirect()->route('super-admin.companies.index')->with('status', "{$company->name} created.");
    }

    public function update(StoreCompanyRequest $request, Company $company)
    {
        $company->update($request->validated());

        return redirect()->route('super-admin.companies.index')->with('status', "{$company->name} updated.");
    }

    public function destroy(Company $company)
    {
        // Deactivate, not delete — preserves history for every record
        // still pointing at this company. Matches Api\CompanyController's
        // destroy() behavior.
        $company->update(['is_active' => false]);

        return redirect()->route('super-admin.companies.index')->with('status', "{$company->name} deactivated.");
    }
    
    public function activate(Company $company)
    {
        $company->update(['is_active' => true]);

        return redirect()->route('super-admin.companies.index')->with('status', "{$company->name} reactivated.");
    }
}
