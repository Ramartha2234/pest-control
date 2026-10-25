<?php

namespace App\Models;

use Database\Factories\TeknisiFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Str;

class Teknisi extends Pengguna
{
    /** @use HasFactory<TeknisiFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::addGlobalScope('role_teknisi', function (Builder $builder) {
            $builder->where('role', 'teknisi');
        });

        static::creating(function ($model) {
            $model->role = 'teknisi';
            if (empty($model->email)) {
                $slug = Str::slug($model->nama ?: 'teknisi');
                $model->email = $slug . random_int(100, 9999) . '@ambius.com';
            }
            if (empty($model->password)) {
                $model->password = bcrypt('password');
            }
        });
    }

    public function pemesanans(): HasManyThrough
    {
        return $this->hasManyThrough(
            Pemesanan::class,
            JadwalPenugasan::class,
            'id_teknisi',
            'id_pemesanan',
            'id_pengguna',
            'id_pemesanan'
        );
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status_aktif', true);
    }
}
