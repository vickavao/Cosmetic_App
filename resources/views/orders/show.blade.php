<x-app-layout title="Détail commande">
    <x-slot name="header">
        <h1 class="text-2xl font-bold text-gray-900">Détail commande</h1>
    </x-slot>

@php
    $badge = fn (\App\Enums\OrderStatus $s) => match ($s) {
        \App\Enums\OrderStatus::EnAttenteValidation => 'badge-orange',
        \App\Enums\OrderStatus::Validee, \App\Enums\OrderStatus::EnPreparation => 'badge-indigo',
        \App\Enums\OrderStatus::PretPourLivraison => 'badge-indigo',
        \App\Enums\OrderStatus::LivreeEtFacturee => 'badge-green',
        \App\Enums\OrderStatus::Refusee, \App\Enums\OrderStatus::Annulee => 'badge-red',
    };

    $doneStatuses = [
        \App\Enums\OrderStatus::EnAttenteValidation,
        \App\Enums\OrderStatus::Validee,
        \App\Enums\OrderStatus::PretPourLivraison,
        \App\Enums\OrderStatus::EnPreparation,
        \App\Enums\OrderStatus::LivreeEtFacturee,
    ];

    $steps = [
        ['key' => 'commande', 'label' => 'Étape 1 : Commande', 'done' => true],
        ['key' => 'validee', 'label' => 'Étape 2 : Validation', 'done' => in_array($order->statut, [\App\Enums\OrderStatus::Validee, \App\Enums\OrderStatus::PretPourLivraison, \App\Enums\OrderStatus::LivreeEtFacturee])],
        ['key' => 'bon_sortie', 'label' => 'Étape 3 : Bon de Sortie', 'done' => in_array($order->statut, [\App\Enums\OrderStatus::PretPourLivraison, \App\Enums\OrderStatus::LivreeEtFacturee])],
        ['key' => 'livree', 'label' => 'Étape 4 : Livrée et facturée', 'done' => $order->statut === \App\Enums\OrderStatus::LivreeEtFacturee],
    ];
    if (in_array($order->statut, [\App\Enums\OrderStatus::Annulee, \App\Enums\OrderStatus::Refusee])) {
        $steps = [
            ['key' => 'commande', 'label' => 'Commande', 'done' => true],
            ['key' => 'refusee', 'label' => 'Refusée', 'done' => true],
        ];
    }
