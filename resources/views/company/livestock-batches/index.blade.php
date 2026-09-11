@extends('layouts.app')

@section('title', 'Livestock')



@php
$editingId = old('livestock_batch_id');
@endphp

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h4 mb-1">Livestock</h1>
        <p class="text-muted small mb-0">Reared batches for {{ $company->name }}.</p>
    </div>
    <button type="button" class="btn btn-primary" id="addLivestockBtn">
        <i class="bi bi-plus-lg me-1"></i> Add livestock batch
    </button>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        {{-- No delete action on purpose — a livestock batch is
             operational history. Loop variable is $row, not $company. --}}
        <table class="table table-hover align-middle w-100" id="livestockTable">
            <thead>
                <tr>
                    <th>S/N</th>
                    <th>Batch</th>
                    <th>Type</th>
                    <th>Started</th>
                    <th>Expected ready</th>
                    <th>Qty start</th>
                    <th>Qty current</th>
                    <th>Status</th>
                    <th class="text-end no-export">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($livestockBatches as $row)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td class="fw-semibold">{{ $row->batch_label }}</td>
                    <td>{{ ucfirst($row->type) }}</td>
                    <td>{{ $row->start_date->format('M j, Y') }}</td>
                    <td>{{ $row->expected_ready_at?->format('M j, Y') ?? '—' }}</td>
                    <td>{{ $row->quantity_start }}</td>
                    <td>{{ $row->quantity_current }}</td>
                    <td>
                        @php
                        $liveStatusClass = match ($row->status) {
                        'active' => 'text-bg-success-subtle text-success-emphasis',
                        'sold' => 'text-bg-info-subtle text-info-emphasis',
                        'closed' => 'text-bg-secondary-subtle text-secondary-emphasis',
                        default => 'text-bg-light border',
                        };
                        @endphp
                        <span class="badge {{ $liveStatusClass }}">{{ ucfirst($row->status) }}</span>
                    </td>
                    <td class="text-end no-export">
                        @php
                        $livestockPayload = [
                        'id' => $row->id,
                        'type' => $row->type,
                        'batch_label' => $row->batch_label,
                        'quantity_start' => $row->quantity_start,
                        'quantity_current' => $row->quantity_current,
                        'start_date' => $row->start_date->format('Y-m-d'),
                        'expected_ready_at' => $row->expected_ready_at?->format('Y-m-d'),
                        'status' => $row->status,
                        ];
                        @endphp
                        <button type="button"
                            class="btn btn-sm btn-outline-secondary btn-edit-livestock"
                            data-livestock='{{ json_encode($livestockPayload) }}'>
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

