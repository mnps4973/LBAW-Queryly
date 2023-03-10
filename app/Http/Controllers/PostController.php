<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

use App\Models\Post;
use App\Models\User;
use App\Models\Role;
use App\Models\Comment;
use App\Models\Tag;
use App\Models\Question_tag;
use App\Models\Badge;
use App\Models\User_badge;
use App\Models\Star;
use App\Models\Report;
use App\Models\User_question;

class PostController extends Controller
{

    //Homepage
    public function show($id)
    {
      $post = Post::find($id);
      if($post->posttype == 'question')
        return view('pages.questionpage', ['question' => $post]);
      return view('pages.questionpage', ['question' => Post::find($post->parentpost)]);
    }

    //Own User Questions
    public function showUserQuestions($userID)
    {
      $user = User::find($userID);
      $questions = Post::where('posttype', 'question');
      $questions = $questions->where('userid', $userID)->get();
      return view('pages.userquestions', ['user' => $user, 'questions' => $questions]);
    }

    //Own User Answers
    public function showUserAnswers($userID)
    {
      $user = User::find($userID);
      $answers = Post::where('posttype', 'answer');
      $answers = $answers->where('userid', $userID)->get();
      return view('pages.useranswers', ['user' => $user, 'answers' => $answers]);
    }

    //Show Comments
    public function showComments($id){
      $post = Post::find($id);
      $comments = Comment::where('postid', $id)->get();
      return view('pages.postcomments', ['post' => $post, 'comments' => $comments]);
    }

    //Delete Post
    public function delete(Request $request, $id)
    {
      $post = Post::find($id);
      $this->authorize('delete', $post);
      $posttype = $post->posttype;
      $postid = $id;
      if($posttype === 'answer') $postid = $post->parentpost;

      $post->delete();
      
      $request->session()->flash('alert-success', 'Post has been successfully deleted!');
      if($posttype === 'answer') return redirect()->route('posts.postPage', ['id' => $postid]);
      return redirect()->route('homepage');
    }

    //Add Question
    public function showAddQuestionForm()
    {
      if (!Auth::check()) return redirect('/login');
      return view('pages.postquestion');
    }

    protected function postQuestion(Request $request)
    {
      $userID = Auth::id();
      $tags = Tag::all();

      $validate = $request->validate([
        'title' => 'required|max:200',
        'postText' => 'required|max:1000|',
      ]);

      $title = $request->input('title');
      $postText = $request->input('postText');
      $data = array('userid' => $userID, 'posttype' => 'question', 'title' => $title, 'posttext' => $postText);
      $postID = DB::table('posts')->insertGetId($data);
      $question = Post::find($postID);
      foreach($tags as $tag){
        if($request->has($tag->tagname)){
          Question_tag::insert(['postid' => $postID, 'tagid' => $tag->id]);
        }
      }
      $user = Auth::user();

      $questionCount = Post::where(['userid' => $user->id, 'posttype' => 'question'])->count();
      if($questionCount == 5){
        $badgeid = Badge::where('badgename', 'Posted 5 questions')->get()->value('id');
        $badge = User_badge::where(['userid' => $user->id, 'badgeid' => $badgeid])->get();
        if($badge->count() == 0) User_badge::insert(['userid' => $user->id, 'badgeid' => $badgeid]);
      } else if ($questionCount == 10){
        $badgeid = Badge::where('badgename', 'Posted 10 questions')->get()->value('id');
        $badge = User_badge::where(['userid' => $user->id, 'badgeid' => $badgeid])->get();
        if($badge->count() == 0) User_badge::insert(['userid' => $user->id, 'badgeid' => $badgeid]);
      } else if ($questionCount == 15){
        $badgeid = Badge::where('badgename', 'Posted 15 questions')->get()->value('id');
        $badge = User_badge::where(['userid' => $user->id, 'badgeid' => $badgeid])->get();
        if($badge->count() == 0) User_badge::insert(['userid' => $user->id, 'badgeid' => $badgeid]);
      } else if ($questionCount == 20){
        $badgeid = Badge::where('badgename', 'Posted 20 questions')->get()->value('id');
        $badge = User_badge::where(['userid' => $user->id, 'badgeid' => $badgeid])->get();
        if($badge->count() == 0) User_badge::insert(['userid' => $user->id, 'badgeid' => $badgeid]);
      }

      return view('pages.questionpage', ['user' => $user, 'question' => $question]);
    }

