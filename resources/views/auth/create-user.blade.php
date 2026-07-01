@extends('layouts.app')

@section('title', 'Add User')
@section('icon', 'fa-user-plus')
@section('page-title', 'Add User')
@section('page-description', 'Create a new user account')

@push('styles')
<style>
    .cursor-pointer { cursor: pointer; }
</style>
@endpush

@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
<li class="breadcrumb-item"><a href="{{ route('users.index') }}">Users</a></li>
<li class="breadcrumb-item">Add User</li>
@endsection

@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="tile">
            <h3 class="tile-title">User Information</h3>
            <div class="tile-body">
                <form action="{{ route('users.store') }}" method="POST">
                    @csrf
                    <div class="form-group">
                        <label class="control-label">Name <span class="text-danger">*</span></label>
                        <input class="form-control" type="text" name="name" value="{{ old('name') }}" required>
                    </div>
                    <div class="form-group">
                        <label class="control-label">Email <span class="text-danger">*</span></label>
                        <input class="form-control" type="email" name="email" value="{{ old('email') }}" required>
                    </div>
                    <div class="form-group">
                        <label class="control-label">Phone Number <span class="text-danger">*</span></label>
                        <input class="form-control" type="text" name="phone" value="{{ old('phone') }}" placeholder="e.g. 255700000000" required>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label class="control-label">Password <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input class="form-control" type="password" name="password" id="password" required>
                                <div class="input-group-append">
                                    <span class="input-group-text cursor-pointer toggle-password" data-target="#password">
                                        <i class="fa fa-eye"></i>
                                    </span>
                                </div>
                            </div>
                            <small class="form-text text-muted">Minimum 8 characters</small>
                        </div>
                        <div class="form-group col-md-6">
                            <label class="control-label">Confirm Password <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input class="form-control" type="password" name="password_confirmation" id="password_confirmation" required>
                                <div class="input-group-append">
                                    <span class="input-group-text cursor-pointer toggle-password" data-target="#password_confirmation">
                                        <i class="fa fa-eye"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="control-label">Attendance Sign PIN (4 digits)</label>
                        <input class="form-control" type="password" name="sign_pin" maxlength="4" pattern="\d{4}" inputmode="numeric" placeholder="Required for HQ sign in/out">
                        <small class="form-text text-muted">Staff use this PIN on the mobile sign page.</small>
                    </div>

                    <div class="form-group">
                        <label class="control-label">Role <span class="text-danger">*</span></label>
                        <select class="form-control" name="role" required>
                            @if(auth()->user()->isSuperAdmin())
                            <option value="ceo" {{ old('role') == 'ceo' ? 'selected' : '' }}>CEO</option>
                            @endif
                            @if(auth()->user()->isSuperAdmin() || auth()->user()->isCeo() || auth()->user()->isHr())
                            <option value="hr" {{ old('role') == 'hr' ? 'selected' : '' }}>HR Manager</option>
                            @endif
                            <option value="staff" {{ old('role') == 'staff' ? 'selected' : '' }}>Staff</option>
                        </select>
                        <small class="form-text text-muted">
                            @if(auth()->user()->isCeo() || auth()->user()->isHr())
                            You cannot create CEO accounts.
                            @endif
                        </small>
                    </div>
                    <div class="tile-footer">
                        <button class="btn btn-primary" type="submit"><i class="fa fa-fw fa-lg fa-check-circle"></i>Create User</button>
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


