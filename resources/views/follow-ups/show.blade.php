@extends('layouts.app')

@section('title', 'Follow-up Details')
@section('icon', 'fa-calendar-check-o')
@section('page-title', 'Follow-up Details')

@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
<li class="breadcrumb-item"><a href="{{ route('follow-ups.index') }}">Follow-ups</a></li>
<li class="breadcrumb-item">Details</li>
@endsection

@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="tile">
            <div class="tile-title-w-btn">
                <h3 class="title">Follow-up Information</h3>
                <p>
                    <a class="btn btn-primary" href="{{ route('follow-ups.edit', $followUp->id) }}"><i class="fa fa-edit"></i>Edit</a>
                </p>
            </div>
            <div class="tile-body">
                <div class="row">
                    <div class="col-md-6">
                        <table class="table table-bordered">
                            <tr>
                                <th>Customer:</th>
                                <td>{{ $followUp->customer->name ?? $followUp->customer->phone_number }}</td>
                            </tr>
                            <tr>
                                <th>Visit Date:</th>
                                <td>{{ $followUp->visit_date->format('M d, Y') }}</td>
                            </tr>
                            <tr>
                                <th>Visit Purpose:</th>
                                <td>{{ $followUp->visit_purpose ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <th>Notes:</th>
                                <td>{{ $followUp->notes ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <th>Status:</th>
                                <td><span class="badge badge-{{ $followUp->status === 'completed' ? 'success' : ($followUp->status === 'cancelled' ? 'danger' : 'warning') }}">{{ ucfirst($followUp->status) }}</span></td>
                            </tr>
                            <tr>
                                <th>Next Follow-up Date:</th>
                                <td>{{ $followUp->next_follow_up_date ? $followUp->next_follow_up_date->format('M d, Y') : 'N/A' }}</td>
                            </tr>
                            <tr>
                                <th>Assigned To:</th>
                                <td>{{ $followUp->assignedUser->name ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <th>Created By:</th>
                                <td>{{ $followUp->creator->name ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <th>Created At:</th>
                                <td>{{ $followUp->created_at->format('M d, Y H:i') }}</td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection





