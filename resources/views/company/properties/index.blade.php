@extends('layouts.app')

@section('title', 'Properties')

@push('styles')
<link href="{{ asset('assets/DataTables/datatables.min.css') }}" rel="stylesheet">
<link href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css" rel="stylesheet">
@endpush

@php
$editingId = old('property_id');
@endphp

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h4 mb-1">Properties</h1>
        <p class="text-muted small mb-0">Buildings, plots, and their units for {{ $company->name }}.</p>
    </div>
    <button type="button" class="btn btn-primary" id="addPropertyBtn">
        <i class="bi bi-plus-lg me-1"></i> Add property
    </button>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        {{-- No delete action on purpose — see PropertyPageController's
             class comment. Loop variable is $row, not $company. --}}
        <table class="table table-hover align-middle w-100" id="propertiesTable">
            <thead>
                <tr>
                    <th>S/N</th>
                    <th>Title</th>
                    <th>Type</th>
                    <th>Address</th>
                    <th>Units</th>
                    <th>Status</th>
                    <th class="text-end no-export">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($properties as $row)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td class="fw-semibold">{{ $row->title }}</td>
                    <td>{{ ucfirst($row->type) }}</td>
                    <td>{{ $row->address }}</td>
                    <td>{{ $row->units->count() }}</td>
                    <td>
                        @php
                            $statusClass = match ($row->status) {
                                'leased' => 'text-bg-success-subtle text-success-emphasis',
                                'sold' => 'text-bg-secondary-subtle text-secondary-emphasis',
                                default => 'text-bg-light border',
                            };
                        @endphp
                        <span class="badge {{ $statusClass }}">{{ ucfirst($row->status) }}</span>
                    </td>
                    <td class="text-end no-export">
                        @php
                            $propertyPayload = [
                                'id' => $row->id,
                                'title' => $row->title,
                                'type' => $row->type,
                                'address' => $row->address,
                                'status' => $row->status,
                            ];
                            $unitsPayload = [
                                'property_id' => $row->id,
                                'property_title' => $row->title,
                                'units' => $row->units->map(fn ($unit) => [
                                    'id' => $unit->id,
                                    'unit_label' => $unit->unit_label,
                                    'rent_amount' => $unit->rent_amount,
                                    'status' => $unit->status,
                                ])->values(),
                            ];
                        @endphp
                        <button type="button"
                            class="btn btn-sm btn-outline-secondary btn-edit-property"
                            data-property='{{ json_encode($propertyPayload) }}'>
                            <i class="bi bi-pencil"></i> Edit
                        </button>
                        <button type="button"
                            class="btn btn-sm btn-outline-primary btn-view-units"
                            data-units='{{ json_encode($unitsPayload) }}'>
                            <i class="bi bi-grid-3x3-gap"></i> Units
                        </button>
                    </td>
                </tr>
                @empty
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- ===================== PROPERTY MODAL ===================== --}}
<div class="modal fade" id="propertyModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="propertyForm" method="POST"
                action="{{ $editingId ? route('company.properties.update', ['company' => $company->slug, 'property' => $editingId]) : route('company.properties.store', ['company' => $company->slug]) }}">
                @csrf
                <input type="hidden" name="_method" id="propertyMethod" value="{{ $editingId ? 'PUT' : '' }}">
                <input type="hidden" name="property_id" id="propertyIdField" value="{{ $editingId }}">

                <div class="modal-header">
                    <h5 class="modal-title" id="propertyModalTitle">{{ $editingId ? 'Edit property' : 'Add property' }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="propertyTitle" class="form-label">Title</label>
                        <input type="text" class="form-control @error('title') is-invalid @enderror"
                            id="propertyTitle" name="title" value="{{ old('title') }}" placeholder="e.g. Marina Heights" required>
                        @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="propertyType" class="form-label">Type</label>
                            <select class="form-select @error('type') is-invalid @enderror" id="propertyType" name="type" required>
                                <option value="">&mdash; Select &mdash;</option>
                                @foreach (['residential' => 'Residential', 'commercial' => 'Commercial', 'land' => 'Land'] as $value => $label)
                                    <option value="{{ $value }}" @selected(old('type') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="propertyStatus" class="form-label">Status</label>
                            <select class="form-select @error('status') is-invalid @enderror" id="propertyStatus" name="status" required>
                                @foreach (['available' => 'Available', 'leased' => 'Leased', 'sold' => 'Sold'] as $value => $label)
                                    <option value="{{ $value }}" @selected(old('status') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="mb-1">
                        <label for="propertyAddress" class="form-label">Address</label>
                        <input type="text" class="form-control @error('address') is-invalid @enderror"
                            id="propertyAddress" name="address" value="{{ old('address') }}" required>
                        @error('address')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save property</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ===================== UNITS MODAL ===================== --}}
<div class="modal fade" id="unitsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="unitsModalTitle">Units</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                {{-- No delete action on units either — see
                     PropertyPageController's class comment. --}}
                <table class="table table-sm align-middle mb-4">
                    <thead>
                        <tr>
                            <th>Unit</th>
                            <th>Rent</th>
                            <th>Status</th>
                            <th class="text-end"></th>
                        </tr>
                    </thead>
                    <tbody id="unitsListBody">
                        <tr id="unitsEmptyRow"><td colspan="4" class="text-muted small">No units yet.</td></tr>
                    </tbody>
                </table>

                <hr>

                <form id="unitForm" method="POST">
                    @csrf
                    <input type="hidden" name="_method" id="unitMethod" value="">
                    <h6 class="fw-semibold mb-3" id="unitFormTitle">Add a unit</h6>

                    <div class="row">
                        <div class="col-md-5 mb-3">
                            <label for="unitLabel" class="form-label">Unit label</label>
                            <input type="text" class="form-control @error('unit_label') is-invalid @enderror"
                                id="unitLabel" name="unit_label" value="{{ old('unit_label') }}" placeholder="e.g. Flat 3B" required>
                            @error('unit_label')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="unitRent" class="form-label">Rent <span class="text-muted fw-normal">(optional)</span></label>
                            <input type="number" step="0.01" min="0" class="form-control @error('rent_amount') is-invalid @enderror"
                                id="unitRent" name="rent_amount" value="{{ old('rent_amount') }}">
                            @error('rent_amount')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="unitStatus" class="form-label">Status</label>
                            <select class="form-select @error('status') is-invalid @enderror" id="unitStatus" name="status" required>
                                @foreach (['vacant' => 'Vacant', 'occupied' => 'Occupied'] as $value => $label)
                                    <option value="{{ $value }}" @selected(old('status') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <button type="button" class="btn btn-outline-secondary btn-sm" id="cancelUnitEditBtn" style="display: none;">Cancel edit</button>
                        <button type="submit" class="btn btn-primary btn-sm" id="unitSubmitBtn">Add unit</button>
                    </div>
                </form>
                <p class="text-muted small mt-2 mb-0">
                    Occupied/vacant flips automatically when a lease starts or ends on
                    <a href="{{ route('company.leases.index', ['company' => $company->slug]) }}">the Leases page</a> — only change it here to correct a mistake.
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')

<script src="{{ asset('assets/DataTables/datatables.min.js') }}"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>

<script>
    $(function() {
        var columnPrint = [0, 1, 2, 3, 4, 5];
        var printTitle = 'Properties - {{ $company->name }} - {{ date("d M, Y") }}';
        $('#propertiesTable').DataTable({
            ordering: false,
            paging: false,
            info: false,
            responsive: true,
            columnDefs: [{ targets: [0], orderable: false }],
            language: { emptyTable: "No properties yet" },
            dom: "<'row mb-3'" +
                "<'col-lg-8 col-md-12 d-flex align-items-center gap-2 text-lg-start text-center mb-2 mb-lg-0'l f>" +
                "<'col-lg-4 col-md-12 text-lg-end text-center mb-2 mb-lg-0'B>" +
                ">" +
                "<'row'<'col-12'tr>>" +
                "<'row mt-3'" +
                "<'col-md-6 col-12 text-md-start text-center'i>" +
                "<'col-md-6 col-12 d-flex justify-content-md-end justify-content-center'p>" +
                ">",
            buttons: [
                { extend: 'print', className: 'btn-info', title: printTitle, exportOptions: { columns: columnPrint } },
                { extend: 'csv', className: 'btn-primary', title: printTitle, exportOptions: { columns: columnPrint } },
                { extend: 'excel', className: 'btn-success', title: printTitle, exportOptions: { columns: columnPrint } },
                { extend: 'pdf', className: 'btn-warning', title: printTitle, exportOptions: { columns: columnPrint } },
            ]
        });

        // ---------- Property modal ----------
        var $propertyModalEl = $('#propertyModal');
        var propertyModal = new bootstrap.Modal($propertyModalEl[0]);
        var $propertyForm = $('#propertyForm');
        var propertyStoreUrl = "{{ route('company.properties.store', ['company' => $company->slug]) }}";
        var propertyUpdateUrlTemplate = "{{ route('company.properties.update', ['company' => $company->slug, 'property' => '__ID__']) }}";

        function openCreatePropertyModal() {
            $propertyForm[0].reset();
            $propertyForm.attr('action', propertyStoreUrl);
            $('#propertyMethod').val('');
            $('#propertyIdField').val('');
            $('#propertyModalTitle').text('Add property');
            propertyModal.show();
        }

        function openEditPropertyModal(property) {
            $propertyForm.attr('action', propertyUpdateUrlTemplate.replace('__ID__', property.id));
            $('#propertyMethod').val('PUT');
            $('#propertyIdField').val(property.id);
            $('#propertyTitle').val(property.title);
            $('#propertyType').val(property.type);
            $('#propertyAddress').val(property.address);
            $('#propertyStatus').val(property.status);
            $('#propertyModalTitle').text('Edit ' + property.title);
            propertyModal.show();
        }

        $('#addPropertyBtn').on('click', openCreatePropertyModal);
        $('#propertiesTable tbody').on('click', '.btn-edit-property', function() {
            openEditPropertyModal($(this).data('property'));
        });

        // ---------- Units modal ----------
        var $unitsModalEl = $('#unitsModal');
        var unitsModal = new bootstrap.Modal($unitsModalEl[0]);
        var $unitForm = $('#unitForm');
        var unitStoreUrlTemplate = "{{ route('company.properties.units.store', ['company' => $company->slug, 'property' => '__PROPERTY_ID__']) }}";
        var unitUpdateUrlTemplate = "{{ route('company.properties.units.update', ['company' => $company->slug, 'property' => '__PROPERTY_ID__', 'unit' => '__UNIT_ID__']) }}";
        var currentPropertyId = null;

        function unitStatusBadge(status) {
            var cls = status === 'occupied' ? 'text-bg-success-subtle text-success-emphasis' : 'text-bg-light border';
            return '<span class="badge ' + cls + '">' + status.charAt(0).toUpperCase() + status.slice(1) + '</span>';
        }

        function resetUnitForm() {
            $unitForm[0].reset();
            $('#unitMethod').val('');
            $('#unitFormTitle').text('Add a unit');
            $('#unitSubmitBtn').text('Add unit');
            $('#cancelUnitEditBtn').hide();
            $unitForm.attr('action', unitStoreUrlTemplate.replace('__PROPERTY_ID__', currentPropertyId));
        }

        function openUnitsModal(payload) {
            currentPropertyId = payload.property_id;
            $('#unitsModalTitle').text('Units — ' + payload.property_title);

            var $body = $('#unitsListBody').empty();
            if (!payload.units.length) {
                $body.append('<tr><td colspan="4" class="text-muted small">No units yet.</td></tr>');
            } else {
                payload.units.forEach(function(unit) {
                    var $row = $(
                        '<tr>' +
                            '<td class="fw-semibold">' + $('<div>').text(unit.unit_label).html() + '</td>' +
                            '<td>' + (unit.rent_amount != null ? parseFloat(unit.rent_amount).toFixed(2) : '—') + '</td>' +
                            '<td>' + unitStatusBadge(unit.status) + '</td>' +
                            '<td class="text-end"><button type="button" class="btn btn-sm btn-outline-secondary btn-edit-unit"><i class="bi bi-pencil"></i></button></td>' +
                        '</tr>'
                    );
                    $row.find('.btn-edit-unit').on('click', function() {
                        $('#unitMethod').val('PUT');
                        $('#unitLabel').val(unit.unit_label);
                        $('#unitRent').val(unit.rent_amount != null ? unit.rent_amount : '');
                        $('#unitStatus').val(unit.status);
                        $('#unitFormTitle').text('Edit ' + unit.unit_label);
                        $('#unitSubmitBtn').text('Save changes');
                        $('#cancelUnitEditBtn').show();
                        $unitForm.attr('action', unitUpdateUrlTemplate.replace('__PROPERTY_ID__', currentPropertyId).replace('__UNIT_ID__', unit.id));
                    });
                    $body.append($row);
                });
            }

            resetUnitForm();
            unitsModal.show();
        }

        $('#propertiesTable tbody').on('click', '.btn-view-units', function() {
            openUnitsModal($(this).data('units'));
        });

        $('#cancelUnitEditBtn').on('click', resetUnitForm);

        @if ($errors->any())
            @if (old('unit_label'))
                {{-- A unit submission failed validation — the parent
                     property context was lost on redirect, so we can
                     only show the form standalone, not the full list. --}}
                $('#unitsModalTitle').text('Units');
                $('#unitsListBody').html('<tr><td colspan="4" class="text-muted small">Reopen this property\'s Units to see the full list.</td></tr>');
                unitsModal.show();
            @else
                propertyModal.show();
            @endif
        @endif
    });
</script>
@endpush
