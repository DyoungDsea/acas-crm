@extends('layouts.app')

@section('title', 'Contacts')


@php
$editingId = old('contact_id');
@endphp

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h4 mb-1">Contacts</h1>
        <p class="text-muted small mb-0">Leads, customers, vendors, and partners for {{ $company->name }}.</p>
    </div>
    <div class="d-flex gap-2">
        @if ($showTrashed)
            <a href="{{ route('company.contacts.index', ['company' => $company->slug]) }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Back to contacts
            </a>
        @else
            <a href="{{ route('company.contacts.index', ['company' => $company->slug, 'trashed' => 1]) }}" class="btn btn-outline-secondary">
                <i class="bi bi-trash me-1"></i> View deleted
            </a>
            <button type="button" class="btn btn-primary" id="addContactBtn">
                <i class="bi bi-plus-lg me-1"></i> Add contact
            </button>
        @endif
    </div>
</div>

@if ($showTrashed)
    <div class="alert alert-secondary">
        Showing deleted contacts. Restoring one moves it back to your regular list.
    </div>
@endif

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <table class="table table-hover align-middle w-100" id="contactsTable">
            <thead>
                <tr>
                    <th>S/N</th>
                    <th>Type</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Assigned to</th>
                    <th class="text-end no-export">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($contacts as $row)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td><span class="badge text-bg-light border">{{ ucfirst($row->type) }}</span></td>
                    <td class="fw-semibold">{{ $row->name }}</td>
                    <td>{{ $row->email ?: '—' }}</td>
                    <td>{{ $row->phone ?: '—' }}</td>
                    <td>{{ $row->assignedUser->name ?? '—' }}</td>
                    <td class="text-end no-export">
                        <div class="d-inline-flex gap-1">
                            @if ($showTrashed)
                                <form method="POST" action="{{ route('company.contacts.restore', ['company' => $company->slug, 'contact' => $row->id]) }}" class="d-inline">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="btn btn-sm btn-outline-success">
                                        <i class="bi bi-arrow-counterclockwise"></i> Restore
                                    </button>
                                </form>
                            @else
                                @php
                                    $contactPayload = [
                                        'id' => $row->id,
                                        'type' => $row->type,
                                        'name' => $row->name,
                                        'email' => $row->email,
                                        'phone' => $row->phone,
                                        'address' => $row->address,
                                        'notes' => $row->notes,
                                        'assigned_to' => $row->assigned_to,
                                    ];
                                @endphp
                                <button type="button"
                                    class="btn btn-sm btn-outline-secondary btn-edit-contact"
                                    data-contact='{{ json_encode($contactPayload) }}'>
                                    <i class="bi bi-pencil"></i> Edit
                                </button>
                                <form method="POST" action="{{ route('company.contacts.destroy', ['company' => $company->slug, 'contact' => $row->id]) }}" class="d-inline form-delete-contact">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                        <i class="bi bi-trash"></i> Delete
                                    </button>
                                </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@unless ($showTrashed)
