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

@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="tile">
            <h3 class="tile-title">Send SMS</h3>
            <div class="tile-body">
                <form action="{{ route('sms.store') }}" method="POST" id="smsForm">
                    @csrf
                    <div class="form-group">
                        <label class="control-label">SMS Type <span class="text-danger">*</span></label>
                        <select class="form-control" name="sms_type" id="sms_type" required>
                            <option value="engagement">Engagement SMS</option>
                            <option value="holiday">Holiday SMS</option>
                            <option value="custom">Custom SMS</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="control-label">Send To <span class="text-danger">*</span></label>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="send_to" id="send_single" value="single" checked>
                            <label class="form-check-label" for="send_single">
                                Single Person / Phone Number
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="send_to" id="send_selected" value="selected">
                            <label class="form-check-label" for="send_selected">
                                Selected Customers
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="send_to" id="send_all" value="all">
                            <label class="form-check-label" for="send_all">
                                All Customers ({{ $customers->count() }} customers)
                            </label>
                        </div>
                    </div>

                    <!-- Single SMS Fields -->
                    <div id="single_fields">
                        <div class="form-group">
                            <label class="control-label">Select Customer <span class="text-muted">(Optional)</span></label>
                            <select class="form-control" name="customer_id" id="customer_id">
                                <option value="">-- Select Customer --</option>
                                @foreach($customers as $customer)
                                <option value="{{ $customer->id }}" data-phone="{{ $customer->phone_number }}" data-name="{{ $customer->name }}" {{ (isset($selectedCustomerId) && $selectedCustomerId == $customer->id) || request('customer_id') == $customer->id ? 'selected' : '' }}>
                                    {{ $customer->name ?? 'N/A' }} - {{ $customer->phone_number }}
                                </option>
                                @endforeach
                            </select>
                            <small class="form-text text-muted">Or enter phone number manually below</small>
                        </div>
                        <div class="form-group">
                            <label class="control-label">Phone Number <span class="text-danger" id="phone_required">*</span></label>
                            <input class="form-control" type="text" name="phone_number" id="phone_number" value="{{ old('phone_number') }}" placeholder="255612345678 or 0612345678">
                            <small class="form-text text-muted">Enter phone number with country code (e.g., 255612345678 or 0612345678). Required if no customer is selected above.</small>
                        </div>
                    </div>

                    <!-- Selected Customers Fields -->
                    <div id="selected_fields" style="display: none;">
                        <div class="form-group">
                            <label class="control-label">Select Customers <span class="text-danger">*</span></label>
                            <select class="form-control" name="selected_customers[]" id="selected_customers" multiple size="10" style="height: 200px;">
                                @foreach($customers as $customer)
                                <option value="{{ $customer->id }}">
                                    {{ $customer->name ?? 'N/A' }} - {{ $customer->phone_number }}
                                </option>
                                @endforeach
                            </select>
                            <small class="form-text text-muted">Hold Ctrl (Windows) or Cmd (Mac) to select multiple customers. Selected: <span id="selected_count">0</span></small>
                        </div>
                    </div>

                    <!-- All Customers Info -->
                    <div id="all_fields" style="display: none;">
                        <div class="alert alert-info">
                            <i class="fa fa-info-circle"></i> This will send SMS to all <strong>{{ $customers->count() }}</strong> customers in the system.
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="control-label">Message <span class="text-danger">*</span></label>
                        <textarea class="form-control" name="message" id="message" rows="5" placeholder="Enter your message here..." required>{{ old('message') }}</textarea>
                        <small class="form-text text-muted">Use {name} to personalize the message with customer name, {year} for current year</small>
                    </div>
                    <div class="form-group">
                        <button type="button" class="btn btn-info btn-sm" id="loadTemplate">Load Template</button>
                        <button type="button" class="btn btn-secondary btn-sm" id="clearMessage">Clear</button>
                        <button type="button" class="btn btn-success btn-sm" id="viewAllTemplates">View All Templates</button>
                    </div>
                    <div class="tile-footer">
                        <button class="btn btn-primary" type="submit"><i class="fa fa-fw fa-lg fa-paper-plane"></i>Send SMS</button>
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
<script>
    $(document).ready(function() {
        // Initialize form state
        updateFormFields();
        
        // Handle send_to radio buttons
        $('input[name="send_to"]').on('change', function() {
            updateFormFields();
        });

        function updateFormFields() {
            var sendTo = $('input[name="send_to"]:checked').val();
            
            // Hide all fields
            $('#single_fields').hide();
            $('#selected_fields').hide();
            $('#all_fields').hide();
            $('#phone_number').removeAttr('required');
            $('#selected_customers').removeAttr('required');
            $('#phone_number').val('');
            $('#customer_id').val('');
            
            // Show relevant fields
            if (sendTo === 'single') {
                $('#single_fields').show();
                $('#phone_required').show();
                // Phone number is required only if no customer is selected
            } else if (sendTo === 'selected') {
                $('#selected_fields').show();
                $('#selected_customers').attr('required', 'required');
            } else if (sendTo === 'all') {
                $('#all_fields').show();
            }
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
            
            if (!isValid) {
                e.preventDefault();
                swal({
                    title: "Validation Error",
                    text: errorMessage,
                    type: "error",
                    confirmButtonText: "OK"
                });
                return false;
            }
            
            // Show confirmation for bulk sends
            if (sendTo === 'selected') {
                var count = $('#selected_customers').val().length;
                if (count > 5) {
                    e.preventDefault();
                    swal({
                        title: "Confirm Bulk Send",
                        text: "You are about to send SMS to " + count + " customers. Continue?",
                        type: "warning",
                        showCancelButton: true,
                        confirmButtonText: "Yes, Send",
                        cancelButtonText: "Cancel"
                    }, function(isConfirm) {
                        if (isConfirm) {
                            $('#smsForm').off('submit').submit();
                        }
                    });
                    return false;
                }
            } else if (sendTo === 'all') {
                var totalCustomers = {{ $customers->count() }};
                if (totalCustomers > 5) {
                    e.preventDefault();
                    swal({
                        title: "Confirm Bulk Send",
                        text: "You are about to send SMS to ALL " + totalCustomers + " customers. Continue?",
                        type: "warning",
                        showCancelButton: true,
                        confirmButtonText: "Yes, Send to All",
                        cancelButtonText: "Cancel"
                    }, function(isConfirm) {
                        if (isConfirm) {
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
