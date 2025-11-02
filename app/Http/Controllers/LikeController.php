<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Like;
use App\Models\Post;

class LikeController extends Controller
{
    public function like(Post $post, Request $request)
    {
        $like = Like::firstOrCreate([
            'user_id' => $request->user()->id,
            'post_id' => $post->id,
        ]);

        return response()->json(['liked'=>true]);
    }

    public function unlike(Post $post, Request $request)
    {
        Like::where('user_id', $request->user()->id)
            ->where('post_id', $post->id)
            ->delete();

        return response()->json(['liked'=>false]);
    }
}
