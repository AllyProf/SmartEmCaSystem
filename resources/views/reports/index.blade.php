@extends('layouts.app')

@section('title', 'Reports')
@section('icon', 'fa-pie-chart')
@section('page-title', 'Reports & Analytics')
@section('page-description', 'System-wide performance, visitor patterns, and communication statistics')

@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
<li class="breadcrumb-item">Reports</li>
@endsection

@section('content')
<!-- Date Filter Form -->
<div class="row">
    <div class="col-md-12">
        <div class="tile">
            <div class="tile-body">
                <form method="GET" action="{{ route('reports.index') }}" class="row align-items-end">
                    <div class="col-md-4">
                        <div class="form-group mb-0">
                            <label class="control-label font-weight-bold">Start Date</label>
                            <input class="form-control" type="date" name="start_date" value="{{ $startDate->format('Y-m-d') }}">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group mb-0">
                            <label class="control-label font-weight-bold">End Date</label>
                            <input class="form-control" type="date" name="end_date" value="{{ $endDate->format('Y-m-d') }}">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-primary"><i class="fa fa-filter"></i> Filter</button>
                        <a href="{{ route('reports.index') }}" class="btn btn-secondary"><i class="fa fa-undo"></i> Reset</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Overview Stats Widgets -->
<div class="row">
    <div class="col-md-6 col-lg-3">
        <div class="widget-small primary coloured-icon">
            <i class="icon fa fa-users fa-3x"></i>
            <div class="info">
                <h4>Total Customers</h4>
                <p><b>{{ $stats['total_customers'] }}</b> <small class="text-white-50">({{ $stats['new_customers_range'] }} in range)</small></p>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-lg-3">
        <div class="widget-small info coloured-icon">
            <i class="icon fa fa-file-text-o fa-3x"></i>
            <div class="info">
                <h4>Visits Recorded</h4>
                <p><b>{{ $stats['visits_total'] }}</b> <small class="text-white-50">({{ $stats['visits_single'] }} S / {{ $stats['visits_group'] }} G)</small></p>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-lg-3">
        <div class="widget-small success coloured-icon">
            <i class="icon fa fa-comment fa-3x"></i>
            <div class="info">
                <h4>SMS Sent</h4>
                <p><b>{{ $stats['sms_sent'] }}</b> <small class="text-white-50">/ {{ $stats['sms_total'] }} total logs</small></p>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-lg-3">
        <div class="widget-small warning coloured-icon">
            <i class="icon fa fa-calendar-check-o fa-3x"></i>
            <div class="info">
                <h4>Follow-Ups</h4>
                <p><b>{{ $stats['followups_total'] }}</b> <small class="text-white-50">registered in range</small></p>
            </div>
        </div>
    </div>
</div>

<!-- Charts Row -->
<div class="row">
    <!-- Customer Registration Trend -->
    <div class="col-md-8">
        <div class="tile">
            <h3 class="tile-title"><i class="fa fa-line-chart" style="color: #940000;"></i> Customer Registrations Trend</h3>
            <div class="embed-responsive embed-responsive-16by9">
                <canvas class="embed-responsive-item" id="customerChart"></canvas>
            </div>
        </div>
    </div>
    
    <!-- SMS Status Breakdown -->
    <div class="col-md-4">
        <div class="tile">
            <h3 class="tile-title"><i class="fa fa-pie-chart" style="color: #940000;"></i> SMS Log Statuses</h3>
            <div class="embed-responsive embed-responsive-16by9">
                <canvas class="embed-responsive-item" id="smsStatusChart"></canvas>
            </div>
            <div class="text-center mt-3">
                <small class="mr-2"><i class="fa fa-circle text-success"></i> Sent ({{ $stats['sms_sent'] }})</small>
                <small class="mr-2"><i class="fa fa-circle text-danger"></i> Failed ({{ $stats['sms_failed'] }})</small>
                <small><i class="fa fa-circle text-warning"></i> Scheduled ({{ $stats['sms_scheduled'] }})</small>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Follow-up Statuses -->
    <div class="col-md-6">
        <div class="tile">
            <h3 class="tile-title"><i class="fa fa-bar-chart" style="color: #940000;"></i> Follow-Up Statuses</h3>
            <div class="embed-responsive embed-responsive-16by9">
                <canvas class="embed-responsive-item" id="followUpChart"></canvas>
            </div>
        </div>
    </div>
    
    <!-- Top Locations and Satisfaction Levels -->
    <div class="col-md-6">
        <div class="tile">
            <h3 class="tile-title"><i class="fa fa-map-marker" style="color: #940000;"></i> Top Visitor Locations</h3>
            <table class="table table-hover table-bordered">
                <thead>
                    <tr>
                        <th>Location</th>
                        <th class="text-center">Count</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($topLocations as $loc)
                    <tr>
                        <td>{{ $loc->location }}</td>
                        <td class="text-center"><strong>{{ $loc->count }}</strong></td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="2" class="text-center">No location details recorded in this period.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
            
            <h3 class="tile-title mt-4"><i class="fa fa-smile-o" style="color: #940000;"></i> Satisfaction Levels</h3>
            <div class="row">
                @forelse($satisfactionCounts as $sat)
                <div class="col-6 mb-3">
                    <div class="p-2 border rounded bg-light text-center">
                        <strong class="text-uppercase" style="color: #940000;">{{ $sat->satisfaction_level }}</strong>
                        <h4 class="mb-0 mt-1">{{ $sat->count }}</h4>
                    </div>
                </div>
                @empty
                <div class="col-12">
                    <p class="text-center text-muted">No satisfaction levels recorded in this period.</p>
                </div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // 1. Customer Registrations Chart
    var ctxCustomer = document.getElementById('customerChart').getContext('2d');
    var customerChart = new Chart(ctxCustomer, {
        type: 'line',
        data: {
            labels: {!! json_encode($chartLabels) !!},
            datasets: [{
                label: 'New Customers',
                data: {!! json_encode($chartCustomerData) !!},
                backgroundColor: 'rgba(148, 0, 0, 0.1)',
                borderColor: '#940000',
                borderWidth: 2.5,
                fill: true,
                tension: 0.3,
                pointBackgroundColor: '#940000'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            }
        }
    });

    // 2. SMS Status Chart
    var ctxSms = document.getElementById('smsStatusChart').getContext('2d');
    var smsStatusChart = new Chart(ctxSms, {
        type: 'doughnut',
        data: {
            labels: ['Sent', 'Failed', 'Scheduled'],
            datasets: [{
                data: [
                    {{ $stats['sms_sent'] }},
                    {{ $stats['sms_failed'] }},
                    {{ $stats['sms_scheduled'] }}
                ],
                backgroundColor: ['#28a745', '#dc3545', '#ffc107'],
                hoverOffset: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            }
        }
    });

    // 3. Follow-Up Status Chart
    @php
        $followUpLabels = [];
        $followUpData = [];
        foreach ($followUpStatusCounts as $statusCount) {
            $followUpLabels[] = ucfirst(str_replace('_', ' ', $statusCount->status));
            $followUpData[] = $statusCount->count;
        }
    @endphp
    
    var ctxFollowUp = document.getElementById('followUpChart').getContext('2d');
    var followUpChart = new Chart(ctxFollowUp, {
        type: 'bar',
        data: {
            labels: {!! json_encode($followUpLabels) !!},
            datasets: [{
                label: 'Follow-ups Count',
                data: {!! json_encode($followUpData) !!},
                backgroundColor: 'rgba(148, 0, 0, 0.7)',
                borderColor: '#940000',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            }
        }
    });
</script>
@endpush