    //Add Answer
    public function showAddAnswerForm(Request $request)
    {
      if (!Auth::check()) return redirect('/login');
      $postParent = $request->input('question');
      $post = Post::find($postParent);
      return view('pages.postanswer', ['post' => $post]);
    }

    protected function postAnswer(Request $request)
    {
      $userID = Auth::id();

      $validate = $request->validate([
        'postText' => 'required|max:1000|'
      ]);

      $postText = $request->input('postText');
      $parentPost = $request->input('parentPost');
      error_log($parentPost);
      $data = array('userid' => $userID, 'posttype' => 'answer', 'posttext' => $postText, 'parentpost' => $parentPost, 'iscorrect' => 'false');
      $postID = DB::table('posts')->insertGetId($data);

      $user = Auth::user();

      $answerCount = Post::where(['userid' => $user->id, 'posttype' => 'answer'])->count();
      if($answerCount == 5){
        $badgeid = Badge::where('badgename', 'Answered 5 questions')->get()->value('id');
        $badge = User_badge::where(['userid' => $user->id, 'badgeid' => $badgeid])->get();
        if($badge->count() == 0) User_badge::insert(['userid' => $user->id, 'badgeid' => $badgeid]);
      } else if ($answerCount == 10){
        $badgeid = Badge::where('badgename', 'Answered 10 questions')->get()->value('id');
        $badge = User_badge::where(['userid' => $user->id, 'badgeid' => $badgeid])->get();
        if($badge->count() == 0) User_badge::insert(['userid' => $user->id, 'badgeid' => $badgeid]);
      } else if ($answerCount == 15){
        $badgeid = Badge::where('badgename', 'Answered 15 questions')->get()->value('id');
        $badge = User_badge::where(['userid' => $user->id, 'badgeid' => $badgeid])->get();
        if($badge->count() == 0) User_badge::insert(['userid' => $user->id, 'badgeid' => $badgeid]);
      } else if ($answerCount == 20){
        $badgeid = Badge::where('badgename', 'Answered 20 questions')->get()->value('id');
        $badge = User_badge::where(['userid' => $user->id, 'badgeid' => $badgeid])->get();
        if($badge->count() == 0) User_badge::insert(['userid' => $user->id, 'badgeid' => $badgeid]);
      }

      $request->session()->flash('alert-success', 'Answer has been successfully posted!');

      return redirect()->route('posts.postPage', ['id' => $parentPost]);
    }

    //Edit Post
    public function showEditForm($id){
      if (!Auth::check()) return redirect('/login');
      $post = Post::find($id);
      $user = Auth::id();
      return view('pages.editpost', ['post' => $post]);
    }

    public function update(Request $request, $id){
      $post = Post::find($id);
      $this->authorize('update', $post);
      $tags = Tag::all();

      if($post->posttype == 'question') {
        $validate = $request->validate([
          'title' => 'required|max:200',
          'postText' => 'required|max:1000|',
        ]);
      } else {
        $validate = $request->validate([
          'postText' => 'required|max:1000|',
        ]);
      }

      if($post->posttype == 'question' && $request->input('title')!=$post->title) $post->title = $request->input('title');
      if($request->input('postText')!=$post->posttext) $post->posttext = $request->input('postText');

      Question_tag::where('postid', $id)->delete();
      foreach($tags as $tag){
        if($request->has($tag->tagname)){
          Question_tag::insert(['postid' => $id, 'tagid' => $tag->id]);
        }
      }

      $post->save();

      $id=$post->id;

      if($post->posttype == 'question') {
        $request->session()->flash('alert-success', 'Question has been successfully edited!');
        return redirect()->route('posts.postPage',['id'=>$id]);
      }
      $request->session()->flash('alert-success', 'Answer has been successfully edited!');
      return redirect()->route('posts.postPage', ['id'=>$post->parentpost]);
    }

