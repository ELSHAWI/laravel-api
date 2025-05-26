<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Chat;
use App\Models\User;
use Illuminate\Support\Facades\Validator;

class AdminChatController extends Controller
{
    // Get filtered messages
    public function getMessages(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'college' => 'nullable|string',
            'grade' => 'nullable|integer',
            'term' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $messages = Chat::with(['user' => function($query) {
                $query->select('id', 'name', 'profile_image', 'college', 'grade', 'term');
            }])
            ->when($request->college, function($query, $college) {
                return $query->whereHas('user', function($q) use ($college) {
                    $q->where('college', $college);
                });
            })
            ->when($request->grade, function($query, $grade) {
                return $query->whereHas('user', function($q) use ($grade) {
                    $q->where('grade', $grade);
                });
            })
            ->when($request->term, function($query, $term) {
                return $query->whereHas('user', function($q) use ($term) {
                    $q->where('term', $term);
                });
            })
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json(['messages' => $messages]);
    }

    // Admin sends a message
    public function sendMessage(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'college' => 'required|string',
            'grade' => 'required|integer',
            'term' => 'required|string',
            'message' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // In a real app, you'd get the admin user from auth
        $adminUser = User::where('role', 'admin')->first();

        $message = Chat::create([
            'user_id' => $adminUser->id,
            'message' => $request->message,
            'sender_type' => 'admin'
        ]);

        return response()->json(['message' => $message->load('user')], 201);
    }

    // Delete a message
    public function deleteMessage($id)
    {
        $message = Chat::findOrFail($id);
        $message->delete();

        return response()->json(['message' => 'Message deleted successfully']);
    }
}