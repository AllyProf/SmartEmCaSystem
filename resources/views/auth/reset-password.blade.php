<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" type="text/css" href="{{ asset('vali-master/docs/css/main.css') }}">
    <link rel="stylesheet" type="text/css" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
    <title>Reset Password - Smart EmCa System</title>
    <style>
        body, h1, h2, h3, h4, h5, h6, p, span, div, a, input, button, select, textarea {
            font-family: "Century Gothic", CenturyGothic, AppleGothic, sans-serif !important;
        }
        .fa {
            font-family: FontAwesome !important;
        }
        .material-half-bg .cover {
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
        a {
            color: #940000;
        }
        a:hover {
            color: #7a0000;
        }
    </style>
</head>
<body>
    <section class="material-half-bg">
        <div class="cover"></div>
    </section>

    <section class="login-content">
        <div class="logo">
            <h1>EmCa Tech</h1>
        </div>
        <div class="login-box" style="min-height: 520px;">
            <form class="login-form" action="{{ route('password.update') }}" method="POST">
                @csrf
                <h3 class="login-head"><i class="fa fa-lg fa-fw fa-key"></i>NEW PASSWORD</h3>
                
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

                <p class="text-muted text-center mb-4">OTP Verified! Please enter your new password below.</p>

                <div class="form-group">
                    <label class="control-label">NEW PASSWORD</label>
                    <input class="form-control" type="password" name="password" placeholder="Min 8 characters" autofocus required>
                </div>

                <div class="form-group">
                    <label class="control-label">CONFIRM PASSWORD</label>
                    <input class="form-control" type="password" name="password_confirmation" placeholder="Confirm new password" required>
                </div>
                
                <div class="form-group btn-container">
                    <button class="btn btn-primary btn-block" type="submit"><i class="fa fa-save fa-lg fa-fw"></i>RESET PASSWORD</button>
                </div>
            </form>
        </div>
    </section>
    <script src="{{ asset('vali-master/docs/js/jquery-3.2.1.min.js') }}"></script>
    <script src="{{ asset('vali-master/docs/js/popper.min.js') }}"></script>
    <script src="{{ asset('vali-master/docs/js/bootstrap.min.js') }}"></script>
    <script src="{{ asset('vali-master/docs/js/main.js') }}"></script>
</body>
</html>
