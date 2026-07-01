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
                <div class="d-flex align-items-center">
                    <div class="form-group mb-0 mr-3">
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fa fa-search"></i></span>
                            </div>
                            <input type="text" id="user-search" class="form-control" placeholder="Search users..." autocomplete="off">
                        </div>
                    </div>
                    <a class="btn btn-primary icon-btn" href="{{ route('users.create') }}"><i class="fa fa-plus"></i>Add User</a>
                </div>
            </div>
            <div class="tile-body" id="user-table-container">
                @include('auth.partials._user_table')
            </div>
        </div>
    </div>
</div>

{{-- Hidden form for actions --}}
<form id="action-form" method="POST" style="display:none;">
    @csrf
    <input type="hidden" name="_method" id="action-method" value="POST">
</form>
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        // Real-time search
        let searchTimer;
        $('#user-search').on('keyup', function() {
            clearTimeout(searchTimer);
            let query = $(this).val();
            
            searchTimer = setTimeout(function() {
                fetchUsers(query);
            }, 500); // Wait 500ms after last keystroke
        });

        function fetchUsers(query = '', page = 1) {
            $('#user-table-container').css('opacity', '0.5');
            $.ajax({
                url: "{{ route('users.index') }}?search=" + query + "&page=" + page,
                type: 'GET',
                success: function(data) {
                    $('#user-table-container').html(data);
                    $('#user-table-container').css('opacity', '1');
                }
            });
        }

        // Pagination click handling
        $(document).on('click', '#pagination-links a', function(e) {
            e.preventDefault();
            let page = $(this).attr('href').split('page=')[1];
            let query = $('#user-search').val();
            fetchUsers(query, page);
        });

        // SweetAlert Delete
        $(document).on('click', '.btn-delete-user', function() {
            let url = $(this).data('url');
            let name = $(this).data('name');
            
            Swal.fire({
                title: 'Delete User?',
                text: "Are you sure you want to delete " + name + "? This action cannot be undone.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $('#action-form').attr('action', url);
                    $('#action-method').val('DELETE');
                    $('#action-form').submit();
                }
            });
        });

        // SweetAlert Toggle Status
        $(document).on('click', '.btn-toggle-status', function() {
            let url = $(this).data('url');
            let name = $(this).data('name');
            let status = $(this).data('status');
            let actionText = status === 'activate' ? 'activate' : 'deactivate';
            
            Swal.fire({
                title: ucfirst(actionText) + ' User?',
                text: "Are you sure you want to " + actionText + " " + name + "?",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#940000',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, ' + actionText + ' it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $('#action-form').attr('action', url);
                    $('#action-method').val('POST');
                    $('#action-form').submit();
                }
            });
        });

        // SweetAlert Reset Device
        $(document).on('click', '.btn-reset-device', function() {
            let url = $(this).data('url');
            let name = $(this).data('name');
            
            Swal.fire({
                title: 'Reset Device Lock?',
                text: "Reset mobile and web device locks for " + name + "? They can sign in again on a new phone or browser.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#940000',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, reset it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $('#action-form').attr('action', url);
                    $('#action-method').val('POST');
                    $('#action-form').submit();
                }
            });
        });

        function ucfirst(string) {
            return string.charAt(0).toUpperCase() + string.slice(1);
        }
    });
</script>
@endpush

