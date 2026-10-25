<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HasilSurvei extends Model
{
    use HasFactory;

    protected $table = 'hasil_survei';

    protected $primaryKey = 'id_hasil_survei';

    protected $fillable = [
        'id_pemesanan',
        'id_teknisi',
        'id_admin',
        'tgl_pengerjaan_diajukan',
        'jam_pengerjaan_diajukan',
        'harga_diajukan',
        'catatan_survei',
        'status_verifikasi',
        'alasan_penolakan',
        'tgl_verifikasi',
    ];

    protected function casts(): array
    {
        return [
            'tgl_pengerjaan_diajukan' => 'date',
            'harga_diajukan' => 'decimal:2',
            'tgl_verifikasi' => 'datetime',
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

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'id_admin', 'id_pengguna');
    }
}
