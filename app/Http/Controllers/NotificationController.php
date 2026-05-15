<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Friendship;
use App\Models\Like;
use App\Models\Post;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $user    = $request->user();
        $postIds = Post::where('user_id', $user->id)->pluck('id');

        $likes = Like::with(['user.profile', 'post'])
            ->whereIn('post_id', $postIds)
            ->where('user_id', '!=', $user->id)
            ->latest()
            ->limit(50)
            ->get()
            ->map(fn($like) => [
                'type'       => 'like',
                'user'       => $this->formatUser($like->user),
                'post_image' => $like->post->image,
                'post_id'    => $like->post_id,
                'created_at' => $like->created_at,
            ]);

        $comments = Comment::with(['user.profile', 'post'])
            ->whereIn('post_id', $postIds)
            ->where('user_id', '!=', $user->id)
            ->latest()
            ->limit(50)
            ->get()
            ->map(fn($comment) => [
                'type'       => 'comment',
                'user'       => $this->formatUser($comment->user),
                'post_image' => $comment->post->image,
                'post_id'    => $comment->post_id,
                'text'       => $comment->content,
                'created_at' => $comment->created_at,
            ]);

        $friendRequests = Friendship::with('user.profile')
            ->where('friend_id', $user->id)
            ->where('status', 'pending')
            ->latest()
            ->get()
            ->map(fn($fr) => [
                'type'          => 'friend_request',
                'friendship_id' => $fr->id,
                'user'          => $this->formatUser($fr->user),
                'created_at'    => $fr->created_at,
            ]);

        $notifications = $likes
            ->concat($comments)
            ->concat($friendRequests)
            ->sortByDesc('created_at')
            ->values();

        return response()->json($notifications);
    }

    private function formatUser($user): array
    {
        return [
            'id'       => $user->id,
            'name'     => $user->name,
            'username' => $user->profile?->username,
            'avatar'   => $user->profile?->avatar,
        ];
    }
}
