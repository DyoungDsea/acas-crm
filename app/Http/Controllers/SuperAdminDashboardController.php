<?php

namespace App\Http\Controllers;

use App\Models\Company;

class SuperAdminDashboardController extends Controller
{
    public function index()
    {
        $companies = Company::withCount(['contacts', 'deals', 'orders'])->get();

        return view('dashboards.super-admin', [
            'companies' => $companies,
            'menu' => config('menus.super_admin'),
            'contextLabel' => 'Super Admin',
        ]);
    }
}
