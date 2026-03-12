@extends('layouts.app')

@section('title', 'Follow-ups')
@section('icon', 'fa-calendar-check-o')
@section('page-title', 'Follow-ups')
@section('page-description', 'Manage customer follow-ups')

@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
<li class="breadcrumb-item">Follow-ups</li>
@endsection

@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="tile">
            <div class="tile-title-w-btn">
                <h3 class="title">All Follow-ups</h3>
                <p><a class="btn btn-primary icon-btn" href="{{ route('follow-ups.create') }}"><i class="fa fa-plus"></i>Add Follow-up</a></p>
            </div>
            <div class="tile-body">
                <div class="table-responsive">
                    <table class="table table-hover table-bordered">
                        <thead>
                            <tr>
                                <th>Customer</th>
                                <th>Visit Date</th>
                                <th>Purpose</th>
                                <th>Next Follow-up</th>
                                <th>Reminder</th>
                                <th>Status</th>
                                <th>Assigned To</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($followUps as $followUp)
                            <tr>
                                <td>{{ $followUp->customer->name ?? $followUp->customer->phone_number }}</td>
                                <td>{{ $followUp->visit_date->format('M d, Y') }}</td>
                                <td>{{ Str::limit($followUp->visit_purpose, 30) ?? 'N/A' }}</td>
                                <td>{{ $followUp->next_follow_up_date ? $followUp->next_follow_up_date->format('M d, Y') : 'N/A' }}</td>
                                <td class="text-center">
                                    @if($followUp->reminder_date)
                                        @if($followUp->reminder_sent_at)
                                            <span class="badge badge-success" title="Sent on {{ $followUp->reminder_sent_at->format('M d, Y H:i') }}"><i class="fa fa-check"></i> Sent</span>
                                        @else
                                            <span class="badge badge-warning text-dark" title="Scheduled for {{ $followUp->reminder_date->format('M d, Y') }}"><i class="fa fa-bell"></i> {{ $followUp->reminder_date->format('M d') }}</span>
                                        @endif
                                    @else
                                        <span class="text-muted small">—</span>
                                    @endif
                                </td>
                                <td><span class="badge badge-{{ $followUp->status === 'completed' ? 'success' : ($followUp->status === 'cancelled' ? 'danger' : 'warning') }}">{{ ucfirst($followUp->status) }}</span></td>
                                <td>{{ $followUp->assignedUser->name ?? 'N/A' }}</td>
                                <td>
                                    <a href="{{ route('follow-ups.show', $followUp->id) }}" class="btn btn-sm btn-info"><i class="fa fa-eye"></i></a>
                                    <a href="{{ route('follow-ups.edit', $followUp->id) }}" class="btn btn-sm btn-primary"><i class="fa fa-edit"></i></a>
                                    <form action="{{ route('follow-ups.destroy', $followUp->id) }}" method="POST" style="display: inline;" onsubmit="return confirm('Are you sure?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger"><i class="fa fa-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="text-center">No follow-ups found. <a href="{{ route('follow-ups.create') }}">Add one now</a></td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($followUps->hasPages())
                <div class="mt-3">
                    <nav aria-label="Page navigation">
                        <ul class="pagination justify-content-center">
                            {{-- Previous Page Link --}}
                            @if ($followUps->onFirstPage())
                                <li class="page-item disabled">
                                    <span class="page-link">Previous</span>
                                </li>
                            @else
                                <li class="page-item">
                                    <a class="page-link" href="{{ $followUps->previousPageUrl() }}" rel="prev">Previous</a>
                                </li>
                            @endif

                            {{-- Pagination Elements --}}
                            @php
                                $currentPage = $followUps->currentPage();
                                $lastPage = $followUps->lastPage();
                                $startPage = max(1, $currentPage - 2);
                                $endPage = min($lastPage, $currentPage + 2);
                            @endphp

                            @if($startPage > 1)
                                <li class="page-item">
                                    <a class="page-link" href="{{ $followUps->url(1) }}">1</a>
                                </li>
                                @if($startPage > 2)
                                    <li class="page-item disabled">
                                        <span class="page-link">...</span>
                                    </li>
                                @endif
                            @endif

                            @for($page = $startPage; $page <= $endPage; $page++)
                                @if ($page == $currentPage)
                                    <li class="page-item active">
                                        <span class="page-link">{{ $page }}</span>
                                    </li>
                                @else
                                    <li class="page-item">
                                        <a class="page-link" href="{{ $followUps->url($page) }}">{{ $page }}</a>
                                    </li>
                                @endif
                            @endfor

                            @if($endPage < $lastPage)
                                @if($endPage < $lastPage - 1)
                                    <li class="page-item disabled">
                                        <span class="page-link">...</span>
                                    </li>
                                @endif
                                <li class="page-item">
                                    <a class="page-link" href="{{ $followUps->url($lastPage) }}">{{ $lastPage }}</a>
                                </li>
                            @endif

                            {{-- Next Page Link --}}
                            @if ($followUps->hasMorePages())
                                <li class="page-item">
                                    <a class="page-link" href="{{ $followUps->nextPageUrl() }}" rel="next">Next</a>
                                </li>
                            @else
                                <li class="page-item disabled">
                                    <span class="page-link">Next</span>
                                </li>
                            @endif
                        </ul>
                    </nav>
                    <div class="text-center mt-2">
                        <small class="text-muted">
                            Showing {{ $followUps->firstItem() }} to {{ $followUps->lastItem() }} of {{ $followUps->total() }} follow-ups
                        </small>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

