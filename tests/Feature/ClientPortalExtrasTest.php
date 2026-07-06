<?php

use App\Enums\AnnouncementType;
use App\Enums\Role;
use App\Models\Announcement;
use App\Models\Client;
use App\Models\User;
use Spatie\Permission\Models\Role as SpatieRole;

use function Pest\Laravel\actingAs;

function portalClient(): array
{
    foreach ([Role::Client, Role::ChefMarketing] as $role) {
        SpatieRole::findOrCreate($role->value, 'web');
    }

    $account = User::factory()->create(['role' => Role::Client]);
    $account->assignRole(Role::Client->value);

    $client = Client::factory()->create(['user_id' => $account->id]);

    return [$account, $client];
}

it('shows only published announcements in the client news feed', function () {
    [$account] = portalClient();

    Announcement::factory()->create(['title' => 'Nouvelle crème publiée']);
    Announcement::factory()->draft()->create(['title' => 'Brouillon caché']);

    actingAs($account)
        ->get(route('portal.news'))
        ->assertOk()
        ->assertSee('Nouvelle crème publiée')
        ->assertDontSee('Brouillon caché');
});

it('lets a client update their own profile', function () {
    [$account, $client] = portalClient();

    actingAs($account)
        ->patch(route('portal.profile.update'), [
            'name' => 'Boutique Belle Peau',
            'email' => 'contact@bellepeau.test',
            'phone' => '0123456789',
            'address' => '12 rue des Lys',
            'ville' => 'Lyon',
        ])
        ->assertRedirect(route('portal.profile'));

    $this->assertDatabaseHas('clients', [
        'id' => $client->id,
        'name' => 'Boutique Belle Peau',
        'ville' => 'Lyon',
    ]);
});

it('lets the chef marketing publish an announcement', function () {
    SpatieRole::findOrCreate(Role::ChefMarketing->value, 'web');
    $chef = User::factory()->create(['role' => Role::ChefMarketing]);
    $chef->assignRole(Role::ChefMarketing->value);

    actingAs($chef)
        ->post(route('announcements.store'), [
            'type' => AnnouncementType::Nouveaute->value,
            'title' => 'Lancement gamme bio',
            'content' => 'Découvrez notre nouvelle gamme certifiée bio.',
            'is_published' => 1,
        ])
        ->assertRedirect(route('announcements.index'));

    $this->assertDatabaseHas('announcements', [
        'title' => 'Lancement gamme bio',
        'created_by' => $chef->id,
        'is_published' => true,
    ]);
});

it('forbids a client from managing announcements', function () {
    [$account] = portalClient();

    actingAs($account)
        ->get(route('announcements.index'))
        ->assertForbidden();
});
