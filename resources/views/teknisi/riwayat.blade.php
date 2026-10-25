@extends('layouts.teknisi')

@section('title', 'Riwayat Pekerjaan Selesai - Portal Teknisi')

@section('content')
<div class="space-y-4">
    {{-- Header & Pencarian --}}
    <div class="rounded-2xl bg-white p-4 border border-slate-200 shadow-sm space-y-3">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-lg font-black text-slate-900">Riwayat Pekerjaan Selesai</h2>
                <p class="text-xs text-slate-500">Semua tugas berstatus Done dan Lunas yang telah Anda selesaikan.</p>
            </div>
            <a href="{{ route('teknisi.dashboard') }}" class="text-xs font-bold text-emerald-700 hover:underline">
                &larr; Dashboard
            </a>
        </div>

        <form action="{{ route('teknisi.riwayat') }}" method="GET" class="flex items-center gap-2">
            <div class="relative flex-1">
                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                    <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
                <input type="text" name="search" value="{{ $search }}" placeholder="Cari kode pesanan atau nama pelanggan..."
                    class="w-full rounded-xl border border-slate-300 bg-white pl-9 pr-3 py-2 text-xs focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
            </div>
            <button type="submit" class="rounded-xl bg-emerald-700 hover:bg-emerald-800 px-4 py-2 text-xs font-bold text-white shadow-sm transition">
                Cari
            </button>
            @if ($search !== '')
                <a href="{{ route('teknisi.riwayat') }}" class="rounded-xl bg-slate-100 px-3 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-200 transition">
                    Reset
                </a>
            @endif
        </form>
    </div>

    {{-- Daftar Riwayat Selesai --}}
    @if ($riwayats->isEmpty())
        <div class="rounded-2xl border border-dashed border-slate-200 bg-white p-10 text-center">
            <h3 class="text-sm font-bold text-slate-800">Belum ada riwayat pekerjaan selesai</h3>
            <p class="text-xs text-slate-400 mt-1">Pekerjaan akan muncul di sini setelah dokumen WO dan pembayaran disetujui admin.</p>
        </div>
    @else
        <div class="space-y-3">
            @foreach ($riwayats as $item)
                @php
                    $latestWo = $item->latestWorkOrder();
                    $latestBayar = $item->latestBuktiPembayaran();
                @endphp
                <div class="rounded-2xl border border-emerald-200 bg-white p-4 shadow-sm hover:shadow-md transition space-y-2.5">
                    <div class="flex items-start justify-between gap-2 border-b border-slate-100 pb-2.5">
                        <div>
                            <span class="font-mono text-xs font-black text-slate-800 bg-slate-100 px-2.5 py-0.5 rounded-lg border border-slate-200">
                                {{ $item->kode_pemesanan }}
                            </span>
                            <h3 class="text-base font-bold text-slate-900 mt-1">{{ $item->pelanggan->nama }}</h3>
                            <p class="text-xs text-slate-500">{{ $item->tarifLayanan->jenis_layanan ?? 'Pest Control' }} &bull; {{ $item->jenis_properti }} &bull; <b class="{{ ($item->tipe_area ?? 'Indoor') === 'Outdoor' ? 'text-amber-700' : 'text-emerald-700' }}">{{ $item->tipe_area ?? 'Indoor' }}</b></p>
                        </div>

                        <div class="text-right">
                            <span class="inline-block rounded-full bg-emerald-100 border border-emerald-200 px-2.5 py-0.5 text-xs font-extrabold text-emerald-800">
                                Selesai & Lunas
                            </span>
                            <span class="block text-[10px] text-slate-400 mt-1">
                                {{ $item->tgl_selesai ? \Carbon\Carbon::parse($item->tgl_selesai)->format('d M Y, H:i') : '-' }}
                            </span>
                        </div>
                    </div>

                    <div class="text-xs text-slate-600 space-y-1.5">
                        <p class="flex items-start gap-1.5">
                            <svg class="h-3.5 w-3.5 text-slate-400 mt-0.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            <span>{{ $item->alamat_lokasi }}</span>
                        </p>

                        <div class="grid grid-cols-2 gap-2 bg-slate-50 p-2 rounded-xl text-[11px]">
                            <div>
                                <span class="block text-[10px] text-slate-400 uppercase font-bold">Jadwal Treatment</span>
                                <span class="font-bold text-slate-800">
                                    {{ $item->jadwalPenugasan?->tgl_pengerjaan ? \Carbon\Carbon::parse($item->jadwalPenugasan->tgl_pengerjaan)->format('d M Y') : '-' }}
                                    ({{ $item->jadwalPenugasan?->jam_pengerjaan ?? '-' }})
                                </span>
                            </div>
                            <div>
                                <span class="block text-[10px] text-slate-400 uppercase font-bold">Harga Final</span>
                                <span class="font-extrabold text-emerald-800">
                                    Rp {{ number_format($item->harga_final ?? $item->harga_estimasi, 0, ',', '.') }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="pt-2 border-t border-slate-100 flex items-center justify-between text-xs">
                        <div class="flex items-center gap-2">
                            @if ($latestWo)
                                <a href="{{ route('dokumen.wo', $latestWo->id_wo) }}" target="_blank" class="rounded-lg bg-emerald-50 border border-emerald-200 px-2.5 py-1 text-[11px] font-bold text-emerald-800 hover:bg-emerald-100 transition">
                                    Unduh WO
                                </a>
                            @endif
                            @if ($latestBayar)
                                <a href="{{ route('dokumen.pembayaran', $latestBayar->id_bukti_pembayaran) }}" target="_blank" class="rounded-lg bg-emerald-50 border border-emerald-200 px-2.5 py-1 text-[11px] font-bold text-emerald-800 hover:bg-emerald-100 transition">
                                    Bukti Bayar
                                </a>
                            @endif
                        </div>

                        <a href="{{ route('teknisi.pemesanan.show', $item->id_pemesanan) }}" class="font-bold text-emerald-700 hover:underline">
                            Detail &rarr;
                        </a>
                    </div>
                </div>
            @endforeach

            <div class="pt-2">
                {{ $riwayats->links() }}
            </div>
        </div>
    @endif
</div>
@endsection
