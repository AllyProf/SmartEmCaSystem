@extends('layouts.app')

@section('title', 'HR Dashboard')
@section('icon', 'fa-dashboard')
@section('page-title', 'HR Dashboard')
@section('page-description', 'Welcome to the Human Resources Dashboard')

@section('breadcrumb')
<li class="breadcrumb-item"><a href="#">Dashboard</a></li>
@endsection

@section('content')
<div class="row">
    <div class="col-md-6 col-lg-3">
        <div class="widget-small primary coloured-icon">
            <i class="icon fa fa-users fa-3x"></i>
            <div class="info">
                <h4>Total Staff</h4>
                <p><b>{{ $stats['total_staff'] }}</b></p>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-lg-3">
        <div class="widget-small info coloured-icon">
            <i class="icon fa fa-clock-o fa-3x"></i>
            <div class="info">
                <h4>Present Today</h4>
                <p><b>{{ $stats['present_today'] }}</b></p>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-lg-3">
        <div class="widget-small warning coloured-icon">
            <i class="icon fa fa-building fa-3x"></i>
            <div class="info">
                <h4>HQ Offices</h4>
                <p><b>1</b></p>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-lg-3">
        <div class="widget-small danger coloured-icon">
            <i class="icon fa fa-user-plus fa-3x"></i>
            <div class="info">
                <h4>Quick Actions</h4>
                <p><a href="{{ route('users.create') }}" class="btn btn-sm" style="background-color: #940000; color: white;">+ Add Staff</a></p>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="tile">
            <h3 class="tile-title">Recent Staff Additions</h3>
            <div class="tile-body">
                <table class="table table-hover table-bordered">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Role</th>
                            <th>Joined</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($stats['recent_users'] as $user)
                        <tr>
                            <td>
                                <b>{{ $user->name }}</b><br>
                                <small class="text-muted">{{ $user->staff_id ?? 'No ID' }}</small>
                            </td>
                            <td><span class="badge badge-secondary">{{ ucfirst($user->role) }}</span></td>
                            <td>{{ $user->created_at->format('M d, Y') }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="3" class="text-center">No staff found</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="tile">
            <h3 class="tile-title">Today's Recent Sign-ins</h3>
            <div class="tile-body">
                <table class="table table-hover table-bordered">
                    <thead>
                        <tr>
                            <th>Staff</th>
                            <th>Sign In</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($stats['recent_attendance'] as $attendance)
                        <tr>
                            <td>{{ $attendance->user->name ?? 'Unknown' }}</td>
                            <td>{{ $attendance->signed_in_at ? $attendance->signed_in_at->format('h:i A') : 'N/A' }}</td>
                            <td>
                                @if($attendance->signed_out_at)
                                <span class="badge badge-secondary">Signed Out</span>
                                @else
                                <span class="badge badge-success">Active</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="3" class="text-center">No recent sign-ins</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="tile-footer text-right">
                <a href="{{ route('attendance.index') }}" class="btn btn-sm btn-primary">View Full Attendance Log</a>
            </div>
        </div>
    </div>
</div>

@endsection
