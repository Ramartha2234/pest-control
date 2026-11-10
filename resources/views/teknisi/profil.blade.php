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
    <div class="rounded-3xl border border-slate-200 bg-white p-5 sm:p-6 shadow-sm space-y-4">
        <div class="border-b border-slate-100 pb-3">
            <h3 class="text-sm font-extrabold text-slate-800">
                Ubah Kata Sandi
            </h3>
            <p class="text-[11px] text-slate-500 mt-0.5">
                Pastikan kata sandi baru Anda aman dan minimal terdiri dari 8 karakter.
            </p>
        </div>

        <form action="{{ route('teknisi.profil.password') }}" method="POST" class="space-y-4">
            @csrf
            @method('PUT')

            <div>
                <label for="current_password" class="block text-xs font-bold text-slate-700 mb-1.5">
                    Kata Sandi Saat Ini <span class="text-rose-500">*</span>
                </label>
                <div class="relative">
                    <input type="password" name="current_password" id="current_password" required
                        class="block w-full rounded-xl border border-slate-300 bg-white px-4 py-2.5 pr-14 text-xs sm:text-sm text-slate-900 placeholder:text-slate-400 shadow-sm transition focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 focus:outline-none @error('current_password') border-rose-400 ring-1 ring-rose-400 @enderror"
                        style="padding-right: 3.5rem;"
                        placeholder="Masukkan kata sandi saat ini">
                    <button type="button" onclick="togglePasswordVisibility('current_password', this)"
                        class="absolute inset-y-0 flex w-12 items-center justify-center text-slate-400 hover:text-emerald-600 focus:outline-none transition cursor-pointer"
                        style="right: 8px;"
                        title="Lihat / sembunyikan kata sandi" aria-label="Lihat / sembunyikan kata sandi">
                        <svg class="h-4 w-4 eye-show" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                        </svg>
                        <svg class="h-4 w-4 eye-hide hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.542-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" />
                        </svg>
                    </button>
                </div>
                @error('current_password')
                    <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="password" class="block text-xs font-bold text-slate-700 mb-1.5">
                    Kata Sandi Baru (Min. 8 karakter) <span class="text-rose-500">*</span>
                </label>
                <div class="relative">
                    <input type="password" name="password" id="password" required minlength="8"
                        class="block w-full rounded-xl border border-slate-300 bg-white px-4 py-2.5 pr-14 text-xs sm:text-sm text-slate-900 placeholder:text-slate-400 shadow-sm transition focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 focus:outline-none @error('password') border-rose-400 ring-1 ring-rose-400 @enderror"
                        style="padding-right: 3.5rem;"
                        placeholder="Minimal 8 karakter">
                    <button type="button" onclick="togglePasswordVisibility('password', this)"
                        class="absolute inset-y-0 flex w-12 items-center justify-center text-slate-400 hover:text-emerald-600 focus:outline-none transition cursor-pointer"
                        style="right: 8px;"
                        title="Lihat / sembunyikan kata sandi" aria-label="Lihat / sembunyikan kata sandi">
                        <svg class="h-4 w-4 eye-show" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                        </svg>
                        <svg class="h-4 w-4 eye-hide hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.542-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" />
                        </svg>
                    </button>
                </div>
                @error('password')
                    <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="password_confirmation" class="block text-xs font-bold text-slate-700 mb-1.5">
                    Ulangi Kata Sandi Baru <span class="text-rose-500">*</span>
                </label>
                <div class="relative">
                    <input type="password" name="password_confirmation" id="password_confirmation" required minlength="8"
                        class="block w-full rounded-xl border border-slate-300 bg-white px-4 py-2.5 pr-14 text-xs sm:text-sm text-slate-900 placeholder:text-slate-400 shadow-sm transition focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 focus:outline-none @error('password_confirmation') border-rose-400 ring-1 ring-rose-400 @enderror"
                        style="padding-right: 3.5rem;"
                        placeholder="Ketik ulang kata sandi baru">
                    <button type="button" onclick="togglePasswordVisibility('password_confirmation', this)"
                        class="absolute inset-y-0 flex w-12 items-center justify-center text-slate-400 hover:text-emerald-600 focus:outline-none transition cursor-pointer"
                        style="right: 8px;"
                        title="Lihat / sembunyikan kata sandi" aria-label="Lihat / sembunyikan kata sandi">
                        <svg class="h-4 w-4 eye-show" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                        </svg>
                        <svg class="h-4 w-4 eye-hide hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.542-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" />
                        </svg>
                    </button>
                </div>
                @error('password_confirmation')
                    <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <button type="submit" class="w-full py-3 px-4 rounded-xl bg-slate-800 hover:bg-slate-900 text-white font-bold text-xs sm:text-sm shadow-md transition flex items-center justify-center gap-2 cursor-pointer mt-2">
                <svg class="h-4 w-4 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                </svg>
                <span>Perbarui Kata Sandi</span>
            </button>
        </form>
    </div>
</div>

<script>
    function togglePasswordVisibility(inputId, btn) {
        const input = document.getElementById(inputId);
        if (!input) return;
        const isPassword = input.type === 'password';
        input.type = isPassword ? 'text' : 'password';
        const showIcon = btn.querySelector('.eye-show');
        const hideIcon = btn.querySelector('.eye-hide');
        if (showIcon && hideIcon) {
            showIcon.classList.toggle('hidden', isPassword);
            hideIcon.classList.toggle('hidden', !isPassword);
        }
    }
</script>
@endsection
