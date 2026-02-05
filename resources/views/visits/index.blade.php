@extends('layouts.app')

@section('title', 'Visit Confirmations')
@section('page-title', 'Visitor Confirmations')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="#">Confirmations</a></li>
@endsection

@section('content')
<!-- Statistics Cards -->
<div class="row">
    <div class="col-md-6 col-lg-3">
        <div class="widget-small primary coloured-icon"><i class="icon fa fa-files-o fa-3x"></i>
            <div class="info">
                <h4>Total Visits</h4>
                <p><b>{{ $stats['total'] }}</b></p>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-lg-3">
        <div class="widget-small info coloured-icon"><i class="icon fa fa-user-o fa-3x"></i>
            <div class="info">
                <h4>Single</h4>
                <p><b>{{ $stats['single'] }}</b></p>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-lg-3">
        <div class="widget-small warning coloured-icon"><i class="icon fa fa-users fa-3x"></i>
            <div class="info">
                <h4>Group</h4>
                <p><b>{{ $stats['group'] }}</b></p>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-lg-3">
        <div class="widget-small danger coloured-icon"><i class="icon fa fa-calendar-check-o fa-3x"></i>
            <div class="info">
                <h4>Last 7 Days</h4>
                <p><b>{{ $stats['recent'] }}</b></p>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="tile">
            <div class="tile-title-w-btn">
                <h3 class="title">All Visit Records</h3>
                <div class="btn-group">
                    <a class="btn btn-primary" href="{{ route('visits.verify') }}" target="_blank"><i class="fa fa-plus"></i> New Visit</a>
                </div>
            </div>
            
            <div class="table-responsive">
                <table class="table table-hover table-bordered" id="visitsTable">
                    <thead class="thead-light">
                        <tr>
                            <th>Date</th>
                            <th>Type</th>
                            <th>Customer / Subject</th>
                            <th>Staff Representative</th>
                            <th>Submitted By</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($visits as $visit)
                        <tr>
                            <td>{{ \Carbon\Carbon::parse($visit->visit_date)->format('d M, Y') }}</td>
                            <td>
                                @if($visit->type == 'single')
                                <span class="badge badge-primary px-3 py-2">Single</span>
                                @else
                                <span class="badge badge-info px-3 py-2">Group</span>
                                @endif
                            </td>
                            <td>
                                @if($visit->type == 'single')
                                    <div class="font-weight-bold">{{ $visit->customer_name }}</div>
                                    <small class="text-muted">{{ $visit->contact_person }}</small>
                                @else
                                    <div class="font-weight-bold">{{ $visit->subject }}</div>
                                    <span class="badge badge-light border">{{ $visit->attendees->count() }} Attendees</span>
                                @endif
                            </td>
                            <td>{{ $visit->representative_name ?? 'N/A' }}</td>
                            <td>
                                <div class="text-truncate" style="max-width: 150px;" title="{{ $visit->created_by_email }}">
                                    {{ $visit->created_by_email }}
                                </div>
                            </td>
                            <td>
                                <div class="btn-group">
                                    <a href="{{ route('visits.show', $visit->id) }}" class="btn btn-primary btn-sm rounded-0">
                                        <i class="fa fa-eye"></i> View
                                    </a>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">No visit confirmations found.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            <div class="mt-4">
                {{ $visits->links() }}
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .tile-title-w-btn {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 25px;
        padding-bottom: 15px;
        border-bottom: 1px solid #eee;
    }
    .badge {
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    #visitsTable th {
        background-color: #f8f9fa;
        text-transform: uppercase;
        font-size: 12px;
        letter-spacing: 1px;
    }
</style>
@endpush
