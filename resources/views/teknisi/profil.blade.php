@extends('layouts.teknisi')

@section('title', 'Profil Teknisi - CV. Ambius Indonesia')

@section('content')
<div class="space-y-4">
    {{-- Header --}}
    <div class="rounded-2xl bg-white p-4 border border-slate-200 shadow-sm flex items-center justify-between">
        <div>
            <h2 class="text-lg font-black text-slate-900">Profil & Keamanan Akun</h2>
            <p class="text-xs text-slate-500">Kelola informasi kontak dan kata sandi akun teknisi Anda.</p>
        </div>
        <a href="{{ route('teknisi.dashboard') }}" class="text-xs font-bold text-emerald-700 hover:underline">
            &larr; Dashboard
        </a>
    </div>

    {{-- Kartu Ringkasan Akun --}}
    <div class="rounded-3xl bg-gradient-to-r from-emerald-800 to-teal-800 p-5 text-white shadow-md">
        <div class="flex items-center gap-4">
            <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-white/20 text-2xl font-black shadow-inner">
                {{ strtoupper(substr($user->nama, 0, 1)) }}
            </div>
            <div>
                <h3 class="text-lg font-black">{{ $user->nama }}</h3>
                <p class="text-xs text-emerald-200">{{ $user->email }}</p>
                <div class="mt-1 flex items-center gap-2">
                    <span class="inline-block rounded-full bg-emerald-900/80 px-2.5 py-0.5 text-[10px] font-bold text-emerald-200">
                        {{ strtoupper($user->role) }}
                    </span>
                    <span class="inline-block rounded-full bg-emerald-500 px-2 py-0.5 text-[10px] font-black text-white">
                        Aktif
                    </span>
                </div>
            </div>
        </div>
    </div>

    {{-- Informasi Data Kontak Teknisi (Read-Only - Dikelola oleh Admin) --}}
    <div class="rounded-3xl border border-slate-200 bg-white p-4 sm:p-5 shadow-sm space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 pb-2">
            <h3 class="text-sm font-extrabold text-slate-800">
                Data Kontak Teknisi
            </h3>
            <span class="inline-flex items-center gap-1 rounded-full bg-slate-100 px-2.5 py-0.5 text-[10px] font-bold text-slate-600">
                <svg class="h-3 w-3 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                </svg>
                Hanya Dapat Diubah Admin
            </span>
        </div>

        <div class="space-y-3">
            <div>
                <span class="block text-[11px] font-bold text-slate-400 uppercase tracking-wider">Nama Lengkap</span>
                <span class="block text-xs font-bold text-slate-800 mt-0.5">{{ $user->nama }}</span>
            </div>

            <div>
                <span class="block text-[11px] font-bold text-slate-400 uppercase tracking-wider">Alamat Email (Login)</span>
                <span class="block text-xs font-bold text-slate-800 mt-0.5">{{ $user->email }}</span>
                <span class="text-[10px] text-slate-400 mt-0.5 block">Alamat email diatur oleh administrator untuk akun login.</span>
            </div>

            <div>
                <span class="block text-[11px] font-bold text-slate-400 uppercase tracking-wider">Nomor WhatsApp / HP</span>
                <span class="block text-xs font-bold text-slate-800 mt-0.5">{{ $user->no_hp ?: '-' }}</span>
                <span class="text-[10px] text-slate-400 mt-0.5 block">Nomor ini digunakan untuk koordinasi penugasan lapangan.</span>
            </div>
        </div>

        <div class="rounded-2xl bg-amber-50 border border-amber-200/80 p-3 text-[11px] text-amber-900 flex items-start gap-2">
            <svg class="h-4 w-4 text-amber-700 mt-0.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <div>
                <b>Perhatian:</b> Data kontak dan identitas teknisi hanya dapat diperbarui oleh <b>Administrator</b> melalui panel manajemen teknisi. Silakan hubungi admin jika terdapat pembaruan nomor kontak atau identitas diri.
            </div>
        </div>
    </div>

    {{-- Form Ubah Password --}}
    <div class="rounded-3xl border border-slate-200 bg-white p-4 sm:p-5 shadow-sm space-y-3">
        <h3 class="text-sm font-extrabold text-slate-800 border-b border-slate-100 pb-2">
            Ubah Kata Sandi
        </h3>

        <form action="{{ route('teknisi.profil.password') }}" method="POST" class="space-y-3">
            @csrf
            @method('PUT')

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Kata Sandi Saat Ini *</label>
                <input type="password" name="current_password" required
                    class="w-full rounded-xl border-slate-300 text-xs focus:border-emerald-500 focus:ring-emerald-500">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Kata Sandi Baru (Min. 8 karakter) *</label>
                <input type="password" name="password" required minlength="8"
                    class="w-full rounded-xl border-slate-300 text-xs focus:border-emerald-500 focus:ring-emerald-500">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Ulangi Kata Sandi Baru *</label>
                <input type="password" name="password_confirmation" required minlength="8"
                    class="w-full rounded-xl border-slate-300 text-xs focus:border-emerald-500 focus:ring-emerald-500">
            </div>

            <button type="submit" class="w-full py-3 rounded-xl bg-slate-800 hover:bg-slate-900 text-white font-bold text-xs shadow-md transition">
                Perbarui Kata Sandi
            </button>
        </form>
    </div>
</div>
@endsection
