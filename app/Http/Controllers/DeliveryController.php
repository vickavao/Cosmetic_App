<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Enums\Role;
use App\Enums\SaleType;
use App\Models\Delivery;
use App\Models\Order;
use App\Services\DeliveryService;
use App\Services\InvoiceService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use RuntimeException;

class DeliveryController extends Controller
{
    public function __construct(
        private DeliveryService $deliveryService,
        private InvoiceService $invoiceService,
    ) {}

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Delivery::class);

        $user = $request->user();

        $deliveries = Delivery::query()
            ->when(
                $user->role === Role::AgentMarketeur,
                fn ($q) => $q->where('agent_id', $user->id)
            )
            ->with(['client', 'agent', 'order'])
            ->latest('date')
            ->paginate(20);

        return view('deliveries.index', ['deliveries' => $deliveries]);
    }

    /**
     * Show the form to create a delivery from a prepared order (prête à livraison).
     */
    public function create(Request $request): View
    {
        Gate::authorize('create', Delivery::class);

        $orders = Order::query()
            ->where('statut', OrderStatus::PreteALivraison->value)
            ->when(
                $request->user()->role === Role::AgentMarketeur,
                fn ($q) => $q->where('user_id', $request->user()->id)
            )
            ->with(['client', 'items.product'])
            ->latest('date_commande')
            ->get();

        return view('deliveries.create', ['orders' => $orders]);
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('create', Delivery::class);

        $data = $request->validate([
            'order_id' => ['required', 'integer', 'exists:orders,id'],
        ]);

        $order = Order::with(['client', 'items.product', 'user'])->findOrFail($data['order_id']);

        if ($order->statut !== OrderStatus::PreteALivraison) {
            return back()->with('warning', 'Seule une commande prête à livraison peut être livrée.');
        }

        try {
            $delivery = $this->deliveryService->createFromOrder(
                $order,
                $order->type_vente ?? SaleType::Comptant,
                agentId: $request->user()->id,
                createdBy: $request->user()->id,
            );
        } catch (RuntimeException $e) {
            return back()->with('warning', $e->getMessage());
        }

        return redirect()
            ->route('deliveries.show', $delivery)
            ->with('status', 'Livraison effectuée et facture générée automatiquement.');
    }

    public function show(Delivery $delivery): View
    {
        Gate::authorize('view', $delivery);

        $delivery->load(['client', 'agent', 'order', 'lines.product', 'goodsIssueNote.lines.product']);

        return view('deliveries.show', ['delivery' => $delivery]);
    }

    /**
     * Confirm the delivery: capture returns, issue stock, generate the issue note.
     */
    public function confirm(Request $request, Delivery $delivery): RedirectResponse
    {
        Gate::authorize('confirm', Delivery::class);

        $validated = $request->validate([
            'returns' => ['nullable', 'array'],
            'returns.*' => ['nullable', 'integer', 'min:0'],
        ]);

        try {
            $this->deliveryService->confirm($delivery, $request->user()->id, $validated['returns'] ?? []);
        } catch (RuntimeException $e) {
            return back()->with('warning', $e->getMessage());
        }

        return redirect()
            ->route('deliveries.show', $delivery)
            ->with('status', 'Livraison confirmée : stock déduit et bon de sortie généré.');
    }
}
