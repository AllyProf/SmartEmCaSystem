@extends('layouts.app')

@section('title', 'Attendance Dashboard')
@section('icon', 'fa-clock-o')
@section('page-title', 'Attendance Management')
@section('page-description', 'Real-time staff monitoring, geofence verification, and productivity tracking.')

@section('content')
<div class="row">
    <!-- Real-time Clock Widget -->
    <div class="col-md-3">
        <div class="tile p-3 text-center shadow-sm" style="background-color: #940000; color: #ffffff; border-radius: 10px; border-bottom: 5px solid #000000;">
            <p class="mb-1 text-uppercase small" style="letter-spacing: 1px; opacity: 0.8;">Current Server Time</p>
            <h2 id="liveClock" class="font-weight-bold mb-0" style="font-size: 2.2rem;">--:--:--</h2>
            <p class="mb-0 small">{{ \Carbon\Carbon::today()->format('l, d M Y') }}</p>
        </div>
    </div>
    
    <!-- Stats Summary: Late Comers -->
    <div class="col-md-3">
        <div class="tile p-3 shadow-sm" style="border-radius: 10px; border-left: 5px solid #940000; min-height: 125px;">
            <p class="text-uppercase small font-weight-bold mb-2" style="color: #940000; letter-spacing: 0.5px;">This Week's Late Comers</p>
            <div class="px-1">
                @forelse($weeklyLateComers as $stat)
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="font-weight-bold text-dark" style="font-size: 0.9rem;">{{ $stat->name }}</span>
                        <span class="badge badge-pill text-white" style="background-color: #940000;">{{ $stat->frequency }} times</span>
                    </div>
                @empty
                    <div class="text-center py-2">
                        <span class="text-muted small italic">No late records this week</span>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
    
    <!-- Stats Summary: Early Arrivals -->
    <div class="col-md-3">
        <div class="tile p-3 shadow-sm" style="border-radius: 10px; border-left: 5px solid #000000; min-height: 125px;">
            <p class="text-uppercase small font-weight-bold mb-2" style="color: #000000; letter-spacing: 0.5px;">Top Early Arrivals</p>
            <div class="px-1">
                @forelse($weeklyEarlyArrivals as $stat)
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="font-weight-bold text-dark" style="font-size: 0.9rem;">{{ $stat->name }}</span>
                        <span class="badge badge-pill text-white" style="background-color: #000000;">{{ $stat->frequency }} times</span>
                    </div>
                @empty
                    <div class="text-center py-2">
                        <span class="text-muted small italic">No early records this week</span>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Active Sessions Info / Rules -->
    <div class="col-md-3">
        <div class="tile p-3 text-center shadow-sm" style="background-color: #000000; color: #ffffff; border-radius: 10px;">
            <p class="mb-2 text-uppercase small font-weight-bold" style="letter-spacing: 1px; color: #940000;">Rules Summary</p>
            <div class="d-flex justify-content-around mb-2">
                <div>
                    <span class="d-block small text-muted">In</span>
                    <span class="font-weight-bold" style="font-size: 1.1rem;">{{ \Carbon\Carbon::parse($expectedInTime)->format('h:i A') }}</span>
                </div>
                <div style="width: 1px; background-color: rgba(255,255,255,0.2); height: 35px;"></div>
                <div>
                    <span class="d-block small text-muted">Out</span>
                    <span class="font-weight-bold" style="font-size: 1.1rem;">{{ \Carbon\Carbon::parse($expectedOutTime)->format('h:i A') }}</span>
                </div>
            </div>
            <button type="button" class="btn btn-sm btn-block text-white" style="background-color: #940000; border-radius: 5px;" data-toggle="modal" data-target="#settingsModal">
                <i class="fa fa-cog"></i> ADJUST POLICY
            </button>
        </div>
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
                    <thead>
                        <tr style="background-color: #000000; color: #ffffff;">
                            <th class="border-0">Staff Info</th>
                            <th class="border-0">Time In</th>
                            <th class="border-0">Time Out</th>
                            <th class="border-0">Working Hours</th>
                            <th class="border-0">Status & Location</th>
                        </tr>
                    </thead>
                    <tbody style="border-top: 3px solid #940000;">
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
            <div class="modal-header text-white" style="background-color: #940000; border-bottom: 3px solid #000000;">
                <h5 class="modal-title font-weight-bold italic text-uppercase" style="letter-spacing: 1px;"><i class="fa fa-cogs"></i> Attendance Policy Settings</h5>
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
                <div class="modal-footer bg-light" style="border-top: 1px solid #dee2e6;">
                    <button type="button" class="btn btn-secondary border-0" data-dismiss="modal" style="border-radius: 5px;">Cancel</button>
                    <button type="submit" class="btn text-white px-4 border-0" style="background-color: #940000; border-radius: 5px;">Save Changes</button>
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
