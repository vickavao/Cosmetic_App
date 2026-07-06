<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Models\GoodsIssueNote;
use App\Models\Order;
use App\Services\DocumentNumberService;
use App\Services\InventoryService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GoodsIssueNoteController extends Controller
{
    public function __construct(
        private InventoryService $inventory,
        private DocumentNumberService $numbers,
    ) {}

    /**
     * Show validated orders ready for goods issue.
     */
    public function create(Request $request): View
    {
        $orders = Order::query()
            ->where('statut', OrderStatus::Validee->value)
            ->with(['client', 'items.product', 'user'])
            ->latest('date_commande')
            ->get();

        return view('goods-issue-notes.create', ['orders' => $orders]);
    }

    /**
     * Create the goods issue note for a validated order, decrementing physical stock.
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'order_id' => ['required', 'integer', 'exists:orders,id'],
        ]);

        $order = Order::with(['client', 'items.product', 'user'])->findOrFail($data['order_id']);

        if ($order->statut !== OrderStatus::Validee) {
            return back()->with('warning', 'Seule une commande validée peut faire l\'objet d\'un bon de sortie.');
        }

        $note = DB::transaction(function () use ($order, $request): GoodsIssueNote {
            $reference = $this->numbers->goodsIssueReference(
                agentName: $order->user?->name ?? 'XX',
                clientName: $order->client?->name ?? 'XX',
            );

            $note = GoodsIssueNote::create([
                'reference' => $reference,
                'order_id' => $order->id,
                'issued_by' => $request->user()->id,
                'date' => today(),
                'motif' => 'Bon de sortie pour commande '.$order->reference,
            ]);

            foreach ($order->items()->with('product')->get() as $item) {
                if ($item->product === null) {
                    continue;
                }

                $this->inventory->issue(
                    $item->product,
                    (int) $item->quantite,
                    $note,
                    $request->user()->id,
                    'Bon de sortie '.$note->reference,
                );

                $note->lines()->create([
                    'product_id' => $item->product_id,
                    'quantite' => $item->quantite,
                ]);
            }

            $order->update(['statut' => OrderStatus::PretPourLivraison]);

            return $note;
        });

        return redirect()
            ->route('goods-issue-notes.show', $note)
            ->with('status', 'Bon de sortie créé. Stock décrémenté. Commande prête pour livraison.');
    }

    public function show(GoodsIssueNote $goodsIssueNote): View
    {
        $goodsIssueNote->load(['order.client', 'order.user', 'order.items.product', 'lines.product', 'issuer']);

        return view('goods-issue-notes.show', ['note' => $goodsIssueNote]);
    }
}
