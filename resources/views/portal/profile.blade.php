<x-app-layout title="Mon Profil">
    <x-slot name="header">
        <h1 class="text-2xl font-bold text-gray-900">Mon Profil</h1>
    </x-slot>

    <div class="max-w-2xl mx-auto space-y-6">
        <p class="text-sm text-gray-500">Mettez à jour vos informations personnelles.</p>

        <form method="POST" action="{{ route('portal.profile.update') }}" class="card space-y-5">
            @csrf
            @method('PATCH')

            <div>
                <label class="form-label" for="name">Nom / Raison sociale</label>
                <input id="name" type="text" name="name" value="{{ old('name', $client->name) }}" class="form-input" required>
                @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <div>
                    <label class="form-label" for="email">Email</label>
                    <input id="email" type="email" name="email" value="{{ old('email', $client->email) }}" class="form-input">
                    @error('email') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="form-label" for="phone">Téléphone</label>
                    <input id="phone" type="text" name="phone" value="{{ old('phone', $client->phone) }}" class="form-input">
                    @error('phone') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <div>
                <label class="form-label" for="address">Adresse</label>
                <input id="address" type="text" name="address" value="{{ old('address', $client->address) }}" class="form-input">
                @error('address') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="form-label" for="ville">Ville</label>
                <input id="ville" type="text" name="ville" value="{{ old('ville', $client->ville) }}" class="form-input">
                @error('ville') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="flex justify-end">
                <button type="submit" class="btn-primary">Enregistrer</button>
            </div>
        </form>
    </div>
</x-app-layout>
