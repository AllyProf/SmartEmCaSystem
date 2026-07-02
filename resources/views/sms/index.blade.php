@extends('layouts.app')

@section('title', 'SMS')
@section('icon', 'fa-comment')
@section('page-title', 'SMS Management')
@section('page-description', 'Send and manage SMS messages')

@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
<li class="breadcrumb-item">SMS</li>
@endsection

@push('styles')
<style>
    @media (max-width: 767.98px) {
        .sms-page .tile-title-w-btn {
            flex-direction: column;
            align-items: flex-start !important;
        }

        .sms-page .tile-title-w-btn p {
            margin-top: 10px;
            width: 100%;
        }

        .sms-page .tile-title-w-btn .btn {
            width: 100%;
        }

        .sms-page .sms-filters .col-12 {
            margin-bottom: 10px;
        }

        .sms-page .sms-table-wrapper {
            overflow-x: visible;
            border: none;
        }

        .sms-page .responsive-table,
        .sms-page .responsive-table thead,
        .sms-page .responsive-table tbody,
        .sms-page .responsive-table th,
        .sms-page .responsive-table td,
        .sms-page .responsive-table tr {
            display: block;
        }

        .sms-page .responsive-table thead tr {
            position: absolute;
            top: -9999px;
            left: -9999px;
        }

        .sms-page .responsive-table tbody tr {
            border: 1px solid #dee2e6;
            margin-bottom: 16px;
            padding: 8px 10px 4px;
            border-radius: 8px;
            background: #fff;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        .sms-page .responsive-table tbody tr.empty-row td {
            display: block;
            padding: 20px 10px !important;
            text-align: center;
        }

        .sms-page .responsive-table tbody tr.empty-row td:before {
            display: none;
        }

        .sms-page .responsive-table td {
            border: none;
            border-bottom: 1px solid #eee;
            position: relative;
            padding: 10px 10px 10px 42% !important;
            text-align: left !important;
            min-height: 42px;
            display: flex;
            align-items: center;
            flex-wrap: wrap;
        }

        .sms-page .responsive-table td:last-child {
            border-bottom: 0;
            justify-content: flex-start;
        }

        .sms-page .responsive-table td:before {
            position: absolute;
            left: 10px;
            width: 38%;
            padding-right: 8px;
            white-space: nowrap;
            font-weight: 600;
            color: #666;
            content: attr(data-label);
            font-size: 0.8rem;
        }

        .sms-page .responsive-table td.sms-mobile-header {
            background: #f8f9fa;
            justify-content: center;
            padding-left: 10px !important;
            border-bottom: 2px solid #dee2e6;
            font-weight: 700;
            font-size: 1rem;
        }

        .sms-page .responsive-table td.sms-mobile-header:before {
            display: none;
        }

        .sms-page .responsive-table td[data-label="Message"] {
            white-space: normal;
            word-break: break-word;
        }

        .sms-page .pagination {
            flex-wrap: wrap;
        }

        .sms-page .pagination .page-link {
            padding: 0.4rem 0.65rem;
            font-size: 0.875rem;
        }

        .sms-page .btn-cancel-batch {
            width: 100%;
            margin-top: 8px;
        }
    }

    /* Bootstrap 4 doesn't support gap utilities */
    .sms-page .scheduled-batch-actions > * + * {
        margin-left: 8px;
    }
    @media (max-width: 767.98px) {
        .sms-page .scheduled-batch-actions > * + * {
            margin-left: 0;
            margin-top: 8px;
        }
    }
</style>
@endpush

