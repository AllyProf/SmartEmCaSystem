@extends('layouts.app')

@section('title', 'Dashboard')
@section('icon', 'fa-dashboard')
@section('page-title', 'Dashboard')
@section('page-description', 'Welcome to Smart EmCa System')

@section('breadcrumb')
<li class="breadcrumb-item"><a href="#">Dashboard</a></li>
@endsection

@section('content')
<div class="row">
    <div class="col-md-6 col-lg-3">
        <div class="widget-small primary coloured-icon">
            <i class="icon fa fa-users fa-3x"></i>
            <div class="info">
                <h4>Total Customers</h4>
                <p><b>{{ $stats['total_customers'] }}</b></p>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-lg-3">
        <div class="widget-small info coloured-icon">
            <i class="icon fa fa-comment fa-3x"></i>
            <div class="info">
                <h4>SMS Sent</h4>
                <p><b>{{ $stats['total_sms_sent'] }}</b></p>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-lg-3">
        <div class="widget-small warning coloured-icon">
            <i class="icon fa fa-calendar-check-o fa-3x"></i>
            <div class="info">
                <h4>Pending Follow-ups</h4>
                <p><b>{{ $stats['pending_follow_ups'] }}</b></p>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-lg-3">
        <div class="widget-small danger coloured-icon">
            <i class="icon fa fa-bell fa-3x"></i>
            <div class="info">
                <h4>Upcoming</h4>
                <p><b>{{ $stats['upcoming_follow_ups']->count() }}</b></p>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="tile">
            <h3 class="tile-title">Recent Customers</h3>
            <div class="tile-body">
                <table class="table table-hover table-bordered">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Phone</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($stats['recent_customers'] as $customer)
                        <tr>
                            <td>{{ $customer->name ?? 'N/A' }}</td>
                            <td>{{ $customer->phone_number }}</td>
                            <td>{{ $customer->created_at->format('M d, Y') }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="3" class="text-center">No customers yet</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="tile">
            <h3 class="tile-title">Recent SMS</h3>
            <div class="tile-body">
                <table class="table table-hover table-bordered">
                    <thead>
                        <tr>
                            <th>Phone</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($stats['recent_sms'] as $sms)
                        <tr>
                            <td>{{ $sms->phone_number }}</td>
                            <td><span class="badge badge-info">{{ ucfirst($sms->sms_type) }}</span></td>
                            <td><span class="badge badge-{{ $sms->status === 'sent' ? 'success' : 'danger' }}">{{ ucfirst($sms->status) }}</span></td>
                            <td>{{ $sms->created_at->format('M d, Y') }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="text-center">No SMS sent yet</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="tile">
            <h3 class="tile-title">Upcoming Follow-ups</h3>
            <div class="tile-body">
                <table class="table table-hover table-bordered">
                    <thead>
                        <tr>
                            <th>Customer</th>
                            <th>Visit Date</th>
                            <th>Next Follow-up</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($stats['upcoming_follow_ups'] as $followUp)
                        <tr>
                            <td>{{ $followUp->customer->name ?? $followUp->customer->phone_number }}</td>
                            <td>{{ $followUp->visit_date->format('M d, Y') }}</td>
                            <td>{{ $followUp->next_follow_up_date ? $followUp->next_follow_up_date->format('M d, Y') : 'N/A' }}</td>
                            <td><span class="badge badge-warning">{{ ucfirst($followUp->status) }}</span></td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="text-center">No upcoming follow-ups</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection





