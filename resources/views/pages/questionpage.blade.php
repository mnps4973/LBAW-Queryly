@inject('post', 'App\Http\Controllers\PostController')
@inject('user', 'App\Http\Controllers\UserController')
@inject('comment', 'App\Http\Controllers\CommentController')

@extends('layouts.app')

@section('title', $question->title)

@section('content')
    <p></p>
    <article class="post" data-id="{{ $question->id }}">
        <div class="card">
            <div class="card-body">
                <h2 class="card-title">Title: {{ $question->title }}</h2>
                @can('delete', $question)
                    <a class="delete" href="#"> Delete Question </a>
                @endcan
                @can('update', $question)
                    <a class="btn" aria-current="page" href="{{  route('posts.edit', $question->id)  }}">Edit</a>
                @endcan
                <p class="card-text">{{ $question->posttext }}</p>
                {{ $question->postdate }}
                <a class="btn" aria-current="page" href="{{route('users.profile', $question->userid)}}">&#64;{{ $question->user()->first()->username }}</a>
            </div>
        </div>
        @php
            $questionComments = app\Http\Controllers\CommentController::showComments($question->id);
        @endphp
        <p></p>
        <div class="flex-header">
            <h5>Comments: ({{count($questionComments)}})</h5>
            <a class="btn" aria-current="page" href="{{route('addComment', $question->id)}}">Add Comment</a>
        </div>
        <div class="accordion" id="accordionExample">
            <div class="accordion-item">
                <h2 class="accordion-header" id="headingOne">
                    <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="false" aria-controls="collapseOne">
                        Show Comments
                    </button>
                </h2>
                <div id="collapseOne" class="accordion-collapse collapse" aria-labelledby="headingOne" data-bs-parent="#accordionExample">
                    <div class="accordion-body">
                        @foreach($questionComments as $comment)
                            @include('partials.comment')
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </article>
    <p></p>
    <header>
        @php
            $answers = app\Http\Controllers\PostController::showAnswers($question->id);
        @endphp
        <div class="flex-header">
            <h3>Answers: ({{count($answers)}})</h3>
            <a class="btn" aria-current="page" href="{{ route('addAnswer') }}?question={{$question->id}}"> Post Answer </a>
        </div>
    </header>
    <p></p>
    <ul>
        @foreach($answers as $answer)
            @include('partials.answer', ['showTitle' => FALSE])
        @endforeach
    </ul>

@endsection