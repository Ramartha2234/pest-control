<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JadwalPenugasan extends Model
{
    use HasFactory;

    protected $table = 'jadwal_penugasan';

    protected $primaryKey = 'id_jadwal';

    protected $fillable = [
        'id_pemesanan',
        'id_teknisi',
        'tgl_survei',
        'jam_survei',
        'tgl_pengerjaan',
        'jam_pengerjaan',
    ];

    protected function casts(): array
    {
        return [
            'tgl_survei' => 'date',
            'tgl_pengerjaan' => 'date',
        ];
    }

    public function pemesanan(): BelongsTo
    {
        return $this->belongsTo(Pemesanan::class, 'id_pemesanan', 'id_pemesanan');
    }

    public function teknisi(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'id_teknisi', 'id_pengguna');
    }
}
