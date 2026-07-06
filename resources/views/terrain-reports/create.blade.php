<x-app-layout title="Rapport journalier">
    <x-slot name="header">
        <h1 class="text-2xl font-bold text-gray-900">Rapport journalier</h1>
    </x-slot>

@php
    $productsJson = $products->map(fn ($p) => ['id' => $p->id, 'name' => $p->name, 'price' => (float) $p->price])->values();
@endphp

    <div class="max-w-3xl mx-auto space-y-6" x-data="terrainReportForm({{ $productsJson->toJson() }})">
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-bold text-gray-900">Rapport journalier</h1>
            <a href="{{ route('terrain-reports.index') }}" class="btn-secondary">Retour</a>
        </div>

        <div class="card">
            <p class="text-sm text-gray-600">
                Magasin : <span class="font-semibold text-gray-900">{{ $client?->name ?? '—' }}</span>
            </p>
            <p class="text-xs text-gray-400 mt-1">Date : {{ now()->format('d/m/Y') }} — un seul rapport est autorisé par jour.</p>
        </div>

        <form method="POST" action="{{ route('terrain-reports.store') }}" class="space-y-6">
            @csrf

            <div class="card p-0 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                    <h2 class="text-base font-semibold text-gray-900">Produits vendus</h2>
                    <button type="button" @click="addLine" class="btn-secondary !py-1.5 !px-3 text-xs">+ Ajouter une ligne</button>
                </div>

                <div class="divide-y divide-gray-100">
                    <template x-for="(line, index) in lines" :key="index">
                        <div class="grid grid-cols-12 gap-3 px-6 py-4 items-end">
                            <div class="col-span-5">
                                <label class="form-label">Produit</label>
                                <select class="form-input" :name="`items[${index}][product_id]`" x-model.number="line.product_id" @change="onProductChange(index)" required>
                                    <option value="">Sélectionner</option>
                                    <template x-for="p in products" :key="p.id">
                                        <option :value="p.id" x-text="p.name"></option>
                                    </template>
                                </select>
                            </div>
                            <div class="col-span-3">
                                <label class="form-label">Prix unitaire (officiel)</label>
                                <p class="form-input bg-gray-50 text-gray-500" x-text="line.product_id ? '$' + priceOf(line.product_id).toFixed(2) : '—'"></p>
                            </div>
                            <div class="col-span-3">
                                <label class="form-label">Quantité</label>
                                <input type="number" min="1" class="form-input" :name="`items[${index}][quantity]`" x-model.number="line.quantity" required>
                            </div>
                            <div class="col-span-1 text-right">
                                <button type="button" @click="removeLine(index)" class="text-red-500 hover:text-red-700" x-show="lines.length > 1">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                                </button>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <div class="card flex items-center justify-between">
                <span class="text-sm text-gray-500">Total du rapport</span>
                <span class="text-xl font-bold text-gray-900" x-text="'$' + total().toFixed(2)"></span>
            </div>

            @error('items') <p class="text-xs text-red-600">{{ $message }}</p> @enderror

            <div class="flex justify-end gap-3">
                <a href="{{ route('terrain-reports.index') }}" class="btn-secondary">Annuler</a>
                <button type="submit" class="btn-primary">Soumettre le rapport</button>
            </div>
        </form>
    </div>

    <script>
        function terrainReportForm(products) {
            return {
                products,
                lines: [{ product_id: '', quantity: 1 }],
                addLine() { this.lines.push({ product_id: '', quantity: 1 }); },
                removeLine(i) { this.lines.splice(i, 1); },
                priceOf(id) { const p = this.products.find(p => p.id === id); return p ? p.price : 0; },
                lineTotal(line) { return this.priceOf(line.product_id) * (line.quantity || 0); },
                total() { return this.lines.reduce((s, l) => s + this.lineTotal(l), 0); },
                onProductChange(index) {
                    const line = this.lines[index];
                    if (!line.product_id) return;
                    const existing = this.lines.findIndex((l, i) => i !== index && l.product_id === line.product_id);
                    if (existing !== -1) {
                        this.lines[existing].quantity += (line.quantity || 1);
                        this.lines.splice(index, 1);
                    }
                },
            };
        }
    </script>
</x-app-layout>
