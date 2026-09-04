<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePharmacyBatchRequest;
use App\Models\Company;
use App\Models\Contact;
use App\Models\PharmacyBatch;
use App\Models\Product;

// Pharmacy-only. The sidebar already hides this page from any other
// company type via config/menus.php's 'types' filter, but that's a
// UX convenience, not enforcement — a URL typed directly bypasses
// the menu entirely, so every method here also checks the company's
// actual type before doing anything.
//
// No destroy()/activate() on purpose: a batch record is inventory
// history — what came in, when, at what expiry — and deleting one
// would corrupt that audit trail the same way deleting a Receipt
// would. Correcting a mistake means editing it, not removing it.
class PharmacyBatchPageController extends Controller
{
    public function index(Company $company)
    {
        abort_unless($company->type === 'pharmacy', 404);

        $batches = PharmacyBatch::where('company_id', $company->id)
            ->with(['product', 'vendorContact'])
            ->orderBy('expiry_date') // FEFO — soonest-expiring stock listed first
            ->get();

        return view('company.pharmacy-batches.index', [
            'company' => $company,
            'batches' => $batches,
            'products' => Product::where('company_id', $company->id)->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'vendors' => Contact::where('company_id', $company->id)->where('type', 'vendor')->orderBy('name')->get(['id', 'name']),
            'menu' => config('menus.company'),
            'contextLabel' => $company->name,
        ]);
    }

    public function store(StorePharmacyBatchRequest $request, Company $company)
    {
        abort_unless($company->type === 'pharmacy', 404);
        $this->assertBelongsToCompany($request, $company);

        $batch = PharmacyBatch::create([
            ...$request->validated(),
            'company_id' => $company->id,
            // A batch always starts fully in stock — quantity_remaining
            // only ever moves via sales drawdown logic (not built yet),
            // never entered by hand.
            'quantity_remaining' => $request->validated('quantity_received'),
        ]);

        return redirect()->route('company.pharmacy-batches.index', ['company' => $company->slug])
            ->with('status', "Batch {$batch->batch_number} added.");
    }

    public function update(StorePharmacyBatchRequest $request, Company $company, PharmacyBatch $pharmacy_batch)
    {
        abort_unless($company->type === 'pharmacy' && $pharmacy_batch->company_id === $company->id, 404);
        $this->assertBelongsToCompany($request, $company);

        $pharmacy_batch->update($request->validated());

        return redirect()->route(
            'company.pharmacy-batches.index',
            ['company' => $company->slug]
        )
            ->with('status', "Batch {$pharmacy_batch->batch_number} updated.");
    }

    // Both product_id and vendor_contact_id use bare 'exists:' rules
    // in StorePharmacyBatchRequest, which only confirm the row
    // exists SOMEWHERE — not that it belongs to this company (the
    // validator queries the raw table directly, bypassing
    // CompanyScope). Same gap already fixed for Deals; closing it
    // here too now that a second company-scoped relationship
    // (vendor_contact_id) exists on this form.
    private function assertBelongsToCompany(StorePharmacyBatchRequest $request, Company $company): void
    {
        $productOk = Product::where('id', $request->validated('product_id'))
            ->where('company_id', $company->id)
            ->exists();

        $vendorId = $request->validated('vendor_contact_id');
        $vendorOk = ! $vendorId || Contact::where('id', $vendorId)
            ->where('company_id', $company->id)
            ->exists();

        abort_unless($productOk && $vendorOk, 422, 'Selected product or vendor does not belong to this company.');
    }
}
