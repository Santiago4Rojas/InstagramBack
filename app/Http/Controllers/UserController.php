<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;

class UserController extends Controller
{
    public function show(User $user)
    {
        $user->load('profile');
        return response()->json([
            'id'          => $user->id,
            'name'        => $user->name,
            'username'    => $user->profile?->username,
            'avatar'      => $user->profile?->avatar,
            'avatar_data' => $user->profile?->avatar_data,
        ]);
    }

    public function search(Request $request)
    {
        $username = $request->query('username', '');

        if (strlen($username) < 1) {
            return response()->json([]);
        }

        $users = User::with('profile')
            ->whereHas('profile', function ($q) use ($username) {
                $q->where('username', 'like', '%' . $username . '%');
            })
            ->where('id', '!=', $request->user()->id)
            ->limit(10)
            ->get()
            ->map(function ($user) {
                return [
                    'id'          => $user->id,
                    'name'        => $user->name,
                    'username'    => $user->profile?->username ?? '',
                    'avatar'      => $user->profile?->avatar,
                    'avatar_data' => $user->profile?->avatar_data,
                ];
            });

        return response()->json($users);
    }
}
