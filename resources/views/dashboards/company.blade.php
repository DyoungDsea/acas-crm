@extends('layouts.app')

@section('title', $company->name.' — Dashboard')

@section('content')
<h1 class="h4 mb-4">{{ $company->name }}</h1>

<p class="text-muted">
    Widgets for contacts, deals, orders, and receipts render here as
    each page's template is built. Data loads from
    <code>{{ url('/api/companies/'.$company->slug) }}</code> via the
    existing JSON API, authenticated through this same session.
</p>
@endsection

