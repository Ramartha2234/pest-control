<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Pengguna extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $table = 'pengguna';

    protected $primaryKey = 'id_pengguna';

    protected $fillable = [
        'nama',
        'name',
        'email',
        'password',
        'no_hp',
        'no_telp',
        'role',
        'status_aktif',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'status_aktif' => 'boolean',
        ];
    }

    public function getIdAttribute(): mixed
    {
        return $this->id_pengguna;
    }

    public function getNameAttribute(): string
    {
        return $this->nama;
    }

    public function setNameAttribute(string $value): void
    {
        $this->attributes['nama'] = $value;
    }

    public function getNoTelpAttribute(): ?string
    {
        return $this->no_hp;
    }

    public function setNoTelpAttribute(?string $value): void
    {
        $this->attributes['no_hp'] = $value;
    }

    public function getIsActiveAttribute(): bool
    {
        return (bool) $this->status_aktif;
    }

    public function setIsActiveAttribute(mixed $value): void
    {
        $this->attributes['status_aktif'] = filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isTeknisi(): bool
    {
        return $this->role === 'teknisi';
    }

    public function jadwalPenugasans(): HasMany
    {
        return $this->hasMany(JadwalPenugasan::class, 'id_teknisi', 'id_pengguna');
    }

    public function hasilSurveis(): HasMany
    {
        return $this->hasMany(HasilSurvei::class, 'id_teknisi', 'id_pengguna');
    }

    public function buktiKehadirans(): HasMany
    {
        return $this->hasMany(BuktiKehadiran::class, 'id_teknisi', 'id_pengguna');
    }

    public function workOrders(): HasMany
    {
        return $this->hasMany(WorkOrder::class, 'id_teknisi', 'id_pengguna');
    }

    public function buktiPembayarans(): HasMany
    {
        return $this->hasMany(BuktiPembayaran::class, 'id_teknisi', 'id_pengguna');
    }

    /**
     * Menghitung jumlah pemesanan aktif yang membutuhkan tindakan teknisi (mis. pengajuan ditolak admin).
     */
    public function countPerluTindakan(): int
    {
        return Pemesanan::whereHas('jadwalPenugasan', fn ($q) => $q->where('id_teknisi', $this->id_pengguna))
            ->whereNotIn('status_pengerjaan', ['Done', 'Cancel'])
            ->with(['hasilSurveis', 'buktiKehadirans', 'workOrders', 'buktiPembayarans'])
            ->get()
            ->filter(fn ($p) => $p->perluTindakanTeknisi())
            ->count();
    }
}
