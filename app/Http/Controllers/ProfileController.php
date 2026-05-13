<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Profile;
use App\Models\Post;
use App\Models\Story;
use App\Models\Friendship;

class ProfileController extends Controller
{
    public function show(Request $request, $username)
    {
        $authUser = $request->user();

        $profile = Profile::with('user')->where('username', $username)->firstOrFail();
        $user    = $profile->user;

        // Estado de amistad
        $friendship = Friendship::where(function ($q) use ($authUser, $user) {
            $q->where('user_id', $authUser->id)->where('friend_id', $user->id);
        })->orWhere(function ($q) use ($authUser, $user) {
            $q->where('user_id', $user->id)->where('friend_id', $authUser->id);
        })->first();

        $isOwn     = $authUser->id === $user->id;
        $isFriend  = $friendship && $friendship->status === 'accepted';
        $canSee    = $isOwn || $isFriend;

        if ($isOwn) {
            $friendshipStatus = 'own';
        } elseif (!$friendship) {
            $friendshipStatus = 'none';
        } elseif ($friendship->status === 'accepted') {
            $friendshipStatus = 'friends';
        } elseif ($friendship->user_id === $authUser->id) {
            $friendshipStatus = 'pending_sent';
        } else {
            $friendshipStatus = 'pending_received';
        }

        $posts = $canSee
            ? Post::where('user_id', $user->id)
                ->with(['likes', 'comments'])
                ->latest()
                ->get()
            : [];

        $friendsCount = $user->sentFriends()->count() + $user->receivedFriends()->count();

        return response()->json([
            'profile'           => $profile,
            'posts'             => $posts,
            'posts_count'       => Post::where('user_id', $user->id)->count(),
            'friends_count'     => $friendsCount,
            'stories_count'     => Story::where('user_id', $user->id)->active()->count(),
            'friendship_status' => $friendshipStatus,
            'friendship_id'     => $friendship?->id,
            'can_see_content'   => $canSee,
        ]);
    }

    public function update(Request $request)
    {
        $user    = $request->user();
        $profile = $user->profile;

        $data = $request->validate([
            'bio'     => 'nullable|string|max:150',
            'website' => 'nullable|url|max:100',
        ]);

        $profile->update($data);

        return response()->json($profile);
    }

    public function uploadAvatar(Request $request)
    {
        $request->validate([
            'avatar' => 'required|image|max:5120',
        ]);

        $user    = $request->user();
        $profile = $user->profile;

        // Borrar avatar anterior si existe
        if ($profile->avatar) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($profile->avatar);
        }

        $path = $request->file('avatar')->store('avatars', 'public');
        $profile->update(['avatar' => $path]);

        return response()->json($profile);
    }
}
