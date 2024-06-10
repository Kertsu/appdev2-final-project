<?php

namespace App\Http\Controllers;

use App\Events\MessageSent;
use App\Events\NewMessage;
use App\Events\ReadMessage;
use App\Http\Requests\MessageRequest;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Traits\HttpResponsesTrait;
use Illuminate\Support\Facades\Auth;

class MessageController extends Controller
{
    use HttpResponsesTrait;
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