    //Edit Post Tags
    public function showEditTagsForm($id){
      if (!Auth::check()) return redirect('/login');
      $post = Post::find($id);
      return view('pages.editposttags', ['post' => $post]);
    }

    public function updateTags(Request $request, $id){
      $post = Post::find($id);
      $tags = Tag::all();
      $this->authorize('updateTags', $post);

      Question_tag::where('postid', $id)->delete();
      foreach($tags as $tag){
        if($request->has($tag->tagname)){
          Question_tag::insert(['postid' => $id, 'tagid' => $tag->id]);
        }
      }

      $id=$post->id;

      if($post->posttype == 'question') {
        $request->session()->flash('alert-success', 'Question has been successfully edited!');
        return redirect()->route('posts.postPage',['id'=>$id]);
      }
      $request->session()->flash('alert-success', 'Answer has been successfully edited!');
      return redirect()->route('posts.postPage', ['id'=>$post->parentpost]);
    }

    //Post Search
    public function search(Request $request)
    {
      $request->validate([
        'search' => 'nullable',
        'tag.*' => 'numeric',
        'orderby' => 'required',
      ]);

      $userSearch = FALSE;
      if($request->has('searchType') && $request->input('searchType') == 'user') {
        $userSearch = TRUE;
      }

      $order = $request->input('orderby');
      
      if($request->has('search') && $request->input("search") != null){
        $search_input = $request->input('search');

        $statement1 = 'tsvectors @@ plainto_tsquery(\'english\',?)';
        $posts = Post::whereRaw($statement1, [$search_input]);
        $posts = $posts->get();
      } else {
        $posts = Post::all();
      }

      if($request->has('tag')){
        $tags = $request->input('tag');
        $posts = $posts->filter(function($post) use ($tags){
          return ($post->tags->contains(function ($item) use ($tags){return in_array($item->id, $tags);}));
        });
      }

      if($order == 'newest'){
        $posts = $posts->sortByDesc('postdate');
      } else if ($order == 'oldest'){
        $posts = $posts->sortBy('postdate');
      } else if ($order == 'morevoted'){
        $posts = $posts->sortByDesc(function($post){
          return count(Star::where('postid', $post->id)->get());
        });
      } else if ($order == 'lessvoted'){
        $posts = $posts->sortBy(function($post){
          return count(Star::where('postid', $post->id)->get());
        });
      }

      return view('pages.search', ['posts' => $posts, 'users' => [], 'userSearch' => $userSearch], compact('posts'));
    }

    //Show Answers of Question
    public static function showAnswers($postParent)
    {
      $answers = DB::table('posts')->where('posttype', 'answer')->where('parentpost', $postParent)->get();
      return $answers;
    }

