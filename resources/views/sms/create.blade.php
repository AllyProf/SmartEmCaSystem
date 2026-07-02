@extends('layouts.app')

@section('title', 'Send SMS')
@section('icon', 'fa-comment')
@section('page-title', 'Send SMS')
@section('page-description', 'Send SMS to customers')

@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
<li class="breadcrumb-item"><a href="{{ route('sms.index') }}">SMS</a></li>
<li class="breadcrumb-item">Send SMS</li>
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
    .select2-container--default .select2-selection--multiple {
        border: 1px solid #ced4da;
    }
    .btn-group-toggle .btn {
        padding: 8px 15px;
        font-weight: 600;
        border-width: 2px;
    }
    .btn-group-toggle .btn.active {
        background-color: #940000;
        border-color: #940000;
        color: #fff;
    }
    .btn-outline-primary {
        color: #940000;
        border-color: #940000;
    }
    .btn-outline-primary:hover {
        background-color: #940000;
        border-color: #940000;
        color: #fff;
    }
    /* Select2 Brand Styling */
    .select2-container--default .select2-results__option--highlighted[aria-selected] {
        background-color: #940000;
    }
    .select2-container--default .select2-selection--multiple .select2-selection__choice {
        background-color: #940000;
        border-color: #7a0000;
        color: #fff;
    }
    .select2-container--default .select2-selection--multiple .select2-selection__choice__remove {
        color: #fff;
        margin-right: 5px;
    }
    .select2-container--default .select2-selection--multiple .select2-selection__choice__remove:hover {
        color: #eee;
    }
</style>
@endpush

