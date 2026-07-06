<x-app-layout title="Rapports journaliers">
    <x-slot name="header">
        <h1 class="text-2xl font-bold text-gray-900">Rapports journaliers</h1>
    </x-slot>

    <div class="max-w-5xl mx-auto space-y-6">
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-bold text-gray-900">Rapports journaliers</h1>
            @if (auth()->user()->isMarketeurTerrain())
                <a href="{{ route('terrain-reports.create') }}" class="btn-primary">+ Nouveau rapport</a>
            @endif
        </div>

        <div class="card p-0 overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Date</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Agent</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Produits</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase">CA</th>
                        <th class="px-6 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($reports as $report)
                        <tr>
                            <td class="px-6 py-4 text-sm text-gray-900">{{ $report->date->format('d/m/Y') }}</td>
                            <td class="px-6 py-4 text-sm text-gray-700">{{ $report->user?->name }}</td>
                            <td class="px-6 py-4 text-sm text-gray-700">{{ $report->items->count() }}</td>
                            <td class="px-6 py-4 text-sm font-medium text-gray-900 text-right">${{ number_format($report->montant_total, 2) }}</td>
                            <td class="px-6 py-4 text-right">
                                <a href="{{ route('terrain-reports.show', $report) }}" class="text-[#6366F1] hover:underline text-sm">Voir</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-10 text-center text-sm text-gray-400">Aucun rapport pour le moment.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $reports->links() }}
    </div>
</x-app-layout>
