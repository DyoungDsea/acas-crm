@extends('layouts.app')

@section('title', 'Product Categories')

 

@php
$editingId = old('category_id');
@endphp

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h4 mb-1">Product Categories</h1>
        <p class="text-muted small mb-0">Grouping for {{ $company->name }}'s product list — picked from a list, not typed by hand.</p>
    </div>
    <button type="button" class="btn btn-primary" id="addCategoryBtn">
        <i class="bi bi-plus-lg me-1"></i> Add category
    </button>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        {{-- Loop variable is $row, not $company. Deleting a category
             is a real delete here (see the controller comment) — it's
             an organizational label, not a financial record, and its
             products just become uncategorized rather than anything
             breaking. --}}
        <table class="table table-hover align-middle w-100" id="categoriesTable">
            <thead>
                <tr>
                    <th>S/N</th>
                    <th>Name</th>
                    <th>Products</th>
                    <th class="text-end no-export">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($categories as $row)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td class="fw-semibold">{{ $row->name }}</td>
                    <td>{{ $row->products_count }}</td>
                    <td class="text-end no-export">
                        <div class="d-inline-flex gap-1">
                            @php
                                $categoryPayload = ['id' => $row->id, 'name' => $row->name];
                            @endphp
                            <button type="button"
                                class="btn btn-sm btn-outline-secondary btn-edit-category"
                                data-category='{{ json_encode($categoryPayload) }}'>
                                <i class="bi bi-pencil"></i> Edit
                            </button>
                            <form method="POST" action="{{ route('company.product-categories.destroy', ['company' => $company->slug, 'product_category' => $row->id]) }}" class="d-inline form-delete-category">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger" data-count="{{ $row->products_count }}">
                                    <i class="bi bi-trash"></i> Delete
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="categoryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="categoryForm" method="POST"
                action="{{ $editingId ? route('company.product-categories.update', ['company' => $company->slug, 'product_category' => $editingId]) : route('company.product-categories.store', ['company' => $company->slug]) }}">
                @csrf
                <input type="hidden" name="_method" id="categoryMethod" value="{{ $editingId ? 'PUT' : '' }}">
                <input type="hidden" name="category_id" id="categoryIdField" value="{{ $editingId }}">

                <div class="modal-header">
                    <h5 class="modal-title" id="categoryModalTitle">{{ $editingId ? 'Edit category' : 'Add category' }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-1">
                        <label for="categoryName" class="form-label">Name</label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror"
                            id="categoryName" name="name" value="{{ old('name') }}" required autofocus>
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save category</button>
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
        $('#categoriesTable').DataTable({
            ordering: false,
            paging: false,
            info: false,
            responsive: true,
            columnDefs: [{ targets: [0], orderable: false }],
            language: { emptyTable: "No categories yet" },
            dom: "<'row mb-3'<'col-12 d-flex justify-content-end'f>><'row'<'col-12'tr>>",
        });

        var $modalEl = $('#categoryModal');
        var modal = new bootstrap.Modal($modalEl[0]);
        var $form = $('#categoryForm');
        var storeUrl = "{{ route('company.product-categories.store', ['company' => $company->slug]) }}";
        var updateUrlTemplate = "{{ route('company.product-categories.update', ['company' => $company->slug, 'product_category' => '__ID__']) }}";

        function openCreateModal() {
            $form[0].reset();
            $form.attr('action', storeUrl);
            $('#categoryMethod').val('');
            $('#categoryIdField').val('');
            $('#categoryModalTitle').text('Add category');
            modal.show();
        }

        function openEditModal(category) {
            $form.attr('action', updateUrlTemplate.replace('__ID__', category.id));
            $('#categoryMethod').val('PUT');
            $('#categoryIdField').val(category.id);
            $('#categoryName').val(category.name);
            $('#categoryModalTitle').text('Edit ' + category.name);
            modal.show();
        }

        $('#addCategoryBtn').on('click', openCreateModal);
        $('#categoriesTable tbody').on('click', '.btn-edit-category', function() {
            openEditModal($(this).data('category'));
        });

        $('.form-delete-category').on('submit', function(e) {
            e.preventDefault();
            var $form = $(this);
            var count = $form.find('button[type="submit"]').data('count');
            var message = count > 0
                ? 'This will leave ' + count + ' product(s) uncategorized. Continue?'
                : 'Delete this category?';

            if (window.alertify) {
                alertify.confirm('Delete category', message,
                    function() { $form.off('submit').trigger('submit'); }, function() {});
            } else { $form.off('submit').trigger('submit'); }
        });

        @if ($errors->any())
            modal.show();
        @endif
    });
</script>
@endpush