    //Show Top Questions
    public function showTopQuestions()
    {
      $limit = 10;
      $postData = DB::select(
        //DB::raw("select p.*, count(s.userId) nstars from posts p, stars s where p.id = s.postId group by (p.id) order by (nstars) desc limit 5"));
        DB::raw("select p.*, count(s.userId) nstars
          from posts p, stars s
          where p.id = s.postId and p.posttype = 'question'
          group by (p.id)
          order by nstars desc
          limit ".$limit));
      $postModels = Post::hydrate($postData);
      $countleft = $limit - count($postModels);
      if ($countleft) {
        $arrSelected = $postModels->pluck('id')->all();

        $postLeft = Post::where('posttype', '=', 'question')->whereNotIn('id', $arrSelected)->orderBy('id')->limit($countleft)->get();
        $postModels->push($postLeft->all());
      }
      return view('pages.topquestions', ['questionStars'=>$postModels->flatten()]);
    }

    //Mark Answer as Correct
    public function correctness($postid)
    {
      $userid = Auth::id();
      $post = Post::find($postid);
      $this->authorize('markcorrect', $post);

      if($post->iscorrect){
        $post->iscorrect = false;
      } else {
        $post->iscorrect = true;
      }
      $post->save();

      $correctcount = Post::where(['userid' => $post->userid, 'posttype' => 'answer', 'iscorrect' => true])->count();
        if($correctcount == 1){
          $badgeid = Badge::where('badgename', '1 correct answer!')->get()->value('id');
          $badge = User_badge::where(['userid' => $userid, 'badgeid' => $badgeid])->get();
          if($badge->count() == 0) User_badge::insert(['userid' => $userid, 'badgeid' => $badgeid]);
        } else if($correctcount == 5){
          $badgeid = Badge::where('badgename', '5 correct answers!')->get()->value('id');
          $badge = User_badge::where(['userid' => $userid, 'badgeid' => $badgeid])->get();
          if($badge->count() == 0) User_badge::insert(['userid' => $userid, 'badgeid' => $badgeid]);
        } else if($correctcount == 10){
          $badgeid = Badge::where('badgename', '10 correct answers!')->get()->value('id');
          $badge = User_badge::where(['userid' => $userid, 'badgeid' => $badgeid])->get();
          if($badge->count() == 0) User_badge::insert(['userid' => $userid, 'badgeid' => $badgeid]);
        } else if($correctcount == 15){
          $badgeid = Badge::where('badgename', '15 correct answers!')->get()->value('id');
          $badge = User_badge::where(['userid' => $userid, 'badgeid' => $badgeid])->get();
          if($badge->count() == 0) User_badge::insert(['userid' => $userid, 'badgeid' => $badgeid]);
        } else if($correctcount == 20){
          $badgeid = Badge::where('badgename', '20 correct answers!')->get()->value('id');
          $badge = User_badge::where(['userid' => $userid, 'badgeid' => $badgeid])->get();
          if($badge->count() == 0) User_badge::insert(['userid' => $userid, 'badgeid' => $badgeid]);
        }
      return;
    }

    public function report(Request $request, $id){
      $userid = Auth::id();
      $user = Auth::user();
      $reporttype = 'post';
      $post = Post::find($id);
      $exists = $user->reports->contains(function($report) use ($id){return ($report->postid == $id);});
      if($exists){
        if($post->posttype == 'question') {
          $request->session()->flash('alert-warning', 'You have already reported this question!');
          return redirect()->route('posts.postPage', ['id' => $id]);
        }
        $request->session()->flash('alert-warning', 'You have already reported this answer!');
        return redirect()->route('posts.postPage', ['id'=>$post->parentpost]);
      }

      Report::insert(['userid' => $userid, 'reporttype' => $reporttype, 'postid' => $id]);
      
      if($post->posttype == 'question') {
        $request->session()->flash('alert-success', 'Question has been successfully reported. Thank you for your help!');
        return redirect()->route('posts.postPage', ['id' => $id]);
      }
      $request->session()->flash('alert-success', 'Answer has been successfully reported. Thank you for your help!');
      return redirect()->route('posts.postPage', ['id'=>$post->parentpost]);
    }

    //Follow post
    public function follow($id){
      $relationship = User_question::where(['userid' => Auth::id(), 'postid' => $id]);
      $helper = $relationship->value('postid');
      if ($helper == null){
        User_question::insert(['userid' => Auth::id(), 'postid' => $id]);
      }
      else {
        $relationship->delete();
      }
      return redirect()->route('posts.follow', $id);
    }
}
