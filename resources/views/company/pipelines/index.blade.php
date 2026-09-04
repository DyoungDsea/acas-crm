@extends('layouts.app')

@section('title', 'Pipelines')

@php
$editingId = old('pipeline_id');
@endphp

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h4 mb-1">Pipelines</h1>
        <p class="text-muted small mb-0">The stages a deal moves through, for {{ $company->name }}.</p>
    </div>
    <button type="button" class="btn btn-primary" id="addPipelineBtn">
        <i class="bi bi-plus-lg me-1"></i> Add pipeline
    </button>
</div>

@if ($pipelines->isEmpty())
<div class="card border-0 shadow-sm">
    <div class="card-body text-center text-muted py-5">
        No pipelines yet. Add one to start tracking deals.
    </div>
</div>
@endif

<div class="row g-3">
    @foreach ($pipelines as $pipeline)
    <div class="col-12 col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div>
                        <h2 class="h6 mb-1">
                            {{ $pipeline->name }}
                            @if ($pipeline->is_default)
                            <span class="badge text-bg-light border ms-1">Default</span>
                            @endif
                        </h2>
                        <p class="text-muted small mb-0">{{ $pipeline->stages->count() }} stage(s)</p>
                    </div>
                    <div class="d-flex gap-1">
                        @php
                        $pipelinePayload = ['id' => $pipeline->id, 'name' => $pipeline->name, 'is_default' => $pipeline->is_default];
                        @endphp
                        <button type="button" class="btn btn-sm btn-outline-secondary btn-edit-pipeline"
                            data-pipeline='{{ json_encode($pipelinePayload) }}'>
                            <i class="bi bi-pencil"></i>
                        </button>
                        <form method="POST" action="{{ route('company.pipelines.destroy', ['company' => $company->slug, 'pipeline' => $pipeline->id]) }}" class="d-inline form-delete-pipeline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-outline-danger" data-name="{{ $pipeline->name }}">
                                <i class="bi bi-trash"></i>
                            </button>
                        </form>
                    </div>
                </div>

                <ul class="list-group list-group-flush mb-3">
                    @forelse ($pipeline->stages as $stage)
                    <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                        <span>
                            {{ $stage->name }}
                            @if ($stage->deals_count > 0)
                            <span class="badge text-bg-light border ms-1">{{ $stage->deals_count }} deal(s)</span>
                            @endif
                        </span>
                        <div class="d-flex gap-1">
                            <form method="POST" action="{{ route('company.pipelines.stages.move', ['company' => $company->slug, 'pipeline' => $pipeline->id, 'stage' => $stage->id]) }}" class="d-inline">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="direction" value="up">
                                <button type="submit" class="btn btn-sm btn-outline-secondary" title="Move up">
                                    <i class="bi bi-arrow-up"></i>
                                </button>
                            </form>
                            <form method="POST" action="{{ route('company.pipelines.stages.move', ['company' => $company->slug, 'pipeline' => $pipeline->id, 'stage' => $stage->id]) }}" class="d-inline">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="direction" value="down">
                                <button type="submit" class="btn btn-sm btn-outline-secondary" title="Move down">
                                    <i class="bi bi-arrow-down"></i>
                                </button>
                            </form>
                            @php
                            $stagePayload = ['id' => $stage->id, 'name' => $stage->name, 'pipeline_id' => $pipeline->id];
                            @endphp
                            <button type="button" class="btn btn-sm btn-outline-secondary btn-edit-stage"
                                data-stage='{{ json_encode($stagePayload) }}'>
                                <i class="bi bi-pencil"></i>
                            </button>
                            <form method="POST" action="{{ route('company.pipelines.stages.destroy', ['company' => $company->slug, 'pipeline' => $pipeline->id, 'stage' => $stage->id]) }}" class="d-inline form-delete-stage">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger" data-name="{{ $stage->name }}" data-count="{{ $stage->deals_count }}">
                                    <i class="bi bi-x-lg"></i>
                                </button>
                            </form>
                        </div>
                    </li>
                    @empty
                    <li class="list-group-item px-0 text-muted small">No stages yet.</li>
                    @endforelse
                </ul>

                <form method="POST" action="{{ route('company.pipelines.stages.store', ['company' => $company->slug, 'pipeline' => $pipeline->id]) }}" class="d-flex gap-2">
                    @csrf
                    <input type="text" name="name" class="form-control form-control-sm" placeholder="New stage name" required>
                    <button type="submit" class="btn btn-sm btn-outline-primary text-nowrap">
                        <i class="bi bi-plus-lg"></i> Add stage
                    </button>
                </form>
            </div>
        </div>
    </div>
    @endforeach
</div>

