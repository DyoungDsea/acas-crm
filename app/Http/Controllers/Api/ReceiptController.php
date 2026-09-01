<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ReceiptResource;
use App\Models\Receipt;
use Illuminate\Http\Request;

// Receipts are never created directly through this controller — they
// are generated automatically by PaymentController when a payment is
// logged. This controller only reads and voids.
class ReceiptController extends Controller
{
    public function index(Request $request)
    {
        return ReceiptResource::collection(
            Receipt::with('issuedByUser')->latest('issued_at')->paginate($request->integer('per_page', 20))
        );
    }

    public function show(Receipt $receipt)
    {
        return new ReceiptResource($receipt->load('issuedByUser', 'payment.order'));
    }

    public function void(Receipt $receipt)
    {
        abort_if($receipt->isVoided(), 422, 'Receipt is already void.');

        $receipt->void();

        return new ReceiptResource($receipt->fresh());
    }
}
