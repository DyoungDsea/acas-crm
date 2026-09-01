<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreContactRequest;
use App\Models\Company;
use App\Models\Contact;
use App\Models\User;
use Illuminate\Http\Request;

// A super admin browsing into a company must only see THIS company's
// contacts — CompanyScope intentionally skips its own filter for
// super admins (correct for cross-company views like the group
// dashboard, wrong here), so every query below explicitly filters by
// the $company bound from the URL rather than relying on the
// automatic scope. Same reasoning for the ownership check in
// update/destroy/restore: two independent route segments
// ({company:slug} and {contact}) mean nothing stops someone from
// pairing a valid contact ID with the wrong company's slug in the
// URL unless it's checked explicitly.
class ContactPageController extends Controller
{
    public function index(Request $request, Company $company)
    {
        $showTrashed = $request->boolean('trashed');

        $contacts = ($showTrashed ? Contact::onlyTrashed() : Contact::query())
            ->where('company_id', $company->id)
            ->with('assignedUser')
            ->orderBy('name')
            ->get();

        return view('company.contacts.index', [
            'company' => $company,
            'contacts' => $contacts,
            'showTrashed' => $showTrashed,
            'staff' => User::where('company_id', $company->id)->orderBy('name')->get(['id', 'name']),
            'menu' => config('menus.company'),
            'contextLabel' => $company->name,
        ]);
    }

    public function store(StoreContactRequest $request, Company $company)
    {
        $contact = Contact::create([
            ...$request->validated(),
            'company_id' => $company->id,
        ]);

        return redirect()->route('company.contacts.index', ['company' => $company->slug])->with('status', "{$contact->name} added.");
    }

    public function update(StoreContactRequest $request, Company $company, Contact $contact)
    {
        abort_unless($contact->company_id === $company->id, 404);

        $contact->update($request->validated());

        return redirect()->route('company.contacts.index', ['company' => $company->slug])->with('status', "{$contact->name} updated.");
    }

    public function destroy(Company $company, Contact $contact)
    {
        abort_unless($contact->company_id === $company->id, 404);

        $contact->delete(); // soft delete — recoverable via restore()

        return redirect()->route('company.contacts.index', ['company' => $company->slug])->with('status', "{$contact->name} deleted.");
    }

    public function restore(Company $company, Contact $contact)
    {
        abort_unless($contact->company_id === $company->id, 404);

        $contact->restore();

        return redirect()->route('company.contacts.index', ['company' => $company->slug, 'trashed' => 1])
            ->with('status', "{$contact->name} restored.");
    }
}
