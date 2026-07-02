@extends('layouts.app')

@section('title', 'Edit Scheduled SMS')
@section('icon', 'fa-clock-o')
@section('page-title', 'Edit Scheduled SMS')
@section('page-description', 'Update a scheduled SMS batch')

@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
<li class="breadcrumb-item"><a href="{{ route('sms.index') }}">SMS</a></li>
<li class="breadcrumb-item">Edit Schedule</li>
@endsection

@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="tile">
            <h3 class="tile-title">Edit Scheduled SMS</h3>
            <div class="tile-body">
                <div class="alert alert-info">
                    <strong>Target:</strong> {{ strtoupper($schedule->send_to) }}
                    <span class="text-muted">· Created {{ $schedule->created_at->format('M d, Y H:i') }}</span>
                </div>

                <form action="{{ route('sms.schedules.update', $schedule->id) }}" method="POST">
                    @csrf
                    @method('PUT')

                    @if($schedule->send_to === 'selected')
                    <div class="form-group">
                        <label class="control-label">Recipients <span class="text-danger">*</span></label>
                        <select class="form-control select2" name="selected_customers[]" id="selected_customers" multiple required>
                            @foreach(($customers ?? []) as $customer)
                                <option value="{{ $customer->id }}" {{ in_array($customer->id, $selectedCustomerIds ?? []) ? 'selected' : '' }}>
                                    {{ $customer->name ?? 'N/A' }} - {{ $customer->phone_number }}
                                </option>
                            @endforeach
                        </select>
                        <small class="form-text text-muted">Add/remove recipients for this scheduled batch.</small>
                    </div>
                    @endif

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="control-label">SMS Type <span class="text-danger">*</span></label>
                                <select class="form-control" name="sms_type" required>
                                    @foreach(['engagement','holiday','custom','other'] as $type)
                                        <option value="{{ $type }}" {{ $schedule->sms_type === $type ? 'selected' : '' }}>
                                            {{ ucfirst($type) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="control-label font-weight-bold">Schedule Date & Time <span class="text-danger">*</span></label>
                                <input class="form-control" type="datetime-local" name="scheduled_at"
                                       value="{{ optional($schedule->scheduled_at)->format('Y-m-d\\TH:i') }}" required>
                                <small class="form-text text-muted">Africa/Dar_es_Salaam timezone.</small>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="control-label">Message <span class="text-danger">*</span></label>
                        <textarea class="form-control" name="message_template" id="message_template" rows="6" required>{{ old('message_template', $schedule->message_template) }}</textarea>
                        <small class="form-text text-muted">Use {name} for customer name, {year} for current year</small>
                        <div class="mt-2 small text-muted">
                            <strong>Length:</strong> <span id="smsChars">0</span> chars
                            <span class="mx-2">·</span>
                            <strong>Parts:</strong> <span id="smsParts">1</span>
                            <span class="mx-2">·</span>
                            <strong>Encoding:</strong> <span id="smsEncoding">GSM-7</span>
                        </div>
                    </div>

                    @if($schedule->send_to === 'all')
                    <div class="form-group">
                        <div class="animated-checkbox">
                            <label>
                                <input type="checkbox" name="include_new_customers" value="1" checked>
                                <span class="label-text font-weight-bold" style="color: #940000;">
                                    Include customers who registered after the schedule was created
                                </span>
                            </label>
                        </div>
                    </div>
                    @endif

                    <div class="tile-footer">
                        <button class="btn btn-primary" type="submit">
                            <i class="fa fa-fw fa-lg fa-save"></i> Update Schedule
                        </button>
                        <a class="btn btn-secondary" href="{{ route('sms.index') }}">
                            <i class="fa fa-fw fa-lg fa-times-circle"></i> Back
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
@if($schedule->send_to === 'selected')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
@endif
<script>
    (function () {
        function isGsm7(text) {
            for (var i = 0; i < text.length; i++) {
                if (text.charCodeAt(i) > 127) return false;
            }
            return true;
        }

        function smsPartsFor(text) {
            var gsm7 = isGsm7(text);
            var single = gsm7 ? 160 : 70;
            var multi = gsm7 ? 153 : 67;
            var len = text.length;
            if (len <= single) return { parts: 1, encoding: gsm7 ? 'GSM-7' : 'Unicode' };
            return { parts: Math.ceil(len / multi), encoding: gsm7 ? 'GSM-7' : 'Unicode' };
        }

        function updateSmsMetrics() {
            var el = document.getElementById('message_template');
            if (!el) return;
            var text = (el.value || '').toString();
            var metrics = smsPartsFor(text);
            document.getElementById('smsChars').textContent = String(text.length);
            document.getElementById('smsParts').textContent = String(metrics.parts);
            document.getElementById('smsEncoding').textContent = metrics.encoding;
        }

        document.addEventListener('DOMContentLoaded', function () {
            @if($schedule->send_to === 'selected')
            if (window.jQuery) {
                $('#selected_customers').select2({
                    width: '100%',
                    placeholder: 'Select Customers'
                });
            }
            @endif

            var el = document.getElementById('message_template');
            if (el) {
                el.addEventListener('input', updateSmsMetrics);
                updateSmsMetrics();
            }
        });
    })();
</script>
@endpush