@endphp

    <div class="max-w-4xl mx-auto space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">{{ $order->reference }}</h1>
                <p class="text-sm text-gray-500">Commande du {{ $order->date_commande?->format('d/m/Y') }}</p>
            </div>
            <a href="{{ route('orders.index') }}" class="btn-secondary">Retour</a>
        </div>

        @if (!empty($manquants))
            <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                <p class="font-medium">Stock insuffisant :</p>
                <ul class="list-disc list-inside mt-1">
                    @foreach ($manquants as $m)
                        <li>{{ $m['name'] }} — demandé {{ $m['demande'] }}, disponible {{ $m['disponible'] }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Timeline de suivi --}}
        <div class="card">
            <p class="text-xs uppercase text-gray-400 font-medium mb-4">Suivi de la commande</p>
            <div class="flex items-center gap-2">
                @foreach ($steps as $i => $step)
                    @php
                        $isLast = $loop->last;
                        $color = $step['done'] ? 'bg-[#6366F1] border-[#6366F1] text-white' : 'bg-white border-gray-300 text-gray-400';
                    @endphp
                    <div class="flex items-center gap-2 {{ $isLast ? '' : 'flex-1' }}">
                        <div class="flex items-center justify-center w-8 h-8 rounded-full border-2 text-xs font-bold shrink-0 {{ $color }}">
                            {{ $i + 1 }}
                        </div>
                        <span class="text-sm font-medium {{ $step['done'] ? 'text-gray-900' : 'text-gray-400' }}">{{ $step['label'] }}</span>
                        @if (!$isLast)
                            <div class="h-0.5 flex-1 mx-2 {{ $steps[$i+1]['done'] ?? false ? 'bg-[#6366F1]' : 'bg-gray-200' }}"></div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-5">
            <div class="card">
                <p class="text-xs uppercase text-gray-400 font-medium">Client</p>
                <p class="mt-1 font-semibold text-gray-900">{{ $order->client?->name }}</p>
                <p class="text-sm text-gray-500">{{ $order->client?->ville }}</p>
            </div>
            <div class="card">
                <p class="text-xs uppercase text-gray-400 font-medium">Statut</p>
                <p class="mt-2"><span class="badge {{ $badge($order->statut) }}">{{ $order->statut->label() }}</span></p>
            </div>
            <div class="card">
                <p class="text-xs uppercase text-gray-400 font-medium">Type de vente</p>
                <p class="mt-1 font-semibold text-gray-900">{{ $order->type_vente === \App\Enums\SaleType::Credit ? 'À crédit' : 'Au comptant' }}</p>
                @if ($order->type_vente === \App\Enums\SaleType::Credit && $order->date_echeance)
                    <p class="text-sm text-gray-500">Échéance : {{ $order->date_echeance->format('d/m/Y') }}</p>
                @endif
            </div>
            <div class="card">
                <p class="text-xs uppercase text-gray-400 font-medium">Total</p>
                <p class="mt-1 text-xl font-bold text-gray-900">@money((float) $order->total)</p>
            </div>
        </div>

        {{-- Traçabilité --}}
        <div class="card">
            <p class="text-xs uppercase text-gray-400 font-medium mb-3">Traçabilité</p>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                <div>
                    <span class="text-gray-500">Créée par :</span>
                    <span class="font-medium text-gray-900">{{ $order->user?->name ?? '—' }}</span>
                    <span class="text-gray-400">le {{ $order->created_at?->format('d/m/Y H:i') }}</span>
                </div>
                <div>
                    @if ($order->traite_par)
                        <span class="text-gray-500">{{ $order->statut === \App\Enums\OrderStatus::Annulee ? 'Refusée par :' : 'Validée par :' }}</span>
                        <span class="font-medium text-gray-900">{{ $order->validator?->name ?? '—' }}</span>
                        <span class="text-gray-400">le {{ $order->traite_le?->format('d/m/Y H:i') }}</span>
                    @else
                        <span class="text-gray-400">En attente de décision du Chef Marketing.</span>
                    @endif
                </div>
            </div>
        </div>

        @if ($order->statut === \App\Enums\OrderStatus::Annulee && $order->motif_rejet)
            <div class="card border-l-4 border-red-500 bg-red-50">
                <p class="text-xs uppercase text-red-400 font-semibold">Feedback du Chef Marketing</p>
                <p class="mt-1 text-sm text-red-800">{{ $order->motif_rejet }}</p>
            </div>
        @endif

        <div class="card p-0 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200"><h2 class="text-base font-semibold text-gray-900">Articles</h2></div>
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                    <tr>
                        <th class="px-6 py-3">Produit</th>
                        <th class="px-6 py-3">Quantité</th>
                        <th class="px-6 py-3">Prix unitaire</th>
                        <th class="px-6 py-3 text-right">Sous-total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach ($order->items as $item)
                        <tr>
                            <td class="px-6 py-4 font-medium text-gray-900">{{ $item->product?->name }}</td>
                            <td class="px-6 py-4 text-gray-600">{{ $item->quantite }}</td>
                            <td class="px-6 py-4 text-gray-600">@money((float) $item->prix_unitaire)</td>
                            <td class="px-6 py-4 text-right font-medium text-gray-900">@money((float) $item->sous_total)</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Étape 2 : Validation / Refus (Chef Marketing) --}}
        @if ($order->statut === \App\Enums\OrderStatus::EnAttenteValidation)
            <div class="flex flex-col items-end gap-3" x-data="{ rejecting: false }">
                <div class="flex justify-end gap-3">
                    @can('reject', $order)
                        <button type="button" class="btn-danger" @click="rejecting = !rejecting">Refuser</button>
                    @endcan
                    @can('validate', $order)
                        <form method="POST" action="{{ route('orders.validate', $order) }}">
                            @csrf @method('PATCH')
                            <button class="btn-primary">Valider la commande</button>
                        </form>
                    @endcan
                </div>
                @can('reject', $order)
                    <form method="POST" action="{{ route('orders.reject', $order) }}" x-show="rejecting" x-cloak class="card w-full max-w-md space-y-3">
                        @csrf @method('PATCH')
                        <div>
                            <label class="form-label" for="motif_rejet">Motif du refus (transmis à l'agent)</label>
                            <textarea id="motif_rejet" name="motif_rejet" rows="3" class="form-input" placeholder="Ex : stock insuffisant, prix non validé...">{{ old('motif_rejet') }}</textarea>
                        </div>
                        <div class="flex justify-end gap-2">
                            <button type="button" class="btn-secondary" @click="rejecting = false">Annuler</button>
                            <button type="submit" class="btn-danger">Confirmer le refus</button>
                        </div>
                    </form>
                @endcan
            </div>
        @endif

        {{-- Étape 3 : Bon de Sortie (Magasinier) --}}
        @can('createGoodsIssueNote', $order)
            <div class="flex justify-end">
                <form method="POST" action="{{ route('orders.goods-issue-note', $order) }}"
                      onsubmit="return confirm('Émettre le Bon de Sortie ? Le stock physique sera décrémenté.');">
                    @csrf
                    <button class="btn-primary">Émettre le Bon de Sortie</button>
                </form>
            </div>
        @endcan

        {{-- Étape 4 : Facturation 1-click (Agent Marketeur) --}}
        @can('createInvoice', $order)
            <div class="flex justify-end">
                <form method="POST" action="{{ route('orders.invoice', $order) }}"
                      onsubmit="return confirm('Générer la facture et clôturer la commande ?');">
                    @csrf
                    <button class="btn-primary">Livrer et générer la facture</button>
                </form>
            </div>
        @endcan
    </div>
</x-app-layout>
