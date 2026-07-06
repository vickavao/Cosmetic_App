<x-app-layout title="Rapport journalier">
    <x-slot name="header">
        <h1 class="text-2xl font-bold text-gray-900">Rapport du {{ $report->date->format('d/m/Y') }}</h1>
    </x-slot>

    <div class="max-w-3xl mx-auto space-y-6">
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-bold text-gray-900">Rapport du {{ $report->date->format('d/m/Y') }}</h1>
            <a href="{{ route('terrain-reports.index') }}" class="btn-secondary">Retour</a>
        </div>

        <div class="card grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <p class="text-xs text-gray-400 uppercase">Agent Terrain</p>
                <p class="text-sm font-medium text-gray-900">{{ $report->user?->name }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-400 uppercase">Superviseur</p>
                <p class="text-sm font-medium text-gray-900">{{ $report->supervisor?->name ?? '—' }}</p>
            </div>
        </div>

        <div class="card p-0 overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Produit</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Quantité</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Prix unitaire</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Sous-total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach ($report->items as $item)
                        <tr>
                            <td class="px-6 py-4 text-sm text-gray-900">{{ $item->product?->name }}</td>
                            <td class="px-6 py-4 text-sm text-gray-700 text-right">{{ $item->quantite }}</td>
                            <td class="px-6 py-4 text-sm text-gray-700 text-right">${{ number_format($item->prix_unitaire, 2) }}</td>
                            <td class="px-6 py-4 text-sm font-medium text-gray-900 text-right">${{ number_format($item->sous_total, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="bg-gray-50">
                    <tr>
                        <td colspan="3" class="px-6 py-4 text-sm font-semibold text-gray-700 text-right">Chiffre d'affaires</td>
                        <td class="px-6 py-4 text-base font-bold text-gray-900 text-right">${{ number_format($report->montant_total, 2) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</x-app-layout>
