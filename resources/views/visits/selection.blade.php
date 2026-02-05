@extends('layouts.visitor')

@section('title', 'Select Visit Type')
@section('header', 'Select Visit Type')

@section('content')
<div class="row justify-content-center" style="margin-top: 30px; margin-bottom: 50px;">
    <div class="col-md-5 col-sm-10 mb-4">
        <a href="{{ route('visits.single') }}" class="text-decoration-none">
            <div class="card p-4 h-100 border-0 shadow-sm transition-hover">
                <div class="card-body text-center">
                    <div class="icon-circle mb-4 mx-auto">
                        <i class="fa fa-user fa-3x text-white"></i>
                    </div>
                    <h3 class="card-title font-weight-bold text-dark">Single Customer</h3>
                    <p class="text-muted">For individual visits or organization representatives (1 person signing).</p>
                    <div class="btn btn-primary btn-block mt-4 rounded-pill">Select Single Form</div>
                </div>
            </div>
        </a>
    </div>
    <div class="col-md-5 col-sm-10 mb-4">
        <a href="{{ route('visits.group') }}" class="text-decoration-none">
            <div class="card p-4 h-100 border-0 shadow-sm transition-hover">
                <div class="card-body text-center">
                    <div class="icon-circle mb-4 mx-auto bg-success">
                        <i class="fa fa-users fa-3x text-white"></i>
                    </div>
                    <h3 class="card-title font-weight-bold text-dark">Group Visit</h3>
                    <p class="text-muted">For multiple attendees. Includes an attendance sheet for group signatures.</p>
                    <div class="btn btn-success btn-block mt-4 rounded-pill">Select Group Form</div>
                </div>
            </div>
        </a>
    </div>
</div>
<div class="text-center">
    <a href="{{ route('login') }}" class="text-muted">Exit to Login</a>
</div>
@endsection

@push('styles')
<style>
    .icon-circle {
        width: 80px;
        height: 80px;
        background-color: #940000;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 4px 10px rgba(148, 0, 0, 0.2);
    }
    .bg-success { background-color: #28a745 !important; box-shadow: 0 4px 10px rgba(40, 167, 69, 0.2); }
    .transition-hover {
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        cursor: pointer;
    }
    .transition-hover:hover {
        transform: translateY(-10px);
        box-shadow: 0 10px 20px rgba(0,0,0,0.1) !important;
    }
    .rounded-pill { border-radius: 50px !important; }
</style>
@endpush
