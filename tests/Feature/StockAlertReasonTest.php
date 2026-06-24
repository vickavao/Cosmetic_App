<?php

use App\Enums\Role;
use App\Enums\StockAlertReason;
use App\Enums\StockAlertStatus;
use App\Models\Product;
use App\Models\StockAlert;
use App\Models\User;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role as SpatieRole;

function magasinierUser(): User
{
    SpatieRole::findOrCreate(Role::Magasinier->value, 'web');
    $user = User::factory()->create(['role' => Role::Magasinier]);
    $user->assignRole(Role::Magasinier->value);

    return $user;
}

it('requires a motif when a magasinier raises a stock alert', function () {
    Notification::fake();
    $magasinier = magasinierUser();
    $product = Product::factory()->create();

    $this->actingAs($magasinier)
        ->from(route('stock-alerts.create'))
        ->post(route('stock-alerts.store'), ['product_id' => $product->id])
        ->assertSessionHasErrors('motif');

    expect(StockAlert::count())->toBe(0);
});

it('stores the motif when provided', function () {
    Notification::fake();
    $magasinier = magasinierUser();
    $product = Product::factory()->create();

    $this->actingAs($magasinier)
        ->post(route('stock-alerts.store'), [
            'product_id' => $product->id,
            'motif' => StockAlertReason::RuptureFournisseur->value,
        ])
        ->assertRedirect(route('stock-alerts.index'));

    $this->assertDatabaseHas('stock_alerts', [
        'product_id' => $product->id,
        'motif' => StockAlertReason::RuptureFournisseur->value,
    ]);
});

it('filters stock alerts by motif and status', function () {
    $chef = User::factory()->create(['role' => Role::ChefMarketing]);
    SpatieRole::findOrCreate(Role::ChefMarketing->value, 'web');
    $chef->assignRole(Role::ChefMarketing->value);

    $product = Product::factory()->create();

    StockAlert::create([
        'product_id' => $product->id,
        'motif' => StockAlertReason::RuptureFournisseur,
        'statut' => StockAlertStatus::EnAttente,
        'source' => 'magasinier',
    ]);
    StockAlert::create([
        'product_id' => $product->id,
        'motif' => StockAlertReason::ForteDemande,
        'statut' => StockAlertStatus::Resolu,
        'source' => 'magasinier',
    ]);

    $response = $this->actingAs($chef)
        ->get(route('stock-alerts.index', ['motif' => StockAlertReason::RuptureFournisseur->value]));

    $response->assertOk();
    expect($response->viewData('alerts')->total())->toBe(1);
});
