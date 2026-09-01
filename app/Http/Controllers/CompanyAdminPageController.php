<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCompanyAdminRequest;
use App\Models\Company;
use App\Models\Contact;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class CompanyAdminPageController extends Controller
{
    public function index()
    {
        $companyAdmins = User::role('company_admin')
            ->with('company')
            ->withCount('assignedContacts')
            ->orderBy('name')
            ->get();

        return view('super-admin.company-admins', [
            'companyAdmins' => $companyAdmins,
            'companies' => Company::orderBy('name')->get(['id', 'name']),
            'menu' => config('menus.super_admin'),
            'contextLabel' => 'Company Admins',
        ]);
    }

    public function store(StoreCompanyAdminRequest $request)
    {
        $admin = User::create([
            'name' => $request->validated('name'),
            'email' => $request->validated('email'),
            'company_id' => $request->validated('company_id'),
            'password' => Hash::make($request->validated('password')),
            'is_active' => true,
        ]);

        $admin->assignRole('company_admin');

        return redirect()->route('super-admin.company-admins.index')->with('status', "{$admin->name} created.");
    }

    public function update(StoreCompanyAdminRequest $request, User $company_admin)
    {
        $data = $request->safe()->only(['name', 'email', 'company_id']);

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->validated('password'));
        }

        $company_admin->update($data);

        return redirect()->route('super-admin.company-admins.index')->with('status', "{$company_admin->name} updated.");
    }

    // Company admins can be assigned to contacts exactly like staff
    // can (the "Assigned to" dropdown on a contact just lists users
    // at that company, role doesn't matter) — so the same
    // reassign-before-deactivate rule from StaffPageController
    // applies here too. The valid reassignment target is scoped to
    // the ADMIN'S OWN company, not the acting super admin's — there
    // isn't one, since super admins aren't tied to any company.
    public function destroy(Request $request, User $company_admin)
    {
        $assignedCount = $company_admin->assignedContacts()->count();

        if ($assignedCount > 0) {
            $reassignTo = $request->input('reassign_to');

            $validTarget = $reassignTo && User::where('company_id', $company_admin->company_id)
                ->where('is_active', true)
                ->where('id', '!=', $company_admin->id)
                ->whereKey($reassignTo)
                ->exists();

            if (! $validTarget) {
                return redirect()->route('super-admin.company-admins.index')
                    ->with('status', "{$company_admin->name} has {$assignedCount} contact(s) assigned. Choose someone to reassign them to before deactivating.");
            }

            Contact::where('assigned_to', $company_admin->id)->update(['assigned_to' => $reassignTo]);
        }

        $company_admin->update(['is_active' => false]);

        return redirect()->route('super-admin.company-admins.index')->with('status', "{$company_admin->name} deactivated.");
    }

    public function activate(User $company_admin)
    {
        $company_admin->update(['is_active' => true]);

        return redirect()->route('super-admin.company-admins.index')->with('status', "{$company_admin->name} reactivated.");
    }
}
