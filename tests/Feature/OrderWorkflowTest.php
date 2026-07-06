<?php

use App\Enums\OrderStatus;
use App\Enums\Role;
use App\Enums\SaleType;
use App\Models\Client;
use App\Models\GoodsIssueNote;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Notifications\CommandeValidee;
use App\Services\DocumentNumberService;
use App\Services\InventoryService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role as SpatieRole;

use function Pest\Laravel\actingAs;

/*
|--------------------------------------------------------------------------
| CDC V2 — 4-Step Order Workflow
|--------------------------------------------------------------------------
| Step 1: Agent Marketeur creates order (EN_ATTENTE, no stock change)
| Step 2: Chef Marketing validates (VALIDEE, notifies Magasinier)
| Step 3: Magasinier creates Bon de Sortie (PRETE_A_LIVRAISON, stock decremented)
| Step 4: Agent Marketeur delivers + auto-invoice (LIVREE)
|--------------------------------------------------------------------------
*/

function workflowUser(Role $role, array $attrs = []): User
{
    SpatieRole::findOrCreate($role->value, 'web');
    $user = User::factory()->create([...$attrs, 'role' => $role]);
    $user->assignRole($role->value);

    return $user;
}

beforeEach(function () {
    $this->chef = workflowUser(Role::ChefMarketing);
    $this->agent = workflowUser(Role::AgentMarketeur, ['supervisor_id' => $this->chef->id]);
    $this->magasinier = workflowUser(Role::Magasinier);
    $this->client = Client::factory()->create(['created_by' => $this->agent->id]);
    $this->product = Product::factory()->create(['stock' => 100, 'stock_reserved' => 0, 'price' => 25.00]);
});

// ──────────── Step 1: Order Creation ────────────

it('step 1: agent marketeur creates a comptant order', function () {
    $response = actingAs($this->agent)
        ->post(route('orders.store'), [
            'client_id' => $this->client->id,
            'type_vente' => 'comptant',
            'date_commande' => today()->toDateString(),
            'items' => [
                ['product_id' => $this->product->id, 'quantite' => 5],
            ],
        ]);

    $response->assertRedirect();

    $order = Order::latest('id')->first();
    expect($order->statut)->toBe(OrderStatus::EnAttenteValidation);
    expect($order->type_vente)->toBe(SaleType::Comptant);
    expect($order->date_echeance)->toBeNull();
    expect((float) $order->total)->toBe(125.00);

    expect($this->product->refresh()->stock)->toBe(100);
});

it('step 1: agent marketeur creates a credit order with date_echeance', function () {
    $echeance = today()->addDays(30)->toDateString();

    $response = actingAs($this->agent)
        ->post(route('orders.store'), [
            'client_id' => $this->client->id,
            'type_vente' => 'credit',
            'date_echeance' => $echeance,
            'date_commande' => today()->toDateString(),
            'items' => [
                ['product_id' => $this->product->id, 'quantite' => 3],
            ],
        ]);

    $response->assertRedirect();

    $order = Order::latest('id')->first();
    expect($order->statut)->toBe(OrderStatus::EnAttenteValidation);
    expect($order->type_vente)->toBe(SaleType::Credit);
    expect($order->date_echeance->toDateString())->toBe($echeance);
});

it('step 1: credit order without date_echeance is rejected', function () {
    $response = actingAs($this->agent)
        ->post(route('orders.store'), [
            'client_id' => $this->client->id,
            'type_vente' => 'credit',
            'date_commande' => today()->toDateString(),
            'items' => [
                ['product_id' => $this->product->id, 'quantite' => 2],
            ],
        ]);

    $response->assertSessionHasErrors('date_echeance');
});

it('step 1: stock does not change when order is created', function () {
    actingAs($this->agent)
        ->post(route('orders.store'), [
            'client_id' => $this->client->id,
            'type_vente' => 'comptant',
            'date_commande' => today()->toDateString(),
            'items' => [
                ['product_id' => $this->product->id, 'quantite' => 20],
            ],
        ]);

    expect($this->product->refresh()->stock)->toBe(100);
    expect($this->product->stock_reserved)->toBe(0);
});

// ──────────── Step 2: Validation ────────────

it('step 2: chef marketing validates order and notifies magasinier', function () {
    Notification::fake();

    $order = wfPendingOrder($this->agent, $this->client, $this->product, 5);

    $response = actingAs($this->chef)
        ->patch(route('orders.validate', $order));

    $response->assertRedirect();

    $order->refresh();
    expect($order->statut)->toBe(OrderStatus::Validee);
    expect($order->traite_par)->toBe($this->chef->id);

    Notification::assertSentTo($this->magasinier, CommandeValidee::class);
});

