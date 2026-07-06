<?php

use App\Enums\Role;
use App\Models\Client;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Spatie\Permission\Models\Role as SpatieRole;

function agentUser(): User
{
    SpatieRole::findOrCreate(Role::AgentMarketeur->value, 'web');
    $user = User::factory()->create(['role' => Role::AgentMarketeur]);
    $user->assignRole(Role::AgentMarketeur->value);

    return $user;
}

it('blocks the order when the requested quantity exceeds available stock', function () {
    $agent = agentUser();
    $client = Client::factory()->create();
    $product = Product::factory()->create(['stock' => 1, 'stock_reserved' => 0, 'seuil_alerte' => 0]);

    $this->actingAs($agent)
        ->post(route('orders.store'), [
            'client_id' => $client->id,
            'date_commande' => today()->toDateString(),
            'type_vente' => 'comptant',
            'items' => [['product_id' => $product->id, 'quantite' => 5]],
        ])
        ->assertSessionHasErrors(['items.0.quantite']);

    expect(Order::count())->toBe(0);
});

it('blocks the order when cumulated duplicate lines exceed available stock', function () {
    $agent = agentUser();
    $client = Client::factory()->create();
    $product = Product::factory()->create(['stock' => 4, 'stock_reserved' => 0, 'seuil_alerte' => 0]);

    $this->actingAs($agent)
        ->post(route('orders.store'), [
            'client_id' => $client->id,
            'date_commande' => today()->toDateString(),
            'type_vente' => 'comptant',
            'items' => [
                ['product_id' => $product->id, 'quantite' => 3],
                ['product_id' => $product->id, 'quantite' => 3],
            ],
        ])
        ->assertSessionHasErrors(['items.0.quantite']);

    expect(Order::count())->toBe(0);
});

it('creates the order when the requested quantity is within available stock', function () {
    $agent = agentUser();
    $client = Client::factory()->create();
    $product = Product::factory()->create(['stock' => 10, 'stock_reserved' => 0, 'seuil_alerte' => 0]);

    $this->actingAs($agent)
        ->post(route('orders.store'), [
            'client_id' => $client->id,
            'date_commande' => today()->toDateString(),
            'type_vente' => 'comptant',
            'items' => [['product_id' => $product->id, 'quantite' => 5]],
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect();

    expect(Order::count())->toBe(1);
});
