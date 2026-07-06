<x-app-layout title="Factures">
    <x-slot name="header">
        <h1 class="text-2xl font-bold text-gray-900">Factures</h1>
    </x-slot>

    <div class="space-y-6">
        @unless ($isAgent)
            <div class="card flex flex-wrap items-end justify-between gap-3">
                <div>
                    <h2 class="text-base font-semibold text-gray-900">Rapport des ventes journalier</h2>
                    <p class="text-sm text-gray-500">Cumule automatiquement les factures de tous les agents pour la journée choisie.</p>
                </div>
                <form method="GET" action="{{ route('invoices.daily-report') }}" class="flex items-end gap-2">
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Date</label>
                        <input type="date" name="date" value="{{ now()->toDateString() }}" class="form-input">
                    </div>
                    <button type="submit" class="btn-primary">Exporter le PDF</button>
                </form>
            </div>
        @endunless

        <form method="GET" action="{{ route('invoices.index') }}" class="card flex flex-wrap items-end gap-3">
            @unless ($isAgent)
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Agent</label>
                    <select name="agent_id" class="form-input" onchange="this.form.submit()">
                        <option value="">Tous</option>
                        @foreach ($agents as $a)
                            <option value="{{ $a->id }}" @selected((string) request('agent_id') === (string) $a->id)>{{ $a->name }}</option>
                        @endforeach
                    </select>
                </div>
            @endunless
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Type de vente</label>
                <select name="type_vente" class="form-input" onchange="this.form.submit()">
                    <option value="">Tous</option>
                    @foreach ($typesVente as $t)
                        <option value="{{ $t->value }}" @selected(request('type_vente') === $t->value)>{{ $t->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Du</label>
                <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-input">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Au</label>
                <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-input">
            </div>
            <button type="submit" class="btn-primary">Filtrer</button>
            <a href="{{ route('invoices.index') }}" class="btn-secondary">Réinitialiser</a>
        </form>

        <div class="card p-0 overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                    <tr>
                        <th class="px-6 py-3">Référence</th>
                        @unless ($isAgent)<th class="px-6 py-3">Agent</th>@endunless
                        <th class="px-6 py-3">Client</th>
                        <th class="px-6 py-3">Type</th>
                        <th class="px-6 py-3">Montant</th>
                        <th class="px-6 py-3">Statut</th>
                        <th class="px-6 py-3 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($invoices as $invoice)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 font-medium text-gray-900">{{ $invoice->reference }}</td>
                            @unless ($isAgent)<td class="px-6 py-4 text-gray-700">{{ $invoice->agent?->name ?? '—' }}</td>@endunless
                            <td class="px-6 py-4 text-gray-700">{{ $invoice->client?->name ?? '—' }}</td>
                            <td class="px-6 py-4"><span class="badge {{ $invoice->type_vente === \App\Enums\SaleType::Credit ? 'badge-orange' : 'badge-green' }}">{{ $invoice->type_vente->label() }}</span></td>
                            <td class="px-6 py-4 text-gray-900">@money((float) $invoice->montant)</td>
                            <td class="px-6 py-4">
                                @php($st = $invoice->statut)
                                <span class="badge {{ $st === \App\Enums\InvoiceStatus::Payee ? 'badge-green' : ($st === \App\Enums\InvoiceStatus::Annulee ? 'badge-red' : ($st === \App\Enums\InvoiceStatus::Partielle ? 'badge-orange' : 'badge-gray')) }}">{{ $st->label() }}</span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <a href="{{ route('invoices.show', $invoice) }}" class="text-sm font-medium text-[#6366F1] hover:underline">Voir</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="{{ $isAgent ? 6 : 7 }}" class="px-6 py-10 text-center text-gray-400">Aucune facture.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div>{{ $invoices->links() }}</div>
    </div>
</x-app-layout>
