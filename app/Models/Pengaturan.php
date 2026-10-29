<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pengaturan extends Model
{
    protected $table = 'pengaturan';

    protected $primaryKey = 'id_pengaturan';

    protected $fillable = [
        'kunci',
        'nilai',
    ];

    /**
     * Dapatkan nilai pengaturan berdasarkan kunci, dengan fallback nilai default.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        try {
            $setting = static::query()->where('kunci', $key)->first();

            return ($setting && $setting->nilai !== null && $setting->nilai !== '')
                ? $setting->nilai
                : $default;
        } catch (\Throwable) {
            return $default;
        }
    }

    /**
     * Simpan atau perbarui nilai pengaturan.
     */
    public static function set(string $key, mixed $value): ?static
    {
        try {
            return static::query()->updateOrCreate(
                ['kunci' => $key],
                ['nilai' => $value]
            );
        } catch (\Throwable) {
            return null;
        }
    }
}
