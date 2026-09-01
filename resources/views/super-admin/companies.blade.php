@extends('layouts.app')

@section('title', 'Companies')

 

@php
// If a create/edit just failed validation, old('company_id') tells
// us whether to re-render the form pointed at the update route (and
// pre-open the modal) or the store route. Set once here so both the
// form tag and the modal title below agree with each other.
$editingId = old('company_id');
@endphp

@section('content')

<div class="row mb-3">
    <div class="col-md-6">
        <h1 class="h4 mb-1">Companies</h1>
        <p class="text-muted small mb-0">Every business unit under the group, in one list.</p>
    </div>
    <div class="col-md-6 text-end ">
        <button type="button" class="btn btn-primary" id="addCompanyBtn">
            <i class="bi bi-plus-lg me-1"></i> Add company
        </button>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">

        <div class="table-responsive">
            <table class="table table-hover align-middle w-100" id="companiesTable">
                <thead>
                    <tr>
                        <th>S/N</th>
                        <th>Name</th>
                        <th>Type</th>
                        <th>Slug</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th class="text-end no-export">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($companies as $row)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td class="fw-semibold">{{ $row->name }}</td>
                        <td>
                            @if ($row->type)
                            <span class="badge text-bg-light border">{{ ucfirst(str_replace('_', ' ', $row->type)) }}</span>
                            @else
                            <span class="text-muted">&mdash;</span>
                            @endif
                        </td>
                        <td><code>{{ $row->slug }}</code></td>
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
                                $companyPayload = $row->only(["id", "name", "slug", "type", "phone", "email", "address"]);
                                @endphp
                                <button type="button"
                                    class="btn btn-sm btn-outline-secondary btn-edit-company"
                                    data-company='{{ json_encode($companyPayload) }}'>
                                    <i class="bi bi-pencil"></i> Edit
                                </button>

                                @if ($row->is_active)
                                <form method="POST" action="{{ route('super-admin.companies.destroy', $row) }}" class="d-inline form-deactivate-company">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                        <i class="bi bi-slash-circle"></i> Deactivate
                                    </button>
                                </form>
                                @else
                                <form method="POST" action="{{ route('super-admin.companies.activate', $row) }}" class="d-inline form-activate-company">
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
</div>

{{-- One modal, reused for both create and edit — nothing about
     managing companies ever leaves this page. Real form submission
     (not AJAX): on validation failure Laravel redirects back with
     $errors + old() automatically, and the block below re-opens this
     same modal with those errors already showing. --}}
