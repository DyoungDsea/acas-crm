@extends('layouts.app')

@section('title', 'Staff')


@php
$editingId = old('staff_id');
@endphp

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h4 mb-1">Staff</h1>
        <p class="text-muted small mb-0">Everyone with a login at {{ $company->name }}.</p>
    </div>
    <button type="button" class="btn btn-primary" id="addStaffBtn">
        <i class="bi bi-plus-lg me-1"></i> Add staff
    </button>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        {{-- Loop variable is $row, not $company — this view extends
             layouts.app, which shares PHP scope with it. --}}
        <table class="table table-hover align-middle w-100" id="staffTable">
            <thead>
                <tr>
                    <th>S/N</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Assigned contacts</th>
                    <th>Status</th>
                    <th>Created</th>
                    <th class="text-end no-export">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($staff as $row)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td class="fw-semibold">
                        {{ $row->name }}
                        @if ($row->id === auth()->id())
                            <span class="badge text-bg-light border ms-1">You</span>
                        @endif
                    </td>
                    <td>{{ $row->email }}</td>
                    <td>{{ $row->assigned_contacts_count }}</td>
                    <td>
                        @if ($row->is_active)
                            <span class="badge text-bg-success-subtle text-success-emphasis">Active</span>
                        @else
                            <span class="badge text-bg-secondary-subtle text-secondary-emphasis">Inactive</span>
                        @endif
                    </td>
                    <td>{{ $row->created_at->format('M j, Y') }}</td>
                    <td class="text-end no-export">
                        <div class="d-inline-flex gap-1">
                            @php
                                $staffPayload = ['id' => $row->id, 'name' => $row->name, 'email' => $row->email];
                            @endphp
                            <button type="button"
                                class="btn btn-sm btn-outline-secondary btn-edit-staff"
                                data-staff='{{ json_encode($staffPayload) }}'>
                                <i class="bi bi-pencil"></i> Edit
                            </button>

                            @if ($row->is_active)
                                @if ($row->assigned_contacts_count > 0)
                                    {{-- Has contacts assigned — deactivating requires
                                         picking someone to take them over first. This
                                         button opens the shared reassignment modal
                                         below instead of submitting directly. --}}
                                    @php
                                        $colleagues = \App\Models\User::where('company_id', $company->id)
                                            ->where('is_active', true)
                                            ->where('id', '!=', $row->id)
                                            ->orderBy('name')
                                            ->get(['id', 'name']);
                                    @endphp
                                    <button type="button" class="btn btn-sm btn-outline-danger btn-needs-reassign"
                                        data-name="{{ $row->name }}"
                                        data-count="{{ $row->assigned_contacts_count }}"
                                        data-url="{{ route('company.staff.destroy', ['company' => $company->slug, 'staff' => $row->id]) }}"
                                        data-colleagues='{{ $colleagues->toJson() }}'>
                                        <i class="bi bi-slash-circle"></i> Deactivate
                                    </button>
                                @else
                                    <form method="POST" action="{{ route('company.staff.destroy', ['company' => $company->slug, 'staff' => $row->id]) }}" class="d-inline form-deactivate-staff">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                            <i class="bi bi-slash-circle"></i> Deactivate
                                        </button>
                                    </form>
                                @endif
                            @else
                                <form method="POST" action="{{ route('company.staff.activate', ['company' => $company->slug, 'staff' => $row->id]) }}" class="d-inline form-activate-staff">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="btn btn-sm btn-outline-success">
                                        <i class="bi bi-check-circle"></i> Activate
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

{{-- Create/edit modal --}}
<div class="modal fade" id="staffModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="staffForm" method="POST"
                action="{{ $editingId ? route('company.staff.update', ['company' => $company->slug, 'staff' => $editingId]) : route('company.staff.store', ['company' => $company->slug]) }}">
                @csrf
                <input type="hidden" name="_method" id="staffMethod" value="{{ $editingId ? 'PUT' : '' }}">
                <input type="hidden" name="staff_id" id="staffIdField" value="{{ $editingId }}">

                <div class="modal-header">
                    <h5 class="modal-title" id="staffModalTitle">{{ $editingId ? 'Edit staff' : 'Add staff' }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="staffName" class="form-label">Full name</label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror"
                            id="staffName" name="name" value="{{ old('name') }}" required>
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label for="staffEmail" class="form-label">Email</label>
                        <input type="email" class="form-control @error('email') is-invalid @enderror"
                            id="staffEmail" name="email" value="{{ old('email') }}" required>
                        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="staffPassword" class="form-label">Password</label>
                            <input type="password" class="form-control @error('password') is-invalid @enderror"
                                id="staffPassword" name="password" autocomplete="new-password">
                            <div class="form-text" id="staffPasswordHelp">Leave blank to keep the current password.</div>
                            @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="staffPasswordConfirm" class="form-label">Confirm password</label>
                            <input type="password" class="form-control"
                                id="staffPasswordConfirm" name="password_confirmation" autocomplete="new-password">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save staff</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Reassign-then-deactivate modal — shared by every row that has
     assigned contacts. Populated dynamically per row by JS below,
     since each row's list of valid reassignment targets (their
     active colleagues) differs. --}}
