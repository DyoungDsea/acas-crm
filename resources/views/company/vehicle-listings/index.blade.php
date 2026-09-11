@extends('layouts.app')

@section('title', 'Vehicle Listings')

 

@php
$editingId = old('vehicle_listing_id');
@endphp

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h4 mb-1">Vehicle Listings</h1>
        <p class="text-muted small mb-0">Everything {{ $company->name }} has for sale or for rent.</p>
    </div>
    <button type="button" class="btn btn-primary" id="addListingBtn">
        <i class="bi bi-plus-lg me-1"></i> Add vehicle
    </button>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        {{-- No delete action on purpose — see the controller's class
             comment. Loop variable is $row, not $company. --}}
        <table class="table table-hover align-middle w-100" id="listingsTable">
            <thead>
                <tr>
                    <th>S/N</th>
                    <th>Vehicle</th>
                    <th>Registration</th>
                    <th>Year</th>
                    <th>Condition</th>
                    <th>Listing</th>
                    <th>Price</th>
                    <th>Status</th>
                    <th class="text-end no-export">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($listings as $row)
                @php
                $attrs = $row->attributes ?? [];
                $listingType = $row->category_id === $saleCategoryId ? 'sale' : 'rent';
                @endphp
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td class="fw-semibold">{{ $attrs['make'] ?? '' }} {{ $attrs['model'] ?? '' }}</td>
                    <td>{{ $attrs['registration_number'] ?? '—' }}</td>
                    <td>{{ $attrs['year'] ?? '—' }}</td>
                    <td>{{ isset($attrs['condition']) ? ucwords(str_replace('_', ' ', $attrs['condition'])) : '—' }}</td>
                    <td>
                        <span class="badge {{ $listingType === 'sale' ? 'text-bg-info-subtle text-info-emphasis' : 'text-bg-primary-subtle text-primary-emphasis' }}">
                            {{ $listingType === 'sale' ? 'For sale' : 'For rent' }}
                        </span>
                    </td>
                    <td>{{ formatNaira($row->unit_price, 2) }}</td>
                    <td>
                        @php
                        $vehicleStatus = $attrs['vehicle_status'] ?? 'available';
                        $statusClass = match ($vehicleStatus) {
                        'available' => 'text-bg-success-subtle text-success-emphasis',
                        'rented' => 'text-bg-warning-subtle text-warning-emphasis',
                        'sold' => 'text-bg-secondary-subtle text-secondary-emphasis',
                        'in_repair' => 'text-bg-danger-subtle text-danger-emphasis',
                        default => 'text-bg-light border',
                        };
                        @endphp
                        <span class="badge {{ $statusClass }}">{{ ucfirst(str_replace('_', ' ', $vehicleStatus)) }}</span>
                    </td>
                    <td class="text-end no-export">
                        @php
                        $listingPayload = [
                        'id' => $row->id,
                        'listing_type' => $listingType,
                        'make' => $attrs['make'] ?? '',
                        'model' => $attrs['model'] ?? '',
                        'registration_number' => $attrs['registration_number'] ?? '',
                        'year' => $attrs['year'] ?? '',
                        'condition' => $attrs['condition'] ?? '',
                        'price' => $row->unit_price,
                        'vehicle_status' => $vehicleStatus,
                        ];
                        @endphp
                        <button type="button"
                            class="btn btn-sm btn-outline-secondary btn-edit-listing"
                            data-listing='{{ json_encode($listingPayload) }}'>
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

