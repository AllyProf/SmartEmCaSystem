<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') - Smart EmCa System</title>
    <!-- Main CSS-->
    <link rel="stylesheet" type="text/css" href="{{ asset('vali-master/docs/css/main.css') }}">
    <!-- Font-icon css-->
    <link rel="stylesheet" type="text/css" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <style>
        body { 
            background-color: #f5f5f5; 
            font-family: "Century Gothic", AppleGothic, sans-serif;
        }
        .visit-container {
            max-width: 900px;
            margin: 40px auto;
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .header-brand {
            color: #940000;
            text-align: center;
            margin-bottom: 30px;
        }
        .header-brand h1 { font-weight: 900; letter-spacing: -1px; }
        .btn-primary {
            background-color: #940000;
            border-color: #940000;
        }
        .btn-primary:hover, .btn-primary:focus {
            background-color: #7a0000;
            border-color: #7a0000;
        }
        
        /* Mobile Responsiveness Improvements (Mobile First) */
        @media (max-width: 768px) {
            .visit-container {
                margin: 0;
                border-radius: 0;
                padding: 20px 15px;
                box-shadow: none;
            }
            .header-brand h1 { font-size: 2rem; }
            .header-brand h4 { font-size: 1.1rem; }
            .radio-inline { display: block; margin-bottom: 10px; margin-left: 0 !important; }
            .signature-wrapper { height: 150px !important; } /* Smaller signature area on mobile */
        }

        /* Loading Spinner CSS */
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
            border: 3px solid rgba(255,255,255,0.3);
            border-radius: 50%;
            border-top-color: #fff;
            animation: spin 1s ease-in-out infinite;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
    @stack('styles')
</head>
<body>
    <div class="container-fluid p-0 p-lg-3">
        <div class="container">
            <div class="visit-container">
                <div class="header-brand">
                    <img src="{{ asset('images/logo.jpg') }}" alt="Logo" style="max-height: 80px; margin-bottom: 15px;">
                    <h4>@yield('header')</h4>
                </div>
                
                @yield('content')
            </div>
        </div>
    </div>

    <!-- Essential JS -->
    <script src="{{ asset('vali-master/docs/js/jquery-3.2.1.min.js') }}"></script>
    <script src="{{ asset('vali-master/docs/js/popper.min.js') }}"></script>
    <script src="{{ asset('vali-master/docs/js/bootstrap.min.js') }}"></script>
    <script src="{{ asset('vali-master/docs/js/main.js') }}"></script>

    <script>
        $(document).ready(function() {
            // Global Form Submission Loading State
            $('form').on('submit', function(e) {
                const $form = $(this);
                const $btn = $form.find('button[type="submit"]');
                
                // We use a small timeout to allow other 'submit' listeners (like signature checks) 
                // to run and potentially call e.preventDefault()
                setTimeout(function() {
                    if (!e.isDefaultPrevented() && $form[0].checkValidity()) {
                        $btn.addClass('btn-loading');
                    }
                }, 10);
            });

            // Display Session Success/Error messages with SweetAlert2
            @if(session('success'))
                Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    text: "{{ session('success') }}",
                    confirmButtonColor: '#940000'
                });
            @endif

            @if(session('error'))
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: "{{ session('error') }}",
                    confirmButtonColor: '#940000'
                });
            @endif

            @if($errors->any())
                Swal.fire({
                    icon: 'error',
                    title: 'Validation Error',
                    html: '<ul class="text-left">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>',
                    confirmButtonColor: '#940000'
                });
            @endif
        });
    </script>
    @stack('scripts')
</body>
</html>
