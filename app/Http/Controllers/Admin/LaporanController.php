<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Pemesanan;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LaporanController extends Controller
{
    /**
     * Tampilkan laporan pendapatan bulanan dan tahunan (HANYA status Done + Lunas)
     * Lengkap dengan nama pelanggan, teknisi, jadwal, link WO & bukti bayar.
     */
    public function index(Request $request): View
    {
        $viewType = $request->query('view_type', 'monthly'); // monthly / yearly
        $selectedYear = (int) $request->query('year', now()->year);
        $selectedMonth = $request->query('month', now()->format('m'));

        // Query dasar: HANYA pemesanan dengan status_pengerjaan = Done dan status_pembayaran = Lunas
        $baseQuery = Pemesanan::with([
            'pelanggan',
            'tarifLayanan',
            'jadwalPenugasan.teknisi',
            'workOrders' => fn ($q) => $q->where('status_verifikasi', 'Disetujui')->latest('id_wo'),
            'buktiPembayarans' => fn ($q) => $q->where('status_verifikasi', 'Disetujui')->latest('id_bukti_pembayaran'),
        ])
            ->where('status_pengerjaan', 'Done')
            ->where('status_pembayaran', 'Lunas');

        if ($viewType === 'yearly') {
            $baseQuery->where(function ($q) use ($selectedYear) {
                $q->whereYear('tgl_selesai', $selectedYear)
                    ->orWhere(function ($sub) use ($selectedYear) {
                        $sub->whereNull('tgl_selesai')
                            ->whereYear('created_at', $selectedYear);
                    });
            });
        } else {
            $baseQuery->where(function ($q) use ($selectedYear, $selectedMonth) {
                $q->where(function ($sub) use ($selectedYear, $selectedMonth) {
                    $sub->whereYear('tgl_selesai', $selectedYear)
                        ->whereMonth('tgl_selesai', $selectedMonth);
                })->orWhere(function ($sub) use ($selectedYear, $selectedMonth) {
                    $sub->whereNull('tgl_selesai')
                        ->whereYear('created_at', $selectedYear)
                        ->whereMonth('created_at', $selectedMonth);
                });
            });
        }

        $pemesanans = (clone $baseQuery)
            ->orderBy('tgl_selesai', 'desc')
            ->paginate(20)
            ->withQueryString();

        // Rekap ringkasan total
        $allMatching = (clone $baseQuery)->get();
        $totalRevenue = $allMatching->sum(fn ($item) => (float) ($item->harga_final ?? $item->harga_estimasi));
        $totalOrders = $allMatching->count();
        $avgTicket = $totalOrders > 0 ? ($totalRevenue / $totalOrders) : 0;

        // Tahun tersedia untuk filter (database agnostic)
        $availableYears = Pemesanan::where('status_pengerjaan', 'Done')
            ->where('status_pembayaran', 'Lunas')
            ->whereNotNull('tgl_selesai')
            ->pluck('tgl_selesai')
            ->map(fn ($date) => Carbon::parse($date)->year)
            ->unique()
            ->sortDesc()
            ->values()
            ->toArray();

        if (empty($availableYears)) {
            $availableYears = [now()->year];
        }

        return view('admin.laporan.index', [
            'pemesanans' => $pemesanans,
            'viewType' => $viewType,
            'selectedYear' => $selectedYear,
            'selectedMonth' => str_pad((string) $selectedMonth, 2, '0', STR_PAD_LEFT),
            'totalRevenue' => $totalRevenue,
            'totalOrders' => $totalOrders,
            'avgTicket' => $avgTicket,
            'availableYears' => $availableYears,
        ]);
    }
}
