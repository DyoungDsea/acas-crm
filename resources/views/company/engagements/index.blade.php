@extends('layouts.app')

@section('title', 'Engagements')

@push('styles')
<link href="{{ asset('assets/DataTables/datatables.min.css') }}" rel="stylesheet">
<link href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css" rel="stylesheet">
@endpush

@php
$editingId = old('engagement_id');
@endphp

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h4 mb-1">Engagements</h1>
        <p class="text-muted small mb-0">Advisory, investment, and management work for {{ $company->name }}.</p>
    </div>
    <button type="button" class="btn btn-primary" id="addEngagementBtn" @disabled($contacts->isEmpty())>
        <i class="bi bi-plus-lg me-1"></i> Add engagement
    </button>
</div>

@if ($contacts->isEmpty())
    <div class="alert alert-warning">
        No contacts yet — add one under
        <a href="{{ route('company.contacts.index', ['company' => $company->slug]) }}">Contacts</a> first.
    </div>
@endif

<div class="card border-0 shadow-sm">
    <div class="card-body">
        {{-- No delete action on purpose — an engagement is a record
             of advisory work performed. Closing one out means setting
             status to Completed/Cancelled, not removing it. Loop
             variable is $row, not $company. --}}
        <table class="table table-hover align-middle w-100" id="engagementsTable">
            <thead>
                <tr>
                    <th>S/N</th>
                    <th>Title</th>
                    <th>Contact</th>
                    <th>Type</th>
                    <th>Lead advisor</th>
                    <th>Start</th>
                    <th>End</th>
                    <th>Status</th>
                    <th class="text-end no-export">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($engagements as $row)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td class="fw-semibold">{{ $row->title }}</td>
                    <td>{{ $row->contact->name ?? '—' }}</td>
                    <td>{{ ucfirst($row->type) }}</td>
                    <td>{{ $row->leadAdvisor->name ?? '—' }}</td>
                    <td>{{ $row->start_date->format('M j, Y') }}</td>
                    <td>{{ $row->end_date?->format('M j, Y') ?? '—' }}</td>
                    <td>
                        @php
                            $statusClass = match ($row->status) {
                                'active' => 'text-bg-success-subtle text-success-emphasis',
                                'completed' => 'text-bg-info-subtle text-info-emphasis',
                                'cancelled' => 'text-bg-danger-subtle text-danger-emphasis',
                                default => 'text-bg-light border',
                            };
                        @endphp
                        <span class="badge {{ $statusClass }}">{{ ucfirst($row->status) }}</span>
                    </td>
                    <td class="text-end no-export">
                        @php
                            $engagementPayload = [
                                'id' => $row->id,
                                'contact_id' => $row->contact_id,
                                'title' => $row->title,
                                'type' => $row->type,
                                'scope' => $row->scope,
                                'status' => $row->status,
                                'start_date' => $row->start_date->format('Y-m-d'),
                                'end_date' => $row->end_date?->format('Y-m-d'),
                                'lead_advisor' => $row->lead_advisor,
                            ];
                        @endphp
                        <button type="button"
                            class="btn btn-sm btn-outline-secondary btn-edit-engagement"
                            data-engagement='{{ json_encode($engagementPayload) }}'>
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

