@extends('layouts.app', ['title' => 'Fetch Emails'])

@section('content')
@include('users.partials.header', [
    'title' => 'Fetch Emails',
    'description' => 'Enter SMTP credentials and email address to fetch emails.',
    'class' => 'col-lg-7'
])

<div class="container-fluid mt--6 mt-6">
    <div class="row">
        <div class="col-xl-12">
            <form action="{{ route('vendor.emailtoweb.emails.fetchWithCredentials') }}" method="POST">
                @csrf
                <div class="form-group">
                    <label for="username">Email</label>
                    <input type="email" class="form-control" id="username" name="username" required>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
                <button type="submit" class="btn btn-primary">Fetch Emails</button>
            </form>
            
        </div>
    </div>
</div>
@endsection
