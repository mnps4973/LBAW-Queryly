<article class="post" data-id="{{ $answer->id }}" user-id="{{Auth::id()}}" id="answer-{{ $answer->id}}">
    <div class="card">
        <div class="card-body">         
            @if($showTitle)
                <h3 class="card-title">Answer to: {{ App\Models\Post::find($answer->parentpost)->title }}</h3>
                <p>
                <a href="{{route('posts.postPage', $answer->parentpost)}}#answer-{{ $answer->id}}" class="btn cardBtn">See Post</a>
            @else
                <p>
            @endif
                @if(Auth::check())
                    @if(Auth::id() == $answer->userid || Auth::user()->hasRole('Moderator'))
                        <a href="#" class="delete btn" id="delete-post">Delete Answer</a>
                    @endif
                    @if(Auth::id() == $answer->userid)
                        <a class="btn cardBtn" aria-current="page" href="{{  route('posts.edit', $answer->id)  }}">Edit</a>
                    @endif
                @endif
                </p>

            <p class="card-text">{{ $answer->posttext }}</p>
            @if( $answer->edited )
                <span class="editedLabel">(edited)</span>
                <br>
            @endif
            <form method="post" action="{{ route('posts.report', $answer->id) }}">
                {{ csrf_field() }}
                <button type="submit" class="btn cardBtn report"> Report Answer </button>
            </form>
            {{ $answer->postdate }}
            @if(!$showTitle)
                @php
                    $username = DB::table('users')->find($answer->userid)->username;
                @endphp
                <a class="btn" aria-current="page" href="{{route('users.profile', $answer->userid)}}">&#64;{{ $username }}</a>
            @endif
            <br>
            @if(!$showTitle)
                @php
                    $correct = $answer->iscorrect;
                @endphp
                @if( $correct )
                    <i class="fa-solid fa-circle-check check"></i>
                @else
                    <i class="fa-regular fa-circle-check check"></i>
                @endif
            @endif

            @php
                $stars = DB::table('stars')->where('postid', $answer->id)->get();
            @endphp
            @if(Auth::check())
                @php
                    $userStar = false;
                    for($i = 0; $i < count($stars); $i++){
                        if($stars[$i]->userid === Auth::id()) $userStar = true;
                    }
                @endphp
                @if($userStar)
                    <i class="fa-solid fa-star star">&nbsp;<span class="starLabel">{{ count($stars) }}</span></i>  
                @else
                    <i class="fa-regular fa-star star">&nbsp;<span class="starLabel">{{ count($stars) }}</span></i>  
                @endif
            @else
                <i class="fa-regular fa-star">&nbsp;<span class="starLabel">{{ count($stars) }}</span></i>
            @endif
            @if(!$showTitle)
                <a class="btn" aria-current="page" href="{{route('addComment', $answer->id)}}">Add Comment</a>
                <br>
                @php
                    $answerComments = app\Http\Controllers\CommentController::showComments($answer->id);
                @endphp
                @if($answerComments->count() != 0)
                    <div class="accordion" id="accordionExample">
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingTwo">
                                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#answer-{{ $answer->id }}" aria-expanded="false" aria-controls="collapseTwo">
                                    Show Comments ({{count($answerComments)}})
                                </button>
                            </h2>
                            <div id="answer-{{ $answer->id }}" class="accordion-collapse collapse" aria-labelledby="headingTwo" data-bs-parent="#accordionExample">
                                <div class="accordion-body">
                                    @foreach($answerComments as $comment)
                                        @include('partials.comment')
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            @endif
        </div>
    </div>
</article>
<br>



