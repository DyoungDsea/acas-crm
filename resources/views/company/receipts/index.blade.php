@extends('layouts.app')

@section('title', 'Receipts')

@push('styles')
<style>
    @media print {
        body * {
            visibility: hidden;
        }

        #receiptPrintArea,
        #receiptPrintArea * {
            visibility: visible;
        }

        #receiptPrintArea {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            padding: 24px;
        }

        .modal-header,
        .modal-footer {
            display: none !important;
        }

        #receiptPrintArea img {
            max-width: 100%;
        }

        @page {
            margin: 8mm;
        }
    }

    .receipt-voided-stamp {
        position: absolute;
        top: 40%;
        left: 50%;
        transform: translate(-50%, -50%) rotate(-20deg);
        font-size: 3rem;
        font-weight: 700;
        color: #dc3545;
        opacity: 0.25;
        border: 6px solid #dc3545;
        padding: 0.25em 0.5em;
        letter-spacing: 0.1em;
        pointer-events: none;
    }
</style>
@endpush

@section('content')
<div class="mb-4">
    <h1 class="h4 mb-1">Receipts</h1>
    <p class="text-muted small mb-0">Issued for every payment recorded against an order for {{ $company->name }}. Print a copy for the customer here.</p>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        {{-- No "add" button on this page on purpose — a receipt is
             only ever created as a side effect of recording a payment
             (see Payments). Voiding also lives on Payments now, not
             here — this page is purely "view / print for the
             customer". Loop variable is $row, not $company. --}}
        <table class="table table-hover align-middle w-100" id="receiptsTable">
            <thead>
                <tr>
                    <th>S/N</th>
                    <th>Receipt #</th>
                    <th>Order #</th>
                    <th>Contact</th>
                    <th>Amount</th>
                    <th>Issued by</th>
                    <th>Issued at</th>
                    <th>Status</th>
                    <th class="text-end no-export">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($receipts as $row)
                @php
                $order = $row->payment->order;
                $receiptPayload = [
                'receipt_number' => $row->receipt_number,
                'issued_at' => optional($row->issued_at)->format('d M Y, H:i'),
                'issued_by' => $row->issuedByUser->name ?? '—',
                'is_voided' => $row->isVoided(),
                'company' => [
                'name' => $company->name,
                'address' => $company->address,
                'phone' => $company->phone,
                'email' => $company->email,
                'header_url' => $company->receipt_header_path ? \Illuminate\Support\Facades\Storage::disk('public')->url($company->receipt_header_path) : null,
                'footer_url' => $company->receipt_footer_path ? \Illuminate\Support\Facades\Storage::disk('public')->url($company->receipt_footer_path) : null,
                'note' => $company->receipt_note,
                ],
                'contact' => [
                'name' => $order->contact->name ?? '—',
                'phone' => $order->contact->phone ?? null,
                'email' => $order->contact->email ?? null,
                'address' => $order->contact->address ?? null,
                ],
                'order_number' => $order->order_number,
                'items' => $order->items->map(fn ($item) => [
                'description' => $item->description,
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
                'subtotal' => $item->subtotal,
                ])->values(),
                'order_total' => $order->total_amount,
                'payment_amount' => $row->payment->amount,
                'payment_method' => $row->payment->method,
                'payment_reference' => $row->payment->reference_note,
                'received_at' => optional($row->payment->received_at)->format('d M Y, H:i'),
                'amount_paid_to_date' => $order->amountPaid(),
                'balance_due' => $order->balanceDue(),
                ];
                @endphp
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td class="fw-semibold">{{ $row->receipt_number }}</td>
                    <td>{{ $order->order_number ?? '—' }}</td>
                    <td>{{ $order->contact->name ?? '—' }}</td>
                    <td>{{ formatNaira($row->payment->amount ?? 0, 2) }}</td>
                    <td>{{ $row->issuedByUser->name ?? '—' }}</td>
                    <td>{{ $row->issued_at?->format('d M Y, H:i') }}</td>
                    <td>
                        @if ($row->isVoided())
                        <span class="badge text-bg-danger-subtle text-danger-emphasis">Void</span>
                        @else
                        <span class="badge text-bg-success-subtle text-success-emphasis">Valid</span>
                        @endif
                    </td>
                    <td class="text-end no-export">
                        <button type="button" class="btn btn-sm btn-outline-secondary btn-view-receipt"
                            data-receipt='{{ json_encode($receiptPayload) }}'>
                            <i class="bi bi-printer"></i> View / print
                        </button>
                    </td>
                </tr>
                @empty
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="receiptModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Receipt</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="receiptPrintArea" class="position-relative p-2">
                    <div id="receiptVoidStamp" class="receipt-voided-stamp d-none">VOID</div>

                    {{-- Company letterhead banner (Settings > Receipt letterhead).
                         When present it replaces the plain-text company block. --}}
                    <img id="receiptHeaderImg" class="d-none w-100 mb-3" alt="">

                    <div class="d-flex justify-content-between align-items-start mb-4">
                        <div id="receiptCompanyBlock">
                            <h4 class="mb-1" id="receiptCompanyName"></h4>
                            <div class="text-muted small" id="receiptCompanyAddress"></div>
                            <div class="text-muted small" id="receiptCompanyContact"></div>
                        </div>
                        <div class="text-end ms-auto">
                            <h5 class="mb-1">RECEIPT</h5>
                            <div class="small"><strong>No:</strong> <span id="receiptNumber"></span></div>
                            <div class="small"><strong>Order:</strong> <span id="receiptOrderNumber"></span></div>
                            <div class="small"><strong>Date:</strong> <span id="receiptIssuedAt"></span></div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="fw-semibold small text-uppercase text-muted mb-1">Billed to</div>
                        <div id="receiptContactName" class="fw-semibold"></div>
                        <div class="small text-muted" id="receiptContactDetails"></div>
                    </div>

                    <table class="table table-sm table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th>Description</th>
                                <th class="text-end" style="width: 80px;">Qty</th>
                                <th class="text-end" style="width: 120px;">Unit price</th>
                                <th class="text-end" style="width: 120px;">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody id="receiptItemsBody"></tbody>
                        <tfoot>
                            <tr>
                                <td colspan="3" class="text-end fw-semibold">Order total</td>
                                <td class="text-end fw-semibold" id="receiptOrderTotal"></td>
                            </tr>
                        </tfoot>
                    </table>

                    <div class="row mt-4">
                        <div class="col-md-6">
                            <div class="fw-semibold small text-uppercase text-muted mb-1">Payment</div>
                            <div class="small">Method: <span id="receiptMethod"></span></div>
                            <div class="small">Reference: <span id="receiptReference"></span></div>
                            <div class="small">Received: <span id="receiptReceivedAt"></span></div>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-sm mb-0">
                                <tbody>
                                    <tr>
                                        <td class="border-0 ps-0">This payment</td>
                                        <td class="border-0 text-end pe-0" id="receiptPaymentAmount"></td>
                                    </tr>
                                    <tr>
                                        <td class="border-0 ps-0">Total paid to date</td>
                                        <td class="border-0 text-end pe-0" id="receiptPaidToDate"></td>
                                    </tr>
                                    <tr>
                                        <td class="border-0 ps-0 fw-semibold">Balance due</td>
                                        <td class="border-0 text-end pe-0 fw-semibold" id="receiptBalanceDue"></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="text-muted small mt-4">
                        Issued by <span id="receiptIssuedBy"></span>. Thank you for your business.
                    </div>
                    <div class="small mt-2 d-none" id="receiptNote" style="white-space: pre-line;"></div>

                    <img id="receiptFooterImg" class="d-none w-100 mt-4" alt="">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="printReceiptBtn"><i class="bi bi-printer me-1"></i> Print</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')


<script>
    $(function() {
        var columnPrint = [0, 1, 2, 3, 4, 5, 6, 7];
        var printTitle = 'Receipts - {{ $company->name }} - {{ date("d M, Y") }}';
        $('#receiptsTable').DataTable({
            ordering: false,
            paging: false,
            info: false,
            responsive: true,
            columnDefs: [{
                targets: [0],
                orderable: false
            }],
            language: {
                emptyTable: "No receipts yet"
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

        function money(n) {
            return '₦' + parseFloat(n || 0).toLocaleString('en-NG', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        }

        function openReceipt(receipt) {
            $('#receiptCompanyName').text(receipt.company.name);
            $('#receiptCompanyAddress').text(receipt.company.address || '');
            $('#receiptCompanyContact').text([receipt.company.phone, receipt.company.email].filter(Boolean).join(' · '));

            $('#receiptNumber').text(receipt.receipt_number);
            $('#receiptOrderNumber').text(receipt.order_number);
            $('#receiptIssuedAt').text(receipt.issued_at || '—');

            $('#receiptContactName').text(receipt.contact.name);
            $('#receiptContactDetails').text([receipt.contact.phone, receipt.contact.email, receipt.contact.address].filter(Boolean).join(' · '));

            var $body = $('#receiptItemsBody').empty();
            (receipt.items || []).forEach(function(item) {
                $body.append(
                    '<tr>' +
                    '<td>' + $('<div>').text(item.description).html() + '</td>' +
                    '<td class="text-end">' + item.quantity + '</td>' +
                    '<td class="text-end">' + money(item.unit_price) + '</td>' +
                    '<td class="text-end">' + money(item.subtotal) + '</td>' +
                    '</tr>'
                );
            });
            $('#receiptOrderTotal').text(money(receipt.order_total));

            $('#receiptMethod').text(receipt.payment_method ? receipt.payment_method.toUpperCase() : '—');
            $('#receiptReference').text(receipt.payment_reference || '—');
            $('#receiptReceivedAt').text(receipt.received_at || '—');
            $('#receiptPaymentAmount').text(money(receipt.payment_amount));
            $('#receiptPaidToDate').text(money(receipt.amount_paid_to_date));
            $('#receiptBalanceDue').text(money(receipt.balance_due));
            $('#receiptIssuedBy').text(receipt.issued_by);

            $('#receiptVoidStamp').toggleClass('d-none', !receipt.is_voided);

            // Letterhead: header image replaces the text company block.
            var hasHeader = !!receipt.company.header_url;
            $('#receiptHeaderImg').toggleClass('d-none', !hasHeader).attr('src', receipt.company.header_url || '');
            $('#receiptCompanyBlock').toggleClass('d-none', hasHeader);
            $('#receiptFooterImg').toggleClass('d-none', !receipt.company.footer_url).attr('src', receipt.company.footer_url || '');
            $('#receiptNote').toggleClass('d-none', !receipt.company.note).text(receipt.company.note || '');

            new bootstrap.Modal(document.getElementById('receiptModal')).show();
        }

        $('#receiptsTable tbody').on('click', '.btn-view-receipt', function() {
            openReceipt($(this).data('receipt'));
        });

        $('#printReceiptBtn').on('click', function() {
            window.print();
        });
    });
</script>
@endpush