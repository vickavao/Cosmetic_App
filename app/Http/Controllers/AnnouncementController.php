<?php

namespace App\Http\Controllers;

use App\Enums\AnnouncementType;
use App\Models\Announcement;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AnnouncementController extends Controller
{
    public function index(): View
    {
        $announcements = Announcement::query()
            ->with('author')
            ->latest()
            ->paginate(15);

        return view('announcements.index', ['announcements' => $announcements]);
    }

    public function create(): View
    {
        return view('announcements.create', ['types' => AnnouncementType::cases()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateData($request);

        Announcement::create([
            ...$data,
            'created_by' => $request->user()->id,
            'published_at' => ($data['is_published'] ?? false) ? now() : null,
        ]);

        return redirect()
            ->route('announcements.index')
            ->with('status', 'Publication créée.');
    }

    public function destroy(Announcement $announcement): RedirectResponse
    {
        $announcement->delete();

        return redirect()
            ->route('announcements.index')
            ->with('status', 'Publication supprimée.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validateData(Request $request): array
    {
        return $request->validate([
            'type' => ['required', Rule::enum(AnnouncementType::class)],
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'image_url' => ['nullable', 'url', 'max:2048'],
            'is_published' => ['nullable', 'boolean'],
        ]);
    }
}
