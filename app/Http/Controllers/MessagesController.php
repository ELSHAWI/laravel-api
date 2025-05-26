<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Events\NewMessage;
use App\Models\Message;
class MessagesController extends Controller
{
    
     public function send(Request $request)
    {
        $request->validate([
            'sender_id' => 'required|integer',
            'receiver_id' => 'required|integer',
            'message' => 'required|string',
        ]);

        $message = Message::create([
            'sender_id' => $request->sender_id,
            'receiver_id' => $request->receiver_id,
            'message' => $request->message,
        ]);

        broadcast(new NewMessage($message))->toOthers();

        return response()->json($message);
    }

    public function fetch(Request $request)
    {
        $request->validate([
            'sender_id' => 'required|integer',
            'receiver_id' => 'required|integer',
        ]);

        $messages = Message::where(function($query) use ($request) {
            $query->where('sender_id', $request->sender_id)
                  ->where('receiver_id', $request->receiver_id);
        })->orWhere(function($query) use ($request) {
            $query->where('sender_id', $request->receiver_id)
                  ->where('receiver_id', $request->sender_id);
        })->orderBy('created_at', 'asc')->get();

        return response()->json($messages);
    }
}
