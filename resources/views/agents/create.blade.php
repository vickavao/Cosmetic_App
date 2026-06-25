@php $roleLabel = $subordinateRole->label(); @endphp
<x-app-layout :title="'Créer un '.$roleLabel">
    <x-slot name="header">
        <h1 class="text-2xl font-bold text-gray-900">Créer un {{ $roleLabel }}</h1>
    </x-slot>

    <div class="max-w-3xl mx-auto space-y-6">
        <div class="flex items-center justify-between">
            <p class="text-sm text-gray-500">{{ $roleLabel }} rattaché à votre équipe.</p>
            <a href="{{ route('terrain.team') }}" class="btn-secondary">Retour</a>
        </div>

        <form method="POST" action="{{ route('agents.store') }}" class="space-y-6">
            @csrf

            <div class="card grid grid-cols-1 sm:grid-cols-2 gap-5">
                <div class="sm:col-span-2">
                    <label class="form-label" for="name">Nom complet</label>
                    <input id="name" type="text" name="name" value="{{ old('name') }}" class="form-input" required autofocus>
                </div>
                <div>
                    <label class="form-label" for="email">Email</label>
                    <input id="email" type="email" name="email" value="{{ old('email') }}" class="form-input" required>
                </div>
                <div>
                    <label class="form-label" for="phone">Téléphone</label>
                    <input id="phone" type="text" name="phone" value="{{ old('phone') }}" class="form-input">
                </div>
                @if ($subordinateRole === \App\Enums\Role::MarketeurTerrain)
                    <div class="sm:col-span-2">
                        @if ($clients->isEmpty())
                            <div class="rounded-lg border border-orange-300 bg-orange-50 px-4 py-3">
                                <p class="text-sm font-medium text-orange-800">Impossible de créer un Marketeur Terrain</p>
                                <p class="mt-1 text-sm text-orange-700">Créez d'abord un magasin client avant de pouvoir affecter un agent terrain.</p>
                                <a href="{{ route('clients.create') }}" class="mt-2 inline-block text-sm font-medium text-orange-800 underline">Créer un client</a>
                            </div>
                        @else
                            <label class="form-label" for="client_id">Magasin client d'affectation <span class="text-red-500">*</span></label>
                            <select id="client_id" name="client_id" class="form-input" required>
                                <option value="">Sélectionner un magasin client</option>
                                @foreach ($clients as $client)
                                    <option value="{{ $client->id }}" @selected(old('client_id') == $client->id)>{{ $client->name }}{{ $client->ville ? ' — '.$client->ville : '' }}</option>
                                @endforeach
                            </select>
                            <p class="mt-1 text-xs text-gray-500">Obligatoire : un Marketeur Terrain doit être affecté au magasin d'un client.</p>
                            @error('client_id')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                        @endif
                    </div>
                @endif
                <div>
                    <label class="form-label" for="password">Mot de passe</label>
                    <input id="password" type="password" name="password" class="form-input" required autocomplete="new-password">
                </div>
                <div>
                    <label class="form-label" for="password_confirmation">Confirmer le mot de passe</label>
                    <input id="password_confirmation" type="password" name="password_confirmation" class="form-input" required autocomplete="new-password">
                </div>
                <div class="sm:col-span-2 flex items-center gap-3 rounded-lg border border-gray-200 px-4 py-3">
                    <input id="is_active" type="checkbox" name="is_active" value="1" checked
                           class="h-4 w-4 rounded border-gray-300 text-[#6366F1] focus:ring-[#6366F1]">
                    <label for="is_active" class="text-sm text-gray-700">Compte actif (peut se connecter)</label>
                </div>
            </div>

            <div class="flex justify-end gap-3">
                <a href="{{ route('terrain.team') }}" class="btn-secondary">Annuler</a>
                <button type="submit" class="btn-primary"
                    @if ($subordinateRole === \App\Enums\Role::MarketeurTerrain && $clients->isEmpty()) disabled @endif>
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" y1="8" x2="19" y2="14"/><line x1="22" y1="11" x2="16" y2="11"/></svg>
                    Créer le compte
                </button>
            </div>
        </form>
    </div>
</x-app-layout>
