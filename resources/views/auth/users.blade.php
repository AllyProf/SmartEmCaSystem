@extends('layouts.app')

@section('title', 'Users')
@section('icon', 'fa-user-plus')
@section('page-title', 'User Management')
@section('page-description', 'Manage system users')

@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
<li class="breadcrumb-item">Users</li>
@endsection

@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="tile">
            <div class="tile-title-w-btn">
                <h3 class="title">All Users</h3>
                <p><a class="btn btn-primary icon-btn" href="{{ route('users.create') }}"><i class="fa fa-plus"></i>Add User</a></p>
            </div>
            <div class="tile-body">
                <div class="table-responsive">
                    <table class="table table-hover table-bordered">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Device Lock</th>
                                <th>Created By</th>
                                <th>Created At</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($users as $user)
                            <tr>
                                <td>{{ $user->name }}</td>
                                <td>{{ $user->email }}</td>
                                <td><span class="badge badge-info">{{ ucfirst(str_replace('_', ' ', $user->role)) }}</span></td>
                                <td>
                                    @if($user->is_active)
                                        <span class="badge badge-success">Active</span>
                                    @else
                                        <span class="badge badge-danger">Inactive</span>
                                    @endif
                                </td>
                                <td>
                                    @if($user->device_id)
                                        <div class="d-flex align-items-center justify-content-between">
                                            <span class="badge badge-success"><i class="fa fa-lock"></i> Locked</span>
                                            <form action="{{ route('users.reset_device', $user->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to reset this device lock? The staff will be able to login from a different phone.')">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline-danger py-0" title="Reset Device Lock">
                                                    <i class="fa fa-refresh"></i> Reset
                                                </button>
                                            </form>
                                        </div>
                                    @else
                                        <span class="badge badge-secondary">Not Linked</span>
                                    @endif
                                </td>
                                <td>{{ $user->creator->name ?? 'System' }}</td>
                                <td>{{ $user->created_at->format('M d, Y') }}</td>
                                <td>
                                    <div class="btn-group">
                                        <a href="{{ route('users.edit', $user->id) }}" class="btn btn-sm btn-outline-primary" title="Edit">
                                            <i class="fa fa-pencil"></i>
                                        </a>
                                        <form action="{{ route('users.toggle_status', $user->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to {{ $user->is_active ? 'deactivate' : 'activate' }} this user?')">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-{{ $user->is_active ? 'warning' : 'success' }}" title="{{ $user->is_active ? 'Deactivate' : 'Activate' }}">
                                                <i class="fa fa-power-off"></i>
                                            </button>
                                        </form>
                                        <form action="{{ route('users.destroy', $user->id) }}" method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to permanently delete this user? This action cannot be undone.')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="text-center">No users found. <a href="{{ route('users.create') }}">Add one now</a></td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($users->hasPages())
                <div class="mt-3">
                    <nav aria-label="Page navigation">
                        <ul class="pagination justify-content-center">
                            {{-- Previous Page Link --}}
                            @if ($users->onFirstPage())
                                <li class="page-item disabled">
                                    <span class="page-link">Previous</span>
                                </li>
                            @else
                                <li class="page-item">
                                    <a class="page-link" href="{{ $users->previousPageUrl() }}" rel="prev">Previous</a>
                                </li>
                            @endif

                            {{-- Pagination Elements --}}
                            @php
                                $currentPage = $users->currentPage();
                                $lastPage = $users->lastPage();
                                $startPage = max(1, $currentPage - 2);
                                $endPage = min($lastPage, $currentPage + 2);
                            @endphp

                            @if($startPage > 1)
                                <li class="page-item">
                                    <a class="page-link" href="{{ $users->url(1) }}">1</a>
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
                                        <a class="page-link" href="{{ $users->url($page) }}">{{ $page }}</a>
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
                                    <a class="page-link" href="{{ $users->url($lastPage) }}">{{ $lastPage }}</a>
                                </li>
                            @endif

                            {{-- Next Page Link --}}
                            @if ($users->hasMorePages())
                                <li class="page-item">
                                    <a class="page-link" href="{{ $users->nextPageUrl() }}" rel="next">Next</a>
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
                            Showing {{ $users->firstItem() }} to {{ $users->lastItem() }} of {{ $users->total() }} users
                        </small>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

