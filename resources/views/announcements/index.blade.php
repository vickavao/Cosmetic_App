<x-app-layout title="Actualités">
    <x-slot name="header">
        <h1 class="text-2xl font-bold text-gray-900">Actualités & Événements</h1>
    </x-slot>

    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <p class="text-sm text-gray-500">Publications visibles par vos clients dans leur espace.</p>
            <a href="{{ route('announcements.create') }}" class="btn-primary">+ Nouvelle publication</a>
        </div>

        <div class="card p-0 overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                    <tr>
                        <th class="px-6 py-3">Titre</th>
                        <th class="px-6 py-3">Type</th>
                        <th class="px-6 py-3">Statut</th>
                        <th class="px-6 py-3">Publié le</th>
                        <th class="px-6 py-3 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($announcements as $announcement)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 font-medium text-gray-900">{{ $announcement->title }}</td>
                            <td class="px-6 py-4">
                                <span class="badge {{ $announcement->type === \App\Enums\AnnouncementType::Evenement ? 'badge-indigo' : 'badge-green' }}">{{ $announcement->type->label() }}</span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="badge {{ $announcement->is_published ? 'badge-green' : 'badge-gray' }}">{{ $announcement->is_published ? 'Publié' : 'Brouillon' }}</span>
                            </td>
                            <td class="px-6 py-4 text-gray-500">{{ $announcement->published_at?->format('d/m/Y') ?? '—' }}</td>
                            <td class="px-6 py-4 text-right">
                                <form method="POST" action="{{ route('announcements.destroy', $announcement) }}"
                                      onsubmit="return confirm('Supprimer cette publication ?');">
                                    @csrf @method('DELETE')
                                    <button class="text-sm font-medium text-red-600 hover:underline">Supprimer</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-6 py-10 text-center text-gray-400">Aucune publication.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div>{{ $announcements->links() }}</div>
    </div>
</x-app-layout>
