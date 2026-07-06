<x-app-layout title="Nouveautés & Événements">
    <x-slot name="header">
        <h1 class="text-2xl font-bold text-gray-900">Nouveautés & Événements</h1>
    </x-slot>

    <div class="max-w-3xl mx-auto space-y-6">
        <p class="text-sm text-gray-500">Les dernières actualités de notre équipe marketing.</p>

        @forelse ($announcements as $announcement)
            <article class="card overflow-hidden">
                @if ($announcement->image_url)
                    <img src="{{ $announcement->image_url }}" alt="{{ $announcement->title }}" class="w-full h-48 object-cover -mx-6 -mt-6 mb-4" style="width: calc(100% + 3rem);">
                @endif
                <div class="flex items-center gap-2 mb-2">
                    <span class="badge {{ $announcement->type === \App\Enums\AnnouncementType::Evenement ? 'badge-indigo' : 'badge-green' }}">{{ $announcement->type->label() }}</span>
                    <span class="text-xs text-gray-400">{{ $announcement->published_at?->format('d/m/Y') }}</span>
                </div>
                <h2 class="text-lg font-semibold text-gray-900">{{ $announcement->title }}</h2>
                <p class="mt-2 text-sm text-gray-600 whitespace-pre-line">{{ $announcement->content }}</p>
                @if ($announcement->author)
                    <p class="mt-3 text-xs text-gray-400">Publié par {{ $announcement->author->name }}</p>
                @endif
            </article>
        @empty
            <div class="card text-center text-gray-400 py-12">Aucune actualité pour le moment.</div>
        @endforelse

        <div>{{ $announcements->links() }}</div>
    </div>
</x-app-layout>