it('step 2: chef marketing can reject an order', function () {
    $order = wfPendingOrder($this->agent, $this->client, $this->product, 5);

    $response = actingAs($this->chef)
        ->patch(route('orders.reject', $order), [
            'motif_rejet' => 'Stock insuffisant pour le client.',
        ]);

    $response->assertRedirect();

    $order->refresh();
    expect($order->statut)->toBe(OrderStatus::Annulee);
    expect($order->motif_rejet)->toBe('Stock insuffisant pour le client.');
});

it('step 2: agent marketeur cannot validate orders', function () {
    $order = wfPendingOrder($this->agent, $this->client, $this->product, 5);

    $response = actingAs($this->agent)
        ->patch(route('orders.validate', $order));

    $response->assertForbidden();
});

// ──────────── Step 3: Bon de Sortie ────────────

it('step 3: magasinier creates bon de sortie and stock is decremented', function () {
    $order = wfValidatedOrder($this->agent, $this->chef, $this->client, $this->product, 10);

    $response = actingAs($this->magasinier)
        ->post(route('goods-issue-notes.store'), [
            'order_id' => $order->id,
        ]);

    $response->assertRedirect();

    $order->refresh();
    expect($order->statut)->toBe(OrderStatus::PretPourLivraison);

    $this->product->refresh();
    expect($this->product->stock)->toBe(90);

    $this->assertDatabaseHas('goods_issue_notes', [
        'order_id' => $order->id,
        'issued_by' => $this->magasinier->id,
    ]);
});

it('step 3: bon de sortie rejected for non-validated orders', function () {
    $order = wfPendingOrder($this->agent, $this->client, $this->product, 5);

    $response = actingAs($this->magasinier)
        ->post(route('goods-issue-notes.store'), [
            'order_id' => $order->id,
        ]);

    $response->assertRedirect();
    $response->assertSessionHas('warning');

    expect($this->product->refresh()->stock)->toBe(100);
});

it('step 3: agent marketeur cannot create bon de sortie', function () {
    $order = wfValidatedOrder($this->agent, $this->chef, $this->client, $this->product, 5);

    $response = actingAs($this->agent)
        ->post(route('goods-issue-notes.store'), [
            'order_id' => $order->id,
        ]);

    $response->assertForbidden();
});

// ──────────── Step 4: Livraison + Facture ────────────

it('step 4: agent delivers and invoice is auto-generated', function () {
    $order = wfReadyOrder($this->agent, $this->chef, $this->magasinier, $this->client, $this->product, 5);

    $response = actingAs($this->agent)
        ->post(route('deliveries.store'), [
            'order_id' => $order->id,
        ]);

    $response->assertRedirect();

    $order->refresh();
    expect($order->statut)->toBe(OrderStatus::LivreeEtFacturee);

    $this->assertDatabaseHas('deliveries', ['order_id' => $order->id]);
    $this->assertDatabaseHas('invoices', ['order_id' => $order->id]);
});

it('step 4: invoice inherits type_vente and date_echeance from order', function () {
    $echeance = today()->addDays(30);
    $order = wfReadyOrder(
        $this->agent, $this->chef, $this->magasinier, $this->client, $this->product, 5,
        typeVente: SaleType::Credit, dateEcheance: $echeance,
    );

    actingAs($this->agent)
        ->post(route('deliveries.store'), ['order_id' => $order->id]);

    $invoice = $order->invoices()->first();
    expect($invoice)->not->toBeNull();
    expect($invoice->type_vente)->toBe(SaleType::Credit);
    expect($invoice->date_echeance->toDateString())->toBe($echeance->toDateString());
});

it('step 4: magasinier cannot deliver orders', function () {
    $order = wfReadyOrder($this->agent, $this->chef, $this->magasinier, $this->client, $this->product, 5);

    $response = actingAs($this->magasinier)
        ->post(route('deliveries.store'), [
            'order_id' => $order->id,
        ]);

    $response->assertForbidden();
});

// ──────────── Full Workflow End-to-End ────────────

