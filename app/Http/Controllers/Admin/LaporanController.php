<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Pemesanan;
use App\Models\Pengaturan;
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
        $data = $this->getLaporanData($request);

        $pemesanans = (clone $data['baseQuery'])
            ->orderBy('tgl_selesai', 'desc')
            ->paginate(20)
            ->withQueryString();

        return view('admin.laporan.index', array_merge($data, [
            'pemesanans' => $pemesanans,
        ]));
    }

    /**
     * Tampilkan lembar dokumen cetak / ekspor PDF resmi (format kertas A4).
     */
    public function cetak(Request $request): View
    {
        $data = $this->getLaporanData($request);

        $pemesanans = (clone $data['baseQuery'])
            ->orderBy('tgl_selesai', 'asc')
            ->get();

        // Pembagian data ke dalam lembar halaman A4
        $chunks = [];
        if ($pemesanans->count() <= 8) {
            $chunks[] = [
                'items' => $pemesanans,
                'has_header' => true,
                'has_ttd' => true,
                'offset' => 0,
            ];
        } else {
            // Halaman 1: Kop Surat, Judul, Ringkasan, dan 10 baris pertama
            $chunks[] = [
                'items' => $pemesanans->slice(0, 10),
                'has_header' => true,
                'has_ttd' => false,
                'offset' => 0,
            ];
            $currentOffset = 10;
            $remaining = $pemesanans->slice(10)->values();
            while ($remaining->count() > 0) {
                if ($remaining->count() <= 9) {
                    $chunks[] = [
                        'items' => $remaining,
                        'has_header' => false,
                        'has_ttd' => true,
                        'offset' => $currentOffset,
                    ];
                    break;
                } else {
                    $chunks[] = [
                        'items' => $remaining->slice(0, 14),
                        'has_header' => false,
                        'has_ttd' => false,
                        'offset' => $currentOffset,
                    ];
                    $currentOffset += 14;
                    $remaining = $remaining->slice(14)->values();
                }
            }
        }

        return view('admin.laporan.cetak', array_merge($data, [
            'chunks' => $chunks,
            'pemesanans' => $pemesanans,
        ]));
    }

    /**
     * Dapatkan query dan ringkasan data laporan pendapatan.
     *
     * @return array<string, mixed>
     */
    protected function getLaporanData(Request $request): array
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

        // Rekap ringkasan total
        $allMatching = (clone $baseQuery)->get();
        $totalRevenue = $allMatching->sum(fn ($item) => (float) ($item->harga_final ?? $item->harga_estimasi));
        $totalOrders = $allMatching->count();
        $avgTicket = $totalOrders > 0 ? ($totalRevenue / $totalOrders) : 0;
        $totalDp = (float) $allMatching->where('wajib_dp', true)->where('status_dp', 'Lunas')->sum('nominal_dp');
        $totalPelunasan = max(0, $totalRevenue - $totalDp);

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

        $namaAccounting = Pengaturan::get('nama_accounting', config('pestcontrol.nama_accounting', 'Siti Rahmawati, S.Ak.'));
        $namaDirektur = Pengaturan::get('nama_direktur', config('pestcontrol.nama_direktur', 'Budi Santoso, S.T.'));

        return [
            'baseQuery' => $baseQuery,
            'viewType' => $viewType,
            'selectedYear' => $selectedYear,
            'selectedMonth' => str_pad((string) $selectedMonth, 2, '0', STR_PAD_LEFT),
            'totalRevenue' => $totalRevenue,
            'totalOrders' => $totalOrders,
            'avgTicket' => $avgTicket,
            'totalDp' => $totalDp,
            'totalPelunasan' => $totalPelunasan,
            'availableYears' => $availableYears,
            'namaAccounting' => $namaAccounting,
            'namaDirektur' => $namaDirektur,
        ];
    }
}
