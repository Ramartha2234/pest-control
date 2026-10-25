<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TarifArea extends Model
{
    use HasFactory;

    protected $table = 'tarif_area';

    protected $primaryKey = 'id_tarif_area';

    protected $fillable = [
        'kode_area',
        'nama_area',
        'biaya_tambahan',
        'keterangan',
        'status_aktif',
    ];

    protected function casts(): array
    {
        return [
            'biaya_tambahan' => 'decimal:2',
            'status_aktif' => 'boolean',
        ];
    }

    public function scopeAktif(Builder $query): Builder
    {
        return $query->where('status_aktif', true);
    }

    /**
     * Helper untuk mengambil nominal biaya tambahan area berdasarkan kode (misal 'outdoor' / 'indoor').
     */
    public static function getBiaya(string $kodeArea, float $default = 0): float
    {
        $area = static::where('kode_area', strtolower($kodeArea))->first();

        return $area ? (float) $area->biaya_tambahan : $default;
    }
}
