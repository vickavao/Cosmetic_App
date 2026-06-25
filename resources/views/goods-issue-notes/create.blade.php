<x-app-layout title="Bon de Sortie">
    <x-slot name="header">
        <h1 class="text-2xl font-bold text-gray-900">Bon de Sortie</h1>
    </x-slot>

    <div class="max-w-4xl mx-auto space-y-6">
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-bold text-gray-900">Commandes validées</h1>
        </div>

        @if ($orders->isEmpty())
            <div class="card text-center py-10">
                <p class="text-gray-500">Aucune commande validée en attente de bon de sortie.</p>
            </div>
        @else
            @foreach ($orders as $order)
                <div class="card space-y-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <h2 class="text-lg font-semibold text-gray-900">{{ $order->reference }}</h2>
                            <p class="text-sm text-gray-500">
                                Client : {{ $order->client?->name }} &mdash;
                                Agent : {{ $order->user?->name }} &mdash;
                                {{ $order->date_commande?->format('d/m/Y') }}
                            </p>
                        </div>
                        <span class="badge badge-indigo">{{ $order->statut->label() }}</span>
                    </div>

                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                            <tr>
                                <th class="px-4 py-2">Produit</th>
                                <th class="px-4 py-2">Quantite</th>
                                <th class="px-4 py-2">Stock actuel</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($order->items as $item)
                                <tr>
                                    <td class="px-4 py-2 font-medium text-gray-900">{{ $item->product?->name }}</td>
                                    <td class="px-4 py-2 text-gray-600">{{ $item->quantite }}</td>
                                    <td class="px-4 py-2 {{ ($item->product?->stock ?? 0) < $item->quantite ? 'text-red-600 font-semibold' : 'text-gray-600' }}">
                                        {{ $item->product?->stock ?? 0 }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                    <div class="flex justify-end">
                        <form method="POST" action="{{ route('goods-issue-notes.store') }}">
                            @csrf
                            <input type="hidden" name="order_id" value="{{ $order->id }}">
                            <button type="submit" class="btn-primary"
                                    onclick="return confirm('Confirmer le bon de sortie ? Le stock sera decrement.')">
                                Creer le Bon de Sortie
                            </button>
                        </form>
                    </div>
                </div>
            @endforeach
        @endif
    </div>
</x-app-layout>
