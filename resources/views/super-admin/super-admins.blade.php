@extends('layouts.app')

@section('title', 'Super Admins')



@php
$editingId = old('admin_id');
@endphp

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h4 mb-1">Super Admins</h1>
        <p class="text-muted small mb-0">Full cross-company access — grant this sparingly.</p>
    </div>
    <button type="button" class="btn btn-primary" id="addAdminBtn">
        <i class="bi bi-plus-lg me-1"></i> Add super admin
    </button>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <table class="table table-hover align-middle w-100" id="superAdminsTable">
            <thead>
                <tr>
                    <th>S/N</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Status</th>
                    <th>Created</th>
                    <th class="text-end no-export">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($superAdmins as $row)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td class="fw-semibold">
                        {{ $row->name }}
                        @if ($row->id === auth()->id())
                        <span class="badge text-bg-light border ms-1">You</span>
                        @endif
                    </td>
                    <td>{{ $row->email }}</td>
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
                            $adminPayload = [
                            'id' => $row->id,
                            'name' => $row->name,
                            'email' => $row->email,
                            ];
                            @endphp
                            <button type="button"
                                class="btn btn-sm btn-outline-secondary btn-edit-admin"
                                data-admin='{{ json_encode($adminPayload) }}'>
                                <i class="bi bi-pencil"></i> Edit
                            </button>

                            @if ($row->id !== auth()->id())
                            @if ($row->is_active)
                            <form method="POST" action="{{ route('super-admin.super-admins.destroy', $row) }}" class="d-inline form-deactivate-admin">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                    <i class="bi bi-slash-circle"></i> Deactivate
                                </button>
                            </form>
                            @else
                            <form method="POST" action="{{ route('super-admin.super-admins.activate', $row) }}" class="d-inline form-activate-admin">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="btn btn-sm btn-outline-success">
                                    <i class="bi bi-check-circle"></i> Activate
                                </button>
                            </form>
                            @endif
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

<div class="modal fade" id="adminModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="adminForm" method="POST"
                action="{{ $editingId ? route('super-admin.super-admins.update', $editingId) : route('super-admin.super-admins.store') }}">
                @csrf
                <input type="hidden" name="_method" id="adminMethod" value="{{ $editingId ? 'PUT' : '' }}">
                <input type="hidden" name="admin_id" id="adminIdField" value="{{ $editingId }}">

                <div class="modal-header">
                    <h5 class="modal-title" id="adminModalTitle">{{ $editingId ? 'Edit super admin' : 'Add super admin' }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="adminName" class="form-label">Full name</label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror"
                            id="adminName" name="name" value="{{ old('name') }}" required>
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label for="adminEmail" class="form-label">Email</label>
                        <input type="email" class="form-control @error('email') is-invalid @enderror"
                            id="adminEmail" name="email" value="{{ old('email') }}" required>
                        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="adminPassword" class="form-label">Password</label>
                            <input type="password" class="form-control @error('password') is-invalid @enderror"
                                id="adminPassword" name="password" autocomplete="new-password">
                            <div class="form-text" id="adminPasswordHelp">Leave blank to keep the current password.</div>
                            @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="adminPasswordConfirm" class="form-label">Confirm password</label>
                            <input type="password" class="form-control"
                                id="adminPasswordConfirm" name="password_confirmation" autocomplete="new-password">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save super admin</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')


<script>
    $(function() {
        var columnPrint = [0, 1, 2, 3]; // S/N, Name, Email, Status
        var printTitle = 'Super Admins - {{ date("d M, Y") }}';
        $('#superAdminsTable').DataTable({
            ordering: false,
            paging: false,
            info: false,
            responsive: true,
            columnDefs: [{
                targets: [0],
                orderable: false
            }],
            language: {
                emptyTable: "No super admins yet"
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

        var $modalEl = $('#adminModal');
        var modal = new bootstrap.Modal($modalEl[0]);
        var $form = $('#adminForm');
        var storeUrl = "{{ route('super-admin.super-admins.store') }}";
        var updateUrlTemplate = "{{ route('super-admin.super-admins.update', ['admin' => '__ID__']) }}";

        function openCreateModal() {
            $form[0].reset();
            $form.attr('action', storeUrl);
            $('#adminMethod').val('');
            $('#adminIdField').val('');
            $('#adminModalTitle').text('Add super admin');
            $('#adminPassword').prop('required', true);
            $('#adminPasswordHelp').text('Required for a new account.');
            modal.show();
        }

        function openEditModal(admin) {
            $form.attr('action', updateUrlTemplate.replace('__ID__', admin.id));
            $('#adminMethod').val('PUT');
            $('#adminIdField').val(admin.id);
            $('#adminName').val(admin.name);
            $('#adminEmail').val(admin.email);
            $('#adminPassword').val('').prop('required', false);
            $('#adminPasswordConfirm').val('');
            $('#adminPasswordHelp').text('Leave blank to keep the current password.');
            $('#adminModalTitle').text('Edit ' + admin.name);
            modal.show();
        }

        $('#addAdminBtn').on('click', openCreateModal);
        $('#superAdminsTable tbody').on('click', '.btn-edit-admin', function() {
            openEditModal($(this).data('admin'));
        });

        $('.form-deactivate-admin').on('submit', function(e) {
            e.preventDefault();
            var $form = $(this);
            if (window.alertify) {
                alertify.confirm('Deactivate super admin', 'They will lose access immediately. Continue?',
                    function() {
                        $form.off('submit').trigger('submit');
                    },
                    function() {});
            } else {
                $form.off('submit').trigger('submit');
            }
        });

        $('.form-activate-admin').on('submit', function(e) {
            e.preventDefault();
            var $form = $(this);
            if (window.alertify) {
                alertify.confirm('Activate super admin', 'They will regain access. Continue?',
                    function() {
                        $form.off('submit').trigger('submit');
                    },
                    function() {});
            } else {
                $form.off('submit').trigger('submit');
            }
        });

        @if($errors -> any())
        @if($editingId)
        $('#adminPassword').prop('required', false);
        $('#adminPasswordHelp').text('Leave blank to keep the current password.');
        @endif
        modal.show();
        @endif
    });
</script>
@endpush
