<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    public function conversations(Request $request)
    {
        $userId = $request->user()->id;

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
    }

    public function index(Request $request, User $user)
    {
        $myId = $request->user()->id;

        Message::where('sender_id', $user->id)
            ->where('receiver_id', $myId)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return Message::where(function ($q) use ($myId, $user) {
                $q->where('sender_id', $myId)->where('receiver_id', $user->id);
            })
            ->orWhere(function ($q) use ($myId, $user) {
                $q->where('sender_id', $user->id)->where('receiver_id', $myId);
            })
            ->with('sender.profile')
            ->latest()
            ->paginate(30);
    }

    public function store(Request $request, User $user)
    {
        try {
            $data = $request->validate(['body' => 'required|string|max:2000']);

            $message = Message::create([
                'sender_id'   => $request->user()->id,
                'receiver_id' => $user->id,
                'body'        => $data['body'],
            ]);

            return $message->load('sender.profile');
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Message store error: ' . $e->getMessage());
            return response()->json([
                'error'   => $e->getMessage(),
                'type'    => get_class($e),
                'hint'    => 'Run: php artisan migrate',
            ], 500);
        }
    }
}
