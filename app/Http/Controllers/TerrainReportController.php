<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Models\Product;
use App\Models\TerrainReport;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class TerrainReportController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $reports = TerrainReport::query()
            ->when($user->role === Role::MarketeurTerrain, fn ($q) => $q->where('user_id', $user->id))
            ->when($user->role === Role::AgentMarketeur, fn ($q) => $q->where('supervisor_id', $user->id))
            ->with(['user', 'supervisor', 'items.product'])
            ->latest('date')
            ->paginate(20);

        return view('terrain-reports.index', ['reports' => $reports]);
    }

    public function create(Request $request): View
    {
        $user = $request->user();

        if (! $user->isMarketeurTerrain()) {
            abort(403, 'Seuls les Marketeurs Terrain peuvent créer des rapports.');
        }

        // Verrou : vérifier si un rapport a déjà été soumis aujourd'hui
        $todayReport = TerrainReport::query()
            ->where('user_id', $user->id)
            ->whereDate('date', today())
            ->first();

        if ($todayReport) {
            abort(403, 'Vous avez déjà soumis un rapport aujourd\'hui.');
        }

        // Auto-injection du client_id : l'Agent Terrain est lié à un client spécifique
        if (! $user->client_id) {
            abort(403, 'Votre compte n\'est pas associé à un magasin client. Contactez votre Agent Marketeur.');
        }

        return view('terrain-reports.create', [
            'products' => Product::query()->where('is_active', true)->orderBy('name')->get(),
            'client' => $user->assignedClient,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        if (! $user->isMarketeurTerrain()) {
            abort(403, 'Seuls les Marketeurs Terrain peuvent créer des rapports.');
        }

        // Verrou : vérifier si un rapport a déjà été soumis aujourd'hui
        $todayReport = TerrainReport::query()
            ->where('user_id', $user->id)
            ->whereDate('date', today())
            ->first();

        if ($todayReport) {
            return back()->with('warning', 'Vous avez déjà soumis un rapport aujourd\'hui.');
        }

        if (! $user->client_id) {
            return back()->with('error', 'Votre compte n\'est pas associé à un magasin client.');
        }

        $data = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
        ]);

        $report = DB::transaction(function () use ($data, $user): TerrainReport {
            $report = TerrainReport::create([
                'user_id' => $user->id,
                'supervisor_id' => $user->supervisor_id,
                'date' => today(),
            ]);

            // Anti-doublon : fusionner les quantités si même produit
            $merged = [];
            foreach ($data['items'] as $line) {
                $pid = $line['product_id'];
                if (isset($merged[$pid])) {
                    $merged[$pid]['quantity'] += $line['quantity'];
                } else {
                    $merged[$pid] = $line;
                }
            }

            // Calculer le CA automatiquement avec les prix officiels du Chef Marketing
            foreach ($merged as $line) {
                $product = Product::findOrFail($line['product_id']);
                $sousTotal = $product->price * $line['quantity'];

                $report->items()->create([
                    'client_id' => $user->client_id, // Magasin lié au profil N3
                    'product_id' => $product->id,
                    'quantite' => $line['quantity'],
                    'prix_unitaire' => $product->price, // Prix officiel (non modifiable)
                    'sous_total' => $sousTotal,
                ]);
            }

            return $report;
        });

        return redirect()
            ->route('terrain-reports.show', $report)
            ->with('status', 'Rapport journalier soumis avec succès.');
    }

    public function show(TerrainReport $report): View
    {
        Gate::authorize('view', $report);

        $report->load(['user', 'supervisor', 'items.product']);

        return view('terrain-reports.show', ['report' => $report]);
    }
}
