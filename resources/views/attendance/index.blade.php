@extends('layouts.app')

@section('title', 'Attendance Dashboard')
@section('icon', 'fa-clock-o')
@section('page-title', 'Attendance Management')
@section('page-description', 'Real-time staff monitoring, geofence verification, and productivity tracking.')

@section('content')
<div class="row">
    <!-- Real-time Clock Widget -->
    <div class="col-md-3">
        <div class="tile p-3 text-center bg-primary text-white">
            <h5 class="mb-0">Current Server Time</h5>
            <h2 id="liveClock" class="font-weight-bold">--:--:--</h2>
            <p class="mb-0">{{ \Carbon\Carbon::today()->format('l, d M Y') }}</p>
        </div>
    </div>
    
    <!-- Stats Summary -->
    <div class="col-md-3">
        <div class="tile p-3">
            <h6>This Week's Late Comers</h6>
            <ul class="list-unstyled mb-0">
                @forelse($weeklyLateComers as $stat)
                    <li class="d-flex justify-content-between">
                        <span>{{ $stat->name }}</span>
                        <span class="badge badge-danger">{{ $stat->frequency }} times</span>
                    </li>
                @empty
                    <li class="text-muted small">No late records this week</li>
                @endforelse
            </ul>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="tile p-3">
            <h6>Top Early Arrivals</h6>
            <ul class="list-unstyled mb-0">
                @forelse($weeklyEarlyArrivals as $stat)
                    <li class="d-flex justify-content-between">
                        <span>{{ $stat->name }}</span>
                        <span class="badge badge-success">{{ $stat->frequency }} times</span>
                    </li>
                @empty
                    <li class="text-muted small">No early records this week</li>
                @endforelse
            </ul>
        </div>
    </div>

    <!-- Active Sessions Info -->
    <div class="col-md-3">
        <div class="tile p-3 text-center bg-info text-white">
            <h5>Rules Summary</h5>
            <p class="mb-1 small">In: <b>{{ \Carbon\Carbon::parse($expectedInTime)->format('h:i A') }}</b></p>
            <p class="mb-0 small">Out: <b>{{ \Carbon\Carbon::parse($expectedOutTime)->format('h:i A') }}</b></p>
            <button type="button" class="btn btn-sm btn-light mt-2" data-toggle="modal" data-target="#settingsModal">
                <i class="fa fa-pencil"></i> Adjust Rules
            </button>
        </div>
    </div>
</div>

<div class="row mt-3">
    <div class="col-md-12">
        <div class="tile">
            <div class="tile-title-w-btn">
                <h3 class="title">Attendance Records</h3>
                <div class="btn-group">
                    <form action="{{ route('attendance.index') }}" method="GET" id="filterForm" class="form-inline">
                        <select name="filter" class="form-control form-control-sm mr-2" onchange="this.form.submit()">
                            <option value="daily" {{ $filterType == 'daily' ? 'selected' : '' }}>Daily</option>
                            <option value="weekly" {{ $filterType == 'weekly' ? 'selected' : '' }}>Weekly</option>
                            <option value="monthly" {{ $filterType == 'monthly' ? 'selected' : '' }}>Monthly</option>
                        </select>
                        <select name="status" class="form-control form-control-sm mr-2" onchange="this.form.submit()">
                            <option value="">All Status</option>
                            <option value="late" {{ $statusFilter == 'late' ? 'selected' : '' }}>Late Comers</option>
                            <option value="overdue" {{ $statusFilter == 'overdue' ? 'selected' : '' }}>Overdue Logout</option>
                        </select>
                        @if($filterType == 'daily')
                            <input type="date" name="date" class="form-control form-control-sm mr-2" value="{{ $date }}" onchange="this.form.submit()">
                        @endif
                    </form>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover table-bordered" id="attendanceTable">
                    <thead class="bg-light">
                        <tr>
                            <th>Staff Info</th>
                            <th>Time In</th>
                            <th>Time Out</th>
                            <th>Working Hours</th>
                            <th>Status & Location</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($attendances as $log)
                            <tr>
                                <td>
                                    <strong>{{ $log->user->name }}</strong><br>
                                    <small class="text-muted">ID: {{ $log->user->staff_id }}</small>
                                </td>
                                <td>
                                    <span class="badge badge-pill badge-info px-3">
                                        <i class="fa fa-sign-in"></i> {{ \Carbon\Carbon::parse($log->signed_in_at)->format('h:i A') }}
                                    </span><br>
                                    <small class="text-muted">{{ \Carbon\Carbon::parse($log->signed_in_at)->format('d M, Y') }}</small>
                                </td>
                                <td>
                                    @if($log->signed_out_at)
                                        <span class="badge badge-pill badge-dark px-3">
                                            <i class="fa fa-sign-out"></i> {{ \Carbon\Carbon::parse($log->signed_out_at)->format('h:i A') }}
                                        </span>
                                    @else
                                        <span class="badge badge-pill badge-warning px-3 border border-warning text-dark">
                                            Still Working
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    @if($log->working_hours == 'Pending')
                                        <span class="text-info font-italic">Calculating...</span>
                                    @else
                                        <span class="font-weight-bold">{{ $log->working_hours }}</span>
                                    @endif
                                </td>
                                <td>
                                    <!-- In-Time Status -->
                                    @if($log->is_late)
                                        <span class="badge badge-danger">Late</span>
                                    @else
                                        <span class="badge badge-success">On Time</span>
                                    @endif

                                    <!-- Overdue Status -->
                                    @if($log->is_overdue)
                                        <span class="badge badge-warning text-dark border border-warning" title="Forgot to sign out or working extra hours">OVERDUE</span>
                                    @endif

                                    <hr class="my-1">
                                    
                                    <!-- Geofence & Method -->
                                    <small>
                                        @if(!$log->location_verified_in)
                                            <i class="fa fa-map-marker text-danger"></i> Outside HQ 
                                        @else
                                            <i class="fa fa-map-marker text-success"></i> inside HQ
                                        @endif
                                        | <i class="fa fa-fingerprint {{ $log->verification_type_in == 'fingerprint' ? 'text-primary' : 'text-info' }}"></i> {{ ucfirst(str_replace('_', ' ', $log->verification_type_in)) }}
                                    </small>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-4">No records found matching your selection.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Settings Modal -->
<div class="modal fade" id="settingsModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content border-0">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="fa fa-cogs"></i> Attendance Policy Settings</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="{{ route('attendance.settings.save') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <p class="text-muted small mb-4">Set the standard working hours for the office. Records outside these times will be flagged as late or overdue.</p>
                    
                    <div class="form-group">
                        <label class="font-weight-bold">Expected Arrival (Sign In Before)</label>
                        <input type="time" name="expected_arrival_time" class="form-control" 
                               value="{{ \Carbon\Carbon::parse($expectedInTime)->format('H:i') }}">
                    </div>

                    <div class="form-group">
                        <label class="font-weight-bold">Expected Departure (Sign Out After)</label>
                        <input type="time" name="expected_departure_time" class="form-control" 
                               value="{{ \Carbon\Carbon::parse($expectedOutTime)->format('H:i') }}">
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function updateClock() {
        const now = new Date();
        const timeStr = now.toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
        document.getElementById('liveClock').textContent = timeStr;
    }
    setInterval(updateClock, 1000);
    updateClock();
</script>
@endpush
@endsection
