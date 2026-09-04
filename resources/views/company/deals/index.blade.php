@extends('layouts.app')

@section('title', 'Deals')


@php
$editingId = old('deal_id');
@endphp

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h4 mb-1">Deals</h1>
        <p class="text-muted small mb-0">Sales opportunities for {{ $company->name }}.</p>
    </div>
    <button type="button" class="btn btn-primary" id="addDealBtn">
        <i class="bi bi-plus-lg me-1"></i> Add deal
    </button>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        {{-- No delete action on this table on purpose — a deal that
             isn't going anywhere gets marked "Lost" via Edit, not
             erased. Loop variable is $row, not $company. --}}
        <table class="table table-hover align-middle w-100" id="dealsTable">
            <thead>
                <tr>
                    <th>S/N</th>
                    <th>Title</th>
                    <th>Contact</th>
                    <th>Stage</th>
                    <th>Amount</th>
                    <th>Owner</th>
                    <th>Status</th>
                    <th class="text-end no-export">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($deals as $row)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td class="fw-semibold">{{ $row->title }}</td>
                    <td>{{ $row->contact->name ?? '—' }}</td>
                    <td>
                        @if ($row->stage)
                        {{ $row->stage->name }}
                        <span class="text-muted small">({{ $row->stage->pipeline->name ?? '' }})</span>
                        @else
                        —
                        @endif
                    </td>
                    <td>{{ number_format($row->amount, 2) }}</td>
                    <td>{{ $row->owner->name ?? '—' }}</td>
                    <td>
                        @php
                        $statusClass = match ($row->status) {
                        'won' => 'text-bg-success-subtle text-success-emphasis',
                        'lost' => 'text-bg-danger-subtle text-danger-emphasis',
                        default => 'text-bg-light border',
                        };
                        @endphp
                        <span class="badge {{ $statusClass }}">{{ ucfirst($row->status) }}</span>
                    </td>
                    <td class="text-end no-export">
                        @php
                        $dealPayload = [
                        'id' => $row->id,
                        'title' => $row->title,
                        'contact_id' => $row->contact_id,
                        'pipeline_stage_id' => $row->pipeline_stage_id,
                        'amount' => $row->amount,
                        'owner_id' => $row->owner_id,
                        'status' => $row->status,
                        ];
                        @endphp
                        <button type="button"
                            class="btn btn-sm btn-outline-secondary btn-edit-deal"
                            data-deal='{{ json_encode($dealPayload) }}'>
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

<div class="modal fade" id="dealModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="dealForm" method="POST"
                action="{{ $editingId ? route('company.deals.update', ['company' => $company->slug, 'deal' => $editingId]) : route('company.deals.store', ['company' => $company->slug]) }}">
                @csrf
                <input type="hidden" name="_method" id="dealMethod" value="{{ $editingId ? 'PUT' : '' }}">
                <input type="hidden" name="deal_id" id="dealIdField" value="{{ $editingId }}">

                <div class="modal-header">
                    <h5 class="modal-title" id="dealModalTitle">{{ $editingId ? 'Edit deal' : 'Add deal' }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="dealTitle" class="form-label">Title</label>
                        <input type="text" class="form-control @error('title') is-invalid @enderror"
                            id="dealTitle" name="title" value="{{ old('title') }}" required>
                        @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label for="dealContact" class="form-label">Contact</label>
                        <select class="form-select @error('contact_id') is-invalid @enderror" id="dealContact" name="contact_id" required>
                            <option value="">&mdash; Select &mdash;</option>
                            @foreach ($contacts as $contactOption)
                            <option value="{{ $contactOption->id }}" @selected(old('contact_id')===$contactOption->id)>
                                {{ $contactOption->name }}
                            </option>
                            @endforeach
                        </select>
                        @error('contact_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        @if ($contacts->isEmpty())
                        <div class="form-text text-warning">No contacts yet — add one under Contacts first.</div>
                        @endif
                    </div>

                    <div class="mb-3">
                        <label for="dealStage" class="form-label">Stage</label>
                        <select class="form-select @error('pipeline_stage_id') is-invalid @enderror" id="dealStage" name="pipeline_stage_id" required>
                            <option value="">&mdash; Select &mdash;</option>
                            @foreach ($stages as $stageOption)
                            <option value="{{ $stageOption->id }}" @selected(old('pipeline_stage_id')===$stageOption->id)>
                                {{ $stageOption->pipeline->name }}: {{ $stageOption->name }}
                            </option>
                            @endforeach
                        </select>
                        @error('pipeline_stage_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        @if ($stages->isEmpty())
                        <div class="form-text text-warning">
                            No pipeline stages yet — <a href="{{ route('company.pipelines.index', ['company' => $company->slug]) }}">set up a pipeline first</a>.
                        </div>
                        @endif
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="dealAmount" class="form-label">Amount</label>
                            <input type="number" step="0.01" min="0" class="form-control @error('amount') is-invalid @enderror"
                                id="dealAmount" name="amount" value="{{ old('amount') }}" required>
                            @error('amount')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="dealStatus" class="form-label">Status</label>
                            <select class="form-select @error('status') is-invalid @enderror" id="dealStatus" name="status" required>
                                @foreach (['open' => 'Open', 'won' => 'Won', 'lost' => 'Lost'] as $value => $label)
                                <option value="{{ $value }}" @selected(old('status', 'open' )===$value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="mb-1">
                        <label for="dealOwner" class="form-label">Owner</label>
                        <select class="form-select @error('owner_id') is-invalid @enderror" id="dealOwner" name="owner_id">
                            <option value="">— Unassigned —</option>
                            @foreach ($staff as $staffOption)
                            <option value="{{ $staffOption->id }}" @selected(old('owner_id')===$staffOption->id)>
                                {{ $staffOption->name }}
                            </option>
                            @endforeach
                        </select>
                        @error('owner_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save deal</button>
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
        var printTitle = 'Deals - {{ $company->name }} - {{ date("d M, Y") }}';
        $('#dealsTable').DataTable({
            ordering: false,
            paging: false,
            info: false,
            responsive: true,
            columnDefs: [{
                targets: [0],
                orderable: false
            }],
            language: {
                emptyTable: "No deals yet"
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

        var $modalEl = $('#dealModal');
        var modal = new bootstrap.Modal($modalEl[0]);
        var $form = $('#dealForm');
        var storeUrl = "{{ route('company.deals.store', ['company' => $company->slug]) }}";
        var updateUrlTemplate = "{{ route('company.deals.update', ['company' => $company->slug, 'deal' => '__ID__']) }}";

        function openCreateModal() {
            $form[0].reset();
            $form.attr('action', storeUrl);
            $('#dealMethod').val('');
            $('#dealIdField').val('');
            $('#dealModalTitle').text('Add deal');
            modal.show();
        }

        function openEditModal(deal) {
            $form.attr('action', updateUrlTemplate.replace('__ID__', deal.id));
            $('#dealMethod').val('PUT');
            $('#dealIdField').val(deal.id);
            $('#dealTitle').val(deal.title);
            $('#dealContact').val(deal.contact_id);
            $('#dealStage').val(deal.pipeline_stage_id);
            $('#dealAmount').val(deal.amount);
            $('#dealStatus').val(deal.status);
            $('#dealOwner').val(deal.owner_id || '');
            $('#dealModalTitle').text('Edit ' + deal.title);
            modal.show();
        }

        $('#addDealBtn').on('click', openCreateModal);
        $('#dealsTable tbody').on('click', '.btn-edit-deal', function() {
            openEditModal($(this).data('deal'));
        });

        @if($errors -> any())
        modal.show();
        @endif
    });
</script>
@endpush
