<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Friendship;
use App\Models\User;

class FriendshipController extends Controller
{
    public function accept(Request $request, Friendship $friendship)
    {
        // solo el receptor puede aceptar
        if ($friendship->friend_id !== $request->user()->id) {
            return response()->json(['message'=>'No autorizado'], 403);
        }

        $friendship->update(['status'=>'accepted']);

        return $friendship;
    }

    public function myFriends(Request $request)
    {
        $user = $request->user();
        $sent     = $user->sentFriends()->with('profile')->get();
        $received = $user->receivedFriends()->with('profile')->get();
        return $sent->merge($received)->values();
    }

    public function pending(Request $request)
    {
        return \App\Models\Friendship::with('user')
            ->where('friend_id', $request->user()->id)
            ->where('status', 'pending')
            ->get();
    }

    public function sendByUsername(Request $request, $username)
    {
        $user = \App\Models\User::whereHas('profile', function($q) use ($username) {
            $q->where('username', $username);
        })->firstOrFail();

        if ($user->id == $request->user()->id) {
            return response()->json(['message' => 'No puedes agregarte'], 400);
        }

        $friendship = \App\Models\Friendship::firstOrCreate([
            'user_id'   => $request->user()->id,
            'friend_id' => $user->id,
        ], [
            'status' => 'pending'
        ]);

        return $friendship;
    }
}
