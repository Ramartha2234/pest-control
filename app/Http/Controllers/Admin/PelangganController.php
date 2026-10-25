<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Pelanggan;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PelangganController extends Controller
{
    /**
     * Tampilkan daftar seluruh pelanggan beserta fitur pencarian.
     */
    public function index(Request $request): View
    {
        $search = trim($request->query('search', ''));

        $query = Pelanggan::withCount('pemesanans')
            ->latest('id_pelanggan');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")
                    ->orWhere('no_hp', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('nama_usaha', 'like', "%{$search}%")
                    ->orWhere('alamat', 'like', "%{$search}%");
            });
        }

        $pelanggans = $query->paginate(15)->withQueryString();

        return view('admin.pelanggan.index', [
            'pelanggans' => $pelanggans,
            'search' => $search,
        ]);
    }

    /**
     * Tampilkan detail riwayat pemesanan pelanggan.
     */
    public function show(Pelanggan $pelanggan): View
    {
        $pelanggan->load(['pemesanans' => function ($q) {
            $q->with(['tarifLayanan', 'jadwalPenugasan.teknisi'])->latest('id_pemesanan');
        }]);

        return view('admin.pelanggan.show', [
            'pelanggan' => $pelanggan,
        ]);
    }
}
