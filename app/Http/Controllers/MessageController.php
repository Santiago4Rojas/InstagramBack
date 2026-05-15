<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MessageController extends Controller
{
    public function conversations(Request $request)
    {
        $userId = $request->user()->id;

        try {
            $messages = Message::where('sender_id', $userId)
                ->orWhere('receiver_id', $userId)
                ->with(['sender.profile', 'receiver.profile'])
                ->latest()
                ->get();

            $conversations = $messages
                ->groupBy(function ($msg) use ($userId) {
                    return $msg->sender_id == $userId ? $msg->receiver_id : $msg->sender_id;
                })
                ->map(function ($msgs, $partnerId) use ($userId) {
                    $last    = $msgs->first();
                    $partner = $last->sender_id == $userId ? $last->receiver : $last->sender;

                    return [
                        'partner' => [
                            'id'       => $partner->id,
                            'name'     => $partner->name,
                            'username' => $partner->profile?->username,
                            'avatar'   => $partner->profile?->avatar,
                        ],
                        'last_message' => [
                            'body'       => $last->body,
                            'created_at' => $last->created_at,
                            'is_mine'    => $last->sender_id == $userId,
                        ],
                        'unread_count' => $msgs
                            ->where('receiver_id', $userId)
                            ->whereNull('read_at')
                            ->count(),
                    ];
                })
                ->values();

            return response()->json($conversations);
        } catch (\Exception $e) {
            return response()->json([]);
        }
    }

    public function index(Request $request, User $user)
    {
        $myId = $request->user()->id;

        try {
            DB::table('messages')
                ->where('sender_id', $user->id)
                ->where('receiver_id', $myId)
                ->whereNull('read_at')
                ->update(['read_at' => now()]);

            $rows = DB::table('messages')
                ->where(function ($q) use ($myId, $user) {
                    $q->where('sender_id', $myId)->where('receiver_id', $user->id);
                })
                ->orWhere(function ($q) use ($myId, $user) {
                    $q->where('sender_id', $user->id)->where('receiver_id', $myId);
                })
                ->orderBy('created_at', 'asc')
                ->get();

            return response()->json(['data' => $rows]);
        } catch (\Exception $e) {
            return response()->json(['data' => [], 'error' => $e->getMessage()]);
        }
    }

    public function store(Request $request, User $user)
    {
        try {
            $data = $request->validate(['body' => 'required|string|max:2000']);

            $id = DB::table('messages')->insertGetId([
                'sender_id'   => $request->user()->id,
                'receiver_id' => $user->id,
                'body'        => $data['body'],
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);

            $row = DB::table('messages')->find($id);

            return response()->json($row, 201);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'type'  => get_class($e),
                'hint'  => 'Run: git pull && composer dump-autoload && php artisan migrate',
            ], 500);
        }
    }
}
