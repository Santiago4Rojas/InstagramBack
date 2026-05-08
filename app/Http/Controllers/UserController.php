<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;

class UserController extends Controller
{
    public function search(Request $request)
    {
        $username = $request->query('username');

        $users = User::where('username', 'like', '%' . $username . '%')
            ->where('id', '!=', $request->user()->id)
            ->select('id', 'name', 'username')
            ->limit(10)
            ->get();

        return response()->json($users);
    }
}
