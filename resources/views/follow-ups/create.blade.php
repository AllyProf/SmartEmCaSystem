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

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>
    .select2-container--default .select2-selection--single {
        height: 38px;
        border: 1px solid #ced4da;
    }
    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 38px;
    }
    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 36px;
    }
    .select2-container--default .select2-results__option--highlighted[aria-selected] {
        background-color: #940000;
    }
</style>
@endpush

@section('content')
<div class="row">
    <div class="{{ isset($customerHistory) && $customerHistory->count() > 0 ? 'col-md-8' : 'col-md-12' }}">
        <div class="tile">
            <h3 class="tile-title">Follow-up Information</h3>
            <div class="tile-body">
                <form action="{{ route('follow-ups.store') }}" method="POST">
                    @csrf
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="control-label">Customer <span class="text-danger">*</span></label>
                                <select class="form-control select2" name="customer_id" required>
                                    <option value="">-- Select Customer --</option>
                                    @foreach($customers as $customer)
                                    <option value="{{ $customer->id }}" {{ (isset($selectedCustomerId) && $selectedCustomerId == $customer->id) || old('customer_id') == $customer->id ? 'selected' : '' }}>
                                        {{ $customer->name ?? 'N/A' }} - {{ $customer->phone_number }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="control-label">Visit Date <span class="text-danger">*</span></label>
                                <input class="form-control" type="date" name="visit_date" value="{{ old('visit_date', date('Y-m-d')) }}" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="control-label">Visit Purpose <span class="text-muted">(Optional)</span></label>
                                <textarea class="form-control" name="visit_purpose" rows="3" placeholder="Purpose of visit">{{ old('visit_purpose') }}</textarea>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="control-label">Notes <span class="text-muted">(Optional)</span></label>
                                <textarea class="form-control" name="notes" rows="3" placeholder="Additional notes">{{ old('notes') }}</textarea>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="control-label">Status <span class="text-danger">*</span></label>
                                <select class="form-control select2-no-search" name="status" required>
                                    <option value="Waiting for next call" {{ old('status') == 'Waiting for next call' ? 'selected' : '' }}>Waiting for next call</option>
                                    <option value="Called - Answered" {{ old('status') == 'Called - Answered' ? 'selected' : '' }}>Called - Answered</option>
                                    <option value="Called - No Answer" {{ old('status') == 'Called - No Answer' ? 'selected' : '' }}>Called - No Answer</option>
                                    <option value="Meeting Completed" {{ old('status') == 'Meeting Completed' ? 'selected' : '' }}>Meeting Completed</option>
                                    <option value="Cancelled" {{ old('status') == 'Cancelled' ? 'selected' : '' }}>Cancelled</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="control-label">Next Follow-up Date & Time <span class="text-muted">(Optional)</span></label>
                                <div class="d-flex" style="gap: 10px;">
                                    <input class="form-control" type="date" name="next_follow_up_date" value="{{ old('next_follow_up_date') }}">
                                    <input class="form-control" type="time" name="next_follow_up_time" value="{{ old('next_follow_up_time') }}">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label class="control-label">Collaborate With <span class="text-muted">(Optional)</span></label>
                                <select class="form-control select2" name="collaborators[]" multiple data-placeholder="-- Select Staff Members --">
                                    @foreach($users as $user)
                                    <option value="{{ $user->id }}" {{ (is_array(old('collaborators')) && in_array($user->id, old('collaborators'))) ? 'selected' : '' }}>
                                        {{ $user->name }} ({{ ucfirst($user->role) }})
                                    </option>
                                    @endforeach
                                </select>
                                <small class="text-muted mt-1 d-block">Select multiple staff to assign to this follow-up.</small>
                            </div>
                        </div>
                    </div>

                    {{-- SMS Reminder Section --}}
                    <div class="tile" style="border-left: 4px solid #940000; background: #fff8f8; border-radius: 6px; padding: 15px 20px; margin-bottom: 20px;">
                        <p class="font-weight-bold mb-2" style="color: #940000;"><i class="fa fa-bell"></i> SMS Reminder Settings</p>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="control-label">Reminder Date & Time <span class="text-muted">(Optional)</span></label>
                                    <div class="d-flex" style="gap: 10px;">
                                        <input class="form-control" type="date" name="reminder_date" value="{{ old('reminder_date') }}">
                                        <input class="form-control" type="time" name="reminder_time" value="{{ old('reminder_time', '07:00') }}">
                                    </div>
                                    <small class="text-muted mt-1 d-block">System sends an SMS at this date and time.</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="control-label">Remind Via</label>
                                    <select class="form-control select2-no-search" name="remind_via">
                                        <option value="assigned_user" {{ old('remind_via', 'assigned_user') == 'assigned_user' ? 'selected' : '' }}>Staff / Assigned User Only</option>
                                        <option value="customer" {{ old('remind_via') == 'customer' ? 'selected' : '' }}>Customer Only</option>
                                        <option value="both" {{ old('remind_via') == 'both' ? 'selected' : '' }}>Both Staff & Customer</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group mb-0">
                                    <label class="control-label">Custom SMS Message <span class="text-muted">(Optional)</span></label>
                                    <textarea class="form-control" name="reminder_message" rows="2" placeholder="E.g., Hi [Name], just a quick reminder about our upcoming meeting...">{{ old('reminder_message') }}</textarea>
                                    <small class="text-muted mt-1 d-block">If provided, this specific message will be sent instead of the default reminder.</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Workflow Optimizer --}}
                    <div class="form-group mb-4 pl-1">
                        <div class="animated-checkbox">
                            <label>
                                <input type="checkbox" name="schedule_next" value="1">
                                <span class="label-text font-weight-bold" style="color: #940000;">Save this record and immediately schedule the NEXT follow-up for this customer</span>
                            </label>
                        </div>
                        <small class="text-muted d-block mt-1">Recommended: Check this to quickly create a continuous history (Option B workflow).</small>
                    </div>

                    <div class="tile-footer">
                        <button class="btn btn-primary" type="submit"><i class="fa fa-fw fa-lg fa-check-circle"></i>Save</button>
                        <a class="btn btn-secondary" href="{{ route('follow-ups.index') }}"><i class="fa fa-fw fa-lg fa-times-circle"></i>Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @if(isset($customerHistory) && $customerHistory->count() > 0)
    <div class="col-md-4">
        <div class="tile">
            <h3 class="tile-title text-success"><i class="fa fa-history"></i> Recent History Saved</h3>
            <div class="tile-body">
                <p class="text-muted small">You just logged interaction(s) for this customer. Now, fill out the form on the left to schedule the NEXT pending follow-up.</p>
                <div class="timeline" style="border-left: 2px solid #ced4da; padding-left: 15px; margin-left: 10px;">
                    @foreach($customerHistory as $history)
                    <div class="mb-3">
                        <div class="font-weight-bold" style="color: #940000; position: relative;">
                            <span style="position: absolute; left: -22px; top: 3px; background: #940000; width: 10px; height: 10px; border-radius: 50%;"></span>
                            {{ \Carbon\Carbon::parse($history->visit_date)->format('M d, Y') }}
                        </div>
                        <div class="small"><strong>Status:</strong> {{ $history->status }}</div>
                        <div class="small"><strong>Purpose:</strong> {{ Str::limit($history->visit_purpose ?? 'N/A', 50) }}</div>
                        <div class="small text-muted mt-1"><em>Logged by {{ $history->assignedUser->name ?? 'System' }}</em></div>
                    </div>
                    @endforeach
                </div>
                <a href="{{ route('follow-ups.index') }}" class="btn btn-outline-secondary btn-sm btn-block mt-3">View Full History</a>
            </div>
        </div>
    </div>
    @endif
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    $(document).ready(function() {
        // Initialize Select2 with search
        $('.select2').select2({
            width: '100%'
        });

        // Initialize Select2 without search for small dropdowns
        $('.select2-no-search').select2({
            minimumResultsForSearch: Infinity,
            width: '100%'
        });
    });
</script>
@endpush