@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="tile">
            <h3 class="tile-title">Send SMS</h3>
            <div class="tile-body">
                <form action="{{ route('sms.store') }}" method="POST" id="smsForm">
                    @csrf
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="control-label">SMS Type <span class="text-danger">*</span></label>
                                <select class="form-control" name="sms_type" id="sms_type" required>
                                    <option value="engagement">Engagement SMS</option>
                                    <option value="holiday">Holiday SMS</option>
                                    <option value="custom">Custom SMS</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="control-label">Send To <span class="text-danger">*</span></label>
                                <div class="btn-group btn-group-toggle d-flex" data-toggle="buttons">
                                    <label class="btn btn-outline-primary active flex-fill">
                                        <input type="radio" name="send_to" id="send_single" value="single" checked autocomplete="off"> 
                                        <i class="fa fa-user"></i> Single
                                    </label>
                                    <label class="btn btn-outline-primary flex-fill">
                                        <input type="radio" name="send_to" id="send_selected" value="selected" autocomplete="off"> 
                                        <i class="fa fa-users"></i> Selected
                                    </label>
                                    <label class="btn btn-outline-primary flex-fill">
                                        <input type="radio" name="send_to" id="send_all" value="all" autocomplete="off"> 
                                        <i class="fa fa-globe"></i> All
                                    </label>
                                </div>
                                @if(!(auth()->user()?->isSuperAdmin() || auth()->user()?->isCeo()))
                                    <small class="text-muted d-block mt-2">
                                        Bulk SMS to <strong>All</strong> customers is restricted to Super Admin / CEO.
                                    </small>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="row" id="recipient_row">
                        <div class="col-md-6" id="customer_selection_col">
                            <!-- Single SMS Fields -->
                            <div id="single_customer_div">
                                <div class="form-group">
                                    <label class="control-label">Select Customer <span class="text-muted">(Optional)</span></label>
                                    <select class="form-control select2" name="customer_id" id="customer_id">
                                        <option value="">-- Select Customer --</option>
                                        @foreach($customers as $customer)
                                        <option value="{{ $customer->id }}" data-phone="{{ $customer->phone_number }}" data-name="{{ $customer->name }}" {{ (isset($selectedCustomerId) && $selectedCustomerId == $customer->id) || request('customer_id') == $customer->id ? 'selected' : '' }}>
                                            {{ $customer->name ?? 'N/A' }} - {{ $customer->phone_number }}
                                        </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            
                            <!-- Selected Customers Fields -->
                            <div id="selected_customers_div" style="display: none;">
                                <div class="form-group">
                                    <label class="control-label">Select Customers <span class="text-danger">*</span></label>
                                    <select class="form-control select2" name="selected_customers[]" id="selected_customers" multiple>
                                        @foreach($customers as $customer)
                                        <option value="{{ $customer->id }}">
                                            {{ $customer->name ?? 'N/A' }} - {{ $customer->phone_number }}
                                        </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6" id="phone_number_col">
                            <div class="form-group">
                                <label class="control-label">Phone Number <span class="text-danger" id="phone_required">*</span></label>
                                <input class="form-control" type="text" name="phone_number" id="phone_number" value="{{ old('phone_number', '+255') }}" placeholder="+255612345678">
                                <small class="form-text text-muted" id="phone_helper">Required if no customer selected.</small>
                            </div>
                        </div>
                    </div>

                    <div id="all_customers_alert" style="display: none;">
                        <div class="alert" style="background-color: #f8f9fa; border: 1px solid #ddd;">
                            <i class="fa fa-info-circle"></i> <span id="all_customers_alert_text">This will send SMS to all <strong>{{ $customers->count() }}</strong> customers.</span>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="control-label">Message <span class="text-danger">*</span></label>
                        <textarea class="form-control" name="message" id="message" rows="5" placeholder="Enter your message here..." required>{{ old('message') }}</textarea>
                        <small class="form-text text-muted">Use {name} for customer name, {year} for current year</small>
                        <div class="mt-2 small text-muted" id="smsMetrics">
                            <strong>Length:</strong> <span id="smsChars">0</span> chars
                            <span class="mx-2">·</span>
                            <strong>Parts:</strong> <span id="smsParts">1</span>
                            <span class="mx-2">·</span>
                            <strong>Encoding:</strong> <span id="smsEncoding">GSM-7</span>
                        </div>
                    </div>

                    <!-- Scheduling Options -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <div class="form-group">
                                <div class="animated-checkbox">
                                    <label>
                                        <input type="checkbox" name="is_scheduled" id="is_scheduled" value="1">
                                        <span class="label-text font-weight-bold" style="color: #940000;"><i class="fa fa-clock-o"></i> Schedule this SMS to be sent later</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6" id="schedule_time_container" style="display: none;">
                            <div class="form-group">
                                <label class="control-label font-weight-bold">Schedule Date & Time <span class="text-danger">*</span></label>
                                <input class="form-control" type="datetime-local" name="scheduled_at" id="scheduled_at">
                                <small class="form-text text-muted">Select when the SMS should be sent (Africa/Dar_es_Salaam timezone).</small>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <button type="button" class="btn btn-primary btn-sm" id="loadTemplate">Load Template</button>
                        <button type="button" class="btn btn-secondary btn-sm" id="clearMessage">Clear</button>
                        <button type="button" class="btn btn-primary btn-sm" id="viewAllTemplates">View All Templates</button>
                    </div>

                    <div class="tile-footer">
                        <button class="btn btn-primary" type="submit" id="submitSmsBtn"><i class="fa fa-fw fa-lg fa-paper-plane"></i><span id="submitSmsBtnText">Send SMS</span></button>
                        <a class="btn btn-secondary" href="{{ route('sms.index') }}"><i class="fa fa-fw fa-lg fa-times-circle"></i>Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Template Modal -->
