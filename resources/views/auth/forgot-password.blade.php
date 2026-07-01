<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" type="text/css" href="{{ asset('vali-master/docs/css/main.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('css/brand-overrides.css') }}">
    <link rel="stylesheet" type="text/css" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
    <title>Forgot Password - Smart EmCa System</title>
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
        .login-content .form-control:focus,
        .login-content textarea.form-control:focus,
        .login-content select.form-control:focus {
            border-color: #940000;
            box-shadow: 0 0 0 0.2rem rgba(148, 0, 0, 0.25);
            outline: none;
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

    <section class="login-content">
        <div class="login-box" style="min-height: 480px;">
            <form class="login-form" action="{{ route('password.otp.send') }}" method="POST" id="forgotPasswordForm">
                @csrf
                <h3 class="login-head"><i class="fa fa-lg fa-fw fa-lock"></i>FORGOT PASSWORD</h3>
                
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

                <p class="text-muted text-center mb-4">Enter your registered phone number to receive an OTP.</p>

                <div class="form-group">
                    <label class="control-label">PHONE NUMBER</label>
                    <input class="form-control" type="text" name="phone" placeholder="e.g. 0764628402" value="{{ old('phone') }}" autofocus required>
                </div>
                
                <div class="form-group btn-container">
                    <button class="btn btn-primary btn-block" type="submit" id="sendOtpBtn"><i class="fa fa-paper-plane fa-lg fa-fw"></i>SEND OTP</button>
                </div>
                
                <div class="form-group mt-3">
                    <p class="semibold-text mb-0 text-center"><a href="{{ route('login') }}"><i class="fa fa-angle-left fa-fw"></i> Back to Login</a></p>
                </div>
            </form>
        </div>
    </section>
    <script src="{{ asset('vali-master/docs/js/jquery-3.2.1.min.js') }}"></script>
    <script src="{{ asset('vali-master/docs/js/popper.min.js') }}"></script>
    <script src="{{ asset('vali-master/docs/js/bootstrap.min.js') }}"></script>
    <script src="{{ asset('vali-master/docs/js/main.js') }}"></script>
    <script>
        $(document).ready(function() {
            $('#forgotPasswordForm').on('submit', function() {
                if (this.checkValidity()) {
                    $('#sendOtpBtn').addClass('btn-loading');
                }
            });
        });
    </script>
</body>
</html>
