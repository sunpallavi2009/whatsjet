@extends('layouts.app', ['title' => 'Email List'])

@section('content')
@include('users.partials.header', [
    'title' => 'Email List',
    'description' => 'List of fetched emails.',
    'class' => 'col-lg-7'
])

<div class="container-fluid mt--6">
    <div class="row">
        <div class="col-xl-12">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>From</th>
                        <th>Subject</th>
                        <th>Date</th>
                        <th>Body</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($messages as $message)
                    <tr>
                        <td>{{ $message->getFrom()[0]->mail }}</td>
                        <td>{{ $message->getSubject() }}</td>
                        <td>{{ $message->getDate() }}</td>
                        <td>{{ $message->getTextBody() }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
