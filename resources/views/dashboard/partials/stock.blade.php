<div class="space-y-6">
    <div class="flex items-center justify-end">
        <a href="{{ route('products.index') }}" class="btn-primary">Ajuster Stock</a>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
        @include('partials.kpi', [
            'label' => 'Produits référencés',
            'value' => $kpis['total_produits'],
            'tone' => 'indigo',
            'hint' => 'Catalogue actif',
            'icon' => '<path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/>',
        ])
        @include('partials.kpi', [
            'label' => 'Unités en stock',
            'value' => number_format($kpis['unites_en_stock'], 0, ',', ' '),
            'tone' => 'green',
            'hint' => 'Quantité physique totale',
            'hintTone' => 'green',
            'icon' => '<path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/>',
        ])
        @include('partials.kpi', [
            'label' => 'Produits en rupture',
            'value' => $kpis['en_rupture'],
            'tone' => 'red',
            'valueTone' => 'red',
            'hint' => 'Réapprovisionnement requis',
            'icon' => '<path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>',
        ])
    </div>

    {{-- Étape 3 : Commandes validées à préparer (Bon de Sortie) --}}
    <div class="card p-0 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 flex items-center gap-2">
            <svg class="text-[#6366F1]" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8"/><path d="M12 17v4"/></svg>
            <h2 class="text-base font-semibold text-gray-900">Commandes à préparer</h2>
            <span class="badge badge-indigo ml-auto">{{ ($ordersToIssue ?? collect())->count() }}</span>
        </div>
        <div class="divide-y divide-gray-100">
            @forelse ($ordersToIssue ?? collect() as $order)
                <div class="flex items-center justify-between px-6 py-4">
                    <div>
                        <p class="text-sm font-medium text-gray-900">{{ $order->reference }} — {{ $order->client?->name }}</p>
                        <p class="text-xs text-gray-500">
                            Agent {{ $order->user?->name }} · {{ $order->items->count() }} produit(s) ·
                            {{ $order->items->sum('quantite') }} unité(s) · {{ $order->date_commande?->format('d/m/Y') }}
                        </p>
                    </div>
                    <div class="flex items-center gap-2">
                        <a href="{{ route('orders.show', $order) }}" class="btn-secondary !py-1.5 !px-3 text-xs">Détails</a>
                        @can('createGoodsIssueNote', $order)
                            <form method="POST" action="{{ route('orders.goods-issue-note', $order) }}"
                                  onsubmit="return confirm('Émettre le Bon de Sortie ? Le stock physique sera décrémenté.');">
                                @csrf
                                <button class="btn-primary !py-1.5 !px-3 text-xs">Générer le Bon de Sortie</button>
                            </form>
                        @endcan
                    </div>
                </div>
            @empty
                <div class="px-6 py-10 text-center text-sm text-gray-400">Aucune commande à préparer.</div>
            @endforelse
        </div>
    </div>

    {{-- Carousel des produits disponibles + quantités --}}
    <div class="card">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-base font-semibold text-gray-900">Produits disponibles</h2>
            <a href="{{ route('products.orders') }}" class="text-sm font-medium text-[#6366F1] hover:underline">Stock vs commandes</a>
        </div>
        @if (($disponibles ?? collect())->isEmpty())
            <p class="text-sm text-gray-400">Aucun produit disponible.</p>
        @else
            <div class="flex gap-4 overflow-x-auto pb-2">
                @foreach ($disponibles as $product)
                    <div class="min-w-[150px] rounded-xl border border-gray-200 overflow-hidden">
                        <div class="aspect-square bg-gray-100 flex items-center justify-center">
                            @if ($product->image_url)
                                <img src="{{ \Illuminate\Support\Str::startsWith($product->image_url, 'http') ? $product->image_url : \Illuminate\Support\Facades\Storage::url($product->image_url) }}" alt="{{ $product->name }}" class="h-full w-full object-cover">
                            @else
                                <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#cbd5e1" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/></svg>
                            @endif
                        </div>
                        <div class="p-3">
                            <p class="text-sm font-medium text-gray-900 truncate">{{ $product->name }}</p>
                            <p class="text-xs text-gray-500 mt-1">Stock : <span class="font-semibold text-gray-900">{{ $product->stock }}</span></p>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <div class="rounded-xl border border-red-200 bg-red-50 overflow-hidden">
        <div class="px-6 py-4 border-b border-red-200 flex items-center gap-2">
            <svg class="text-red-600" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
            <h2 class="text-base font-semibold text-red-800">Alertes de rupture</h2>
        </div>
        <div class="divide-y divide-red-100">
            @forelse ($alertes as $product)
                <div class="flex items-center justify-between px-6 py-4 bg-white/60">
                    <div>
                        <p class="text-sm font-medium text-gray-900">{{ $product->name }}</p>
                        <p class="text-xs text-gray-500">SKU {{ $product->sku }} · seuil {{ $product->seuil_alerte }}</p>
                    </div>
                    <div class="flex items-center gap-4">
                        <span class="badge badge-red">Stock : {{ $product->stock }}</span>
                        <a href="{{ route('products.edit', $product) }}" class="btn-secondary !py-1.5 !px-3 text-xs">Ajuster Stock</a>
                    </div>
                </div>
            @empty
                <div class="px-6 py-10 text-center text-sm text-gray-500 bg-white/60">Aucune rupture de stock. 🎉</div>
            @endforelse
        </div>
    </div>
</div>
