<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateCompanySettingsPageRequest;
use App\Models\Company;
use Illuminate\Support\Facades\Storage;

// company_admin-only (see routes/web.php) — plain staff have no
// business changing payment rules, contact details or the receipt
// letterhead.
//
// Letterhead images live on the "public" disk under
// receipt-letterheads/{company id}/ (needs `php artisan storage:link`
// once on the server). Replacing or removing an image deletes the old
// file so nothing orphaned piles up.
class CompanySettingsPageController extends Controller
{
    public function edit(Company $company)
    {
        return view('company.settings.edit', [
            'company' => $company,
            'settings' => $company->paymentSetting,
            'menu' => config('menus.company'),
            'contextLabel' => $company->name,
        ]);
    }

    public function update(UpdateCompanySettingsPageRequest $request, Company $company)
    {
        $company->update($request->safe()->only(['phone', 'email', 'address']));

        $company->paymentSetting->update(
            $request->safe()->only(['allow_partial_payments', 'require_full_payment', 'minimum_partial_percent'])
        );

        $letterhead = ['receipt_note' => $request->validated('receipt_note')];
        foreach (['header', 'footer'] as $slot) {
            $column = "receipt_{$slot}_path";

            if ($request->hasFile("receipt_{$slot}")) {
                $this->deleteFile($company->{$column});
                $letterhead[$column] = $request->file("receipt_{$slot}")
                    ->store("receipt-letterheads/{$company->id}", 'public');
            } elseif ($request->boolean("remove_receipt_{$slot}")) {
                $this->deleteFile($company->{$column});
                $letterhead[$column] = null;
            }
        }
        // forceFill, not update(): these columns may not be in the
        // model's $fillable, and mass assignment silently drops
        // anything that isn't — the file would upload but the path
        // would never reach the database.
        $company->forceFill($letterhead)->save();

        return redirect()->route('company.settings.edit', ['company' => $company->slug])
            ->with('status', 'Settings updated.');
    }

    private function deleteFile(?string $path): void
    {
        if ($path) {
            Storage::disk('public')->delete($path);
        }
    }
}