<div class="modal fade" id="listingModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="listingForm" method="POST"
                action="{{ $editingId ? route('company.vehicle-listings.update', ['company' => $company->slug, 'vehicle_listing' => $editingId]) : route('company.vehicle-listings.store', ['company' => $company->slug]) }}">
                @csrf
                <input type="hidden" name="_method" id="listingMethod" value="{{ $editingId ? 'PUT' : '' }}">
                <input type="hidden" name="vehicle_listing_id" id="listingIdField" value="{{ $editingId }}">

                <div class="modal-header">
                    <h5 class="modal-title" id="listingModalTitle">{{ $editingId ? 'Edit vehicle' : 'Add vehicle' }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="listingMake" class="form-label">Make</label>
                            <input type="text" class="form-control @error('make') is-invalid @enderror"
                                id="listingMake" name="make" value="{{ old('make') }}" placeholder="e.g. Toyota" required>
                            @error('make')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="listingModel" class="form-label">Model</label>
                            <input type="text" class="form-control @error('model') is-invalid @enderror"
                                id="listingModel" name="model" value="{{ old('model') }}" placeholder="e.g. Camry" required>
                            @error('model')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="listingRegistration" class="form-label">Registration <span class="text-muted fw-normal">(optional)</span></label>
                            <input type="text" class="form-control @error('registration_number') is-invalid @enderror"
                                id="listingRegistration" name="registration_number" value="{{ old('registration_number') }}">
                            @error('registration_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="listingYear" class="form-label">Year <span class="text-muted fw-normal">(optional)</span></label>
                            <input type="number" min="1950" max="{{ date('Y') + 1 }}" class="form-control @error('year') is-invalid @enderror"
                                id="listingYear" name="year" value="{{ old('year') }}">
                            @error('year')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="listingCondition" class="form-label">Condition <span class="text-muted fw-normal">(optional)</span></label>
                            <select class="form-select @error('condition') is-invalid @enderror" id="listingCondition" name="condition">
                                <option value="">&mdash; Select &mdash;</option>
                                @foreach (['brand_new' => 'Brand new', 'foreign_used' => 'Foreign used', 'locally_used' => 'Locally used'] as $value => $label)
                                <option value="{{ $value }}" @selected(old('condition')===$value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('condition')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="listingType" class="form-label">Listing</label>
                            <select class="form-select @error('listing_type') is-invalid @enderror" id="listingType" name="listing_type" required>
                                <option value="sale" @selected(old('listing_type')==='sale' )>For sale</option>
                                <option value="rent" @selected(old('listing_type')==='rent' )>For rent</option>
                            </select>
                            @error('listing_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-1">
                            <label for="listingPrice" class="form-label">Price <span class="text-muted fw-normal">(sale price, or rate per rental)</span></label>
                            <input type="number" step="0.01" min="0" class="form-control @error('price') is-invalid @enderror"
                                id="listingPrice" name="price" value="{{ old('price') }}" required>
                            @error('price')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6 mb-1">
                            <label for="listingStatus" class="form-label">Status</label>
                            <select class="form-select @error('vehicle_status') is-invalid @enderror" id="listingStatus" name="vehicle_status" required>
                                @foreach (['available' => 'Available', 'rented' => 'Rented', 'sold' => 'Sold', 'in_repair' => 'In repair'] as $value => $label)
                                <option value="{{ $value }}" @selected(old('vehicle_status')===$value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            <div class="form-text">Set automatically once a sale/rental is recorded — only change it here to correct a mistake.</div>
                            @error('vehicle_status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save vehicle</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')


<script>
    $(function() {
        var columnPrint = [0, 1, 2, 3, 4, 5, 6, 7];
        var printTitle = 'Vehicle Listings - {{ $company->name }} - {{ date("d M, Y") }}';
        $('#listingsTable').DataTable({
            ordering: false,
            paging: false,
            info: false,
            responsive: true,
            columnDefs: [{
                targets: [0],
                orderable: false
            }],
            language: {
                emptyTable: "No vehicles listed yet"
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

        var $modalEl = $('#listingModal');
        var modal = new bootstrap.Modal($modalEl[0]);
        var $form = $('#listingForm');
        var storeUrl = "{{ route('company.vehicle-listings.store', ['company' => $company->slug]) }}";
        var updateUrlTemplate = "{{ route('company.vehicle-listings.update', ['company' => $company->slug, 'vehicle_listing' => '__ID__']) }}";

        function openCreateModal() {
            $form[0].reset();
            $form.attr('action', storeUrl);
            $('#listingMethod').val('');
            $('#listingIdField').val('');
            $('#listingModalTitle').text('Add vehicle');
            $('#listingStatus').val('available');
            modal.show();
        }

        function openEditModal(listing) {
            $form.attr('action', updateUrlTemplate.replace('__ID__', listing.id));
            $('#listingMethod').val('PUT');
            $('#listingIdField').val(listing.id);
            $('#listingMake').val(listing.make);
            $('#listingModel').val(listing.model);
            $('#listingRegistration').val(listing.registration_number || '');
            $('#listingYear').val(listing.year || '');
            $('#listingCondition').val(listing.condition || '');
            $('#listingType').val(listing.listing_type);
            $('#listingPrice').val(listing.price);
            $('#listingStatus').val(listing.vehicle_status);
            $('#listingModalTitle').text('Edit ' + listing.make + ' ' + listing.model);
            modal.show();
        }

        $('#addListingBtn').on('click', openCreateModal);
        $('#listingsTable tbody').on('click', '.btn-edit-listing', function() {
            openEditModal($(this).data('listing'));
        });

        @if($errors -> any())
        modal.show();
        @endif
    });
</script>
@endpush
