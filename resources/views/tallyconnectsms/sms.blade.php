@extends('layouts.app', ['title' => __tr('Gmail To Web')])

@section('content')
@include('users.partials.header', [
    'title' => 'TallyConnects SMS',
    'description' => 'Enter Phone Number and Message to Send Msg.',
    'class' => 'col-lg-7'
])
<div class="container-fluid mt-lg--6">
    <div class="row">
        <div class="col-xl-12 mb-3">
            <div class="float-right">
                <!-- Additional content or controls can go here -->
            </div>
        </div>
        <div class="col-12 mb-4">
            <div class="card card-stats mb-4 mb-xl-0">
                <div class="card-body">
                    <div class="col-xl-12">
                        <form method="POST" action="{{ route('vendor.tallyconnectsms.read.sendsms') }}">
                            @csrf

                            <div class="form-group">
                                <label for="phone">{{ __tr('Phone Number') }}</label>
                                <input type="text" id="phone" name="phone" class="form-control" value="{{ old('phone') }}" required title="Please enter exactly 10 digits" placeholder="Please enter exactly 10 digits">
                                @error('phone')
                                <div class="text-danger">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label for="message">{{ __tr('Message') }}</label>
                                <textarea id="message" name="message" class="form-control" required placeholder="Enter your message">{{ old('message') }}</textarea>
                                @error('message')
                                <div class="text-danger">{{ $message }}</div>
                                @enderror
                            </div>
                            {{-- <div class="form-group">
                                <label for="message">{{ __tr('Message') }}</label>
                                <textarea id="message" name="message" class="form-control" required placeholder="Enter your message">{{ old('message') }}</textarea>
                                @error('message')
                                <div class="text-danger">{{ $message }}</div>
                                @enderror
                            </div> --}}

                            <!-- Add more form fields as needed -->

                            <button type="submit" class="btn btn-primary">{{ __tr('Send SMS') }}</button>
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
