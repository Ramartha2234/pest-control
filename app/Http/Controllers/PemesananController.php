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
        $this->statusService->autoCancelExpired();
        $pemesanan->refresh();

        $pemesanan->load(['pelanggan', 'tarifLayanan']);

        return view('pemesanan.confirmation', [
            'pemesanan' => $pemesanan,
        ]);
    }

    /**
     * Upload bukti transfer Down Payment (DP 10%) oleh pelanggan.
     */
    public function uploadDp(Request $request, Pemesanan $pemesanan): RedirectResponse
    {
        $request->validate([
            'bukti_dp' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'kode_pemesanan' => ['nullable', 'string'],
        ], [
            'bukti_dp.required' => 'File bukti transfer DP wajib diunggah.',
            'bukti_dp.image' => 'File bukti transfer harus berupa gambar (JPG, JPEG, PNG, WEBP).',
            'bukti_dp.max' => 'Ukuran file bukti transfer maksimal 5 MB.',
        ]);

        if (! $pemesanan->wajib_dp) {
            return back()->with('error', 'Pemesanan ini tidak mewajibkan pembayaran Down Payment (DP).');
        }

        if ($pemesanan->status_dp === 'Lunas') {
            return back()->with('warning', 'Pembayaran Down Payment (DP) untuk pesanan ini sudah diverifikasi lunas.');
        }

        $path = $request->file('bukti_dp')->store('bukti_dp', 'public');

        $this->statusService->uploadBuktiDp($pemesanan, $path);

        return back()->with('success', 'Bukti transfer DP 10% berhasil diunggah! Mohon menunggu admin memverifikasi pembayaran Anda.');
    }
}
