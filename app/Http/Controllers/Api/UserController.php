<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

// EnsureCompanyAdmin middleware on the route — a company admin can
// only create/manage staff within their own company (enforced by the
// BelongsToCompany-style global scope isn't on User directly, so the
// controller sets company_id from the acting admin explicitly below).
class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = $request->user()->is_super_admin
            ? User::query()
            : User::where('company_id', $request->user()->company_id);

        return UserResource::collection($query->paginate($request->integer('per_page', 20)));
    }

    public function store(StoreUserRequest $request)
    {
        $companyId = $request->user()->is_super_admin
            ? $request->validated('company_id')
            : $request->user()->company_id;

        $user = User::create([
            ...$request->safe()->except(['password', 'role', 'company_id']),
            'company_id' => $companyId,
            'password' => Hash::make($request->validated('password')),
        ]);

        $user->assignRole($request->validated('role'));

        return new UserResource($user);
    }

    public function show(User $user)
    {
        return new UserResource($user->load('roles'));
    }

    public function update(StoreUserRequest $request, User $user)
    {
        $data = $request->safe()->except(['password', 'role', 'company_id']);

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->validated('password'));
        }

        $user->update($data);

        if ($request->filled('role')) {
            $user->syncRoles([$request->validated('role')]);
        }

        return new UserResource($user);
    }

    public function destroy(User $user)
    {
        $user->update(['is_active' => false]);

        return response()->noContent();
    }
}
