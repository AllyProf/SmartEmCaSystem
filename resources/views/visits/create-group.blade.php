@extends('layouts.visitor')

@section('title', 'Group Visit Attendance')
@section('header', 'ATTENDANCE SHEET')

@section('content')
<form action="{{ route('visits.store') }}" method="POST" id="groupForm">
    @csrf
    <input type="hidden" name="type" value="group">

    <div class="row mb-4">
        <div class="col-md-12">
            <div class="form-group row">
                <label class="col-sm-2 col-form-label font-weight-bold">SUBJECT:</label>
                <div class="col-sm-10">
                    <input type="text" name="subject" class="form-control" style="border-bottom: 2px solid #ccc; border-top: 0; border-left: 0; border-right: 0; border-radius: 0;" required>
                </div>
            </div>
        </div>
    </div>

    <div class="table-responsive-wrapper">
        <table class="table table-bordered table-sm responsive-table" id="attendanceTable">
            <thead class="thead-dark d-none d-md-table-header-group">
                <tr>
                    <th style="width: 40px;">S/N</th>
                    <th style="width: 180px;">NAME</th>
                    <th style="width: 150px;">INSTITUTION</th>
                    <th style="width: 120px;">POSITION</th>
                    <th style="width: 130px;">PHONE</th>
                    <th style="width: 150px;">EMAIL</th>
                    <th style="width: 80px;">SIGN</th>
                    <th style="width: 40px;"></th>
                </tr>
            </thead>
            <tbody>
                <!-- Rows will be added here via JS -->
            </tbody>
        </table>
    </div>

    <div class="mt-3 text-center text-md-left">
        <button type="button" class="btn btn-outline-success btn-sm shadow-sm" onclick="addRow()">
            <i class="fa fa-plus"></i> Add Attendee
        </button>
    </div>

    <div class="form-group text-center border-top pt-4 mt-5">
        <button type="submit" class="btn btn-primary btn-lg px-5 shadow">
            <i class="fa fa-check"></i> Submit Attendance Sheet
        </button>
        <div class="mt-3">
            <a href="{{ route('visits.selection') }}" class="btn btn-link text-secondary">Cancel and Go Back</a>
        </div>
    </div>
</form>

<!-- Signature Modal -->
<div class="modal fade" id="signatureModal" tabindex="-1" role="dialog" data-backdrop="static">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title">Provide Signature</h5>
                <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body bg-light">
                <div style="border: 2px dashed #940000; background: white; height: 300px; border-radius: 5px;">
                    <canvas id="modalCanvas" style="width: 100%; height: 100%; touch-action: none;"></canvas>
                </div>
                <p class="text-center text-muted mt-2"><small>Sign inside the box above</small></p>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-outline-secondary" onclick="clearModalPad()">Clear</button>
                <button type="button" class="btn btn-primary px-4" onclick="saveModalSignature()">Save Signature</button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('styles')
<style>
/* Narrow Inputs on Computer */
@media (min-width: 768px) {
    #attendanceTable input.form-control {
        padding: 4px 8px;
        font-size: 13px;
        height: 32px;
    }
    #attendanceTable th {
        font-size: 12px;
        padding: 8px 4px;
        text-align: center;
    }
}

