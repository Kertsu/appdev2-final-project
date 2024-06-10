<?php

namespace App\Http\Controllers;

use App\Events\NewMessage;
use App\Http\Requests\MessageRequest;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Traits\HttpResponsesTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    use HttpResponsesTrait;
    public function show(string $username)
    {
        $user = User::where('username', $username)->first();
        if (!$user) {
            return $this->error(null, 'User not found', 400);
        }
        return $this->success($user);
    }

    public function validate_username(string $username)
    {
        $user = User::where('username', $username)->first();
        if (!$user) {
            return $this->error(null, "Sorry, we couldn't find the user you were looking for.", 404);
        }
        return $this->success(["user" => $user]);
    }

    public function destroy()
    {
        $user = User::where('id', Auth::user()->id)->first();
        $user->delete();
        return $this->success(null, 'Account deleted successfully');
    }


    public function get_self()
    {
        $user = Auth::user();

        if (!$user->email_verified_at) {
            return $this->success([
                "user" => $user, "otpRequired" => true
            ]);
        }

        return $this->success([
            "user" => $user, "otpRequired" => false
        ]);
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
            return $this->error(null, 'Conversation already exist', 400);
        }

        if (Auth::user()->id == $recipient->id) {
            return $this->error(null, 'Seriously? You cannot initiate a conversation with yourself here', 400);
        }

        $new_conversation = Conversation::create([
            'initiator_id' => Auth::user()->id,
            'recipient_id' => $recipient->id,
            'initiator_username' => $this->generate_initiator_username()
        ]);

        $message = Message::create([
            'sender_id' => Auth::user()->id,
            'conversation_id' => $new_conversation->id,
            'content' => $validatedData['content']
        ]);

        $conversation = Conversation::where('id', $new_conversation->id)->with(['latestMessage', 'recipient'])->first();

        event(new NewMessage($conversation));


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
}
