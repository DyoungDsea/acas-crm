@extends('layouts.app')

@section('title', 'Stock')



@php
$editingId = old('lot_id');
@endphp

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h4 mb-1">Stock</h1>
        <p class="text-muted small mb-0">What {{ $company->name }} has received and what's left to sell. Orders draw from the oldest stock first.</p>
    </div>
    <button type="button" class="btn btn-primary" id="addLotBtn" @disabled($products->isEmpty())>
        <i class="bi bi-plus-lg me-1"></i> Add stock
    </button>
</div>

@if ($products->isEmpty())
<div class="alert alert-warning">
    No active products yet — add the products you sell under
    <a href="{{ route('company.products.index', ['company' => $company->slug]) }}">Products</a> first, then come back to record how many you have.
</div>
@endif



<div class="card border-0 shadow-sm">
    <div class="card-body">
        {{-- No delete action on this table on purpose — see
             StockLotPageController's class comment. Loop variable is
             $row, not $company. --}}
        <table class="table table-hover align-middle w-100" id="lotsTable">
            <thead>
                <tr>
                    <th>S/N</th>
                    <th>Product</th>
                    <th>Supplier</th>
                    <th>Reference</th>
                    <th>Received</th>
                    <th>Qty received</th>
                    <th>Qty remaining</th>
                    <th>Status</th>
                    <th class="text-end no-export">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($lots as $row)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td class="fw-semibold">{{ $row->product->name ?? '—' }}</td>
                    <td>{{ $row->vendorContact->name ?? '—' }}</td>
                    <td>{{ $row->reference ?? '—' }}</td>
                    <td>{{ $row->received_at->format('M j, Y') }}</td>
                    <td>{{ $row->quantity_received }}</td>
                    <td>{{ $row->quantity_remaining }}</td>
                    <td>
                        @php
                        $statusClass = match ($row->status) {
                        'active' => 'text-bg-success-subtle text-success-emphasis',
                        'depleted' => 'text-bg-secondary-subtle text-secondary-emphasis',
                        default => 'text-bg-light border',
                        };
                        @endphp
                        <span class="badge {{ $statusClass }}">{{ ucfirst($row->status) }}</span>
                    </td>
                    <td class="text-end no-export">
                        @php
                        $lotPayload = [
                        'id' => $row->id,
                        'product_id' => $row->product_id,
                        'vendor_contact_id' => $row->vendor_contact_id,
                        'reference' => $row->reference,
                        'quantity_received' => $row->quantity_received,
                        'quantity_sold' => $row->quantity_received - $row->quantity_remaining,
                        'received_at' => $row->received_at->format('Y-m-d'),
                        'status' => $row->status,
                        ];
                        @endphp
                        <button type="button"
                            class="btn btn-sm btn-outline-secondary btn-edit-lot"
                            data-lot='{{ json_encode($lotPayload) }}'>
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

