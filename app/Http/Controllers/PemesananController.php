<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePemesananRequest;
use App\Models\Pemesanan;
use App\Models\TarifArea;
use App\Models\TarifLayanan;
use App\Services\PemesananStatusService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PemesananController extends Controller
{
    public function __construct(
        protected PemesananStatusService $statusService
    ) {}

    /**
     * Cek daftar slot jam survei yang sudah terisi pada tanggal tertentu (API JSON).
     */
    public function cekSlot(Request $request): JsonResponse
    {
        $tanggal = (string) $request->query('tanggal', '');
        if (empty($tanggal) || ! strtotime($tanggal)) {
            return response()->json([
                'success' => true,
                'tanggal' => $tanggal,
                'slot_terisi' => [],
            ]);
        }

        $slotTerisi = $this->statusService->getSlotTerisiPadaTanggal($tanggal);

        return response()->json([
            'success' => true,
            'tanggal' => $tanggal,
            'slot_terisi' => $slotTerisi,
        ]);
    }

    /**
     * Tampilkan formulir pemesanan online untuk pelanggan.
     */
    public function create(): View
    {
        $tarifList = TarifLayanan::aktif()->get();
        $tarifAreas = TarifArea::aktif()->get()->keyBy('kode_area');

        return view('pemesanan.create', [
            'tarifList' => $tarifList,
            'tarifAreas' => $tarifAreas,
        ]);
    }

    /**
     * Simpan pemesanan baru melalui PemesananStatusService.
     */
    public function store(StorePemesananRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['validasi_slot'] = true;
        $pemesanan = $this->statusService->buatPemesanan($data);

        return redirect()->route('pemesanan.confirmation', $pemesanan->id_pemesanan)
            ->with('success_code', $pemesanan->kode_pemesanan);
    }

    /**
     * Tampilkan halaman konfirmasi sukses pemesanan.
     */
    public function confirmation(Pemesanan $pemesanan): View
    {
        $pemesanan->load(['pelanggan', 'tarifLayanan']);

        return view('pemesanan.confirmation', [
            'pemesanan' => $pemesanan,
        ]);
    }
}
