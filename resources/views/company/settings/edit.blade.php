@extends('layouts.app')

@section('title', 'Company Settings')

@section('content')
<div class="mb-4">
    <h1 class="h4 mb-1">Company Settings</h1>
    <p class="text-muted small mb-0">{{ $company->name }} — payment rules and contact details.</p>
</div>

<form method="POST" action="{{ route('company.settings.update', ['company' => $company->slug]) }}">
    @csrf
    @method('PUT')

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <h2 class="h6 mb-3">Company details</h2>

            <div class="row g-3 mb-3">
                <div class="col-md-4">
                    <label class="form-label">Name</label>
                    <input type="text" class="form-control" value="{{ $company->name }}" disabled>
                    <div class="form-text">Contact a super admin to change the name, slug, or type.</div>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Slug</label>
                    <input type="text" class="form-control" value="{{ $company->slug }}" disabled>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Type</label>
                    <input type="text" class="form-control" value="{{ $company->type ? ucfirst(str_replace('_', ' ', $company->type)) : '—' }}" disabled>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-md-4">
                    <label for="companyPhone" class="form-label">Phone</label>
                    <input type="text" class="form-control @error('phone') is-invalid @enderror"
                        id="companyPhone" name="phone" value="{{ old('phone', $company->phone) }}">
                    @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label for="companyEmail" class="form-label">Email</label>
                    <input type="email" class="form-control @error('email') is-invalid @enderror"
                        id="companyEmail" name="email" value="{{ old('email', $company->email) }}">
                    @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label for="companyAddress" class="form-label">Address</label>
                    <input type="text" class="form-control @error('address') is-invalid @enderror"
                        id="companyAddress" name="address" value="{{ old('address', $company->address) }}">
                    @error('address')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <h2 class="h6 mb-1">Payment rules</h2>
            <p class="text-muted small mb-3">Controls what staff can do when logging a payment against an order.</p>

            {{-- Hidden fallback before each checkbox — a checkbox
                 sends nothing at all when unchecked, so without this
                 the field would be missing entirely rather than
                 false, and 'required' validation would fail. --}}
            <div class="form-check form-switch mb-3">
                <input type="hidden" name="require_full_payment" value="0">
                <input type="checkbox" class="form-check-input" role="switch" id="requireFullPayment"
                    name="require_full_payment" value="1"
                    @checked(old('require_full_payment', $settings->require_full_payment))>
                <label class="form-check-label" for="requireFullPayment">Require full payment</label>
                <div class="form-text">Orders must be paid in full in one payment — partial payments are refused regardless of the setting below.</div>
            </div>

            <div class="form-check form-switch mb-3">
                <input type="hidden" name="allow_partial_payments" value="0">
                <input type="checkbox" class="form-check-input" role="switch" id="allowPartialPayments"
                    name="allow_partial_payments" value="1"
                    @checked(old('allow_partial_payments', $settings->allow_partial_payments))>
                <label class="form-check-label" for="allowPartialPayments">Allow partial payments</label>
                <div class="form-text">Staff can log a payment smaller than the order total, leaving the balance for later.</div>
            </div>

            <div class="mb-1" style="max-width: 260px;">
                <label for="minimumPartialPercent" class="form-label">Minimum partial payment</label>
                <div class="input-group">
                    <input type="number" min="1" max="100" class="form-control @error('minimum_partial_percent') is-invalid @enderror"
                        id="minimumPartialPercent" name="minimum_partial_percent"
                        value="{{ old('minimum_partial_percent', $settings->minimum_partial_percent) }}">
                    <span class="input-group-text">% of order total</span>
                    @error('minimum_partial_percent')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="form-text">Leave blank to allow any partial amount, however small.</div>
            </div>
        </div>
    </div>

    <button type="submit" class="btn btn-primary">Save settings</button>
</form>
@endsection

@push('scripts')
<script>
    $(function () {
        // Partial-payment controls don't matter once full payment is
        // required — grey them out rather than leaving a confusing,
        // contradictory-looking combination on screen.
        function syncPartialControls() {
            var fullRequired = $('#requireFullPayment').is(':checked');
            $('#allowPartialPayments').prop('disabled', fullRequired);
            $('#minimumPartialPercent').prop('disabled', fullRequired);
        }

        $('#requireFullPayment').on('change', syncPartialControls);
        syncPartialControls();
    });
</script>
@endpush
