<?php

namespace App\Services;

use App\Models\BuktiKehadiran;
use App\Models\BuktiPembayaran;
use App\Models\HasilSurvei;
use App\Models\JadwalPenugasan;
use App\Models\Pelanggan;
use App\Models\Pemesanan;
use App\Models\Pengguna;
use App\Models\TarifArea;
use App\Models\TarifLayanan;
use App\Models\WorkOrder;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PemesananStatusService
{
    /**
     * Alur 1: Pelanggan membuat pemesanan online.
     * Mengkalkulasi estimasi harga dari tarif aktif dan men-generate kode unik AMB-XXXXXX.
     */
    public function buatPemesanan(array $data): Pemesanan
    {
        return DB::transaction(function () use ($data) {
            // Validasi ketersediaan slot (1 Slot Jam = Maksimal 1 Pelanggan)
            if (! empty($data['validasi_slot']) && isset($data['tgl_survei_diminta'], $data['jam_survei_diminta']) && ! $this->isSlotTersedia($data['tgl_survei_diminta'], $data['jam_survei_diminta'])) {
                throw new \InvalidArgumentException('Mohon maaf, slot jam survei pada tanggal tersebut sudah dibooking oleh pelanggan lain.');
            }

            // Normalisasi no_hp ke format standar '08...' untuk deduplikasi pelanggan yang konsisten
            $cleanPhone = preg_replace('/[\s\-\.]/', '', (string) $data['no_hp']);
            if (str_starts_with($cleanPhone, '+62')) {
                $cleanPhone = '0'.substr($cleanPhone, 3);
            } elseif (str_starts_with($cleanPhone, '62')) {
                $cleanPhone = '0'.substr($cleanPhone, 2);
            }

            // 1. Cari atau buat data pelanggan (deduplikasi berdasarkan nomor HP)
            $pelanggan = Pelanggan::firstOrCreate(
                ['no_hp' => $cleanPhone],
                [
                    'nama' => $data['nama'],
                    'email' => $data['email'] ?? null,
                    'nama_usaha' => $data['nama_usaha'] ?? null,
                    'alamat' => $data['alamat_lokasi'] ?? null,
                ]
            );

            // Perbarui data pelanggan jika ada perubahan
            $pelanggan->update(array_filter([
                'nama' => $data['nama'],
                'email' => $data['email'] ?? null,
                'nama_usaha' => $data['nama_usaha'] ?? null,
                'alamat' => $data['alamat_lokasi'] ?? null,
            ]));

            // 2. Ambil tarif layanan aktif
            $tarif = TarifLayanan::aktif()->findOrFail($data['id_tarif']);

            // Kalkulasi harga estimasi: Tarif dasar x estimasi luas area
            $ukuranArea = isset($data['ukuran_area']) ? (float) $data['ukuran_area'] : null;
            $tarifDasar = (float) $tarif->tarif_dasar;
            if ($ukuranArea && $ukuranArea > 0) {
                $hargaEstimasi = $tarifDasar * $ukuranArea;
            } else {
                $hargaEstimasi = $tarifDasar;
            }

            // Pilihan Area (Indoor / Outdoor): Ambil biaya dari tabel tarif_area
            $tipeArea = isset($data['tipe_area']) && strcasecmp($data['tipe_area'], 'Outdoor') === 0 ? 'Outdoor' : 'Indoor';
            $biayaTambahanArea = TarifArea::getBiaya(
                strtolower($tipeArea),
                strcasecmp($tipeArea, 'Outdoor') === 0 ? 100000 : 0
            );
            $hargaEstimasi += $biayaTambahanArea;

            // Deteksi kewajiban Down Payment (DP): nominal estimasi >= threshold (Rp 10.000.000)
            $dpThreshold = (float) config('pestcontrol.dp_threshold', 10000000);
            $dpPercentage = (float) config('pestcontrol.dp_percentage', 10);
            $wajibDp = $hargaEstimasi >= $dpThreshold;
            $nominalDp = $wajibDp ? round($hargaEstimasi * ($dpPercentage / 100), 2) : 0;
            $statusDp = $wajibDp ? 'Belum Dibayar' : null;

            // 3. Simpan pemesanan
            $pemesanan = Pemesanan::create([
                'id_pelanggan' => $pelanggan->id_pelanggan,
                'id_tarif' => $tarif->id_tarif,
                'kode_pemesanan' => Pemesanan::generateKodePemesanan(),
                'jenis_properti' => $data['jenis_properti'],
                'tipe_area' => $tipeArea,
                'alamat_lokasi' => $data['alamat_lokasi'],
                'keluhan' => $data['keluhan'],
                'ukuran_area' => $ukuranArea,
                'tgl_survei_diminta' => $data['tgl_survei_diminta'],
                'jam_survei_diminta' => $data['jam_survei_diminta'],
                'harga_estimasi' => $hargaEstimasi,
                'wajib_dp' => $wajibDp,
                'nominal_dp' => $nominalDp,
                'status_dp' => $statusDp,
                'status_pengerjaan' => 'Pending',
                'status_pembayaran' => 'Belum Lunas',
                'tgl_pemesanan' => now(),
                'batas_konfirmasi' => now()->addDays(3),
            ]);

            return $pemesanan;
        });
    }

    /**
     * Pelanggan mengunggah bukti transfer DP 10%.
     * Mengubah status_dp menjadi 'Menunggu Verifikasi' dan mereset alasan penolakan sebelumnya.
     */
    public function uploadBuktiDp(
        Pemesanan $pemesanan,
        string $buktiDpPath
    ): Pemesanan {
        return DB::transaction(function () use ($pemesanan, $buktiDpPath) {
            $pemesanan->update([
                'bukti_dp' => $buktiDpPath,
                'status_dp' => 'Menunggu Verifikasi',
                'alasan_penolakan_dp' => null,
            ]);

            return $pemesanan->fresh(['pelanggan', 'tarifLayanan']);
        });
    }

    /**
     * Verifikasi Down Payment (DP) oleh Admin.
     * Admin memverifikasi bukti transfer DP yang diunggah pelanggan (Setujui atau Tolak dengan alasan).
     */
    public function verifikasiDp(
        Pemesanan $pemesanan,
        int $adminId,
        bool|string $disetujui = true,
        ?string $alasanPenolakan = null,
        ?string $catatan = null,
        ?string $newBuktiPath = null
    ): Pemesanan {
        // Mendukung kompatibilitas signature lama: verifikasiDp($pemesanan, $adminId, $filePath, $catatan)
        if (is_string($disetujui)) {
            $filePath = $disetujui;
            $catatanText = $alasanPenolakan;

            return DB::transaction(function () use ($pemesanan, $adminId, $filePath, $catatanText) {
                $pemesanan->update([
                    'id_admin' => $adminId,
                    'status_dp' => 'Lunas',
                    'bukti_dp' => $filePath,
                    'tgl_bayar_dp' => now(),
                    'tgl_verifikasi_dp' => now(),
                    'alasan_penolakan_dp' => null,
                    'catatan_dp' => $catatanText,
                ]);

                return $pemesanan->fresh(['pelanggan', 'tarifLayanan']);
            });
        }

        return DB::transaction(function () use ($pemesanan, $adminId, $disetujui, $alasanPenolakan, $catatan, $newBuktiPath) {
            $updateData = [
                'id_admin' => $adminId,
            ];

            if ($newBuktiPath) {
                $updateData['bukti_dp'] = $newBuktiPath;
            }

            if ($disetujui) {
                $updateData['status_dp'] = 'Lunas';
                $updateData['tgl_bayar_dp'] = now();
                $updateData['tgl_verifikasi_dp'] = now();
                $updateData['alasan_penolakan_dp'] = null;
                if ($catatan !== null) {
                    $updateData['catatan_dp'] = $catatan;
                }
            } else {
                $updateData['status_dp'] = 'Ditolak';
                $updateData['tgl_verifikasi_dp'] = now();
                $updateData['alasan_penolakan_dp'] = $alasanPenolakan;
                $updateData['catatan_dp'] = $alasanPenolakan;
            }

            $pemesanan->update($updateData);

            return $pemesanan->fresh(['pelanggan', 'tarifLayanan']);
        });
    }

    /**
     * Alur 2: Admin memverifikasi pemesanan, mengatur jadwal survei, dan menugaskan teknisi.
     */
    public function verifikasiDanTugaskan(
        Pemesanan $pemesanan,
        int $adminId,
        int $teknisiId,
        string $tglSurvei,
        string $jamSurvei
    ): Pemesanan {
        if ($pemesanan->wajib_dp && $pemesanan->status_dp !== 'Lunas') {
            throw new \InvalidArgumentException('Pemesanan skala besar (nominal >= Rp 10.000.000) wajib diverifikasi pembayaran Down Payment (DP 10%) terlebih dahulu sebelum survei dijadwalkan.');
        }

        return DB::transaction(function () use ($pemesanan, $adminId, $teknisiId, $tglSurvei, $jamSurvei) {
            $pemesanan->update([
                'id_admin' => $adminId,
                'tgl_verifikasi' => now(),
            ]);

            JadwalPenugasan::updateOrCreate(
                ['id_pemesanan' => $pemesanan->id_pemesanan],
                [
                    'id_teknisi' => $teknisiId,
                    'tgl_survei' => $tglSurvei,
                    'jam_survei' => $jamSurvei,
                ]
            );

            return $pemesanan->fresh(['pelanggan', 'jadwalPenugasan.teknisi', 'tarifLayanan']);
        });
    }

    /**
     * Alur 2 (Tolak): Menolak pemesanan saat verifikasi -> status Cancel + alasan.
     */
    public function tolakPemesanan(Pemesanan $pemesanan, int $adminId, string $alasan): Pemesanan
    {
        return DB::transaction(function () use ($pemesanan, $adminId, $alasan) {
            $pemesanan->update([
                'id_admin' => $adminId,
                'tgl_verifikasi' => now(),
                'status_pengerjaan' => 'Cancel',
                'status_pembayaran' => 'Ditolak',
                'dibatalkan_oleh' => 'CV. Ambius Indonesia',
                'alasan_pembatalan' => $alasan,
            ]);

            return $pemesanan;
        });
    }

    /**
     * Alur 3: Auto-cancel pemesanan yang belum diverifikasi setelah 3 hari.
     */
    public function autoCancelExpired(): int
    {
        return DB::transaction(function () {
            $expiredOrders = Pemesanan::where('status_pengerjaan', 'Pending')
                ->whereNull('tgl_verifikasi')
                ->where(function ($q) {
                    $q->where('batas_konfirmasi', '<=', now())
                        ->orWhere('created_at', '<=', now()->subDays(3));
                })
                ->get();

            $count = 0;
            foreach ($expiredOrders as $order) {
                $order->update([
                    'status_pengerjaan' => 'Cancel',
                    'status_pembayaran' => 'Ditolak',
                    'dibatalkan_oleh' => 'Sistem',
                    'alasan_pembatalan' => 'Tidak ada konfirmasi dalam 3 hari',
                    'is_fiktif' => true,
                ]);
                $count++;
            }

            return $count;
        });
    }

    /**
     * Menghitung jumlah total teknisi lapangan yang aktif sebagai batas kuota per jam.
     */
    public function getKapasitasTeknisiAktif(): int
    {
        $count = Pengguna::where('role', 'teknisi')
            ->where('status_aktif', true)
            ->count();

        return max(1, $count);
    }

    /**
     * Cek apakah slot jam survei pada tanggal tertentu masih tersedia.
     * Aturan Opsi A (Kapasitas Tim): Jam hanya penuh jika seluruh kuota teknisi aktif
     * sudah terisi penugasan (Survei / Pengerjaan) atau pemesanan pending pada jam tersebut.
     * Pemesanan dengan status 'Cancel' atau 'Done' tidak dihitung.
     */
    public function isSlotTersedia(string $tanggal, string $jam, ?int $excludePemesananId = null): bool
    {
        $parts = explode(':', trim($jam));
        $hour = str_pad($parts[0] ?? '09', 2, '0', STR_PAD_LEFT);

        $slotTerisi = $this->getSlotTerisiPadaTanggal($tanggal, $excludePemesananId);

        return ! in_array($hour, $slotTerisi, true);
    }

    /**
     * Mengambil daftar slot jam (format dua digit 'HH', misal ['09', '14'])
     * yang sudah penuh / mencapai kuota kapasitas teknisi aktif pada tanggal tertentu.
     * Memperhitungkan baik jadwal Survei maupun Pengerjaan aktif, serta pemesanan pending.
     *
     * @return array<int, string>
     */
    public function getSlotTerisiPadaTanggal(string $tanggal, ?int $excludePemesananId = null): array
    {
        $kapasitas = $this->getKapasitasTeknisiAktif();
        $dateOnly = Carbon::parse($tanggal)->toDateString();

        // 1. Ambil semua penugasan teknisi aktif (Survei atau Pengerjaan) pada tanggal ini
        $jadwals = JadwalPenugasan::with('pemesanan')
            ->whereHas('pemesanan', function ($q) use ($excludePemesananId) {
                $q->whereNotIn('status_pengerjaan', ['Cancel', 'Done'])
                    ->when($excludePemesananId, fn ($sq) => $sq->where('id_pemesanan', '!=', $excludePemesananId));
            })
            ->when($excludePemesananId, fn ($q) => $q->where('id_pemesanan', '!=', $excludePemesananId))
            ->where(function ($q) use ($dateOnly) {
                $q->whereDate('tgl_survei', $dateOnly)
                    ->orWhereDate('tgl_pengerjaan', $dateOnly);
            })
            ->get();

        // 2. Ambil pemesanan baru yang belum memiliki JadwalPenugasan (masih menunggu verifikasi admin)
        $pemesanansPending = Pemesanan::whereDoesntHave('jadwalPenugasan')
            ->whereNotIn('status_pengerjaan', ['Cancel', 'Done'])
            ->when($excludePemesananId, fn ($q) => $q->where('id_pemesanan', '!=', $excludePemesananId))
            ->whereDate('tgl_survei_diminta', $dateOnly)
            ->get();

        $hourUsage = []; // '09' => ['assigned' => [teknisiId => true], 'pending' => int]

        foreach ($jadwals as $j) {
            // Survei
            if ($j->tgl_survei && Carbon::parse($j->tgl_survei)->toDateString() === $dateOnly && $j->jam_survei) {
                $parts = explode(':', (string) $j->jam_survei);
                $h = str_pad($parts[0] ?? '', 2, '0', STR_PAD_LEFT);
                if ($h !== '') {
                    $tekId = $j->id_teknisi ?: ('j_'.$j->id_jadwal);
                    $hourUsage[$h]['assigned'][$tekId] = true;
                }
            }

            // Pengerjaan
            if ($j->tgl_pengerjaan && Carbon::parse($j->tgl_pengerjaan)->toDateString() === $dateOnly && $j->jam_pengerjaan) {
                $parts = explode(':', (string) $j->jam_pengerjaan);
                $h = str_pad($parts[0] ?? '', 2, '0', STR_PAD_LEFT);
                if ($h !== '') {
                    $tekId = $j->id_teknisi ?: ('j_'.$j->id_jadwal);
                    $hourUsage[$h]['assigned'][$tekId] = true;
                }
            }
        }

        foreach ($pemesanansPending as $p) {
            if ($p->jam_survei_diminta) {
                $parts = explode(':', (string) $p->jam_survei_diminta);
                $h = str_pad($parts[0] ?? '', 2, '0', STR_PAD_LEFT);
                if ($h !== '') {
                    $hourUsage[$h]['pending'] = ($hourUsage[$h]['pending'] ?? 0) + 1;
                }
            }
        }

        $slotPenuh = [];

        foreach ($hourUsage as $h => $data) {
            $assignedCount = isset($data['assigned']) ? count($data['assigned']) : 0;
            $pendingCount = $data['pending'] ?? 0;
            $totalTerpakai = $assignedCount + $pendingCount;

            if ($totalTerpakai >= $kapasitas) {
                $slotPenuh[] = str_pad((string) $h, 2, '0', STR_PAD_LEFT);
            }
        }

        sort($slotPenuh);

        return $slotPenuh;
    }

    /**
     * Mengambil daftar jadwal sibuk teknisi per tanggal (YYYY-MM-DD).
     *
     * @return array<string, array{jam: string[], detail: array<string, array{jam: string, tipe: string, kode: string}>}>
     */
    public function getJadwalSibukTeknisi(int $teknisiId, ?int $excludePemesananId = null): array
    {
        $jadwals = JadwalPenugasan::with('pemesanan:id_pemesanan,kode_pemesanan,status_pengerjaan')
            ->where('id_teknisi', $teknisiId)
            ->when($excludePemesananId, fn ($q) => $q->where('id_pemesanan', '!=', $excludePemesananId))
            ->whereHas('pemesanan', function ($q) {
                $q->whereNotIn('status_pengerjaan', ['Done', 'Cancel']);
            })
            ->get();

        $map = [];

        foreach ($jadwals as $j) {
            $kode = $j->pemesanan?->kode_pemesanan ?? '#'.$j->id_pemesanan;

            // 1. Cek survei
            if ($j->tgl_survei && $j->jam_survei) {
                $tgl = Carbon::parse($j->tgl_survei)->toDateString();
                $jamFull = substr((string) $j->jam_survei, 0, 5);
                $hour = str_pad(explode(':', $jamFull)[0] ?? '', 2, '0', STR_PAD_LEFT);

                if ($hour !== '') {
                    $map[$tgl]['jam'][] = $hour;
                    $map[$tgl]['detail'][$hour] = [
                        'jam' => $jamFull,
                        'tipe' => 'Survei',
                        'kode' => $kode,
                    ];
                }
            }

            // 2. Cek pengerjaan
            if ($j->tgl_pengerjaan && $j->jam_pengerjaan) {
                $tgl = Carbon::parse($j->tgl_pengerjaan)->toDateString();
                $jamFull = substr((string) $j->jam_pengerjaan, 0, 5);
                $hour = str_pad(explode(':', $jamFull)[0] ?? '', 2, '0', STR_PAD_LEFT);

                if ($hour !== '') {
                    $map[$tgl]['jam'][] = $hour;
                    $map[$tgl]['detail'][$hour] = [
                        'jam' => $jamFull,
                        'tipe' => 'Pengerjaan',
                        'kode' => $kode,
                    ];
                }
            }
        }

        foreach ($map as $tgl => &$val) {
            $val['jam'] = array_values(array_unique($val['jam']));
        }

        return $map;
    }

    /**
     * Cek apakah teknisi memiliki bentrok jadwal pada tanggal & jam yang sama.
     */
    public function cekBentrokJadwalTeknisi(
        int $teknisiId,
        string $tanggal,
        string $jam,
        ?int $excludePemesananId = null
    ): ?JadwalPenugasan {
        $jamShort = substr(trim($jam), 0, 5);
        $jamLong = strlen($jamShort) === 5 ? $jamShort.':00' : $jamShort;
        $dateOnly = Carbon::parse($tanggal)->toDateString();

        return JadwalPenugasan::with(['pemesanan', 'teknisi'])
            ->where('id_teknisi', $teknisiId)
            ->when($excludePemesananId, fn ($q) => $q->where('id_pemesanan', '!=', $excludePemesananId))
            ->where(function ($q) use ($dateOnly, $jamShort, $jamLong) {
                $q->where(function ($sq) use ($dateOnly, $jamShort, $jamLong) {
                    $sq->whereDate('tgl_survei', $dateOnly)
                        ->where(function ($jq) use ($jamShort, $jamLong) {
                            $jq->where('jam_survei', $jamShort)
                                ->orWhere('jam_survei', $jamLong);
                        });
                })->orWhere(function ($sq) use ($dateOnly, $jamShort, $jamLong) {
                    $sq->whereDate('tgl_pengerjaan', $dateOnly)
                        ->where(function ($jq) use ($jamShort, $jamLong) {
                            $jq->where('jam_pengerjaan', $jamShort)
                                ->orWhere('jam_pengerjaan', $jamLong);
                        });
                });
            })
            ->whereHas('pemesanan', function ($pq) {
                $pq->whereNotIn('status_pengerjaan', ['Done', 'Cancel']);
            })
            ->first();
    }

    /**
     * Alur 4: Teknisi upload bukti kehadiran survei.
     * Jika sebelumnya pernah ditolak, disimpan sebagai baris baru.
     */
    public function submitBuktiKehadiranSurvei(
        Pemesanan $pemesanan,
        int $teknisiId,
        string $fotoPath,
        ?string $catatan = null
    ): BuktiKehadiran {
        return DB::transaction(function () use ($pemesanan, $teknisiId, $fotoPath, $catatan) {
            return BuktiKehadiran::create([
                'id_pemesanan' => $pemesanan->id_pemesanan,
                'id_teknisi' => $teknisiId,
                'jenis_kehadiran' => 'Survei',
                'foto' => $fotoPath,
                'catatan' => $catatan,
                'status_verifikasi' => 'Menunggu',
            ]);
        });
    }

    /**
     * Alur 4/7: Admin verifikasi bukti kehadiran (Survei atau Pengerjaan).
     * Jika reject: alasan wajib.
     * Jika approve kehadiran Pengerjaan: status_pengerjaan OTOMATIS menjadi On-Going!
     */
    public function verifikasiBuktiKehadiran(
        BuktiKehadiran $bukti,
        int $adminId,
        bool $disetujui,
        ?string $alasanPenolakan = null
    ): BuktiKehadiran {
        return DB::transaction(function () use ($bukti, $adminId, $disetujui, $alasanPenolakan) {
            $bukti->update([
                'id_admin' => $adminId,
                'tgl_verifikasi' => now(),
                'status_verifikasi' => $disetujui ? 'Disetujui' : 'Ditolak',
                'alasan_penolakan' => $disetujui ? null : $alasanPenolakan,
            ]);

            // Alur 7: Saat mulai kerja, foto kehadiran (Pengerjaan) di-approve -> status_pengerjaan jadi On-Going OTOMATIS
            if ($disetujui && $bukti->jenis_kehadiran === 'Pengerjaan') {
                $bukti->pemesanan()->update([
                    'status_pengerjaan' => 'On-Going',
                ]);
            }

            return $bukti;
        });
    }

    /**
     * Alur 5: Teknisi input hasil survei (tgl pengerjaan, jam, harga deal).
     * Jika diunggah ulang setelah ditolak, disimpan sebagai baris baru.
     */
    public function submitHasilSurvei(
        Pemesanan $pemesanan,
        int $teknisiId,
        string $tglPengerjaan,
        string $jamPengerjaan,
        float $hargaDiajukan,
        ?string $catatan = null
    ): HasilSurvei {
        return DB::transaction(function () use ($pemesanan, $teknisiId, $tglPengerjaan, $jamPengerjaan, $hargaDiajukan, $catatan) {
            return HasilSurvei::create([
                'id_pemesanan' => $pemesanan->id_pemesanan,
                'id_teknisi' => $teknisiId,
                'tgl_pengerjaan_diajukan' => $tglPengerjaan,
                'jam_pengerjaan_diajukan' => $jamPengerjaan,
                'harga_diajukan' => $hargaDiajukan,
                'catatan_survei' => $catatan,
                'status_verifikasi' => 'Menunggu',
            ]);
        });
    }

    /**
     * Alur 5: Admin verifikasi hasil survei.
     * Setuju: jadwal_penugasan.tgl_pengerjaan & jam_pengerjaan terisi, pemesanan.harga_final terisi.
     * Tolak: alasan wajib, teknisi input ulang (baris baru).
     */
    public function verifikasiHasilSurvei(
        HasilSurvei $hasilSurvei,
        int $adminId,
        bool $disetujui,
        ?string $alasanPenolakan = null
    ): HasilSurvei {
        return DB::transaction(function () use ($hasilSurvei, $adminId, $disetujui, $alasanPenolakan) {
            $hasilSurvei->update([
                'id_admin' => $adminId,
                'tgl_verifikasi' => now(),
                'status_verifikasi' => $disetujui ? 'Disetujui' : 'Ditolak',
                'alasan_penolakan' => $disetujui ? null : $alasanPenolakan,
            ]);

            if ($disetujui) {
                $pemesanan = $hasilSurvei->pemesanan;
                $pemesanan->update([
                    'harga_final' => $hasilSurvei->harga_diajukan,
                ]);

                JadwalPenugasan::updateOrCreate(
                    ['id_pemesanan' => $pemesanan->id_pemesanan],
                    [
                        'id_teknisi' => $hasilSurvei->id_teknisi,
                        'tgl_pengerjaan' => $hasilSurvei->tgl_pengerjaan_diajukan,
                        'jam_pengerjaan' => $hasilSurvei->jam_pengerjaan_diajukan,
                    ]
                );
            }

            return $hasilSurvei;
        });
    }

    /**
     * Alur 6: Admin bisa ubah jadwal survei & tanggal pengerjaan kapan saja.
     */
    public function ubahJadwal(
        Pemesanan $pemesanan,
        ?string $tglSurvei = null,
        ?string $jamSurvei = null,
        ?string $tglPengerjaan = null,
        ?string $jamPengerjaan = null
    ): JadwalPenugasan {
        return DB::transaction(function () use ($pemesanan, $tglSurvei, $jamSurvei, $tglPengerjaan, $jamPengerjaan) {
            $jadwal = $pemesanan->jadwalPenugasan;
            if (! $jadwal) {
                throw new \InvalidArgumentException('Pemesanan belum memiliki penugasan teknisi.');
            }

            $updateData = [];
            if ($tglSurvei !== null) {
                $updateData['tgl_survei'] = $tglSurvei;
            }
            if ($jamSurvei !== null) {
                $updateData['jam_survei'] = $jamSurvei;
            }
            if ($tglPengerjaan !== null) {
                $updateData['tgl_pengerjaan'] = $tglPengerjaan;
            }
            if ($jamPengerjaan !== null) {
                $updateData['jam_pengerjaan'] = $jamPengerjaan;
            }

            $jadwal->update($updateData);

            // Jika pesanan sudah berstatus Done dan tanggal pengerjaan diubah/disinkronkan,
            // sesuaikan juga tgl_selesai pemesanan agar pada laporan tanggalnya sama
            if ($pemesanan->status_pengerjaan === 'Done' && $tglPengerjaan !== null) {
                $jamSelesai = $pemesanan->tgl_selesai ? Carbon::parse($pemesanan->tgl_selesai) : now();
                $pemesanan->update([
                    'tgl_selesai' => Carbon::parse($tglPengerjaan)->setTime($jamSelesai->hour, $jamSelesai->minute, $jamSelesai->second),
                ]);
            }

            return $jadwal;
        });
    }

    /**
     * Alur 7: Teknisi upload bukti kehadiran pengerjaan.
     */
    public function submitBuktiKehadiranPengerjaan(
        Pemesanan $pemesanan,
        int $teknisiId,
        string $fotoPath,
        ?string $catatan = null
    ): BuktiKehadiran {
        return DB::transaction(function () use ($pemesanan, $teknisiId, $fotoPath, $catatan) {
            return BuktiKehadiran::create([
                'id_pemesanan' => $pemesanan->id_pemesanan,
                'id_teknisi' => $teknisiId,
                'jenis_kehadiran' => 'Pengerjaan',
                'foto' => $fotoPath,
                'catatan' => $catatan,
                'status_verifikasi' => 'Menunggu',
            ]);
        });
    }

    /**
     * Alur 8: Teknisi upload Work Order (WO).
     */
    public function submitWorkOrder(
        Pemesanan $pemesanan,
        int $teknisiId,
        string $filePath,
        ?string $catatan = null
    ): WorkOrder {
        return DB::transaction(function () use ($pemesanan, $teknisiId, $filePath, $catatan) {
            return WorkOrder::create([
                'id_pemesanan' => $pemesanan->id_pemesanan,
                'id_teknisi' => $teknisiId,
                'file_wo' => $filePath,
                'catatan' => $catatan,
                'status_verifikasi' => 'Menunggu',
            ]);
        });
    }

    /**
     * Alur 8: Admin verifikasi Work Order.
     * Memicu evaluasi otomatis: jika WO & Bukti Bayar KEDUANYA disetujui -> Done & Lunas!
     */
    public function verifikasiWorkOrder(
        WorkOrder $wo,
        int $adminId,
        bool $disetujui,
        ?string $alasanPenolakan = null
    ): WorkOrder {
        return DB::transaction(function () use ($wo, $adminId, $disetujui, $alasanPenolakan) {
            $wo->update([
                'id_admin' => $adminId,
                'tgl_verifikasi' => now(),
                'status_verifikasi' => $disetujui ? 'Disetujui' : 'Ditolak',
                'alasan_penolakan' => $disetujui ? null : $alasanPenolakan,
            ]);

            $this->evaluasiPenyelesaianOtomatis($wo->pemesanan);

            return $wo;
        });
    }

    /**
     * Alur 8: Teknisi upload bukti pembayaran (Cash / Transfer).
     */
    public function submitBuktiPembayaran(
        Pemesanan $pemesanan,
        int $teknisiId,
        string $filePath,
        string $metodePembayaran,
        ?float $jumlahBayar = null,
        ?string $catatan = null
    ): BuktiPembayaran {
        return DB::transaction(function () use ($pemesanan, $teknisiId, $filePath, $metodePembayaran, $jumlahBayar, $catatan) {
            return BuktiPembayaran::create([
                'id_pemesanan' => $pemesanan->id_pemesanan,
                'id_teknisi' => $teknisiId,
                'file_bukti' => $filePath,
                'metode_pembayaran' => $metodePembayaran,
                'jumlah_bayar' => $jumlahBayar ?? ($pemesanan->harga_final ?? $pemesanan->harga_estimasi),
                'catatan' => $catatan,
                'status_verifikasi' => 'Menunggu',
            ]);
        });
    }

    /**
     * Alur 8: Admin verifikasi Bukti Pembayaran.
     * Jika ditolak: status_pembayaran jadi 'Ditolak' + alasan.
     * Jika disetujui: evaluasi otomatis penyelesaian pesanan (Done & Lunas).
     */
    public function verifikasiBuktiPembayaran(
        BuktiPembayaran $bukti,
        int $adminId,
        bool $disetujui,
        ?string $alasanPenolakan = null
    ): BuktiPembayaran {
        return DB::transaction(function () use ($bukti, $adminId, $disetujui, $alasanPenolakan) {
            $bukti->update([
                'id_admin' => $adminId,
                'tgl_verifikasi' => now(),
                'status_verifikasi' => $disetujui ? 'Disetujui' : 'Ditolak',
                'alasan_penolakan' => $disetujui ? null : $alasanPenolakan,
            ]);

            $pemesanan = $bukti->pemesanan;

            if (! $disetujui) {
                $pemesanan->update([
                    'status_pembayaran' => 'Ditolak',
                ]);
            }

            $this->evaluasiPenyelesaianOtomatis($pemesanan);

            return $bukti;
        });
    }

    /**
     * Alur 8: Evaluasi Otomatis Penyelesaian.
     * Kalau KEDUANYA (Work Order & Bukti Pembayaran terbaru) Disetujui:
     * - status_pengerjaan = 'Done'
     * - status_pembayaran = 'Lunas'
     * - tgl_selesai = now()
     * Selama belum keduanya disetujui: tetap On-Going & Belum Lunas (atau Ditolak jika bukti ditolak).
     */
    public function evaluasiPenyelesaianOtomatis(Pemesanan $pemesanan): bool
    {
        $latestWo = $pemesanan->latestWorkOrder();
        $latestBukti = $pemesanan->latestBuktiPembayaran();

        if (
            $latestWo && $latestWo->status_verifikasi === 'Disetujui' &&
            $latestBukti && $latestBukti->status_verifikasi === 'Disetujui'
        ) {
            // Selaraskan tgl_selesai dengan tgl_pengerjaan jika ada, agar pada laporan tanggalnya sama
            $tglKerja = $pemesanan->jadwalPenugasan?->tgl_pengerjaan;
            $tglSelesai = $tglKerja
                ? Carbon::parse($tglKerja)->setTime(now()->hour, now()->minute, now()->second)
                : now();

            $pemesanan->update([
                'status_pengerjaan' => 'Done',
                'status_pembayaran' => 'Lunas',
                'tgl_selesai' => $tglSelesai,
            ]);

            return true;
        }

        return false;
    }

    /**
     * Alur 10: Admin membatalkan pemesanan.
     */
    public function batalkanPemesanan(
        Pemesanan $pemesanan,
        string $dibatalkanOleh,
        string $alasan
    ): Pemesanan {
        return DB::transaction(function () use ($pemesanan, $dibatalkanOleh, $alasan) {
            $pemesanan->update([
                'status_pengerjaan' => 'Cancel',
                'status_pembayaran' => 'Ditolak',
                'dibatalkan_oleh' => $dibatalkanOleh,
                'alasan_pembatalan' => $alasan,
            ]);

            return $pemesanan;
        });
    }
}
