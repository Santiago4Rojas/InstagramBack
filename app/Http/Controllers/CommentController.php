<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Comment;
use App\Models\Post;

class CommentController extends Controller
{
    public function index(Post $post)
    {
        return $post->comments()->with('user.profile')->latest()->get();
    }
    
    public function store(Request $request, Post $post)
    {
        $data = $request->validate([
            'content' => 'required|string'
        ]);

        $comment = Comment::create([
            'user_id' => $request->user()->id,
            'post_id' => $post->id,
            'content' => $data['content'],
        ]);

        return $comment->load('user.profile');
    }
}
