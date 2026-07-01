<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" type="text/css" href="{{ asset('vali-master/docs/css/main.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('css/brand-overrides.css') }}">
    <link rel="stylesheet" type="text/css" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
    <title>Login - Smart EmCa System</title>
    <style>
        body, h1, h2, h3, h4, h5, h6, p, span, div, a, input, button, select, textarea {
            font-family: "Century Gothic", CenturyGothic, AppleGothic, sans-serif !important;
        }
        .fa {
            font-family: FontAwesome !important;
        }
        .material-half-bg .cover {
            height: 100vh;
            background-color: #940000;
            background-image: url('{{ asset('images/background_image.jpg') }}');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            position: relative;
        }
        .material-half-bg .cover::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.35);
        }
        .btn-primary {
            background-color: #940000;
            border-color: #940000;
        }
        .btn-primary:hover, .btn-primary:focus {
            background-color: #7a0000;
            border-color: #7a0000;
        }
        a {
            color: #940000;
        }
        a:hover {
            color: #7a0000;
        }
        .cursor-pointer {
            cursor: pointer;
        }
        .login-content .form-control:focus,
        .login-content textarea.form-control:focus,
        .login-content select.form-control:focus {
            border-color: #940000;
            box-shadow: 0 0 0 0.2rem rgba(148, 0, 0, 0.25);
            outline: none;
        }
        .login-content .input-group .form-control:focus {
            border-color: #940000;
            box-shadow: none;
        }
        .login-content .input-group:focus-within {
            box-shadow: 0 0 0 0.2rem rgba(148, 0, 0, 0.25);
            border-radius: 0.25rem;
        }
        .login-content .input-group:focus-within .form-control,
        .login-content .input-group:focus-within .input-group-text {
            border-color: #940000;
        }
        .btn-loading {
            position: relative;
            color: transparent !important;
            pointer-events: none;
        }
        .btn-loading::after {
            content: "";
            position: absolute;
            width: 20px;
            height: 20px;
            top: 50%;
            left: 50%;
            margin-top: -10px;
            margin-left: -10px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: #fff;
            animation: spin 1s ease-in-out infinite;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <section class="material-half-bg">
        <div class="cover"></div>
    </section>

    <header class="navbar navbar-expand-md navbar-dark d-flex justify-content-between p-3" style="position: absolute; top:0; left: 0; right: 0; width: 100%; z-index: 1000;">
        <a href="{{ route('staff.sign') }}" class="btn btn-outline-light" style="border-width: 2px; font-weight: bold; background-color: rgba(255,255,255,0.1);">
            <i class="fa fa-map-marker fa-lg fa-fw"></i> STAFF SIGN AT HQ
        </a>
        <a href="{{ route('visits.verify') }}" class="btn btn-outline-light" style="border-width: 2px; font-weight: bold; background-color: rgba(255,255,255,0.1);">
            <i class="fa fa-pencil-square-o fa-lg fa-fw"></i> CUSTOMER VISIT
        </a>
    </header>

    <section class="login-content">
        <div class="login-box">
            <form class="login-form" action="{{ route('login') }}" method="POST" id="loginForm">
                @csrf
                <h3 class="login-head"><i class="fa fa-lg fa-fw fa-user"></i>SIGN IN</h3>
                
                @if(session('success'))
                <div class="alert alert-success">
                    {{ session('success') }}
                </div>
                @endif

                @if(session('error'))
                <div class="alert alert-danger">
                    {{ session('error') }}
                </div>
                @endif

                @if ($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
                @endif

                <div class="form-group">
                    <label class="control-label">EMAIL</label>
                    <input class="form-control" type="email" name="email" placeholder="Email" value="{{ old('email') }}" autofocus required>
                </div>
                <div class="form-group">
                    <label class="control-label">PASSWORD</label>
                    <div class="input-group">
                        <input class="form-control" type="password" name="password" id="password" placeholder="Password" required>
                        <div class="input-group-append">
                            <span class="input-group-text cursor-pointer toggle-password" data-target="#password">
                                <i class="fa fa-eye"></i>
                            </span>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <div class="utility">
                        <div class="animated-checkbox">
                            <label>
                                <input type="checkbox" name="remember"><span class="label-text">Stay Signed in</span>
                            </label>
                        </div>
                        <p class="semibold-text mb-2"><a href="{{ route('password.request') }}">Forgot Password ?</a></p>
                    </div>
                </div>
                <div class="form-group btn-container">
                    <button class="btn btn-primary btn-block" type="submit" id="loginBtn"><i class="fa fa-sign-in fa-lg fa-fw"></i>SIGN IN</button>
                </div>
            </form>
        </div>
    </section>
    <script src="{{ asset('vali-master/docs/js/jquery-3.2.1.min.js') }}"></script>
    <script src="{{ asset('vali-master/docs/js/popper.min.js') }}"></script>
    <script src="{{ asset('vali-master/docs/js/bootstrap.min.js') }}"></script>
    <script src="{{ asset('vali-master/docs/js/main.js') }}"></script>
    <script src="{{ asset('vali-master/docs/js/plugins/pace.min.js') }}"></script>
    <script>
        $(document).ready(function() {
            $('#loginForm').on('submit', function() {
                if (this.checkValidity()) {
                    $('#loginBtn').addClass('btn-loading');
                }
            });

            $('.toggle-password').click(function() {
                const target = $($(this).data('target'));
                const icon = $(this).find('i');

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
</body>
</html>
