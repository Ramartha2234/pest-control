@extends('layouts.admin')

@section('title', 'Detail Pelanggan: ' . $pelanggan->nama . ' - CV. Ambius')
@section('page_title', 'Profil & Riwayat Pelanggan')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <a href="{{ route('admin.pelanggan.index') }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-primary-600 hover:text-primary-800">
            &larr; Kembali ke Daftar Pelanggan
        </a>
    </div>

    {{-- Detail Pelanggan --}}
    <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-gray-100 pb-5">
            <div>
                <span class="text-xs font-semibold uppercase tracking-wider text-gray-400">Profil Pelanggan</span>
                <h3 class="text-xl font-extrabold text-gray-900 mt-1">{{ $pelanggan->nama }}</h3>
                @if ($pelanggan->nama_usaha)
                    <p class="text-sm font-semibold text-primary-700 mt-0.5">{{ $pelanggan->nama_usaha }}</p>
                @endif
            </div>
            <a href="https://wa.me/{{ preg_replace('/^0/', '62', preg_replace('/[^0-9]/', '', $pelanggan->no_hp)) }}"
               target="_blank" rel="noopener"
               class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-4 py-2.5 text-xs font-bold text-white shadow-sm hover:bg-emerald-700 transition">
                <span>💬</span> Hubungi via WhatsApp ({{ $pelanggan->no_hp }})
            </a>
        </div>

        <div class="mt-4 grid grid-cols-1 sm:grid-cols-3 gap-4 text-xs">
            <div class="bg-gray-50 p-3 rounded-xl border border-gray-100">
                <span class="block text-gray-400 uppercase font-bold text-[10px]">Email</span>
                <span class="font-medium text-gray-800 mt-1 block">{{ $pelanggan->email ?: 'Tidak dicantumkan' }}</span>
            </div>
            <div class="bg-gray-50 p-3 rounded-xl border border-gray-100">
                <span class="block text-gray-400 uppercase font-bold text-[10px]">No. Telepon / WA</span>
                <span class="font-medium text-gray-800 mt-1 block">{{ $pelanggan->no_hp }}</span>
            </div>
            <div class="bg-gray-50 p-3 rounded-xl border border-gray-100">
                <span class="block text-gray-400 uppercase font-bold text-[10px]">Terdaftar Sejak</span>
                <span class="font-medium text-gray-800 mt-1 block">{{ $pelanggan->created_at->format('d M Y') }}</span>
            </div>
        </div>

        @if ($pelanggan->alamat)
            <div class="mt-4 text-xs">
                <span class="font-bold text-gray-700">Alamat Terakhir:</span>
                <p class="mt-1 text-gray-600 bg-gray-50 p-3 rounded-xl border border-gray-100 leading-relaxed">{{ $pelanggan->alamat }}</p>
            </div>
        @endif
    </div>

    {{-- Riwayat Pemesanan --}}
    <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm space-y-4">
        <h4 class="text-base font-bold text-gray-900">Riwayat Seluruh Pemesanan ({{ $pelanggan->pemesanans->count() }})</h4>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-gray-50 text-[11px] font-bold uppercase tracking-wider text-gray-500 border-b border-gray-200">
                    <tr>
                        <th class="px-4 py-3">Kode & Tanggal</th>
                        <th class="px-4 py-3">Layanan & Properti</th>
                        <th class="px-4 py-3">Teknisi</th>
                        <th class="px-4 py-3">Status Pengerjaan</th>
                        <th class="px-4 py-3">Status Pembayaran</th>
                        <th class="px-4 py-3 text-right">Nominal</th>
                        <th class="px-4 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-gray-700">
                    @forelse ($pelanggan->pemesanans as $order)
                        @php
                            $stColor = match($order->status_pengerjaan) {
                                'Pending' => 'bg-amber-100 text-amber-800',
                                'On-Going' => 'bg-sky-100 text-sky-800',
                                'Done' => 'bg-emerald-100 text-emerald-800',
                                'Cancel' => 'bg-rose-100 text-rose-800',
                                default => 'bg-gray-100 text-gray-800'
                            };
                            $pbColor = match($order->status_pembayaran) {
                                'Lunas' => 'bg-emerald-100 text-emerald-800',
                                'Ditolak' => 'bg-rose-100 text-rose-800',
                                default => 'bg-amber-100 text-amber-800'
                            };
                        @endphp
                        <tr class="hover:bg-gray-50/80 transition">
                            <td class="px-4 py-3 font-mono font-bold text-gray-900">
                                <div>{{ $order->kode_pemesanan }}</div>
                                <div class="text-[10px] text-gray-400 font-sans">{{ $order->tgl_pemesanan->format('d M Y') }}</div>
                            </td>
                            <td class="px-4 py-3">
                                <div class="font-semibold">{{ $order->tarifLayanan->jenis_layanan ?? '-' }}</div>
                                <div class="text-[10px] text-gray-500">{{ $order->jenis_properti }}</div>
                            </td>
                            <td class="px-4 py-3 text-gray-700">
                                {{ $order->jadwalPenugasan?->teknisi?->nama ?? 'Belum Ditugaskan' }}
                            </td>
                            <td class="px-4 py-3">
                                <span class="rounded-full px-2.5 py-0.5 text-[11px] font-semibold {{ $stColor }}">
                                    {{ $order->status_pengerjaan }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <span class="rounded-full px-2.5 py-0.5 text-[11px] font-semibold {{ $pbColor }}">
                                    {{ $order->status_pembayaran }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right font-bold text-gray-900">
                                Rp {{ number_format($order->harga_final ?? $order->harga_estimasi, 0, ',', '.') }}
                            </td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('admin.pemesanan.show', $order->id_pemesanan) }}" class="rounded-lg bg-primary-50 text-primary-700 hover:bg-primary-100 px-3 py-1 font-semibold transition">
                                    Detail &rarr;
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-gray-500">
                                Pelanggan ini belum memiliki riwayat pemesanan.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
