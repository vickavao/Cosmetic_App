<?php

use App\Enums\Role;
use App\Models\Client;
use App\Models\Product;
use App\Models\TerrainReport;
use App\Models\User;
use Spatie\Permission\Models\Role as SpatieRole;

function terrainUser(): User
{
    SpatieRole::findOrCreate(Role::MarketeurTerrain->value, 'web');
    $client = Client::factory()->create();
    $user = User::factory()->create([
        'role' => Role::MarketeurTerrain,
        'magasin' => 'Boutique Centre',
        'client_id' => $client->id,
    ]);
    $user->assignRole(Role::MarketeurTerrain->value);

    return $user;
}

it('stores a terrain report with distinct product lines', function () {
    $agent = terrainUser();
    $productA = Product::factory()->create(['price' => 10]);
    $productB = Product::factory()->create(['price' => 15]);

    $this->actingAs($agent)
        ->post(route('terrain.store'), [
            'date' => today()->toDateString(),
            'items' => [
                ['product_id' => $productA->id, 'quantite' => 2, 'prix_unitaire' => 10],
                ['product_id' => $productB->id, 'quantite' => 3, 'prix_unitaire' => 15],
            ],
        ])
        ->assertRedirect();

    $report = TerrainReport::first();
    expect($report->items)->toHaveCount(2);
    expect($report->nb_ventes)->toBe(5);

    expect($report->items->every(fn ($item) => $item->client_id === $agent->client_id))->toBeTrue();
});

it('merges duplicate product lines with same price (anti-doublon)', function () {
    $agent = terrainUser();
    $product = Product::factory()->create(['price' => 10]);

    $this->actingAs($agent)
        ->post(route('terrain.store'), [
            'date' => today()->toDateString(),
            'items' => [
                ['product_id' => $product->id, 'quantite' => 2, 'prix_unitaire' => 10],
                ['product_id' => $product->id, 'quantite' => 3, 'prix_unitaire' => 10],
            ],
        ])
        ->assertRedirect();

    $report = TerrainReport::first();
    expect($report->items)->toHaveCount(1);
    expect($report->items->first()->quantite)->toBe(5);
    expect($report->nb_ventes)->toBe(5);
});

it('keeps separate lines when prices differ', function () {
    $agent = terrainUser();
    $product = Product::factory()->create(['price' => 10]);

    $this->actingAs($agent)
        ->post(route('terrain.store'), [
            'date' => today()->toDateString(),
            'items' => [
                ['product_id' => $product->id, 'quantite' => 2, 'prix_unitaire' => 10],
                ['product_id' => $product->id, 'quantite' => 1, 'prix_unitaire' => 12],
            ],
        ])
        ->assertRedirect();

    $report = TerrainReport::first();
    expect($report->items)->toHaveCount(2);
    expect($report->nb_ventes)->toBe(3);
});
