@extends('layouts.app')

@section('title', 'Rooms')
 

@php
$editingId = old('room_id');
@endphp

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h4 mb-1">Rooms</h1>
        <p class="text-muted small mb-0">Every room at {{ $company->name }} and its nightly rate.</p>
    </div>
    <button type="button" class="btn btn-primary" id="addRoomBtn">
        <i class="bi bi-plus-lg me-1"></i> Add room
    </button>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        {{-- No delete on purpose — see the controller comment. Loop variable is $row, not $company. --}}
        <table class="table table-hover align-middle w-100" id="roomsTable">
            <thead>
                <tr>
                    <th>S/N</th>
                    <th>Room</th>
                    <th>Type</th>
                    <th>Floor</th>
                    <th>Sleeps</th>
                    <th>Rate / night</th>
                    <th>Status</th>
                    <th class="text-end no-export">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($rooms as $row)
                @php
                $attrs = $row->attributes ?? [];
                $manual = $attrs['room_status'] ?? 'available';
                $occupiedBy = $occupiedNow[$row->id] ?? null;
                $payload = [
                'id' => $row->id,
                'room_number' => $attrs['room_number'] ?? '',
                'room_type' => $attrs['room_type'] ?? '',
                'floor' => $attrs['floor'] ?? '',
                'capacity' => $attrs['capacity'] ?? 1,
                'nightly_rate' => $row->unit_price,
                'room_status' => $manual,
                'notes' => $attrs['notes'] ?? '',
                ];
                @endphp
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td class="fw-semibold">{{ $attrs['room_number'] ?? $row->name }}</td>
                    <td>{{ $attrs['room_type'] ?? '—' }}</td>
                    <td>{{ $attrs['floor'] ?? '—' }}</td>
                    <td>{{ $attrs['capacity'] ?? '—' }}</td>
                    <td>{{ formatNaira($row->unit_price) }}</td>
                    <td>
                        @if ($manual === 'maintenance')
                        <span class="badge text-bg-danger-subtle text-danger-emphasis">Maintenance</span>
                        @elseif ($occupiedBy)
                        <span class="badge text-bg-warning-subtle text-warning-emphasis">Occupied · {{ $occupiedBy }}</span>
                        @else
                        <span class="badge text-bg-success-subtle text-success-emphasis">Available</span>
                        @endif
                    </td>
                    <td class="text-end no-export">
                        <button type="button" class="btn btn-sm btn-outline-secondary btn-edit-room"
                            data-room='{{ json_encode($payload) }}'>
                            <i class="bi bi-pencil"></i> Edit
                        </button>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="roomModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="roomForm" method="POST"
                action="{{ $editingId ? route('company.hotel-rooms.update', ['company' => $company->slug, 'hotel_room' => $editingId]) : route('company.hotel-rooms.store', ['company' => $company->slug]) }}">
                @csrf
                <input type="hidden" name="_method" id="roomMethod" value="{{ $editingId ? 'PUT' : '' }}">
                <input type="hidden" name="room_id" id="roomIdField" value="{{ $editingId }}">

                <div class="modal-header">
                    <h5 class="modal-title" id="roomModalTitle">{{ $editingId ? 'Edit room' : 'Add room' }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="roomNumber" class="form-label">Room number</label>
                            <input type="text" class="form-control @error('room_number') is-invalid @enderror"
                                id="roomNumber" name="room_number" value="{{ old('room_number') }}" placeholder="e.g. 101" required>
                            @error('room_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="roomType" class="form-label">Room type</label>
                            <input type="text" list="roomTypeList" class="form-control @error('room_type') is-invalid @enderror"
                                id="roomType" name="room_type" value="{{ old('room_type') }}" placeholder="e.g. Deluxe" required>
                            <datalist id="roomTypeList">
                                @foreach ($rooms->map(fn ($r) => $r->attributes['room_type'] ?? null)->filter()->unique() as $t)
                                <option value="{{ $t }}">
                                    @endforeach
                                <option value="Standard">
                                <option value="Deluxe">
                                <option value="Executive">
                                <option value="Suite">
                            </datalist>
                            @error('room_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="roomFloor" class="form-label">Floor <span class="text-muted fw-normal">(optional)</span></label>
                            <input type="text" class="form-control @error('floor') is-invalid @enderror"
                                id="roomFloor" name="floor" value="{{ old('floor') }}">
                            @error('floor')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="roomCapacity" class="form-label">Sleeps</label>
                            <input type="number" min="1" max="20" class="form-control @error('capacity') is-invalid @enderror"
                                id="roomCapacity" name="capacity" value="{{ old('capacity', 2) }}" required>
                            @error('capacity')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="roomRate" class="form-label">Rate / night (₦)</label>
                            <input type="number" step="0.01" min="0" class="form-control @error('nightly_rate') is-invalid @enderror"
                                id="roomRate" name="nightly_rate" value="{{ old('nightly_rate') }}" required>
                            @error('nightly_rate')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="roomStatus" class="form-label">Status</label>
                        <select class="form-select @error('room_status') is-invalid @enderror" id="roomStatus" name="room_status" required>
                            <option value="available" @selected(old('room_status', 'available' )==='available' )>Available for booking</option>
                            <option value="maintenance" @selected(old('room_status')==='maintenance' )>Maintenance (cannot be booked)</option>
                        </select>
                        <div class="form-text">"Occupied" is shown automatically from reservations — you don't set it here.</div>
                        @error('room_status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-1">
                        <label for="roomNotes" class="form-label">Notes <span class="text-muted fw-normal">(optional)</span></label>
                        <input type="text" class="form-control @error('notes') is-invalid @enderror"
                            id="roomNotes" name="notes" value="{{ old('notes') }}" placeholder="e.g. AC, king bed, balcony">
                        @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save room</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')

<script>
    $(function() {
        var columnPrint = [0, 1, 2, 3, 4, 5, 6];
        var printTitle = 'Rooms - {{ $company->name }} - {{ date("d M, Y") }}';
        $('#roomsTable').DataTable({
            ordering: false,
            paging: false,
            info: false,
            responsive: true,
            columnDefs: [{
                targets: [0],
                orderable: false
            }],
            language: {
                emptyTable: "No rooms yet"
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

        var modal = new bootstrap.Modal($('#roomModal')[0]);
        var $form = $('#roomForm');
        var storeUrl = "{{ route('company.hotel-rooms.store', ['company' => $company->slug]) }}";
        var updateUrlTemplate = "{{ route('company.hotel-rooms.update', ['company' => $company->slug, 'hotel_room' => '__ID__']) }}";

        $('#addRoomBtn').on('click', function() {
            $form[0].reset();
            $form.attr('action', storeUrl);
            $('#roomMethod').val('');
            $('#roomIdField').val('');
            $('#roomModalTitle').text('Add room');
            $('#roomCapacity').val(2);
            modal.show();
        });

        $('#roomsTable tbody').on('click', '.btn-edit-room', function() {
            var r = $(this).data('room');
            $form.attr('action', updateUrlTemplate.replace('__ID__', r.id));
            $('#roomMethod').val('PUT');
            $('#roomIdField').val(r.id);
            $('#roomNumber').val(r.room_number);
            $('#roomType').val(r.room_type);
            $('#roomFloor').val(r.floor || '');
            $('#roomCapacity').val(r.capacity);
            $('#roomRate').val(r.nightly_rate);
            $('#roomStatus').val(r.room_status);
            $('#roomNotes').val(r.notes || '');
            $('#roomModalTitle').text('Edit room ' + r.room_number);
            modal.show();
        });

        @if($errors -> any())
        modal.show();
        @endif
    });
</script>
@endpush
