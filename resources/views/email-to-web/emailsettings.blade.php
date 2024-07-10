@extends('layouts.app', ['title' => 'Fetch Emails'])

@section('content')
@include('users.partials.header', [
    'title' => 'Fetch Emails',
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
                        <form action="{{ route('vendor.emailtoweb.emails.fetchWithCredentials') }}" method="POST">
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
                            <div id="host-port-fields" class="col-xl-12 mb-3" style="display: none;">
                                <div class="form-group">
                                    <label for="host">IMAP Host</label>
                                    <input type="text" class="form-control" id="host" name="host" placeholder="Enter IMAP Host">
                                </div>
                                <div class="form-group">
                                    <label for="port">IMAP Port</label>
                                    <input type="number" class="form-control" id="port" name="port" placeholder="Enter IMAP Port">
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
    document.addEventListener('DOMContentLoaded', function () {
        const togglePassword = document.querySelector('#togglePassword');
        const password = document.querySelector('#password');
        const hostPortFields = document.querySelector('#host-port-fields');
        const usernameField = document.querySelector('#username');
        const existingCredentials = @json($credentialsExist);

        // Initial setup based on existing credentials
        if (existingCredentials) {
            hostPortFields.style.display = 'none';
        } else {
            hostPortFields.style.display = 'block'; // Show by default if no existing credentials
        }

        // Show/hide IMAP host and port fields based on username input
        usernameField.addEventListener('input', function () {
            const email = usernameField.value.trim();
            if (email !== '') {
                $.ajax({
                    url: '{{ route('vendor.emailtoweb.checkEmailExists') }}',
                    method: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        email: email
                    },
                    success: function(response) {
                        if (response.exists) {
                            hostPortFields.style.display = 'none';
                        } else {
                            hostPortFields.style.display = 'block';
                        }
                    }
                });
            } else {
                hostPortFields.style.display = 'none';
            }
        });

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
    });
</script>

@endpush
@endsection
