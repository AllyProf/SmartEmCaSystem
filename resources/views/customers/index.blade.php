@extends('layouts.app')

@section('title', 'Customers')
@section('icon', 'fa-users')
@section('page-title', 'Customers')
@section('page-description', 'Manage your customers')

@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
<li class="breadcrumb-item">Customers</li>
@endsection

@section('content')
<div class="row">
    <div class="col-md-6 col-lg-3">
        <div class="widget-small primary coloured-icon">
            <i class="icon fa fa-users fa-3x"></i>
            <div class="info">
                <h4>Total Customers</h4>
                <p><b>{{ $stats['total'] }}</b></p>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-lg-3">
        <div class="widget-small info coloured-icon">
            <i class="icon fa fa-user-plus fa-3x"></i>
            <div class="info">
                <h4>Added Today</h4>
                <p><b>{{ $stats['today'] }}</b></p>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-lg-3">
        <div class="widget-small warning coloured-icon">
            <i class="icon fa fa-calendar fa-3x"></i>
            <div class="info">
                <h4>This Week</h4>
                <p><b>{{ $stats['this_week'] }}</b></p>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-lg-3">
        <div class="widget-small danger coloured-icon">
            <i class="icon fa fa-calendar-check-o fa-3x"></i>
            <div class="info">
                <h4>This Month</h4>
                <p><b>{{ $stats['this_month'] }}</b></p>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="tile">
            <div class="tile-title-w-btn">
                <h3 class="title">All Customers</h3>
                <p><a class="btn btn-primary icon-btn" href="{{ route('customers.create') }}"><i class="fa fa-plus"></i>Add Customer</a></p>
            </div>
            <div class="tile-body">
                <div class="table-responsive">
                    <table class="table table-hover table-bordered" id="customersTable">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Phone Number</th>
                                <th>Location</th>
                                <th>Visiting Purpose</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($customers as $customer)
                            <tr>
                                <td>{{ $customer->name ?? 'N/A' }}</td>
                                <td>{{ $customer->phone_number }}</td>
                                <td>{{ $customer->location ?? 'N/A' }}</td>
                                <td>{{ Str::limit($customer->visiting_purpose, 50) ?? 'N/A' }}</td>
                                <td data-order="{{ $customer->created_at->timestamp }}">{{ $customer->created_at->format('M d, Y') }}</td>
                                <td>
                                    <a href="{{ route('customers.show', $customer->id) }}" class="btn btn-sm btn-info"><i class="fa fa-eye"></i></a>
                                    <a href="{{ route('customers.edit', $customer->id) }}" class="btn btn-sm btn-primary"><i class="fa fa-edit"></i></a>
                                    <form action="{{ route('customers.destroy', $customer->id) }}" method="POST" style="display: inline;" onsubmit="return confirm('Are you sure?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger"><i class="fa fa-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="text-center">No customers found. <a href="{{ route('customers.create') }}">Add one now</a></td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($customers->hasPages())
                <div class="mt-3">
                    <nav aria-label="Page navigation">
                        <ul class="pagination justify-content-center">
                            {{-- Previous Page Link --}}
                            @if ($customers->onFirstPage())
                                <li class="page-item disabled">
                                    <span class="page-link">Previous</span>
                                </li>
                            @else
                                <li class="page-item">
                                    <a class="page-link" href="{{ $customers->previousPageUrl() }}" rel="prev">Previous</a>
                                </li>
                            @endif

                            {{-- Pagination Elements --}}
                            @php
                                $currentPage = $customers->currentPage();
                                $lastPage = $customers->lastPage();
                                $startPage = max(1, $currentPage - 2);
                                $endPage = min($lastPage, $currentPage + 2);
                            @endphp

                            @if($startPage > 1)
                                <li class="page-item">
                                    <a class="page-link" href="{{ $customers->url(1) }}">1</a>
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
                                        <a class="page-link" href="{{ $customers->url($page) }}">{{ $page }}</a>
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
                                    <a class="page-link" href="{{ $customers->url($lastPage) }}">{{ $lastPage }}</a>
                                </li>
                            @endif

                            {{-- Next Page Link --}}
                            @if ($customers->hasMorePages())
                                <li class="page-item">
                                    <a class="page-link" href="{{ $customers->nextPageUrl() }}" rel="next">Next</a>
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
                            Showing {{ $customers->firstItem() }} to {{ $customers->lastItem() }} of {{ $customers->total() }} customers
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
    $('#customersTable').DataTable({
        "paging": false,
        "info": false,
        "order": [[4, "desc"]]
    });
</script>
@endpush

