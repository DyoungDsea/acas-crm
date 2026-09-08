<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOrderPaymentRequest;
use App\Models\Company;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Receipt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

// Payments are immutable once logged — no update()/destroy() here.
// The only thing that can happen to money already recorded is the
// associated receipt being voided (Receipt is the voidable entity,
// not Payment — see Receipt::void()), and that action is exposed
// from this page rather than Receipts: a payment row is what a staff
// member is looking at when they realise "this one was wrong", so
// that's where the Void button belongs. Receipts stays a read-only,
// print-for-the-customer view.
//
// store() re-enforces the same company payment-settings rules as
// Api\PaymentController (require_full_payment, allow_partial_payments,
// minimum_partial_percent). That logic depends on the order's live
// balance and the parent company's settings, so it has to live here
// too rather than in the FormRequest — same reasoning already noted
// on StorePaymentRequest/StoreOrderPaymentRequest.
class PaymentPageController extends Controller
{
    public function index(Company $company)
    {
        $payments = Payment::whereHas('order', fn($q) => $q->where('company_id', $company->id))
            ->with(['order.contact', 'receivedByUser', 'receipt'])
            ->latest('received_at')
            ->get();

        // Only orders that can still take money — fully paid or void
        // orders don't belong in the "record a payment" dropdown.
        $openOrders = Order::where('company_id', $company->id)
            ->whereIn('status', ['unpaid', 'partially_paid'])
            ->with('contact')
            ->latest()
            ->get()
            ->map(fn($order) => [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'contact_name' => $order->contact->name ?? '—',
                'balance_due' => $order->balanceDue(),
            ]);

        return view('company.payments.index', [
            'company' => $company,
            'payments' => $payments,
            'openOrders' => $openOrders,
            'paymentSetting' => $company->paymentSetting,
            'menu' => config('menus.company'),
            'contextLabel' => $company->name,
        ]);
    }

    public function store(StoreOrderPaymentRequest $request, Company $company)
    {
        $order = Order::where('company_id', $company->id)->findOrFail($request->validated('order_id'));
        $settings = $company->paymentSetting;
        $amount = $request->validated('amount');
        $balance = $order->balanceDue();

        if ($amount > $balance) {
            throw ValidationException::withMessages(['amount' => 'Amount exceeds the remaining balance of ' . number_format($balance, 2) . '.']);
        }

        if ($settings->require_full_payment && $amount < $balance) {
            throw ValidationException::withMessages(['amount' => 'This company requires payment in full.']);
        }

        if (! $settings->require_full_payment && $settings->allow_partial_payments && $settings->minimum_partial_percent) {
            $minimum = $order->total_amount * ($settings->minimum_partial_percent / 100);

            if ($amount < $minimum && $amount < $balance) {
                throw ValidationException::withMessages(['amount' => 'Minimum partial payment is ' . $settings->minimum_partial_percent . '% of the order total (' . number_format($minimum, 2) . ').']);
            }
        }

        $payment = DB::transaction(function () use ($request, $order, $amount) {
            $payment = $order->payments()->create([
                'amount' => $amount,
                'method' => $request->validated('method'),
                'reference_note' => $request->validated('reference_note'),
                'received_at' => $request->validated('received_at') ?? now(),
                'received_by' => Auth::id(),
            ]);
            // Order::refreshStatus() fires automatically via Payment's
            // `created` event — see Payment.php.

            Receipt::create([
                'payment_id' => $payment->id,
                'receipt_number' => $this->generateReceiptNumber($order),
                'issued_by' => Auth::id(),
                'issued_at' => now(),
            ]);

            return $payment;
        });

        return redirect()->route('company.payments.index', ['company' => $company->slug])
            ->with('status', "Payment of {$amount} recorded for order {$order->order_number}.");
    }

    public function void(Company $company, Payment $payment)
    {
        abort_unless($payment->order->company_id === $company->id, 404);

        $receipt = $payment->receipt;
        abort_if(! $receipt, 422, 'This payment has no receipt to void.');
        abort_if($receipt->isVoided(), 422, 'Receipt is already void.');

        $receipt->void();

        return redirect()->route('company.payments.index', ['company' => $company->slug])
            ->with('status', "Payment voided — receipt {$receipt->receipt_number} marked void.");
    }

    // Scoped per-company, same fix already applied to Order numbers
    // in OrderPageController — Api\PaymentController's version of this
    // counts receipts across ALL companies, letting two companies
    // share one drifting global sequence.
    private function generateReceiptNumber(Order $order): string
    {
        $prefix = strtoupper(Str::substr(Str::slug($order->company->name), 0, 3));
        $year = now()->year;
        $sequence = str_pad(
            (string) (Receipt::whereHas('payment.order', fn($q) => $q->where('company_id', $order->company_id))
                ->whereYear('created_at', $year)->count() + 1),
            6,
            '0',
            STR_PAD_LEFT
        );

        return "{$prefix}-{$year}-{$sequence}";
    }
}
