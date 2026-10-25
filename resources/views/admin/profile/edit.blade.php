@extends('layouts.admin')

@section('title', 'Profil Admin - CV. Ambius')
@section('page_title', 'Pengaturan Akun Admin')

@section('content')
<div class="w-full">
    <div class="bg-white rounded-xl border border-gray-200 p-6 sm:p-8 shadow-sm">
        <form action="{{ route('admin.profile.update') }}" method="POST" class="space-y-8">
            @csrf
            @method('PUT')

            <!-- Section 1: Informasi Profil -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 pb-6 border-b border-gray-200">
                <div class="space-y-1">
                    <h3 class="text-sm font-bold text-gray-900 uppercase tracking-wider">Informasi Profil</h3>
                    <p class="text-xs text-gray-500">Perbarui nama lengkap dan alamat email akun admin Anda.</p>
                </div>
                <div class="lg:col-span-2 space-y-4">
                    <div>
                        <label for="name" class="block text-xs font-bold uppercase text-gray-400 tracking-wider">Nama Lengkap</label>
                        <input type="text" name="name" id="name" value="{{ old('name', $user->name) }}" required
                               class="mt-2 block w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-900 shadow-sm focus:border-primary-500 focus:ring-primary-500 focus:outline-none"
                               placeholder="Contoh: Owner CV. Ambius">
                        @error('name')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="email" class="block text-xs font-bold uppercase text-gray-400 tracking-wider">Alamat Email</label>
                        <input type="email" name="email" id="email" value="{{ old('email', $user->email) }}" required
                               class="mt-2 block w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-900 shadow-sm focus:border-primary-500 focus:ring-primary-500 focus:outline-none"
                               placeholder="admin@ambius.com">
                        @error('email')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Section 2: Keamanan / Ubah Password -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 pb-6 border-b border-gray-200">
                <div class="space-y-1">
                    <h3 class="text-sm font-bold text-gray-900 uppercase tracking-wider">Ubah Password</h3>
                    <p class="text-xs text-gray-500">Biarkan kosong jika Anda tidak ingin mengubah password saat ini.</p>
                </div>
                <div class="lg:col-span-2 grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="password" class="block text-xs font-bold uppercase text-gray-400 tracking-wider">Password Baru</label>
                        <input type="password" name="password" id="password"
                               class="mt-2 block w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-900 shadow-sm focus:border-primary-500 focus:ring-primary-500 focus:outline-none"
                               placeholder="Min. 8 karakter">
                        @error('password')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="password_confirmation" class="block text-xs font-bold uppercase text-gray-400 tracking-wider">Konfirmasi Password Baru</label>
                        <input type="password" name="password_confirmation" id="password_confirmation"
                               class="mt-2 block w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-900 shadow-sm focus:border-primary-500 focus:ring-primary-500 focus:outline-none"
                               placeholder="Ulangi password baru">
                    </div>
                </div>
            </div>

            <!-- Action Button -->
            <div class="flex justify-end pt-2">
                <button type="submit" class="rounded-xl bg-primary-500 hover:bg-primary-600 px-8 py-3 text-sm font-bold text-white shadow-md transition hover:scale-[1.01]">
                    Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