<div class="modal fade" id="contactModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="contactForm" method="POST"
                action="{{ $editingId ? route('company.contacts.update', ['company' => $company->slug, 'contact' => $editingId]) : route('company.contacts.store', ['company' => $company->slug]) }}">
                @csrf
                <input type="hidden" name="_method" id="contactMethod" value="{{ $editingId ? 'PUT' : '' }}">
                <input type="hidden" name="contact_id" id="contactIdField" value="{{ $editingId }}">

                <div class="modal-header">
                    <h5 class="modal-title" id="contactModalTitle">{{ $editingId ? 'Edit contact' : 'Add contact' }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="contactType" class="form-label">Type</label>
                        <select class="form-select @error('type') is-invalid @enderror" id="contactType" name="type" required>
                            @foreach (['lead' => 'Lead', 'customer' => 'Customer', 'vendor' => 'Vendor', 'partner' => 'Partner'] as $value => $label)
                                <option value="{{ $value }}" @selected(old('type') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label for="contactName" class="form-label">Name</label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror"
                            id="contactName" name="name" value="{{ old('name') }}" required>
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="contactEmail" class="form-label">Email</label>
                            <input type="email" class="form-control @error('email') is-invalid @enderror"
                                id="contactEmail" name="email" value="{{ old('email') }}">
                            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="contactPhone" class="form-label">Phone</label>
                            <input type="text" class="form-control @error('phone') is-invalid @enderror"
                                id="contactPhone" name="phone" value="{{ old('phone') }}">
                            @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="contactAddress" class="form-label">Address</label>
                        <input type="text" class="form-control @error('address') is-invalid @enderror"
                            id="contactAddress" name="address" value="{{ old('address') }}">
                        @error('address')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label for="contactAssignedTo" class="form-label">Assigned to</label>
                        <select class="form-select @error('assigned_to') is-invalid @enderror" id="contactAssignedTo" name="assigned_to">
                            <option value="">— Unassigned —</option>
                            @foreach ($staff as $staffOption)
                                <option value="{{ $staffOption->id }}" @selected(old('assigned_to') === $staffOption->id)>
                                    {{ $staffOption->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('assigned_to')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-1">
                        <label for="contactNotes" class="form-label">Notes</label>
                        <textarea class="form-control @error('notes') is-invalid @enderror"
                            id="contactNotes" name="notes" rows="3">{{ old('notes') }}</textarea>
                        @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save contact</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endunless
@endsection

@push('scripts')


<script>
    $(function() {
        var columnPrint = [0, 1, 2, 3, 4, 5];
        var printTitle = 'Contacts - {{ $company->name }} - {{ date("d M, Y") }}';
        $('#contactsTable').DataTable({
            ordering: false,
            paging: false,
            info: false,
            responsive: true,
            columnDefs: [{ targets: [0], orderable: false }],
            language: { emptyTable: "{{ $showTrashed ? 'No deleted contacts' : 'No contacts yet' }}" },
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

        @unless ($showTrashed)
        var $modalEl = $('#contactModal');
        var modal = new bootstrap.Modal($modalEl[0]);
        var $form = $('#contactForm');
        var storeUrl = "{{ route('company.contacts.store', ['company' => $company->slug]) }}";
        var updateUrlTemplate = "{{ route('company.contacts.update', ['company' => $company->slug, 'contact' => '__ID__']) }}";

        function openCreateModal() {
            $form[0].reset();
            $form.attr('action', storeUrl);
            $('#contactMethod').val('');
            $('#contactIdField').val('');
            $('#contactModalTitle').text('Add contact');
            modal.show();
        }

        function openEditModal(contact) {
            $form.attr('action', updateUrlTemplate.replace('__ID__', contact.id));
            $('#contactMethod').val('PUT');
            $('#contactIdField').val(contact.id);
            $('#contactType').val(contact.type);
            $('#contactName').val(contact.name);
            $('#contactEmail').val(contact.email);
            $('#contactPhone').val(contact.phone);
            $('#contactAddress').val(contact.address);
            $('#contactAssignedTo').val(contact.assigned_to || '');
            $('#contactNotes').val(contact.notes);
            $('#contactModalTitle').text('Edit ' + contact.name);
            modal.show();
        }

        $('#addContactBtn').on('click', openCreateModal);
        $('#contactsTable tbody').on('click', '.btn-edit-contact', function() {
            openEditModal($(this).data('contact'));
        });

        $('.form-delete-contact').on('submit', function(e) {
            e.preventDefault();
            var $form = $(this);
            if (window.alertify) {
                alertify.confirm('Delete contact', 'This can be undone from "View deleted" afterward. Continue?',
                    function() { $form.off('submit').trigger('submit'); }, function() {});
            } else { $form.off('submit').trigger('submit'); }
        });

        @if ($errors->any())
            modal.show();
        @endif
        @endunless
    });
</script>
@endpush
