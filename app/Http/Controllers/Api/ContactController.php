<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreContactRequest;
use App\Http\Resources\ContactResource;
use App\Models\Contact;
use Illuminate\Http\Request;

// No manual company filtering needed anywhere in this controller —
// the CompanyScope global scope (via BelongsToCompany on Contact)
// already restricts every query to the acting user's company.
class ContactController extends Controller
{
    public function index(Request $request)
    {
        $query = Contact::query();

        if ($request->filled('type')) {
            $query->where('type', $request->string('type'));
        }

        return ContactResource::collection(
            $query->latest()->paginate($request->integer('per_page', 20))
        );
    }

    public function store(StoreContactRequest $request)
    {
        return new ContactResource(Contact::create($request->validated()));
    }

    public function show(Contact $contact)
    {
        return new ContactResource($contact->load('assignedUser'));
    }

    public function update(StoreContactRequest $request, Contact $contact)
    {
        $contact->update($request->validated());

        return new ContactResource($contact);
    }

    public function destroy(Contact $contact)
    {
        $contact->delete(); // soft delete

        return response()->noContent();
    }
}
