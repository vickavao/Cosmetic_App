<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Rapport des ventes — {{ $date->format('d/m/Y') }}</title>
    <style>
        * { font-family: DejaVu Sans, sans-serif; }
        body { color: #1f2937; font-size: 12px; margin: 0; padding: 32px; }
        .header { border-bottom: 2px solid #6366F1; padding-bottom: 16px; margin-bottom: 24px; }
        .title { font-size: 22px; font-weight: bold; color: #6366F1; }
        .muted { color: #6b7280; }
        h2 { font-size: 14px; margin: 20px 0 6px; color: #111827; }
        table { width: 100%; border-collapse: collapse; margin-top: 4px; }
        th { background: #f3f4f6; text-align: left; padding: 6px 8px; font-size: 10px; text-transform: uppercase; color: #6b7280; }
        td { padding: 6px 8px; border-bottom: 1px solid #e5e7eb; }
        .right { text-align: right; }
        .subtotal td { font-weight: bold; background: #fafafa; }
        .grand-total { margin-top: 24px; border-top: 2px solid #1f2937; padding-top: 10px; font-size: 16px; font-weight: bold; text-align: right; }
        .badge { padding: 2px 6px; border-radius: 4px; font-size: 10px; }
        .badge-green { background: #dcfce7; color: #166534; }
        .badge-orange { background: #ffedd5; color: #9a3412; }
        .footer { margin-top: 40px; font-size: 10px; color: #9ca3af; text-align: center; }
    </style>
</head>
<body>
    <div class="header">
        <div class="title">RAPPORT DES VENTES JOURNALIER</div>
        <div class="muted">Date : {{ $date->format('d/m/Y') }} — {{ $invoices->count() }} facture(s)</div>
    </div>

    @forelse ($parAgent as $agentName => $agentInvoices)
        <h2>{{ $agentName }}</h2>
        <table>
            <thead>
                <tr>
                    <th>Référence</th>
                    <th>Client</th>
                    <th>Type</th>
                    <th class="right">Montant</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($agentInvoices as $invoice)
                    @php($estComptant = $invoice->type_vente === \App\Enums\SaleType::Comptant)
                    <tr>
                        <td>{{ $invoice->reference }}</td>
                        <td>{{ $invoice->client?->name ?? '—' }}</td>
                        <td><span class="badge {{ $estComptant ? 'badge-green' : 'badge-orange' }}">{{ $estComptant ? 'Payé' : 'Pris à crédit' }}</span></td>
                        <td class="right">{{ number_format((float) $invoice->montant, 2, ',', ' ') }} $</td>
                    </tr>
                @endforeach
                <tr class="subtotal">
                    <td colspan="3" class="right">Sous-total {{ $agentName }}</td>
                    <td class="right">{{ number_format((float) $agentInvoices->sum('montant'), 2, ',', ' ') }} $</td>
                </tr>
            </tbody>
        </table>
    @empty
        <p class="muted">Aucune facture émise pour cette journée.</p>
    @endforelse

    <div class="grand-total">
        TOTAL GÉNÉRAL : {{ number_format($totalGeneral, 2, ',', ' ') }} $
    </div>

    <div class="footer">
        Document généré le {{ now()->format('d/m/Y H:i') }} — Premidis SARL · Passerelle département des ventes
    </div>
</body>
</html>
