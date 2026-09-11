@extends('layouts.app')

@section('title', 'Reports')


@section('content')
<div class="mb-4">
    <h1 class="h4 mb-1">Reports</h1>
    <p class="text-muted small mb-0">Group-wide activity and revenue, across every company.</p>
</div>

{{-- Read-only page — no add/edit here, this is a report over data
     that already lives on Companies/Orders/Payments. --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-lg-4 col-xl-2">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center py-4">
                <div class="fw-bold fs-4 text-dark">{{ $activeCompanies }}<span class="text-muted fs-6">/{{ $totalCompanies }}</span></div>
                <div class="text-muted small">Active companies</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-4 col-xl-2">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center py-4">
                <div class="fw-bold fs-4 text-dark">{{ $totalContacts }}</div>
                <div class="text-muted small">Contacts</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-4 col-xl-2">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center py-4">
                <div class="fw-bold fs-4 text-dark">{{ $totalOrders }}</div>
                <div class="text-muted small">Orders</div>
            </div>
        </div>
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
                                <td class="text-end {{ $totalOutstanding > 0 ? 'text-warning-emphasis' : 'text-dark' }}">{{ formatNaira($totalOutstanding, 2) }}</td>
                            </tr>
                            <tr>
                                <th>Collected this month</th>
                                <td class="text-end text-success-emphasis">{{ formatNaira($totalCollectedThisMonth, 2) }}</td>
                            </tr>


                        </tbody>

                    </table>
                </div>

            </div>
        </div>
    </div>


</div>

<div class="row g-3 mb-4">
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h6 class="fw-semibold mb-3">Revenue by company type</h6>
                @if ($byType->isEmpty())
                <p class="text-muted small mb-0">No companies yet.</p>
                @else
                <table class="table table-sm align-middle mb-0">
                    <tbody>
                        @foreach ($byType as $row)
                        <tr>
                            <td>
                                <span class="badge text-bg-light border">{{ ucfirst(str_replace('_', ' ', $row['type'] ?? 'unset')) }}</span>
                                <span class="text-muted small ms-1">{{ $row['company_count'] }} compan{{ $row['company_count'] === 1 ? 'y' : 'ies' }}</span>
                            </td>
                            <td class="text-end fw-semibold">{{ formatNaira($row['revenue']) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                @endif
            </div>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h6 class="fw-semibold mb-3">Collected this month, by company</h6>
                @php
                $collectingCompanies = $perCompany->where('collected_this_month', '>', 0);
                @endphp
                @if ($collectingCompanies->isEmpty())
                <p class="text-muted small mb-0">No payments recorded this month yet.</p>
                @else
                <table class="table table-sm align-middle mb-0">
                    <tbody>
                        @foreach ($collectingCompanies->sortByDesc('collected_this_month') as $row)
                        <tr>
                            <td class="fw-semibold">{{ $row['company']->name }}</td>
                            <td class="text-end">{{ formatNaira($row['collected_this_month']) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        {{-- Loop variable is $row, not $company — this view extends
             layouts.app, which shares PHP scope with it. Looping as
             $company would clobber the layout's own $company (used
             for sidebar theming) and leave it stuck on whichever row
             rendered last. --}}
        <table class="table table-hover align-middle w-100" id="reportsTable">
            <thead>
                <tr>
                    <th>S/N</th>
                    <th>Company</th>
                    <th>Type</th>
                    <th>Contacts</th>
                    <th>Orders</th>
                    <th>Revenue</th>
                    <th>Outstanding</th>
                    <th>Collected this month</th>
                    <th class="text-end no-export">&nbsp;</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($perCompany as $row)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td class="fw-semibold">{{ $row['company']->name }}</td>
                    <td><span class="badge text-bg-light border">{{ ucfirst(str_replace('_', ' ', $row['company']->type)) }}</span></td>
                    <td>{{ $row['company']->contacts_count }}</td>
                    <td>{{ $row['company']->orders_count }}</td>
                    <td>{{ formatNaira($row['revenue']) }}</td>
                    <td class="{{ $row['outstanding'] > 0 ? 'text-warning-emphasis' : '' }}">{{ formatNaira($row['outstanding']) }}</td>
                    <td>{{ formatNaira($row['collected_this_month']) }}</td>
                    <td class="text-end no-export">
                        <a href="{{ route('company.dashboard', ['company' => $row['company']->slug]) }}" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-arrow-right-circle"></i> View
                        </a>
                    </td>
                </tr>
                @empty
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

@push('scripts') 
<script>
    $(function() {
        var columnPrint = [0, 1, 2, 3, 4, 5, 6, 7];
        var printTitle = 'Group Report - {{ date("d M, Y") }}';
        $('#reportsTable').DataTable({
            paging: false,
            info: false,
            responsive: true,
            columnDefs: [{
                targets: [0, 8],
                orderable: false
            }],
            order: [
                [5, 'desc']
            ],
            language: {
                emptyTable: "No companies yet"
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
    });
</script>
@endpush
