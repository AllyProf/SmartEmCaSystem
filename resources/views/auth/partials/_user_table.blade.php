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
                <td>
                    <b>{{ $user->name }}</b><br>
                    <small class="text-muted">{{ $user->staff_id ?? 'No ID' }}</small>
                </td>
                <td>{{ $user->email }}<br><small>{{ $user->phone }}</small></td>
                <td><span class="badge badge-info">{{ ucfirst(str_replace('_', ' ', $user->role)) }}</span></td>
                <td>
                    @if($user->is_active)
                        <span class="badge badge-success">Active</span>
                    @else
                        <span class="badge badge-danger">Inactive</span>
                    @endif
                </td>
                <td>
                    <div class="small">
                        <div class="mb-1">
                            <i class="fa fa-mobile"></i>
                            @if($user->device_id)
                                <span class="badge badge-success">Mobile locked</span>
                            @else
                                <span class="badge badge-secondary">Mobile free</span>
                            @endif
                        </div>
                        <div>
                            <i class="fa fa-globe"></i>
                            @if($user->web_device_id)
                                <span class="badge badge-success">Web locked</span>
                            @else
                                <span class="badge badge-secondary">Web free</span>
                            @endif
                        </div>
                        @if($user->device_id || $user->web_device_id)
                            <button type="button" class="btn btn-sm btn-outline-danger py-0 mt-1 btn-reset-device"
                                    data-url="{{ route('users.reset_device', $user->id) }}"
                                    data-name="{{ $user->name }}"
                                    title="Reset device locks">
                                <i class="fa fa-refresh"></i> Reset both
                            </button>
                        @endif
                    </div>
                </td>
                <td>{{ $user->creator->name ?? 'System' }}</td>
                <td>{{ $user->created_at->format('M d, Y') }}</td>
                <td>
                    <div class="btn-group">
                        <a href="{{ route('users.edit', $user->id) }}" class="btn btn-sm btn-outline-primary" title="Edit">
                            <i class="fa fa-pencil"></i>
                        </a>
                        <button type="button" class="btn btn-sm btn-outline-{{ $user->is_active ? 'warning' : 'success' }} btn-toggle-status" 
                                data-url="{{ route('users.toggle_status', $user->id) }}"
                                data-status="{{ $user->is_active ? 'deactivate' : 'activate' }}"
                                data-name="{{ $user->name }}"
                                title="{{ $user->is_active ? 'Deactivate' : 'Activate' }}">
                            <i class="fa fa-power-off"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-danger btn-delete-user" 
                                data-url="{{ route('users.destroy', $user->id) }}"
                                data-name="{{ $user->name }}"
                                title="Delete">
                            <i class="fa fa-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="8" class="text-center">No users found.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if($users->hasPages())
<div class="mt-3" id="pagination-links">
    {{ $users->appends(['search' => request('search')])->links('pagination::bootstrap-4') }}
</div>
@endif
