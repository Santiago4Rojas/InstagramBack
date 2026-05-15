<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PostController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        // IDs de amigos aceptados (enviados y recibidos)
        $friendIds = $user->sentFriends()->pluck('users.id')
            ->merge($user->receivedFriends()->pluck('users.id'));

        // Posts propios + posts de amigos
        $allowedIds = $friendIds->push($user->id)->unique()->values();

        return Post::with(['user.profile', 'likes', 'comments.user.profile'])
            ->whereIn('user_id', $allowedIds)
            ->latest()
            ->paginate(20);
    }

    public function explore(Request $request)
    {
        return Post::with(['user.profile', 'likes', 'comments'])
            ->latest()
            ->paginate(20);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'image' => 'required|image|max:2048',
            'caption' => 'nullable|string'
        ]);

        $path = $request->file('image')->store('posts', 'public');

        $post = Post::create([
            'user_id' => $request->user()->id,
            'image' => $path,
            'caption' => $data['caption'] ?? null,
        ]);

        return $post->load('user.profile');
    }

    public function show(Post $post)
    {
        return $post->load(['user.profile','comments.user.profile','likes']);
    }

    public function destroy(Post $post, Request $request)
    {
        if ($post->user_id !== $request->user()->id) {
            return response()->json(['message'=>'No autorizado'], 403);
        }

        Storage::disk('public')->delete($post->image);
        $post->delete();
        return response()->json(['message'=>'Eliminado']);
    }
}