<div class="modal fade" id="companyModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="companyForm" method="POST"
                action="{{ $editingId ? route('super-admin.companies.update', $editingId) : route('super-admin.companies.store') }}">
                @csrf
                <input type="hidden" name="_method" id="companyMethod" value="{{ $editingId ? 'PUT' : '' }}">
                <input type="hidden" name="company_id" id="companyIdField" value="{{ $editingId }}">

                <div class="modal-header">
                    <h5 class="modal-title" id="companyModalTitle">{{ $editingId ? 'Edit company' : 'Add company' }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="companyName" class="form-label">Company name</label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror"
                            id="companyName" name="name" value="{{ old('name') }}" required>
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label for="companySlug" class="form-label">Slug</label>
                        <input type="text" class="form-control @error('slug') is-invalid @enderror"
                            id="companySlug" name="slug" value="{{ old('slug') }}" required>
                        <div class="form-text">Used in URLs, e.g. <code>/acas-pharmacy/...</code>. Auto-filled from the name — edit if needed.</div>
                        @error('slug')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label for="companyType" class="form-label">Type</label>
                        <select class="form-select @error('type') is-invalid @enderror" id="companyType" name="type">
                            <option value="">&mdash; Select &mdash;</option>
                            @foreach (['farms' => 'Farms', 'properties' => 'Properties', 'pharmacy' => 'Pharmacy', 'trading' => 'Trading', 'energy' => 'Energy', 'automotive' => 'Automotive', 'agric_services' => 'Agric Services'] as $value => $label)
                            <option value="{{ $value }}" @selected(old('type')===$value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <div class="form-text">Also determines the sidebar color once staff log in — a type outside this list falls back to the default green.</div>
                        @error('type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="companyPhone" class="form-label">Phone</label>
                            <input type="text" class="form-control @error('phone') is-invalid @enderror"
                                id="companyPhone" name="phone" value="{{ old('phone') }}">
                            @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="companyEmail" class="form-label">Email</label>
                            <input type="email" class="form-control @error('email') is-invalid @enderror"
                                id="companyEmail" name="email" value="{{ old('email') }}">
                            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="mb-1">
                        <label for="companyAddress" class="form-label">Address</label>
                        <textarea class="form-control @error('address') is-invalid @enderror"
                            id="companyAddress" name="address" rows="2">{{ old('address') }}</textarea>
                        @error('address')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save company</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')


<script>
    $(function() {
        // DataTables here only adds search/the export toolbar/responsive
        // column-collapsing on top of the rows Blade already rendered
        // above — it is never given a data source of its own.
        var columnPrint = [0, 1, 2, ]; //column to print
        var printTitle = 'Companies - {{ date("d M, Y") }}';
        $('#companiesTable').DataTable({
            ordering: false,
            paging: false,
            info: false,
            responsive: true,
            columnDefs: [{
                targets: [0],
                orderable: false
            }],
            language: {
                emptyTable: "No data found"
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

        var $modalEl = $('#companyModal');
        var modal = new bootstrap.Modal($modalEl[0]);
        var $form = $('#companyForm');
        var storeUrl = "{{ route('super-admin.companies.store') }}";
        var updateUrlTemplate = "{{ route('super-admin.companies.update', ['company' => '__ID__']) }}";

        // ---- Slug auto-fill from name, until the user edits slug directly ----
        var slugTouched = {{ $editingId ? 'true' : 'false' }};
        $('#companySlug').on('input', function() {
            slugTouched = true;
        });
        $('#companyName').on('input', function() {
            if (slugTouched) return;
            var slug = $(this).val()
                .toLowerCase()
                .trim()
                .replace(/[^a-z0-9]+/g, '-')
                .replace(/(^-|-$)/g, '');
            $('#companySlug').val(slug);
        });

        function openCreateModal() {
            $form[0].reset();
            $form.attr('action', storeUrl);
            $('#companyMethod').val('');
            $('#companyIdField').val('');
            $('#companyModalTitle').text('Add company');
            slugTouched = false;
            modal.show();
        }

        function openEditModal(company) {
            $form.attr('action', updateUrlTemplate.replace('__ID__', company.id));
            $('#companyMethod').val('PUT');
            $('#companyIdField').val(company.id);
            $('#companyName').val(company.name);
            $('#companySlug').val(company.slug);
            $('#companyType').val(company.type || '');
            $('#companyPhone').val(company.phone);
            $('#companyEmail').val(company.email);
            $('#companyAddress').val(company.address);
            $('#companyModalTitle').text('Edit ' + company.name);
            slugTouched = true; // don't overwrite an existing slug while editing
            modal.show();
        }

        $('#addCompanyBtn').on('click', openCreateModal);

        $('#companiesTable tbody').on('click', '.btn-edit-company', function() {
            openEditModal($(this).data('company'));
        });

        // Deactivating changes access for everyone at that company —
        // confirm via AlertifyJS before the (real, non-AJAX) form submits.
        $('.form-deactivate-company').on('submit', function(e) {
            e.preventDefault();
            var $form = $(this);
            if (window.alertify) {
                alertify.confirm(
                    'Deactivate company',
                    'Staff at this company will lose access immediately. Continue?',
                    function() {
                        $form.off('submit').trigger('submit');
                    },
                    function() {}
                );
            } else {
                $form.off('submit').trigger('submit');
            }
        });

        // Reactivating restores access — lower-stakes than deactivating,
        // but still a real permission change worth a confirm, not a
        // silent one-click action.
        $('.form-activate-company').on('submit', function(e) {
            e.preventDefault();
            var $form = $(this);
            if (window.alertify) {
                alertify.confirm(
                    'Activate company',
                    'Staff at this company will regain access. Continue?',
                    function() {
                        $form.off('submit').trigger('submit');
                    },
                    function() {}
                );
            } else {
                $form.off('submit').trigger('submit');
            }
        });

        @if($errors -> any())
        // A create/edit just failed validation — the form above was
        // already re-rendered pointed at the right action with old()
        // values and error messages, so this just needs to reopen it.
        modal.show();
        @endif
    });
</script>
@endpush
