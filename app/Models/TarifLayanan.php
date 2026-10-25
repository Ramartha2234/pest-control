<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TarifLayanan extends Model
{
    use HasFactory;

    protected $table = 'tarif_layanan';

    protected $primaryKey = 'id_tarif';

    protected $fillable = [
        'jenis_layanan',
        'jenis_properti',
        'ukuran_area_min',
        'ukuran_area_max',
        'satuan',
        'tarif_dasar',
        'nama_paket',
        'deskripsi',
        'status_aktif',
    ];

    protected function casts(): array
    {
        return [
            'tarif_dasar' => 'decimal:2',
            'status_aktif' => 'boolean',
            'ukuran_area_min' => 'integer',
            'ukuran_area_max' => 'integer',
        ];
    }

    public function scopeAktif(Builder $query): Builder
    {
        return $query->where('status_aktif', true);
    }

    public function pemesanans(): HasMany
    {
        return $this->hasMany(Pemesanan::class, 'id_tarif', 'id_tarif');
    }
}
