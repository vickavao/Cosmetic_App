<?php

use App\Enums\Role;
use App\Models\Client;
use App\Models\User;
use App\Services\MessagingService;

function matrixUser(Role $role, array $attributes = []): User
{
    return User::factory()->create(array_merge(['role' => $role], $attributes));
}

beforeEach(function () {
    $this->messaging = app(MessagingService::class);
});

it('lets a magasinier chat only with chef marketing', function () {
    $magasinier = matrixUser(Role::Magasinier);
    $chef = matrixUser(Role::ChefMarketing);
    $agent = matrixUser(Role::AgentMarketeur);

    expect($this->messaging->canChatWith($magasinier, $chef))->toBeTrue();
    expect($this->messaging->canChatWith($magasinier, $agent))->toBeFalse();
});

it('lets chef marketing chat with agents, magasiniers, directeur and admin', function () {
    $chef = matrixUser(Role::ChefMarketing);

    foreach ([Role::AgentMarketeur, Role::Magasinier, Role::Directeur, Role::Admin] as $role) {
        expect($this->messaging->canChatWith($chef, matrixUser($role)))->toBeTrue();
    }

    expect($this->messaging->canChatWith($chef, matrixUser(Role::MarketeurTerrain)))->toBeFalse();
});

it('lets directeur and admin chat only with chef marketing', function () {
    $directeur = matrixUser(Role::Directeur);
    $chef = matrixUser(Role::ChefMarketing);
    $agent = matrixUser(Role::AgentMarketeur);

    expect($this->messaging->canChatWith($directeur, $chef))->toBeTrue();
    expect($this->messaging->canChatWith($directeur, $agent))->toBeFalse();
});

it('lets a terrain agent chat only with their supervisor', function () {
    $agent = matrixUser(Role::AgentMarketeur);
    $terrain = matrixUser(Role::MarketeurTerrain, ['supervisor_id' => $agent->id]);
    $otherAgent = matrixUser(Role::AgentMarketeur);

    expect($this->messaging->canChatWith($terrain, $agent))->toBeTrue();
    expect($this->messaging->canChatWith($terrain, $otherAgent))->toBeFalse();
});

it('lets an agent marketeur chat with their terrains, clients and chef', function () {
    $agent = matrixUser(Role::AgentMarketeur);
    $chef = matrixUser(Role::ChefMarketing);
    $terrain = matrixUser(Role::MarketeurTerrain, ['supervisor_id' => $agent->id]);

    $clientAccount = matrixUser(Role::Client);
    Client::factory()->create(['user_id' => $clientAccount->id, 'agent_id' => $agent->id]);

    expect($this->messaging->canChatWith($agent, $terrain))->toBeTrue();
    expect($this->messaging->canChatWith($agent, $chef))->toBeTrue();
    expect($this->messaging->canChatWith($agent, $clientAccount))->toBeTrue();

    $otherClientAccount = matrixUser(Role::Client);
    Client::factory()->create(['user_id' => $otherClientAccount->id, 'agent_id' => matrixUser(Role::AgentMarketeur)->id]);
    expect($this->messaging->canChatWith($agent, $otherClientAccount))->toBeFalse();
});

it('lets a client chat only with their assigned agent', function () {
    $agent = matrixUser(Role::AgentMarketeur);
    $clientAccount = matrixUser(Role::Client);
    Client::factory()->create(['user_id' => $clientAccount->id, 'agent_id' => $agent->id]);

    expect($this->messaging->canChatWith($clientAccount, $agent))->toBeTrue();
    expect($this->messaging->canChatWith($clientAccount, matrixUser(Role::AgentMarketeur)))->toBeFalse();
});

it('excludes inactive users from allowed contacts', function () {
    $chef = matrixUser(Role::ChefMarketing);
    matrixUser(Role::Magasinier, ['is_active' => false]);
    $activeAgent = matrixUser(Role::AgentMarketeur, ['is_active' => true]);

    $contacts = $this->messaging->allowedContacts($chef);

    expect($contacts->pluck('id'))->toContain($activeAgent->id);
    expect($contacts->every(fn ($u) => $u->is_active))->toBeTrue();
});
