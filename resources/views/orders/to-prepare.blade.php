<x-app-layout title="Commandes à préparer">
    <x-slot name="header">
        <h1 class="text-2xl font-bold text-gray-900">Commandes à préparer</h1>
    </x-slot>

    <div class="space-y-6">
        <p class="text-sm text-gray-500">
            Commandes validées par le Chef Marketing, en attente d'émission du Bon de Sortie.
            L'émission décrémente physiquement le stock.
        </p>

        <div class="card p-0 overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                    <tr>
                        <th class="px-6 py-3">Référence</th>
                        <th class="px-6 py-3">Client</th>
                        <th class="px-6 py-3">Agent</th>
                        <th class="px-6 py-3">Produits</th>
                        <th class="px-6 py-3">Date</th>
                        <th class="px-6 py-3 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($orders as $order)
                        <tr class="hover:bg-gray-50 align-top">
                            <td class="px-6 py-4 font-medium text-gray-900">{{ $order->reference }}</td>
                            <td class="px-6 py-4 text-gray-700">{{ $order->client?->name ?? '—' }}</td>
                            <td class="px-6 py-4 text-gray-700">{{ $order->user?->name ?? '—' }}</td>
                            <td class="px-6 py-4 text-gray-600">
                                <ul class="space-y-0.5">
                                    @foreach ($order->items as $item)
                                        <li>{{ $item->product?->name }} <span class="text-gray-400">×{{ $item->quantite }}</span></li>
                                    @endforeach
                                </ul>
                            </td>
                            <td class="px-6 py-4 text-gray-500">{{ $order->date_commande?->format('d/m/Y') }}</td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('orders.show', $order) }}" class="btn-secondary !py-1.5 !px-3 text-xs">Détails</a>
                                    @can('createGoodsIssueNote', $order)
                                        <form method="POST" action="{{ route('orders.goods-issue-note', $order) }}"
                                              onsubmit="return confirm('Émettre le Bon de Sortie ? Le stock physique sera décrémenté.');">
                                            @csrf
                                            <button class="btn-primary !py-1.5 !px-3 text-xs">Générer le Bon de Sortie</button>
                                        </form>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-6 py-10 text-center text-gray-400">Aucune commande à préparer.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div>{{ $orders->links() }}</div>
    </div>
</x-app-layout>
