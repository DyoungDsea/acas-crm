<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;

// Super admin only. Read-only — this page has no store/update/destroy,
// it's a report over data that already exists elsewhere (Companies,
// Orders, Payments). Every figure here is built the same way
// CompanyDashboardController computes its own — total revenue and
// outstanding balance in particular use the same single-query
// approach (rather than looping Order::balanceDue() per row) to
// avoid an N+1 across every order in every company at once, which
// matters more here than on a single company's dashboard.
class SuperAdminReportPageController extends Controller
{
    public function index()
    {
        $companies = Company::withCount(['contacts', 'orders' => fn($q) => $q->where('status', '!=', 'void')])->orderBy('name')->get();

        $perCompany = $companies->map(function ($company) {
            return [
                'company' => $company,
                'revenue' => (float) Order::where('company_id', $company->id)->where('status', '!=', 'void')->sum('total_amount'),
                'outstanding' => $this->outstandingBalance($company->id),
                'collected_this_month' => (float) Payment::whereHas('order', fn($q) => $q->where('company_id', $company->id))
                    ->whereMonth('received_at', now()->month)
                    ->whereYear('received_at', now()->year)
                    ->sum('amount'),
            ];
        })->sortByDesc('revenue')->values();

        $byType = $companies->groupBy('type')->map(function ($group, $type) use ($perCompany) {
            $revenue = $perCompany->whereIn('company.id', $group->pluck('id'))->sum('revenue');

            return [
                'type' => $type,
                'company_count' => $group->count(),
                'revenue' => $revenue,
            ];
        })->sortByDesc('revenue')->values();

        return view('super-admin.reports', [
            'totalCompanies' => $companies->count(),
            'activeCompanies' => $companies->where('is_active', true)->count(),
            'totalContacts' => $companies->sum('contacts_count'),
            'totalOrders' => $companies->sum('orders_count'),
            'totalRevenue' => $perCompany->sum('revenue'),
            'totalOutstanding' => $perCompany->sum('outstanding'),
            'totalCollectedThisMonth' => $perCompany->sum('collected_this_month'),
            'perCompany' => $perCompany,
            'byType' => $byType,
            'menu' => config('menus.super_admin'),
            'contextLabel' => 'Reports',
        ]);
    }

    // Same reasoning as CompanyDashboardController's private method
    // of the same name — Order::balanceDue() queries per call, so
    // looping it across every open order in every company would be
    // an N+1 at group scale. One grouped query per company instead.
    private function outstandingBalance(string $companyId): float
    {
        return (float) Order::where('orders.company_id', $companyId)
            ->whereIn('orders.status', ['unpaid', 'partially_paid'])
            ->leftJoin('payments', 'payments.order_id', '=', 'orders.id')
            ->groupBy('orders.id', 'orders.total_amount')
            ->get([DB::raw('orders.total_amount - COALESCE(SUM(payments.amount), 0) as balance')])
            ->sum('balance');
    }
}
