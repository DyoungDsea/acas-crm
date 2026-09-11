@extends('layouts.app')

@section('title', 'Crop Cycles')


@php
$editingId = old('crop_cycle_id');
@endphp

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h4 mb-1">Crop Cycles</h1>
        <p class="text-muted small mb-0">Planting cycles for {{ $company->name }}.</p>
    </div>
    <button type="button" class="btn btn-primary" id="addCropCycleBtn">
        <i class="bi bi-plus-lg me-1"></i> Add crop cycle
    </button>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        {{-- No delete action on purpose — a crop cycle is operational
             history. Loop variable is $row, not $company. --}}
        <table class="table table-hover align-middle w-100" id="cropCyclesTable">
            <thead>
                <tr>
                    <th>S/N</th>
                    <th>Plot</th>
                    <th>Crop</th>
                    <th>Planted</th>
                    <th>Expected harvest</th>
                    <th>Harvested</th>
                    <th>Yield</th>
                    <th>Status</th>
                    <th class="text-end no-export">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($cropCycles as $row)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td class="fw-semibold">{{ $row->plot_label }}</td>
                    <td>{{ ucfirst(str_replace('_', ' ', $row->crop_type)) }}</td>
                    <td>{{ $row->planted_at->format('M j, Y') }}</td>
                    <td>{{ $row->expected_harvest_at?->format('M j, Y') ?? '—' }}</td>
                    <td>{{ $row->harvested_at?->format('M j, Y') ?? '—' }}</td>
                    <td>{{ $row->yield_quantity ? number_format($row->yield_quantity, 2).' '.$row->yield_unit : '—' }}</td>
                    <td>
                        @php
                        $cropStatusClass = match ($row->status) {
                        'growing' => 'text-bg-info-subtle text-info-emphasis',
                        'harvested' => 'text-bg-success-subtle text-success-emphasis',
                        'closed' => 'text-bg-secondary-subtle text-secondary-emphasis',
                        default => 'text-bg-light border',
                        };
                        @endphp
                        <span class="badge {{ $cropStatusClass }}">{{ ucfirst($row->status) }}</span>
                    </td>
                    <td class="text-end no-export">
                        @php
                        $cropPayload = [
                        'id' => $row->id,
                        'crop_type' => $row->crop_type,
                        'plot_label' => $row->plot_label,
                        'planted_at' => $row->planted_at->format('Y-m-d'),
                        'expected_harvest_at' => $row->expected_harvest_at?->format('Y-m-d'),
                        'harvested_at' => $row->harvested_at?->format('Y-m-d'),
                        'yield_quantity' => $row->yield_quantity,
                        'yield_unit' => $row->yield_unit,
                        'status' => $row->status,
                        ];
                        @endphp
                        <button type="button"
                            class="btn btn-sm btn-outline-secondary btn-edit-crop-cycle"
                            data-crop-cycle='{{ json_encode($cropPayload) }}'>
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

