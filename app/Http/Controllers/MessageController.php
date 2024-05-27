<?php

namespace App\Http\Controllers;

use App\Events\MessageSent;
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

    public function get_messages(Conversation $conversation){
        
    }
}
