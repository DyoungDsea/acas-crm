<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreStaffRequest;
use App\Models\Company;
use App\Models\Contact;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class StaffPageController extends Controller
{
    public function index(Company $company)
    {
        $staff = User::where('company_id', $company->id)
            ->withCount('assignedContacts')
            ->orderBy('name')
            ->get();

        return view('company.staff.index', [
            'company' => $company,
            'staff' => $staff,
            'menu' => config('menus.company'),
            'contextLabel' => $company->name,
        ]);
    }

    public function store(StoreStaffRequest $request, Company $company)
    {
        $member = User::create([
            'name' => $request->validated('name'),
            'email' => $request->validated('email'),
            'company_id' => $company->id,
            'password' => Hash::make($request->validated('password')),
            'is_active' => true,
        ]);

        $member->assignRole('staff');

        return redirect()->route('company.staff.index', ['company' => $company->slug])->with('status', "{$member->name} added.");
    }

    public function update(StoreStaffRequest $request, Company $company, User $staff)
    {
        abort_unless($staff->company_id === $company->id, 404);

        $data = $request->safe()->only(['name', 'email']);

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->validated('password'));
        }

        $staff->update($data);

        return redirect()->route('company.staff.index', ['company' => $company->slug])->with('status', "{$staff->name} updated.");
    }

    // A staff member who owns contacts can't just be switched off —
    // those contacts would silently sit on a person who can no longer
    // log in, with nothing visibly wrong on screen. If they have any
    // assigned contacts, deactivation requires a valid 'reassign_to'
    // (another active person at this same company) in the same
    // request; the view enforces this via a modal before the form
    // ever submits, and this check backs that up server-side in case
    // that's ever bypassed.
    public function destroy(Request $request, Company $company, User $staff)
    {
        abort_unless($staff->company_id === $company->id, 404);

        $assignedCount = $staff->assignedContacts()->count();

        if ($assignedCount > 0) {
            $reassignTo = $request->input('reassign_to');

            $validTarget = $reassignTo && User::where('company_id', $company->id)
                ->where('is_active', true)
                ->where('id', '!=', $staff->id)
                ->whereKey($reassignTo)
                ->exists();

            if (! $validTarget) {
                return redirect()->route('company.staff.index', ['company' => $company->slug])
                    ->with('status', "{$staff->name} has {$assignedCount} contact(s) assigned. Choose someone to reassign them to before deactivating.");
            }

            Contact::where('assigned_to', $staff->id)->update(['assigned_to' => $reassignTo]);
        }

        $staff->update(['is_active' => false]);

        return redirect()->route('company.staff.index', ['company' => $company->slug])->with('status', "{$staff->name} deactivated.");
    }

    public function activate(Company $company, User $staff)
    {
        abort_unless($staff->company_id === $company->id, 404);

        $staff->update(['is_active' => true]);

        return redirect()->route('company.staff.index', ['company' => $company->slug])->with('status', "{$staff->name} reactivated.");
    }
}
