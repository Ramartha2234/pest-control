<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateProfileRequest;
use App\Models\Pengaturan;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Show the form for editing the admin profile.
     */
    public function edit(): View
    {
        return view('admin.profile.edit', [
            'user' => auth()->user(),
            'bankName' => Pengaturan::get('bank_name', config('pestcontrol.bank_name')),
            'bankAccountNumber' => Pengaturan::get('bank_account_number', config('pestcontrol.bank_account_number')),
            'bankAccountHolder' => Pengaturan::get('bank_account_holder', config('pestcontrol.bank_account_holder')),
            'whatsappNumber' => Pengaturan::get('whatsapp_number', config('pestcontrol.whatsapp_number')),
            'namaAccounting' => Pengaturan::get('nama_accounting', config('pestcontrol.nama_accounting', 'Siti Rahmawati, S.Ak.')),
            'namaDirektur' => Pengaturan::get('nama_direktur', config('pestcontrol.nama_direktur', 'Budi Santoso, S.T.')),
        ]);
    }

    /**
     * Update the admin profile.
     */
    public function update(UpdateProfileRequest $request): RedirectResponse
    {
        /** @var User $user */
        $user = auth()->user();

        $data = $request->safe()->only(['name', 'email']);

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->input('password'));
        }

        $user->update($data);

        // Simpan pengaturan rekening bank dan WhatsApp operasional
        if ($request->has('bank_name')) {
            Pengaturan::set('bank_name', $request->input('bank_name'));
        }
        if ($request->has('bank_account_number')) {
            Pengaturan::set('bank_account_number', $request->input('bank_account_number'));
        }
        if ($request->has('bank_account_holder')) {
            Pengaturan::set('bank_account_holder', $request->input('bank_account_holder'));
        }
        if ($request->has('whatsapp_number')) {
            Pengaturan::set('whatsapp_number', $request->input('whatsapp_number'));
        }
        if ($request->has('nama_accounting')) {
            Pengaturan::set('nama_accounting', $request->input('nama_accounting'));
        }
        if ($request->has('nama_direktur')) {
            Pengaturan::set('nama_direktur', $request->input('nama_direktur'));
        }

        return redirect()->route('admin.profile.edit')
            ->with('success', 'Pengaturan sistem dan akun Anda berhasil diperbarui.');
    }
}
