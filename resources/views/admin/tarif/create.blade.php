@extends('layouts.admin')

@section('title', 'Tambah Tarif Layanan Baru - CV. Ambius')
@section('page_title', 'Tambah Tarif Layanan')

@section('content')
<div class="max-w-2xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <a href="{{ route('admin.tarif.index') }}" class="text-xs font-semibold text-primary-600 hover:text-primary-800">
            &larr; Kembali ke Katalog Tarif
        </a>
    </div>

    <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
        <h3 class="text-base font-bold text-gray-900 border-b border-gray-100 pb-3">Formulir Tarif Layanan Baru</h3>

        <form action="{{ route('admin.tarif.store') }}" method="POST" class="mt-4 space-y-4 text-xs">
            @csrf

            <div>
                <label class="block font-semibold text-gray-700 mb-1">Jenis Layanan Hama <span class="text-rose-500">*</span></label>
                <input type="text" name="jenis_layanan" value="{{ old('jenis_layanan') }}" required
                       placeholder="Contoh: Pengendalian Rayap (Termite Control)"
                       class="w-full rounded-xl border-gray-300 text-xs focus:border-primary-500 focus:ring-primary-500">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block font-semibold text-gray-700 mb-1">Kategori / Jenis Properti</label>
                    <select name="jenis_properti" class="w-full rounded-xl border-gray-300 text-xs focus:border-primary-500 focus:ring-primary-500">
                        <option value="">Semua Properti</option>
                        <option value="Rumah Tinggal" {{ old('jenis_properti') === 'Rumah Tinggal' ? 'selected' : '' }}>Rumah Tinggal</option>
                        <option value="Ruko/Restoran" {{ old('jenis_properti') === 'Ruko/Restoran' ? 'selected' : '' }}>Ruko / Restoran / Kafe</option>
                        <option value="Kantor/Gedung" {{ old('jenis_properti') === 'Kantor/Gedung' ? 'selected' : '' }}>Kantor / Gedung Komersial</option>
                        <option value="Gudang/Pabrik" {{ old('jenis_properti') === 'Gudang/Pabrik' ? 'selected' : '' }}>Gudang / Pabrik Industri</option>
                    </select>
                </div>
                <div>
                    <label class="block font-semibold text-gray-700 mb-1">Nama Paket Layanan</label>
                    <input type="text" name="nama_paket" value="{{ old('nama_paket') }}" placeholder="Contoh: Paket Standar Garansi 3 Tahun"
                           class="w-full rounded-xl border-gray-300 text-xs focus:border-primary-500 focus:ring-primary-500">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block font-semibold text-gray-700 mb-1">Tarif Dasar (Rp) <span class="text-rose-500">*</span></label>
                    <input type="number" name="tarif_dasar" value="{{ old('tarif_dasar') }}" required min="0" step="1000" placeholder="1500000"
                           class="w-full rounded-xl border-gray-300 text-xs focus:border-primary-500 focus:ring-primary-500">
                </div>
                <div>
                    <label class="block font-semibold text-gray-700 mb-1">Satuan Ukuran <span class="text-rose-500">*</span></label>
                    <input type="text" name="satuan" value="{{ old('satuan', 'm2') }}" required placeholder="m2, titik, dll"
                           class="w-full rounded-xl border-gray-300 text-xs focus:border-primary-500 focus:ring-primary-500">
                </div>
            </div>

            <div>
                <label class="block font-semibold text-gray-700 mb-1">Deskripsi & Cakupan Penanganan</label>
                <textarea name="deskripsi" rows="3" placeholder="Jelaskan metode treatment, bahan kimia yang digunakan, dan garansi..."
                          class="w-full rounded-xl border-gray-300 text-xs focus:border-primary-500 focus:ring-primary-500">{{ old('deskripsi') }}</textarea>
            </div>

            <div class="flex items-center gap-2 pt-2">
                <input type="checkbox" name="status_aktif" value="1" id="status_aktif" {{ old('status_aktif', true) ? 'checked' : '' }}
                       class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                <label for="status_aktif" class="font-medium text-gray-700">Aktifkan tarif ini (Tampil di formulir pemesanan online)</label>
            </div>

            <div class="pt-4 border-t border-gray-100 flex items-center justify-end gap-2">
                <a href="{{ route('admin.tarif.index') }}" class="px-4 py-2 rounded-xl border border-gray-300 font-semibold text-gray-600 hover:bg-gray-50 transition">
                    Batal
                </a>
                <button type="submit" class="px-5 py-2 rounded-xl bg-primary-600 font-bold text-white shadow-sm hover:bg-primary-700 transition">
                    Simpan Tarif Baru
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
