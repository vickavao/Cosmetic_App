<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Enums\SaleType;
use App\Models\Invoice;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class InvoiceController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Invoice::class);

        $user = $request->user();

        $isAgent = $user->role === Role::AgentMarketeur;

        $invoices = Invoice::query()
            ->when($isAgent, fn ($q) => $q->where('agent_id', $user->id))
            ->when(! $isAgent && $request->filled('agent_id'), fn ($q) => $q->where('agent_id', $request->integer('agent_id')))
            ->when($request->filled('type_vente'), fn ($q) => $q->where('type_vente', $request->string('type_vente')))
            ->when($request->filled('date_from'), fn ($q) => $q->whereDate('date', '>=', $request->date('date_from')))
            ->when($request->filled('date_to'), fn ($q) => $q->whereDate('date', '<=', $request->date('date_to')))
            ->with(['client', 'agent'])
            ->latest('date')
            ->paginate(20)
            ->withQueryString();

        return view('invoices.index', [
            'invoices' => $invoices,
            'typesVente' => SaleType::cases(),
            'isAgent' => $isAgent,
            'agents' => $isAgent ? collect() : User::query()
                ->where('role', Role::AgentMarketeur->value)
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function show(Invoice $invoice): View
    {
        Gate::authorize('view', $invoice);

        $invoice->load(['client', 'agent', 'lines.product']);

        return view('invoices.show', ['invoice' => $invoice]);
    }

    public function downloadPdf(Invoice $invoice): Response
    {
        Gate::authorize('view', $invoice);

        $invoice->load(['client', 'agent', 'lines.product']);

        $pdf = Pdf::loadView('invoices.pdf', ['invoice' => $invoice])->setPaper('a4', 'portrait');

        return $pdf->download('facture-'.$invoice->reference.'.pdf');
    }

    public function dailyReport(Request $request): Response
    {
        Gate::authorize('viewAny', Invoice::class);

        abort_if($request->user()->role === Role::AgentMarketeur, 403, 'Le rapport consolidé est réservé au Chef Marketing.');

        $date = $request->filled('date') ? $request->date('date') : today();

        $invoices = Invoice::query()
            ->whereDate('date', $date)
            ->with(['client', 'agent'])
            ->orderBy('agent_id')
            ->latest('date')
            ->get();

        $parAgent = $invoices->groupBy(fn (Invoice $invoice): string => $invoice->agent?->name ?? 'Inconnu');

        $pdf = Pdf::loadView('invoices.daily-report', [
            'date' => $date,
            'invoices' => $invoices,
            'parAgent' => $parAgent,
            'totalGeneral' => (float) $invoices->sum('montant'),
        ])->setPaper('a4', 'portrait');

        return $pdf->download('rapport-ventes-'.$date->format('Y-m-d').'.pdf');
    }
}
