<x-app-layout title="Mes factures">
    <x-slot name="header">
        <h1 class="text-2xl font-bold text-gray-900">Mes factures</h1>
    </x-slot>

    <div class="max-w-5xl mx-auto space-y-6">
        <p class="text-sm text-gray-500">Suivez ici vos factures et leurs échéances.</p>

        <div class="card p-0 overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                    <tr>
                        <th class="px-6 py-3">Référence</th>
                        <th class="px-6 py-3">Type</th>
                        <th class="px-6 py-3">Montant</th>
                        <th class="px-6 py-3">Reste à payer</th>
                        <th class="px-6 py-3">Statut</th>
                        <th class="px-6 py-3">Date</th>
                        <th class="px-6 py-3">Échéance</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($invoices as $invoice)
                        @php
                            $daysUntilDue = null;
                            $alertClass = '';
                            $alertLabel = '';
                            if ($invoice->date_echeance && $invoice->resteAPayer() > 0) {
                                $daysUntilDue = (int) now()->startOfDay()->diffInDays($invoice->date_echeance->startOfDay(), false);
                                if ($daysUntilDue < 0) {
                                    $alertClass = 'bg-red-100 text-red-800';
                                    $alertLabel = 'En retard';
                                } elseif ($daysUntilDue === 0) {
                                    $alertClass = 'bg-red-100 text-red-800';
                                    $alertLabel = "Aujourd'hui";
                                } elseif ($daysUntilDue === 1) {
                                    $alertClass = 'bg-red-100 text-red-700';
                                    $alertLabel = 'J-1';
                                } elseif ($daysUntilDue === 2) {
                                    $alertClass = 'bg-orange-100 text-orange-700';
                                    $alertLabel = 'J-2';
                                } elseif ($daysUntilDue === 3) {
                                    $alertClass = 'bg-yellow-100 text-yellow-700';
                                    $alertLabel = 'J-3';
                                }
                            }
                        @endphp
                        <tr class="{{ $alertClass ? 'border-l-4 ' . ($daysUntilDue !== null && $daysUntilDue < 0 ? 'border-l-red-500' : ($daysUntilDue !== null && $daysUntilDue <= 1 ? 'border-l-red-400' : ($daysUntilDue !== null && $daysUntilDue === 2 ? 'border-l-orange-400' : 'border-l-yellow-400'))) : '' }}">
                            <td class="px-6 py-4 font-medium text-gray-900">{{ $invoice->reference }}</td>
                            <td class="px-6 py-4">
                                <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium {{ $invoice->type_vente === \App\Enums\SaleType::Credit ? 'bg-blue-100 text-blue-700' : 'bg-green-100 text-green-700' }}">
                                    {{ $invoice->type_vente->label() }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-gray-700">@money((float) $invoice->montant)</td>
                            <td class="px-6 py-4 font-medium {{ $invoice->resteAPayer() > 0 ? 'text-red-600' : 'text-green-600' }}">
                                @money($invoice->resteAPayer())
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-700">{{ $invoice->statut->label() }}</span>
                            </td>
                            <td class="px-6 py-4 text-gray-500">{{ $invoice->date?->format('d/m/Y') }}</td>
                            <td class="px-6 py-4">
                                @if ($invoice->date_echeance)
                                    <span class="text-gray-700">{{ $invoice->date_echeance->format('d/m/Y') }}</span>
                                    @if ($alertLabel)
                                        <span class="ml-1 inline-flex rounded-full px-2 py-0.5 text-xs font-bold {{ $alertClass }}">{{ $alertLabel }}</span>
                                    @endif
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-6 py-10 text-center text-gray-400">Aucune facture pour le moment.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $invoices->links() }}
    </div>
</x-app-layout>