it('full 4-step workflow: order -> validation -> bon de sortie -> delivery + invoice', function () {
    Notification::fake();

    // Step 1: Agent creates order
    actingAs($this->agent)
        ->post(route('orders.store'), [
            'client_id' => $this->client->id,
            'type_vente' => 'comptant',
            'date_commande' => today()->toDateString(),
            'items' => [
                ['product_id' => $this->product->id, 'quantite' => 8],
            ],
        ]);

    $order = Order::latest('id')->first();
    expect($order->statut)->toBe(OrderStatus::EnAttenteValidation);
    expect($this->product->refresh()->stock)->toBe(100);

    // Step 2: Chef validates
    actingAs($this->chef)->patch(route('orders.validate', $order));
    $order->refresh();
    expect($order->statut)->toBe(OrderStatus::Validee);
    Notification::assertSentTo($this->magasinier, CommandeValidee::class);

    // Step 3: Magasinier creates bon de sortie
    actingAs($this->magasinier)->post(route('goods-issue-notes.store'), ['order_id' => $order->id]);
    $order->refresh();
    expect($order->statut)->toBe(OrderStatus::PretPourLivraison);
    expect($this->product->refresh()->stock)->toBe(92);

    // Step 4: Agent delivers
    actingAs($this->agent)->post(route('deliveries.store'), ['order_id' => $order->id]);
    $order->refresh();
    expect($order->statut)->toBe(OrderStatus::LivreeEtFacturee);

    $invoice = $order->invoices()->first();
    expect($invoice)->not->toBeNull();
    expect($invoice->type_vente)->toBe(SaleType::Comptant);
    expect((float) $invoice->montant)->toBe(200.00);
});

// ──────────── Helpers ────────────

function wfPendingOrder(User $agent, Client $client, Product $product, int $qty): Order
{
    $order = Order::create([
        'reference' => 'CMD-'.strtoupper(uniqid()),
        'client_id' => $client->id,
        'user_id' => $agent->id,
        'statut' => OrderStatus::EnAttenteValidation,
        'type_vente' => SaleType::Comptant,
        'total' => $product->price * $qty,
        'date_commande' => today(),
    ]);

    $order->items()->create([
        'product_id' => $product->id,
        'quantite' => $qty,
        'prix_unitaire' => $product->price,
        'sous_total' => $product->price * $qty,
    ]);

    return $order;
}

function wfValidatedOrder(User $agent, User $chef, Client $client, Product $product, int $qty): Order
{
    $order = wfPendingOrder($agent, $client, $product, $qty);

    app(InventoryService::class)->reserveForOrder($order, $chef->id);

    $order->update([
        'statut' => OrderStatus::Validee,
        'traite_par' => $chef->id,
        'traite_le' => now(),
    ]);

    return $order;
}

function wfReadyOrder(
    User $agent,
    User $chef,
    User $magasinier,
    Client $client,
    Product $product,
    int $qty,
    SaleType $typeVente = SaleType::Comptant,
    ?Carbon $dateEcheance = null,
): Order {
    $order = Order::create([
        'reference' => 'CMD-'.strtoupper(uniqid()),
        'client_id' => $client->id,
        'user_id' => $agent->id,
        'statut' => OrderStatus::EnAttenteValidation,
        'type_vente' => $typeVente,
        'date_echeance' => $dateEcheance,
        'total' => $product->price * $qty,
        'date_commande' => today(),
    ]);

    $order->items()->create([
        'product_id' => $product->id,
        'quantite' => $qty,
        'prix_unitaire' => $product->price,
        'sous_total' => $product->price * $qty,
    ]);

    app(InventoryService::class)->reserveForOrder($order, $chef->id);
    $order->update(['statut' => OrderStatus::Validee, 'traite_par' => $chef->id, 'traite_le' => now()]);

    $inventoryService = app(InventoryService::class);
    $documentNumbers = app(DocumentNumberService::class);

    $reference = $documentNumbers->goodsIssueReference(
        agentName: $agent->name,
        clientName: $client->name,
    );

    $note = GoodsIssueNote::create([
        'reference' => $reference,
        'order_id' => $order->id,
        'issued_by' => $magasinier->id,
        'date' => today(),
        'motif' => 'Bon de sortie pour commande '.$order->reference,
    ]);

    foreach ($order->items()->with('product')->get() as $item) {
        if ($item->product === null) {
            continue;
        }

        $inventoryService->issue(
            $item->product,
            (int) $item->quantite,
            $note,
            $magasinier->id,
            'Bon de sortie '.$note->reference,
        );

        $note->lines()->create([
            'product_id' => $item->product_id,
            'quantite' => $item->quantite,
        ]);
    }

    $order->update(['statut' => OrderStatus::PretPourLivraison]);

    return $order;
}