<div class="modal fade" id="templateModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">SMS Templates</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="templateList"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    $(document).ready(function() {
        // Initialize Select2
        $('.select2').select2({
            width: '100%'
        });

        $('#customer_id').select2({
            placeholder: "-- Select Customer --",
            allowClear: true,
            width: '100%'
        });

        $('#selected_customers').select2({
            placeholder: "Select Customers",
            width: '100%'
        });

        // Initialize form state
        updateFormFields();
        
        // Handle send_to radio buttons
        $('input[name="send_to"]').on('change', function() {
            updateFormFields();
        });

        function updateFormFields() {
            var sendTo = $('input[name="send_to"]:checked').val();
            
            // Default visibility
            $('#recipient_row').show();
            $('#single_customer_div').hide();
            $('#selected_customers_div').hide();
            $('#all_customers_alert').hide();
            $('#phone_number_col').show();
            
            $('#phone_number').removeAttr('required');
            $('#selected_customers').removeAttr('required');
            
            // Show relevant fields
            if (sendTo === 'single') {
                $('#single_customer_div').show();
                $('#phone_required').show();
                if (!$('#phone_number').val()) {
                    $('#phone_number').val('+255');
                }
            } else if (sendTo === 'selected') {
                $('#selected_customers_div').show();
                $('#selected_customers').attr('required', 'required');
                $('#phone_number_col').hide();
            } else if (sendTo === 'all') {
                $('#recipient_row').hide();
                $('#all_customers_alert').show();
            }

            updateScheduleUI();
            updateSmsMetrics();
        }

        // Update selected count
        $('#selected_customers').on('change', function() {
            var count = $(this).val() ? $(this).val().length : 0;
            $('#selected_count').text(count);
        });

        // Auto-fill phone number when customer is selected
        $('#customer_id').on('change', function() {
            var selectedOption = $(this).find('option:selected');
            var phone = selectedOption.data('phone');
            if (phone) {
                $('#phone_number').val(phone);
            } else {
                // Only reset to +255 if it was changed by a customer selection
                if ($('input[name="send_to"]:checked').val() === 'single' && !$(this).val()) {
                    $('#phone_number').val('+255');
                }
            }
        });

        // Load template based on SMS type
        $('#loadTemplate').on('click', function() {
            var smsType = $('#sms_type').val();
            var customerName = $('#customer_id option:selected').data('name') || '';
            
            $.get('{{ route("sms.templates") }}', { type: smsType }, function(data) {
                if (data.templates && data.templates.length > 0) {
                    var template = data.templates[0];
                    template = template.replace('{year}', new Date().getFullYear());
                    if (customerName) {
                        template = template.replace('{name}', customerName);
                    }
                    $('#message').val(template);
                }
            });
        });

        // View all templates
        $('#viewAllTemplates').on('click', function() {
            var smsType = $('#sms_type').val();
            
            $.get('{{ route("sms.templates") }}', { type: smsType }, function(data) {
                var html = '<h6>' + smsType.charAt(0).toUpperCase() + smsType.slice(1) + ' Templates:</h6><ul class="list-group">';
                if (data.templates && data.templates.length > 0) {
                    data.templates.forEach(function(template, index) {
                        html += '<li class="list-group-item" style="cursor: pointer;" onclick="$(\'#message\').val(\'' + template.replace(/'/g, "\\'") + '\'); $(\'#templateModal\').modal(\'hide\');">';
                        html += '<strong>Template ' + (index + 1) + ':</strong><br>' + template;
                        html += '</li>';
                    });
                }
                html += '</ul>';
                $('#templateList').html(html);
                $('#templateModal').modal('show');
            });
        });

        // Clear message
        $('#clearMessage').on('click', function() {
            $('#message').val('');
        });

        function isGsm7(text) {
            // Practical estimation: treat non-ASCII as Unicode.
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
            var text = ($('#message').val() || '').toString();
            var metrics = smsPartsFor(text);
            $('#smsChars').text(text.length);
            $('#smsParts').text(metrics.parts);
            $('#smsEncoding').text(metrics.encoding);
        }

        $('#message').on('input', updateSmsMetrics);
        updateSmsMetrics();

        // Handle scheduled checkbox change
        $('#is_scheduled').on('change', function() {
            if ($(this).is(':checked')) {
                $('#schedule_time_container').show();
                $('#scheduled_at').attr('required', 'required');
            } else {
                $('#schedule_time_container').hide();
                $('#scheduled_at').removeAttr('required').val('');
            }
            updateScheduleUI();
            updateSmsMetrics();
        });

        function updateScheduleUI() {
            var isScheduled = $('#is_scheduled').is(':checked');
            var totalCustomers = {{ $customers->count() }};

            if (isScheduled) {
                $('#submitSmsBtnText').text('Schedule SMS');
                $('#submitSmsBtn i').removeClass('fa-paper-plane').addClass('fa-clock-o');
                $('#all_customers_alert_text').html('This will <strong>schedule</strong> SMS for all <strong>' + totalCustomers + '</strong> customers.');
            } else {
                $('#submitSmsBtnText').text('Send SMS');
                $('#submitSmsBtn i').removeClass('fa-clock-o').addClass('fa-paper-plane');
                $('#all_customers_alert_text').html('This will send SMS to all <strong>' + totalCustomers + '</strong> customers.');
            }
        }

        // Form validation
        $('#smsForm').on('submit', function(e) {
            var sendTo = $('input[name="send_to"]:checked').val();
            var isValid = true;
            var errorMessage = '';
            
            if (sendTo === 'single') {
                var phoneNumber = $('#phone_number').val().trim();
                var customerId = $('#customer_id').val();
                
                if (!phoneNumber && !customerId) {
                    isValid = false;
                    errorMessage = 'Please select a customer from the dropdown OR enter a phone number manually';
                } else if (phoneNumber) {
                    // Validate phone number format if provided
                    var cleanNumber = phoneNumber.replace(/[^0-9]/g, '');
                    if (cleanNumber.length < 9 || cleanNumber.length > 12) {
                        isValid = false;
                        errorMessage = 'Phone number must be between 9 and 12 digits';
                    }
                }
            } else if (sendTo === 'selected') {
                var selected = $('#selected_customers').val();
                if (!selected || selected.length === 0) {
                    isValid = false;
                    errorMessage = 'Please select at least one customer';
                }
            } else if (sendTo === 'all') {
                var totalCustomers = {{ $customers->count() }};
                if (totalCustomers === 0) {
                    isValid = false;
                    errorMessage = 'No customers found in the system';
                }
            }
            
            // Schedule validation
            if (isValid && $('#is_scheduled').is(':checked')) {
                var scheduledAtVal = $('#scheduled_at').val();
                if (!scheduledAtVal) {
                    isValid = false;
                    errorMessage = 'Please select a schedule date and time';
                } else {
                    var scheduledDate = new Date(scheduledAtVal);
                    var now = new Date();
                    if (scheduledDate <= now) {
                        isValid = false;
                        errorMessage = 'Schedule date and time must be in the future';
                    }
                }
            }
            
            if (!isValid) {
                e.preventDefault();
                Swal.fire({
                    title: "Validation Error",
                    text: errorMessage,
                    icon: "error",
                    confirmButtonText: "OK"
                });
                return false;
            }
            
            // Show confirmation for bulk sends/schedules
            var isScheduled = $('#is_scheduled').is(':checked');
            var actionVerb = isScheduled ? 'schedule' : 'send';
            var confirmTitle = isScheduled ? 'Confirm Bulk Schedule' : 'Confirm Bulk Send';
            var confirmYes = isScheduled ? 'Yes, Schedule' : 'Yes, Send';
            var confirmYesAll = isScheduled ? 'Yes, Schedule All' : 'Yes, Send to All';

            if (sendTo === 'selected') {
                var count = $('#selected_customers').val().length;
                if (count > 5) {
                    e.preventDefault();
                    Swal.fire({
                        title: confirmTitle,
                        text: "You are about to " + actionVerb + " SMS to " + count + " customers. Continue?",
                        icon: "warning",
                        showCancelButton: true,
                        confirmButtonColor: '#940000',
                        confirmButtonText: confirmYes,
                        cancelButtonText: "Cancel"
                    }).then(function(result) {
                        if (result.isConfirmed) {
                            $('#smsForm').off('submit').submit();
                        }
                    });
                    return false;
                }
            } else if (sendTo === 'all') {
                var totalCustomers = {{ $customers->count() }};
                if (totalCustomers > 5) {
                    e.preventDefault();
                    Swal.fire({
                        title: confirmTitle,
                        text: "You are about to " + actionVerb + " SMS to ALL " + totalCustomers + " customers. Continue?",
                        icon: "warning",
                        showCancelButton: true,
                        confirmButtonColor: '#940000',
                        confirmButtonText: confirmYesAll,
                        cancelButtonText: "Cancel"
                    }).then(function(result) {
                        if (result.isConfirmed) {
                            $('#smsForm').off('submit').submit();
                        }
                    });
                    return false;
                }
            }
        });
    });
</script>
@endpush
