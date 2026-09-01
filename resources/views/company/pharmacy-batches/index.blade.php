@extends('layouts.app')

@section('title', 'Pharmacy Stock')


@php
$editingId = old('batch_id');
@endphp

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h4 mb-1">Pharmacy Stock</h1>
        <p class="text-muted small mb-0">Stock intake batches, oldest expiry first.</p>
    </div>
    <button type="button" class="btn btn-primary" id="addBatchBtn">
        <i class="bi bi-plus-lg me-1"></i> Add batch
    </button>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        {{-- No delete action on this table on purpose — see
             PharmacyBatchPageController's class comment. Loop
             variable is $row, not $company. --}}
        <table class="table table-hover align-middle w-100" id="batchesTable">
            <thead>
                <tr>
                    <th>S/N</th>
                    <th>Product</th>
                    <th>Batch #</th>
                    <th>Expiry</th>
                    <th>Qty received</th>
                    <th>Qty remaining</th>
                    <th>Status</th>
                    <th class="text-end no-export">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($batches as $row)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td class="fw-semibold">{{ $row->product->name ?? '—' }}</td>
                    <td><code>{{ $row->batch_number }}</code></td>
                    <td>
                        {{ $row->expiry_date->format('M j, Y') }}
                        @if ($row->isExpired())
                            <span class="badge text-bg-danger-subtle text-danger-emphasis ms-1">Expired</span>
                        @endif
                    </td>
                    <td>{{ $row->quantity_received }}</td>
                    <td>{{ $row->quantity_remaining }}</td>
                    <td>
                        @php
                            $statusClass = match ($row->status) {
                                'active' => 'text-bg-success-subtle text-success-emphasis',
                                'expired' => 'text-bg-danger-subtle text-danger-emphasis',
                                'depleted' => 'text-bg-secondary-subtle text-secondary-emphasis',
                                default => 'text-bg-light border',
                            };
                        @endphp
                        <span class="badge {{ $statusClass }}">{{ ucfirst($row->status) }}</span>
                    </td>
                    <td class="text-end no-export">
                        @php
                            $batchPayload = [
                                'id' => $row->id,
                                'product_id' => $row->product_id,
                                'batch_number' => $row->batch_number,
                                'quantity_received' => $row->quantity_received,
                                'expiry_date' => $row->expiry_date->format('Y-m-d'),
                                'received_at' => $row->received_at->format('Y-m-d'),
                                'status' => $row->status,
                            ];
                        @endphp
                        <button type="button"
                            class="btn btn-sm btn-outline-secondary btn-edit-batch"
                            data-batch='{{ json_encode($batchPayload) }}'>
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

<div class="modal fade" id="batchModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="batchForm" method="POST"
                action="{{ $editingId ? route('company.pharmacy-batches.update', ['company' => $company->slug, 'pharmacy_batch' => $editingId]) : route('company.pharmacy-batches.store', ['company' => $company->slug]) }}">
                @csrf
                <input type="hidden" name="_method" id="batchMethod" value="{{ $editingId ? 'PUT' : '' }}">
                <input type="hidden" name="batch_id" id="batchIdField" value="{{ $editingId }}">

                <div class="modal-header">
                    <h5 class="modal-title" id="batchModalTitle">{{ $editingId ? 'Edit batch' : 'Add batch' }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="batchProduct" class="form-label">Product</label>
                        <select class="form-select @error('product_id') is-invalid @enderror" id="batchProduct" name="product_id" required>
                            <option value="">&mdash; Select &mdash;</option>
                            @foreach ($products as $productOption)
                                <option value="{{ $productOption->id }}" @selected(old('product_id') === $productOption->id)>
                                    {{ $productOption->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('product_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        @if ($products->isEmpty())
                            <div class="form-text text-warning">No active products yet — add one under Products first.</div>
                        @endif
                    </div>

                    <div class="mb-3">
                        <label for="batchNumber" class="form-label">Batch number</label>
                        <input type="text" class="form-control @error('batch_number') is-invalid @enderror"
                            id="batchNumber" name="batch_number" value="{{ old('batch_number') }}" required>
                        @error('batch_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="batchQuantity" class="form-label" id="batchQuantityLabel">Quantity received</label>
                            <input type="number" min="1" class="form-control @error('quantity_received') is-invalid @enderror"
                                id="batchQuantity" name="quantity_received" value="{{ old('quantity_received') }}" required>
                            <div class="form-text" id="batchQuantityHelp">Sets the starting stock for this batch.</div>
                            @error('quantity_received')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="batchStatus" class="form-label">Status</label>
                            <select class="form-select @error('status') is-invalid @enderror" id="batchStatus" name="status" required>
                                @foreach (['active' => 'Active', 'expired' => 'Expired', 'depleted' => 'Depleted'] as $value => $label)
                                    <option value="{{ $value }}" @selected(old('status') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-1">
                            <label for="batchReceivedAt" class="form-label">Received on</label>
                            <input type="date" class="form-control @error('received_at') is-invalid @enderror"
                                id="batchReceivedAt" name="received_at" value="{{ old('received_at') }}" required>
                            @error('received_at')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6 mb-1">
                            <label for="batchExpiry" class="form-label">Expiry date</label>
                            <input type="date" class="form-control @error('expiry_date') is-invalid @enderror"
                                id="batchExpiry" name="expiry_date" value="{{ old('expiry_date') }}" required>
                            @error('expiry_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save batch</button>
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
        var printTitle = 'Pharmacy Stock - {{ $company->name }} - {{ date("d M, Y") }}';
        $('#batchesTable').DataTable({
            ordering: false,
            paging: false,
            info: false,
            responsive: true,
            columnDefs: [{ targets: [0], orderable: false }],
            language: { emptyTable: "No batches yet" },
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

        var $modalEl = $('#batchModal');
        var modal = new bootstrap.Modal($modalEl[0]);
        var $form = $('#batchForm');
        var storeUrl = "{{ route('company.pharmacy-batches.store', ['company' => $company->slug]) }}";
        var updateUrlTemplate = "{{ route('company.pharmacy-batches.update', ['company' => $company->slug, 'pharmacy_batch' => '__ID__']) }}";

        function openCreateModal() {
            $form[0].reset();
            $form.attr('action', storeUrl);
            $('#batchMethod').val('');
            $('#batchIdField').val('');
            $('#batchModalTitle').text('Add batch');
            $('#batchQuantity').prop('disabled', false);
            $('#batchQuantityHelp').text('Sets the starting stock for this batch.');
            modal.show();
        }

        function openEditModal(batch) {
            $form.attr('action', updateUrlTemplate.replace('__ID__', batch.id));
            $('#batchMethod').val('PUT');
            $('#batchIdField').val(batch.id);
            $('#batchProduct').val(batch.product_id);
            $('#batchNumber').val(batch.batch_number);
            $('#batchQuantity').val(batch.quantity_received);
            $('#batchStatus').val(batch.status);
            $('#batchReceivedAt').val(batch.received_at);
            $('#batchExpiry').val(batch.expiry_date);
            $('#batchModalTitle').text('Edit batch ' + batch.batch_number);
            modal.show();
        }

        $('#addBatchBtn').on('click', openCreateModal);
        $('#batchesTable tbody').on('click', '.btn-edit-batch', function() {
            openEditModal($(this).data('batch'));
        });

        @if ($errors->any())
            modal.show();
        @endif
    });
</script>
@endpush
