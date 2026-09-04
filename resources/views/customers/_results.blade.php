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
                <td data-order="{{ $customer->updated_at->timestamp }}">
                    {{ $customer->name ?? 'N/A' }}
                    @if($customer->created_at->isToday())
                        <span class="badge badge-success ml-1">New</span>
                    @endif
                </td>
                <td>{{ $customer->phone_number }}</td>
                <td>{{ $customer->location ?? 'N/A' }}</td>
                <td>{{ Str::limit($customer->visiting_purpose, 50) ?? 'N/A' }}</td>
                <td data-order="{{ $customer->updated_at->timestamp }}">{{ $customer->created_at->format('M d, Y') }}</td>
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
                <td colspan="6" class="text-center">
                    @if($dateFrom || $dateTo || $region || $search)
                        No customers match the selected filters. <a href="{{ route('customers.index') }}">Clear filters</a>
                    @else
                        No customers found. <a href="{{ route('customers.create') }}">Add one now</a>
                    @endif
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div id="customersPagination">
    @if($customers->hasPages())
    <div class="mt-3">
        <nav aria-label="Page navigation">
            <ul class="pagination justify-content-center">
                @if ($customers->onFirstPage())
                    <li class="page-item disabled">
                        <span class="page-link">Previous</span>
                    </li>
                @else
                    <li class="page-item">
                        <a class="page-link" href="{{ $customers->previousPageUrl() }}" rel="prev">Previous</a>
                    </li>
                @endif

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
