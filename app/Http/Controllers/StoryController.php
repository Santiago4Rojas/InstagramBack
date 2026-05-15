<?php

namespace App\Http\Controllers;

use App\Models\Story;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class StoryController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $friendIds = $user->sentFriends()->pluck('users.id')
            ->merge($user->receivedFriends()->pluck('users.id'));

        $allowedIds = $friendIds->push($user->id)->unique()->values();

        $stories = Story::with('user.profile')
            ->active()
            ->whereIn('user_id', $allowedIds)
            ->latest()
            ->get();

        $grouped = $stories->groupBy('user_id')->map(function ($items) {
            $first = $items->first();
            return [
                'user'    => $first->user,
                'stories' => $items->values(),
            ];
        })->values();

        return response()->json($grouped);
    }

    public function store(Request $request)
    {
        $request->validate([
            'media'   => 'required|mimes:jpg,jpeg,png,gif,webp,mp4,mov,avi,webm|max:51200',
            'caption' => 'nullable|string|max:200',
        ]);

        $file      = $request->file('media');
        $mime      = $file->getMimeType();
        $mediaType = str_starts_with($mime, 'video/') ? 'video' : 'image';

        $path      = $file->store('stories', 'public');
        $imageData = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($file->getRealPath()));

        $story = Story::create([
            'user_id'    => $request->user()->id,
            'image'      => $path,
            'image_data' => $imageData,
            'media_type' => $mediaType,
            'caption'    => $request->input('caption'),
            'expires_at' => now()->addHours(24),
        ]);

        return response()->json($story->load('user.profile'), 201);
    }

    public function destroy(Story $story, Request $request)
    {
        if ($story->user_id !== $request->user()->id) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        Storage::disk('public')->delete($story->image);
        $story->delete();

        return response()->json(['message' => 'Historia eliminada']);
    }
}
