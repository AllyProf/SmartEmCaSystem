@extends('layouts.app')

@section('title', 'Add User')
@section('icon', 'fa-user-plus')
@section('page-title', 'Add User')
@section('page-description', 'Create a new user account')

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
                        <label class="control-label">Password <span class="text-danger">*</span></label>
                        <input class="form-control" type="password" name="password" required>
                        <small class="form-text text-muted">Minimum 8 characters</small>
                    </div>
                    <div class="form-group">
                        <label class="control-label">Confirm Password <span class="text-danger">*</span></label>
                        <input class="form-control" type="password" name="password_confirmation" required>
                    </div>
                    <div class="form-group">
                        <label class="control-label">Role <span class="text-danger">*</span></label>
                        <select class="form-control" name="role" required>
                            @if(auth()->user()->isSuperAdmin())
                            <option value="ceo" {{ old('role') == 'ceo' ? 'selected' : '' }}>CEO</option>
                            @endif
                            <option value="staff" {{ old('role') == 'staff' ? 'selected' : '' }}>Staff</option>
                        </select>
                        <small class="form-text text-muted">
                            @if(auth()->user()->isCeo())
                            You can only create staff accounts.
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





