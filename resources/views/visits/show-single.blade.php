@extends('layouts.app')

@section('title', 'Visit Details')

@section('content')
<div class="row d-print-none mb-3">
    <div class="col-md-12 text-right">
        <button class="btn btn-primary" onclick="window.print()"><i class="fa fa-print"></i> Print Form</button>
        <a href="{{ route('visits.index') }}" class="btn btn-secondary">Back</a>
    </div>
</div>

<div class="tile" id="printArea" style="padding: 0; border: 1px solid #ddd; box-shadow: none; position: relative; min-height: 1100px; background-color: white; overflow: hidden;">
    <!-- Verified Watermark -->
    <div class="watermark d-print-block">VERIFIED</div>

    <!-- Header Block (Matching Image) -->
    <div class="container-fluid p-0">
        <div class="row no-gutters align-items-center" style="margin: 0; border-bottom: 5px solid #940000;">
            <div class="col-4 p-4 text-center">
                 <img src="{{ asset('images/logo.jpg') }}" alt="EmCa Logo" style="max-height: 100px; width: auto;">
            </div>
            <div class="col-8">
                <div style="background-color: #940000; position: relative; padding: 40px 30px 40px 60px; color: white; -webkit-print-color-adjust: exact; print-color-adjust: exact; clip-path: polygon(15% 0, 100% 0, 100% 100%, 0% 100%);">
                    <div style="font-family: 'Century Gothic', AppleGothic, sans-serif; text-align: right;">
                        <p style="margin:0; font-size: 20px; font-weight: bold;">EmCa Techonologies</p>
                        <p style="margin:0; font-size: 14px;">S.L.P 20, Moshi - Kilimanjaro</p>
                        <p style="margin:0; font-size: 14px;">Tel: +255 749 719 998</p>
                        <p style="margin:0; font-size: 14px;">Email: emca@emca.tech</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="p-5">
        <div class="text-center mb-5">
            <h3 style="font-family: 'Century Gothic', AppleGothic, sans-serif; font-weight: bold; letter-spacing: 1px; color: #333;">CUSTOMER VISIT CONFIRMATION FORM</h3>
        </div>

        <div class="row mb-5" style="font-family: 'Century Gothic', AppleGothic, sans-serif; font-size: 16px;">
            <div class="col-6">
                <span class="font-weight-bold">Visit Date:</span> 
                <span style="border-bottom: 1px solid #333; display: inline-block; width: 250px; padding-left: 10px; font-style: italic;">{{ $visit->visit_date }}</span>
            </div>
            <div class="col-6">
                <span class="font-weight-bold">Location:</span> 
                <span style="border-bottom: 1px solid #333; display: inline-block; width: 300px; padding-left: 10px; font-style: italic;">{{ $visit->location }}</span>
            </div>
        </div>

        <!-- Professional Grid Design -->
        <table class="table-custom mt-4">
            <thead>
                <tr>
                    <th colspan="2">CUSTOMER INFORMATION</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="label-cell">Customer / Organization Name:</td>
                    <td class="data-cell">{{ $visit->customer_name }}</td>
                </tr>
                <tr>
                    <td class="label-cell">Contact Person / Title:</td>
                    <td class="data-cell">{{ $visit->contact_person }}</td>
                </tr>
                <tr>
                    <td class="label-cell">Phone / Email:</td>
                    <td class="data-cell">{{ $visit->phone }} / {{ $visit->email }}</td>
                </tr>
                
                <tr>
                    <th colspan="2">EMCA TECHNOLOGIES REPRESENTATIVE</th>
                </tr>
                <tr>
                    <td class="label-cell">Name:</td>
                    <td class="data-cell">{{ $visit->representative_name }}</td>
                </tr>
                <tr>
                    <td class="label-cell">Title:</td>
                    <td class="data-cell">{{ $visit->representative_title }}</td>
                </tr>

                <tr>
                    <th colspan="2">PURPOSE OF VISIT</th>
                </tr>
                <tr>
                    <td colspan="2" style="height: 100px; vertical-align: top; padding: 15px;">
                        <span style="font-size: 11px; color: #777; font-weight: bold; display: block; margin-bottom: 5px;">BRIEF DESCRIPTION:</span>
                        <div style="font-size: 15px; line-height: 1.6;">{{ $visit->purpose }}</div>
                    </td>
                </tr>

                <tr>
                    <th colspan="2">CUSTOMER FEEDBACK</th>
                </tr>
                <tr>
                    <td colspan="2" style="height: 100px; vertical-align: top; padding: 15px;">
                        <span style="font-size: 11px; color: #777; font-weight: bold; display: block; margin-bottom: 5px;">COMMENTS / SUGGESTSIONS:</span>
                        <div style="font-size: 15px; line-height: 1.6;">{{ $visit->feedback }}</div>
                    </td>
                </tr>
                <tr>
                    <td colspan="2" style="padding: 12px 15px;">
                        <span class="font-weight-bold mr-3">Satisfaction Level:</span>
                        @foreach(['Very Satisfied', 'Satisfied', 'Average', 'Unsatisfied'] as $level)
                            <span style="margin-right: 25px; display: inline-flex; align-items: center;">
                                <span style="display: inline-block; width: 22px; height: 22px; border: 1px solid #333; margin-right: 8px; text-align: center; line-height: 20px; font-weight: bold; font-size: 18px;">
                                    {!! ($visit->satisfaction_level == $level) ? '&#10003;' : '' !!}
                                </span>
                                <span style="font-size: 14px; {{ ($visit->satisfaction_level == $level) ? 'font-weight: bold;' : '' }}">{{ $level }}</span>
                            </span>
                        @endforeach
                    </td>
                </tr>
            </tbody>
        </table>

        <!-- Confirmation Section (Exact Layout) -->
        <div class="mt-5" style="border: 2px solid #333; font-family: 'Century Gothic', AppleGothic, sans-serif; position: relative; z-index: 20;">
            <div style="background-color: #f8f9fa; padding: 8px 15px; border-bottom: 2px solid #333; font-weight: bold; font-size: 15px;">
                CONFIRMATION | I confirm that the visit took place as stated above.
            </div>
            <div class="row no-gutters" style="margin:0;">
                <div class="col-6 p-4" style="border-right: 2px solid #333;">
                    <p class="font-weight-bold mb-2" style="font-size: 17px;">Customer</p>
                    <p class="mb-3">Name: <span style="border-bottom: 1px solid #333; display: inline-block; width: 75%; padding-left: 10px; font-style: italic;">{{ $visit->customer_name }}</span></p>
                    
                    <div style="position: relative; height: 100px;">
                        @if($visit->customer_signature_path)
                            <img src="{{ asset('storage/'.$visit->customer_signature_path) }}" style="position: absolute; bottom: 0; left: 50%; transform: translateX(-50%); max-height: 120px; mix-blend-mode: multiply; z-index: 10;">
                        @endif
                        <div class="d-flex justify-content-between pr-3" style="font-size: 14px; position: absolute; bottom: 0; width: 100%; border-top: 2px solid #333; padding-top: 5px;">
                            <span>Signature</span>
                            <span>Date: {{ \Carbon\Carbon::parse($visit->visit_date)->format('d/m/Y') }}</span>
                        </div>
                    </div>
                </div>
                <div class="col-6 p-4">
                    <p class="font-weight-bold mb-2" style="font-size: 17px;">EmCa Techonologies</p>
                    <p class="mb-3">Name: <span style="border-bottom: 1px solid #333; display: inline-block; width: 75%; padding-left: 10px; font-style: italic;">{{ $visit->representative_name }}</span></p>
                    
                    <div style="position: relative; height: 100px;">
                        @if($visit->representative_signature_path)
                            <img src="{{ asset('storage/'.$visit->representative_signature_path) }}" style="position: absolute; bottom: 0; left: 50%; transform: translateX(-50%); max-height: 120px; mix-blend-mode: multiply; z-index: 10;">
                        @endif
                        <div class="d-flex justify-content-between pr-3" style="font-size: 14px; position: absolute; bottom: 0; width: 100%; border-top: 2px solid #333; padding-top: 5px;">
                            <span>Signature</span>
                            <span>Date: {{ \Carbon\Carbon::parse($visit->visit_date)->format('d/m/Y') }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Decorative Footer Block (Matching Image Header style) -->
    <div style="position: absolute; bottom: 0; left: 0; width: 100%; height: 60px; pointer-events: none;">
        <div style="background-color: #940000; height: 100%; clip-path: polygon(0 40%, 85% 40%, 100% 100%, 0% 100%); -webkit-print-color-adjust: exact; print-color-adjust: exact; display: flex; align-items: flex-end; justify-content: flex-start; padding: 0 40px 10px 40px;">
            <div style="color: white; font-family: 'Century Gothic', AppleGothic, sans-serif; font-size: 12px; pointer-events: auto;">
                Benbella Road, Moshi | www.emca.tech | <i class="fa fa-instagram"></i> @emcatechn
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
.table-custom {
    width: 100%;
    border-collapse: collapse;
    border: 2px solid #333;
}
.table-custom th {
    background-color: #f0f0f0;
    color: #333;
    padding: 10px 15px;
    text-align: left;
    border: 1px solid #333;
    font-size: 14px;
    letter-spacing: 1px;
    -webkit-print-color-adjust: exact;
}
.table-custom td {
    border: 1px solid #333;
    padding: 10px 15px;
    font-size: 15px;
}
.label-cell {
    width: 35%;
    font-weight: bold;
    background-color: #f9f9f9;
}
.watermark {
    position: absolute;
    top: 55%;
    left: 50%;
    transform: translate(-50%, -50%) rotate(-45deg);
    font-size: 180px;
    color: rgba(0, 100, 0, 0.06);
    pointer-events: none;
    z-index: 10;
    font-weight: 900;
    text-transform: uppercase;
    white-space: nowrap;
    border: 30px solid rgba(0, 100, 0, 0.06);
    padding: 20px 60px;
    border-radius: 60px;
}
@media print {
    body * { visibility: hidden; }
    #printArea, #printArea * { visibility: visible; }
    #printArea { position: absolute; left: 0; top: 0; width: 100%; border: none !important; margin: 0; padding: 0; }
    .table-custom td, .table-custom th { border: 1px solid #333 !important; }
    .watermark { color: rgba(0, 100, 0, 0.1) !important; border-color: rgba(0, 100, 0, 0.1) !important; }
}
</style>
@endpush
@endsection
