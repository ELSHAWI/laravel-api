<?php 

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class LegalChatController extends Controller
{
    public function handle(Request $request)
    {
        $messages = $request->input('messages');

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . env('OPENROUTER_API_KEY'),
            'Content-Type' => 'application/json',
        ])->post('https://openrouter.ai/api/v1/chat/completions', [
            'model' => 'mistralai/mistral-7b-instruct',
            'messages' => $messages,
        ]);

        if ($response->successful()) {
            $reply = $response->json()['choices'][0]['message']['content'];
            return response()->json(['reply' => $reply]);
        } else {
            return response()->json(['error' => 'Failed to fetch response'], 500);
        }
    }
}
