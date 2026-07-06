<?php

namespace App\Enums;

enum AnnouncementType: string
{
    case Nouveaute = 'nouveaute';
    case Evenement = 'evenement';

    public function label(): string
    {
        return match ($this) {
            self::Nouveaute => 'Nouveauté',
            self::Evenement => 'Événement',
        };
    }

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $type): string => $type->value, self::cases());
    }
}
