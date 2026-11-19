<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

// Guest is either an existing contact or a brand-new one typed in on
// the form (same "+ Add new" pattern as the Orders page). The view
// disables whichever half is unused, and required_without makes sure
// one of them arrives. Room/contact ownership is checked in the
// controller — a bare exists: rule bypasses CompanyScope.
class StoreReservationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'room_id' => ['required', 'exists:products,id'],
            'contact_id' => ['required_without:new_contact_name', 'nullable', 'exists:contacts,id'],
            'new_contact_name' => ['required_without:contact_id', 'nullable', 'string', 'max:255'],
            'new_contact_phone' => ['nullable', 'string', 'max:50'],
            'new_contact_email' => ['nullable', 'email', 'max:255'],
            'check_in' => ['required', 'date'],
            'check_out' => ['required', 'date', 'after:check_in'],
        ];
    }

    public function messages(): array
    {
        return [
            'contact_id.required_without' => 'Choose a guest, or add a new one.',
            'new_contact_name.required_without' => 'Enter the new guest\'s name.',
            'check_out.after' => 'Check-out must be at least one night after check-in.',
        ];
    }
}
