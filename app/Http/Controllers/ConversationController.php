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
}
