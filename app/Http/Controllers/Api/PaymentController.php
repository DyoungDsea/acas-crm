<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePaymentRequest;
use App\Http\Resources\PaymentResource;
use App\Models\Order;
use App\Models\Receipt;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

// Reached via /orders/{order}/payments. This is where the company's
// payment_settings (from earlier: allow_partial_payments,
// require_full_payment, minimum_partial_percent) actually get
// enforced — a static FormRequest rules() array can't see the
// order's remaining balance or the parent company, so the check
// lives here instead.
class PaymentController extends Controller
{
    public function store(StorePaymentRequest $request, Order $order)
    {
        $settings = $order->company->paymentSetting;
        $amount = $request->validated('amount');
        $balance = $order->balanceDue();

        if ($amount > $balance) {
            throw ValidationException::withMessages(['amount' => 'Amount exceeds the remaining balance of '.number_format($balance, 2).'.']);
        }

        if ($settings->require_full_payment && $amount < $balance) {
            throw ValidationException::withMessages(['amount' => 'This company requires payment in full.']);
        }

        if (! $settings->require_full_payment && $settings->allow_partial_payments && $settings->minimum_partial_percent) {
            $minimum = $order->total_amount * ($settings->minimum_partial_percent / 100);

            if ($amount < $minimum && $amount < $balance) {
                throw ValidationException::withMessages(['amount' => 'Minimum partial payment is '.$settings->minimum_partial_percent.'% of the order total ('.number_format($minimum, 2).').']);
            }
        }

        $payment = DB::transaction(function () use ($request, $order, $amount) {
            $payment = $order->payments()->create([
                ...$request->safe()->except('received_at'),
                'received_at' => $request->validated('received_at') ?? now(),
                'received_by' => $request->user()->id,
            ]);
            // Order::refreshStatus() fires automatically via the
            // Payment model's `created` event — see Payment.php.

            Receipt::create([
                'payment_id' => $payment->id,
                'receipt_number' => $this->generateReceiptNumber($order),
                'issued_by' => $request->user()->id,
                'issued_at' => now(),
            ]);

            return $payment;
        });

        return new PaymentResource($payment->load('receipt', 'receivedByUser'));
    }

    private function generateReceiptNumber(Order $order): string
    {
        $prefix = strtoupper(Str::substr(Str::slug($order->company->name), 0, 3));
        $year = now()->year;
        $sequence = str_pad((string) (\App\Models\Receipt::whereYear('created_at', $year)->count() + 1), 6, '0', STR_PAD_LEFT);

        return "{$prefix}-{$year}-{$sequence}";
    }
}
