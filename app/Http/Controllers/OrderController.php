<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Enums\Role;
use App\Http\Requests\StoreOrderRequest;
use App\Models\Client;
use App\Models\GoodsIssueNote;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Notifications\BonSortieEmis;
use App\Notifications\CommandeSoumise;
use App\Notifications\CommandeTraitee;
use App\Notifications\CommandeValidee;
use App\Notifications\StockInsuffisant;
use App\Services\InventoryService;
use App\Services\InvoiceService;
use App\Services\OrderService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

class OrderController extends Controller
{
    public function __construct(
        private OrderService $orderService,
        private InventoryService $inventoryService,
    ) {}

    public function index(Request $request): View
    {
        $user = $request->user();

        $orders = Order::query()
            ->when($request->boolean('en_attente'), fn ($q) => $q->enAttente())
            ->when(
                $user->role === Role::AgentMarketeur || $user->role === Role::MarketeurTerrain,
                fn ($q) => $q->where('user_id', $user->id)
            )
            ->with(['client', 'user'])
            ->latest('date_commande')
            ->paginate(20);

        return view('orders.index', ['orders' => $orders]);
    }

    public function create(): View
    {
        Gate::authorize('create', Order::class);

        return view('orders.create', [
            'clients' => Client::query()->orderBy('name')->get(),
            'products' => Product::query()->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(StoreOrderRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $order = DB::transaction(function () use ($data, $request): Order {
            $order = Order::create([
                'reference' => 'CMD-'.strtoupper(Str::random(8)),
                'client_id' => $data['client_id'],
                'user_id' => $request->user()->id,
                'statut' => OrderStatus::EnAttenteValidation,
                'type_vente' => $data['type_vente'],
                'date_echeance' => $data['date_echeance'] ?? null,
                'total' => 0,
                'date_commande' => $data['date_commande'] ?? today(),
                'notes' => $data['notes'] ?? null,
            ]);

            $total = 0;

            $merged = [];
            foreach ($data['items'] as $line) {
                $pid = $line['product_id'];
                if (isset($merged[$pid])) {
                    $merged[$pid]['quantite'] += $line['quantite'];
                } else {
                    $merged[$pid] = $line;
                }
            }

            foreach ($merged as $line) {
                $product = Product::findOrFail($line['product_id']);
                $sousTotal = $product->price * $line['quantite'];
                $total += $sousTotal;

                $order->items()->create([
                    'product_id' => $product->id,
                    'quantite' => $line['quantite'],
                    'prix_unitaire' => $product->price,
                    'sous_total' => $sousTotal,
                ]);
            }

            $order->update(['total' => $total]);

            return $order;
        });

        // Notifier le Chef Marketing (supervisor) de la soumission
        $chef = $order->user?->supervisor;
        if ($chef) {
            $chef->notify(new CommandeSoumise($order));
        }

        return redirect()
            ->route('orders.show', $order)
            ->with('status', 'Commande créée et soumise au Chef Marketing.');
    }

    /**
     * Notify stock managers (production) about products in rupture.
     *
     * @param  array<int, array{product_id:int, name:string, demande:int, disponible:int}>  $manquants
     */
    private function notifyStockManagers(Order $order, array $manquants): void
    {
        $stockManagers = User::query()
            ->whereIn('role', [Role::Magasinier->value, Role::Directeur->value, Role::Admin->value])
            ->where('is_active', true)
            ->get();

        if ($stockManagers->isNotEmpty()) {
            Notification::send($stockManagers, new StockInsuffisant($order, $manquants));
        }
    }

    public function show(Order $order): View
    {
        Gate::authorize('view', $order);

        $order->load(['client', 'user', 'items.product']);

        return view('orders.show', [
            'order' => $order,
            'manquants' => $this->orderService->checkStock($order),
        ]);
    }

    /**
     * Validate an order: decrement stock and flag rupture.
     */
    public function validateOrder(Order $order): RedirectResponse
    {
        Gate::authorize('validate', $order);

        if ($order->statut !== OrderStatus::EnAttenteValidation) {
            return back()->with('warning', 'Cette commande a déjà été traitée.');
        }

        $requestUser = request()->user();

        // Retirer du panier les produits devenus indisponibles avant validation
        $manquants = $this->orderService->pruneUnavailableItems($order);

        if ($manquants !== []) {
            $this->orderService->recordStockAlerts($order, $manquants, $requestUser?->id);

            // Notifier le Chef Marketing (celui qui valide) + la production
            if ($requestUser) {
                $requestUser->notify(new StockInsuffisant($order, $manquants));
            }
            $this->notifyStockManagers($order, $manquants);

            // Notifier l'agent du retrait des produits en rupture
            if ($order->user) {
                $order->user->notify(new CommandeTraitee($order, 'stock_insufficient'));
            }
        }

        // Si tous les produits ont été retirés, rien à valider
        if ($order->items()->count() === 0) {
            return back()->with('warning', 'Validation impossible : tous les produits sont en rupture. Les responsables ont été alertés.');
        }

        // Réserver (bloquer) le stock disponible sans toucher au stock physique.
        // Le stock physique ne sera déduit qu'à la livraison / bon de sortie.
        $reserveManquants = $this->inventoryService->reserveForOrder($order, $requestUser?->id);

        if ($reserveManquants !== []) {
            $this->orderService->recordStockAlerts($order, $reserveManquants, $requestUser?->id);
            $this->notifyStockManagers($order, $reserveManquants);

            $noms = implode(', ', array_column($reserveManquants, 'name'));

            return back()->with('warning', "Réservation impossible : stock disponible insuffisant pour {$noms}. Les responsables stock ont été alertés.");
        }

        $order->update([
            'statut' => OrderStatus::Validee,
            'traite_par' => $requestUser?->id,
            'traite_le' => now(),
        ]);

        // Notifier l'agent que sa commande a été validée
        if ($order->user) {
            $order->user->notify(new CommandeTraitee($order, 'validated'));
        }

        // Notifier les magasiniers pour préparer le bon de sortie
        $magasiniers = User::query()
            ->where('role', Role::Magasinier->value)
            ->where('is_active', true)
            ->get();

        if ($magasiniers->isNotEmpty()) {
            Notification::send($magasiniers, new CommandeValidee($order));
        }

        $this->orderService->notifyRupture();

        if ($manquants !== []) {
            $noms = implode(', ', array_column($manquants, 'name'));

            return back()->with('warning', "Commande validée partiellement. Produit(s) retiré(s) pour rupture : {$noms}.");
        }

        return back()->with('status', 'Commande validée.');
    }

    /**
     * Refuse a pending order.
     */
    public function rejectOrder(Request $request, Order $order): RedirectResponse
    {
        Gate::authorize('reject', $order);

        if ($order->statut !== OrderStatus::EnAttenteValidation) {
            return back()->with('warning', 'Cette commande a déjà été traitée.');
        }

        $data = $request->validate([
            'motif_rejet' => ['nullable', 'string', 'max:1000'],
        ]);

        // Au cas où une réservation existait déjà, on la libère.
        $this->inventoryService->releaseForOrder($order, $request->user()?->id);

        $order->update([
            'statut' => OrderStatus::Annulee,
            'traite_par' => $request->user()?->id,
            'traite_le' => now(),
            'motif_rejet' => $data['motif_rejet'] ?? null,
        ]);

        // Notifier l'agent que sa commande a été refusée
        if ($order->user) {
            $order->user->notify(new CommandeTraitee($order, 'rejected'));
        }

        return back()->with('status', 'Commande refusée.');
    }

    /**
     * Dedicated page for the Magasinier: validated orders waiting for a
     * Bon de Sortie (Étape 3). Listing the freshest validated orders first.
     */
    public function toPrepare(): View
    {
        $orders = Order::query()
            ->where('statut', OrderStatus::Validee->value)
            ->with(['client', 'user', 'items.product'])
            ->latest('date_commande')
            ->paginate(20);

        return view('orders.to-prepare', ['orders' => $orders]);
    }

    /**
     * Dedicated page for Chef Marketing: all pending orders with filters.
     */
    public function pendingValidation(Request $request): View
    {
        $orders = Order::query()
            ->where('statut', OrderStatus::EnAttenteValidation)
            ->when($request->filled('client_id'), fn ($q) => $q->where('client_id', $request->integer('client_id')))
            ->when($request->filled('agent_id'), fn ($q) => $q->where('user_id', $request->integer('agent_id')))
            ->when($request->filled('date_from'), fn ($q) => $q->whereDate('date_commande', '>=', $request->date('date_from')))
            ->when($request->filled('date_to'), fn ($q) => $q->whereDate('date_commande', '<=', $request->date('date_to')))
            ->with(['client', 'user', 'items.product'])
            ->latest('date_commande')
            ->paginate(20);

        return view('orders.pending', [
            'orders' => $orders,
            'clients' => Client::query()->orderBy('name')->get(),
            'agents' => User::query()->where('role', Role::AgentMarketeur->value)->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function destroy(Order $order): RedirectResponse
    {
        Gate::authorize('delete', $order);

        $order->delete();

        return redirect()
            ->route('orders.index')
            ->with('status', 'Commande supprimée.');
    }

    /**
     * Create Goods Issue Note (Bon de Sortie) - Étape 3 du workflow sécurisé.
     * Décrémente physiquement le stock (via InventoryService) et passe la
     * commande à PretPourLivraison. Seul le Magasinier est autorisé.
     */
    public function createGoodsIssueNote(Order $order): RedirectResponse
    {
        Gate::authorize('createGoodsIssueNote', $order);

        if ($order->statut !== OrderStatus::Validee) {
            return back()->with('warning', 'Le Bon de Sortie ne peut être émis que pour une commande validée.');
        }

        $requestUser = request()->user();

        DB::transaction(function () use ($order, $requestUser): void {
            $order->loadMissing('items');

            $goodsIssueNote = GoodsIssueNote::create([
                'reference' => 'BS-'.strtoupper(Str::random(8)),
                'order_id' => $order->id,
                'issued_by' => $requestUser->id,
                'date' => today(),
            ]);

            foreach ($order->items as $item) {
                $product = Product::query()->find($item->product_id);

                if ($product === null) {
                    continue;
                }

                // Sortie physique : décrémente le stock réel et consomme la
                // réservation correspondante, le tout journalisé.
                $this->inventoryService->issue(
                    $product,
                    $item->quantite,
                    $goodsIssueNote,
                    $requestUser->id,
                    'Bon de sortie '.$goodsIssueNote->reference,
                );

                $goodsIssueNote->lines()->create([
                    'product_id' => $item->product_id,
                    'quantite' => $item->quantite,
                ]);
            }

            $order->update(['statut' => OrderStatus::PretPourLivraison]);

            // Notifier l'Agent Marketeur de l'autorisation officielle de livrer.
            if ($order->user) {
                $order->user->notify(new BonSortieEmis($order));
            }
        });

        return back()->with('status', 'Bon de Sortie émis. Stock décrémenté. Commande prête pour livraison.');
    }

    /**
     * Create Invoice - Étape 4 du workflow sécurisé.
     * Génère la facture en clonant les lignes de la commande validée puis passe
     * la commande à LivreeEtFacturee. Seul l'Agent Marketeur est autorisé.
     */
    public function createInvoice(Order $order, InvoiceService $invoiceService): RedirectResponse
    {
        Gate::authorize('createInvoice', $order);

        if ($order->statut !== OrderStatus::PretPourLivraison) {
            return back()->with('warning', 'La facture ne peut être générée que pour une commande prête pour livraison.');
        }

        $requestUser = request()->user();

        $invoice = DB::transaction(function () use ($order, $requestUser, $invoiceService): Invoice {
            $order->loadMissing('items');

            $items = $order->items
                ->map(fn ($item): array => [
                    'product_id' => $item->product_id,
                    'quantite' => $item->quantite,
                ])
                ->all();

            $invoice = $invoiceService->create(
                $order->client_id,
                $requestUser->id,
                $order->type_vente,
                $items,
                $order->notes,
                $order->id,
                $order->date_echeance,
            );

            $order->update(['statut' => OrderStatus::LivreeEtFacturee]);

            return $invoice;
        });

        return redirect()
            ->route('invoices.show', $invoice)
            ->with('status', 'Facture générée. Commande livrée et facturée.');
    }
}
