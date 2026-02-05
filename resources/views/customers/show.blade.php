@extends('layouts.app')

@section('title', 'Customer Details')
@section('icon', 'fa-user')
@section('page-title', 'Customer Details')

@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
<li class="breadcrumb-item"><a href="{{ route('customers.index') }}">Customers</a></li>
<li class="breadcrumb-item">Details</li>
@endsection

@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="tile">
            <div class="tile-title-w-btn">
                <h3 class="title">Customer Information</h3>
                <p>
                    <a class="btn btn-primary" href="{{ route('customers.edit', $customer->id) }}"><i class="fa fa-edit"></i>Edit</a>
                    <a class="btn btn-info" href="{{ route('sms.create', ['customer_id' => $customer->id]) }}"><i class="fa fa-comment"></i>Send SMS</a>
                    <a class="btn btn-success" href="{{ route('follow-ups.create', ['customer_id' => $customer->id]) }}"><i class="fa fa-calendar-plus-o"></i>Add Follow-up</a>
                </p>
            </div>
            <div class="tile-body">
                <div class="row">
                    <div class="col-md-6">
                        <table class="table table-bordered">
                            <tr>
                                <th>Name:</th>
                                <td>{{ $customer->name ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <th>Phone Number:</th>
                                <td>{{ $customer->phone_number }}</td>
                            </tr>
                            <tr>
                                <th>Location:</th>
                                <td>{{ $customer->location ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <th>Visiting Purpose:</th>
                                <td>{{ $customer->visiting_purpose ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <th>Created By:</th>
                                <td>{{ $customer->creator->name ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <th>Created At:</th>
                                <td>{{ $customer->created_at->format('M d, Y H:i') }}</td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="tile">
            <h3 class="tile-title">SMS History</h3>
            <div class="tile-body">
                <div class="table-responsive">
                    <table class="table table-hover table-bordered">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Type</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($customer->smsLogs as $sms)
                            <tr>
                                <td>{{ $sms->created_at->format('M d, Y') }}</td>
                                <td><span class="badge badge-info">{{ ucfirst($sms->sms_type) }}</span></td>
                                <td><span class="badge badge-{{ $sms->status === 'sent' ? 'success' : 'danger' }}">{{ ucfirst($sms->status) }}</span></td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="3" class="text-center">No SMS sent to this customer</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="tile">
            <h3 class="tile-title">Follow-ups</h3>
            <div class="tile-body">
                <div class="table-responsive">
                    <table class="table table-hover table-bordered">
                        <thead>
                            <tr>
                                <th>Visit Date</th>
                                <th>Status</th>
                                <th>Next Follow-up</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($customer->followUps as $followUp)
                            <tr>
                                <td>{{ $followUp->visit_date->format('M d, Y') }}</td>
                                <td><span class="badge badge-{{ $followUp->status === 'completed' ? 'success' : ($followUp->status === 'cancelled' ? 'danger' : 'warning') }}">{{ ucfirst($followUp->status) }}</span></td>
                                <td>{{ $followUp->next_follow_up_date ? $followUp->next_follow_up_date->format('M d, Y') : 'N/A' }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="3" class="text-center">No follow-ups for this customer</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

