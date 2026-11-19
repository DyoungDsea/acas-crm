<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Contact;
use App\Models\Deal;
use App\Models\Order;
use App\Models\PharmacyBatch;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;

// Universal widgets (contacts/deals/orders/payments) render for every
// company type — same reasoning as OrderPageController, these
// resources exist for all of them. The stock-alerts section at the
// bottom is pharmacy-only and gated the same way PharmacyBatch pages
// already are, via $company->type rather than a route-level guard,
// since this is one shared dashboard route for every company.
class CompanyDashboardController extends Controller
{
    public function index(Company $company)
    {
        $openDeals = Deal::where('company_id', $company->id)->where('status', 'open');

        $data = [
            'company' => $company,
            'contactsCount' => Contact::where('company_id', $company->id)->count(),
            'openDealsCount' => (clone $openDeals)->count(),
            'openDealsValue' => (clone $openDeals)->sum('amount'),
            'ordersCount' => Order::where('company_id', $company->id)->where('status', '!=', 'void')->count(),
            'totalRevenue' => Order::where('company_id', $company->id)->where('status', '!=', 'void')->sum('total_amount'),
            'outstandingBalance' => $this->outstandingBalance($company),
            'paymentsThisMonth' => Payment::whereHas('order', fn($q) => $q->where('company_id', $company->id))
                ->whereMonth('received_at', now()->month)
                ->whereYear('received_at', now()->year)
                ->sum('amount'),
            'recentOrders' => Order::where('company_id', $company->id)
                ->with('contact')
                ->latest()
                ->take(5)
                ->get(),
            'recentPayments' => Payment::whereHas('order', fn($q) => $q->where('company_id', $company->id))
                ->with('order.contact')
                ->latest('received_at')
                ->take(5)
                ->get(),
            'menu' => config('menus.company'),
            'contextLabel' => $company->name,
        ];

        if ($company->type === 'pharmacy') {
            $data['expiringBatches'] = PharmacyBatch::where('company_id', $company->id)
                ->where('status', 'active')
                ->whereBetween('expiry_date', [now()->toDateString(), now()->addDays(30)->toDateString()])
                ->with('product')
                ->orderBy('expiry_date')
                ->get();

            // 10 units is an arbitrary default, not a per-product
            // setting — the schema has no reorder_level column on
            // Product yet. If that ever gets added, swap this
            // ->having('total', '<=', 10) for a join against it.
            $data['lowStockProducts'] = PharmacyBatch::where('company_id', $company->id)
                ->where('status', 'active')
                ->where('expiry_date', '>=', now()->toDateString())
                ->selectRaw('product_id, SUM(quantity_remaining) as total')
                ->groupBy('product_id')
                ->having('total', '<=', 10)
                ->with('product')
                ->get();
        }

        return view('dashboards.company', $data);
    }

    // Order::balanceDue() runs its own query per call, so looping it
    // over every open order would be an N+1. This does the same
    // total_amount-minus-payments math in one query instead.
    private function outstandingBalance(Company $company): float
    {
        return (float) Order::where('orders.company_id', $company->id)
            ->whereIn('orders.status', ['unpaid', 'partially_paid'])
            ->leftJoin('payments', 'payments.order_id', '=', 'orders.id')
            ->groupBy('orders.id', 'orders.total_amount')
            ->get([DB::raw('orders.total_amount - COALESCE(SUM(payments.amount), 0) as balance')])
            ->sum('balance');
    }
}
