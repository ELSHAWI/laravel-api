<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function getConversations(Request $request)
    {
        $request->validate(['admin_id' => 'required|integer']);

        // Get unique student IDs who have messaged this admin
        $studentIds = Message::where('receiver_id', $request->admin_id)
            ->distinct()
            ->pluck('sender_id');

        // Get unread counts for each student
        $unreadCounts = [];
        foreach ($studentIds as $studentId) {
            $unreadCounts[$studentId] = Message::where('sender_id', $studentId)
                ->where('receiver_id', $request->admin_id)
                // ->whereNull('read_at')
                ->count();
        }

        // Get student details (in a real app, you'd have a students table)
        $students = array_map(function($id) {
            return [
                'id' => $id,
                'name' => "Student $id" // Replace with actual name from your DB
            ];
        }, $studentIds->toArray());

        return response()->json([
            'students' => $students,
            'unread_counts' => $unreadCounts
        ]);
    }
}