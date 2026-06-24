<?php

use App\Enums\Role;
use App\Models\User;
use Spatie\Permission\Models\Role as SpatieRole;

function supervisorAgent(): User
{
    foreach ([Role::AgentMarketeur, Role::MarketeurTerrain] as $role) {
        SpatieRole::findOrCreate($role->value, 'web');
    }

    $agent = User::factory()->create(['role' => Role::AgentMarketeur]);
    $agent->assignRole(Role::AgentMarketeur->value);

    return $agent;
}

it('requires a magasin when creating a terrain agent', function () {
    $agent = supervisorAgent();

    $this->actingAs($agent)
        ->from(route('agents.create'))
        ->post(route('agents.store'), [
            'name' => 'Jean Terrain',
            'email' => 'jean@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])
        ->assertSessionHasErrors('magasin');
});

it('creates an active terrain agent when a magasin is provided', function () {
    $agent = supervisorAgent();

    $this->actingAs($agent)
        ->post(route('agents.store'), [
            'name' => 'Jean Terrain',
            'email' => 'jean@example.com',
            'magasin' => 'Boutique Centre',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])
        ->assertRedirect(route('terrain.team'));

    $created = User::where('email', 'jean@example.com')->first();
    expect($created->magasin)->toBe('Boutique Centre');
    expect($created->is_active)->toBeTrue();
});

it('assigns a magasin and reactivates an unassigned terrain agent', function () {
    $agent = supervisorAgent();
    $terrain = User::factory()->create([
        'role' => Role::MarketeurTerrain,
        'supervisor_id' => $agent->id,
        'magasin' => null,
        'is_active' => false,
    ]);
    $terrain->assignRole(Role::MarketeurTerrain->value);

    $this->actingAs($agent)
        ->patch(route('agents.assign-magasin', $terrain), [
            'magasin' => 'Boutique Nord',
            'activate' => 1,
        ])
        ->assertRedirect();

    $terrain->refresh();
    expect($terrain->magasin)->toBe('Boutique Nord');
    expect($terrain->is_active)->toBeTrue();
});

it('forbids assigning a magasin to a non terrain agent', function () {
    $agent = supervisorAgent();
    $other = User::factory()->create(['role' => Role::AgentMarketeur]);

    $this->actingAs($agent)
        ->patch(route('agents.assign-magasin', $other), ['magasin' => 'X'])
        ->assertForbidden();
});
