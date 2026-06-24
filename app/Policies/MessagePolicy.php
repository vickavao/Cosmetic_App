<?php

namespace App\Policies;

use App\Models\Message;
use App\Models\User;
use App\Services\MessagingService;

class MessagePolicy
{
    public function __construct(private MessagingService $messaging) {}

    /**
     * Enforce the messaging matrix defined in MessagingService.
     */
    public function canChatWith(User $auth, User $receiver): bool
    {
        return $this->messaging->canChatWith($auth, $receiver);
    }

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Message $message): bool
    {
        return $user->id === $message->sender_id || $user->id === $message->receiver_id;
    }

    public function delete(User $user, Message $message): bool
    {
        return $user->id === $message->sender_id;
    }
}