<div class="modal fade" id="livestockModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="livestockForm" method="POST"
                action="{{ $editingId ? route('company.livestock-batches.update', ['company' => $company->slug, 'livestock_batch' => $editingId]) : route('company.livestock-batches.store', ['company' => $company->slug]) }}">
                @csrf
                <input type="hidden" name="_method" id="livestockMethod" value="{{ $editingId ? 'PUT' : '' }}">
                <input type="hidden" name="livestock_batch_id" id="livestockIdField" value="{{ $editingId }}">

                <div class="modal-header">
                    <h5 class="modal-title" id="livestockModalTitle">{{ $editingId ? 'Edit livestock batch' : 'Add livestock batch' }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="livestockType" class="form-label">Type</label>
                            <select class="form-select @error('type') is-invalid @enderror" id="livestockType" name="type" required>
                                <option value="">&mdash; Select &mdash;</option>
                                @foreach (['poultry' => 'Poultry', 'fishery' => 'Fishery'] as $value => $label)
                                <option value="{{ $value }}" @selected(old('type')===$value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="livestockLabel" class="form-label">Batch label</label>
                            <input type="text" class="form-control @error('batch_label') is-invalid @enderror"
                                id="livestockLabel" name="batch_label" value="{{ old('batch_label') }}" placeholder="e.g. Flock 3, Pond B" required>
                            @error('batch_label')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="quantityStart" class="form-label">Quantity at start</label>
                            <input type="number" min="1" class="form-control @error('quantity_start') is-invalid @enderror"
                                id="quantityStart" name="quantity_start" value="{{ old('quantity_start') }}" required>
                            @error('quantity_start')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6 mb-3" id="quantityCurrentWrapper" style="display: none;">
                            <label for="quantityCurrent" class="form-label">Quantity now</label>
                            <input type="number" min="0" class="form-control @error('quantity_current') is-invalid @enderror"
                                id="quantityCurrent" name="quantity_current" value="{{ old('quantity_current') }}">
                            <div class="form-text">Lower this to record losses or sales. Can't exceed the starting quantity.</div>
                            @error('quantity_current')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="startDate" class="form-label">Start date</label>
                            <input type="date" class="form-control @error('start_date') is-invalid @enderror"
                                id="startDate" name="start_date" value="{{ old('start_date') }}" required>
                            @error('start_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="expectedReadyAt" class="form-label">Expected ready <span class="text-muted fw-normal">(optional)</span></label>
                            <input type="date" class="form-control @error('expected_ready_at') is-invalid @enderror"
                                id="expectedReadyAt" name="expected_ready_at" value="{{ old('expected_ready_at') }}">
                            @error('expected_ready_at')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="mb-1">
                        <label for="livestockStatus" class="form-label">Status</label>
                        <select class="form-select @error('status') is-invalid @enderror" id="livestockStatus" name="status" required>
                            @foreach (['active' => 'Active', 'sold' => 'Sold', 'closed' => 'Closed'] as $value => $label)
                            <option value="{{ $value }}" @selected(old('status')===$value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save livestock batch</button>
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
        var printTitle = 'Livestock - {{ $company->name }} - {{ date("d M, Y") }}';
        $('#livestockTable').DataTable({
            ordering: false,
            paging: false,
            info: false,
            responsive: true,
            columnDefs: [{
                targets: [0],
                orderable: false
            }],
            language: {
                emptyTable: "No livestock batches yet"
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

        var $modalEl = $('#livestockModal');
        var modal = new bootstrap.Modal($modalEl[0]);
        var $form = $('#livestockForm');
        var storeUrl = "{{ route('company.livestock-batches.store', ['company' => $company->slug]) }}";
        var updateUrlTemplate = "{{ route('company.livestock-batches.update', ['company' => $company->slug, 'livestock_batch' => '__ID__']) }}";

        function openCreateModal() {
            $form[0].reset();
            $form.attr('action', storeUrl);
            $('#livestockMethod').val('');
            $('#livestockIdField').val('');
            $('#livestockModalTitle').text('Add livestock batch');
            // quantity_current doesn't exist yet on a new batch — it
            // starts equal to quantity_start, set server-side.
            $('#quantityCurrentWrapper').hide();
            $('#quantityCurrent').prop('required', false);
            modal.show();
        }

        function openEditModal(batch) {
            $form.attr('action', updateUrlTemplate.replace('__ID__', batch.id));
            $('#livestockMethod').val('PUT');
            $('#livestockIdField').val(batch.id);
            $('#livestockType').val(batch.type);
            $('#livestockLabel').val(batch.batch_label);
            $('#quantityStart').val(batch.quantity_start);
            $('#startDate').val(batch.start_date);
            $('#expectedReadyAt').val(batch.expected_ready_at || '');
            $('#livestockStatus').val(batch.status);
            $('#quantityCurrentWrapper').show();
            $('#quantityCurrent').prop('required', true).attr('max', batch.quantity_start).val(batch.quantity_current);
            $('#livestockModalTitle').text('Edit ' + batch.batch_label);
            modal.show();
        }

        $('#addLivestockBtn').on('click', openCreateModal);
        $('#livestockTable tbody').on('click', '.btn-edit-livestock', function() {
            openEditModal($(this).data('livestock'));
        });

        @if($errors -> any())
        modal.show();
        @endif
    });
</script>
@endpush
