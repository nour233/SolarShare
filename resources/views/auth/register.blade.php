<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Register - Solartec</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">

    <link href="https://fonts.googleapis.com" rel="preconnect">
    <link href="https://fonts.gstatic.com" rel="preconnect" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;500&family=Roboto:wght@500;700;900&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="/front/css/bootstrap.min.css" rel="stylesheet">
    <link href="/front/css/style.css" rel="stylesheet">
</head>

<body>
    <div class="container-fluid bg-dark p-0">
        <div class="row gx-0 d-none d-lg-flex">
            <div class="col-lg-7 px-5 text-start">
                <div class="h-100 d-inline-flex align-items-center me-4">
                    <small class="fa fa-map-marker-alt text-primary me-2"></small>
                    <small>123 Street, New York, USA</small>
                </div>
                <div class="h-100 d-inline-flex align-items-center">
                    <small class="far fa-clock text-primary me-2"></small>
                    <small>Mon - Fri : 09.00 AM - 09.00 PM</small>
                </div>
            </div>
        </div>
    </div>

    <nav class="navbar navbar-expand-lg bg-white navbar-light sticky-top p-0">
        <a href="{{ route('home') }}" class="navbar-brand d-flex align-items-center border-end px-4 px-lg-5">
            <h2 class="m-0 text-primary">Solartec</h2>
        </a>
        <a href="{{ route('home') }}" class="btn btn-primary rounded-0 py-4 px-lg-5 ms-auto">Home<i class="fa fa-home ms-3"></i></a>
    </nav>

    <main class="container-fluid bg-light py-5">
        <div class="container py-5">
            <div class="row justify-content-center">
                <div class="col-lg-6">
                    <div class="bg-white rounded p-5 shadow-sm">
                        <div class="text-center mb-4">
                            <h6 class="text-primary">Join Solartec</h6>
                            <h1 class="mb-3">Create Your Account</h1>
                            <p class="mb-0">Start your journey with renewable energy.</p>
                        </div>

                        @if ($errors->any())
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <form method="POST" action="{{ route('register') }}">
                            @csrf

                            <div class="mb-3">
                                <label for="name" class="form-label">Full name</label>
                                <input id="name" type="text" name="name" value="{{ old('name') }}"
                                    class="form-control border-0 bg-light py-3 @error('name') is-invalid @enderror"
                                    required autofocus>
                            </div>

                            <div class="mb-3">
                                <label for="email" class="form-label">Email address</label>
                                <input id="email" type="email" name="email" value="{{ old('email') }}"
                                    class="form-control border-0 bg-light py-3 @error('email') is-invalid @enderror"
                                    required>
                            </div>

                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <input id="password" type="password" name="password"
                                    class="form-control border-0 bg-light py-3 @error('password') is-invalid @enderror"
                                    required>
                            </div>

                            <div class="mb-4">
                                <label for="password_confirmation" class="form-label">Confirm password</label>
                                <input id="password_confirmation" type="password" name="password_confirmation"
                                    class="form-control border-0 bg-light py-3" required>
                            </div>

                            <button type="submit" class="btn btn-primary rounded-pill w-100 py-3">
                                Create Account<i class="fa fa-arrow-right ms-3"></i>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </main>
</body>

</html>
