@extends('layouts.app')

@section('title', 'Attendance Settings')
@section('icon', 'fa-cogs')
@section('page-title', 'Attendance Settings')
@section('page-description', 'Configure global attendance rules.')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('attendance.index') }}">Attendance</a></li>
    <li class="breadcrumb-item active">Settings</li>
@endsection

@section('content')
<div class="row">
    <div class="col-md-6 offset-md-3">
        <div class="tile">
            <h3 class="tile-title">Configure Expected Arrival</h3>
            <p>Set the typical time staff are expected to sign in by. Any sign in after this time will be marked as <strong>Late</strong>.</p>
            
            <form action="{{ route('attendance.settings.save') }}" method="POST">
                @csrf
                <div class="form-group">
                    <label for="expected_arrival_time">Expected Arrival Time</label>
                    <input type="time" class="form-control @error('expected_arrival_time') is-invalid @enderror" 
                           id="expected_arrival_time" name="expected_arrival_time" 
                           value="{{ old('expected_arrival_time', $expectedTime ? \Carbon\Carbon::parse($expectedTime->value)->format('H:i') : '08:00') }}" required>
                    @error('expected_arrival_time')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>

                <div class="form-group text-right mb-0">
                    <a href="{{ route('attendance.index') }}" class="btn btn-secondary mr-2">Cancel</a>
                    <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> Save Settings</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
