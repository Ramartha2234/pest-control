@extends('layouts.admin')

@section('title', 'Data Pelanggan - CV. Ambius Indonesia')
@section('page_title', 'Kelola Data Pelanggan')

@section('content')
<div class="space-y-6">
    {{-- Header & Pencarian --}}
    <div class="flex flex-col sm:flex-row items-center justify-between gap-4 bg-white p-6 rounded-2xl border border-gray-200 shadow-sm">
        <div>
            <h3 class="text-base font-bold text-gray-900">Database Pelanggan</h3>
            <p class="text-xs text-gray-500 mt-1">Data kontak dan riwayat pemesanan pelanggan (tanpa registrasi/login).</p>
        </div>

        <form action="{{ route('admin.pelanggan.index') }}" method="GET" class="flex w-full sm:w-auto items-center gap-2">
            <div class="relative w-full sm:w-72">
                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                    <svg class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
                <input type="text" name="search" value="{{ $search }}" placeholder="Cari nama, no HP, email..."
                       class="w-full rounded-xl border border-gray-300 bg-white py-2 pl-9 pr-3 text-xs text-gray-900 focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500">
            </div>
            <button type="submit" class="rounded-xl bg-primary-600 px-4 py-2 text-xs font-semibold text-white shadow-sm hover:bg-primary-700 transition">
                Cari
            </button>
            @if ($search)
                <a href="{{ route('admin.pelanggan.index') }}" class="rounded-xl border border-gray-300 px-3 py-2 text-xs font-semibold text-gray-600 hover:bg-gray-50 transition">
                    Reset
                </a>
            @endif
        </form>
    </div>

    {{-- Tabel Pelanggan --}}
    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-gray-50 text-[11px] font-bold uppercase tracking-wider text-gray-500 border-b border-gray-200">
                    <tr>
                        <th class="px-6 py-4">Nama Pelanggan</th>
                        <th class="px-6 py-4">Nomor WhatsApp / HP</th>
                        <th class="px-6 py-4">Email</th>
                        <th class="px-6 py-4">Nama Usaha / Bisnis</th>
                        <th class="px-6 py-3.5 text-center whitespace-nowrap">Total Pesanan</th>
                        <th class="px-6 py-3.5 text-center whitespace-nowrap">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-gray-700">
                    @forelse ($pelanggans as $pelanggan)
                        <tr class="hover:bg-gray-50/80 transition">
                            <td class="px-6 py-3.5 font-bold text-gray-900">
                                {{ $pelanggan->nama }}
                            </td>
                            <td class="px-6 py-3.5 font-mono font-medium">
                                <a href="https://wa.me/{{ preg_replace('/^0/', '62', preg_replace('/[^0-9]/', '', $pelanggan->no_hp)) }}"
                                   target="_blank" rel="noopener" class="text-emerald-700 hover:underline">
                                    {{ $pelanggan->no_hp }}
                                </a>
                            </td>
                            <td class="px-6 py-3.5 text-gray-500">
                                {{ $pelanggan->email ?: '-' }}
                            </td>
                            <td class="px-6 py-3.5 font-medium text-gray-800">
                                {{ $pelanggan->nama_usaha ?: '-' }}
                            </td>
                            <td class="px-6 py-3.5 text-center whitespace-nowrap">
                                <span class="inline-block rounded-full bg-primary-100 px-2.5 py-0.5 text-xs font-bold text-primary-800">
                                    {{ $pelanggan->pemesanans_count }}
                                </span>
                            </td>
                            <td class="px-6 py-3.5 text-center whitespace-nowrap">
                                <a href="{{ route('admin.pelanggan.show', $pelanggan->id_pelanggan) }}" class="rounded-xl bg-primary-50 px-3.5 py-1.5 font-bold text-primary-700 hover:bg-primary-100 transition inline-block">
                                    Lihat Riwayat &rarr;
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                Belum ada data pelanggan yang cocok dengan pencarian.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-4 border-t border-gray-100">
            {{ $pelanggans->links() }}
        </div>
    </div>
</div>
@endsection
