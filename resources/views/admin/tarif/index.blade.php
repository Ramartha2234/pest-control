@extends('layouts.admin')

@section('title', 'Katalog Tarif Layanan - CV. Ambius')
@section('page_title', 'Kelola Tarif Layanan')

@section('content')
<div class="space-y-6">
    {{-- Header & Tambah Tarif --}}
    <div class="flex flex-col sm:flex-row items-center justify-between gap-4 bg-white p-6 rounded-2xl border border-gray-200 shadow-sm">
        <div>
            <h3 class="text-base font-bold text-gray-900">Katalog Tarif & Layanan Pest Control</h3>
            <p class="text-xs text-gray-500 mt-1">Kelola harga estimasi untuk formulir pemesanan online. Tarif non-aktif tidak akan muncul di form publik.</p>
        </div>
        <a href="{{ route('admin.tarif.create') }}" class="inline-flex items-center gap-2 rounded-xl bg-primary-600 px-4 py-2.5 text-xs font-bold text-white shadow-sm hover:bg-primary-700 transition">
            <svg class="h-4 w-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
            </svg>
            <span>Tambah Tarif Baru</span>
        </a>
    </div>

    {{-- Tabel Tarif --}}
    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-gray-50 text-[11px] font-bold uppercase tracking-wider text-gray-500 border-b border-gray-200">
                    <tr>
                        <th class="px-6 py-4">Jenis Layanan Hama</th>
                        <th class="px-6 py-4">Paket & Properti</th>
                        <th class="px-6 py-4">Jangkauan Area</th>
                        <th class="px-6 py-4 text-right">Tarif Dasar</th>
                        <th class="px-6 py-4 text-center">Status</th>
                        <th class="px-6 py-4 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-gray-700">
                    @forelse ($tarifs as $tarif)
                        <tr class="hover:bg-gray-50/80 transition {{ !$tarif->status_aktif ? 'opacity-60 bg-gray-50/40' : '' }}">
                            <td class="px-6 py-4 font-bold text-gray-900">
                                <div>{{ $tarif->jenis_layanan }}</div>
                                <div class="text-[11px] text-gray-400 font-normal line-clamp-1">{{ $tarif->deskripsi }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="font-semibold text-gray-800">{{ $tarif->nama_paket ?: 'Paket Standar' }}</div>
                                <div class="text-[11px] text-primary-700 font-medium">{{ $tarif->jenis_properti ?: 'Semua Properti' }}</div>
                            </td>
                            <td class="px-6 py-4 font-medium text-gray-800">
                                Per {{ $tarif->satuan }}
                            </td>
                            <td class="px-6 py-4 text-right font-extrabold text-gray-900">
                                Rp {{ number_format($tarif->tarif_dasar, 0, ',', '.') }}
                            </td>
                            <td class="px-6 py-4 text-center">
                                <form action="{{ route('admin.tarif.toggle', $tarif->id_tarif) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-bold transition {{ $tarif->status_aktif ? 'bg-emerald-100 text-emerald-800 hover:bg-emerald-200' : 'bg-gray-200 text-gray-600 hover:bg-gray-300' }}" title="Klik untuk mengubah status">
                                        <span class="h-2 w-2 rounded-full {{ $tarif->status_aktif ? 'bg-emerald-600' : 'bg-gray-400' }}"></span>
                                        {{ $tarif->status_aktif ? 'Aktif' : 'Non-Aktif' }}
                                    </button>
                                </form>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <div class="inline-flex items-center gap-1.5 justify-center">
                                    <a href="{{ route('admin.tarif.edit', $tarif->id_tarif) }}" class="rounded-xl bg-primary-50 px-3.5 py-1.5 font-bold text-primary-700 hover:bg-primary-100 transition inline-block">
                                        Edit
                                    </a>
                                    <form action="{{ route('admin.tarif.destroy', $tarif->id_tarif) }}" method="POST" class="inline-block" onsubmit="return confirm('Apakah Anda yakin ingin menghapus / menonaktifkan tarif ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="rounded-xl bg-rose-50 px-3.5 py-1.5 font-bold text-rose-700 hover:bg-rose-100 transition inline-block">
                                            Hapus
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                Belum ada data tarif layanan.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-4 border-t border-gray-100">
            {{ $tarifs->links() }}
        </div>
    </div>

    {{-- TABEL BARU: Tarif Tambahan Area Penanganan (Indoor & Outdoor) --}}
    <div class="space-y-4">
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-2 bg-gradient-to-r from-emerald-50 via-teal-50 to-white p-6 rounded-2xl border border-emerald-200 shadow-sm">
            <div>
                <div class="flex items-center gap-2">
                    <h3 class="text-base font-extrabold text-gray-900">Tabel Tarif Tambahan Area Penanganan</h3>
                </div>
                <p class="text-xs text-gray-600 mt-1 max-w-2xl">
                    Tabel khusus untuk mengatur biaya area penanganan (Indoor vs Outdoor). Biaya ini otomatis diterapkan pada kalkulator pemesanan pelanggan.
                </p>
            </div>
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl bg-emerald-100 text-emerald-800 font-extrabold text-xs">
                Biaya Tambahan Khusus
            </span>
        </div>

        <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-gray-50 text-[11px] font-bold uppercase tracking-wider text-gray-500 border-b border-gray-200">
                        <tr>
                            <th class="px-6 py-4">Nama Area Penanganan</th>
                            <th class="px-6 py-4">Kode Identifikasi</th>
                            <th class="px-6 py-4">Cakupan Lokasi / Keterangan</th>
                            <th class="px-6 py-4 text-right">Biaya Tambahan (Rp)</th>
                            <th class="px-6 py-4 text-center">Status</th>
                            <th class="px-6 py-4 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 text-gray-700">
                        @forelse ($tarifAreas as $area)
                            <tr class="hover:bg-gray-50/80 transition {{ !$area->status_aktif ? 'opacity-60 bg-gray-50/40' : '' }}">
                                <td class="px-6 py-4 font-bold text-gray-900">
                                    <div class="flex items-center gap-2">
                                        <span class="text-sm font-extrabold">{{ $area->nama_area }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="font-mono text-xs font-bold px-2 py-0.5 rounded bg-gray-100 text-gray-700 uppercase">
                                        {{ $area->kode_area }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-gray-600">
                                    {{ $area->keterangan ?: '-' }}
                                </td>
                                <td class="px-6 py-4 text-right whitespace-nowrap">
                                    <span class="text-sm font-black {{ (float)$area->biaya_tambahan > 0 ? 'text-amber-700' : 'text-emerald-700' }}">
                                        @if ((float)$area->biaya_tambahan > 0)
                                            + Rp {{ number_format($area->biaya_tambahan, 0, ',', '.') }}
                                        @else
                                            Rp 0 (Tarif Tetap)
                                        @endif
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <form action="{{ route('admin.tarif-area.toggle', $area->id_tarif_area) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-bold transition {{ $area->status_aktif ? 'bg-emerald-100 text-emerald-800 hover:bg-emerald-200' : 'bg-gray-200 text-gray-600 hover:bg-gray-300' }}" title="Klik untuk mengubah status aktif area">
                                            <span class="h-2 w-2 rounded-full {{ $area->status_aktif ? 'bg-emerald-600' : 'bg-gray-400' }}"></span>
                                            {{ $area->status_aktif ? 'Aktif' : 'Non-Aktif' }}
                                        </button>
                                    </form>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <button type="button"
                                            onclick="openEditAreaModal('{{ $area->id_tarif_area }}', '{{ addslashes($area->nama_area) }}', '{{ (int)$area->biaya_tambahan }}', '{{ addslashes($area->keterangan ?? '') }}', {{ $area->status_aktif ? 'true' : 'false' }})"
                                            class="inline-flex items-center gap-1.5 rounded-xl bg-primary-50 px-3.5 py-1.5 font-bold text-primary-700 hover:bg-primary-100 transition shadow-sm">
                                        Ubah Harga
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                                    Belum ada data tarif area.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- Modal Ubah Tarif Area --}}
<div id="modalEditArea" class="hidden fixed inset-0 z-50 overflow-y-auto bg-black/50 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="relative w-full max-w-lg rounded-2xl bg-white p-6 sm:p-7 shadow-2xl space-y-5 animate-scale-in">
        <div class="flex items-center justify-between border-b border-gray-100 pb-3">
            <h3 class="text-base font-bold text-gray-900">
                Edit Tarif Area Penanganan
            </h3>
            <button type="button" onclick="closeEditAreaModal()" class="text-gray-400 hover:text-gray-600 text-2xl leading-none transition">
                &times;
            </button>
        </div>

        <form id="formEditArea" method="POST" action="" class="space-y-4">
            @csrf
            @method('PUT')

            <div>
                <label for="edit_nama_area" class="block text-sm font-semibold text-gray-700">Nama Area</label>
                <input type="text" name="nama_area" id="edit_nama_area" required
                       class="mt-2 block w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-900 shadow-sm focus:border-primary-500 focus:ring-primary-500 focus:outline-none"
                       placeholder="Contoh: Indoor (Dalam Ruangan)">
            </div>

            <div>
                <label for="edit_biaya_tambahan" class="block text-sm font-semibold text-gray-700">
                    Biaya Tambahan (Rp) <span class="text-rose-500">*</span>
                </label>
                <input type="number" name="biaya_tambahan" id="edit_biaya_tambahan" required min="0" step="1000"
                       class="mt-2 block w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-900 shadow-sm focus:border-primary-500 focus:ring-primary-500 focus:outline-none font-semibold"
                       placeholder="0">
                <p class="mt-1.5 text-xs text-gray-500">Masukkan nominal biaya tambahan (contoh: 100000, 150000). Masukkan 0 jika tidak ada biaya tambahan.</p>
            </div>

            <div>
                <label for="edit_keterangan" class="block text-sm font-semibold text-gray-700">Cakupan Lokasi / Keterangan</label>
                <input type="text" name="keterangan" id="edit_keterangan"
                       placeholder="Contoh: Rumah, kantor, kamar, restoran, gudang"
                       class="mt-2 block w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-900 shadow-sm focus:border-primary-500 focus:ring-primary-500 focus:outline-none">
            </div>

            <div class="flex items-center pt-1">
                <input type="checkbox" name="status_aktif" value="1" id="edit_status_aktif"
                       class="h-4 w-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                <label for="edit_status_aktif" class="ml-2 block text-sm font-medium text-gray-700">Aktifkan opsi ini di formulir pemesanan online</label>
            </div>

            <div class="pt-4 border-t border-gray-100 flex items-center justify-end gap-3">
                <button type="button" onclick="closeEditAreaModal()"
                        class="rounded-xl border border-gray-300 px-5 py-2.5 text-sm font-semibold text-gray-600 hover:bg-gray-50 transition">
                    Batal
                </button>
                <button type="submit"
                        class="rounded-xl bg-primary-600 hover:bg-primary-700 px-6 py-2.5 text-sm font-bold text-white shadow-sm transition">
                    Simpan Perubahan Tarif
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function openEditAreaModal(id, nama, biaya, keterangan, status) {
        const modal = document.getElementById('modalEditArea');
        const form = document.getElementById('formEditArea');
        form.action = "{{ url('admin/tarif-area') }}/" + id;
        document.getElementById('edit_nama_area').value = nama;
        document.getElementById('edit_biaya_tambahan').value = biaya;
        document.getElementById('edit_keterangan').value = keterangan;
        document.getElementById('edit_status_aktif').checked = Boolean(status);
        modal.classList.remove('hidden');
    }

    function closeEditAreaModal() {
        const modal = document.getElementById('modalEditArea');
        modal.classList.add('hidden');
    }

    // Close on escape
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeEditAreaModal();
        }
    });
</script>
@endsection
