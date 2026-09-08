<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Receipt;

// Receipts are never created directly here — a receipt only ever
// comes into existence as a side effect of PaymentPageController::store()
// logging a payment. This controller is read-only: its job is to let
// an admin pull up a receipt and hand the customer a printed copy
// with the full order breakdown, not to manage receipt state.
//
// Voiding lives on PaymentPageController now, not here — see the
// comment on that controller. A receipt shown on this page may
// still be void (it stays visible for the audit trail); the print
// view just marks it clearly as void rather than hiding it.
//
// Receipt has no company_id column of its own — company ownership
// only exists through payment.order.company_id — so every query
// here filters via that relationship chain instead of a flat where().
class ReceiptPageController extends Controller
{
    public function index(Company $company)
    {
        $receipts = Receipt::whereHas('payment.order', fn($q) => $q->where('company_id', $company->id))
            ->with([
                'issuedByUser',
                'payment.order.contact',
                'payment.order.items.product',
                'payment.order.payments', // needed to show balance-after-this-payment on the printed slip
            ])
            ->latest('issued_at')
            ->get();

        return view('company.receipts.index', [
            'company' => $company,
            'receipts' => $receipts,
            'menu' => config('menus.company'),
            'contextLabel' => $company->name,
        ]);
    }
}
