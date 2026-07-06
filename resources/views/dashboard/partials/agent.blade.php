@php
    $badge = fn (\App\Enums\OrderStatus $s) => match ($s) {
        \App\Enums\OrderStatus::EnAttenteValidation => 'badge-orange',
        \App\Enums\OrderStatus::Validee, \App\Enums\OrderStatus::EnPreparation => 'badge-indigo',
        \App\Enums\OrderStatus::PretPourLivraison => 'badge-blue',
        \App\Enums\OrderStatus::LivreeEtFacturee => 'badge-green',
        \App\Enums\OrderStatus::Refusee, \App\Enums\OrderStatus::Annulee => 'badge-red',
    };
@endphp

<div class="space-y-6">
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-5">
        @include('partials.kpi', [
            'label' => 'En attente',
            'value' => $kpis['commandes_en_attente'],
            'tone' => 'orange',
            'hint' => 'Soumises au Chef Marketing',
            'icon' => '<circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/>',
        ])
        @include('partials.kpi', [
            'label' => 'En cours',
            'value' => $kpis['en_cours'],
            'tone' => 'indigo',
            'hint' => 'Validées / En préparation',
            'icon' => '<path d="M21 12a9 9 0 1 1-6.219-8.56"/>',
        ])
        @include('partials.kpi', [
            'label' => 'Livrées',
            'value' => $kpis['livrees'],
            'tone' => 'green',
            'hint' => 'Commandes terminées',
            'hintTone' => 'green',
            'icon' => '<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><path d="m9 11 3 3L22 4"/>',
        ])
        @include('partials.kpi', [
            'label' => 'Refusées',
            'value' => $kpis['refusees'],
            'tone' => 'red',
            'hint' => 'Rejetées par le Chef',
            'icon' => '<path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>',
        ])
    </div>

    {{-- Étape 4 : Commandes prêtes à livrer (Bon de Sortie émis par le Magasinier) --}}
    @if (($ordersToDeliver ?? collect())->isNotEmpty())
        <div class="rounded-xl border border-blue-200 bg-blue-50 overflow-hidden">
            <div class="px-6 py-4 border-b border-blue-200 flex items-center gap-2">
                <svg class="text-blue-600" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 18H3c-.6 0-1-.4-1-1V7c0-.6.4-1 1-1h10c.6 0 1 .4 1 1v11"/><path d="M14 9h4l4 4v4c0 .6-.4 1-1 1h-2"/><circle cx="7" cy="18" r="2"/><circle cx="17" cy="18" r="2"/></svg>
                <h2 class="text-base font-semibold text-blue-800">À livrer et facturer</h2>
                <span class="badge badge-blue ml-auto">{{ $ordersToDeliver->count() }}</span>
            </div>
            <div class="divide-y divide-blue-100">
                @foreach ($ordersToDeliver as $order)
                    <div class="flex items-center justify-between px-6 py-4 bg-white/60">
                        <div>
                            <p class="text-sm font-medium text-gray-900">{{ $order->reference }} — {{ $order->client?->name }}</p>
                            <p class="text-xs text-gray-500">
                                {{ $order->items->count() }} produit(s) · {{ $order->items->sum('quantite') }} unité(s) ·
                                Bon de Sortie émis · @money((float) $order->total)
                            </p>
                        </div>
                        <div class="flex items-center gap-2">
                            <a href="{{ route('orders.show', $order) }}" class="btn-secondary !py-1.5 !px-3 text-xs">Détails</a>
                            @can('createInvoice', $order)
                                <form method="POST" action="{{ route('orders.invoice', $order) }}"
                                      onsubmit="return confirm('Générer la facture et clôturer la livraison ?');">
                                    @csrf
                                    <button class="btn-primary !py-1.5 !px-3 text-xs">Livrer et générer la facture</button>
                                </form>
                            @endcan
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
            <h2 class="text-base font-semibold text-gray-900">Mes commandes récentes</h2>
            <div class="flex items-center gap-3">
                <a href="{{ route('orders.index') }}" class="text-sm font-medium text-[#6366F1] hover:underline">Voir tout</a>
                <a href="{{ route('orders.create') }}" class="btn-primary !py-1.5 !px-3 text-xs">+ Nouvelle</a>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                    <tr>
                        <th class="px-6 py-3">Référence</th>
                        <th class="px-6 py-3">Client</th>
                        <th class="px-6 py-3">Date</th>
                        <th class="px-6 py-3">Total</th>
                        <th class="px-6 py-3">Statut</th>
                        <th class="px-6 py-3 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($recentOrders as $order)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 font-medium text-gray-900">{{ $order->reference }}</td>
                            <td class="px-6 py-4 text-gray-600">{{ $order->client?->name ?? '—' }}</td>
                            <td class="px-6 py-4 text-gray-500">{{ $order->date_commande?->format('d/m/Y') }}</td>
                            <td class="px-6 py-4 font-medium text-gray-900">@money((float) $order->total)</td>
                            <td class="px-6 py-4"><span class="badge {{ $badge($order->statut) }}">{{ $order->statut->label() }}</span></td>
                            <td class="px-6 py-4 text-right">
                                <a href="{{ route('orders.show', $order) }}" class="text-sm font-medium text-[#6366F1] hover:underline">Suivre</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-10 text-center text-gray-400">Aucune commande pour le moment.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
            <h2 class="text-base font-semibold text-gray-900">Dernières actions de l'équipe</h2>
            <a href="{{ route('terrain.team') }}" class="text-sm font-medium text-[#6366F1] hover:underline">Voir tout</a>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                    <tr>
                        <th class="px-6 py-3">Marketeur</th>
                        <th class="px-6 py-3">Date</th>
                        <th class="px-6 py-3">Ventes</th>
                        <th class="px-6 py-3">Statut</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($teamReports as $report)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 font-medium text-gray-900">{{ $report->user?->name }}</td>
                            <td class="px-6 py-4 text-gray-500">{{ $report->date?->format('d/m/Y') }}</td>
                            <td class="px-6 py-4 text-gray-900">{{ $report->nb_ventes }}</td>
                            <td class="px-6 py-4">
                                @if ($report->plaintes_clients)
                                    <span class="badge badge-orange">Plainte signalée</span>
                                @elseif ($report->rupture_stock)
                                    <span class="badge badge-red">Rupture stock</span>
                                @else
                                    <span class="badge badge-green">RAS</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-10 text-center text-gray-400">Aucun rapport pour le moment.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
