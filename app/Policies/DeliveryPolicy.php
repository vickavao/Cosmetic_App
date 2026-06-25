<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\Delivery;
use App\Models\User;

class DeliveryPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, [
            Role::Admin,
            Role::Directeur,
            Role::ChefMarketing,
            Role::AgentMarketeur,
            Role::Magasinier,
        ], true);
    }

    public function view(User $user, Delivery $delivery): bool
    {
        if ($user->role === Role::AgentMarketeur) {
            return $delivery->agent_id === $user->id || $delivery->order?->user_id === $user->id;
        }

        return $this->viewAny($user);
    }

    /**
     * Create a delivery from a prepared order (prête à livraison).
     * Per CDC V2, the Agent Marketeur delivers at Step 4.
     */
    public function create(User $user): bool
    {
        return in_array($user->role, [
            Role::Admin,
            Role::ChefMarketing,
            Role::AgentMarketeur,
        ], true);
    }

    /**
     * Confirm a delivery (issues stock + goods issue note).
     */
    public function confirm(User $user): bool
    {
        return in_array($user->role, [
            Role::Admin,
            Role::ChefMarketing,
            Role::Magasinier,
        ], true);
    }
}
