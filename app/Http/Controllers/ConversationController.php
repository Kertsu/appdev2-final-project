<?php

namespace App\Http\Controllers;

use App\Events\ReadMessage;
use App\Models\Conversation;
use App\Models\Message;
use App\Traits\HttpResponsesTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ConversationController extends Controller
{
    use HttpResponsesTrait;
    public function index(Request $request)
    {
        $user = Auth::user();

        $query = Conversation::where('initiator_id', $user->id)
            ->orWhere('recipient_id', $user->id)
            ->with(['latestMessage', 'recipient'])
            ->orderByDesc(
                Message::select('created_at')
                    ->whereColumn('conversation_id', 'conversations.id')
                    ->latest()
                    ->limit(1)
            );
            

        if ($request->has('first') && $request->has('page') && $request->has('rows')) {
            $page = $request->input('page');
            $rows = $request->input('rows');

            $offset = ($page - 1) * $rows;

            $query->skip($offset)->take($rows);
        }

        $conversations = $query->get();

        return $this->success([
            'conversations' => $conversations,
            'totalRecords' => $conversations->count(), 
        ]);
    }



    public function get_messages(Conversation $conversation)
    {
        $user = Auth::user();
        if ($conversation->initiator_id !== $user->id && $conversation->recipient_id !== $user->id) {
            return $this->error(null, "Conversation not found", 404);
        }

        $messages = $conversation->messages()->with('sender')->get();

        $latestMessage = $conversation->messages()
            ->where(function ($query) use ($user) {
                $query->whereNull('sender_id')
                    ->orWhere('sender_id', '!=', $user->id);
            })
            ->orderByDesc('created_at')
            ->whereNull('read_at')
            ->first();


        if ($latestMessage) {
            $unreadMessages = $conversation->messages()
                ->where('sender_id', $latestMessage->sender_id)
                ->where('created_at', '<=', $latestMessage->created_at)
                ->whereNull('read_at')->get();

            $unreadMessages->each(function ($message) {
                $message->update(['read_at' => now()]);
            });
        }

        if ($latestMessage && isset($unreadMessages)) {
            event(new ReadMessage($latestMessage));
        }

        return $this->success([
            'messages' => $messages,
        ]);
    }
}
