@extends('layouts.app')

@section('title', 'Attendance Sheet')

@section('content')
<div class="row d-print-none mb-3">
    <div class="col-md-12 text-right">
        <button class="btn btn-primary" onclick="window.print()"><i class="fa fa-print"></i> Print Sheet</button>
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
                    <div style="font-family: Arial, sans-serif; text-align: right;">
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
        <div class="mb-4" style="font-family: Arial, sans-serif;">
            <h3 style="font-weight: bold; margin-bottom: 30px; letter-spacing: 1px; color: #333;">ATTENDANCE SHEET</h3>
            <p style="font-size: 18px; font-weight: bold;">
                SUBJECT: <span style="border-bottom: 2px dotted #333; display: inline-block; width: 80%; padding-left: 10px; font-style: italic;">{{ $visit->subject }}</span>
            </p>
        </div>

        <table class="table table-bordered mt-5 attendance-table" style="border: 2px solid #333; font-family: Arial, sans-serif;">
            <thead>
                <tr style="background-color: #f2f2f2 !important; color: #333 !important; -webkit-print-color-adjust: exact;">
                    <th style="width: 50px; border: 1px solid #333; text-align: center;">S/N</th>
                    <th style="border: 1px solid #333;">NAME</th>
                    <th style="border: 1px solid #333;">INSTITUTION</th>
                    <th style="border: 1px solid #333;">POSITION</th>
                    <th style="border: 1px solid #333;">PHONE NUMBER</th>
                    <th style="border: 1px solid #333;">EMAIL</th>
                    <th style="width: 140px; border: 1px solid #333; text-align: center;">SIGNATURE</th>
                </tr>
            </thead>
            <tbody>
                @foreach($visit->attendees as $index => $attendee)
                <tr style="height: 60px;">
                    <td class="text-center align-middle" style="border: 1px solid #333; font-weight: bold; background-color: #fcfcfc;">{{ $index + 1 }}</td>
                    <td class="align-middle" style="border: 1px solid #333; padding-left: 10px;">{{ $attendee->name }}</td>
                    <td class="align-middle" style="border: 1px solid #333; padding-left: 10px;">{{ $attendee->institution }}</td>
                    <td class="align-middle" style="border: 1px solid #333; padding-left: 10px;">{{ $attendee->position }}</td>
                    <td class="align-middle" style="border: 1px solid #333; padding-left: 10px;">{{ $attendee->phone }}</td>
                    <td class="align-middle" style="border: 1px solid #333; padding-left: 10px;">{{ $attendee->email }}</td>
                    <td class="text-center align-middle" style="padding: 2px; border: 1px solid #333;">
                        @if($attendee->signature_path)
                            <img src="{{ asset('storage/'.$attendee->signature_path) }}" style="max-height: 50px; max-width: 130px; mix-blend-mode: multiply;">
                        @endif
                    </td>
                </tr>
                @endforeach
                <!-- Add empty rows to match the paper form feel -->
                @for ($i = $visit->attendees->count(); $i < 13; $i++)
                <tr style="height: 50px;">
                    <td class="align-middle text-center" style="border: 1px solid #333; background-color: #fcfcfc;">{{ $i + 1 }}</td>
                    <td style="border: 1px solid #333;"></td>
                    <td style="border: 1px solid #333;"></td>
                    <td style="border: 1px solid #333;"></td>
                    <td style="border: 1px solid #333;"></td>
                    <td style="border: 1px solid #333;"></td>
                    <td style="border: 1px solid #333;"></td>
                </tr>
                @endfor
            </tbody>
        </table>
    </div>

    <!-- Decorative Footer Block (Matching Image) -->
    <div style="position: absolute; bottom: 0; left: 0; width: 100%; height: 60px; pointer-events: none;">
        <div style="background-color: #940000; height: 100%; clip-path: polygon(0 40%, 85% 40%, 100% 100%, 0% 100%); -webkit-print-color-adjust: exact; print-color-adjust: exact; display: flex; align-items: flex-end; justify-content: flex-start; padding: 0 40px 10px 40px;">
            <div style="color: white; font-family: Arial, sans-serif; font-size: 12px; pointer-events: auto;">
                Benbella Road, Moshi | www.emca.tech | <i class="fa fa-instagram"></i> @emcatechn
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
.attendance-table th {
    padding: 12px 8px !important;
    font-size: 13px;
    letter-spacing: 0.5px;
}
.watermark {
    position: absolute;
    top: 55%;
    left: 50%;
    transform: translate(-50%, -50%) rotate(-45deg);
    font-size: 180px;
    color: rgba(0, 100, 0, 0.05);
    pointer-events: none;
    z-index: 10;
    font-weight: 900;
    text-transform: uppercase;
    white-space: nowrap;
    border: 30px solid rgba(0, 100, 0, 0.05);
    padding: 20px 60px;
    border-radius: 60px;
}
@media print {
    body * { visibility: hidden; }
    #printArea, #printArea * { visibility: visible; }
    #printArea { position: absolute; left: 0; top: 0; width: 100%; border: none !important; margin: 0; padding: 0; }
    .attendance-table td, .attendance-table th { border: 1px solid #333 !important; }
    .watermark { color: rgba(0, 100, 0, 0.08) !important; border-color: rgba(0, 100, 0, 0.08) !important; }
}
</style>
@endpush
@endsection
