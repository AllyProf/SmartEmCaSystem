@extends('layouts.visitor')

@section('title', 'Staff Verification')
@section('header', 'Staff Authentication')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-8 col-lg-6">
        <div class="p-2 p-md-4">
            <p class="text-center text-muted mb-4">Enter your registered staff email to access the Customer Visit confirmation system.</p>
            
            <form action="{{ route('visits.verify.check') }}" method="POST" id="verifyForm">
                @csrf
                <div class="form-group mb-4">
                    <label class="control-label font-weight-bold">Staff Email Address</label>
                    <div class="input-group shadow-sm">
                        <div class="input-group-prepend">
                            <span class="input-group-text bg-white border-right-0"><i class="fa fa-envelope text-muted"></i></span>
                        </div>
                        <input class="form-control border-left-0 h-auto py-3" type="email" name="email" placeholder="e.g. name@emca.tech" required autofocus>
                    </div>
                </div>
                
                <div class="form-group mt-5">
                    <button class="btn btn-primary btn-block btn-lg shadow" type="submit">
                        <i class="fa fa-unlock-alt"></i> Verify & Proceed
                    </button>
                    
                    <div class="text-center mt-4">
                        <a href="{{ route('login') }}" class="btn btn-link text-secondary">
                            <i class="fa fa-arrow-left"></i> Back to Login
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
