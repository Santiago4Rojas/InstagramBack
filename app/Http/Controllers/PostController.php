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

        $friendIds = $user->sentFriends()->pluck('users.id')
            ->merge($user->receivedFriends()->pluck('users.id'));

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
            'image'   => 'required|image|max:10240',
            'caption' => 'nullable|string'
        ]);

        $file      = $request->file('image');
        $path      = $file->store('posts', 'public');
        $imageData = $this->encodeBase64($file);

        $post = Post::create([
            'user_id'    => $request->user()->id,
            'image'      => $path,
            'image_data' => $imageData,
            'caption'    => $data['caption'] ?? null,
        ]);

        return $post->load('user.profile');
    }

    public function show(Post $post)
    {
        return $post->load(['user.profile', 'comments.user.profile', 'likes']);
    }

    public function update(Request $request, Post $post)
    {
        if ($post->user_id !== $request->user()->id) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        $data = $request->validate([
            'image'   => 'nullable|image|max:10240',
            'caption' => 'nullable|string',
        ]);

        if ($request->hasFile('image')) {
            if ($post->image) {
                Storage::disk('public')->delete($post->image);
            }
            $file             = $request->file('image');
            $post->image      = $file->store('posts', 'public');
            $post->image_data = $this->encodeBase64($file);
        }

        if ($request->has('caption')) {
            $post->caption = $data['caption'];
        }

        $post->save();
        return $post->load('user.profile');
    }

    public function destroy(Post $post, Request $request)
    {
        if ($post->user_id !== $request->user()->id) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        Storage::disk('public')->delete($post->image);
        $post->delete();
        return response()->json(['message' => 'Eliminado']);
    }

    private function encodeBase64($file): string
    {
        $mime = $file->getMimeType();
        $data = base64_encode(file_get_contents($file->getRealPath()));
        return 'data:' . $mime . ';base64,' . $data;
    }
}
