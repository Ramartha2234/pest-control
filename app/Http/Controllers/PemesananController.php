<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePemesananRequest;
use App\Models\Pemesanan;
use App\Models\TarifArea;
use App\Models\TarifLayanan;
use App\Services\PemesananStatusService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PemesananController extends Controller
{
    public function __construct(
        protected PemesananStatusService $statusService
    ) {}

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
        $pemesanan = $this->statusService->buatPemesanan($request->validated());

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
