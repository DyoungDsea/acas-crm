@extends('layouts.app')

@section('title', 'Products')

 

@php
$editingId = old('product_id');
@endphp

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h4 mb-1">Products</h1>
        <p class="text-muted small mb-0">Everything {{ $company->name }} sells or stocks.</p>
    </div>
    <button type="button" class="btn btn-primary" id="addProductBtn">
        <i class="bi bi-plus-lg me-1"></i> Add product
    </button>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        {{-- Loop variable is $row, not $company. --}}
        <table class="table table-hover align-middle w-100" id="productsTable">
            <thead>
                <tr>
                    <th>S/N</th>
                    <th>Name</th>
                    <th>Category</th>
                    <th>SKU</th>
                    <th>Unit price</th>
                    <th>Status</th>
                    <th class="text-end no-export">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($products as $row)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td class="fw-semibold">{{ $row->name }}</td>
                    <td>
                        @if ($row->category)
                            <span class="badge text-bg-light border">{{ $row->category->name }}</span>
                        @else
                            <span class="text-muted">Uncategorized</span>
                        @endif
                    </td>
                    <td>{{ $row->sku ?: '—' }}</td>
                    <td>{{ number_format($row->unit_price, 2) }}</td>
                    <td>
                        @if ($row->is_active)
                            <span class="badge text-bg-success-subtle text-success-emphasis">Active</span>
                        @else
                            <span class="badge text-bg-secondary-subtle text-secondary-emphasis">Inactive</span>
                        @endif
                    </td>
                    <td class="text-end no-export">
                        <div class="d-inline-flex gap-1">
                            @php
                                $productPayload = [
                                    'id' => $row->id,
                                    'category_id' => $row->category_id,
                                    'name' => $row->name,
                                    'sku' => $row->sku,
                                    'unit_price' => $row->unit_price,
                                ];
                            @endphp
                            <button type="button"
                                class="btn btn-sm btn-outline-secondary btn-edit-product"
                                data-product='{{ json_encode($productPayload) }}'>
                                <i class="bi bi-pencil"></i> Edit
                            </button>

                            @if ($row->is_active)
                            <form method="POST" action="{{ route('company.products.destroy', ['company' => $company->slug, 'product' => $row->id]) }}" class="d-inline form-deactivate-product">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                    <i class="bi bi-slash-circle"></i> Deactivate
                                </button>
                            </form>
                            @else
                            <form method="POST" action="{{ route('company.products.activate', ['company' => $company->slug, 'product' => $row->id]) }}" class="d-inline form-activate-product">
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

<div class="modal fade" id="productModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="productForm" method="POST"
                action="{{ $editingId ? route('company.products.update', ['company' => $company->slug, 'product' => $editingId]) : route('company.products.store', ['company' => $company->slug]) }}">
                @csrf
                <input type="hidden" name="_method" id="productMethod" value="{{ $editingId ? 'PUT' : '' }}">
                <input type="hidden" name="product_id" id="productIdField" value="{{ $editingId }}">

                <div class="modal-header">
                    <h5 class="modal-title" id="productModalTitle">{{ $editingId ? 'Edit product' : 'Add product' }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="productCategory" class="form-label">Category</label>
                        <select class="form-select @error('category_id') is-invalid @enderror" id="productCategory" name="category_id" required>
                            <option value="">&mdash; Select &mdash;</option>
                            @foreach ($categories as $categoryOption)
                                <option value="{{ $categoryOption->id }}" @selected(old('category_id') === $categoryOption->id)>
                                    {{ $categoryOption->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('category_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        @if ($categories->isEmpty())
                            <div class="form-text text-warning">
                                No categories yet — <a href="{{ route('company.product-categories.index', ['company' => $company->slug]) }}">add one first</a>.
                            </div>
                        @endif
                    </div>

                    <div class="mb-3">
                        <label for="productName" class="form-label">Name</label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror"
                            id="productName" name="name" value="{{ old('name') }}" required>
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="productSku" class="form-label">SKU</label>
                            <input type="text" class="form-control @error('sku') is-invalid @enderror"
                                id="productSku" name="sku" value="{{ old('sku') }}">
                            @error('sku')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="productUnitPrice" class="form-label">Unit price</label>
                            <input type="number" step="0.01" min="0" class="form-control @error('unit_price') is-invalid @enderror"
                                id="productUnitPrice" name="unit_price" value="{{ old('unit_price') }}" required>
                            @error('unit_price')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save product</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')


<script>
    $(function() {
        var columnPrint = [0, 1, 2, 3, 4, 5];
        var printTitle = 'Products - {{ $company->name }} - {{ date("d M, Y") }}';
        $('#productsTable').DataTable({
            ordering: false,
            paging: false,
            info: false,
            responsive: true,
            columnDefs: [{ targets: [0], orderable: false }],
            language: { emptyTable: "No products yet" },
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

        var $modalEl = $('#productModal');
        var modal = new bootstrap.Modal($modalEl[0]);
        var $form = $('#productForm');
        var storeUrl = "{{ route('company.products.store', ['company' => $company->slug]) }}";
        var updateUrlTemplate = "{{ route('company.products.update', ['company' => $company->slug, 'product' => '__ID__']) }}";

        function openCreateModal() {
            $form[0].reset();
            $form.attr('action', storeUrl);
            $('#productMethod').val('');
            $('#productIdField').val('');
            $('#productModalTitle').text('Add product');
            modal.show();
        }

        function openEditModal(product) {
            $form.attr('action', updateUrlTemplate.replace('__ID__', product.id));
            $('#productMethod').val('PUT');
            $('#productIdField').val(product.id);
            $('#productCategory').val(product.category_id);
            $('#productName').val(product.name);
            $('#productSku').val(product.sku);
            $('#productUnitPrice').val(product.unit_price);
            $('#productModalTitle').text('Edit ' + product.name);
            modal.show();
        }

        $('#addProductBtn').on('click', openCreateModal);
        $('#productsTable tbody').on('click', '.btn-edit-product', function() {
            openEditModal($(this).data('product'));
        });

        $('.form-deactivate-product').on('submit', function(e) {
            e.preventDefault();
            var $form = $(this);
            if (window.alertify) {
                alertify.confirm('Deactivate product', 'It will no longer be available for new orders. Continue?',
                    function() { $form.off('submit').trigger('submit'); }, function() {});
            } else { $form.off('submit').trigger('submit'); }
        });

        $('.form-activate-product').on('submit', function(e) {
            e.preventDefault();
            var $form = $(this);
            if (window.alertify) {
                alertify.confirm('Activate product', 'It will become available for new orders again. Continue?',
                    function() { $form.off('submit').trigger('submit'); }, function() {});
            } else { $form.off('submit').trigger('submit'); }
        });

        @if ($errors->any())
            modal.show();
        @endif
    });
</script>
@endpush
