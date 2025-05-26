<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Chat;
use Illuminate\Support\Facades\Validator;
use App\Events\MessageSent;

class ChatController extends Controller
{
    // public function send(Request $request)
    // {
    //     $validator = Validator::make($request->all(), [
    //         'user_id' => 'required|exists:users,id',
    //         'message' => 'nullable|string',
    //         'attachment' => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:5120' // 5MB max
    //     ]);

    //     if ($validator->fails()) {
    //         return response()->json(['errors' => $validator->errors()], 422);
    //     }

    //     $attachmentPath = null;

    //     if ($request->hasFile('attachment')) {
    //         $attachmentPath = $request->file('attachment')->store('attachments', 'public');
    //     }

    //     $message = Chat::create([
    //         'user_id' => $request->user_id,
    //         'message' => $request->message,
    //         'attachment' => $attachmentPath
    //     ]);

    //     return response()->json(['message' => $message->load('user')], 201);
    // }
    public function send(Request $request)
    {
        $request->validate([
            'message' => 'required_without:attachment',
            'attachment' => 'nullable|string',
            'user_id' => 'required|integer|exists:users,id',
            'college' => 'required|string',
            'grade' => 'required|integer',
            'term' => 'required|string',
        ]);

        $message = Chat::create([
            'user_id' => $request->user_id,
            'message' => $request->message,
            'attachment' => $request->attachment,
        ]);

        // Load the user relationship
        $message->load('user');

        // Broadcast the message
        broadcast(new MessageSent($message))->toOthers();

        return response()->json([
            'message' => 'Message sent successfully'
        ]);
    }

    public function upload(Request $request)
    {
        $request->validate([
            'attachment' => 'required|file|max:5120', // 5MB max
        ]);

        $path = $request->file('attachment')->store('public/attachments');
        $url = str_replace('public/', '', $path);

        return response()->json([
            'url' => $url
        ]);
    }
    public function edit(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'message' => 'nullable|string',
            'attachment' => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:5120'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $message = Chat::findOrFail($id);

        if ($request->has('message')) {
            $message->message = $request->message;
        }

        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store('attachments', 'public');
            $message->attachment = $attachmentPath;
        }

        $message->save();

        return response()->json(['message' => $message->load('user')], 200);
    }

    public function delete($id)
    {
        $message = Chat::findOrFail($id);
        $message->delete();

        return response()->json(['message' => 'Deleted successfully'], 200);
    }

    public function all()
    {
        $messages = Chat::with('user')->orderBy('created_at', 'asc')->get();

        return response()->json(['messages' => $messages]);
    }
    public function getFilteredMessages(Request $request)
    {
        $college = $request->input('college');
        $grade = $request->input('grade');
        $term = $request->input('term');

        $messages = Chat::with('user')
            ->whereHas('user', function($query) use ($college, $grade, $term) {
                $query->where('college', $college)
                    ->where('grade', $grade)
                    ->where('term', $term);
            })
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json(['messages' => $messages]);
    }
}
