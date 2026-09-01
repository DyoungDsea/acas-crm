@extends('layouts.app')

@section('title', 'Super Admin — Group overview')

@section('content')
<h1 class="h4 mb-4">Group overview</h1>

<div class="row g-3 mb-4">
    @foreach ($companies as $row)
    <div class="col-12 col-md-6 col-lg-4">
        <div class="card company-card h-100 border-0 shadow-sm overflow-hidden">
            <div class="card-body p-4">

                {{-- Header --}}
                <div class="d-flex align-items-start justify-content-between mb-4">
                    <div>
                        <h5 class="fw-semibold mb-1 text-dark">
                            {{ $row->name }}
                        </h5>

                        <span class="badge bg-primary-subtle text-primary rounded-pill px-3 py-2">
                            {{ ucfirst(str_replace('_', ' ', $row->type)) }}
                        </span>
                    </div>
                </div>

                {{-- Statistics --}}
                <div class="row g-2 mb-4">

                    <div class="col-4">
                        <div class="bg-light rounded-3 text-center py-3">
                            <div class="fw-bold fs-5 text-dark">
                                {{ $row->contacts_count }}
                            </div>
                            <div class="text-muted small">
                                Contacts
                            </div>
                        </div>
                    </div>

                    <div class="col-4">
                        <div class="bg-light rounded-3 text-center py-3">
                            <div class="fw-bold fs-5 text-dark">
                                {{ $row->deals_count }}
                            </div>
                            <div class="text-muted small">
                                Deals
                            </div>
                        </div>
                    </div>

                    <div class="col-4">
                        <div class="bg-light rounded-3 text-center py-3">
                            <div class="fw-bold fs-5 text-dark">
                                {{ $row->orders_count }}
                            </div>
                            <div class="text-muted small">
                                Orders
                            </div>
                        </div>
                    </div>

                </div>

                {{-- Action --}}
                <a href="{{ route('company.dashboard', ['company' => $row->slug]) }}"
                    class="btn btn-primary w-100 py-2 fw-semibold">
                    <i class="bi bi-arrow-right-circle me-2"></i>
                    View Company
                </a>

            </div>
        </div>
    </div>
    @endforeach
</div>
@endsection