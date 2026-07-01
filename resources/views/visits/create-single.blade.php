@extends('layouts.visitor')

@section('title', 'Customer Visit Confirmation')
@section('header', 'CUSTOMER VISIT CONFIRMATION FORM')

@push('styles')
<style>
    .wizard-progress {
        display: flex;
        justify-content: space-between;
        margin-bottom: 30px;
        position: relative;
    }
    .wizard-progress::before {
        content: '';
        position: absolute;
        top: 18px;
        left: 10%;
        right: 10%;
        height: 3px;
        background: #e9ecef;
        z-index: 0;
    }
    .wizard-step-indicator {
        flex: 1;
        text-align: center;
        position: relative;
        z-index: 1;
    }
    .wizard-step-indicator .step-circle {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        background: #e9ecef;
        color: #6c757d;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        margin-bottom: 6px;
        transition: all 0.3s ease;
        border: 3px solid #fff;
        box-shadow: 0 0 0 2px #e9ecef;
    }
    .wizard-step-indicator.active .step-circle,
    .wizard-step-indicator.completed .step-circle {
        background: #940000;
        color: #fff;
        box-shadow: 0 0 0 2px #940000;
    }
    .wizard-step-indicator.completed .step-circle {
        background: #7a0000;
    }
    .wizard-step-indicator .step-label {
        font-size: 0.75rem;
        color: #6c757d;
        font-weight: 600;
        text-transform: uppercase;
    }
    .wizard-step-indicator.active .step-label {
        color: #940000;
    }
    .wizard-step {
        display: none;
        animation: fadeIn 0.3s ease;
    }
    .wizard-step.active {
        display: block;
    }
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(8px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .wizard-nav {
        display: flex;
        justify-content: space-between;
        margin-top: 30px;
        padding-top: 20px;
        border-top: 1px solid #e9ecef;
    }
    @media (max-width: 576px) {
        .wizard-step-indicator .step-label {
            font-size: 0.65rem;
        }
        .wizard-step-indicator .step-circle {
            width: 30px;
            height: 30px;
            font-size: 0.85rem;
        }
    }
</style>
@endpush

@section('content')
<form action="{{ route('visits.store') }}" method="POST" id="visitForm" novalidate>
    @csrf
    <input type="hidden" name="type" value="single">

    <!-- Wizard Progress -->
    <div class="wizard-progress">
        <div class="wizard-step-indicator active" data-indicator="1">
            <div class="step-circle">1</div>
            <div class="step-label">Visit</div>
        </div>
        <div class="wizard-step-indicator" data-indicator="2">
            <div class="step-circle">2</div>
            <div class="step-label">Customer</div>
        </div>
        <div class="wizard-step-indicator" data-indicator="3">
            <div class="step-circle">3</div>
            <div class="step-label">Details</div>
        </div>
        <div class="wizard-step-indicator" data-indicator="4">
            <div class="step-circle">4</div>
            <div class="step-label">Sign</div>
        </div>
    </div>

    <!-- Step 1: Visit Details -->
    <div class="wizard-step active" data-step="1">
        <div class="card mb-0 shadow-sm border-0 bg-light">
            <div class="card-header bg-dark text-white font-weight-bold">VISIT DETAILS</div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Visit Date <span class="text-danger">*</span></label>
                            <input class="form-control" type="date" name="visit_date" value="{{ date('Y-m-d') }}" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Location / Office <span class="text-danger">*</span></label>
                            <input class="form-control" type="text" name="location" placeholder="e.g. Moshi Tech Hub" required>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Step 2: Customer Information -->
    <div class="wizard-step" data-step="2">
        <div class="card mb-0 shadow-sm border-0 bg-light">
            <div class="card-header bg-dark text-white font-weight-bold">CUSTOMER INFORMATION</div>
            <div class="card-body">
                <div class="form-group">
                    <label>Customer / Organization Name <span class="text-danger">*</span></label>
                    <input class="form-control" type="text" name="customer_name" required>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Contact Person / Title <span class="text-danger">*</span></label>
                            <input class="form-control" type="text" name="contact_person" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Phone Number <span class="text-danger">*</span></label>
                            <input class="form-control" type="tel" name="phone" id="phone" value="255" placeholder="e.g. 255712345678" maxlength="12" inputmode="numeric" required>
                        </div>
                    </div>
                </div>
                <div class="form-group mb-0">
                    <label>Email Address (Optional)</label>
                    <input class="form-control" type="email" name="email">
                </div>
            </div>
        </div>
    </div>

    <!-- Step 3: Visit Purpose & Representative -->
    <div class="wizard-step" data-step="3">
        <div class="card mb-4 shadow-sm border-0">
            <div class="card-header bg-secondary text-white font-weight-bold">EMCA REPRESENTATIVE (Logged In)</div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Name</label>
                            <input class="form-control" type="text" name="representative_name" value="{{ $staff->name }}" readonly>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Title / Position</label>
                            <input class="form-control" type="text" name="representative_title" value="{{ $staff->role_label }}" readonly>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="form-group">
            <label class="font-weight-bold text-uppercase">Purpose of Visit <span class="text-danger">*</span></label>
            <textarea class="form-control" name="purpose" rows="3" placeholder="Brief description of the visit objective..." required></textarea>
        </div>

        <div class="form-group">
            <label class="font-weight-bold text-uppercase">Customer Feedback / Suggestions</label>
            <textarea class="form-control" name="feedback" rows="3" placeholder="Any comments or suggestions from the customer?"></textarea>
        </div>

        <div class="form-group mb-0">
            <label class="font-weight-bold text-uppercase">Satisfaction Level</label>
            <div class="d-flex flex-wrap justify-content-between mt-2">
                <label class="radio-inline mr-3"><input type="radio" name="satisfaction_level" value="Very Satisfied"> Very Satisfied</label>
                <label class="radio-inline mr-3"><input type="radio" name="satisfaction_level" value="Satisfied" checked> Satisfied</label>
                <label class="radio-inline mr-3"><input type="radio" name="satisfaction_level" value="Average"> Average</label>
                <label class="radio-inline"><input type="radio" name="satisfaction_level" value="Unsatisfied"> Unsatisfied</label>
            </div>
        </div>
    </div>

    <!-- Step 4: Signatures -->
    <div class="wizard-step" data-step="4">
        <div class="card mb-0 shadow-sm border-0" style="background-color: #f8f9fa;">
            <div class="card-header font-weight-bold">CONFIRMATION | I confirm that the visit took place as stated above.</div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 border-right">
                        <h5 class="text-center">Customer <span class="text-danger">*</span></h5>
                        <div class="signature-wrapper" style="border: 2px dashed #ccc; background: white; height: 200px; position: relative; border-radius: 5px;">
                            <canvas id="customerSignatureCanvas" style="width: 100%; height: 100%; touch-action: none;"></canvas>
                            <div style="position: absolute; bottom: 5px; right: 5px;">
                                <button type="button" class="btn btn-sm btn-link text-danger" id="clearCustomer">Clear</button>
                            </div>
                        </div>
                        <input type="hidden" name="customer_signature" id="customerSignatureInput">
                    </div>

                    <div class="col-md-6 mt-4 mt-md-0">
                        <h5 class="text-center">EmCa Representative <span class="text-danger">*</span></h5>
                        <div class="signature-wrapper" style="border: 2px dashed #ccc; background: white; height: 200px; position: relative; border-radius: 5px;">
                            <canvas id="repSignatureCanvas" style="width: 100%; height: 100%; touch-action: none;"></canvas>
                            <div style="position: absolute; bottom: 5px; right: 5px;">
                                <button type="button" class="btn btn-sm btn-link text-danger" id="clearRep">Clear</button>
                            </div>
                        </div>
                        <input type="hidden" name="representative_signature" id="repSignatureInput">
                    </div>
                </div>
                <p class="text-center text-muted mt-3 mb-0"><small>Digital signatures will be captured upon submission.</small></p>
            </div>
        </div>
    </div>

    <!-- Wizard Navigation -->
    <div class="wizard-nav">
        <button type="button" class="btn btn-outline-secondary" id="prevBtn" style="visibility: hidden;">
            <i class="fa fa-arrow-left"></i> Previous
        </button>
        <button type="button" class="btn btn-primary px-4" id="nextBtn">
            Next <i class="fa fa-arrow-right"></i>
        </button>
        <button type="submit" class="btn btn-primary btn-lg px-4 shadow" id="submitBtn" style="display: none;">
            <i class="fa fa-check-circle"></i> Submit Confirmation
        </button>
    </div>
</form>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/signature_pad@4.0.0/dist/signature_pad.umd.min.js"></script>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        let currentStep = 1;
        const totalSteps = 4;
        let signaturePadsInitialized = false;
        let customerPad, repPad;
        const customerCanvas = document.getElementById('customerSignatureCanvas');
        const repCanvas = document.getElementById('repSignatureCanvas');

        const prevBtn = document.getElementById('prevBtn');
        const nextBtn = document.getElementById('nextBtn');
        const submitBtn = document.getElementById('submitBtn');
        const visitForm = document.getElementById('visitForm');

        function resizeCanvas(canvas) {
            const ratio = Math.max(window.devicePixelRatio || 1, 1);
            canvas.width = canvas.offsetWidth * ratio;
            canvas.height = canvas.offsetHeight * ratio;
            canvas.getContext("2d").scale(ratio, ratio);
        }

        function initSignaturePads() {
            if (signaturePadsInitialized) {
                const customerData = customerPad.toData();
                const repData = repPad.toData();
                resizeCanvas(customerCanvas);
                resizeCanvas(repCanvas);
                customerPad.fromData(customerData);
                repPad.fromData(repData);
                return;
            }

            resizeCanvas(customerCanvas);
            resizeCanvas(repCanvas);

            customerPad = new SignaturePad(customerCanvas, {
                backgroundColor: 'rgb(255, 255, 255)'
            });
            repPad = new SignaturePad(repCanvas, {
                backgroundColor: 'rgb(255, 255, 255)'
            });

            document.getElementById('clearCustomer').addEventListener('click', () => customerPad.clear());
            document.getElementById('clearRep').addEventListener('click', () => repPad.clear());

            signaturePadsInitialized = true;
        }

        function updateWizardUI() {
            document.querySelectorAll('.wizard-step').forEach(step => {
                step.classList.toggle('active', parseInt(step.dataset.step) === currentStep);
            });

            document.querySelectorAll('.wizard-step-indicator').forEach(indicator => {
                const stepNum = parseInt(indicator.dataset.indicator);
                indicator.classList.remove('active', 'completed');
                if (stepNum === currentStep) {
                    indicator.classList.add('active');
                } else if (stepNum < currentStep) {
                    indicator.classList.add('completed');
                }
            });

            prevBtn.style.visibility = currentStep === 1 ? 'hidden' : 'visible';
            nextBtn.style.display = currentStep === totalSteps ? 'none' : 'inline-block';
            submitBtn.style.display = currentStep === totalSteps ? 'inline-block' : 'none';

            if (currentStep === totalSteps) {
                setTimeout(initSignaturePads, 50);
            }

            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        function isValidPhone(value) {
            const digits = value.replace(/\D/g, '');
            return /^255\d{9}$/.test(digits);
        }

        const phoneInput = document.getElementById('phone');
        const phonePrefix = '255';
        const phoneMaxLength = 12;

        function enforcePhonePrefix() {
            let digits = phoneInput.value.replace(/\D/g, '');

            if (!digits.startsWith(phonePrefix)) {
                digits = phonePrefix + digits.replace(/^255/, '');
            }

            phoneInput.value = digits.slice(0, phoneMaxLength);
        }

        phoneInput.addEventListener('input', function() {
            this.setCustomValidity('');
            enforcePhonePrefix();
        });

        phoneInput.addEventListener('focus', function() {
            if (!this.value.replace(/\D/g, '').startsWith(phonePrefix)) {
                this.value = phonePrefix;
            }
        });

        phoneInput.addEventListener('keydown', function(e) {
            const cursorPos = this.selectionStart;
            if ((e.key === 'Backspace' || e.key === 'Delete') && cursorPos <= phonePrefix.length) {
                e.preventDefault();
            }
        });

        function validateStep(step) {
            const stepEl = document.querySelector(`.wizard-step[data-step="${step}"]`);
            const inputs = stepEl.querySelectorAll('input, textarea, select');
            let valid = true;

            inputs.forEach(input => {
                if (input.readOnly || input.type === 'hidden' || input.type === 'radio') {
                    return;
                }

                if (input.name === 'phone') {
                    if (!isValidPhone(input.value)) {
                        input.setCustomValidity('Enter 9 digits after 255 (e.g. 255712345678).');
                        input.reportValidity();
                        valid = false;
                        return;
                    }
                    input.setCustomValidity('');
                }

                if (!input.checkValidity()) {
                    input.reportValidity();
                    valid = false;
                }
            });

            return valid;
        }

        nextBtn.addEventListener('click', function() {
            if (!validateStep(currentStep)) {
                return;
            }
            if (currentStep < totalSteps) {
                currentStep++;
                updateWizardUI();
            }
        });

        prevBtn.addEventListener('click', function() {
            if (currentStep > 1) {
                currentStep--;
                updateWizardUI();
            }
        });

        visitForm.addEventListener('submit', function(e) {
            if (!signaturePadsInitialized) {
                initSignaturePads();
            }

            if (customerPad.isEmpty()) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Signature Required',
                    text: 'Please provide customer signature.',
                    confirmButtonColor: '#940000'
                });
                e.preventDefault();
                return;
            }
            if (repPad.isEmpty()) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Signature Required',
                    text: 'Please provide EmCa representative signature.',
                    confirmButtonColor: '#940000'
                });
                e.preventDefault();
                return;
            }

            document.getElementById('customerSignatureInput').value = customerPad.toDataURL();
            document.getElementById('repSignatureInput').value = repPad.toDataURL();
        });

        window.addEventListener("resize", () => {
            if (!signaturePadsInitialized) return;

            const customerData = customerPad.toData();
            const repData = repPad.toData();

            resizeCanvas(customerCanvas);
            resizeCanvas(repCanvas);

            customerPad.fromData(customerData);
            repPad.fromData(repData);
        });
    });
</script>
@endpush
