<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreTeknisiRequest;
use App\Http\Requests\Admin\UpdateTeknisiRequest;
use App\Models\Teknisi;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class TeknisiController extends Controller
{
    /**
     * Tampilkan daftar teknisi beserta status akun dan fitur pencarian.
     */
    public function index(Request $request): View
    {
        $search = trim($request->query('search', ''));
        $query = Teknisi::latest('id_pengguna');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")
                    ->orWhere('no_hp', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $teknisis = $query->paginate(10)->withQueryString();

        return view('admin.teknisi.index', [
            'teknisis' => $teknisis,
            'search' => $search,
        ]);
    }

    /**
     * Tampilkan form pembuatan teknisi baru dan akun login.
     */
    public function create(): View
    {
        return view('admin.teknisi.create');
    }

    /**
     * Simpan teknisi baru dan buat akun login role teknisi.
     */
    public function store(StoreTeknisiRequest $request): RedirectResponse
    {
        $isActive = $request->has('is_active') ? $request->boolean('is_active') : true;
        $nama = $request->input('nama');
        $email = $request->input('email') ?: (Str::slug($nama).random_int(100, 9999).'@ambius.com');
        $password = $request->filled('password') ? bcrypt($request->input('password')) : bcrypt('password');

        Teknisi::create([
            'nama' => $nama,
            'email' => $email,
            'no_telp' => $request->input('no_telp'),
            'password' => $password,
            'is_active' => $isActive,
        ]);

        return redirect()->route('admin.teknisi.index')
            ->with('success', 'Teknisi dan akun login baru berhasil ditambahkan.');
    }

    /**
     * Tampilkan form edit data teknisi dan akun login.
     */
    public function edit(Teknisi $teknisi): View
    {
        return view('admin.teknisi.edit', [
            'teknisi' => $teknisi,
            'pengguna' => $teknisi,
        ]);
    }

    /**
     * Perbarui data teknisi dan akun login.
     */
    public function update(UpdateTeknisiRequest $request, Teknisi $teknisi): RedirectResponse
    {
        $data = [
            'nama' => $request->input('nama'),
            'no_telp' => $request->input('no_telp'),
            'is_active' => $request->boolean('is_active'),
        ];

        if ($request->filled('email')) {
            $data['email'] = $request->input('email');
        }

        if ($request->filled('password')) {
            $data['password'] = bcrypt($request->input('password'));
        }

        $teknisi->update($data);

        return redirect()->route('admin.teknisi.index')
            ->with('success', 'Data teknisi dan akun berhasil diperbarui.');
    }

    /**
     * Hapus atau nonaktifkan teknisi.
     */
    public function destroy(Teknisi $teknisi): RedirectResponse
    {
        $hasOrders = $teknisi->jadwalPenugasans()->exists();

        if ($hasOrders) {
            $teknisi->update(['is_active' => false]);

            return redirect()->route('admin.teknisi.index')
                ->with('success', 'Teknisi ini memiliki riwayat pemesanan, status diubah menjadi non-aktif.');
        }

        $teknisi->delete();

        return redirect()->route('admin.teknisi.index')
            ->with('success', 'Data teknisi berhasil dihapus.');
    }
}
