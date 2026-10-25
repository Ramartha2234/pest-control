<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TarifArea;
use App\Models\TarifLayanan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TarifLayananController extends Controller
{
    /**
     * Tampilkan katalog seluruh tarif layanan dan tarif area.
     */
    public function index(): View
    {
        $tarifs = TarifLayanan::orderBy('status_aktif', 'desc')
            ->orderBy('jenis_layanan')
            ->paginate(15);

        $tarifAreas = TarifArea::orderBy('id_tarif_area')->get();

        return view('admin.tarif.index', [
            'tarifs' => $tarifs,
            'tarifAreas' => $tarifAreas,
        ]);
    }

    /**
     * Form tambah tarif layanan baru.
     */
    public function create(): View
    {
        return view('admin.tarif.create');
    }

    /**
     * Simpan tarif layanan baru.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'jenis_layanan' => ['required', 'string', 'max:255'],
            'jenis_properti' => ['nullable', 'string', 'max:255'],
            'ukuran_area_min' => ['nullable', 'integer', 'min:0'],
            'ukuran_area_max' => ['nullable', 'integer', 'min:0'],
            'satuan' => ['required', 'string', 'max:50'],
            'tarif_dasar' => ['required', 'numeric', 'min:0'],
            'nama_paket' => ['nullable', 'string', 'max:255'],
            'deskripsi' => ['nullable', 'string'],
            'status_aktif' => ['nullable', 'boolean'],
        ], [
            'jenis_layanan.required' => 'Jenis layanan hama wajib diisi.',
            'tarif_dasar.required' => 'Tarif dasar harga wajib diisi.',
            'tarif_dasar.numeric' => 'Tarif dasar harus berupa angka nominal.',
            'satuan.required' => 'Satuan hitung wajib diisi (misal: m2, titik, ruangan).',
        ]);

        $validated['status_aktif'] = $request->boolean('status_aktif', true);
        $validated['ukuran_area_max'] = $request->input('ukuran_area_max', null);

        TarifLayanan::create($validated);

        return redirect()->route('admin.tarif.index')
            ->with('success', 'Tarif layanan baru berhasil ditambahkan.');
    }

    /**
     * Form ubah tarif layanan.
     */
    public function edit(TarifLayanan $tarif): View
    {
        return view('admin.tarif.edit', [
            'tarif' => $tarif,
        ]);
    }

    /**
     * Perbarui data tarif layanan.
     */
    public function update(Request $request, TarifLayanan $tarif): RedirectResponse
    {
        $validated = $request->validate([
            'jenis_layanan' => ['required', 'string', 'max:255'],
            'jenis_properti' => ['nullable', 'string', 'max:255'],
            'ukuran_area_min' => ['nullable', 'integer', 'min:0'],
            'ukuran_area_max' => ['nullable', 'integer', 'min:0'],
            'satuan' => ['required', 'string', 'max:50'],
            'tarif_dasar' => ['required', 'numeric', 'min:0'],
            'nama_paket' => ['nullable', 'string', 'max:255'],
            'deskripsi' => ['nullable', 'string'],
            'status_aktif' => ['nullable', 'boolean'],
        ]);

        $validated['status_aktif'] = $request->boolean('status_aktif');
        $validated['ukuran_area_max'] = $request->input('ukuran_area_max', null);

        $tarif->update($validated);

        return redirect()->route('admin.tarif.index')
            ->with('success', 'Tarif layanan berhasil diperbarui.');
    }

    /**
     * Aktifkan / Nonaktifkan tarif layanan secara instan.
     */
    public function toggleStatus(TarifLayanan $tarif): RedirectResponse
    {
        $tarif->update([
            'status_aktif' => ! $tarif->status_aktif,
        ]);

        $statusTeks = $tarif->status_aktif ? 'diaktifkan' : 'dinonaktifkan';

        return redirect()->route('admin.tarif.index')
            ->with('success', "Tarif layanan {$tarif->jenis_layanan} berhasil {$statusTeks}.");
    }

    /**
     * Nonaktifkan tarif layanan (bukan hapus permanen agar integritas riwayat terjaga).
     */
    public function destroy(TarifLayanan $tarif): RedirectResponse
    {
        $tarif->update(['status_aktif' => false]);

        return redirect()->route('admin.tarif.index')
            ->with('success', "Tarif layanan {$tarif->jenis_layanan} berhasil dinonaktifkan.");
    }

    /**
     * Perbarui data tarif area (Indoor / Outdoor).
     */
    public function updateArea(Request $request, TarifArea $tarifArea): RedirectResponse
    {
        $validated = $request->validate([
            'nama_area' => ['required', 'string', 'max:100'],
            'biaya_tambahan' => ['required', 'numeric', 'min:0'],
            'keterangan' => ['nullable', 'string', 'max:255'],
            'status_aktif' => ['nullable', 'boolean'],
        ], [
            'nama_area.required' => 'Nama area wajib diisi.',
            'biaya_tambahan.required' => 'Biaya tambahan area wajib diisi.',
            'biaya_tambahan.numeric' => 'Biaya tambahan harus berupa angka.',
            'biaya_tambahan.min' => 'Biaya tambahan minimal Rp 0.',
        ]);

        $validated['status_aktif'] = $request->boolean('status_aktif', true);

        $tarifArea->update($validated);

        return redirect()->route('admin.tarif.index')
            ->with('success', "Tarif area {$tarifArea->nama_area} berhasil diperbarui (Biaya: Rp ".number_format($tarifArea->biaya_tambahan, 0, ',', '.').').');
    }

    /**
     * Toggle status aktif tarif area.
     */
    public function toggleAreaStatus(TarifArea $tarifArea): RedirectResponse
    {
        $tarifArea->update([
            'status_aktif' => ! $tarifArea->status_aktif,
        ]);

        $statusTeks = $tarifArea->status_aktif ? 'diaktifkan' : 'dinonaktifkan';

        return redirect()->route('admin.tarif.index')
            ->with('success', "Status area {$tarifArea->nama_area} berhasil {$statusTeks}.");
    }
}