/* Responsive Table Transformation for Mobile */
@media (max-width: 767.98px) {
    .responsive-table, 
    .responsive-table thead, 
    .responsive-table tbody, 
    .responsive-table th, 
    .responsive-table td, 
    .responsive-table tr { 
        display: block; 
    }
    
    .responsive-table thead tr { 
        position: absolute;
        top: -9999px;
        left: -9999px;
    }
    
    .responsive-table tr { 
        border: 1px solid #ccc; 
        margin-bottom: 20px;
        padding: 10px;
        border-radius: 8px;
        background: #fff;
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    }
    
    .responsive-table td { 
        border: none;
        border-bottom: 1px solid #eee; 
        position: relative;
        padding-left: 45% !important; 
        text-align: left !important;
        min-height: 45px;
        display: flex;
        align-items: center;
        padding-top: 10px;
        padding-bottom: 10px;
    }
    
    .responsive-table td:last-child {
        border-bottom: 0;
    }
    
    .responsive-table td:before { 
        position: absolute;
        left: 10px;
        width: 40%; 
        padding-right: 10px; 
        white-space: nowrap;
        font-weight: bold;
        color: #666;
        content: attr(data-label);
    }

    .responsive-table td:first-child {
        background: #f8f9fa;
        font-size: 1.2rem;
        justify-content: center;
        padding-left: 0 !important;
        border-bottom: 2px solid #dee2e6;
    }
    .responsive-table td:first-child:before {
        content: "Attendee #";
        position: static;
        width: auto;
        margin-right: 5px;
    }

    .responsive-table input.form-control {
        border: 1px solid #ced4da !important;
        background-color: #fff !important;
        height: auto;
    }
}
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/signature_pad@4.1.7/dist/signature_pad.umd.min.js"></script>
<script>
    let rowCount = 0;
    let currentRowIndex = null;
    let modalPad = null;

    function initModalPad() {
        var canvas = document.getElementById('modalCanvas');
        
        function resizeCanvas() {
            var ratio =  Math.max(window.devicePixelRatio || 1, 1);
            canvas.width = canvas.offsetWidth * ratio;
            canvas.height = canvas.offsetHeight * ratio;
            canvas.getContext("2d").scale(ratio, ratio);
        }
        
        modalPad = new SignaturePad(canvas, { backgroundColor: 'rgba(255, 255, 255, 0)' });
        
        $('#signatureModal').on('shown.bs.modal', function() {
            setTimeout(resizeCanvas, 200);
            modalPad.clear();
        });
    }

    $(document).ready(function() {
        initModalPad();
        addRow(); // Add first row
    });

    function addRow() {
        rowCount++;
        let idx = rowCount - 1;
        let html = `
            <tr id="row_${idx}">
                <td data-label="S/N" class="text-center align-middle font-weight-bold">${rowCount}</td>
                <td data-label="Name"><input type="text" name="attendees[${idx}][name]" class="form-control" required placeholder="Name"></td>
                <td data-label="Institution"><input type="text" name="attendees[${idx}][institution]" class="form-control" placeholder="Institution"></td>
                <td data-label="Position"><input type="text" name="attendees[${idx}][position]" class="form-control" placeholder="Position"></td>
                <td data-label="Phone"><input type="text" name="attendees[${idx}][phone]" class="form-control" value="+255" placeholder="Phone"></td>
                <td data-label="Email"><input type="email" name="attendees[${idx}][email]" class="form-control" placeholder="Email (Optional)"></td>
                <td data-label="Signature" class="text-center">
                    <button type="button" class="btn btn-sm btn-outline-primary btn-block shadow-sm py-1" id="btn_sign_${idx}" onclick="openSignatureModal(${idx})" style="font-size: 11px;">
                        <i class="fa fa-pencil"></i> Sign
                    </button>
                    <input type="hidden" name="attendees[${idx}][signature]" id="input_sign_${idx}">
                    <div id="sign_preview_${idx}" style="display:none; margin-top:5px;"><i class="fa fa-check-circle text-success"></i> Signed</div>
                </td>
                <td data-label="Action" class="text-center align-middle">
                     <button type="button" class="btn btn-sm btn-link text-danger p-0" onclick="removeRow(${idx})"><i class="fa fa-trash"></i></button>
                </td>
            </tr>
        `;
        $('#attendanceTable tbody').append(html);
    }

    function removeRow(idx) {
        $(`#row_${idx}`).remove();
        updateSN();
    }

    function updateSN() {
        $('#attendanceTable tbody tr').each(function(index) {
            $(this).find('td:first').text(index + 1);
        });
    }

    function openSignatureModal(idx) {
        currentRowIndex = idx;
        $('#signatureModal').modal('show');
    }

    function clearModalPad() {
        modalPad.clear();
    }

    function saveModalSignature() {
        if (modalPad.isEmpty()) {
            Swal.fire({
                icon: 'warning',
                title: 'Empty Signature',
                text: 'Please provide a signature before saving.',
                confirmButtonColor: '#940000'
            });
            return;
        }
        let dataUrl = modalPad.toDataURL();
        $(`#input_sign_${currentRowIndex}`).val(dataUrl);
        
        // Update UI
        $(`#btn_sign_${currentRowIndex}`).hide();
        $(`#sign_preview_${currentRowIndex}`).show();
        
        $('#signatureModal').modal('hide');
    }

    $('#groupForm').on('submit', function(e) {
        if ($('#attendanceTable tbody tr').length === 0) {
            Swal.fire({
                icon: 'error',
                title: 'No Attendees',
                text: 'Please add at least one attendee.',
                confirmButtonColor: '#940000'
            });
            e.preventDefault();
            return false;
        }
        
        let allSigned = true;
        $('input[name^="attendees"]').each(function() {
            if ($(this).attr('name').includes('[signature]') && !$(this).val()) {
                allSigned = false;
            }
        });

        if (!allSigned) {
             Swal.fire({
                icon: 'warning',
                title: 'Missing Signatures',
                text: 'Please ensure all attendees have signed.',
                confirmButtonColor: '#940000'
            });
            e.preventDefault();
            return false;
        }
    });
</script>
@endpush
