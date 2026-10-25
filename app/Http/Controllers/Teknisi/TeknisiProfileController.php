<?php

namespace App\Http\Controllers\Teknisi;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class TeknisiProfileController extends Controller
{
    /**
     * Tampilkan profil singkat teknisi dan form ubah password.
     */
    public function edit(): View
    {
        return view('teknisi.profil', [
            'user' => Auth::user(),
        ]);
    }

    /**
     * Data kontak teknisi hanya dapat diperbarui oleh administrator.
     */
    public function update(Request $request): RedirectResponse
    {
        return redirect()->route('teknisi.profil.edit')
            ->with('error', 'Data kontak teknisi hanya dapat diubah oleh Administrator melalui panel admin.');
    }

    /**
     * Perbarui password teknisi.
     */
    public function updatePassword(Request $request): RedirectResponse
    {
        $user = Auth::user();

        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ], [
            'current_password.required' => 'Password saat ini wajib diisi.',
            'current_password.current_password' => 'Password saat ini tidak sesuai.',
            'password.required' => 'Password baru wajib diisi.',
            'password.confirmed' => 'Konfirmasi password baru tidak cocok.',
            'password.min' => 'Password baru minimal harus 8 karakter.',
        ]);

        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        return redirect()->route('teknisi.profil.edit')
            ->with('success', 'Password Anda berhasil diperbarui.');
    }
}