<div class="modal fade" id="reassignModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="reassignForm" method="POST">
                @csrf
                @method('DELETE')
                <div class="modal-header">
                    <h5 class="modal-title" id="reassignModalTitle">Reassign contacts</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p id="reassignModalBody" class="text-muted small"></p>
                    <label for="reassignTarget" class="form-label">Reassign their contacts to</label>
                    <select class="form-select" id="reassignTarget" name="reassign_to" required></select>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Reassign &amp; deactivate</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')

 
<script>
    $(function() {
        var columnPrint = [0, 1, 2, 3, 4];
        var printTitle = 'Staff - {{ $company->name }} - {{ date("d M, Y") }}';
        $('#staffTable').DataTable({
            ordering: false,
            paging: false,
            info: false,
            responsive: true,
            columnDefs: [{ targets: [0], orderable: false }],
            language: { emptyTable: "No staff yet" },
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

        // ---- Create / edit ----
        var $modalEl = $('#staffModal');
        var modal = new bootstrap.Modal($modalEl[0]);
        var $form = $('#staffForm');
        var storeUrl = "{{ route('company.staff.store', ['company' => $company->slug]) }}";
        var updateUrlTemplate = "{{ route('company.staff.update', ['company' => $company->slug, 'staff' => '__ID__']) }}";

        function openCreateModal() {
            $form[0].reset();
            $form.attr('action', storeUrl);
            $('#staffMethod').val('');
            $('#staffIdField').val('');
            $('#staffModalTitle').text('Add staff');
            $('#staffPassword').prop('required', true);
            $('#staffPasswordHelp').text('Required for a new account.');
            modal.show();
        }

        function openEditModal(staff) {
            $form.attr('action', updateUrlTemplate.replace('__ID__', staff.id));
            $('#staffMethod').val('PUT');
            $('#staffIdField').val(staff.id);
            $('#staffName').val(staff.name);
            $('#staffEmail').val(staff.email);
            $('#staffPassword').val('').prop('required', false);
            $('#staffPasswordConfirm').val('');
            $('#staffPasswordHelp').text('Leave blank to keep the current password.');
            $('#staffModalTitle').text('Edit ' + staff.name);
            modal.show();
        }

        $('#addStaffBtn').on('click', openCreateModal);
        $('#staffTable tbody').on('click', '.btn-edit-staff', function() {
            openEditModal($(this).data('staff'));
        });

        // ---- Deactivate: no contacts assigned — plain confirm ----
        $('.form-deactivate-staff').on('submit', function(e) {
            e.preventDefault();
            var $form = $(this);
            if (window.alertify) {
                alertify.confirm('Deactivate staff', 'They will lose access immediately. Continue?',
                    function() { $form.off('submit').trigger('submit'); }, function() {});
            } else { $form.off('submit').trigger('submit'); }
        });

        $('.form-activate-staff').on('submit', function(e) {
            e.preventDefault();
            var $form = $(this);
            if (window.alertify) {
                alertify.confirm('Activate staff', 'They will regain access. Continue?',
                    function() { $form.off('submit').trigger('submit'); }, function() {});
            } else { $form.off('submit').trigger('submit'); }
        });

        // ---- Deactivate: HAS contacts assigned — reassignment required ----
        var $reassignModalEl = $('#reassignModal');
        var reassignModal = new bootstrap.Modal($reassignModalEl[0]);

        $('#staffTable tbody').on('click', '.btn-needs-reassign', function() {
            var name = $(this).data('name');
            var count = $(this).data('count');
            var url = $(this).data('url');
            var colleagues = $(this).data('colleagues');

            $('#reassignForm').attr('action', url);
            $('#reassignModalTitle').text('Reassign ' + name + '\u2019s contacts');
            $('#reassignModalBody').text(
                name + ' has ' + count + ' contact' + (count == 1 ? '' : 's') +
                ' assigned. Choose who should take ' + (count == 1 ? 'it' : 'them') + ' over before deactivating.'
            );

            var $select = $('#reassignTarget').empty();
            if (colleagues && colleagues.length) {
                colleagues.forEach(function(c) {
                    $select.append($('<option>').val(c.id).text(c.name));
                });
            } else {
                $select.append($('<option>').val('').text('No one else active at this company'));
            }

            reassignModal.show();
        });

        @if ($errors->any())
            modal.show();
        @endif
    });
</script>
@endpush
