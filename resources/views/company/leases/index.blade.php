@extends('layouts.app')

@section('title', 'Leases')

@push('styles')
<link href="{{ asset('assets/DataTables/datatables.min.css') }}" rel="stylesheet">
<link href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css" rel="stylesheet">
@endpush

@php
$editingId = old('lease_id');
@endphp

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h4 mb-1">Leases</h1>
        <p class="text-muted small mb-0">Who's renting what, across every property, for {{ $company->name }}.</p>
    </div>
    <button type="button" class="btn btn-primary" id="addLeaseBtn" @disabled($units->isEmpty() || $contacts->isEmpty())>
        <i class="bi bi-plus-lg me-1"></i> Add lease
    </button>
</div>

@if ($units->isEmpty())
<div class="alert alert-warning">
    No property units yet — add a property and at least one unit under
    <a href="{{ route('company.properties.index', ['company' => $company->slug]) }}">Properties</a> first.
</div>
@elseif ($contacts->isEmpty())
<div class="alert alert-warning">
    No contacts yet — add one under
    <a href="{{ route('company.contacts.index', ['company' => $company->slug]) }}">Contacts</a> first.
</div>
@endif

<div class="card border-0 shadow-sm">
    <div class="card-body">
        {{-- No delete action on purpose — a lease is history. Ending
             one means setting status to Ended/Terminated, not
             removing it. Loop variable is $row, not $company. --}}
        <table class="table table-hover align-middle w-100" id="leasesTable">
            <thead>
                <tr>
                    <th>S/N</th>
                    <th>Property / unit</th>
                    <th>Tenant</th>
                    <th>Rent</th>
                    <th>Start</th>
                    <th>End</th>
                    <th>Status</th>
                    <th class="text-end no-export">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($leases as $row)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td class="fw-semibold">{{ $row->propertyUnit->property->title ?? '—' }} — {{ $row->propertyUnit->unit_label ?? '—' }}</td>
                    <td>{{ $row->contact->name ?? '—' }}</td>
                    <td>{{ formatNaira($row->rent_amount, 2) }}</td>
                    <td>{{ $row->start_date->format('M j, Y') }}</td>
                    <td>{{ $row->end_date?->format('M j, Y') ?? '—' }}</td>
                    <td>
                        @php
                        $statusClass = match ($row->status) {
                        'active' => 'text-bg-success-subtle text-success-emphasis',
                        'ended' => 'text-bg-secondary-subtle text-secondary-emphasis',
                        'terminated' => 'text-bg-danger-subtle text-danger-emphasis',
                        default => 'text-bg-light border',
                        };
                        @endphp
                        <span class="badge {{ $statusClass }}">{{ ucfirst($row->status) }}</span>
                    </td>
                    <td class="text-end no-export">
                        @php
                        $leasePayload = [
                        'id' => $row->id,
                        'property_unit_id' => $row->property_unit_id,
                        'contact_id' => $row->contact_id,
                        'start_date' => $row->start_date->format('Y-m-d'),
                        'end_date' => $row->end_date?->format('Y-m-d'),
                        'rent_amount' => $row->rent_amount,
                        'status' => $row->status,
                        ];
                        @endphp
                        <button type="button"
                            class="btn btn-sm btn-outline-secondary btn-edit-lease"
                            data-lease='{{ json_encode($leasePayload) }}'>
                            <i class="bi bi-pencil"></i> Edit
                        </button>
                    </td>
                </tr>
                @empty
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="leaseModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="leaseForm" method="POST"
                action="{{ $editingId ? route('company.leases.update', ['company' => $company->slug, 'lease' => $editingId]) : route('company.leases.store', ['company' => $company->slug]) }}">
                @csrf
                <input type="hidden" name="_method" id="leaseMethod" value="{{ $editingId ? 'PUT' : '' }}">
                <input type="hidden" name="lease_id" id="leaseIdField" value="{{ $editingId }}">

                <div class="modal-header">
                    <h5 class="modal-title" id="leaseModalTitle">{{ $editingId ? 'Edit lease' : 'Add lease' }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="leaseUnit" class="form-label">Unit</label>
                        <select class="form-select @error('property_unit_id') is-invalid @enderror" id="leaseUnit" name="property_unit_id" required>
                            <option value="">&mdash; Select &mdash;</option>
                            @foreach ($units as $unitOption)
                            <option value="{{ $unitOption['id'] }}" @selected(old('property_unit_id')===$unitOption['id'])
                                @if ($unitOption['status']==='occupied' && ! $editingId) disabled @endif>
                                {{ $unitOption['label'] }} @if ($unitOption['status'] === 'occupied') (occupied) @endif
                            </option>
                            @endforeach
                        </select>
                        <div class="form-text">Occupied units are disabled here — end the existing lease on that unit first.</div>
                        @error('property_unit_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label for="leaseContact" class="form-label">Tenant</label>
                        <select class="form-select @error('contact_id') is-invalid @enderror" id="leaseContact" name="contact_id" required>
                            <option value="">&mdash; Select &mdash;</option>
                            @foreach ($contacts as $contactOption)
                            <option value="{{ $contactOption->id }}" @selected(old('contact_id')===$contactOption->id)>
                                {{ $contactOption->name }}
                            </option>
                            @endforeach
                        </select>
                        @error('contact_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="leaseRent" class="form-label">Rent amount</label>
                            <input type="number" step="0.01" min="0" class="form-control @error('rent_amount') is-invalid @enderror"
                                id="leaseRent" name="rent_amount" value="{{ old('rent_amount') }}" required>
                            @error('rent_amount')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="leaseStatus" class="form-label">Status</label>
                            <select class="form-select @error('status') is-invalid @enderror" id="leaseStatus" name="status" required>
                                @foreach (['active' => 'Active', 'ended' => 'Ended', 'terminated' => 'Terminated'] as $value => $label)
                                <option value="{{ $value }}" @selected(old('status')===$value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-1">
                            <label for="leaseStartDate" class="form-label">Start date</label>
                            <input type="date" class="form-control @error('start_date') is-invalid @enderror"
                                id="leaseStartDate" name="start_date" value="{{ old('start_date') }}" required>
                            @error('start_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6 mb-1">
                            <label for="leaseEndDate" class="form-label">End date <span class="text-muted fw-normal">(optional)</span></label>
                            <input type="date" class="form-control @error('end_date') is-invalid @enderror"
                                id="leaseEndDate" name="end_date" value="{{ old('end_date') }}">
                            @error('end_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save lease</button>
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
        var printTitle = 'Leases - {{ $company->name }} - {{ date("d M, Y") }}';
        $('#leasesTable').DataTable({
            ordering: false,
            paging: false,
            info: false,
            responsive: true,
            columnDefs: [{
                targets: [0],
                orderable: false
            }],
            language: {
                emptyTable: "No leases yet"
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

        var $modalEl = $('#leaseModal');
        var modal = new bootstrap.Modal($modalEl[0]);
        var $form = $('#leaseForm');
        var storeUrl = "{{ route('company.leases.store', ['company' => $company->slug]) }}";
        var updateUrlTemplate = "{{ route('company.leases.update', ['company' => $company->slug, 'lease' => '__ID__']) }}";

        function openCreateModal() {
            $form[0].reset();
            $form.attr('action', storeUrl);
            $('#leaseMethod').val('');
            $('#leaseIdField').val('');
            $('#leaseModalTitle').text('Add lease');
            modal.show();
        }

        function openEditModal(lease) {
            $form.attr('action', updateUrlTemplate.replace('__ID__', lease.id));
            $('#leaseMethod').val('PUT');
            $('#leaseIdField').val(lease.id);
            // The unit this lease is already on must be selectable
            // even though it's occupied (by this same lease) — enable
            // it explicitly since the option may be disabled from the
            // server-rendered "disabled unless editing" logic above.
            $('#leaseUnit').find('option').prop('disabled', false);
            $('#leaseUnit').val(lease.property_unit_id);
            $('#leaseContact').val(lease.contact_id);
            $('#leaseRent').val(lease.rent_amount);
            $('#leaseStatus').val(lease.status);
            $('#leaseStartDate').val(lease.start_date);
            $('#leaseEndDate').val(lease.end_date || '');
            $('#leaseModalTitle').text('Edit lease');
            modal.show();
        }

        $('#addLeaseBtn').on('click', openCreateModal);
        $('#leasesTable tbody').on('click', '.btn-edit-lease', function() {
            openEditModal($(this).data('lease'));
        });

        @if($errors -> any())
        modal.show();
        @endif
    });
</script>
@endpush
