<?php

use App\Enums\InvoiceStatus;
use App\Enums\Role;
use App\Enums\SaleType;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\User;
use Spatie\Permission\Models\Role as SpatieRole;

use function Pest\Laravel\actingAs;

function reportUser(Role $role, array $attrs = []): User
{
    SpatieRole::findOrCreate($role->value, 'web');
    $user = User::factory()->create([...$attrs, 'role' => $role]);
    $user->assignRole($role->value);

    return $user;
}

function reportInvoice(User $agent, Client $client, float $montant): Invoice
{
    $invoice = Invoice::create([
        'reference' => 'FAC-'.strtoupper(uniqid()),
        'client_id' => $client->id,
        'agent_id' => $agent->id,
        'statut' => InvoiceStatus::Emise,
        'type_vente' => SaleType::Comptant,
        'montant' => $montant,
        'montant_paye' => 0,
        'date' => today(),
    ]);

    return $invoice;
}

it('lets the chef marketing export the consolidated daily sales report as pdf', function () {
    $chef = reportUser(Role::ChefMarketing);
    $agentOne = reportUser(Role::AgentMarketeur, ['supervisor_id' => $chef->id]);
    $agentTwo = reportUser(Role::AgentMarketeur, ['supervisor_id' => $chef->id]);
    $client = Client::factory()->create();

    reportInvoice($agentOne, $client, 120.00);
    reportInvoice($agentTwo, $client, 80.00);

    $response = actingAs($chef)->get(route('invoices.daily-report', ['date' => today()->toDateString()]));

    $response->assertOk();
    expect($response->headers->get('content-type'))->toContain('application/pdf');
});

it('forbids an agent marketeur from pulling the all-agents daily report', function () {
    $agent = reportUser(Role::AgentMarketeur);

    actingAs($agent)
        ->get(route('invoices.daily-report'))
        ->assertForbidden();
});

it('hides the daily report export button from the agent invoices screen', function () {
    $agent = reportUser(Role::AgentMarketeur);

    actingAs($agent)
        ->get(route('invoices.index'))
        ->assertOk()
        ->assertDontSee('Rapport des ventes journalier');
});

it('shows the daily report export button to the chef marketing', function () {
    $chef = reportUser(Role::ChefMarketing);

    actingAs($chef)
        ->get(route('invoices.index'))
        ->assertOk()
        ->assertSee('Rapport des ventes journalier');
});
