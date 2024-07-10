@extends('layouts.app', ['title' => 'Email List'])

@section('content')
@include('users.partials.header', [
    'title' => 'Email List',
    'description' => 'List of fetched emails.',
    'class' => 'col-lg-7'
])

<div class="container-fluid mt-lg--6">
    <div class="row">
        <div class="col-xl-12 mb-3">
            <div class="float-right">
                <!-- Any additional content or controls can be placed here -->
            </div>
        </div>
        <div class="col-xl-12">
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="emailListTable" class="table align-items-center table-flush">
                            <thead class="thead-light">
                                <tr>
                                    <th>{{ __('Subject') }}</th>
                                    <th>{{ __('From') }}</th>
                                    <th>{{ __('Date') }}</th>
                                    {{-- <th>{{ __('Date') }}</th> --}}
                                    <th>{{ __('Actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Loop through each message -->
                                @foreach ($messages as $message)
                                    <tr>
                                        <td>{{ $message->getSubject() }}</td>
                                        <td>{{ $message->getFrom()[0]->mail }}</td>
                                        <td>{{ $message->getDate() }}</td>
                                        <td>
                                            <a href="#" class="btn btn-sm btn-primary view-email"
                                                data-toggle="modal"
                                                data-target="#emailModal"
                                                data-subject="{{ $message->getSubject() }}"
                                                data-from="{{ $message->getFrom()[0]->mail }}"
                                                data-date="{{ $message->getDate() }}"
                                                data-body="{{ htmlspecialchars($message->getTextBody() ?? $message->getTextBody()) }}"
                                            >
                                                {{ __('View') }}
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach

                                
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="emailModal" tabindex="-1" role="dialog" aria-labelledby="emailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="emailModalLabel">Email Details</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="modalEmailSubject"></div>
                <div id="modalEmailFrom"></div>
                <div id="modalEmailDate"></div>
                <hr>
                <div id="modalEmailBody"></div>
                <!-- Additional fields as needed -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>


<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/js/toastr.min.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/css/toastr.min.css" />

<script>
    $(document).ready(function() {
        $('.view-email').on('click', function(e) {
            e.preventDefault(); // Prevent default action of the anchor tag

            var subject = $(this).data('subject');
            var from = $(this).data('from');
            var date = $(this).data('date');
            var body = $(this).data('body');

            console.log('Subject:', subject); // Check if data is correctly read
            console.log('From:', from);
            console.log('Date:', date);
            console.log('Body:', body);

            // Populate modal with email details
            $('#modalEmailSubject').text('Subject: ' + subject);
            $('#modalEmailFrom').text('From: ' + from);
            $('#modalEmailDate').text('Date: ' + date);
            $('#modalEmailBody').html(body); // Use .html() to display HTML content

            $('#emailModal').modal('show'); // Show the modal
        });
    });

    @if(session()->has('success'))
            toastr.success('{{ session('success') }}');
        @endif

        @if(session()->has('error'))
            toastr.error('{{ session('error') }}');
        @endif
</script>
@endsection

