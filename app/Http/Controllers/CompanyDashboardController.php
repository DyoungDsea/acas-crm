<?php

namespace App\Http\Controllers;

use App\Models\Company;

class CompanyDashboardController extends Controller
{
    public function index(Company $company)
    {
        return view('dashboards.company', [
            'company' => $company,
            'menu' => config('menus.company'),
            'contextLabel' => $company->name,
        ]);
    }
}
