<x-app-layout title="Nouvelle publication">
    <x-slot name="header">
        <h1 class="text-2xl font-bold text-gray-900">Nouvelle publication</h1>
    </x-slot>

    <div class="max-w-2xl mx-auto space-y-6">
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-bold text-gray-900">Nouvelle publication</h1>
            <a href="{{ route('announcements.index') }}" class="btn-secondary">Retour</a>
        </div>

        <form method="POST" action="{{ route('announcements.store') }}" class="card space-y-5">
            @csrf

            <div>
                <label class="form-label" for="type">Type</label>
                <select id="type" name="type" class="form-input" required>
                    @foreach ($types as $type)
                        <option value="{{ $type->value }}" @selected(old('type') === $type->value)>{{ $type->label() }}</option>
                    @endforeach
                </select>
                @error('type') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="form-label" for="title">Titre</label>
                <input id="title" type="text" name="title" value="{{ old('title') }}" class="form-input" required placeholder="Ex : Lancement de notre nouvelle crème hydratante">
                @error('title') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="form-label" for="content">Contenu</label>
                <textarea id="content" name="content" rows="5" class="form-input" required placeholder="Décrivez la nouveauté ou l'événement...">{{ old('content') }}</textarea>
                @error('content') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="form-label" for="image_url">Image (URL, optionnel)</label>
                <input id="image_url" type="url" name="image_url" value="{{ old('image_url') }}" class="form-input" placeholder="https://...">
                @error('image_url') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="flex items-center gap-3">
                <input type="checkbox" id="is_published" name="is_published" value="1" class="rounded border-gray-300" @checked(old('is_published', true))>
                <label for="is_published" class="text-sm text-gray-700">Publier immédiatement (visible par les clients)</label>
            </div>

            <div class="flex justify-end gap-3">
                <a href="{{ route('announcements.index') }}" class="btn-secondary">Annuler</a>
                <button type="submit" class="btn-primary">Publier</button>
            </div>
        </form>
    </div>
</x-app-layout>
