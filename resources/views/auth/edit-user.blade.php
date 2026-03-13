@extends('layouts.app')

@section('title', 'Edit User')
@section('icon', 'fa-pencil')
@section('page-title', 'Edit User')
@section('page-description', 'Modify an existing user account')

@push('styles')
<style>
    .cursor-pointer { cursor: pointer; }
</style>
@endpush

@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
<li class="breadcrumb-item"><a href="{{ route('users.index') }}">Users</a></li>
<li class="breadcrumb-item">Edit User</li>
@endsection

@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="tile">
            <h3 class="tile-title">Edit User Details - {{ $user->name }}</h3>
            <div class="tile-body">
                <form action="{{ route('users.update', $user->id) }}" method="POST">
                    @csrf
                    @method('PUT')
                    
                    <div class="form-group">
                        <label class="control-label">Name <span class="text-danger">*</span></label>
                        <input class="form-control" type="text" name="name" value="{{ old('name', $user->name) }}" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="control-label">Email <span class="text-danger">*</span></label>
                        <input class="form-control" type="email" name="email" value="{{ old('email', $user->email) }}" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="control-label">Phone Number <span class="text-danger">*</span></label>
                        <input class="form-control" type="text" name="phone" value="{{ old('phone', $user->phone) }}" placeholder="e.g. 255700000000" required>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label class="control-label">Password</label>
                            <div class="input-group">
                                <input class="form-control" type="password" name="password" id="password" autocomplete="new-password">
                                <div class="input-group-append">
                                    <span class="input-group-text cursor-pointer toggle-password" data-target="#password">
                                        <i class="fa fa-eye"></i>
                                    </span>
                                </div>
                            </div>
                            <small class="form-text text-muted">Leave blank to keep current password. Minimum 8 characters if changing.</small>
                        </div>
                        <div class="form-group col-md-6">
                            <label class="control-label">Confirm Password</label>
                            <div class="input-group">
                                <input class="form-control" type="password" name="password_confirmation" id="password_confirmation" autocomplete="new-password">
                                <div class="input-group-append">
                                    <span class="input-group-text cursor-pointer toggle-password" data-target="#password_confirmation">
                                        <i class="fa fa-eye"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="control-label">Role <span class="text-danger">*</span></label>
                        <select class="form-control" name="role" required>
                            @if(auth()->user()->isSuperAdmin())
                            <option value="ceo" {{ old('role', $user->role) == 'ceo' ? 'selected' : '' }}>CEO</option>
                            @endif
                            @if(auth()->user()->isSuperAdmin() || auth()->user()->isCeo() || auth()->user()->isHr())
                            <option value="hr" {{ old('role', $user->role) == 'hr' ? 'selected' : '' }}>HR Manager</option>
                            @endif
                            <option value="staff" {{ old('role', $user->role) == 'staff' ? 'selected' : '' }}>Staff</option>
                        </select>
                        <small class="form-text text-muted">
                            @if(auth()->user()->isCeo() || auth()->user()->isHr())
                            You cannot assign CEO accounts.
                            @endif
                        </small>
                    </div>

                    <div class="tile-footer">
                        <button class="btn btn-primary" type="submit"><i class="fa fa-fw fa-lg fa-check-circle"></i>Update User</button>
                        <a class="btn btn-secondary" href="{{ route('users.index') }}"><i class="fa fa-fw fa-lg fa-times-circle"></i>Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        $('.toggle-password').click(function() {
            let target = $($(this).data('target'));
            let icon = $(this).find('i');
            
            if (target.attr('type') === 'password') {
                target.attr('type', 'text');
                icon.removeClass('fa-eye').addClass('fa-eye-slash');
            } else {
                target.attr('type', 'password');
                icon.removeClass('fa-eye-slash').addClass('fa-eye');
            }
        });
    });
</script>
@endpush