<div class="modal fade" id="lotModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="lotForm" method="POST"
                action="{{ $editingId ? route('company.stock-lots.update', ['company' => $company->slug, 'stock_lot' => $editingId]) : route('company.stock-lots.store', ['company' => $company->slug]) }}">
                @csrf
                <input type="hidden" name="_method" id="lotMethod" value="{{ $editingId ? 'PUT' : '' }}">
                <input type="hidden" name="lot_id" id="lotIdField" value="{{ $editingId }}">

                <div class="modal-header">
                    <h5 class="modal-title" id="lotModalTitle">{{ $editingId ? 'Edit stock' : 'Add stock' }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="lotProduct" class="form-label">Product</label>
                        <select class="form-select @error('product_id') is-invalid @enderror" id="lotProduct" name="product_id" required>
                            <option value="">&mdash; Select &mdash;</option>
                            @foreach ($products as $productOption)
                            <option value="{{ $productOption->id }}" @selected(old('product_id')===$productOption->id)>
                                {{ $productOption->name }}
                            </option>
                            @endforeach
                        </select>
                        @error('product_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label for="lotVendor" class="form-label">Supplier <span class="text-muted fw-normal">(optional)</span></label>
                        <select class="form-select @error('vendor_contact_id') is-invalid @enderror" id="lotVendor" name="vendor_contact_id">
                            <option value="">— Not tracked —</option>
                            @foreach ($vendors as $vendorOption)
                            <option value="{{ $vendorOption->id }}" @selected(old('vendor_contact_id')===$vendorOption->id)>
                                {{ $vendorOption->name }}
                            </option>
                            @endforeach
                        </select>
                        @error('vendor_contact_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        @if ($vendors->isEmpty())
                        <div class="form-text">
                            No supplier contacts yet — add one under <a href="{{ route('company.contacts.index', ['company' => $company->slug]) }}">Contacts</a> (type: Vendor) to track who you bought from.
                        </div>
                        @endif
                    </div>

                    <div class="mb-3">
                        <label for="lotReference" class="form-label">Reference <span class="text-muted fw-normal">(optional)</span></label>
                        <input type="text" class="form-control @error('reference') is-invalid @enderror"
                            id="lotReference" name="reference" value="{{ old('reference') }}" placeholder="e.g. supplier invoice or delivery number">
                        @error('reference')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="lotQuantity" class="form-label">Quantity received</label>
                            <input type="number" min="1" class="form-control @error('quantity_received') is-invalid @enderror"
                                id="lotQuantity" name="quantity_received" value="{{ old('quantity_received') }}" required>
                            <div class="form-text" id="lotQuantityHelp">Sets the starting stock for this lot.</div>
                            @error('quantity_received')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="lotReceivedAt" class="form-label">Received on</label>
                            <input type="date" class="form-control @error('received_at') is-invalid @enderror"
                                id="lotReceivedAt" name="received_at" value="{{ old('received_at') }}" required>
                            @error('received_at')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="mb-1" id="lotStatusWrapper" style="{{ $editingId ? '' : 'display: none;' }}">
                        <label for="lotStatus" class="form-label">Status</label>
                        <select class="form-select @error('status') is-invalid @enderror" id="lotStatus" name="status">
                            @foreach (['active' => 'Active', 'depleted' => 'Depleted'] as $value => $label)
                            <option value="{{ $value }}" @selected(old('status', 'active' )===$value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <div class="form-text">Sells out automatically. Set to Depleted by hand only to take leftover stock off sale (damaged, lost).</div>
                        @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save stock</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')


<script>
    $(function() {
        var columnPrint = [0, 1, 2, 4, 5, 6, 7];
        var printTitle = 'Stock - {{ $company->name }} - {{ date("d M, Y") }}';
        $('#lotsTable').DataTable({
            ordering: false,
            paging: false,
            info: false,
            responsive: true,
            columnDefs: [{
                targets: [0],
                orderable: false
            }],
            language: {
                emptyTable: "No stock recorded yet"
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

        var $modalEl = $('#lotModal');
        var modal = new bootstrap.Modal($modalEl[0]);
        var $form = $('#lotForm');
        var storeUrl = "{{ route('company.stock-lots.store', ['company' => $company->slug]) }}";
        var updateUrlTemplate = "{{ route('company.stock-lots.update', ['company' => $company->slug, 'stock_lot' => '__ID__']) }}";

        function openCreateModal() {
            $form[0].reset();
            $form.attr('action', storeUrl);
            $('#lotMethod').val('');
            $('#lotIdField').val('');
            $('#lotModalTitle').text('Add stock');
            $('#lotStatusWrapper').hide();
            $('#lotStatus').val('active');
            $('#lotQuantityHelp').text('Sets the starting stock for this lot.');
            $('#lotReceivedAt').val(new Date().toISOString().slice(0, 10));
            modal.show();
        }

        function openEditModal(lot) {
            $form.attr('action', updateUrlTemplate.replace('__ID__', lot.id));
            $('#lotMethod').val('PUT');
            $('#lotIdField').val(lot.id);
            $('#lotProduct').val(lot.product_id);
            $('#lotVendor').val(lot.vendor_contact_id || '');
            $('#lotReference').val(lot.reference || '');
            $('#lotQuantity').val(lot.quantity_received).attr('min', Math.max(1, lot.quantity_sold));
            $('#lotReceivedAt').val(lot.received_at);
            $('#lotStatus').val(lot.status);
            $('#lotStatusWrapper').show();
            $('#lotQuantityHelp').text(
                lot.quantity_sold > 0 ?
                lot.quantity_sold + ' already sold from this lot. Correcting this moves what\'s left by the same amount; it can\'t go below ' + lot.quantity_sold + '.' :
                'Correcting this moves what\'s left by the same amount.'
            );
            $('#lotModalTitle').text('Edit stock');
            modal.show();
        }

        $('#addLotBtn').on('click', openCreateModal);
        $('#lotsTable tbody').on('click', '.btn-edit-lot', function() {
            openEditModal($(this).data('lot'));
        });

        @if($errors -> any())
        modal.show();
        @endif
    });
</script>
@endpush