<div class="modal fade" id="engagementModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="engagementForm" method="POST"
                action="{{ $editingId ? route('company.engagements.update', ['company' => $company->slug, 'engagement' => $editingId]) : route('company.engagements.store', ['company' => $company->slug]) }}">
                @csrf
                <input type="hidden" name="_method" id="engagementMethod" value="{{ $editingId ? 'PUT' : '' }}">
                <input type="hidden" name="engagement_id" id="engagementIdField" value="{{ $editingId }}">

                <div class="modal-header">
                    <h5 class="modal-title" id="engagementModalTitle">{{ $editingId ? 'Edit engagement' : 'Add engagement' }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="engagementTitle" class="form-label">Title</label>
                        <input type="text" class="form-control @error('title') is-invalid @enderror"
                            id="engagementTitle" name="title" value="{{ old('title') }}" placeholder="e.g. 2026 rice season advisory" required>
                        @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="engagementContact" class="form-label">Contact</label>
                            <select class="form-select @error('contact_id') is-invalid @enderror" id="engagementContact" name="contact_id" required>
                                <option value="">&mdash; Select &mdash;</option>
                                @foreach ($contacts as $contactOption)
                                    <option value="{{ $contactOption->id }}" @selected(old('contact_id') === $contactOption->id)>
                                        {{ $contactOption->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('contact_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="engagementType" class="form-label">Type</label>
                            <select class="form-select @error('type') is-invalid @enderror" id="engagementType" name="type" required>
                                <option value="">&mdash; Select &mdash;</option>
                                @foreach (['investment' => 'Investment', 'management' => 'Management', 'consultancy' => 'Consultancy'] as $value => $label)
                                    <option value="{{ $value }}" @selected(old('type') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="engagementScope" class="form-label">Scope <span class="text-muted fw-normal">(optional)</span></label>
                        <textarea class="form-control @error('scope') is-invalid @enderror"
                            id="engagementScope" name="scope" rows="3" placeholder="What this engagement covers">{{ old('scope') }}</textarea>
                        @error('scope')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="engagementLeadAdvisor" class="form-label">Lead advisor <span class="text-muted fw-normal">(optional)</span></label>
                            <select class="form-select @error('lead_advisor') is-invalid @enderror" id="engagementLeadAdvisor" name="lead_advisor">
                                <option value="">— Unassigned —</option>
                                @foreach ($advisors as $advisorOption)
                                    <option value="{{ $advisorOption->id }}" @selected(old('lead_advisor') === $advisorOption->id)>
                                        {{ $advisorOption->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('lead_advisor')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="engagementStatus" class="form-label">Status</label>
                            <select class="form-select @error('status') is-invalid @enderror" id="engagementStatus" name="status" required>
                                @foreach (['active' => 'Active', 'completed' => 'Completed', 'cancelled' => 'Cancelled'] as $value => $label)
                                    <option value="{{ $value }}" @selected(old('status') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-1">
                            <label for="engagementStartDate" class="form-label">Start date</label>
                            <input type="date" class="form-control @error('start_date') is-invalid @enderror"
                                id="engagementStartDate" name="start_date" value="{{ old('start_date') }}" required>
                            @error('start_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6 mb-1">
                            <label for="engagementEndDate" class="form-label">End date <span class="text-muted fw-normal">(optional)</span></label>
                            <input type="date" class="form-control @error('end_date') is-invalid @enderror"
                                id="engagementEndDate" name="end_date" value="{{ old('end_date') }}">
                            @error('end_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save engagement</button>
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
        var printTitle = 'Engagements - {{ $company->name }} - {{ date("d M, Y") }}';
        $('#engagementsTable').DataTable({
            ordering: false,
            paging: false,
            info: false,
            responsive: true,
            columnDefs: [{ targets: [0], orderable: false }],
            language: { emptyTable: "No engagements yet" },
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

        var $modalEl = $('#engagementModal');
        var modal = new bootstrap.Modal($modalEl[0]);
        var $form = $('#engagementForm');
        var storeUrl = "{{ route('company.engagements.store', ['company' => $company->slug]) }}";
        var updateUrlTemplate = "{{ route('company.engagements.update', ['company' => $company->slug, 'engagement' => '__ID__']) }}";

        function openCreateModal() {
            $form[0].reset();
            $form.attr('action', storeUrl);
            $('#engagementMethod').val('');
            $('#engagementIdField').val('');
            $('#engagementModalTitle').text('Add engagement');
            modal.show();
        }

        function openEditModal(engagement) {
            $form.attr('action', updateUrlTemplate.replace('__ID__', engagement.id));
            $('#engagementMethod').val('PUT');
            $('#engagementIdField').val(engagement.id);
            $('#engagementTitle').val(engagement.title);
            $('#engagementContact').val(engagement.contact_id);
            $('#engagementType').val(engagement.type);
            $('#engagementScope').val(engagement.scope || '');
            $('#engagementLeadAdvisor').val(engagement.lead_advisor || '');
            $('#engagementStatus').val(engagement.status);
            $('#engagementStartDate').val(engagement.start_date);
            $('#engagementEndDate').val(engagement.end_date || '');
            $('#engagementModalTitle').text('Edit ' + engagement.title);
            modal.show();
        }

        $('#addEngagementBtn').on('click', openCreateModal);
        $('#engagementsTable tbody').on('click', '.btn-edit-engagement', function() {
            openEditModal($(this).data('engagement'));
        });

        @if ($errors->any())
            modal.show();
        @endif
    });
</script>
@endpush
