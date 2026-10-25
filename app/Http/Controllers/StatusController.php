<?php

namespace App\Http\Controllers;

use App\Models\Pemesanan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StatusController extends Controller
{
    /**
     * Tampilkan halaman pelacakan status pesanan pelanggan.
     */
    public function index(Request $request): View|RedirectResponse
    {
        $code = trim($request->query('code', ''));
        $pemesanan = null;

        if ($code !== '') {
            $pemesanan = Pemesanan::with([
                'pelanggan',
                'tarifLayanan',
                'jadwalPenugasan.teknisi',
                'buktiKehadirans',
                'workOrders',
                'buktiPembayarans',
                'hasilSurveis',
            ])
                ->where(function ($query) use ($code) {
                    $query->where('kode_pemesanan', $code)
                        ->orWhere('kode_pemesanan', strtoupper($code));
                })
                ->first();

            if (! $pemesanan) {
                return redirect()->route('status.index')
                    ->with('error', 'Kode tidak ditemukan. Harap periksa kembali kode pemesanan Anda atau hubungi admin kami melalui WhatsApp jika membutuhkan bantuan.');
            }
        }

        return view('status.index', [
            'pemesanan' => $pemesanan,
            'code' => $code,
        ]);
    }

    /**
     * Form submit pencarian status (POST) -> redirect ke index dengan parameter code.
     */
    public function show(Request $request): RedirectResponse
    {
        $request->validate([
            'kode_unik' => ['required', 'string', 'max:50'],
        ], [
            'kode_unik.required' => 'Masukkan kode unik pemesanan Anda.',
        ]);

        return redirect()->route('status.index', [
            'code' => strtoupper(trim($request->input('kode_unik'))),
        ]);
    }
}
