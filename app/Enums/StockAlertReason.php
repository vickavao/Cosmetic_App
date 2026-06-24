<?php

namespace App\Enums;

enum StockAlertReason: string
{
    case RuptureFournisseur = 'rupture_fournisseur';
    case ForteDemande = 'forte_demande';
    case LivraisonRetard = 'livraison_retard';
    case ProduitPerime = 'produit_perime';
    case ProduitEndommage = 'produit_endommage';
    case ErreurInventaire = 'erreur_inventaire';
    case Autre = 'autre';

    public function label(): string
    {
        return match ($this) {
            self::RuptureFournisseur => 'Rupture fournisseur',
            self::ForteDemande => 'Forte demande / ventes élevées',
            self::LivraisonRetard => 'Livraison en retard',
            self::ProduitPerime => 'Produit périmé / retiré',
            self::ProduitEndommage => 'Produit endommagé / cassé',
            self::ErreurInventaire => 'Écart / erreur d\'inventaire',
            self::Autre => 'Autre',
        };
    }

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $r): string => $r->value, self::cases());
    }
}
