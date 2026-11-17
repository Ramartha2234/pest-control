@extends('layouts.admin')

@section('title', 'Tambah Teknisi Baru - CV. Ambius')
@section('page_title', 'Tambah Teknisi Baru')

@section('content')
<div class="max-w-2xl">
    <div class="mb-6">
        <a href="{{ route('admin.teknisi.index') }}" class="text-sm font-semibold text-gray-500 hover:text-gray-700 flex items-center gap-1">
            &larr; Kembali ke Daftar Teknisi
        </a>
    </div>

    {{-- Form Card --}}
    <div class="relative rounded-2xl border border-gray-100 bg-white p-8 shadow-xl">
        <div class="absolute inset-x-0 top-0 h-1.5 bg-gradient-to-r from-primary-400 to-primary-600 rounded-t-2xl"></div>

        <form action="{{ route('admin.teknisi.store') }}" method="POST" class="space-y-6">
            @csrf

            {{-- Info Banner --}}
            <div class="rounded-xl bg-amber-50 border border-amber-200/80 p-4 text-xs text-amber-900 flex items-start gap-3">
                <svg class="h-5 w-5 text-amber-600 mt-0.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <div class="space-y-1">
                    <p class="font-bold text-amber-950">Informasi Pembuatan Akun Teknisi</p>
                    <p class="text-amber-800 text-[11px] leading-relaxed">
                        Data ini sekaligus membuat akun login untuk portal teknisi. Anda dapat menentukan email dan password sendiri, atau membiarkannya kosong untuk menggunakan email otomatis dan kata sandi default: <span class="font-mono font-bold bg-amber-100 px-1 py-0.5 rounded text-amber-900">password</span>.
                    </p>
                </div>
            </div>

            <div>
                <label for="nama" class="block text-sm font-semibold text-gray-700">Nama Lengkap Teknisi <span class="text-red-500">*</span></label>
                <input type="text" name="nama" id="nama" value="{{ old('nama') }}" required
                       class="mt-2 block w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-900 shadow-sm focus:border-primary-500 focus:ring-primary-500 focus:outline-none"
                       placeholder="Contoh: Dedi Setiadi">
                @error('nama')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="no_telp" class="block text-sm font-semibold text-gray-700">Nomor Telepon / WhatsApp <span class="text-red-500">*</span></label>
                <input type="tel" name="no_telp" id="no_telp" value="{{ old('no_telp') }}" required
                       class="mt-2 block w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-900 shadow-sm focus:border-primary-500 focus:ring-primary-500 focus:outline-none"
                       placeholder="Contoh: 081234567890">
                @error('no_telp')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="border-t border-gray-100 pt-5 space-y-5">
                <h4 class="text-xs font-bold uppercase tracking-wider text-gray-500">Kredensial Login Portal Teknisi</h4>

                <div>
                    <label for="email" class="block text-sm font-semibold text-gray-700">Alamat Email (Login)</label>
                    <input type="email" name="email" id="email" value="{{ old('email') }}"
                           class="mt-2 block w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-900 shadow-sm focus:border-primary-500 focus:ring-primary-500 focus:outline-none"
                           placeholder="Contoh: dedi@ambius.com atau dedi@gmail.com">
                    <p class="mt-1.5 text-xs text-gray-500">
                        Kosongkan jika ingin sistem membuatkan email otomatis berbasis nama teknisi (contoh: dedi1234@ambius.com).
                    </p>
                    @error('email')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="password" class="block text-sm font-semibold text-gray-700">Password Awal Akun</label>
                    <input type="password" name="password" id="password"
                           class="mt-2 block w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-900 shadow-sm focus:border-primary-500 focus:ring-primary-500 focus:outline-none"
                           placeholder="Minimal 6 karakter (Default: password)">
                    <p class="mt-1.5 text-xs text-gray-500">
                        Kosongkan jika ingin menggunakan password default: <span class="font-mono font-semibold text-gray-700">password</span>. Teknisi dapat mengubahnya nanti di profil mereka.
                    </p>
                    @error('password')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="border-t border-gray-100 pt-5">
                <div class="flex items-center">
                    <input type="checkbox" name="is_active" id="is_active" value="1" checked class="h-4 w-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                    <label for="is_active" class="ml-2 block text-sm font-semibold text-gray-700">Aktif (Dapat Ditugaskan ke Lapangan)</label>
                </div>
            </div>

            <div class="flex items-center justify-end gap-4 pt-4 border-t border-gray-100">
                <a href="{{ route('admin.teknisi.index') }}" class="rounded-xl px-5 py-3 text-sm font-semibold text-gray-600 hover:bg-gray-100 transition">
                    Batal
                </a>
                <button type="submit" class="rounded-xl bg-primary-500 hover:bg-primary-600 px-8 py-3 text-sm font-bold text-white shadow-lg transition">
                    Simpan Teknisi & Akun
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
