@extends('layouts.app')

@section('title', 'Payments')


@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h4 mb-1">Payments</h1>
        <p class="text-muted small mb-0">Payments recorded against orders for {{ $company->name }}.</p>
    </div>
    <button type="button" class="btn btn-primary" id="addPaymentBtn" @disabled($openOrders->isEmpty())>
        <i class="bi bi-plus-lg me-1"></i> Record payment
    </button>
</div>

@if ($openOrders->isEmpty())
<div class="alert alert-warning">
    No orders currently have a balance due — every order is either fully paid or has no
    payments to make yet. Add one under
    <a href="{{ route('company.orders.index', ['company' => $company->slug]) }}">Orders</a>.
</div>
@endif

<div class="card border-0 shadow-sm">
    <div class="card-body">
        {{-- No edit action on this table on purpose — a payment is an
             immutable financial record once logged. The only action
             available is Void, which voids the payment's receipt
             rather than deleting the payment — see Receipt::void().
             Loop variable is $row, not $company. --}}
        <table class="table table-hover align-middle w-100" id="paymentsTable">
            <thead>
                <tr>
                    <th>S/N</th>
                    <th>Order #</th>
                    <th>Contact</th>
                    <th>Amount</th>
                    <th>Method</th>
                    <th>Reference</th>
                    <th>Received by</th>
                    <th>Received at</th>
                    <th class="text-end no-export">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($payments as $row)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td class="fw-semibold">{{ $row->order->order_number ?? '—' }}</td>
                    <td>{{ $row->order->contact->name ?? '—' }}</td>
                    <td>{{ formatNaira($row->amount, 2) }}</td>
                    <td><span class="badge text-bg-light border">{{ ucfirst($row->method) }}</span></td>
                    <td>{{ $row->reference_note ?? '—' }}</td>
                    <td>{{ $row->receivedByUser->name ?? '—' }}</td>
                    <td>{{ $row->received_at?->format('d/m/Y, H:i') }}</td>

                    <td class="text-end no-export">
                        @if ($row->receipt->isVoided())
                        <span class="badge text-bg-danger-subtle text-danger-emphasis ms-1">Void</span>
                        @endif
                        @if ($row->receipt && ! $row->receipt->isVoided())
                        <form method="POST" action="{{ route('company.payments.void', ['company' => $company->slug, 'payment' => $row->id]) }}" class="d-inline form-void-payment">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                <i class="bi bi-x-circle"></i> Void
                            </button>
                        </form>
                        @endif
                    </td>
                </tr>
                @empty
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="paymentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="paymentForm" method="POST" action="{{ route('company.payments.store', ['company' => $company->slug]) }}">
                @csrf

                <div class="modal-header">
                    <h5 class="modal-title">Record payment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="paymentOrder" class="form-label">Order</label>
                        <select class="form-select @error('order_id') is-invalid @enderror" id="paymentOrder" name="order_id" required>
                            <option value="">&mdash; Select &mdash;</option>
                            @foreach ($openOrders as $orderOption)
                            <option value="{{ $orderOption['id'] }}" data-balance="{{ $orderOption['balance_due'] }}"
                                @selected(old('order_id')===$orderOption['id'])>
                                {{ $orderOption['order_number'] }} — {{ $orderOption['contact_name'] }} (balance: {{ number_format($orderOption['balance_due'], 2) }})
                            </option>
                            @endforeach
                        </select>
                        @error('order_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label for="paymentAmount" class="form-label">Amount</label>
                        <input type="number" step="0.01" min="0.01" class="form-control @error('amount') is-invalid @enderror"
                            id="paymentAmount" name="amount" value="{{ old('amount') }}" required>
                        @error('amount')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <div class="form-text" id="paymentAmountHint">
                            @if ($paymentSetting->require_full_payment)
                            This company requires payment in full.
                            @elseif ($paymentSetting->allow_partial_payments && $paymentSetting->minimum_partial_percent)
                            Minimum partial payment: {{ $paymentSetting->minimum_partial_percent }}% of the order total, unless paying the balance in full.
                            @endif
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="paymentMethod" class="form-label">Method</label>
                            <select class="form-select @error('method') is-invalid @enderror" id="paymentMethod" name="method" required>
                                <option value="">&mdash; Select &mdash;</option>
                                @foreach (['cash' => 'Cash', 'transfer' => 'Transfer', 'pos' => 'POS', 'cheque' => 'Cheque'] as $value => $label)
                                <option value="{{ $value }}" @selected(old('method')===$value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('method')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="paymentReceivedAt" class="form-label">Received at</label>
                            <input type="datetime-local" class="form-control @error('received_at') is-invalid @enderror"
                                id="paymentReceivedAt" name="received_at" value="{{ old('received_at') }}">
                            <div class="form-text">Leave blank to use now.</div>
                            @error('received_at')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="mb-1">
                        <label for="paymentReference" class="form-label">Reference note</label>
                        <input type="text" class="form-control @error('reference_note') is-invalid @enderror"
                            id="paymentReference" name="reference_note" value="{{ old('reference_note') }}"
                            placeholder="e.g. bank alert ref, POS slip no.">
                        @error('reference_note')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save payment</button>
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
        var printTitle = 'Payments - {{ $company->name }} - {{ date("d M, Y") }}';
        $('#paymentsTable').DataTable({
            ordering: false,
            paging: false,
            info: false,
            responsive: true,
            columnDefs: [{
                targets: [0],
                orderable: false
            }],
            language: {
                emptyTable: "No payments yet"
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

        var $modalEl = $('#paymentModal');
        var modal = new bootstrap.Modal($modalEl[0]);
        var $form = $('#paymentForm');

        $('#addPaymentBtn').on('click', function() {
            $form[0].reset();
            modal.show();
        });

        // Cap the amount field to the selected order's balance so a
        // typo can't submit something the server will reject anyway.
        $('#paymentOrder').on('change', function() {
            var balance = parseFloat($(this).find('option:selected').data('balance'));
            if (!isNaN(balance)) {
                $('#paymentAmount').attr('max', balance.toFixed(2));
            } else {
                $('#paymentAmount').removeAttr('max');
            }
        });

        $('.form-void-payment').on('submit', function(e) {
            e.preventDefault();
            var $form = $(this);
            if (window.alertify) {
                alertify.confirm('Void payment', 'This voids the receipt tied to this payment. It cannot be undone, and correcting it means recording a fresh payment. Continue?',
                    function() {
                        $form.off('submit').trigger('submit');
                    },
                    function() {});
            } else {
                $form.off('submit').trigger('submit');
            }
        });

        @if($errors -> any())
        modal.show();
        @endif
    });
</script>
@endpush
