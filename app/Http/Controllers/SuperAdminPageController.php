<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSuperAdminRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class SuperAdminPageController extends Controller
{
    public function index()
    {
        $superAdmins = User::where('is_super_admin', true)
            ->orderBy('name')
            ->get();

        return view('super-admin.super-admins', [
            'superAdmins' => $superAdmins,
            'menu' => config('menus.super_admin'),
            'contextLabel' => 'Super Admins',
        ]);
    }

    public function store(StoreSuperAdminRequest $request)
    {
        $admin = User::create([
            'name' => $request->validated('name'),
            'email' => $request->validated('email'),
            'password' => Hash::make($request->validated('password')),
            'is_super_admin' => true,
            'is_active' => true,
            'company_id' => null,
        ]);

        return redirect()->route('super-admin.super-admins.index')->with('status', "{$admin->name} created.");
    }

    public function update(StoreSuperAdminRequest $request, User $admin)
    {
        $data = $request->safe()->only(['name', 'email']);

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->validated('password'));
        }

        $admin->update($data);

        return redirect()->route('super-admin.super-admins.index')->with('status', "{$admin->name} updated.");
    }

    public function destroy(Request $request, User $admin)
    {
        if ($request->user()->id === $admin->id) {
            return redirect()->route('super-admin.super-admins.index')
                ->with('status', "You can't deactivate your own account.");
        }

        $admin->update(['is_active' => false]);

        return redirect()->route('super-admin.super-admins.index')->with('status', "{$admin->name} deactivated.");
    }

    public function activate(User $admin)
    {
        $admin->update(['is_active' => true]);

        return redirect()->route('super-admin.super-admins.index')->with('status', "{$admin->name} reactivated.");
    }
}
