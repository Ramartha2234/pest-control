<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BuktiKehadiran;
use App\Models\BuktiPembayaran;
use App\Models\HasilSurvei;
use App\Models\Pemesanan;
use App\Models\WorkOrder;
use Carbon\Carbon;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Tampilkan dashboard ringkasan eksekutif admin.
     */
    public function index(): View
    {
        $now = Carbon::now();

        // 1. Rekap status pengerjaan
        $counts = [
            'pending_verifikasi' => Pemesanan::where('status_pengerjaan', 'Pending')->whereNull('tgl_verifikasi')->count(),
            'pending_survei' => Pemesanan::where('status_pengerjaan', 'Pending')->whereNotNull('tgl_verifikasi')->count(),
            'pending_belum_survey' => Pemesanan::where('status_pengerjaan', 'Pending')->whereNull('tgl_verifikasi')->count(),
            'pending_sudah_survey' => Pemesanan::where('status_pengerjaan', 'Pending')->whereNotNull('tgl_verifikasi')->count(),
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

        // 4. Jadwal Survey / Belum Survey
        $surveyBookings = Pemesanan::with(['pelanggan', 'tarifLayanan', 'jadwalPenugasan.teknisi'])
            ->where('status_pengerjaan', 'Pending')
            ->whereNotNull('tgl_verifikasi')
            ->orderBy('tgl_survei_diminta', 'asc')
            ->limit(5)
            ->get();

        // 5. Jadwal Pengerjaan Terdekat
        $scheduledBookings = Pemesanan::with(['pelanggan', 'tarifLayanan', 'jadwalPenugasan.teknisi'])
            ->whereHas('jadwalPenugasan', fn ($q) => $q->whereNotNull('tgl_pengerjaan'))
            ->whereNotIn('status_pengerjaan', ['Done', 'Cancel'])
            ->get()
            ->sortBy(fn ($item) => $item->jadwalPenugasan->tgl_pengerjaan)
            ->take(5);

        // 6. Antrean Verifikasi Lapangan Teknisi (Menunggu Verifikasi Admin)
        $pendingVerifications = [
            'kehadiran' => BuktiKehadiran::with(['pemesanan.pelanggan', 'teknisi'])->where('status_verifikasi', 'Menunggu')->count(),
            'hasil_survei' => HasilSurvei::with(['pemesanan.pelanggan', 'teknisi'])->where('status_verifikasi', 'Menunggu')->count(),
            'work_order' => WorkOrder::with(['pemesanan.pelanggan', 'teknisi'])->where('status_verifikasi', 'Menunggu')->count(),
            'pembayaran' => BuktiPembayaran::with(['pemesanan.pelanggan', 'teknisi'])->where('status_verifikasi', 'Menunggu')->count(),
        ];

        // 7. Agenda Besok
        $tomorrow = Carbon::tomorrow();
        $tomorrowDate = $tomorrow->format('Y-m-d');

        $tomorrowSurveysCount = Pemesanan::where(function ($q) use ($tomorrowDate) {
            $q->whereHas('jadwalPenugasan', fn ($j) => $j->whereDate('tgl_survei', $tomorrowDate))
                ->orWhereDate('tgl_survei_diminta', $tomorrowDate);
        })->whereNotIn('status_pengerjaan', ['Cancel', 'Done'])->count();

        $tomorrowTreatmentsCount = Pemesanan::whereHas('jadwalPenugasan', fn ($q) => $q->whereDate('tgl_pengerjaan', $tomorrowDate))
            ->whereNotIn('status_pengerjaan', ['Cancel', 'Done'])->count();

        $tomorrowBookings = Pemesanan::with(['pelanggan', 'tarifLayanan', 'jadwalPenugasan.teknisi'])
            ->where(function ($query) use ($tomorrowDate) {
                $query->whereHas('jadwalPenugasan', function ($q) use ($tomorrowDate) {
                    $q->whereDate('tgl_survei', $tomorrowDate)
                        ->orWhereDate('tgl_pengerjaan', $tomorrowDate);
                })
                    ->orWhereDate('tgl_survei_diminta', $tomorrowDate);
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
            'monthName' => $now->translatedFormat('F Y'),
            'tomorrowSurveysCount' => $tomorrowSurveysCount,
            'tomorrowTreatmentsCount' => $tomorrowTreatmentsCount,
            'tomorrowDate' => $tomorrow,
            'tomorrowBookings' => $tomorrowBookings,
        ]);
    }
}
