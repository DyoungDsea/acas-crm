<?php

namespace App\Http\Requests;

// Extends the shared StoreOrderRequest instead of editing it, because
// Api\OrderController uses that one too and the API has no "create a
// contact while placing the order" concept — loosening contact_id
// there would let an API call through with no contact at all.
//
// On the Orders page the person either picks an existing contact or
// types a new one in (same pattern as "+ Add new category" on the
// Products page). The form disables whichever half isn't in use, so
// only one of contact_id / new_contact_name normally arrives — the
// required_without rules make sure at least one does.
class StoreOrderPageRequest extends StoreOrderRequest
{
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'contact_id' => ['required_without:new_contact_name', 'nullable', 'exists:contacts,id'],
            'new_contact_name' => ['required_without:contact_id', 'nullable', 'string', 'max:255'],
            'new_contact_phone' => ['nullable', 'string', 'max:50'],
            'new_contact_email' => ['nullable', 'email', 'max:255'],
        ]);
    }

    public function messages(): array
    {
        return [
            'contact_id.required_without' => 'Choose a contact, or add a new one.',
            'new_contact_name.required_without' => 'Enter the new contact\'s name.',
        ];
    }
}
