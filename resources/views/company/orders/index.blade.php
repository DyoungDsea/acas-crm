@extends('layouts.app')

@section('title', 'Orders')



@php
$editingId = old('order_id');
@endphp

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h4 mb-1">Orders</h1>
        <p class="text-muted small mb-0">Sales orders for {{ $company->name }}.</p>
    </div>
    <button type="button" class="btn btn-primary" id="addOrderBtn" @disabled($contacts->isEmpty())>
        <i class="bi bi-plus-lg me-1"></i> Add order
    </button>
</div>

@if ($contacts->isEmpty())
    <div class="alert alert-warning">
        No customers yet — add a contact with type "Customer" under
        <a href="{{ route('company.contacts.index', ['company' => $company->slug]) }}">Contacts</a> first.
    </div>
@endif

<div class="card border-0 shadow-sm">
    <div class="card-body">
        {{-- No delete action on this table on purpose — an order is
             financial history, not a draft. Editing is only offered
             once payments exist can't be edited either (guarded
             server-side); the Edit button is simply hidden here.
             Loop variable is $row, not $company. --}}
        <table class="table table-hover align-middle w-100" id="ordersTable">
            <thead>
                <tr>
                    <th>S/N</th>
                    <th>Order #</th>
                    <th>Contact</th>
                    <th>Items</th>
                    <th>Total</th>
                    <th>Paid</th>
                    <th>Balance</th>
                    <th>Status</th>
                    <th class="text-end no-export">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($orders as $row)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td class="fw-semibold">{{ $row->order_number }}</td>
                    <td>{{ $row->contact->name ?? '—' }}</td>
                    <td>{{ $row->items->count() }}</td>
                    <td>{{ formatNaira($row->total_amount, 2) }}</td>
                    <td>{{ formatNaira($row->amountPaid(), 2) }}</td>
                    <td>{{ formatNaira($row->balanceDue(), 2) }}</td>
                    <td>
                        @php
                            $statusClass = match ($row->status) {
                                'paid' => 'text-bg-success-subtle text-success-emphasis',
                                'partially_paid' => 'text-bg-warning-subtle text-warning-emphasis',
                                'void' => 'text-bg-danger-subtle text-danger-emphasis',
                                default => 'text-bg-light border',
                            };
                        @endphp
                        <span class="badge {{ $statusClass }}">{{ ucwords(str_replace('_', ' ', $row->status)) }}</span>
                    </td>
                    <td class="text-end no-export">
                        @if ($row->payments->isEmpty())
                            @php
                                $orderPayload = [
                                    'id' => $row->id,
                                    'order_number' => $row->order_number,
                                    'contact_id' => $row->contact_id,
                                    'items' => $row->items->map(fn ($item) => [
                                        'product_id' => $item->product_id,
                                        'description' => $item->description,
                                        'quantity' => $item->quantity,
                                        'unit_price' => $item->unit_price,
                                    ])->values(),
                                ];
                            @endphp
                            <button type="button"
                                class="btn btn-sm btn-outline-secondary btn-edit-order"
                                data-order='{{ json_encode($orderPayload) }}'>
                                <i class="bi bi-pencil"></i> Edit
                            </button>
                        @else
                            <span class="text-muted small">Locked (has payments)</span>
                        @endif
                    </td>
                </tr>
                @empty
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="orderModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="orderForm" method="POST"
                action="{{ $editingId ? route('company.orders.update', ['company' => $company->slug, 'order' => $editingId]) : route('company.orders.store', ['company' => $company->slug]) }}">
                @csrf
                <input type="hidden" name="_method" id="orderMethod" value="{{ $editingId ? 'PUT' : '' }}">
                <input type="hidden" name="order_id" id="orderIdField" value="{{ $editingId }}">

                <div class="modal-header">
                    <h5 class="modal-title" id="orderModalTitle">{{ $editingId ? 'Edit order' : 'Add order' }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="orderContact" class="form-label">Contact</label>
                        <select class="form-select @error('contact_id') is-invalid @enderror" id="orderContact" name="contact_id" required>
                            <option value="">&mdash; Select &mdash;</option>
                            @foreach ($contacts as $contactOption)
                                <option value="{{ $contactOption->id }}" @selected(old('contact_id') === $contactOption->id)>
                                    {{ $contactOption->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('contact_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <label class="form-label">Line items</label>
                    <table class="table table-sm align-middle" id="orderItemsTable">
                        <thead>
                            <tr>
                                <th style="min-width: 180px;">Product</th>
                                <th>Description</th>
                                <th style="width: 90px;">Qty</th>
                                <th style="width: 130px;">Unit price</th>
                                <th style="width: 130px;">Subtotal</th>
                                <th style="width: 40px;"></th>
                            </tr>
                        </thead>
                        <tbody id="orderItemsBody"></tbody>
                        <tfoot>
                            <tr>
                                <td colspan="4" class="text-end fw-semibold">Total</td>
                                <td class="fw-semibold" id="orderItemsGrandTotal">0.00</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                    @error('items')<div class="text-danger small mb-2">{{ $message }}</div>@enderror

                    <button type="button" class="btn btn-sm btn-outline-primary" id="addOrderItemBtn">
                        <i class="bi bi-plus-lg me-1"></i> Add line
                    </button>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save order</button>
                </div>
            </form>
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
        var columnPrint = [0, 1, 2, 3, 4, 5, 6, 7];
        var printTitle = 'Orders - {{ $company->name }} - {{ date("d M, Y") }}';
        $('#ordersTable').DataTable({
            ordering: false,
            paging: false,
            info: false,
            responsive: true,
            columnDefs: [{ targets: [0], orderable: false }],
            language: { emptyTable: "No orders yet" },
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

        var products = @json($products->map(fn ($p) => ['id' => $p->id, 'name' => $p->name, 'unit_price' => $p->unit_price]));

        var $modalEl = $('#orderModal');
        var modal = new bootstrap.Modal($modalEl[0]);
        var $form = $('#orderForm');
        var storeUrl = "{{ route('company.orders.store', ['company' => $company->slug]) }}";
        var updateUrlTemplate = "{{ route('company.orders.update', ['company' => $company->slug, 'order' => '__ID__']) }}";
        var itemIndex = 0;

        function productOptions(selectedId) {
            var html = '<option value="">Custom / no product</option>';
            products.forEach(function(p) {
                var sel = (selectedId && String(selectedId) === String(p.id)) ? ' selected' : '';
                html += '<option value="' + p.id + '" data-price="' + p.unit_price + '" data-name="' + p.name.replace(/"/g, '&quot;') + '"' + sel + '>' + p.name + '</option>';
            });
            return html;
        }

        function addItemRow(item) {
            item = item || {};
            var idx = itemIndex++;
            var $row = $(
                '<tr data-idx="' + idx + '">' +
                    '<td><select class="form-select form-select-sm item-product" name="items[' + idx + '][product_id]">' + productOptions(item.product_id) + '</select></td>' +
                    '<td><input type="text" class="form-control form-control-sm item-description" name="items[' + idx + '][description]" required></td>' +
                    '<td><input type="number" min="1" step="1" class="form-control form-control-sm item-quantity" name="items[' + idx + '][quantity]" value="1" required></td>' +
                    '<td><input type="number" min="0" step="0.01" class="form-control form-control-sm item-unit-price" name="items[' + idx + '][unit_price]" value="0.00" required></td>' +
                    '<td class="item-subtotal">0.00</td>' +
                    '<td><button type="button" class="btn btn-sm btn-outline-danger remove-item-btn"><i class="bi bi-trash"></i></button></td>' +
                '</tr>'
            );

            $('#orderItemsBody').append($row);

            $row.find('.item-description').val(item.description || '');
            $row.find('.item-quantity').val(item.quantity || 1);
            $row.find('.item-unit-price').val(item.unit_price != null ? item.unit_price : '0.00');
            recalcRow($row);
        }

        function recalcRow($row) {
            var qty = parseFloat($row.find('.item-quantity').val()) || 0;
            var price = parseFloat($row.find('.item-unit-price').val()) || 0;
            $row.find('.item-subtotal').text((qty * price).toFixed(2));
            recalcGrandTotal();
        }

        function recalcGrandTotal() {
            var total = 0;
            $('#orderItemsBody tr').each(function() {
                total += parseFloat($(this).find('.item-subtotal').text()) || 0;
            });
            $('#orderItemsGrandTotal').text(total.toFixed(2));
        }

        function resetItems() {
            $('#orderItemsBody').empty();
            itemIndex = 0;
            recalcGrandTotal();
        }

        function openCreateModal() {
            $form[0].reset();
            $form.attr('action', storeUrl);
            $('#orderMethod').val('');
            $('#orderIdField').val('');
            $('#orderModalTitle').text('Add order');
            resetItems();
            addItemRow();
            modal.show();
        }

        function openEditModal(order) {
            $form.attr('action', updateUrlTemplate.replace('__ID__', order.id));
            $('#orderMethod').val('PUT');
            $('#orderIdField').val(order.id);
            $('#orderContact').val(order.contact_id);
            $('#orderModalTitle').text('Edit ' + order.order_number);
            resetItems();
            (order.items && order.items.length ? order.items : [{}]).forEach(addItemRow);
            modal.show();
        }

        $('#addOrderBtn').on('click', openCreateModal);
        $('#addOrderItemBtn').on('click', function() { addItemRow(); });

        $('#ordersTable tbody').on('click', '.btn-edit-order', function() {
            openEditModal($(this).data('order'));
        });

        $('#orderItemsBody')
            .on('input', '.item-quantity, .item-unit-price', function() {
                recalcRow($(this).closest('tr'));
            })
            .on('change', '.item-product', function() {
                var $opt = $(this).find('option:selected');
                var $row = $(this).closest('tr');
                if ($opt.val()) {
                    $row.find('.item-description').val($opt.data('name'));
                    $row.find('.item-unit-price').val(parseFloat($opt.data('price')).toFixed(2));
                    recalcRow($row);
                }
            })
            .on('click', '.remove-item-btn', function() {
                if ($('#orderItemsBody tr').length > 1) {
                    $(this).closest('tr').remove();
                    recalcGrandTotal();
                }
            });

        @if ($errors->any())
            resetItems();
            @if (old('items'))
                @foreach (old('items') as $oldItem)
                    addItemRow(@json($oldItem));
                @endforeach
            @else
                addItemRow();
            @endif
            modal.show();
        @endif
    });
</script>
@endpush
