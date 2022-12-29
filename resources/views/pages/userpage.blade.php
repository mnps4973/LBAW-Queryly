@extends('layouts.app')

@section('content')
    <br>
    <h2 class="centering">Our Users</h2>
    <hr>
    <br>
    @include('partials.searchbar', ['userSearch' => TRUE])
    
    <br>

    <ul>
        @foreach($users as $user)
            @include('partials.user')
        @endforeach
    </ul>

@endsection