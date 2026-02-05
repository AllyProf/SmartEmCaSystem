@extends('layouts.app')

@section('title', 'Edit Customer')
@section('icon', 'fa-edit')
@section('page-title', 'Edit Customer')

@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
<li class="breadcrumb-item"><a href="{{ route('customers.index') }}">Customers</a></li>
<li class="breadcrumb-item">Edit</li>
@endsection

@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="tile">
            <h3 class="tile-title">Edit Customer Information</h3>
            <div class="tile-body">
                <form action="{{ route('customers.update', $customer->id) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="form-group">
                        <label class="control-label">Name <span class="text-muted">(Optional)</span></label>
                        <input class="form-control" type="text" name="name" value="{{ old('name', $customer->name) }}" placeholder="Customer name">
                    </div>
                    <div class="form-group">
                        <label class="control-label">Phone Number <span class="text-danger">*</span></label>
                        <input class="form-control" type="text" name="phone_number" value="{{ old('phone_number', $customer->phone_number) }}" placeholder="255612345678" required>
                    </div>
                    <div class="form-group">
                        <label class="control-label">Location <span class="text-muted">(Optional)</span></label>
                        <input class="form-control" type="text" name="location" value="{{ old('location', $customer->location) }}" placeholder="Customer location">
                    </div>
                    <div class="form-group">
                        <label class="control-label">Visiting Purpose <span class="text-muted">(Optional)</span></label>
                        <textarea class="form-control" name="visiting_purpose" rows="3" placeholder="Purpose of visit">{{ old('visiting_purpose', $customer->visiting_purpose) }}</textarea>
                    </div>
                    <div class="tile-footer">
                        <button class="btn btn-primary" type="submit"><i class="fa fa-fw fa-lg fa-check-circle"></i>Update</button>
                        <a class="btn btn-secondary" href="{{ route('customers.index') }}"><i class="fa fa-fw fa-lg fa-times-circle"></i>Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection





