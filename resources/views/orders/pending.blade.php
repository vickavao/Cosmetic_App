<x-app-layout title="Commandes a valider">
    <x-slot name="header">
        <h1 class="text-2xl font-bold text-gray-900">Commandes a valider</h1>
    </x-slot>

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
        {{-- Filters --}}
        <form method="GET" action="{{ route('orders.pending') }}" class="card p-4">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                <div>
                    <label class="form-label">Client</label>
                    <select name="client_id" class="form-input">
                        <option value="">Tous les clients</option>
                        @foreach ($clients as $client)
                            <option value="{{ $client->id }}" @selected(request('client_id') == $client->id)>{{ $client->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="form-label">Agent N2</label>
                    <select name="agent_id" class="form-input">
                        <option value="">Tous les agents</option>
                        @foreach ($agents as $agent)
                            <option value="{{ $agent->id }}" @selected(request('agent_id') == $agent->id)>{{ $agent->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="form-label">Du</label>
                    <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-input">
                </div>
                <div class="flex gap-2">
                    <div class="flex-1">
                        <label class="form-label">Au</label>
                        <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-input">
                    </div>
                    <div class="flex items-end">
                        <button type="submit" class="btn-primary">Filtrer</button>
                    </div>
                </div>
            </div>
        </form>

        <p class="text-sm text-gray-500">{{ $orders->total() }} commande(s) en attente de validation.</p>

        <div class="card p-0 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                        <tr>
                            <th class="px-6 py-3">Reference</th>
                            <th class="px-6 py-3">Client</th>
                            <th class="px-6 py-3">Agent N2</th>
                            <th class="px-6 py-3">Type</th>
                            <th class="px-6 py-3">Montant</th>
                            <th class="px-6 py-3">Date</th>
                            <th class="px-6 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($orders as $order)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 font-medium text-gray-900">
                                    <a href="{{ route('orders.show', $order) }}" class="text-[#6366F1] hover:underline">{{ $order->reference }}</a>
                                </td>
                                <td class="px-6 py-4 text-gray-600">{{ $order->client?->name }}</td>
                                <td class="px-6 py-4 text-gray-600">{{ $order->user?->name }}</td>
                                <td class="px-6 py-4">
                                    <span class="badge {{ $order->type_vente?->value === 'credit' ? 'badge-orange' : 'badge-green' }}">
                                        {{ $order->type_vente?->value === 'credit' ? 'Credit' : 'Comptant' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 font-medium text-gray-900">@money((float) $order->total)</td>
                                <td class="px-6 py-4 text-gray-500">{{ $order->date_commande?->format('d/m/Y') }}</td>
                                <td class="px-6 py-4 text-right space-x-2">
                                    <form method="POST" action="{{ route('orders.validate', $order) }}" class="inline">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-semibold text-white bg-green-600 rounded-lg hover:bg-green-700 transition">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                                            Valider
                                        </button>
                                    </form>
                                    <a href="{{ route('orders.show', $order) }}" class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-semibold text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition">
                                        Details
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="px-6 py-10 text-center text-gray-400">Aucune commande en attente.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div>{{ $orders->appends(request()->query())->links() }}</div>
    </div>
</x-app-layout>
