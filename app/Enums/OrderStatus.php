<?php

namespace App\Enums;

enum OrderStatus: string
{
    case EnAttenteValidation = 'en_attente_validation';
    case Validee = 'validee';
    case Refusee = 'refusee';
    case PretPourLivraison = 'pret_pour_livraison';
    case EnPreparation = 'en_preparation';
    case LivreeEtFacturee = 'livree_et_facturee';
    case Annulee = 'annulee';

    public function label(): string
    {
        return match ($this) {
            self::EnAttenteValidation => 'En attente de validation',
            self::Validee => 'Validée',
            self::Refusee => 'Refusée',
            self::PretPourLivraison => 'Prêt pour livraison',
            self::EnPreparation => 'En préparation',
            self::LivreeEtFacturee => 'Livrée et facturée',
            self::Annulee => 'Annulée',
        };
    }

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $status): string => $status->value, self::cases());
    }
}
