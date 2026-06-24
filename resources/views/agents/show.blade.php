<x-app-layout title="Membre de l'équipe">
    <x-slot name="header">
        <h1 class="text-2xl font-bold text-gray-900">{{ $agent->name }}</h1>
    </x-slot>

    <div class="max-w-3xl mx-auto space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">{{ $agent->role?->label() }} · {{ $agent->email }}</p>
                @if ($agent->magasin)<p class="text-sm text-gray-500">Magasin : {{ $agent->magasin }}</p>@endif
            </div>
            <a href="{{ route('terrain.team') }}" class="btn-secondary">Retour</a>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="card"><p class="text-xs uppercase text-gray-400 font-medium">Statut</p><p class="mt-1">@if ($agent->is_active)<span class="badge badge-green">Actif</span>@else<span class="badge badge-orange">Désactivé</span>@endif</p></div>
            <div class="card"><p class="text-xs uppercase text-gray-400 font-medium">Commandes</p><p class="mt-1 text-xl font-bold text-gray-900">{{ $agent->orders_count }}</p></div>
            <div class="card"><p class="text-xs uppercase text-gray-400 font-medium">Clients gérés</p><p class="mt-1 text-xl font-bold text-gray-900">{{ $agent->managed_clients_count }}</p></div>
        </div>

        @if ($agent->role === \App\Enums\Role::MarketeurTerrain)
            <div class="card {{ blank($agent->magasin) ? 'border-orange-300 bg-orange-50/40' : '' }}">
                @if (blank($agent->magasin))
                    <div class="flex items-center gap-2 mb-3">
                        <span class="badge badge-orange">Aucun magasin assigné</span>
                        <span class="text-xs text-gray-500">Ce compte reste désactivé tant qu'aucun magasin n'est associé.</span>
                    </div>
                @endif
                <form method="POST" action="{{ route('agents.assign-magasin', $agent) }}" class="flex flex-col sm:flex-row sm:items-end gap-3">
                    @csrf @method('PATCH')
                    <div class="flex-1">
                        <label class="block text-xs font-medium text-gray-500 mb-1" for="magasin">Magasin associé</label>
                        <input type="text" id="magasin" name="magasin" value="{{ old('magasin', $agent->magasin) }}"
                               class="form-input w-full" placeholder="Nom du magasin" required>
                        @error('magasin')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                    @if (blank($agent->magasin))
                        <label class="inline-flex items-center gap-2 text-sm text-gray-600 pb-2">
                            <input type="checkbox" name="activate" value="1" checked class="rounded border-gray-300">
                            Activer le compte
                        </label>
                    @endif
                    <button type="submit" class="btn-primary">{{ blank($agent->magasin) ? 'Associer le magasin' : 'Mettre à jour' }}</button>
                </form>
            </div>
        @endif

        <form method="POST" action="{{ route('agents.toggle-active', $agent) }}" class="card flex items-center justify-between">
            @csrf @method('PATCH')
            <span class="text-sm text-gray-600">{{ $agent->is_active ? 'Mettre en congé (désactiver le compte).' : 'Réactiver le compte.' }}</span>
            <button type="submit" class="{{ $agent->is_active ? 'btn-secondary' : 'btn-primary' }}">{{ $agent->is_active ? 'Désactiver' : 'Activer' }}</button>
        </form>

        <div class="card p-0 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200"><h2 class="text-base font-semibold text-gray-900">Dernières commandes</h2></div>
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                    <tr><th class="px-6 py-3">Référence</th><th class="px-6 py-3">Client</th><th class="px-6 py-3">Date</th><th class="px-6 py-3 text-right">Total</th></tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($orders as $order)
                        <tr>
                            <td class="px-6 py-3 font-medium text-gray-900">{{ $order->reference }}</td>
                            <td class="px-6 py-3 text-gray-700">{{ $order->client?->name ?? '—' }}</td>
                            <td class="px-6 py-3 text-gray-500">{{ $order->date_commande?->format('d/m/Y') }}</td>
                            <td class="px-6 py-3 text-right text-gray-900">@money((float) $order->total)</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-6 py-8 text-center text-gray-400">Aucune commande.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
