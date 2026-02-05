@extends('layouts.visitor')

@section('title', 'Success')
@section('header', 'SUCCESS')

@section('content')
<div class="text-center py-5">
    <div class="mb-4">
        <div class="d-inline-block p-4 rounded-circle bg-light shadow-sm mb-3">
            <i class="fa fa-check-circle text-success" style="font-size: 80px;"></i>
        </div>
    </div>
    <h2 class="font-weight-bold">Form Submitted Successfully!</h2>
    <p class="lead text-muted mt-3">Thank you for your cooperation. The confirmation details and signatures have been securely recorded and SMS notifications have been sent.</p>
    
    <div class="mt-5 d-flex flex-column flex-sm-row justify-content-center">
        <a href="{{ route('visits.selection') }}" class="btn btn-primary btn-lg shadow px-5 mb-3 mb-sm-0 mx-sm-2">
            <i class="fa fa-plus"></i> Submit Another
        </a>
        <a href="{{ route('login') }}" class="btn btn-outline-secondary btn-lg mx-sm-2">
            <i class="fa fa-sign-out"></i> Go to Home
        </a>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener("DOMContentLoaded", function() {
        // Confetti effect or similar could go here, but SweetAlert already handled on previous page redirect if any.
        // For success landing, we just keep it clean.
    });
</script>
@endpush
@endsection
