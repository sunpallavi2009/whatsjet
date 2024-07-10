@extends('layouts.app', ['title' => 'Fetch Gmails'])

@section('content')
@include('users.partials.header', [
    'title' => 'Fetch Gmails',
    'description' => 'Enter SMTP credentials and email address to fetch emails.',
    'class' => 'col-lg-7'
])

<div class="container-fluid mt-lg--6">
    <div class="row">
        <div class="col-xl-12 mb-3">
            <div class="float-right">
                <!-- Any additional content or controls can be placed here -->
            </div>
        </div>

        <div class="col-12 mb-4">
            <div class="card card-stats mb-4 mb-xl-0">
                <div class="card-body">
                    <div class="col-xl-12">
                        <form action="{{ route('vendor.gmailtoweb.gmails.fetchWithCredentials') }}" method="POST">
                            @csrf
                            <div class="col-xl-12">
                                <div class="form-group">
                                    <label for="username">Email</label>
                                    <input type="email" class="form-control" id="username" name="username" placeholder="Enter Email Address" required>
                                </div>
                            </div>
                            <div class="col-xl-12 mb-3">
                                <div class="form-group">
                                    <label for="password">Password</label>
                                    <div class="input-group">
                                        <input type="password" class="form-control" id="password" name="password" placeholder="******" required>
                                        <div class="input-group-append">
                                            <span class="input-group-text">
                                                <i class="fas fa-eye" id="togglePassword" style="cursor: pointer;"></i>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="nav-item text-right col">
                                <button type="submit" class="btn btn-primary">Fetch Emails</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/js/toastr.min.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/css/toastr.min.css" />

@push('appScripts')
<script>
        // Toggle password visibility
        togglePassword.addEventListener('click', function () {
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            password.setAttribute('type', type);
            this.classList.toggle('fa-eye-slash'); // Toggle the eye icon class
        });

        // Toastr notifications
        @if(session()->has('success'))
            toastr.success('{{ session('success') }}');
        @endif

        @if(session()->has('error'))
            toastr.error('{{ session('error') }}');
        @endif
    
</script>

@endpush
@endsection
