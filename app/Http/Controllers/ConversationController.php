<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Traits\HttpResponsesTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ConversationController extends Controller
{
    use HttpResponsesTrait;
    public function index()
    {
        $user = Auth::user();
        $conversations = Conversation::where('initiator_id', $user->id)
            ->orWhere('recipient_id', $user->id)
            ->get();

        return $this->success([
            'conversations' => $conversations,
        ]);
    }

    public function get_messages(Conversation $conversation)
    {
        if ($conversation->initiator_id !== Auth::user()->id && $conversation->recipient_id !== Auth::user()->id) {
            return $this->error(null, "Conversation not found", 404);
        }
        $messages = $conversation->messages()->with('sender')->get();
        return $this->success(
            [
                'messages' => $messages,
            ]
        );
    }
}
