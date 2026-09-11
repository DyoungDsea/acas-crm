@extends('layouts.app')

@section('title', 'Solar Projects')

@push('styles')
<link href="{{ asset('assets/DataTables/datatables.min.css') }}" rel="stylesheet">
<link href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css" rel="stylesheet">
@endpush

@php
$editingId = old('solar_project_id');
@endphp

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h4 mb-1">Solar Projects</h1>
        <p class="text-muted small mb-0">Surveys, installs, and consultancy work for {{ $company->name }}.</p>
    </div>
    <button type="button" class="btn btn-primary" id="addProjectBtn" @disabled($contacts->isEmpty())>
        <i class="bi bi-plus-lg me-1"></i> Add project
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
        {{-- No delete action on purpose — a project is a record of
             work done or scheduled. Loop variable is $row, not
             $company. --}}
        <table class="table table-hover align-middle w-100" id="projectsTable">
            <thead>
                <tr>
                    <th>S/N</th>
                    <th>Title</th>
                    <th>Contact</th>
                    <th>Type</th>
                    <th>Start</th>
                    <th>Completion</th>
                    <th>Surveys</th>
                    <th>Status</th>
                    <th class="text-end no-export">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($projects as $row)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td class="fw-semibold">{{ $row->title }}</td>
                    <td>{{ $row->contact->name ?? '—' }}</td>
                    <td>{{ ucfirst(str_replace('_', ' ', $row->type)) }}</td>
                    <td>{{ $row->start_date?->format('M j, Y') ?? '—' }}</td>
                    <td>{{ $row->completion_date?->format('M j, Y') ?? '—' }}</td>
                    <td>{{ $row->siteSurveys->count() }}</td>
                    <td>
                        @php
                            $statusClass = match ($row->status) {
                                'quoted' => 'text-bg-info-subtle text-info-emphasis',
                                'installing' => 'text-bg-warning-subtle text-warning-emphasis',
                                'completed' => 'text-bg-success-subtle text-success-emphasis',
                                default => 'text-bg-light border',
                            };
                        @endphp
                        <span class="badge {{ $statusClass }}">{{ ucfirst($row->status) }}</span>
                    </td>
                    <td class="text-end no-export">
                        @php
                            $projectPayload = [
                                'id' => $row->id,
                                'contact_id' => $row->contact_id,
                                'title' => $row->title,
                                'type' => $row->type,
                                'status' => $row->status,
                                'start_date' => $row->start_date?->format('Y-m-d'),
                                'completion_date' => $row->completion_date?->format('Y-m-d'),
                            ];
                            $surveysPayload = [
                                'project_id' => $row->id,
                                'project_title' => $row->title,
                                'surveys' => $row->siteSurveys->map(fn ($survey) => [
                                    'id' => $survey->id,
                                    'survey_date' => $survey->survey_date->format('Y-m-d'),
                                    'findings' => $survey->findings,
                                    'status' => $survey->status,
                                    'surveyor' => $survey->surveyor->name ?? '—',
                                ])->values(),
                            ];
                        @endphp
                        <button type="button"
                            class="btn btn-sm btn-outline-secondary btn-edit-project"
                            data-project='{{ json_encode($projectPayload) }}'>
                            <i class="bi bi-pencil"></i> Edit
                        </button>
                        <button type="button"
                            class="btn btn-sm btn-outline-primary btn-view-surveys"
                            data-surveys='{{ json_encode($surveysPayload) }}'>
                            <i class="bi bi-clipboard-data"></i> Surveys
                        </button>
                    </td>
                </tr>
                @empty
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- ===================== PROJECT MODAL ===================== --}}
<div class="modal fade" id="projectModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="projectForm" method="POST"
                action="{{ $editingId ? route('company.solar-projects.update', ['company' => $company->slug, 'solar_project' => $editingId]) : route('company.solar-projects.store', ['company' => $company->slug]) }}">
                @csrf
                <input type="hidden" name="_method" id="projectMethod" value="{{ $editingId ? 'PUT' : '' }}">
                <input type="hidden" name="solar_project_id" id="projectIdField" value="{{ $editingId }}">

                <div class="modal-header">
                    <h5 class="modal-title" id="projectModalTitle">{{ $editingId ? 'Edit project' : 'Add project' }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="projectTitle" class="form-label">Title</label>
                        <input type="text" class="form-control @error('title') is-invalid @enderror"
                            id="projectTitle" name="title" value="{{ old('title') }}" placeholder="e.g. Ibafo Estate rooftop install" required>
                        @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="projectContact" class="form-label">Contact</label>
                            <select class="form-select @error('contact_id') is-invalid @enderror" id="projectContact" name="contact_id" required>
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
                            <label for="projectType" class="form-label">Type</label>
                            <select class="form-select @error('type') is-invalid @enderror" id="projectType" name="type" required>
                                <option value="">&mdash; Select &mdash;</option>
                                @foreach (['site_survey' => 'Site survey', 'installation' => 'Installation', 'consultancy' => 'Consultancy', 'solar_farm' => 'Solar farm'] as $value => $label)
                                    <option value="{{ $value }}" @selected(old('type') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="projectStartDate" class="form-label">Start date <span class="text-muted fw-normal">(optional)</span></label>
                            <input type="date" class="form-control @error('start_date') is-invalid @enderror"
                                id="projectStartDate" name="start_date" value="{{ old('start_date') }}">
                            @error('start_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="projectCompletionDate" class="form-label">Completion date <span class="text-muted fw-normal">(optional)</span></label>
                            <input type="date" class="form-control @error('completion_date') is-invalid @enderror"
                                id="projectCompletionDate" name="completion_date" value="{{ old('completion_date') }}">
                            @error('completion_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="mb-1">
                        <label for="projectStatus" class="form-label">Status</label>
                        <select class="form-select @error('status') is-invalid @enderror" id="projectStatus" name="status" required>
                            @foreach (['survey' => 'Survey', 'quoted' => 'Quoted', 'installing' => 'Installing', 'completed' => 'Completed'] as $value => $label)
                                <option value="{{ $value }}" @selected(old('status') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save project</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ===================== SURVEYS MODAL ===================== --}}
<div class="modal fade" id="surveysModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="surveysModalTitle">Surveys</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                {{-- No delete action on surveys either — same
                     reasoning as the project itself. --}}
                <table class="table table-sm align-middle mb-4">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Findings</th>
                            <th>Surveyor</th>
                            <th>Status</th>
                            <th class="text-end"></th>
                        </tr>
                    </thead>
                    <tbody id="surveysListBody">
                        <tr id="surveysEmptyRow"><td colspan="5" class="text-muted small">No surveys logged yet.</td></tr>
                    </tbody>
                </table>

                <hr>

                <form id="surveyForm" method="POST">
                    @csrf
                    <input type="hidden" name="_method" id="surveyMethod" value="">
                    <h6 class="fw-semibold mb-3" id="surveyFormTitle">Add a survey</h6>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="surveyDate" class="form-label">Survey date</label>
                            <input type="date" class="form-control @error('survey_date') is-invalid @enderror"
                                id="surveyDate" name="survey_date" value="{{ old('survey_date') }}" required>
                            @error('survey_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="surveyStatus" class="form-label">Status</label>
                            <select class="form-select @error('status') is-invalid @enderror" id="surveyStatus" name="status" required>
                                @foreach (['pending' => 'Pending', 'completed' => 'Completed'] as $value => $label)
                                    <option value="{{ $value }}" @selected(old('status') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="surveyFindings" class="form-label">Findings <span class="text-muted fw-normal">(optional)</span></label>
                        <textarea class="form-control @error('findings') is-invalid @enderror"
                            id="surveyFindings" name="findings" rows="3">{{ old('findings') }}</textarea>
                        @error('findings')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <button type="button" class="btn btn-outline-secondary btn-sm" id="cancelSurveyEditBtn" style="display: none;">Cancel edit</button>
                        <button type="submit" class="btn btn-primary btn-sm" id="surveySubmitBtn">Add survey</button>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
            </div>
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
        var printTitle = 'Solar Projects - {{ $company->name }} - {{ date("d M, Y") }}';
        $('#projectsTable').DataTable({
            ordering: false,
            paging: false,
            info: false,
            responsive: true,
            columnDefs: [{ targets: [0], orderable: false }],
            language: { emptyTable: "No projects yet" },
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

        // ---------- Project modal ----------
        var $projectModalEl = $('#projectModal');
        var projectModal = new bootstrap.Modal($projectModalEl[0]);
        var $projectForm = $('#projectForm');
        var projectStoreUrl = "{{ route('company.solar-projects.store', ['company' => $company->slug]) }}";
        var projectUpdateUrlTemplate = "{{ route('company.solar-projects.update', ['company' => $company->slug, 'solar_project' => '__ID__']) }}";

        function openCreateProjectModal() {
            $projectForm[0].reset();
            $projectForm.attr('action', projectStoreUrl);
            $('#projectMethod').val('');
            $('#projectIdField').val('');
            $('#projectModalTitle').text('Add project');
            projectModal.show();
        }

        function openEditProjectModal(project) {
            $projectForm.attr('action', projectUpdateUrlTemplate.replace('__ID__', project.id));
            $('#projectMethod').val('PUT');
            $('#projectIdField').val(project.id);
            $('#projectTitle').val(project.title);
            $('#projectContact').val(project.contact_id);
            $('#projectType').val(project.type);
            $('#projectStartDate').val(project.start_date || '');
            $('#projectCompletionDate').val(project.completion_date || '');
            $('#projectStatus').val(project.status);
            $('#projectModalTitle').text('Edit ' + project.title);
            projectModal.show();
        }

        $('#addProjectBtn').on('click', openCreateProjectModal);
        $('#projectsTable tbody').on('click', '.btn-edit-project', function() {
            openEditProjectModal($(this).data('project'));
        });

        // ---------- Surveys modal ----------
        var $surveysModalEl = $('#surveysModal');
        var surveysModal = new bootstrap.Modal($surveysModalEl[0]);
        var $surveyForm = $('#surveyForm');
        var surveyStoreUrlTemplate = "{{ route('company.solar-projects.surveys.store', ['company' => $company->slug, 'solar_project' => '__PROJECT_ID__']) }}";
        var surveyUpdateUrlTemplate = "{{ route('company.solar-projects.surveys.update', ['company' => $company->slug, 'solar_project' => '__PROJECT_ID__', 'survey' => '__SURVEY_ID__']) }}";
        var currentProjectId = null;

        function statusBadge(status) {
            var cls = status === 'completed' ? 'text-bg-success-subtle text-success-emphasis' : 'text-bg-light border';
            return '<span class="badge ' + cls + '">' + status.charAt(0).toUpperCase() + status.slice(1) + '</span>';
        }

        function resetSurveyForm() {
            $surveyForm[0].reset();
            $('#surveyMethod').val('');
            $('#surveyFormTitle').text('Add a survey');
            $('#surveySubmitBtn').text('Add survey');
            $('#cancelSurveyEditBtn').hide();
            $surveyForm.attr('action', surveyStoreUrlTemplate.replace('__PROJECT_ID__', currentProjectId));
        }

        function openSurveysModal(payload) {
            currentProjectId = payload.project_id;
            $('#surveysModalTitle').text('Surveys — ' + payload.project_title);

            var $body = $('#surveysListBody').empty();
            if (!payload.surveys.length) {
                $body.append('<tr><td colspan="5" class="text-muted small">No surveys logged yet.</td></tr>');
            } else {
                payload.surveys.forEach(function(survey) {
                    var $row = $(
                        '<tr>' +
                            '<td>' + survey.survey_date + '</td>' +
                            '<td class="small">' + (survey.findings ? $('<div>').text(survey.findings).html() : '—') + '</td>' +
                            '<td>' + survey.surveyor + '</td>' +
                            '<td>' + statusBadge(survey.status) + '</td>' +
                            '<td class="text-end"><button type="button" class="btn btn-sm btn-outline-secondary btn-edit-survey"><i class="bi bi-pencil"></i></button></td>' +
                        '</tr>'
                    );
                    $row.find('.btn-edit-survey').on('click', function() {
                        $('#surveyMethod').val('PUT');
                        $('#surveyDate').val(survey.survey_date);
                        $('#surveyStatus').val(survey.status);
                        $('#surveyFindings').val(survey.findings || '');
                        $('#surveyFormTitle').text('Edit survey — ' + survey.survey_date);
                        $('#surveySubmitBtn').text('Save changes');
                        $('#cancelSurveyEditBtn').show();
                        $surveyForm.attr('action', surveyUpdateUrlTemplate.replace('__PROJECT_ID__', currentProjectId).replace('__SURVEY_ID__', survey.id));
                    });
                    $body.append($row);
                });
            }

            resetSurveyForm();
            surveysModal.show();
        }

        $('#projectsTable tbody').on('click', '.btn-view-surveys', function() {
            openSurveysModal($(this).data('surveys'));
        });

        $('#cancelSurveyEditBtn').on('click', resetSurveyForm);

        @if ($errors->any())
            @if (old('survey_date'))
                {{-- A survey submission failed validation — the
                     project context was lost on redirect, so we can
                     only show the form standalone, not the full list. --}}
                $('#surveysModalTitle').text('Surveys');
                $('#surveysListBody').html('<tr><td colspan="5" class="text-muted small">Reopen this project\'s Surveys to see the full list.</td></tr>');
                surveysModal.show();
            @else
                projectModal.show();
            @endif
        @endif
    });
</script>
@endpush
