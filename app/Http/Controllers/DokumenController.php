<?php

namespace App\Http\Controllers;

use App\Models\BuktiKehadiran;
use App\Models\BuktiPembayaran;
use App\Models\Pemesanan;
use App\Models\WorkOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DokumenController extends Controller
{
    /**
     * Unduh / Tampilkan foto bukti kehadiran (Survei / Pengerjaan).
     */
    public function showBuktiKehadiran(Request $request, BuktiKehadiran $buktiKehadiran): BinaryFileResponse
    {
        $this->authorizeDocumentAccess($request, $buktiKehadiran->id_pemesanan, $buktiKehadiran->id_teknisi, $buktiKehadiran->status_verifikasi);

        $path = $this->resolveFilePath($buktiKehadiran->foto);

        return response()->file($path);
    }

    /**
     * Unduh / Tampilkan dokumen Work Order (WO).
     */
    public function showWorkOrder(Request $request, WorkOrder $workOrder): BinaryFileResponse
    {
        $this->authorizeDocumentAccess($request, $workOrder->id_pemesanan, $workOrder->id_teknisi, $workOrder->status_verifikasi);

        $path = $this->resolveFilePath($workOrder->file_wo);

        return response()->file($path);
    }

    /**
     * Unduh / Tampilkan dokumen / foto bukti pembayaran.
     */
    public function showBuktiPembayaran(Request $request, BuktiPembayaran $buktiPembayaran): BinaryFileResponse
    {
        $this->authorizeDocumentAccess($request, $buktiPembayaran->id_pemesanan, $buktiPembayaran->id_teknisi, $buktiPembayaran->status_verifikasi);

        $path = $this->resolveFilePath($buktiPembayaran->file_bukti);

        return response()->file($path);
    }

    /**
     * Verifikasi otorisasi akses file dokumen privat.
     * - Admin: Akses penuh
     * - Teknisi: Hanya jika ditugaskan pada pemesanan ini
     * - Pelanggan: Hanya jika menyediakan kode unik pemesanan yang valid & dokumen telah 'Disetujui'
     */
    protected function authorizeDocumentAccess(Request $request, int $idPemesanan, int $idTeknisi, string $statusVerifikasi): void
    {
        if (Auth::check()) {
            $user = Auth::user();
            if ($user->isAdmin()) {
                return;
            }

            if ($user->isTeknisi() && (int) $user->id_pengguna === (int) $idTeknisi) {
                return;
            }
        }

        // Akses oleh pelanggan via parameter ?kode=AMB-XXXXXX
        $kode = $request->query('kode') ?? $request->header('X-Kode-Pemesanan');
        if ($kode) {
            $pemesanan = Pemesanan::where('id_pemesanan', $idPemesanan)
                ->where(function ($q) use ($kode) {
                    $q->where('kode_pemesanan', $kode)
                        ->orWhere('kode_pemesanan', strtoupper($kode));
                })
                ->first();

            if ($pemesanan) {
                // Aturan Checklist: Hanya dokumen dengan status 'Disetujui' yang bisa diunduh oleh pelanggan
                if ($statusVerifikasi !== 'Disetujui') {
                    abort(403, 'Dokumen ini belum berstatus Disetujui sehingga belum dapat diunduh.');
                }

                return;
            }
        }

        abort(403, 'Anda tidak memiliki hak akses untuk melihat atau mengunduh dokumen ini.');
    }

    /**
     * Temukan path absolut file pada storage lokal.
     */
    protected function resolveFilePath(string $relativePath): string
    {
        if (Storage::disk('local')->exists($relativePath)) {
            return Storage::disk('local')->path($relativePath);
        }

        if (Storage::disk('public')->exists($relativePath)) {
            return Storage::disk('public')->path($relativePath);
        }

        if (file_exists(storage_path('app/'.$relativePath))) {
            return storage_path('app/'.$relativePath);
        }

        if (file_exists(storage_path('app/public/'.$relativePath))) {
            return storage_path('app/public/'.$relativePath);
        }

        abort(404, 'File dokumen tidak ditemukan pada server.');
    }
}
