<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkOrder extends Model
{
    use HasFactory;

    protected $table = 'work_order';

    protected $primaryKey = 'id_wo';

    protected $fillable = [
        'id_pemesanan',
        'id_teknisi',
        'id_admin',
        'file_wo',
        'catatan',
        'status_verifikasi',
        'alasan_penolakan',
        'tgl_verifikasi',
    ];

    protected function casts(): array
    {
        return [
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
