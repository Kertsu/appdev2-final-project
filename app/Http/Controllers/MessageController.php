<?php

namespace App\Http\Controllers;

use App\Events\MessageSent;
use App\Events\ReadMessage;
use App\Http\Requests\MessageRequest;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Traits\HttpResponsesTrait;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class MessageController extends Controller
{
    use HttpResponsesTrait;
    public function send_message(Conversation $conversation, MessageRequest $request)
    {
        $validatedData = $request->validated();

        if (!$conversation || ($conversation->initiator_id !== Auth::user()->id && $conversation->recipient_id !== Auth::user()->id)) {
            return $this->error(null, 'Conversation not found', 404);
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
    // public function send_message(MessageRequest $request, Conversation $conversation)
    // {
    //     $user = Auth::user();
    //     $message = $conversation->messages()->create([
    //         'sender_id' => $user->id,
    //         'content' => $request->input('content'),
    //     ]);

    //     $recipient = $conversation->initiator_id == $user->id ? $conversation->recipient_id : $conversation->initiator_id;

    //     $recipientUser = User::find($recipient);
    //     if ($recipientUser && $recipientUser->isViewingConversation($conversation->id)) {
    //         $message->update(['read_at' => now()]);
    //         event(new MessageSent($message));
    //     }

    //     event(new MessageSent($message));

    //     return $this->success(['message' => $message]);
    // }


    public function initiate_conversation(string $username, MessageRequest $request)
    {
        $validatedData = $request->validated();

        $recipient = User::where('username', $username)->first();

        if (!$recipient) {
            return $this->error(null, 'Recipient not found', 404);
        }

        $existingConversation = Conversation::where([
            ['initiator_id', '=', Auth::user()->id],
            ['recipient_id', '=', $recipient->id]
        ])->first();

        if ($existingConversation) {
            return $this->success([
                'conversation_id' => $existingConversation->id
            ], 'Conversation already exists');
        }

        if (Auth::user()->id == $recipient->id) {
            return $this->error(null, 'You cannot initiate a conversation with yourself', 400);
        }

        $conversation = Conversation::create([
            'initiator_id' => Auth::user()->id,
            'recipient_id' => $recipient->id,
            'initiator_username' => $this->generate_initiator_username()
        ]);

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

    private function generate_initiator_username()
    {
        do {
            $randomUsername = 'Whisp_' . Str::random(8);
        } while (Conversation::where('initiator_username', $randomUsername)->exists());

        return $randomUsername;
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
