<?php

namespace App\Enums;

enum StatusPelunasan: string
{
    case BelumLunas = 'belum_lunas';
    case Lunas = 'lunas';
    case Ditolak = 'ditolak';

    public function label(): string
    {
        return match ($this) {
            self::BelumLunas => 'Belum Lunas',
            self::Lunas => 'Lunas',
            self::Ditolak => 'Ditolak',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::BelumLunas => 'amber',
            self::Lunas => 'green',
            self::Ditolak => 'red',
        };
    }
}
