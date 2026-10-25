<?php

namespace App\Enums;

enum JenisLayanan: string
{
    case Rayap = 'rayap';
    case Tikus = 'tikus';
    case Kecoa = 'kecoa';
    case Nyamuk = 'nyamuk';
    case Lalat = 'lalat';

    public function label(): string
    {
        return match ($this) {
            self::Rayap => 'Pengendalian Rayap (Termite Control)',
            self::Tikus => 'Pengendalian Tikus (Rodent Control)',
            self::Kecoa => 'Pengendalian Kecoa (Cockroach Control)',
            self::Nyamuk => 'Pengendalian Nyamuk (Mosquito Control)',
            self::Lalat => 'Pengendalian Lalat (Fly Control)',
        };
    }
}
