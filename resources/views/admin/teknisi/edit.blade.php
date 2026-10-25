@extends('layouts.admin')

@section('title', 'Edit Data Teknisi - CV. Ambius')
@section('page_title', 'Edit Data Teknisi')

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

        <form action="{{ route('admin.teknisi.update', $teknisi->id) }}" method="POST" class="space-y-6">
            @csrf
            @method('PUT')

            <div>
                <label for="nama" class="block text-sm font-semibold text-gray-700">Nama Lengkap Teknisi</label>
                <input type="text" name="nama" id="nama" value="{{ old('nama', $teknisi->nama) }}" required
                       class="mt-2 block w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-900 shadow-sm focus:border-primary-500 focus:ring-primary-500 focus:outline-none"
                       placeholder="Contoh: Dedi Setiadi">
                @error('nama')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="no_telp" class="block text-sm font-semibold text-gray-700">Nomor Telepon / WhatsApp</label>
                <input type="tel" name="no_telp" id="no_telp" value="{{ old('no_telp', $teknisi->no_telp) }}" required
                       class="mt-2 block w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-900 shadow-sm focus:border-primary-500 focus:ring-primary-500 focus:outline-none"
                       placeholder="Contoh: 081234567890">
                @error('no_telp')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700">Status Keaktifan</label>
                <div class="mt-3 flex items-center gap-4">
                    <label class="inline-flex items-center text-sm font-semibold text-gray-700">
                        <input type="radio" name="is_active" value="1" {{ old('is_active', $teknisi->is_active) ? 'checked' : '' }} class="h-4 w-4 border-gray-300 text-primary-600 focus:ring-primary-500">
                        <span class="ml-2 text-green-700">Aktif (Dapat Ditugaskan)</span>
                    </label>
                    <label class="inline-flex items-center text-sm font-semibold text-gray-700">
                        <input type="radio" name="is_active" value="0" {{ old('is_active', $teknisi->is_active) ? '' : 'checked' }} class="h-4 w-4 border-gray-300 text-primary-600 focus:ring-primary-500">
                        <span class="ml-2 text-red-700">Nonaktif</span>
                    </label>
                </div>
                @error('is_active')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex items-center justify-end gap-4 pt-4 border-t border-gray-100">
                <a href="{{ route('admin.teknisi.index') }}" class="rounded-xl px-5 py-3 text-sm font-semibold text-gray-600 hover:bg-gray-100 transition">
                    Batal
                </a>
                <button type="submit" class="rounded-xl bg-primary-500 hover:bg-primary-600 px-8 py-3 text-sm font-bold text-white shadow-lg transition">
                    Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
