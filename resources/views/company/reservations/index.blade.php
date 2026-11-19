@extends('layouts.app')

@section('title', 'Reservations')


@php
$editingId = old('reservation_id');
$hasBookableRoom = $rooms->contains(fn ($r) => $r['bookable']);
$today = now()->startOfDay();
@endphp

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h4 mb-1">Reservations</h1>
        <p class="text-muted small mb-0">Every stay at {{ $company->name }}. Take payment on the Payments page, print from Receipts.</p>
    </div>
    <button type="button" class="btn btn-primary" id="addReservationBtn" @disabled(! $hasBookableRoom)>
        <i class="bi bi-plus-lg me-1"></i> New reservation
    </button>
</div>

@if (! $hasBookableRoom)
<div class="alert alert-warning">
    No bookable rooms — add one under
    <a href="{{ route('company.hotel-rooms.index', ['company' => $company->slug]) }}">Rooms</a>.
</div>
@endif

<div class="card border-0 shadow-sm">
    <div class="card-body">
        {{-- No delete — a reservation is financial history. Edit and
             Cancel are only offered while no payment exists (guarded
             server-side too). Loop variable is $row, not $company. --}}
        <table class="table table-hover align-middle w-100" id="reservationsTable">
            <thead>
                <tr>
                    <th>S/N</th>
                    <th>Reservation #</th>
                    <th>Guest</th>
                    <th>Room</th>
                    <th>Check-in</th>
                    <th>Check-out</th>
                    <th>Nights</th>
                    <th>Total</th>
                    <th>Paid</th>
                    <th>Balance</th>
                    <th>Status</th>
                    <th class="text-end no-export">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($reservations as $row)
                @php
                $item = $row->items->first();
                $cancelled = $row->status === 'void';
                $hasPayments = $row->payments->isNotEmpty();
                $paid = $row->payments->sum('amount');
                $phase = match (true) {
                $row->rental_end_at->lte($today) => ['Checked out', 'text-bg-secondary-subtle text-secondary-emphasis'],
                $row->rental_start_at->lte($today) => ['In house', 'text-bg-warning-subtle text-warning-emphasis'],
                default => ['Upcoming', 'text-bg-info-subtle text-info-emphasis'],
                };
                $payClass = match ($row->status) {
                'paid' => 'text-bg-success-subtle text-success-emphasis',
                'partially_paid' => 'text-bg-warning-subtle text-warning-emphasis',
                default => 'text-bg-light border',
                };
                @endphp
                <tr class="{{ $cancelled ? 'text-muted' : '' }}">
                    <td>{{ $loop->iteration }}</td>
                    <td class="fw-semibold">{{ $row->order_number }}</td>
                    <td>{{ $row->contact->name ?? '—' }}</td>
                    <td>{{ $item->product->name ?? '—' }}</td>
                    <td>{{ $row->rental_start_at->format('d M Y') }}</td>
                    <td>{{ $row->rental_end_at->format('d M Y') }}</td>
                    <td>{{ $item->quantity ?? '—' }}</td>
                    <td>{{ formatNaira($row->total_amount) }}</td>
                    <td>{{ $cancelled ? '—' : formatNaira($paid) }}</td>
                    <td>{{ $cancelled ? '—' : formatNaira($row->total_amount - $paid) }}</td>
                    <td>
                        @if ($cancelled)
                        <span class="badge text-bg-danger-subtle text-danger-emphasis">Cancelled</span>
                        @else
                        <span class="badge {{ $payClass }}">{{ ucwords(str_replace('_', ' ', $row->status)) }}</span>
                        <span class="badge {{ $phase[1] }} ms-1">{{ $phase[0] }}</span>
                        @endif
                    </td>
                    <td class="text-end no-export">
                        @if ($cancelled)
                        <span class="text-muted small">Cancelled</span>
                        @elseif ($hasPayments)
                        <span class="text-muted small">Locked (has payments)</span>
                        @else
                        @php
                        $payload = [
                        'id' => $row->id,
                        'room_id' => $item->product_id ?? null,
                        'contact_id' => $row->contact_id,
                        'check_in' => $row->rental_start_at->format('Y-m-d'),
                        'check_out' => $row->rental_end_at->format('Y-m-d'),
                        ];
                        @endphp
                        <div class="d-inline-flex gap-1">
                            <button type="button" class="btn btn-sm btn-outline-secondary btn-edit-reservation"
                                data-reservation='{{ json_encode($payload) }}'>
                                <i class="bi bi-pencil"></i> Edit
                            </button>
                            <form method="POST"
                                action="{{ route('company.reservations.cancel', ['company' => $company->slug, 'order' => $row->id]) }}"
                                class="d-inline form-cancel-reservation">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                    <i class="bi bi-x-circle"></i> Cancel
                                </button>
                            </form>
                        </div>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="reservationModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="reservationForm" method="POST"
                action="{{ $editingId ? route('company.reservations.update', ['company' => $company->slug, 'order' => $editingId]) : route('company.reservations.store', ['company' => $company->slug]) }}">
                @csrf
                <input type="hidden" name="_method" id="reservationMethod" value="{{ $editingId ? 'PUT' : '' }}">
                <input type="hidden" name="reservation_id" id="reservationIdField" value="{{ $editingId }}">

                <div class="modal-header">
                    <h5 class="modal-title" id="reservationModalTitle">{{ $editingId ? 'Edit reservation' : 'New reservation' }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="resRoom" class="form-label">Room</label>
                        <select class="form-select @error('room_id') is-invalid @enderror" id="resRoom" name="room_id" required>
                            <option value="">&mdash; Select &mdash;</option>
                            @foreach ($rooms as $roomOption)
                            <option value="{{ $roomOption['id'] }}" data-rate="{{ $roomOption['rate'] }}"
                                data-bookable="{{ $roomOption['bookable'] ? '1' : '0' }}"
                                @selected(old('room_id')===$roomOption['id'])
                                @disabled(! $roomOption['bookable'] && old('room_id') !==$roomOption['id'])>
                                {{ $roomOption['label'] }} — {{ formatNaira($roomOption['rate']) }}/night
                                @if ($roomOption['capacity']) · sleeps {{ $roomOption['capacity'] }} @endif
                                @if (! $roomOption['bookable']) (maintenance) @endif
                            </option>
                            @endforeach
                        </select>
                        @error('room_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <label class="form-label mb-1" for="resContact">Guest</label>
                            <button type="button" class="btn btn-link btn-sm p-0" id="toggleNewGuest">+ Add new guest</button>
                        </div>
                        <div id="existingGuestBlock">
                            <select class="form-select @error('contact_id') is-invalid @enderror" id="resContact" name="contact_id" required>
                                <option value="">&mdash; Select &mdash;</option>
                                @foreach ($contacts as $contactOption)
                                <option value="{{ $contactOption->id }}" @selected(old('contact_id')===$contactOption->id)>{{ $contactOption->name }}</option>
                                @endforeach
                            </select>
                            @error('contact_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div id="newGuestBlock" style="display: none;">
                            <input type="text" class="form-control mb-2 @error('new_contact_name') is-invalid @enderror"
                                id="newGuestName" name="new_contact_name" value="{{ old('new_contact_name') }}" placeholder="Guest full name" disabled>
                            @error('new_contact_name')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            <div class="row g-2">
                                <div class="col-6">
                                    <input type="text" class="form-control" id="newGuestPhone" name="new_contact_phone"
                                        value="{{ old('new_contact_phone') }}" placeholder="Phone (optional)" disabled>
                                </div>
                                <div class="col-6">
                                    <input type="email" class="form-control @error('new_contact_email') is-invalid @enderror" id="newGuestEmail"
                                        name="new_contact_email" value="{{ old('new_contact_email') }}" placeholder="Email (optional)" disabled>
                                    @error('new_contact_email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="resCheckIn" class="form-label">Check-in</label>
                            <input type="date" class="form-control @error('check_in') is-invalid @enderror"
                                id="resCheckIn" name="check_in" value="{{ old('check_in') }}" required>
                            @error('check_in')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="resCheckOut" class="form-label">Check-out</label>
                            <input type="date" class="form-control @error('check_out') is-invalid @enderror"
                                id="resCheckOut" name="check_out" value="{{ old('check_out') }}" required>
                            @error('check_out')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="alert alert-light border mb-0 py-2" id="stayPreview" style="display: none;"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save reservation</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')

<script>
    $(function() {
        var columnPrint = [0, 1, 2, 3, 4, 5, 6, 7, 8, 9];
        var printTitle = 'Reservations - {{ $company->name }} - {{ date("d M, Y") }}';
        $('#reservationsTable').DataTable({
            ordering: false,
            paging: false,
            info: false,
            responsive: true,
            columnDefs: [{
                targets: [0],
                orderable: false
            }],
            language: {
                emptyTable: "No reservations yet"
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

        var modal = new bootstrap.Modal($('#reservationModal')[0]);
        var $form = $('#reservationForm');
        var storeUrl = "{{ route('company.reservations.store', ['company' => $company->slug]) }}";
        var updateUrlTemplate = "{{ route('company.reservations.update', ['company' => $company->slug, 'order' => '__ID__']) }}";

        // Disabled inputs aren't submitted, so exactly one of
        // contact_id / new_contact_* reaches the server.
        function setNewGuestMode(on) {
            $('#existingGuestBlock').toggle(!on);
            $('#newGuestBlock').toggle(on);
            $('#resContact').prop('disabled', on).prop('required', !on);
            $('#newGuestName').prop('disabled', !on).prop('required', on);
            $('#newGuestPhone, #newGuestEmail').prop('disabled', !on);
            $('#toggleNewGuest').text(on ? '← Pick existing guest' : '+ Add new guest');
            if (on) {
                $('#resContact').val('');
            }
        }
        $('#toggleNewGuest').on('click', function() {
            setNewGuestMode($('#newGuestBlock').is(':hidden'));
        });

        function naira(n) {
            return '₦' + Number(n).toLocaleString('en-NG', {
                maximumFractionDigits: 0
            });
        }

        function updatePreview() {
            var rate = parseFloat($('#resRoom option:selected').data('rate'));
            var a = $('#resCheckIn').val(),
                b = $('#resCheckOut').val();
            var $p = $('#stayPreview');
            if (isNaN(rate) || !a || !b) {
                $p.hide();
                return;
            }
            var nights = Math.round((new Date(b) - new Date(a)) / 86400000);
            if (nights < 1) {
                $p.text('Check-out must be after check-in.').show();
                return;
            }
            $p.text(nights + (nights === 1 ? ' night' : ' nights') + ' × ' + naira(rate) + ' = ' + naira(nights * rate)).show();
        }
        $('#resRoom, #resCheckIn, #resCheckOut').on('change input', updatePreview);
        $('#resCheckIn').on('change', function() {
            var v = $(this).val();
            if (v) {
                $('#resCheckOut').attr('min', v);
            }
        });

        $('#addReservationBtn').on('click', function() {
            $form[0].reset();
            $form.attr('action', storeUrl);
            $('#reservationMethod').val('');
            $('#reservationIdField').val('');
            $('#reservationModalTitle').text('New reservation');
            $('#resRoom option').each(function() {
                $(this).prop('disabled', $(this).data('bookable') == 0);
            });
            setNewGuestMode(false);
            updatePreview();
            modal.show();
        });

        $('#reservationsTable tbody').on('click', '.btn-edit-reservation', function() {
            var r = $(this).data('reservation');
            $form.attr('action', updateUrlTemplate.replace('__ID__', r.id));
            $('#reservationMethod').val('PUT');
            $('#reservationIdField').val(r.id);
            // The reservation's own room stays selectable even if it
            // has since gone into maintenance.
            $('#resRoom option').each(function() {
                $(this).prop('disabled', $(this).data('bookable') == 0 && $(this).val() !== r.room_id);
            });
            $('#resRoom').val(r.room_id);
            setNewGuestMode(false);
            $('#resContact').val(r.contact_id);
            $('#resCheckIn').val(r.check_in);
            $('#resCheckOut').val(r.check_out);
            $('#reservationModalTitle').text('Edit reservation');
            updatePreview();
            modal.show();
        });

        $('.form-cancel-reservation').on('submit', function(e) {
            e.preventDefault();
            var $f = $(this);
            if (window.alertify) {
                alertify.confirm('Cancel reservation', 'The room becomes available again and this reservation drops out of revenue. It stays on record as cancelled. Continue?',
                    function() {
                        $f.off('submit').trigger('submit');
                    },
                    function() {});
            } else {
                $f.off('submit').trigger('submit');
            }
        });

        @if($errors -> any())
        @if(old('new_contact_name') !== null && old('contact_id') === null)
        setNewGuestMode(true);
        @endif
        updatePreview();
        modal.show();
        @endif
    });
</script>
@endpush
