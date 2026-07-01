@extends('layouts.app')

@section('title', 'Add Customer')
@section('icon', 'fa-user-plus')
@section('page-title', 'Add Customer')
@section('page-description', 'Add a new customer to the system')

@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
<li class="breadcrumb-item"><a href="{{ route('customers.index') }}">Customers</a></li>
<li class="breadcrumb-item">Add Customer</li>
@endsection

@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="tile">
            <h3 class="tile-title">Customer Information</h3>
            <div class="tile-body">
                <form action="{{ route('customers.store') }}" method="POST">
                    @csrf
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="control-label">Name <span class="text-muted">(Optional)</span></label>
                                <input class="form-control" type="text" name="name" value="{{ old('name') }}" placeholder="Customer name">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="control-label">Phone Number <span class="text-danger">*</span></label>
                                <input class="form-control" type="text" name="phone_number" value="{{ old('phone_number', '+255') }}" placeholder="+255612345678" required>
                                <small class="form-text text-muted">Enter phone number with country code (e.g., +255612345678)</small>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="control-label">Location <span class="text-muted">(Optional)</span></label>
                                <input class="form-control" type="text" name="location" value="{{ old('location') }}" placeholder="Customer location">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="control-label">Visiting Purpose <span class="text-muted">(Optional)</span></label>
                                <textarea class="form-control" name="visiting_purpose" rows="3" placeholder="Purpose of visit">{{ old('visiting_purpose') }}</textarea>
                            </div>
                        </div>
                    </div>
                    <div class="tile-footer">
                        <button class="btn btn-primary" type="submit"><i class="fa fa-fw fa-lg fa-check-circle"></i>Save</button>
                        <a class="btn btn-secondary" href="{{ route('customers.index') }}"><i class="fa fa-fw fa-lg fa-times-circle"></i>Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection





