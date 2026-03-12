@extends('layouts.app')

@section('title', 'Add Follow-up')
@section('icon', 'fa-calendar-plus-o')
@section('page-title', 'Add Follow-up')
@section('page-description', 'Create a new follow-up record')

@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
<li class="breadcrumb-item"><a href="{{ route('follow-ups.index') }}">Follow-ups</a></li>
<li class="breadcrumb-item">Add Follow-up</li>
@endsection

@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="tile">
            <h3 class="tile-title">Follow-up Information</h3>
            <div class="tile-body">
                <form action="{{ route('follow-ups.store') }}" method="POST">
                    @csrf
                    <div class="form-group">
                        <label class="control-label">Customer <span class="text-danger">*</span></label>
                        <select class="form-control" name="customer_id" required>
                            <option value="">-- Select Customer --</option>
                            @foreach($customers as $customer)
                            <option value="{{ $customer->id }}" {{ (isset($selectedCustomerId) && $selectedCustomerId == $customer->id) || old('customer_id') == $customer->id ? 'selected' : '' }}>
                                {{ $customer->name ?? 'N/A' }} - {{ $customer->phone_number }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="control-label">Visit Date <span class="text-danger">*</span></label>
                        <input class="form-control" type="date" name="visit_date" value="{{ old('visit_date', date('Y-m-d')) }}" required>
                    </div>
                    <div class="form-group">
                        <label class="control-label">Visit Purpose <span class="text-muted">(Optional)</span></label>
                        <textarea class="form-control" name="visit_purpose" rows="3" placeholder="Purpose of visit">{{ old('visit_purpose') }}</textarea>
                    </div>
                    <div class="form-group">
                        <label class="control-label">Notes <span class="text-muted">(Optional)</span></label>
                        <textarea class="form-control" name="notes" rows="3" placeholder="Additional notes">{{ old('notes') }}</textarea>
                    </div>
                    <div class="form-group">
                        <label class="control-label">Status <span class="text-danger">*</span></label>
                        <select class="form-control" name="status" required>
                            <option value="pending" {{ old('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                            <option value="completed" {{ old('status') == 'completed' ? 'selected' : '' }}>Completed</option>
                            <option value="cancelled" {{ old('status') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="control-label">Next Follow-up Date <span class="text-muted">(Optional)</span></label>
                        <input class="form-control" type="date" name="next_follow_up_date" value="{{ old('next_follow_up_date') }}">
                    </div>
                    <div class="form-group">
                        <label class="control-label">Assigned To <span class="text-muted">(Optional)</span></label>
                        <select class="form-control" name="assigned_to">
                            <option value="">-- Select User --</option>
                            @foreach($users as $user)
                            <option value="{{ $user->id }}" {{ old('assigned_to') == $user->id ? 'selected' : '' }}>
                                {{ $user->name }} ({{ ucfirst($user->role) }})
                            </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- SMS Reminder Section --}}
                    <div class="tile" style="border-left: 4px solid #940000; background: #fff8f8; border-radius: 6px; padding: 15px 20px; margin-bottom: 20px;">
                        <p class="font-weight-bold mb-2" style="color: #940000;"><i class="fa fa-bell"></i> SMS Reminder Settings</p>
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label class="control-label">Reminder Date <span class="text-muted">(Optional)</span></label>
                                <input class="form-control" type="date" name="reminder_date" value="{{ old('reminder_date') }}" placeholder="When to send the SMS reminder">
                                <small class="text-muted">The system will send an SMS on this date at 7:00 AM.</small>
                            </div>
                            <div class="form-group col-md-6">
                                <label class="control-label">Remind Via</label>
                                <select class="form-control" name="remind_via">
                                    <option value="assigned_user" {{ old('remind_via', 'assigned_user') == 'assigned_user' ? 'selected' : '' }}>Staff / Assigned User Only</option>
                                    <option value="customer" {{ old('remind_via') == 'customer' ? 'selected' : '' }}>Customer Only</option>
                                    <option value="both" {{ old('remind_via') == 'both' ? 'selected' : '' }}>Both Staff & Customer</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="tile-footer">
                        <button class="btn btn-primary" type="submit"><i class="fa fa-fw fa-lg fa-check-circle"></i>Save</button>
                        <a class="btn btn-secondary" href="{{ route('follow-ups.index') }}"><i class="fa fa-fw fa-lg fa-times-circle"></i>Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

