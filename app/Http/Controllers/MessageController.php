<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMessageRequest;
use App\Models\Message;
use App\Models\User;
use App\Services\MessagingService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    public function __construct(private MessagingService $messaging) {}

    /**
     * Show the messaging interface with the contacts allowed by the messaging
     * matrix and, optionally, the conversation with a selected contact.
     */
    public function index(Request $request, ?User $user = null): View
    {
        $auth = $request->user();
        $contacts = $this->messaging->allowedContacts($auth);

        $conversation = collect();

        if ($user !== null && $auth->can('chat-with', $user)) {
            $conversation = Message::query()
                ->between($auth->id, $user->id)
                ->with(['sender', 'receiver'])
                ->orderBy('created_at')
                ->get();

            Message::query()
                ->where('sender_id', $user->id)
                ->where('receiver_id', $auth->id)
                ->where('lu', false)
                ->update(['lu' => true]);
        }

        return view('messages.index', [
            'contacts' => $contacts,
            'conversation' => $conversation,
            'activeContact' => $user,
        ]);
    }

    public function store(StoreMessageRequest $request): RedirectResponse
    {
        $message = Message::create([
            'sender_id' => $request->user()->id,
            'receiver_id' => $request->integer('receiver_id'),
            'content' => $request->string('content'),
            'lu' => false,
        ]);

        return back()->with('status', 'Message envoyé.')->with('message_id', $message->id);
    }
}