<div class="modal fade" id="cropCycleModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="cropCycleForm" method="POST"
                action="{{ $editingId ? route('company.crop-cycles.update', ['company' => $company->slug, 'crop_cycle' => $editingId]) : route('company.crop-cycles.store', ['company' => $company->slug]) }}">
                @csrf
                <input type="hidden" name="_method" id="cropCycleMethod" value="{{ $editingId ? 'PUT' : '' }}">
                <input type="hidden" name="crop_cycle_id" id="cropCycleIdField" value="{{ $editingId }}">

                <div class="modal-header">
                    <h5 class="modal-title" id="cropCycleModalTitle">{{ $editingId ? 'Edit crop cycle' : 'Add crop cycle' }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="cropType" class="form-label">Crop type</label>
                            <input type="text" class="form-control @error('crop_type') is-invalid @enderror"
                                id="cropType" name="crop_type" value="{{ old('crop_type') }}" placeholder="e.g. rice, maize" required>
                            @error('crop_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="plotLabel" class="form-label">Plot label</label>
                            <input type="text" class="form-control @error('plot_label') is-invalid @enderror"
                                id="plotLabel" name="plot_label" value="{{ old('plot_label') }}" placeholder="e.g. Plot A3" required>
                            @error('plot_label')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="plantedAt" class="form-label">Planted on</label>
                            <input type="date" class="form-control @error('planted_at') is-invalid @enderror"
                                id="plantedAt" name="planted_at" value="{{ old('planted_at') }}" required>
                            @error('planted_at')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="expectedHarvestAt" class="form-label">Expected harvest <span class="text-muted fw-normal">(optional)</span></label>
                            <input type="date" class="form-control @error('expected_harvest_at') is-invalid @enderror"
                                id="expectedHarvestAt" name="expected_harvest_at" value="{{ old('expected_harvest_at') }}">
                            @error('expected_harvest_at')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="harvestedAt" class="form-label">Harvested on <span class="text-muted fw-normal">(optional)</span></label>
                            <input type="date" class="form-control @error('harvested_at') is-invalid @enderror"
                                id="harvestedAt" name="harvested_at" value="{{ old('harvested_at') }}">
                            @error('harvested_at')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="cropStatus" class="form-label">Status</label>
                            <select class="form-select @error('status') is-invalid @enderror" id="cropStatus" name="status" required>
                                @foreach (['planted' => 'Planted', 'growing' => 'Growing', 'harvested' => 'Harvested', 'closed' => 'Closed'] as $value => $label)
                                <option value="{{ $value }}" @selected(old('status')===$value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-1">
                            <label for="yieldQuantity" class="form-label">Yield quantity <span class="text-muted fw-normal">(optional)</span></label>
                            <input type="number" step="0.01" min="0" class="form-control @error('yield_quantity') is-invalid @enderror"
                                id="yieldQuantity" name="yield_quantity" value="{{ old('yield_quantity') }}">
                            @error('yield_quantity')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6 mb-1">
                            <label for="yieldUnit" class="form-label">Yield unit <span class="text-muted fw-normal">(optional)</span></label>
                            <input type="text" class="form-control @error('yield_unit') is-invalid @enderror"
                                id="yieldUnit" name="yield_unit" value="{{ old('yield_unit') }}" placeholder="e.g. bags, tons, kg">
                            @error('yield_unit')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save crop cycle</button>
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
        var printTitle = 'Crop Cycles - {{ $company->name }} - {{ date("d M, Y") }}';
        $('#cropCyclesTable').DataTable({
            ordering: false,
            paging: false,
            info: false,
            responsive: true,
            columnDefs: [{
                targets: [0],
                orderable: false
            }],
            language: {
                emptyTable: "No crop cycles yet"
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

        var $modalEl = $('#cropCycleModal');
        var modal = new bootstrap.Modal($modalEl[0]);
        var $form = $('#cropCycleForm');
        var storeUrl = "{{ route('company.crop-cycles.store', ['company' => $company->slug]) }}";
        var updateUrlTemplate = "{{ route('company.crop-cycles.update', ['company' => $company->slug, 'crop_cycle' => '__ID__']) }}";

        function openCreateModal() {
            $form[0].reset();
            $form.attr('action', storeUrl);
            $('#cropCycleMethod').val('');
            $('#cropCycleIdField').val('');
            $('#cropCycleModalTitle').text('Add crop cycle');
            modal.show();
        }

        function openEditModal(cycle) {
            $form.attr('action', updateUrlTemplate.replace('__ID__', cycle.id));
            $('#cropCycleMethod').val('PUT');
            $('#cropCycleIdField').val(cycle.id);
            $('#cropType').val(cycle.crop_type);
            $('#plotLabel').val(cycle.plot_label);
            $('#plantedAt').val(cycle.planted_at);
            $('#expectedHarvestAt').val(cycle.expected_harvest_at || '');
            $('#harvestedAt').val(cycle.harvested_at || '');
            $('#yieldQuantity').val(cycle.yield_quantity || '');
            $('#yieldUnit').val(cycle.yield_unit || '');
            $('#cropStatus').val(cycle.status);
            $('#cropCycleModalTitle').text('Edit ' + cycle.plot_label);
            modal.show();
        }

        $('#addCropCycleBtn').on('click', openCreateModal);
        $('#cropCyclesTable tbody').on('click', '.btn-edit-crop-cycle', function() {
            openEditModal($(this).data('crop-cycle'));
        });

        @if($errors -> any())
        modal.show();
        @endif
    });
</script>
@endpush
