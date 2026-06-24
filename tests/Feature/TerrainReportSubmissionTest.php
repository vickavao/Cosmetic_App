<?php

use App\Enums\Role;
use App\Models\User;
use App\Notifications\RapportTerrainSoumis;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role as SpatieRole;

function roleUser(Role $role, array $attrs = []): User
{
    SpatieRole::findOrCreate($role->value, 'web');
    $user = User::factory()->create([...$attrs, 'role' => $role]);
    $user->assignRole($role->value);

    return $user;
}

it('submits the agent report to their chef marketing supervisor', function () {
    Notification::fake();

    $chef = roleUser(Role::ChefMarketing);
    $agent = roleUser(Role::AgentMarketeur, ['supervisor_id' => $chef->id]);

    $this->actingAs($agent)
        ->post(route('reports.terrain.submit'), ['period' => 'mois'])
        ->assertRedirect();

    Notification::assertSentTo($chef, RapportTerrainSoumis::class);
});

it('makes the chef report available to directeur and admin, not to self', function () {
    Notification::fake();

    $chef = roleUser(Role::ChefMarketing);
    $directeur = roleUser(Role::Directeur, ['is_active' => true]);
    $admin = roleUser(Role::Admin, ['is_active' => true]);

    $this->actingAs($chef)
        ->post(route('reports.terrain.submit'), ['period' => 'mois'])
        ->assertRedirect();

    Notification::assertSentTo($directeur, RapportTerrainSoumis::class);
    Notification::assertSentTo($admin, RapportTerrainSoumis::class);
    Notification::assertNotSentTo($chef, RapportTerrainSoumis::class);
});
