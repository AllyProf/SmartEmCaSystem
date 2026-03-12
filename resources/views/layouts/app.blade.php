<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Smart EmCa System')</title>
    
    <!-- Main CSS-->
    <link rel="stylesheet" type="text/css" href="{{ asset('vali-master/docs/css/main.css') }}">
    <!-- Font-icon css-->
    <link rel="stylesheet" type="text/css" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
    
    <style>
        :root {
            --primary: #940000;
            --secondary: #6c757d;
        }
        body, h1, h2, h3, h4, h5, h6, p, span, div, a, input, button, select, textarea {
            font-family: "Century Gothic", CenturyGothic, AppleGothic, sans-serif !important;
        }
        .fa {
            font-family: FontAwesome !important;
        }
        body {
            font-family: "Century Gothic", CenturyGothic, AppleGothic, sans-serif;
        }
        .app-header {
            background-color: #940000 !important;
        }
        .app-header__logo {
            background-color: #000000 !important; /* Logo area black for contrast against red header */
            font-family: inherit;
        }
        .app-sidebar__user {
            background: #000000 !important; /* Removed red from photo section as requested */
        }
        .widget-small.primary.coloured-icon {
            background-color: #940000;
        }
        .btn-primary {
            background-color: #940000;
            border-color: #940000;
        }
        .btn-primary:hover {
            background-color: #7a0000;
            border-color: #7a0000;
        }
        .app-menu__item.active {
            background-color: #940000;
        }
        .app-menu__item:hover {
            background-color: rgba(148, 0, 0, 0.1);
        }
    </style>
    
    @stack('styles')
