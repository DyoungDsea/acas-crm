@extends('layouts.app')

@section('title', 'Sales & Rentals')



@php
$editingId = old('transaction_id');
$hasAvailableVehicle = $vehicleOptions->contains(fn ($v) => $v['status'] === 'available');
@endphp

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h4 mb-1">Sales & Rentals</h1>
        <p class="text-muted small mb-0">Every vehicle sale and rental for {{ $company->name }}. Take payment on the Payments page, print from Receipts.</p>
    </div>
    <button type="button" class="btn btn-primary" id="addTransactionBtn" @disabled(! $hasAvailableVehicle || $contacts->isEmpty())>
        <i class="bi bi-plus-lg me-1"></i> Record sale / rental
    </button>
</div>

@if (! $hasAvailableVehicle)
<div class="alert alert-warning">
    No available vehicles — add one, or check one isn't already marked sold/rented, under
    <a href="{{ route('company.vehicle-listings.index', ['company' => $company->slug]) }}">Vehicle Listings</a>.
</div>
@elseif ($contacts->isEmpty())
<div class="alert alert-warning">
    No contacts yet — add one under
    <a href="{{ route('company.contacts.index', ['company' => $company->slug]) }}">Contacts</a> first.
</div>
@endif

<div class="card border-0 shadow-sm">
    <div class="card-body">
        {{-- No delete — a transaction is financial history, same as
             Orders. Edit is only offered while no payment has been
             logged yet (guarded server-side too). Loop variable is
             $row, not $company. --}}
        <table class="table table-hover align-middle w-100" id="transactionsTable">
            <thead>
                <tr>
                    <th>S/N</th>
                    <th>Vehicle</th>
                    <th>Customer</th>
                    <th>Type</th>
                    <th>Total</th>
                    <th>Paid</th>
                    <th>Balance</th>
                    <th>Rental period</th>
                    <th>Status</th>
                    <th class="text-end no-export">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($transactions as $row)
                @php
                $item = $row->items->first();
                $isRental = $row->rental_start_at !== null;
                $hasPayments = $row->payments->isNotEmpty();
                $vehicleStatus = $item?->product?->attributes['vehicle_status'] ?? null;
                @endphp
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td class="fw-semibold">{{ $item->description ?? '—' }}</td>
                    <td>{{ $row->contact->name ?? '—' }}</td>
                    <td>
                        <span class="badge {{ $isRental ? 'text-bg-primary-subtle text-primary-emphasis' : 'text-bg-info-subtle text-info-emphasis' }}">
                            {{ $isRental ? 'Rental' : 'Sale' }}
                        </span>
                    </td>
                    <td>{{ formatNaira($row->total_amount, 2) }}</td>
                    <td>{{ formatNaira($row->amountPaid(), 2) }}</td>
                    <td>{{ formatNaira($row->balanceDue(), 2) }}</td>
                    <td>
                        @if ($isRental)
                        {{ $row->rental_start_at->format('M j, Y') }} &ndash; {{ $row->rental_end_at?->format('M j, Y') ?? 'open' }}
                        @else
                        —
                        @endif
                    </td>
                    <td>
                        @php
                        $statusClass = match ($row->status) {
                        'paid' => 'text-bg-success-subtle text-success-emphasis',
                        'partially_paid' => 'text-bg-warning-subtle text-warning-emphasis',
                        default => 'text-bg-light border',
                        };
                        @endphp
                        <span class="badge {{ $statusClass }}">{{ ucwords(str_replace('_', ' ', $row->status)) }}</span>
                        @if ($isRental && $vehicleStatus === 'available')
                        <span class="badge text-bg-secondary-subtle text-secondary-emphasis ms-1">Returned</span>
                        @endif
                    </td>
                    <td class="text-end no-export">
                        <div class="d-inline-flex gap-1">
                            @if (! $hasPayments)
                            @php
                            $transactionPayload = [
                            'id' => $row->id,
                            'vehicle_product_id' => $item->product_id ?? null,
                            'contact_id' => $row->contact_id,
                            'rental_start_at' => $row->rental_start_at?->format('Y-m-d'),
                            'rental_end_at' => $row->rental_end_at?->format('Y-m-d'),
                            ];
                            @endphp
                            <button type="button"
                                class="btn btn-sm btn-outline-secondary btn-edit-transaction"
                                data-transaction='{{ json_encode($transactionPayload) }}'>
                                <i class="bi bi-pencil"></i> Edit
                            </button>
                            @else
                            <span class="text-muted small">Locked (has payments)</span>
                            @endif

                            @if ($isRental && $vehicleStatus === 'rented')
                            <form method="POST"
                                action="{{ route('company.vehicle-transactions.return', ['company' => $company->slug, 'order' => $row->id]) }}"
                                class="d-inline">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="btn btn-sm btn-outline-secondary">
                                    <i class="bi bi-arrow-return-left"></i> Mark returned
                                </button>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="transactionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="transactionForm" method="POST"
                action="{{ $editingId ? route('company.vehicle-transactions.update', ['company' => $company->slug, 'order' => $editingId]) : route('company.vehicle-transactions.store', ['company' => $company->slug]) }}">
                @csrf
                <input type="hidden" name="_method" id="transactionMethod" value="{{ $editingId ? 'PUT' : '' }}">
                <input type="hidden" name="transaction_id" id="transactionIdField" value="{{ $editingId }}">

                <div class="modal-header">
                    <h5 class="modal-title" id="transactionModalTitle">{{ $editingId ? 'Edit transaction' : 'Record sale / rental' }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="transactionVehicle" class="form-label">Vehicle</label>
                        <select class="form-select @error('vehicle_product_id') is-invalid @enderror" id="transactionVehicle" name="vehicle_product_id" required>
                            <option value="">&mdash; Select &mdash;</option>
                            @foreach ($vehicleOptions as $vehicleOption)
                            <option value="{{ $vehicleOption['id'] }}"
                                data-price="{{ $vehicleOption['price'] }}"
                                data-rental="{{ $vehicleOption['is_rental'] ? '1' : '0' }}"
                                @selected(old('vehicle_product_id')===$vehicleOption['id'])
                                @if ($vehicleOption['status'] !=='available' && ! $editingId) disabled @endif>
                                {{ $vehicleOption['label'] }} ({{ $vehicleOption['is_rental'] ? 'for rent' : 'for sale' }}) — {{ number_format($vehicleOption['price'], 2) }}
                                @if ($vehicleOption['status'] !== 'available') ({{ ucfirst($vehicleOption['status']) }}) @endif
                            </option>
                            @endforeach
                        </select>
                        <div class="form-text">Unavailable vehicles are disabled here — free one up on Vehicle Listings or by editing its own transaction.</div>
                        @error('vehicle_product_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label for="transactionContact" class="form-label">Customer</label>
                        <select class="form-select @error('contact_id') is-invalid @enderror" id="transactionContact" name="contact_id" required>
                            <option value="">&mdash; Select &mdash;</option>
                            @foreach ($contacts as $contactOption)
                            <option value="{{ $contactOption->id }}" @selected(old('contact_id')===$contactOption->id)>
                                {{ $contactOption->name }}
                            </option>
                            @endforeach
                        </select>
                        @error('contact_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="row" id="rentalDatesRow" style="display: none;">
                        <div class="col-md-6 mb-1">
                            <label for="rentalStartAt" class="form-label">Rental start</label>
                            <input type="date" class="form-control @error('rental_start_at') is-invalid @enderror"
                                id="rentalStartAt" name="rental_start_at" value="{{ old('rental_start_at') }}">
                            @error('rental_start_at')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6 mb-1">
                            <label for="rentalEndAt" class="form-label">Rental end <span class="text-muted fw-normal">(optional)</span></label>
                            <input type="date" class="form-control @error('rental_end_at') is-invalid @enderror"
                                id="rentalEndAt" name="rental_end_at" value="{{ old('rental_end_at') }}">
                            @error('rental_end_at')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save transaction</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')


<script>
    $(function() {
        var columnPrint = [0, 1, 2, 3, 4, 5, 6, 7, 8];
        var printTitle = 'Sales & Rentals - {{ $company->name }} - {{ date("d M, Y") }}';
        $('#transactionsTable').DataTable({
            ordering: false,
            paging: false,
            info: false,
            responsive: true,
            columnDefs: [{
                targets: [0],
                orderable: false
            }],
            language: {
                emptyTable: "No sales or rentals recorded yet"
            },
            dom: "<'row mb-3'" +
                "<'col-lg-8 col-md-12 d-flex align-items-center gap-2 text-lg-start text-center mb-2 mb-lg-0'l f>" +
                "<'col-lg-4 col-md-12 text-lg-end text-center mb-2 mb-lg-0'B>" +
                ">" +
                "<'row'<'col-12'tr>>" +
                "<'row mt-3'" +
                "<'col-md-6 col-12 text-md-start text-center'i>" +
                "<'col-md-6 col-12 d-flex justify-content-md-end justify-content-center'p>" +
                ">",
            buttons: [{
                    extend: 'print',
                    className: 'btn-info',
                    title: printTitle,
                    exportOptions: {
                        columns: columnPrint
                    }
                },
                {
                    extend: 'csv',
                    className: 'btn-primary',
                    title: printTitle,
                    exportOptions: {
                        columns: columnPrint
                    }
                },
                {
                    extend: 'excel',
                    className: 'btn-success',
                    title: printTitle,
                    exportOptions: {
                        columns: columnPrint
                    }
                },
                {
                    extend: 'pdf',
                    className: 'btn-warning',
                    title: printTitle,
                    exportOptions: {
                        columns: columnPrint
                    }
                },
            ]
        });

        var $modalEl = $('#transactionModal');
        var modal = new bootstrap.Modal($modalEl[0]);
        var $form = $('#transactionForm');
        var storeUrl = "{{ route('company.vehicle-transactions.store', ['company' => $company->slug]) }}";
        var updateUrlTemplate = "{{ route('company.vehicle-transactions.update', ['company' => $company->slug, 'order' => '__ID__']) }}";

        function toggleRentalDates() {
            var $opt = $('#transactionVehicle').find('option:selected');
            var isRental = $opt.data('rental') == 1;
            $('#rentalDatesRow').toggle(isRental);
            $('#rentalStartAt').prop('required', isRental);
        }

        function openCreateModal() {
            $form[0].reset();
            $form.attr('action', storeUrl);
            $('#transactionMethod').val('');
            $('#transactionIdField').val('');
            $('#transactionModalTitle').text('Record sale / rental');
            $('#rentalDatesRow').hide();
            modal.show();
        }

        function openEditModal(transaction) {
            $form.attr('action', updateUrlTemplate.replace('__ID__', transaction.id));
            $('#transactionMethod').val('PUT');
            $('#transactionIdField').val(transaction.id);
            // This transaction's own vehicle must stay selectable even
            // though it's marked unavailable (it's reserved by this
            // very order) — same reasoning as the Leases unit dropdown.
            $('#transactionVehicle').find('option').prop('disabled', false);
            $('#transactionVehicle').val(transaction.vehicle_product_id);
            $('#transactionContact').val(transaction.contact_id);
            $('#rentalStartAt').val(transaction.rental_start_at || '');
            $('#rentalEndAt').val(transaction.rental_end_at || '');
            $('#transactionModalTitle').text('Edit transaction');
            toggleRentalDates();
            modal.show();
        }

        $('#addTransactionBtn').on('click', openCreateModal);
        $('#transactionVehicle').on('change', toggleRentalDates);
        $('#transactionsTable tbody').on('click', '.btn-edit-transaction', function() {
            openEditModal($(this).data('transaction'));
        });

        @if($errors -> any())
        modal.show();
        @if(old('rental_start_at'))
        $('#rentalDatesRow').show();
        @endif
        @endif
    });
</script>
@endpush
