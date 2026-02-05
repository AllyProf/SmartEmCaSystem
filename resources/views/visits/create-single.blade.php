@extends('layouts.visitor')

@section('title', 'Customer Visit Confirmation')
@section('header', 'CUSTOMER VISIT CONFIRMATION FORM')

@section('content')
<form action="{{ route('visits.store') }}" method="POST" id="visitForm">
    @csrf
    <input type="hidden" name="type" value="single">

    <div class="row">
        <div class="col-md-6">
            <div class="form-group">
                <label>Visit Date</label>
                <input class="form-control" type="date" name="visit_date" value="{{ date('Y-m-d') }}" required>
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label>Location / Office</label>
                <input class="form-control" type="text" name="location" placeholder="e.g. Moshi Tech Hub" required>
            </div>
        </div>
    </div>

    <!-- Group Information Card -->
    <div class="card mb-4 shadow-sm border-0 bg-light">
        <div class="card-header bg-dark text-white font-weight-bold">CUSTOMER INFORMATION</div>
        <div class="card-body">
            <div class="form-group">
                <label>Customer / Organization Name</label>
                <input class="form-control" type="text" name="customer_name" required>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Contact Person / Title</label>
                        <input class="form-control" type="text" name="contact_person" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Phone Number</label>
                        <input class="form-control" type="text" name="phone" value="+255" required>
                    </div>
                </div>
            </div>
            <div class="form-group">
                <label>Email Address (Optional)</label>
                <input class="form-control" type="email" name="email">
            </div>
        </div>
    </div>

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
                        <input class="form-control" type="text" name="representative_title" placeholder="e.g. Sales Executive" required>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="form-group">
        <label class="font-weight-bold text-uppercase">Purpose of Visit</label>
        <textarea class="form-control" name="purpose" rows="3" placeholder="Brief description of the visit objective..." required></textarea>
    </div>

    <div class="form-group">
        <label class="font-weight-bold text-uppercase">Customer Feedback / Suggestions</label>
        <textarea class="form-control" name="feedback" rows="3" placeholder="Any comments or suggestions from the customer?"></textarea>
    </div>

    <div class="form-group">
        <label class="font-weight-bold text-uppercase">Satisfaction Level</label>
        <div class="d-flex flex-wrap justify-content-between mt-2">
            <label class="radio-inline mr-3"><input type="radio" name="satisfaction_level" value="Very Satisfied"> Very Satisfied</label>
            <label class="radio-inline mr-3"><input type="radio" name="satisfaction_level" value="Satisfied" checked> Satisfied</label>
            <label class="radio-inline mr-3"><input type="radio" name="satisfaction_level" value="Average"> Average</label>
            <label class="radio-inline"><input type="radio" name="satisfaction_level" value="Unsatisfied"> Unsatisfied</label>
        </div>
    </div>

    <!-- Confirmation & Signatures -->
    <div class="card mb-4 shadow-sm border-0" style="background-color: #f8f9fa;">
        <div class="card-header font-weight-bold">CONFIRMATION | I confirm that the visit took place as stated above.</div>
        <div class="card-body">
            <div class="row">
                <!-- Customer Signature -->
                <div class="col-md-6 border-right">
                    <h5 class="text-center">Customer</h5>
                    <div class="signature-wrapper" style="border: 2px dashed #ccc; background: white; height: 200px; position: relative; border-radius: 5px;">
                        <canvas id="customerSignatureCanvas" style="width: 100%; height: 100%; touch-action: none;"></canvas>
                        <div style="position: absolute; bottom: 5px; right: 5px;">
                            <button type="button" class="btn btn-sm btn-link text-danger" id="clearCustomer">Clear</button>
                        </div>
                    </div>
                    <input type="hidden" name="customer_signature" id="customerSignatureInput">
                </div>

                <!-- Rep Signature -->
                <div class="col-md-6 mt-4 mt-md-0">
                    <h5 class="text-center">EmCa Representative</h5>
                    <div class="signature-wrapper" style="border: 2px dashed #ccc; background: white; height: 200px; position: relative; border-radius: 5px;">
                        <canvas id="repSignatureCanvas" style="width: 100%; height: 100%; touch-action: none;"></canvas>
                        <div style="position: absolute; bottom: 5px; right: 5px;">
                            <button type="button" class="btn btn-sm btn-link text-danger" id="clearRep">Clear</button>
                        </div>
                    </div>
                    <input type="hidden" name="representative_signature" id="repSignatureInput">
                </div>
            </div>
        </div>
    </div>

    <div class="text-center mt-5">
        <button type="submit" class="btn btn-primary btn-lg px-5 shadow" id="submitBtn">
            <i class="fa fa-check-circle"></i> Submit Confirmation
        </button>
        <p class="mt-2 text-muted"><small>Digital signatures will be captured upon submission.</small></p>
    </div>
</form>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/signature_pad@4.0.0/dist/signature_pad.umd.min.js"></script>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        const customerCanvas = document.getElementById('customerSignatureCanvas');
        const repCanvas = document.getElementById('repSignatureCanvas');

        // Adjust canvas sizing
        function resizeCanvas(canvas) {
            const ratio = Math.max(window.devicePixelRatio || 1, 1);
            canvas.width = canvas.offsetWidth * ratio;
            canvas.height = canvas.offsetHeight * ratio;
            canvas.getContext("2d").scale(ratio, ratio);
        }

        resizeCanvas(customerCanvas);
        resizeCanvas(repCanvas);

        const customerPad = new SignaturePad(customerCanvas);
        const repPad = new SignaturePad(repCanvas);

        document.getElementById('clearCustomer').addEventListener('click', () => customerPad.clear());
        document.getElementById('clearRep').addEventListener('click', () => repPad.clear());

        document.getElementById('visitForm').addEventListener('submit', function(e) {
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
        
        // Handle window resize for signature pads
        window.addEventListener("resize", () => {
            resizeCanvas(customerCanvas);
            resizeCanvas(repCanvas);
            customerPad.clear();
            repPad.clear();
        });
    });
</script>
@endpush
