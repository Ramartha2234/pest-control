<?php

namespace App\Models;

use App\Enums\StatusPelunasan;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Pemesanan extends Model
{
    use HasFactory;

    protected $table = 'pemesanan';

    protected $primaryKey = 'id_pemesanan';

    protected $fillable = [
        'id_admin',
        'id_pelanggan',
        'id_tarif',
        'kode_pemesanan',
        'jenis_properti',
        'tipe_area',
        'alamat_lokasi',
        'keluhan',
        'ukuran_area',
        'tgl_survei_diminta',
        'jam_survei_diminta',
        'harga_estimasi',
        'harga_final',
        'status_pengerjaan',
        'status_pembayaran',
        'alasan_pembatalan',
        'dibatalkan_oleh',
        'batas_konfirmasi',
        'tgl_pemesanan',
        'tgl_selesai',
        'tgl_verifikasi',
    ];

    protected function casts(): array
    {
        return [
            'tgl_survei_diminta' => 'date',
            'harga_estimasi' => 'decimal:2',
            'harga_final' => 'decimal:2',
            'ukuran_area' => 'decimal:2',
            'batas_konfirmasi' => 'datetime',
            'tgl_pemesanan' => 'datetime',
            'tgl_selesai' => 'datetime',
            'tgl_verifikasi' => 'datetime',
        ];
    }

    public mixed $legacy_tanggal_survey = null;

    public mixed $legacy_tanggal_pengerjaan = null;

    public mixed $legacy_teknisi_id = null;

    public mixed $legacy_nama_client = null;

    public mixed $legacy_no_telp_client = null;

    public mixed $legacy_is_fiktif = null;

    protected static function booted(): void
    {
        static::creating(function (Pemesanan $pemesanan) {
            if (empty($pemesanan->kode_pemesanan)) {
                $pemesanan->kode_pemesanan = static::generateKodePemesanan();
            }
            if (empty($pemesanan->tgl_pemesanan)) {
                $pemesanan->tgl_pemesanan = now();
            }
            if (empty($pemesanan->batas_konfirmasi)) {
                $pemesanan->batas_konfirmasi = now()->addDays(3);
            }
            if (empty($pemesanan->status_pengerjaan)) {
                $pemesanan->status_pengerjaan = 'Pending';
            }
            if (empty($pemesanan->status_pembayaran)) {
                $pemesanan->status_pembayaran = 'Belum Lunas';
            }
            if (empty($pemesanan->tgl_survei_diminta)) {
                $pemesanan->tgl_survei_diminta = now()->addDay()->toDateString();
            }
            if (empty($pemesanan->jam_survei_diminta)) {
                $pemesanan->jam_survei_diminta = '09:00';
            }
            if (empty($pemesanan->jenis_properti)) {
                $pemesanan->jenis_properti = 'Rumah Tinggal';
            }
            if (empty($pemesanan->alamat_lokasi)) {
                $pemesanan->alamat_lokasi = 'Alamat belum diisi';
            }
            if (empty($pemesanan->keluhan)) {
                $pemesanan->keluhan = 'Penanganan hama';
            }
            if (empty($pemesanan->harga_estimasi)) {
                $pemesanan->harga_estimasi = 1000000;
            }

            if ($pemesanan->legacy_nama_client) {
                if ($pemesanan->id_pelanggan && $pel = Pelanggan::find($pemesanan->id_pelanggan)) {
                    $pel->update(['nama' => $pemesanan->legacy_nama_client]);
                } else {
                    $pel = Pelanggan::create([
                        'nama' => $pemesanan->legacy_nama_client,
                        'no_hp' => $pemesanan->legacy_no_telp_client ?? '081234567890',
                    ]);
                    $pemesanan->id_pelanggan = $pel->id_pelanggan;
                }
            } elseif (empty($pemesanan->id_pelanggan)) {
                $pelanggan = Pelanggan::first() ?? Pelanggan::create([
                    'nama' => 'Pelanggan Walk-In',
                    'no_hp' => '081234567890',
                ]);
                $pemesanan->id_pelanggan = $pelanggan->id_pelanggan;
            }

            if (empty($pemesanan->id_tarif)) {
                $tarif = TarifLayanan::first() ?? TarifLayanan::create([
                    'jenis_layanan' => 'Pengendalian Rayap (Termite Control)',
                    'satuan' => 'm2',
                    'tarif_dasar' => 1500000,
                    'status_aktif' => true,
                ]);
                $pemesanan->id_tarif = $tarif->id_tarif;
            }
        });

        static::saved(function (Pemesanan $pemesanan) {
            if ($pemesanan->legacy_teknisi_id || $pemesanan->legacy_tanggal_survey || $pemesanan->legacy_tanggal_pengerjaan) {
                $teknisiId = $pemesanan->legacy_teknisi_id;
                if (! Pengguna::where('id_pengguna', $teknisiId)->exists()) {
                    $teknisi = Pengguna::where('role', 'teknisi')->first() ?? Pengguna::create([
                        'nama' => 'Teknisi Utama',
                        'email' => 'teknisi_'.uniqid().'@ambius.com',
                        'role' => 'teknisi',
                        'password' => bcrypt('password'),
                        'status_aktif' => true,
                    ]);
                    $teknisiId = $teknisi->id_pengguna;
                }

                JadwalPenugasan::updateOrCreate(
                    ['id_pemesanan' => $pemesanan->id_pemesanan],
                    [
                        'id_teknisi' => $teknisiId,
                        'tgl_survei' => $pemesanan->legacy_tanggal_survey ? Carbon::parse($pemesanan->legacy_tanggal_survey)->format('Y-m-d') : ($pemesanan->tgl_survei_diminta?->format('Y-m-d') ?? now()->format('Y-m-d')),
                        'jam_survei' => $pemesanan->jam_survei_diminta ?? '09:00',
                        'tgl_pengerjaan' => $pemesanan->legacy_tanggal_pengerjaan ? Carbon::parse($pemesanan->legacy_tanggal_pengerjaan)->format('Y-m-d') : null,
                        'jam_pengerjaan' => $pemesanan->legacy_tanggal_pengerjaan ? '10:00' : null,
                    ]
                );
            }
        });
    }

    /**
     * Generate unique booking code with format AMB-XXXXXX
     * Uses uppercase alphanumeric characters, non-sequential.
     */
    public static function generateKodePemesanan(): string
    {
        do {
            $pool = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ'; // exclude ambiguous 0,1,O,I
            $random = '';
            for ($i = 0; $i < 6; $i++) {
                $random .= $pool[random_int(0, strlen($pool) - 1)];
            }
            $code = 'AMB-'.$random;
        } while (static::where('kode_pemesanan', $code)->exists());

        return $code;
    }

    /* Relationships */

    public function pelanggan(): BelongsTo
    {
        return $this->belongsTo(Pelanggan::class, 'id_pelanggan', 'id_pelanggan');
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'id_admin', 'id_pengguna');
    }

    public function tarifLayanan(): BelongsTo
    {
        return $this->belongsTo(TarifLayanan::class, 'id_tarif', 'id_tarif');
    }

    public function jadwalPenugasan(): HasOne
    {
        return $this->hasOne(JadwalPenugasan::class, 'id_pemesanan', 'id_pemesanan');
    }

    public function hasilSurveis(): HasMany
    {
        return $this->hasMany(HasilSurvei::class, 'id_pemesanan', 'id_pemesanan');
    }

    public function buktiKehadirans(): HasMany
    {
        return $this->hasMany(BuktiKehadiran::class, 'id_pemesanan', 'id_pemesanan');
    }

    public function workOrders(): HasMany
    {
        return $this->hasMany(WorkOrder::class, 'id_pemesanan', 'id_pemesanan');
    }

    public function buktiPembayarans(): HasMany
    {
        return $this->hasMany(BuktiPembayaran::class, 'id_pemesanan', 'id_pemesanan');
    }

    public function buktiPengerjaans(): HasMany
    {
        return $this->hasMany(BuktiKehadiran::class, 'id_pemesanan', 'id_pemesanan')
            ->where('jenis_kehadiran', 'Pengerjaan');
    }

    /* Active / Latest Helpers */

    public function getTeknisiAttribute(): ?Pengguna
    {
        return $this->jadwalPenugasan?->teknisi;
    }

    public function latestHasilSurvei(): ?HasilSurvei
    {
        return $this->hasilSurveis()->latest('id_hasil_survei')->first();
    }

    public function latestBuktiSurvei(): ?BuktiKehadiran
    {
        return $this->buktiKehadirans()
            ->where('jenis_kehadiran', 'Survei')
            ->latest('id_bukti_kehadiran')
            ->first();
    }

    public function latestBuktiPengerjaan(): ?BuktiKehadiran
    {
        return $this->buktiKehadirans()
            ->where('jenis_kehadiran', 'Pengerjaan')
            ->latest('id_bukti_kehadiran')
            ->first();
    }

    public function latestWorkOrder(): ?WorkOrder
    {
        return $this->workOrders()->latest('id_wo')->first();
    }

    public function latestBuktiPembayaran(): ?BuktiPembayaran
    {
        return $this->buktiPembayarans()->latest('id_bukti_pembayaran')->first();
    }

    /**
     * Cek apakah pemesanan ini membutuhkan tindakan segera dari teknisi (misal pengajuan ditolak admin).
     */
    public function perluTindakanTeknisi(): bool
    {
        if ($this->status_pengerjaan === 'Cancel' || $this->status_pengerjaan === 'Done') {
            return false;
        }

        return $this->latestBuktiSurvei()?->status_verifikasi === 'Ditolak'
            || $this->latestHasilSurvei()?->status_verifikasi === 'Ditolak'
            || $this->latestBuktiPengerjaan()?->status_verifikasi === 'Ditolak'
            || $this->latestWorkOrder()?->status_verifikasi === 'Ditolak'
            || $this->latestBuktiPembayaran()?->status_verifikasi === 'Ditolak';
    }

    /**
     * Daftar alasan penolakan aktif yang perlu diperbaiki/diunggah ulang oleh teknisi.
     */
    public function daftarAlasanPenolakan(): array
    {
        $alasan = [];

        if ($this->latestBuktiSurvei()?->status_verifikasi === 'Ditolak') {
            $alasan[] = [
                'tahap' => 'Foto Kehadiran Survei',
                'alasan' => $this->latestBuktiSurvei()->alasan_penolakan ?? 'Foto ditolak oleh admin. Harap unggah ulang.',
            ];
        }

        if ($this->latestHasilSurvei()?->status_verifikasi === 'Ditolak') {
            $alasan[] = [
                'tahap' => 'Pengajuan Hasil Survei & Harga',
                'alasan' => $this->latestHasilSurvei()->alasan_penolakan ?? 'Pengajuan hasil survei ditolak oleh admin. Harap ajukan ulang.',
            ];
        }

        if ($this->latestBuktiPengerjaan()?->status_verifikasi === 'Ditolak') {
            $alasan[] = [
                'tahap' => 'Foto Kehadiran Pengerjaan',
                'alasan' => $this->latestBuktiPengerjaan()->alasan_penolakan ?? 'Foto kehadiran pengerjaan ditolak oleh admin. Harap unggah ulang.',
            ];
        }

        if ($this->latestWorkOrder()?->status_verifikasi === 'Ditolak') {
            $alasan[] = [
                'tahap' => 'Dokumen Work Order (WO)',
                'alasan' => $this->latestWorkOrder()->alasan_penolakan ?? 'Dokumen WO ditolak oleh admin. Harap unggah ulang.',
            ];
        }

        if ($this->latestBuktiPembayaran()?->status_verifikasi === 'Ditolak') {
            $alasan[] = [
                'tahap' => 'Bukti Pembayaran',
                'alasan' => $this->latestBuktiPembayaran()->alasan_penolakan ?? 'Bukti pembayaran ditolak oleh admin. Harap unggah ulang.',
            ];
        }

        return $alasan;
    }

    /**
     * Mendapatkan tahap alur pengerjaan aktif teknisi (1 sampai 5).
     * 1: Survei Lapangan (Upload Foto Kehadiran Survei)
     * 2: Hasil Survei (Input Jadwal Pengerjaan & Harga Kesepakatan)
     * 3: Mulai Pengerjaan (Upload Foto Kehadiran Pengerjaan)
     * 4: Selesai Pengerjaan (Upload WO & Bukti Pembayaran)
     * 5: Selesai Total (Pekerjaan Selesai & Lunas)
     */
    public function tahapPengerjaanTeknisi(): int
    {
        if ($this->status_pengerjaan === 'Done') {
            return 5;
        }

        if ($this->status_pengerjaan === 'Cancel') {
            return 1;
        }

        $buktiSurveiDisetujui = $this->latestBuktiSurvei()?->status_verifikasi === 'Disetujui';
        if (! $buktiSurveiDisetujui) {
            return 1;
        }

        $hasilSurveiDisetujui = $this->latestHasilSurvei()?->status_verifikasi === 'Disetujui';
        if (! $hasilSurveiDisetujui) {
            return 2;
        }

        $buktiKerjaDisetujui = $this->latestBuktiPengerjaan()?->status_verifikasi === 'Disetujui' || $this->status_pengerjaan === 'On-Going';
        if (! $buktiKerjaDisetujui) {
            return 3;
        }

        $woDisetujui = $this->latestWorkOrder()?->status_verifikasi === 'Disetujui';
        $bayarDisetujui = $this->latestBuktiPembayaran()?->status_verifikasi === 'Disetujui';

        if ($woDisetujui && $bayarDisetujui) {
            return 5;
        }

        return 4;
    }

    /* Legacy compatibility mutators */
    public function setNominalAttribute($value): void
    {
        $this->attributes['harga_estimasi'] = $value;
    }

    public function setKodeUnikAttribute($value): void
    {
        $this->attributes['kode_pemesanan'] = $value;
    }

    public function setIsLunasAttribute($value): void
    {
        $val = $value instanceof \BackedEnum ? $value->value : (string) $value;
        $status = match (strtolower($val)) {
            'lunas' => 'Lunas',
            'ditolak' => 'Ditolak',
            default => 'Belum Lunas',
        };
        $this->attributes['status_pembayaran'] = $status;
        if ($status === 'Lunas') {
            if (empty($this->attributes['status_pengerjaan']) || $this->attributes['status_pengerjaan'] === 'Pending') {
                $this->attributes['status_pengerjaan'] = 'Done';
            }
            if (empty($this->attributes['tgl_selesai'])) {
                $this->attributes['tgl_selesai'] = now();
            }
        }
    }

    public function setStatusPengerjaanAttribute($value): void
    {
        $val = $value instanceof \BackedEnum ? $value->value : (string) $value;
        $this->attributes['status_pengerjaan'] = match (strtolower($val)) {
            'pending' => 'Pending',
            'ongoing', 'on-going' => 'On-Going',
            'done' => 'Done',
            'cancel' => 'Cancel',
            default => (string) $value,
        };
    }

    public function setTanggalSurveyAttribute($value): void
    {
        $this->attributes['tgl_survei_diminta'] = $value;
        $this->legacy_tanggal_survey = $value;
    }

    public function setTanggalPengerjaanAttribute($value): void
    {
        $this->legacy_tanggal_pengerjaan = $value;
    }

    public function setTeknisiIdAttribute($value): void
    {
        $this->legacy_teknisi_id = $value;
    }

    public function setNamaClientAttribute($value): void
    {
        $this->legacy_nama_client = $value;
    }

    public function setNoTelpClientAttribute($value): void
    {
        $this->legacy_no_telp_client = $value;
    }

    public function setIsFiktifAttribute($value): void
    {
        if ($value === null) {
            $this->legacy_is_fiktif = null;

            return;
        }

        $bool = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        if ($bool === true) {
            $this->attributes['status_pengerjaan'] = 'Cancel';
            $this->attributes['status_pembayaran'] = 'Ditolak';
            $this->attributes['dibatalkan_oleh'] = 'CV. Ambius Indonesia';
            $this->attributes['alasan_pembatalan'] = 'Telah diverifikasi sebagai pemesanan fiktif oleh admin.';
        }
        $this->legacy_is_fiktif = $bool;
    }

    public function setAlasanCancelAttribute($value): void
    {
        $this->attributes['alasan_pembatalan'] = $value;
    }

    /* Legacy compatibility accessors */
    public function getIdAttribute(): mixed
    {
        return $this->id_pemesanan;
    }

    public function getAlasanCancelAttribute(): ?string
    {
        return $this->alasan_pembatalan;
    }

    public function getIsFiktifAttribute(): ?bool
    {
        if ($this->status_pengerjaan === 'Cancel' && (str_contains(strtolower((string) $this->alasan_pembatalan), 'fiktif') || $this->dibatalkan_oleh === 'Sistem')) {
            return true;
        }
        if ($this->tgl_verifikasi !== null) {
            return false;
        }
        if ($this->legacy_is_fiktif !== null) {
            return $this->legacy_is_fiktif;
        }

        return null;
    }

    public function getTeknisiIdAttribute(): ?int
    {
        return $this->jadwalPenugasan?->id_teknisi ?? $this->legacy_teknisi_id;
    }

    public function getKodeUnikAttribute(): string
    {
        return $this->kode_pemesanan;
    }

    public function getNamaClientAttribute(): string
    {
        return $this->pelanggan?->nama ?? ($this->legacy_nama_client ?? '');
    }

    public function getNoTelpClientAttribute(): string
    {
        return $this->pelanggan?->no_hp ?? ($this->legacy_no_telp_client ?? '');
    }

    public function getAlamatPengerjaanAttribute(): string
    {
        return $this->alamat_lokasi;
    }

    public function getNominalAttribute(): mixed
    {
        return $this->harga_final ?? $this->harga_estimasi;
    }

    public function canBeMarkedDone(): bool
    {
        return strtolower((string) $this->status_pembayaran) === 'lunas' || $this->is_lunas === StatusPelunasan::Lunas;
    }

    public function getJenisLayananAttribute(): mixed
    {
        $nama = $this->tarifLayanan?->jenis_layanan ?? 'Pengendalian Hama';

        return new class($nama)
        {
            public function __construct(public string $name) {}

            public function label(): string
            {
                return $this->name;
            }

            public function __toString(): string
            {
                return $this->name;
            }
        };
    }

    public function getTanggalSurveyAttribute(): ?Carbon
    {
        return $this->jadwalPenugasan?->tgl_survei ? Carbon::parse($this->jadwalPenugasan->tgl_survei) : ($this->tgl_survei_diminta ? Carbon::parse($this->tgl_survei_diminta) : null);
    }

    public function getTanggalPengerjaanAttribute(): ?Carbon
    {
        return $this->jadwalPenugasan?->tgl_pengerjaan ? Carbon::parse($this->jadwalPenugasan->tgl_pengerjaan) : null;
    }

    public function getIsLunasAttribute(): mixed
    {
        $status = match (strtolower((string) $this->status_pembayaran)) {
            'lunas' => 'lunas',
            'ditolak' => 'ditolak',
            default => 'belum_lunas',
        };

        return StatusPelunasan::tryFrom($status) ?? StatusPelunasan::BelumLunas;
    }

    public function getSisaWaktuVerifikasiAttribute(): string
    {
        if ($this->tgl_verifikasi || $this->status_pengerjaan !== 'Pending') {
            return '-';
        }

        if (now()->greaterThanOrEqualTo($this->batas_konfirmasi)) {
            return 'Kedaluwarsa';
        }

        $diffInDays = ceil(now()->diffInHours($this->batas_konfirmasi, false) / 24);

        return max(1, (int) $diffInDays).' Hari Lagi';
    }
}
