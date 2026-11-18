<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BuktiKehadiran;
use App\Models\BuktiPembayaran;
use App\Models\HasilSurvei;
use App\Models\Pemesanan;
use App\Models\WorkOrder;
use App\Services\PemesananStatusService;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        protected PemesananStatusService $statusService
    ) {}

    /**
     * Tampilkan dashboard ringkasan eksekutif admin.
     */
    public function index(): View
    {
        // Jalankan auto-cancel real-time untuk pemesanan yang melewati batas konfirmasi
        $this->statusService->autoCancelExpired();

        $now = Carbon::now();

        // 1. Rekap status pengerjaan
        $counts = [
            'pending_verifikasi' => Pemesanan::where('status_pengerjaan', 'Pending')->whereNull('tgl_verifikasi')->count(),
            'pending_survei' => Pemesanan::where('status_pengerjaan', 'Pending')
                ->whereNotNull('tgl_verifikasi')
                ->whereDoesntHave('jadwalPenugasan', fn ($q) => $q->whereNotNull('tgl_pengerjaan'))
                ->whereDoesntHave('hasilSurveis', fn ($q) => $q->where('status_verifikasi', 'Disetujui'))
                ->count(),
            'pending_belum_survey' => Pemesanan::where('status_pengerjaan', 'Pending')
                ->whereDoesntHave('jadwalPenugasan', fn ($q) => $q->whereNotNull('tgl_pengerjaan'))
                ->whereDoesntHave('hasilSurveis', fn ($q) => $q->where('status_verifikasi', 'Disetujui'))
                ->count(),
            'pending_sudah_survey' => Pemesanan::where('status_pengerjaan', 'Pending')
                ->where(function ($q) {
                    $q->whereHas('jadwalPenugasan', fn ($sq) => $sq->whereNotNull('tgl_pengerjaan'))
                        ->orWhereHas('hasilSurveis', fn ($sq) => $sq->where('status_verifikasi', 'Disetujui'));
                })
                ->count(),
            'ongoing' => Pemesanan::whereIn('status_pengerjaan', ['On-Going', 'ongoing', 'Ongoing'])->count(),
            'done' => Pemesanan::whereIn('status_pengerjaan', ['Done', 'done'])->count(),
            'cancel' => Pemesanan::whereIn('status_pengerjaan', ['Cancel', 'cancel'])->count(),
        ];

        // 2. Pendapatan bulan berjalan (hanya Done + Lunas)
        $currentMonthOrders = Pemesanan::where('status_pengerjaan', 'Done')
            ->where('status_pembayaran', 'Lunas')
            ->where(function ($q) use ($now) {
                $q->where(function ($sub) use ($now) {
                    $sub->whereMonth('tgl_selesai', $now->month)
                        ->whereYear('tgl_selesai', $now->year);
                })->orWhere(function ($sub) use ($now) {
                    $sub->whereNull('tgl_selesai')
                        ->whereMonth('created_at', $now->month)
                        ->whereYear('created_at', $now->year);
                });
            })
            ->get();

        $currentMonthRevenue = $currentMonthOrders->sum(fn ($item) => (float) ($item->harga_final ?? $item->harga_estimasi));
        $currentMonthOrdersCount = Pemesanan::where(function ($q) use ($now) {
            $q->whereMonth('tgl_pemesanan', $now->month)
                ->whereYear('tgl_pemesanan', $now->year)
                ->orWhere(function ($sub) use ($now) {
                    $sub->whereMonth('created_at', $now->month)
                        ->whereYear('created_at', $now->year);
                });
        })->count();

        // 3. Antrean Verifikasi Baru (Belum diverifikasi admin)
        $unverifiedBookings = Pemesanan::with(['pelanggan', 'tarifLayanan'])
            ->where('status_pengerjaan', 'Pending')
            ->whereNull('tgl_verifikasi')
            ->orderBy('tgl_pemesanan', 'asc')
            ->limit(5)
            ->get();

        // 4. Jadwal Survey (hanya yang sudah ACC admin & belum selesai tahap survei)
        $surveyBookings = Pemesanan::with(['pelanggan', 'tarifLayanan', 'jadwalPenugasan.teknisi'])
            ->where('status_pengerjaan', 'Pending')
            ->whereNotNull('tgl_verifikasi')
            ->whereDoesntHave('jadwalPenugasan', fn ($q) => $q->whereNotNull('tgl_pengerjaan'))
            ->whereDoesntHave('hasilSurveis', fn ($q) => $q->where('status_verifikasi', 'Disetujui'))
            ->get()
            ->sortBy(fn ($item) => $item->jadwalPenugasan?->tgl_survei ?? $item->tgl_survei_diminta)
            ->take(10)
            ->values();

        // 5. Jadwal Pengerjaan (hanya yang sudah terjadwal pengerjaan & belum selesai/batal)
        $scheduledBookings = Pemesanan::with(['pelanggan', 'tarifLayanan', 'jadwalPenugasan.teknisi'])
            ->whereHas('jadwalPenugasan', fn ($q) => $q->whereNotNull('tgl_pengerjaan'))
            ->whereNotIn('status_pengerjaan', ['Done', 'Cancel'])
            ->get()
            ->sortBy(fn ($item) => $item->jadwalPenugasan->tgl_pengerjaan)
            ->take(10)
            ->values();

        // 6. Antrean Verifikasi Lapangan, Dokumen, Pembayaran & DP (Menunggu Verifikasi Admin)
        $pendingKehadiran = BuktiKehadiran::with(['pemesanan.pelanggan', 'pemesanan.tarifLayanan', 'teknisi'])
            ->where('status_verifikasi', 'Menunggu')
            ->whereHas('pemesanan', fn ($q) => $q->whereNotIn('status_pengerjaan', ['Cancel']))
            ->latest('created_at')
            ->get();

        $pendingHasilSurvei = HasilSurvei::with(['pemesanan.pelanggan', 'pemesanan.tarifLayanan', 'teknisi'])
            ->where('status_verifikasi', 'Menunggu')
            ->whereHas('pemesanan', fn ($q) => $q->whereNotIn('status_pengerjaan', ['Cancel']))
            ->latest('created_at')
            ->get();

        $pendingWorkOrders = WorkOrder::with(['pemesanan.pelanggan', 'pemesanan.tarifLayanan', 'teknisi'])
            ->where('status_verifikasi', 'Menunggu')
            ->whereHas('pemesanan', fn ($q) => $q->whereNotIn('status_pengerjaan', ['Cancel']))
            ->latest('created_at')
            ->get();

        $pendingPembayaran = BuktiPembayaran::with(['pemesanan.pelanggan', 'pemesanan.tarifLayanan', 'teknisi'])
            ->where('status_verifikasi', 'Menunggu')
            ->whereHas('pemesanan', fn ($q) => $q->whereNotIn('status_pengerjaan', ['Cancel']))
            ->latest('created_at')
            ->get();

        $pendingDp = Pemesanan::with(['pelanggan', 'tarifLayanan'])
            ->where('wajib_dp', true)
            ->where('status_dp', 'Menunggu Verifikasi')
            ->whereNotIn('status_pengerjaan', ['Done', 'Cancel'])
            ->latest('tgl_bayar_dp')
            ->get();

        $pendingVerifications = [
            'kehadiran' => $pendingKehadiran->count(),
            'hasil_survei' => $pendingHasilSurvei->count(),
            'work_order' => $pendingWorkOrders->count(),
            'pembayaran' => $pendingPembayaran->count(),
            'dp' => $pendingDp->count(),
        ];

        $verificationQueue = collect()
            ->merge($pendingKehadiran->map(fn (BuktiKehadiran $item): array => [
                'kategori' => $item->jenis_kehadiran === 'Pengerjaan' ? 'Kehadiran Pengerjaan' : 'Kehadiran Survei',
                'badge_class' => $item->jenis_kehadiran === 'Pengerjaan'
                    ? 'bg-blue-50 text-blue-700 border-blue-200'
                    : 'bg-cyan-50 text-cyan-700 border-cyan-200',
                'id_pemesanan' => $item->id_pemesanan,
                'kode_pemesanan' => $item->pemesanan?->kode_pemesanan ?? '-',
                'pelanggan' => $item->pemesanan?->pelanggan?->nama ?? '-',
                'layanan' => $item->pemesanan?->tarifLayanan?->jenis_layanan ?? '-',
                'pengirim' => $item->teknisi?->nama ?? 'Teknisi',
                'keterangan' => $item->catatan ? Str::limit($item->catatan, 60) : 'Foto kehadiran '.$item->jenis_kehadiran.' menunggu verifikasi',
                'waktu' => $item->created_at,
            ]))
            ->merge($pendingHasilSurvei->map(fn (HasilSurvei $item): array => [
                'kategori' => 'Hasil Survei',
                'badge_class' => 'bg-indigo-50 text-indigo-700 border-indigo-200',
                'id_pemesanan' => $item->id_pemesanan,
                'kode_pemesanan' => $item->pemesanan?->kode_pemesanan ?? '-',
                'pelanggan' => $item->pemesanan?->pelanggan?->nama ?? '-',
                'layanan' => $item->pemesanan?->tarifLayanan?->jenis_layanan ?? '-',
                'pengirim' => $item->teknisi?->nama ?? 'Teknisi',
                'keterangan' => 'Jadwal: '.($item->tgl_pengerjaan_diajukan?->format('d M Y') ?? '-').' • Rp '.number_format((float) $item->harga_diajukan, 0, ',', '.'),
                'waktu' => $item->created_at,
            ]))
            ->merge($pendingWorkOrders->map(fn (WorkOrder $item): array => [
                'kategori' => 'Work Order (WO)',
                'badge_class' => 'bg-purple-50 text-purple-700 border-purple-200',
                'id_pemesanan' => $item->id_pemesanan,
                'kode_pemesanan' => $item->pemesanan?->kode_pemesanan ?? '-',
                'pelanggan' => $item->pemesanan?->pelanggan?->nama ?? '-',
                'layanan' => $item->pemesanan?->tarifLayanan?->jenis_layanan ?? '-',
                'pengirim' => $item->teknisi?->nama ?? 'Teknisi',
                'keterangan' => $item->catatan ? Str::limit($item->catatan, 60) : 'Dokumen Work Order menunggu verifikasi',
                'waktu' => $item->created_at,
            ]))
            ->merge($pendingPembayaran->map(fn (BuktiPembayaran $item): array => [
                'kategori' => 'Bukti Pembayaran',
                'badge_class' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                'id_pemesanan' => $item->id_pemesanan,
                'kode_pemesanan' => $item->pemesanan?->kode_pemesanan ?? '-',
                'pelanggan' => $item->pemesanan?->pelanggan?->nama ?? '-',
                'layanan' => $item->pemesanan?->tarifLayanan?->jenis_layanan ?? '-',
                'pengirim' => $item->teknisi?->nama ?? 'Teknisi',
                'keterangan' => ($item->metode_pembayaran ?? 'Pembayaran').' • Rp '.number_format((float) $item->jumlah_bayar, 0, ',', '.'),
                'waktu' => $item->created_at,
            ]))
            ->merge($pendingDp->map(fn (Pemesanan $item): array => [
                'kategori' => 'Bukti DP (10%)',
                'badge_class' => 'bg-amber-50 text-amber-800 border-amber-200',
                'id_pemesanan' => $item->id_pemesanan,
                'kode_pemesanan' => $item->kode_pemesanan,
                'pelanggan' => $item->pelanggan?->nama ?? '-',
                'layanan' => $item->tarifLayanan?->jenis_layanan ?? '-',
                'pengirim' => $item->pelanggan?->nama ?? 'Pelanggan',
                'keterangan' => 'Nominal DP: Rp '.number_format((float) $item->nominal_dp, 0, ',', '.'),
                'waktu' => $item->tgl_bayar_dp ?? $item->updated_at,
            ]))
            ->sortByDesc('waktu')
            ->values();

        // 7. Pekerjaan Berlangsung Hari Ini
        $todayDate = $now->format('Y-m-d');

        $todayBookings = Pemesanan::with(['pelanggan', 'tarifLayanan', 'jadwalPenugasan.teknisi'])
            ->whereNotIn('status_pengerjaan', ['Done', 'Cancel'])
            ->where(function ($query) use ($todayDate) {
                $query->where('status_pengerjaan', 'On-Going')
                    ->orWhereHas('jadwalPenugasan', fn ($q) => $q->whereDate('tgl_pengerjaan', $todayDate))
                    ->orWhere(function ($sq) use ($todayDate) {
                        $sq->whereHas('jadwalPenugasan', function ($jq) use ($todayDate) {
                            $jq->whereDate('tgl_survei', $todayDate)
                                ->whereNull('tgl_pengerjaan');
                        })->whereDoesntHave('hasilSurveis', fn ($hs) => $hs->where('status_verifikasi', 'Disetujui'));
                    });
            })
            ->get()
            ->sortBy(function ($item) {
                $isPengerjaan = $item->status_pengerjaan === 'On-Going' || $item->jadwalPenugasan?->tgl_pengerjaan !== null;
                $jam = $isPengerjaan
                    ? ($item->jadwalPenugasan?->jam_pengerjaan ?? '23:59')
                    : ($item->jadwalPenugasan?->jam_survei ?? '23:59');

                return ($item->status_pengerjaan === 'On-Going' ? '0-' : '1-').$jam;
            })
            ->values();

        // 8. Agenda Besok
        $tomorrow = Carbon::tomorrow();
        $tomorrowDate = $tomorrow->format('Y-m-d');

        $tomorrowSurveysCount = Pemesanan::where(function ($q) use ($tomorrowDate) {
            $q->whereHas('jadwalPenugasan', fn ($j) => $j->whereDate('tgl_survei', $tomorrowDate)->whereNull('tgl_pengerjaan'))
                ->orWhere(function ($reqQ) use ($tomorrowDate) {
                    $reqQ->whereDoesntHave('jadwalPenugasan')
                        ->whereDate('tgl_survei_diminta', $tomorrowDate);
                });
        })
            ->whereDoesntHave('hasilSurveis', fn ($hs) => $hs->where('status_verifikasi', 'Disetujui'))
            ->whereNotIn('status_pengerjaan', ['Cancel', 'Done'])
            ->count();

        $tomorrowTreatmentsCount = Pemesanan::whereHas('jadwalPenugasan', fn ($q) => $q->whereDate('tgl_pengerjaan', $tomorrowDate))
            ->whereNotIn('status_pengerjaan', ['Cancel', 'Done'])
            ->count();

        $tomorrowBookings = Pemesanan::with(['pelanggan', 'tarifLayanan', 'jadwalPenugasan.teknisi'])
            ->where(function ($query) use ($tomorrowDate) {
                $query->whereHas('jadwalPenugasan', fn ($q) => $q->whereDate('tgl_pengerjaan', $tomorrowDate))
                    ->orWhere(function ($sq) use ($tomorrowDate) {
                        $sq->where(function ($dateQ) use ($tomorrowDate) {
                            $dateQ->whereHas('jadwalPenugasan', fn ($jq) => $jq->whereDate('tgl_survei', $tomorrowDate)->whereNull('tgl_pengerjaan'))
                                ->orWhere(function ($reqQ) use ($tomorrowDate) {
                                    $reqQ->whereDoesntHave('jadwalPenugasan')
                                        ->whereDate('tgl_survei_diminta', $tomorrowDate);
                                });
                        })->whereDoesntHave('hasilSurveis', fn ($hs) => $hs->where('status_verifikasi', 'Disetujui'));
                    });
            })
            ->whereNotIn('status_pengerjaan', ['Cancel', 'Done'])
            ->latest('id_pemesanan')
            ->get();

        return view('admin.dashboard', [
            'counts' => $counts,
            'currentMonthRevenue' => $currentMonthRevenue,
            'currentMonthOrdersCount' => $currentMonthOrdersCount,
            'unverifiedBookings' => $unverifiedBookings,
            'surveyBookings' => $surveyBookings,
            'scheduledBookings' => $scheduledBookings,
            'pendingVerifications' => $pendingVerifications,
            'verificationQueue' => $verificationQueue,
            'todayDate' => $now,
            'todayBookings' => $todayBookings,
            'monthName' => $now->translatedFormat('F Y'),
            'tomorrowSurveysCount' => $tomorrowSurveysCount,
            'tomorrowTreatmentsCount' => $tomorrowTreatmentsCount,
            'tomorrowDate' => $tomorrow,
            'tomorrowBookings' => $tomorrowBookings,
        ]);
    }
}