@section('content')
<div class="row sms-page">
    <div class="col-md-12">
        <div class="tile">
            <div class="tile-title-w-btn">
                <h3 class="title">SMS Logs</h3>
                <p><a class="btn btn-primary icon-btn" href="{{ route('sms.create') }}"><i class="fa fa-plus"></i>Send SMS</a></p>
            </div>
            <div class="tile-body">
                @if(isset($scheduledBatches) && $scheduledBatches->isNotEmpty())
                <div class="alert alert-warning mb-4">
                    <strong><i class="fa fa-clock-o"></i> Pending Scheduled SMS</strong>
                    <ul class="list-unstyled mb-0 mt-2">
                        @foreach($scheduledBatches as $batch)
                        <li class="d-flex flex-wrap justify-content-between align-items-center border-bottom py-2">
                            <span class="mb-2 mb-md-0">
                                <strong>{{ $batch->total }}</strong> message(s) scheduled for
                                <strong>{{ $batch->scheduled_at->format('M d, Y H:i') }}</strong>
                                <span class="text-muted">· {{ Str::limit($batch->message_template, 50) }}</span>
                                @if($batch->status === 'paused')
                                    <span class="badge badge-secondary ml-2"><i class="fa fa-pause"></i> Paused</span>
                                @endif
                            </span>
                            <span class="d-flex flex-wrap scheduled-batch-actions">
                                <a class="btn btn-sm btn-secondary"
                                   href="{{ route('sms.schedules.edit', $batch->id) }}">
                                    <i class="fa fa-pencil"></i> Edit
                                </a>
                                @if($batch->status === 'scheduled')
                                    <form action="{{ route('sms.schedules.pause', $batch->id) }}" method="POST" class="mb-0 d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-warning">
                                            <i class="fa fa-pause"></i> Pause
                                        </button>
                                    </form>
                                @else
                                    <form action="{{ route('sms.schedules.resume', $batch->id) }}" method="POST" class="mb-0 d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-success">
                                            <i class="fa fa-play"></i> Resume
                                        </button>
                                    </form>
                                @endif
                                <form action="{{ route('sms.schedules.cancel', $batch->id) }}" method="POST" class="mb-0 cancel-batch-form d-inline">
                                    @csrf
                                    <button type="button" class="btn btn-sm btn-danger btn-cancel-batch"
                                        data-total="{{ $batch->total }}"
                                        data-datetime="{{ $batch->scheduled_at->format('M d, Y H:i') }}">
                                        <i class="fa fa-times"></i> Cancel Batch
                                    </button>
                                </form>
                            </span>
                        </li>
                        @endforeach
                    </ul>
                </div>
                @endif
                <div class="row mb-3 sms-filters">
                    <div class="col-12 col-md-4">
                        <input type="text" class="form-control" id="searchInput" placeholder="Search by phone, customer, or message...">
                    </div>
                    <div class="col-12 col-md-3">
                        <select class="form-control" id="statusFilter">
                            <option value="">All Status</option>
                            <option value="sent">Sent</option>
                            <option value="failed">Failed</option>
                            <option value="scheduled">Scheduled</option>
                            <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-3">
                        <select class="form-control" id="typeFilter">
                            <option value="">All Types</option>
                            <option value="engagement">Engagement</option>
                            <option value="holiday">Holiday</option>
                            <option value="custom">Custom</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-2">
                        <button class="btn btn-secondary btn-block" id="clearFilters">Clear</button>
                    </div>
                </div>
                <div class="table-responsive sms-table-wrapper">
                    <table class="table table-hover table-bordered responsive-table" id="smsTable">
                        <thead>
                            <tr>
                                <th>Phone Number</th>
                                <th>Customer</th>
                                <th>Message</th>
                                <th>Type</th>
                                <th>Status</th>
                                <th>Sent By</th>
                                <th>Send Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($smsLogs as $sms)
                            @php
                                $displayDate = match (true) {
                                    $sms->status === 'scheduled' && $sms->scheduled_at => $sms->scheduled_at,
                                    $sms->status === 'cancelled' => $sms->updated_at,
                                    (bool) $sms->sent_at => $sms->sent_at,
                                    default => $sms->created_at,
                                };
                            @endphp
                            <tr>
                                <td class="sms-mobile-header" data-label="Phone Number">{{ $sms->phone_number }}</td>
                                <td data-label="Customer">{{ $sms->customer->name ?? 'N/A' }}</td>
                                <td data-label="Message">{{ Str::limit($sms->message, 50) }}</td>
                                <td data-label="Type"><span class="badge badge-info">{{ ucfirst($sms->sms_type) }}</span></td>
                                <td data-label="Status">
                                    <span class="d-none">{{ $sms->status }}</span>
                                    @if($sms->status === 'sent')
                                        <span class="badge badge-success">Sent</span>
                                    @elseif($sms->status === 'failed')
                                        <span class="badge badge-danger">Failed</span>
                                    @elseif($sms->status === 'scheduled')
                                        <span class="badge badge-warning">
                                            <i class="fa fa-clock-o"></i> Scheduled
                                        </span>
                                    @elseif($sms->status === 'cancelled')
                                        <span class="badge badge-dark">Cancelled</span>
                                    @else
                                        <span class="badge badge-secondary">{{ ucfirst($sms->status) }}</span>
                                    @endif
                                </td>
                                <td data-label="Sent By">{{ $sms->sender->name ?? 'N/A' }}</td>
                                <td data-label="Send Date" data-order="{{ $displayDate->timestamp }}">
                                    {{ $displayDate->format('M d, Y H:i') }}
                                    @if($sms->status === 'scheduled')
                                        <small class="text-muted d-block">Scheduled send</small>
                                    @elseif($sms->status === 'cancelled')
                                        <small class="text-muted d-block">Cancelled</small>
                                    @endif
                                </td>
                                <td data-label="Actions">
                                    @if($sms->status === 'scheduled')
                                        <form action="{{ route('sms.cancel', $sms->id) }}" method="POST" class="d-inline cancel-sms-form">
                                            @csrf
                                            <button type="button" class="btn btn-sm btn-danger btn-cancel-sms" title="Cancel"
                                                data-phone="{{ $sms->phone_number }}">
                                                <i class="fa fa-times"></i> Cancel
                                            </button>
                                        </form>
                                    @elseif($sms->status === 'failed')
                                        <button type="button" class="btn btn-sm btn-info btn-sms-error" title="View Error"
                                            data-phone="{{ $sms->phone_number }}"
                                            data-message="{{ e(Str::limit($sms->message, 100)) }}">
                                            <i class="fa fa-info-circle"></i> Error
                                        </button>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr class="empty-row">
                                <td colspan="8" class="text-center">No SMS logs found. <a href="{{ route('sms.create') }}">Send one now</a></td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($smsLogs->hasPages())
                <div class="mt-3">
                    <nav aria-label="Page navigation">
                        <ul class="pagination justify-content-center">
                            {{-- Previous Page Link --}}
                            @if ($smsLogs->onFirstPage())
                                <li class="page-item disabled">
                                    <span class="page-link">Previous</span>
                                </li>
                            @else
                                <li class="page-item">
                                    <a class="page-link" href="{{ $smsLogs->previousPageUrl() }}" rel="prev">Previous</a>
                                </li>
                            @endif

                            {{-- Pagination Elements --}}
                            @php
                                $currentPage = $smsLogs->currentPage();
                                $lastPage = $smsLogs->lastPage();
                                $startPage = max(1, $currentPage - 2);
                                $endPage = min($lastPage, $currentPage + 2);
                            @endphp

                            @if($startPage > 1)
                                <li class="page-item">
                                    <a class="page-link" href="{{ $smsLogs->url(1) }}">1</a>
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
                                        <a class="page-link" href="{{ $smsLogs->url($page) }}">{{ $page }}</a>
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
                                    <a class="page-link" href="{{ $smsLogs->url($lastPage) }}">{{ $lastPage }}</a>
                                </li>
                            @endif

                            {{-- Next Page Link --}}
                            @if ($smsLogs->hasMorePages())
                                <li class="page-item">
                                    <a class="page-link" href="{{ $smsLogs->nextPageUrl() }}" rel="next">Next</a>
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
                            Showing {{ $smsLogs->firstItem() }} to {{ $smsLogs->lastItem() }} of {{ $smsLogs->total() }} SMS logs
                        </small>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('vali-master/docs/js/plugins/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('vali-master/docs/js/plugins/dataTables.bootstrap.min.js') }}"></script>
<script>
    var table = $('#smsTable').DataTable({
        "paging": false,
        "info": false,
        "dom": "rt",
        "order": [[6, "desc"]], // Sort by date descending
        "columnDefs": [
            { "orderable": false, "targets": 7 } // Disable sorting on Actions column
        ]
    });
    
    // Apply status filter from URL (after cancel redirect)
    const urlStatus = new URLSearchParams(window.location.search).get('status');
    if (urlStatus) {
        $('#statusFilter').val(urlStatus);
        table.column(4).search(urlStatus).draw();
    }

    @if(session('sms_cancelled'))
    Swal.fire({
        title: 'Cancelled',
        text: @json(session('sms_cancelled')),
        icon: 'success',
        confirmButtonColor: '#940000',
        confirmButtonText: 'OK'
    });
    @endif

    // Search functionality
    $('#searchInput').on('keyup', function() {
        table.search(this.value).draw();
    });
    
    // Status filter
    $('#statusFilter').on('change', function() {
        table.column(4).search(this.value).draw();
    });
    
    // Type filter
    $('#typeFilter').on('change', function() {
        table.column(3).search(this.value).draw();
    });
    
    // Clear filters
    $('#clearFilters').on('click', function() {
        $('#searchInput').val('');
        $('#statusFilter').val('');
        $('#typeFilter').val('');
        table.search('').columns().search('').draw();
    });
    
    // Cancel batch with SweetAlert
    $(document).on('click', '.btn-cancel-batch', function() {
        const form = $(this).closest('form');
        const total = $(this).data('total');
        const datetime = $(this).data('datetime');

        Swal.fire({
            title: 'Cancel Scheduled Batch?',
            html: 'Cancel all <strong>' + total + '</strong> scheduled messages for <strong>' + datetime + '</strong>?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#940000',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, cancel batch',
            cancelButtonText: 'No, keep scheduled'
        }).then((result) => {
            if (result.isConfirmed) {
                form.submit();
            }
        });
    });

    // Cancel single scheduled SMS with SweetAlert
    $(document).on('click', '.btn-cancel-sms', function() {
        const form = $(this).closest('form');
        const phone = $(this).data('phone');

        Swal.fire({
            title: 'Cancel Scheduled SMS?',
            text: 'Cancel the scheduled SMS to ' + phone + '?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#940000',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, cancel it',
            cancelButtonText: 'No, keep scheduled'
        }).then((result) => {
            if (result.isConfirmed) {
                form.submit();
            }
        });
    });

    // SMS error details with SweetAlert
    $(document).on('click', '.btn-sms-error', function() {
        const phoneNumber = $(this).data('phone');
        const message = $(this).data('message');
        const errorMessage = 'Failed to send SMS. Please check the phone number format.';

        Swal.fire({
            title: 'SMS Error Details',
            html: '<strong>Phone Number:</strong> ' + phoneNumber + '<br><br>' +
                  '<strong>Message:</strong> ' + message + '<br><br>' +
                  '<strong>Error:</strong> ' + errorMessage + '<br><br>' +
                  '<small class="text-muted">Note: Phone numbers must be 12 digits starting with 255 (e.g., 255612345678) or 10 digits starting with 0 (e.g., 0612345678)</small>',
            icon: 'error',
            confirmButtonColor: '#940000',
            confirmButtonText: 'OK',
            width: window.innerWidth < 768 ? '95%' : '600px'
        });
    });
</script>
@endpush