</head>
<body class="app sidebar-mini">
    <!-- Navbar-->
    <header class="app-header">
        <a class="app-header__logo" href="{{ route('dashboard') }}">EmCa Tech</a>
        <a class="app-sidebar__toggle" href="#" data-toggle="sidebar" aria-label="Hide Sidebar"></a>
        <!-- Navbar Right Menu-->
        <ul class="app-nav">
            <!-- User Menu-->
            <li class="dropdown">
                <a class="app-nav__item" href="#" data-toggle="dropdown" aria-label="Open Profile Menu">
                    <i class="fa fa-user fa-lg"></i>
                </a>
                <ul class="dropdown-menu settings-menu dropdown-menu-right">
                    <li><a class="dropdown-item" href="#"><i class="fa fa-user fa-lg"></i> Profile</a></li>
                    <li>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="dropdown-item" style="border: none; background: none; width: 100%; text-align: left;">
                                <i class="fa fa-sign-out fa-lg"></i> Logout
                            </button>
                        </form>
                    </li>
                </ul>
            </li>
        </ul>
    </header>
    
    <!-- Sidebar menu-->
    <div class="app-sidebar__overlay" data-toggle="sidebar"></div>
    <aside class="app-sidebar">
        <div class="app-sidebar__user">
            <img class="app-sidebar__user-avatar" src="https://ui-avatars.com/api/?name={{ urlencode(auth()->user()->name) }}&background=940000&color=fff" alt="User Image">
            <div>
                <p class="app-sidebar__user-name">{{ auth()->user()->name }}</p>
                <p class="app-sidebar__user-designation">{{ ucfirst(str_replace('_', ' ', auth()->user()->role)) }}</p>
            </div>
        </div>
        <ul class="app-menu">
            <li>
                <a class="app-menu__item {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
                    <i class="app-menu__icon fa fa-dashboard"></i>
                    <span class="app-menu__label">Dashboard</span>
                </a>
            </li>
            <li>
                <a class="app-menu__item {{ request()->routeIs('customers.*') ? 'active' : '' }}" href="{{ route('customers.index') }}">
                    <i class="app-menu__icon fa fa-users"></i>
                    <span class="app-menu__label">Customers</span>
                </a>
            </li>
            <li>
                <a class="app-menu__item {{ request()->routeIs('sms.*') ? 'active' : '' }}" href="{{ route('sms.index') }}">
                    <i class="app-menu__icon fa fa-comment"></i>
                    <span class="app-menu__label">SMS</span>
                </a>
            </li>
            <li>
                <a class="app-menu__item {{ request()->routeIs('follow-ups.*') ? 'active' : '' }}" href="{{ route('follow-ups.index') }}">
                    <i class="app-menu__icon fa fa-calendar-check-o"></i>
                    <span class="app-menu__label">Follow-ups</span>
                </a>
            </li>
            <li>
                <a class="app-menu__item {{ request()->routeIs('visits.index') ? 'active' : '' }}" href="{{ route('visits.index') }}">
                    <i class="app-menu__icon fa fa-file-text-o"></i>
                    <span class="app-menu__label">Confirmations</span>
                </a>
            </li>
            @if(auth()->user()->role === 'ceo' || auth()->user()->role === 'super_admin')
            <li>
                <a class="app-menu__item {{ request()->routeIs('attendance.*') ? 'active' : '' }}" href="{{ route('attendance.index') }}">
                    <i class="app-menu__icon fa fa-clock-o"></i>
                    <span class="app-menu__label">Attendance</span>
                </a>
            </li>
            @endif
            @can('manageUsers', App\Models\User::class)
            <li>
                <a class="app-menu__item {{ request()->routeIs('users.*') ? 'active' : '' }}" href="{{ route('users.index') }}">
                    <i class="app-menu__icon fa fa-user-plus"></i>
                    <span class="app-menu__label">Users</span>
                </a>
            </li>
            @endcan
        </ul>
    </aside>
    
    <main class="app-content">
        <div class="app-title">
            <div>
                <h1><i class="fa @yield('icon', 'fa-dashboard')"></i> @yield('page-title', 'Dashboard')</h1>
                <p>@yield('page-description', '')</p>
            </div>
            <ul class="app-breadcrumb breadcrumb">
                <li class="breadcrumb-item"><i class="fa fa-home fa-lg"></i></li>
                @yield('breadcrumb')
            </ul>
        </div>
        
        @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
        @endif

        @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
        @endif

        @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
        @endif

        @yield('content')
    </main>
    
    <!-- Essential javascripts for application to work-->
    <script src="{{ asset('vali-master/docs/js/jquery-3.2.1.min.js') }}"></script>
    <script src="{{ asset('vali-master/docs/js/popper.min.js') }}"></script>
    <script src="{{ asset('vali-master/docs/js/bootstrap.min.js') }}"></script>
    <script src="{{ asset('vali-master/docs/js/main.js') }}"></script>
    <!-- The javascript plugin to display page loading on top-->
    <script src="{{ asset('vali-master/docs/js/plugins/pace.min.js') }}"></script>
    <!-- Sweet Alert -->
    <script src="{{ asset('vali-master/docs/js/plugins/sweetalert.min.js') }}"></script>
    
    <script>
        // Function to cleanup Sweet Alert overlays
        function cleanupSweetAlert() {
            $('.sweet-overlay').remove();
            $('.sweet-alert').remove();
            $('body').removeClass('stop-scrolling');
        }

        // Sweet Alert for success/error messages
        @if(session('success'))
            swal({
                title: "Success!",
                text: "{{ session('success') }}",
                type: "success",
                confirmButtonText: "OK",
                closeOnConfirm: true
            }, function() {
                // Cleanup after alert is confirmed
                setTimeout(cleanupSweetAlert, 200);
            });
            // Also cleanup after 5 seconds as fallback
            setTimeout(cleanupSweetAlert, 5000);
        @endif

        @if(session('error'))
            swal({
                title: "Error!",
                text: "{{ session('error') }}",
                type: "error",
                confirmButtonText: "OK",
                closeOnConfirm: true
            }, function() {
                // Cleanup after alert is confirmed
                setTimeout(cleanupSweetAlert, 200);
            });
            // Also cleanup after 5 seconds as fallback
            setTimeout(cleanupSweetAlert, 5000);
        @endif

        // Clean up any leftover Sweet Alert overlays on page load
        $(document).ready(function() {
            // Remove any existing overlays that might be stuck
            setTimeout(cleanupSweetAlert, 1000);
            
            // Monitor and cleanup stuck overlays every 2 seconds
            setInterval(function() {
                // If overlay exists but alert doesn't have visible class, remove it
                if ($('.sweet-overlay').length > 0 && !$('.sweet-alert').hasClass('visible')) {
                    cleanupSweetAlert();
                }
            }, 2000);
        });

        // CSRF Token setup for AJAX
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });
    </script>
    
    @stack('scripts')
</body>
</html>

