<?php

namespace App\Services;

use App\Enums\Role;
use App\Models\Client;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Centralises the messaging matrix (who can chat with whom):
 *  - Client            <-> their Agent Marketeur
 *  - Directeur / Admin <-> Chef Marketing
 *  - Chef Marketing    <-> Agent Marketeur, Magasinier, Directeur, Admin
 *  - Magasinier        <-> Chef Marketing
 *  - Agent Marketeur   <-> their Marketeurs Terrain, their Clients, Chef Marketing
 *  - Marketeur Terrain <-> their Agent Marketeur (supervisor)
 */
class MessagingService
{
    /**
     * Active users the given user is allowed to chat with, ordered by name.
     *
     * @return Collection<int, User>
     */
    public function allowedContacts(User $user): Collection
    {
        $ids = $this->allowedContactIds($user);

        if ($ids->isEmpty()) {
            return collect();
        }

        return User::query()
            ->whereIn('id', $ids->all())
            ->where('id', '!=', $user->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    /**
     * Whether $auth is allowed to exchange messages with $receiver.
     */
    public function canChatWith(User $auth, User $receiver): bool
    {
        if ($auth->id === $receiver->id) {
            return false;
        }

        return $this->allowedContactIds($auth)->contains($receiver->id);
    }

    /**
     * The set of user ids the given user may chat with.
     *
     * @return Collection<int, int>
     */
    private function allowedContactIds(User $user): Collection
    {
        return match ($user->role) {
            Role::Client => collect([$user->clientProfile?->agent_id])->filter()->values(),
            Role::MarketeurTerrain => collect([$user->supervisor_id])->filter()->values(),
            Role::AgentMarketeur => $this->agentContactIds($user),
            Role::Magasinier => $this->idsForRoles([Role::ChefMarketing]),
            Role::ChefMarketing => $this->idsForRoles([
                Role::AgentMarketeur,
                Role::Magasinier,
                Role::Directeur,
                Role::Admin,
            ]),
            Role::Directeur, Role::Admin => $this->idsForRoles([Role::ChefMarketing]),
            default => collect(),
        };
    }

    /**
     * Agent Marketeur contacts: supervised terrains, managed clients, chef marketing.
     *
     * @return Collection<int, int>
     */
    private function agentContactIds(User $user): Collection
    {
        $terrainIds = User::query()
            ->where('supervisor_id', $user->id)
            ->pluck('id');

        $chefIds = $this->idsForRoles([Role::ChefMarketing]);

        $clientAccountIds = Client::query()
            ->where('agent_id', $user->id)
            ->whereNotNull('user_id')
            ->pluck('user_id');

        return $terrainIds
            ->merge($chefIds)
            ->merge($clientAccountIds)
            ->unique()
            ->values();
    }

    /**
     * Ids of all users holding any of the given roles.
     *
     * @param  array<int, Role>  $roles
     * @return Collection<int, int>
     */
    private function idsForRoles(array $roles): Collection
    {
        return User::query()
            ->whereIn('role', array_map(static fn (Role $r): string => $r->value, $roles))
            ->pluck('id');
    }
}
