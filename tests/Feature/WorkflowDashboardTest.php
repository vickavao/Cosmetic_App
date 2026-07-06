<?php

use App\Enums\InvoiceStatus;
use App\Enums\OrderStatus;
use App\Enums\Role;
use App\Enums\SaleType;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Spatie\Permission\Models\Role as SpatieRole;

use function Pest\Laravel\actingAs;

function dashUser(Role $role, array $attrs = []): User
{
    SpatieRole::findOrCreate($role->value, 'web');
    $user = User::factory()->create([...$attrs, 'role' => $role]);
    $user->assignRole($role->value);

    return $user;
}

function dashOrder(User $agent, Client $client, Product $product, OrderStatus $statut): Order
{
    $order = Order::create([
        'reference' => 'CMD-'.strtoupper(uniqid()),
        'client_id' => $client->id,
        'user_id' => $agent->id,
        'statut' => $statut,
        'type_vente' => SaleType::Comptant,
        'total' => $product->price * 2,
        'date_commande' => today(),
    ]);

    $order->items()->create([
        'product_id' => $product->id,
        'quantite' => 2,
        'prix_unitaire' => $product->price,
        'sous_total' => $product->price * 2,
    ]);

    return $order;
}

beforeEach(function () {
    $this->chef = dashUser(Role::ChefMarketing);
    $this->agent = dashUser(Role::AgentMarketeur, ['supervisor_id' => $this->chef->id]);
    $this->magasinier = dashUser(Role::Magasinier);
    $this->client = Client::factory()->create(['created_by' => $this->agent->id]);
    $this->product = Product::factory()->create(['stock' => 100, 'stock_reserved' => 0, 'price' => 25.00]);
});

it('magasinier dashboard shows validated orders with goods issue action', function () {
    $order = dashOrder($this->agent, $this->client, $this->product, OrderStatus::Validee);

    actingAs($this->magasinier)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee($order->reference)
        ->assertSee('Générer le Bon de Sortie');
});

it('magasinier dashboard hides orders that are not yet validated', function () {
    $order = dashOrder($this->agent, $this->client, $this->product, OrderStatus::EnAttenteValidation);

    actingAs($this->magasinier)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertDontSee($order->reference);
});

it('agent dashboard shows the deliver/invoice action only once ready for delivery', function () {
    $order = dashOrder($this->agent, $this->client, $this->product, OrderStatus::PretPourLivraison);

    actingAs($this->agent)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee($order->reference)
        ->assertSee('Livrer et générer la facture');
});

it('agent dashboard does not show deliver action before the goods issue note', function () {
    dashOrder($this->agent, $this->client, $this->product, OrderStatus::Validee);

    actingAs($this->agent)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertDontSee('Livrer et générer la facture');
});

it('agent can download the invoice pdf', function () {
    $order = dashOrder($this->agent, $this->client, $this->product, OrderStatus::LivreeEtFacturee);

    $invoice = Invoice::create([
        'reference' => 'FAC-'.strtoupper(uniqid()),
        'client_id' => $this->client->id,
        'agent_id' => $this->agent->id,
        'order_id' => $order->id,
        'statut' => InvoiceStatus::Emise,
        'type_vente' => SaleType::Comptant,
        'montant' => 50.00,
        'montant_paye' => 0,
        'date' => today(),
    ]);

    $invoice->lines()->create([
        'product_id' => $this->product->id,
        'quantite' => 2,
        'prix_unitaire' => 25.00,
        'sous_total' => 50.00,
    ]);

    $response = actingAs($this->agent)->get(route('invoices.pdf', $invoice));

    $response->assertOk();
    expect($response->headers->get('content-type'))->toContain('application/pdf');
});
