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
            $first = $request->input('first');
            $rows = $request->input('rows');

            $query->skip($first)->take($rows);
        }

        $conversations = $query->get();

        return $this->success([
            'conversations' => $conversations,
            'totalRecords' => Conversation::all()->count(),
        ]);
    }



    public function get_messages(Request $request, Conversation $conversation)
    {
        $user = Auth::user();
        if ($conversation->initiator_id !== $user->id && $conversation->recipient_id !== $user->id) {
            return $this->error(null, "Conversation not found", 404);
        }

        $rows = $request->input('rows');
        $before = $request->input('before');

        $messageQuery = $conversation->messages()
            ->with('sender')
            ->orderBy('created_at', 'desc');

        if ($before) {
            $messageQuery = $messageQuery->where('created_at', '<', $before);
        }

        $messages = $messageQuery->limit($rows)->get();

        $totalRecords = $conversation->messages()->count();

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
            'totalRecords' => $totalRecords,
        ]);
    }
}
