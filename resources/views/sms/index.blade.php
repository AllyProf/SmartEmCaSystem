@extends('layouts.app')

@section('title', 'SMS')
@section('icon', 'fa-comment')
@section('page-title', 'SMS Management')
@section('page-description', 'Send and manage SMS messages')

@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
<li class="breadcrumb-item">SMS</li>
@endsection

@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="tile">
            <div class="tile-title-w-btn">
                <h3 class="title">SMS Logs</h3>
                <p><a class="btn btn-primary icon-btn" href="{{ route('sms.create') }}"><i class="fa fa-plus"></i>Send SMS</a></p>
            </div>
            <div class="row mb-3">
                <div class="col-md-4">
                    <div class="widget-small info coloured-icon">
                        <i class="icon fa fa-comment fa-2x"></i>
                        <div class="info">
                            <h4>Total SMS</h4>
                            <p><b>{{ $stats['total'] ?? 0 }}</b></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="widget-small success coloured-icon">
                        <i class="icon fa fa-check fa-2x"></i>
                        <div class="info">
                            <h4>Sent</h4>
                            <p><b>{{ $stats['sent'] ?? 0 }}</b></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="widget-small danger coloured-icon">
                        <i class="icon fa fa-times fa-2x"></i>
                        <div class="info">
                            <h4>Failed</h4>
                            <p><b>{{ $stats['failed'] ?? 0 }}</b></p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="tile-body">
                <div class="row mb-3">
                    <div class="col-md-4">
                        <input type="text" class="form-control" id="searchInput" placeholder="Search by phone, customer, or message...">
                    </div>
                    <div class="col-md-3">
                        <select class="form-control" id="statusFilter">
                            <option value="">All Status</option>
                            <option value="sent">Sent</option>
                            <option value="failed">Failed</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select class="form-control" id="typeFilter">
                            <option value="">All Types</option>
                            <option value="engagement">Engagement</option>
                            <option value="holiday">Holiday</option>
                            <option value="custom">Custom</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button class="btn btn-secondary btn-block" id="clearFilters">Clear</button>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover table-bordered" id="smsTable">
                        <thead>
                            <tr>
                                <th>Phone Number</th>
                                <th>Customer</th>
                                <th>Message</th>
                                <th>Type</th>
                                <th>Status</th>
                                <th>Sent By</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($smsLogs as $sms)
                            <tr>
                                <td>{{ $sms->phone_number }}</td>
                                <td>{{ $sms->customer->name ?? 'N/A' }}</td>
                                <td>{{ Str::limit($sms->message, 50) }}</td>
                                <td><span class="badge badge-info">{{ ucfirst($sms->sms_type) }}</span></td>
                                <td>
                                    <span class="badge badge-{{ $sms->status === 'sent' ? 'success' : 'danger' }}">{{ ucfirst($sms->status) }}</span>
                                </td>
                                <td>{{ $sms->sender->name ?? 'N/A' }}</td>
                                <td>{{ $sms->created_at->format('M d, Y H:i') }}</td>
                                <td>
                                    @if($sms->status === 'failed')
                                        <button class="btn btn-sm btn-info" onclick="showErrorDetails({{ $sms->id }})" title="View Error">
                                            <i class="fa fa-info-circle"></i> Error
                                        </button>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
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
        "order": [[6, "desc"]], // Sort by date descending
        "columnDefs": [
            { "orderable": false, "targets": 7 } // Disable sorting on Actions column
        ]
    });
    
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
    
    function showErrorDetails(id) {
        // Get error details from the row
        var row = $('#smsTable tbody tr').filter(function() {
            return $(this).find('button[onclick*="' + id + '"]').length > 0;
        });
        
        var phoneNumber = row.find('td:eq(0)').text().trim();
        var message = row.find('td:eq(2)').text().trim();
        
        // Try to get error from API response (would need to fetch from server)
        var errorMessage = 'Failed to send SMS. Please check the phone number format.';
        
        swal({
            title: "SMS Error Details",
            html: "<strong>Phone Number:</strong> " + phoneNumber + "<br><br>" +
                  "<strong>Message:</strong> " + message + "<br><br>" +
                  "<strong>Error:</strong> " + errorMessage + "<br><br>" +
                  "<small class='text-muted'>Note: Phone numbers must be 12 digits starting with 255 (e.g., 255612345678) or 10 digits starting with 0 (e.g., 0612345678)</small>",
            type: "error",
            confirmButtonText: "OK",
            width: '600px'
        });
    }
</script>
@endpush

