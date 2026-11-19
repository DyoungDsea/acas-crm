@extends('layouts.app')

@section('title', $company->name.' — Dashboard')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h4 mb-1">{{ $company->name }}</h1>
        <span class="badge bg-primary-subtle text-primary rounded-pill px-3 py-2">
            {{ ucfirst(str_replace('_', ' ', $company->type)) }}
        </span>
    </div>
</div>

{{-- Universal stat cards — every company type has Contacts, Deals,
     Orders, and Payments, so these render unconditionally. --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-lg-4 col-xl-2">
        <a href="{{ route('company.contacts.index', ['company' => $company->slug]) }}" class="text-decoration-none">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center py-4">
                    <div class="fw-bold fs-4 text-dark">{{ $contactsCount }}</div>
                    <div class="text-muted small">Contacts</div>
                </div>
            </div>
        </a>
    </div>
    <div class="col-6 col-lg-4 col-xl-2">
        <a href="{{ route('company.deals.index', ['company' => $company->slug]) }}" class="text-decoration-none">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center py-4">
                    <div class="fw-bold fs-4 text-dark">{{ $openDealsCount }}</div>
                    <div class="text-muted small">Open deals</div>
                    <div class="text-muted small">{{ formatNaira($openDealsValue, 2) }}</div>
                </div>
            </div>
        </a>
    </div>
    <div class="col-6 col-lg-4 col-xl-2">
        <a href="{{ route('company.orders.index', ['company' => $company->slug]) }}" class="text-decoration-none">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center py-4">
                    <div class="fw-bold fs-4 text-dark">{{ $ordersCount }}</div>
                    <div class="text-muted small">Orders</div>
                </div>
            </div>
        </a>
    </div>
    <div class="col-6 col-lg-4 col-xl-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body py-4">
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <tbody>
                            <tr>
                                <th>Total revenue</th>
                                <td class="text-end">{{ formatNaira($totalRevenue, 2) }}</td>
                            </tr>
                            <tr>
                                <th>Outstanding balance</th>
                                <td class="text-end {{ $outstandingBalance > 0 ? 'text-warning-emphasis' : 'text-dark' }}">{{ formatNaira($outstandingBalance, 2) }}</td>
                            </tr>
                            <tr>
                                <th>Collected this month</th>
                                <td class="text-end text-success-emphasis">{{ formatNaira($paymentsThisMonth, 2) }}</td>
                            </tr>


                        </tbody>

                    </table>
                </div>

            </div>
        </div>
    </div>

</div>

<div class="row g-3 mb-4">
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="fw-semibold mb-0">Recent orders</h6>
                  
                </div>
                @if ($recentOrders->isEmpty())
                <p class="text-muted small mb-0">No orders yet.</p>
                @else
                <table class="table table-sm align-middle mb-0">
                    <tbody>
                        @foreach ($recentOrders as $row)
                        <tr>
                            <td class="fw-semibold">{{ $row->order_number }}</td>
                            <td>{{ $row->contact->name ?? '—' }}</td>
                            <td class="text-end">{{ formatNaira($row->total_amount, 2) }}</td>
                            <td class="text-end">
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
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                @endif
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="fw-semibold mb-0">Recent payments</h6>
                    <a href="{{ route('company.payments.index', ['company' => $company->slug]) }}" class="small">View all</a>
                </div>
                @if ($recentPayments->isEmpty())
                <p class="text-muted small mb-0">No payments yet.</p>
                @else
                <table class="table table-sm align-middle mb-0">
                    <tbody>
                        @foreach ($recentPayments as $row)
                        <tr>
                            <td class="fw-semibold">{{ $row->order->order_number ?? '—' }}</td>
                            <td>{{ $row->order->contact->name ?? '—' }}</td>
                            <td class="text-end">{{ formatNaira($row->amount, 2) }}</td>
                            <td class="text-end text-muted small">{{ $row->received_at?->format('d M Y') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- Pharmacy-only. Every other company type has no stock table to
     alert on yet, so this section is entirely absent for them rather
     than shown empty. --}}
@if ($company->type === 'pharmacy')
<div class="row g-3">
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="fw-semibold mb-0">Expiring within 30 days</h6>
                    <a href="{{ route('company.pharmacy-batches.index', ['company' => $company->slug]) }}" class="small">View all stock</a>
                </div>
                @if ($expiringBatches->isEmpty())
                <p class="text-muted small mb-0">Nothing expiring soon.</p>
                @else
                <table class="table table-sm align-middle mb-0">
                    <tbody>
                        @foreach ($expiringBatches as $batch)
                        <tr>
                            <td class="fw-semibold">{{ $batch->product->name ?? '—' }}</td>
                            <td class="text-muted small">{{ $batch->batch_number }}</td>
                            <td class="text-end">{{ $batch->quantity_remaining }} left</td>
                            <td class="text-end text-warning-emphasis small">{{ $batch->expiry_date->format('d M Y') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                @endif
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="fw-semibold mb-0">Low stock (10 or fewer units)</h6>
                    <a href="{{ route('company.pharmacy-batches.index', ['company' => $company->slug]) }}" class="small">View all stock</a>
                </div>
                @if ($lowStockProducts->isEmpty())
                <p class="text-muted small mb-0">Nothing running low.</p>
                @else
                <table class="table table-sm align-middle mb-0">
                    <tbody>
                        @foreach ($lowStockProducts as $row)
                        <tr>
                            <td class="fw-semibold">{{ $row->product->name ?? '—' }}</td>
                            <td class="text-end text-danger-emphasis">{{ $row->total }} left</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                @endif
            </div>
        </div>
    </div>
</div>
@endif
@endsection