{{-- Create/edit pipeline modal --}}
<div class="modal fade" id="pipelineModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="pipelineForm" method="POST"
                action="{{ $editingId ? route('company.pipelines.update', ['company' => $company->slug, 'pipeline' => $editingId]) : route('company.pipelines.store', ['company' => $company->slug]) }}">
                @csrf
                <input type="hidden" name="_method" id="pipelineMethod" value="{{ $editingId ? 'PUT' : '' }}">
                <input type="hidden" name="pipeline_id" id="pipelineIdField" value="{{ $editingId }}">

                <div class="modal-header">
                    <h5 class="modal-title" id="pipelineModalTitle">{{ $editingId ? 'Edit pipeline' : 'Add pipeline' }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="pipelineName" class="form-label">Name</label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror"
                            id="pipelineName" name="name" value="{{ old('name') }}" required autofocus>
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-check form-switch">
                        <input type="hidden" name="is_default" value="0">
                        <input type="checkbox" class="form-check-input" role="switch" id="pipelineIsDefault" name="is_default" value="1">
                        <label class="form-check-label" for="pipelineIsDefault">Default pipeline</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save pipeline</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Edit stage modal — a single shared modal, action rewritten per
     stage before it opens. --}}
<div class="modal fade" id="stageModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="stageForm" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title">Rename stage</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <label for="stageName" class="form-label">Name</label>
                    <input type="text" class="form-control" id="stageName" name="name" required>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $(function() {
        var $pipelineModalEl = $('#pipelineModal');
        var pipelineModal = new bootstrap.Modal($pipelineModalEl[0]);
        var $pipelineForm = $('#pipelineForm');
        var pipelineStoreUrl = "{{ route('company.pipelines.store', ['company' => $company->slug]) }}";
        var pipelineUpdateUrlTemplate = "{{ route('company.pipelines.update', ['company' => $company->slug, 'pipeline' => '__ID__']) }}";

        $('#addPipelineBtn').on('click', function() {
            $pipelineForm[0].reset();
            $pipelineForm.attr('action', pipelineStoreUrl);
            $('#pipelineMethod').val('');
            $('#pipelineIdField').val('');
            $('#pipelineModalTitle').text('Add pipeline');
            pipelineModal.show();
        });

        $('.btn-edit-pipeline').on('click', function() {
            var pipeline = $(this).data('pipeline');
            $pipelineForm.attr('action', pipelineUpdateUrlTemplate.replace('__ID__', pipeline.id));
            $('#pipelineMethod').val('PUT');
            $('#pipelineIdField').val(pipeline.id);
            $('#pipelineName').val(pipeline.name);
            $('#pipelineIsDefault').prop('checked', !!pipeline.is_default);
            $('#pipelineModalTitle').text('Edit ' + pipeline.name);
            pipelineModal.show();
        });

        $('.form-delete-pipeline').on('submit', function(e) {
            e.preventDefault();
            var $form = $(this);
            var name = $form.find('button[type="submit"]').data('name');
            if (window.alertify) {
                alertify.confirm('Delete pipeline', 'Delete "' + name + '" and all its stages? This can\'t be undone.',
                    function() {
                        $form.off('submit').trigger('submit');
                    },
                    function() {});
            } else {
                $form.off('submit').trigger('submit');
            }
        });

        // ---- Stage rename modal ----
        var $stageModalEl = $('#stageModal');
        var stageModal = new bootstrap.Modal($stageModalEl[0]);
        var $stageForm = $('#stageForm');
        var stageUpdateTemplate = "{{ route('company.pipelines.stages.update', ['company' => $company->slug, 'pipeline' => '__PID__', 'stage' => '__SID__']) }}";

        $('.btn-edit-stage').on('click', function() {
            var stage = $(this).data('stage');
            $stageForm.attr('action', stageUpdateTemplate.replace('__PID__', stage.pipeline_id).replace('__SID__', stage.id));
            $('#stageName').val(stage.name);
            stageModal.show();
        });

        $('.form-delete-stage').on('submit', function(e) {
            e.preventDefault();
            var $form = $(this);
            var $btn = $form.find('button[type="submit"]');
            var name = $btn.data('name');
            var count = $btn.data('count');
            var message = count > 0 ?
                '"' + name + '" has ' + count + ' deal(s) in it — move them to another stage first.' :
                'Delete stage "' + name + '"?';

            if (window.alertify) {
                alertify.confirm('Delete stage', message,
                    function() {
                        $form.off('submit').trigger('submit');
                    },
                    function() {});
            } else {
                $form.off('submit').trigger('submit');
            }
        });

        @if($errors -> any())
        @if(old('is_default') !== null || old('pipeline_id') !== null)
        pipelineModal.show();
        @endif
        @endif
    });
</script>
@endpush
