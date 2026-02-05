@extends('layouts.app')

@section('title', 'Edit Follow-up')
@section('icon', 'fa-edit')
@section('page-title', 'Edit Follow-up')

@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
<li class="breadcrumb-item"><a href="{{ route('follow-ups.index') }}">Follow-ups</a></li>
<li class="breadcrumb-item">Edit</li>
@endsection

@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="tile">
            <h3 class="tile-title">Edit Follow-up Information</h3>
            <div class="tile-body">
                <form action="{{ route('follow-ups.update', $followUp->id) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="form-group">
                        <label class="control-label">Customer <span class="text-danger">*</span></label>
                        <select class="form-control" name="customer_id" required>
                            <option value="">-- Select Customer --</option>
                            @foreach($customers as $customer)
                            <option value="{{ $customer->id }}" {{ old('customer_id', $followUp->customer_id) == $customer->id ? 'selected' : '' }}>
                                {{ $customer->name ?? 'N/A' }} - {{ $customer->phone_number }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="control-label">Visit Date <span class="text-danger">*</span></label>
                        <input class="form-control" type="date" name="visit_date" value="{{ old('visit_date', $followUp->visit_date->format('Y-m-d')) }}" required>
                    </div>
                    <div class="form-group">
                        <label class="control-label">Visit Purpose <span class="text-muted">(Optional)</span></label>
                        <textarea class="form-control" name="visit_purpose" rows="3" placeholder="Purpose of visit">{{ old('visit_purpose', $followUp->visit_purpose) }}</textarea>
                    </div>
                    <div class="form-group">
                        <label class="control-label">Notes <span class="text-muted">(Optional)</span></label>
                        <textarea class="form-control" name="notes" rows="3" placeholder="Additional notes">{{ old('notes', $followUp->notes) }}</textarea>
                    </div>
                    <div class="form-group">
                        <label class="control-label">Status <span class="text-danger">*</span></label>
                        <select class="form-control" name="status" required>
                            <option value="pending" {{ old('status', $followUp->status) == 'pending' ? 'selected' : '' }}>Pending</option>
                            <option value="completed" {{ old('status', $followUp->status) == 'completed' ? 'selected' : '' }}>Completed</option>
                            <option value="cancelled" {{ old('status', $followUp->status) == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="control-label">Next Follow-up Date <span class="text-muted">(Optional)</span></label>
                        <input class="form-control" type="date" name="next_follow_up_date" value="{{ old('next_follow_up_date', $followUp->next_follow_up_date ? $followUp->next_follow_up_date->format('Y-m-d') : '') }}">
                    </div>
                    <div class="form-group">
                        <label class="control-label">Assigned To <span class="text-muted">(Optional)</span></label>
                        <select class="form-control" name="assigned_to">
                            <option value="">-- Select User --</option>
                            @foreach($users as $user)
                            <option value="{{ $user->id }}" {{ old('assigned_to', $followUp->assigned_to) == $user->id ? 'selected' : '' }}>
                                {{ $user->name }} ({{ ucfirst($user->role) }})
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="tile-footer">
                        <button class="btn btn-primary" type="submit"><i class="fa fa-fw fa-lg fa-check-circle"></i>Update</button>
                        <a class="btn btn-secondary" href="{{ route('follow-ups.index') }}"><i class="fa fa-fw fa-lg fa-times-circle"></i>Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection





