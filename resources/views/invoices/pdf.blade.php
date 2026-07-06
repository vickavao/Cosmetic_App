<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Facture {{ $invoice->reference }}</title>
    <style>
        * { font-family: DejaVu Sans, sans-serif; }
        body { color: #1f2937; font-size: 12px; margin: 0; padding: 32px; }
        .header { display: flex; justify-content: space-between; border-bottom: 2px solid #6366F1; padding-bottom: 16px; margin-bottom: 24px; }
        .title { font-size: 24px; font-weight: bold; color: #6366F1; }
        .muted { color: #6b7280; }
        .meta { margin-bottom: 24px; }
        .meta td { padding: 2px 0; }
        table.lines { width: 100%; border-collapse: collapse; margin-top: 8px; }
        table.lines th { background: #f3f4f6; text-align: left; padding: 8px; font-size: 11px; text-transform: uppercase; color: #6b7280; }
        table.lines td { padding: 8px; border-bottom: 1px solid #e5e7eb; }
        .right { text-align: right; }
        .total-row td { font-weight: bold; font-size: 14px; border-top: 2px solid #1f2937; }
        .footer { margin-top: 40px; font-size: 10px; color: #9ca3af; text-align: center; }
        .badge { padding: 3px 8px; border-radius: 4px; background: #eef2ff; color: #4338ca; font-size: 11px; }
    </style>
</head>
<body>
    <div class="header">
        <div>
            <div class="title">FACTURE</div>
            <div class="muted">{{ $invoice->reference }}</div>
        </div>
        <div class="right">
            <div><strong>Date :</strong> {{ $invoice->date?->format('d/m/Y') }}</div>
            @if ($invoice->date_echeance)
                <div><strong>Échéance :</strong> {{ $invoice->date_echeance->format('d/m/Y') }}</div>
            @endif
            <div><span class="badge">{{ $invoice->type_vente->label() }}</span></div>
        </div>
    </div>

    <table class="meta" width="100%">
        <tr>
            <td>
                <strong>Client</strong><br>
                {{ $invoice->client?->name }}<br>
                <span class="muted">{{ $invoice->client?->ville }}</span>
            </td>
            <td class="right">
                <strong>Agent</strong><br>
                {{ $invoice->agent?->name }}
            </td>
        </tr>
    </table>

    <table class="lines">
        <thead>
            <tr>
                <th>Produit</th>
                <th class="right">Quantité</th>
                <th class="right">Prix unitaire</th>
                <th class="right">Sous-total</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($invoice->lines as $line)
                <tr>
                    <td>{{ $line->product?->name }}</td>
                    <td class="right">{{ $line->quantite }}</td>
                    <td class="right">{{ number_format((float) $line->prix_unitaire, 2, ',', ' ') }} $</td>
                    <td class="right">{{ number_format((float) $line->sous_total, 2, ',', ' ') }} $</td>
                </tr>
            @endforeach
            <tr class="total-row">
                <td colspan="3" class="right">TOTAL</td>
                <td class="right">{{ number_format((float) $invoice->montant, 2, ',', ' ') }} $</td>
            </tr>
        </tbody>
    </table>

    @if ($invoice->notes)
        <p class="muted" style="margin-top: 24px;"><strong>Notes :</strong> {{ $invoice->notes }}</p>
    @endif

    <div class="footer">
        Document généré le {{ now()->format('d/m/Y H:i') }} — Cosmétique
    </div>
</body>
</html>
