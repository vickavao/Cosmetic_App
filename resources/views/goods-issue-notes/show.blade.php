<x-app-layout title="Bon de Sortie {{ $note->reference }}">
    <x-slot name="header">
        <h1 class="text-2xl font-bold text-gray-900">Bon de Sortie</h1>
    </x-slot>

    <div class="max-w-4xl mx-auto space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">{{ $note->reference }}</h1>
                <p class="text-sm text-gray-500">{{ $note->date?->format('d/m/Y') }}</p>
            </div>
            <a href="{{ route('orders.show', $note->order_id) }}" class="btn-secondary">Voir la commande</a>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
            <div class="card">
                <p class="text-xs uppercase text-gray-400 font-medium">Commande</p>
                <p class="mt-1 font-semibold text-gray-900">{{ $note->order?->reference ?? '---' }}</p>
            </div>
            <div class="card">
                <p class="text-xs uppercase text-gray-400 font-medium">Client</p>
                <p class="mt-1 font-semibold text-gray-900">{{ $note->order?->client?->name ?? '---' }}</p>
            </div>
            <div class="card">
                <p class="text-xs uppercase text-gray-400 font-medium">Emis par</p>
                <p class="mt-1 font-semibold text-gray-900">{{ $note->issuer?->name ?? '---' }}</p>
            </div>
        </div>

        @if ($note->motif)
            <div class="card">
                <p class="text-xs uppercase text-gray-400 font-medium mb-2">Motif</p>
                <p class="text-sm text-gray-700">{{ $note->motif }}</p>
            </div>
        @endif

        <div class="card p-0 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-base font-semibold text-gray-900">Articles sortis</h2>
            </div>
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                    <tr>
                        <th class="px-6 py-3">Produit</th>
                        <th class="px-6 py-3">Quantite</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach ($note->lines as $line)
                        <tr>
                            <td class="px-6 py-4 font-medium text-gray-900">{{ $line->product?->name }}</td>
                            <td class="px-6 py-4 text-gray-600">{{ $line->quantite }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
