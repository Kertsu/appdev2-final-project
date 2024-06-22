<?php

namespace App\Http\Controllers;

use App\Traits\HttpResponsesTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Events\MessageSent;
use App\Events\ReadMessage;
use App\Http\Requests\MessageRequest;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;

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
            'totalRecords' => count($conversations),
        ]);
    }



    public function get_messages(Request $request, Conversation $conversation)
    {
        $user = Auth::user();
        if ($conversation->initiator_id !== $user->id && $conversation->recipient_id !== $user->id) {
            return $this->error(null, "Conversation not found", 404);
        }

        $rows = $request->input('rows', 10);
        $before = $request->input('before');

        $messageQuery = $conversation->messages()
            ->with('sender')
            ->orderBy('created_at', 'desc');

        if ($before) {
            $messageQuery = $messageQuery->where('created_at', '<', $before);
        }

        $messages = $messageQuery->limit($rows)->get();

        $olderMessages = $conversation->messages()->where('created_at', '<', $messages[count($messages) - 1]->created_at)->count();

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
            'olderMessages' => $olderMessages,
        ]);
    }

    public function send_message(Conversation $conversation, MessageRequest $request)
    {
        $validatedData = $request->validated();

        if (!$conversation || ($conversation->initiator_id !== Auth::user()->id && $conversation->recipient_id !== Auth::user()->id)) {
            return $this->error(null, 'Conversation not found', 404);
        }

        $initiatorDeleted = !User::find($conversation->initiator_id);
        $recipientDeleted = !User::find($conversation->recipient_id);
        if ($initiatorDeleted || $recipientDeleted) {
            return $this->error(null, 'The other person is unavailable on Whisper Link', 403);
        }

        $message = Message::create([
            'sender_id' => Auth::user()->id,
            'conversation_id' => $conversation->id,
            'content' => $validatedData['content']
        ]);

        event(new MessageSent($message));

        return $this->success(
            [
                'message' => $message,
                'conversation' => $conversation,
            ],
            'Message sent'
        );
    }


    public function update_message(Conversation $conversation, Message $message, MessageRequest $request)
    {
        if ($message->conversation_id !== $conversation->id) {
            return $this->error(null, 'Message does not exist', 404);
        }

        if ($message->sender_id !== Auth::user()->id) {
            return $this->error(null, 'Invalid action', 401);
        }

        $message->update([
            'content' => $request->input('content')
        ]);

        return $this->success([
            'conversation' => $conversation,
            'message' => $message,
        ], "Message updated successfully");
    }

    public function mark_message_as_read(Conversation $conversation, Message $message)
    {
        $message->update(['read_at' => now()]);

        event(new ReadMessage($message));

        return $this->success(['message' => $message], 'Message marked as read');
    }
}
