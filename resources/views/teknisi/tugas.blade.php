@extends('layouts.teknisi')

@section('title', 'Daftar Tugas - Portal Teknisi Ambius')

@section('content')
<div class="space-y-4">
    {{-- Header & Pencarian --}}
    <div class="rounded-2xl bg-white p-4 border border-slate-200 shadow-sm space-y-3">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-lg font-black text-slate-900">Daftar Penugasan</h2>
                <p class="text-xs text-slate-500">Pemesanan yang ditugaskan kepada Anda</p>
            </div>
            <a href="{{ route('teknisi.dashboard') }}" class="text-xs font-bold text-emerald-700 hover:underline">
                &larr; Dashboard
            </a>
        </div>

        {{-- Form Pencarian --}}
        <form action="{{ route('teknisi.tugas') }}" method="GET" class="flex items-center gap-2">
            @if (!empty($currentFilter))
                <input type="hidden" name="status" value="{{ $currentFilter }}">
            @endif
            <div class="relative flex-1">
                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                    <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
                <input type="text" name="search" value="{{ $search }}" placeholder="Cari kode (AMB-..), nama pelanggan..."
                    class="w-full rounded-xl border border-slate-300 bg-white pl-9 pr-3 py-2 text-xs focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
            </div>
            <button type="submit" class="rounded-xl bg-emerald-700 hover:bg-emerald-800 px-4 py-2 text-xs font-bold text-white shadow-sm transition">
                Cari
            </button>
            @if ($search !== '')
                <a href="{{ route('teknisi.tugas', ['status' => $currentFilter]) }}" class="rounded-xl bg-slate-100 px-3 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-200 transition">
                    Reset
                </a>
            @endif
        </form>

        {{-- Filter Status Tabs (Horizontal Scrollable on Mobile) --}}
        <div class="flex items-center gap-1.5 overflow-x-auto pb-1 text-xs no-scrollbar">
            <a href="{{ route('teknisi.tugas', ['search' => $search]) }}"
               class="px-3 py-1.5 rounded-full whitespace-nowrap font-bold transition {{ empty($currentFilter) ? 'bg-emerald-700 text-white shadow-sm' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                Semua ({{ $counts['total'] }})
            </a>
            <a href="{{ route('teknisi.tugas', ['status' => 'Pending', 'search' => $search]) }}"
               class="px-3 py-1.5 rounded-full whitespace-nowrap font-bold transition {{ $currentFilter === 'Pending' ? 'bg-amber-500 text-white shadow-sm' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                Pending ({{ $counts['pending'] }})
            </a>
            <a href="{{ route('teknisi.tugas', ['status' => 'On-Going', 'search' => $search]) }}"
               class="px-3 py-1.5 rounded-full whitespace-nowrap font-bold transition {{ $currentFilter === 'On-Going' ? 'bg-sky-500 text-white shadow-sm' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                On-Going ({{ $counts['ongoing'] }})
            </a>
            <a href="{{ route('teknisi.tugas', ['status' => 'Done', 'search' => $search]) }}"
               class="px-3 py-1.5 rounded-full whitespace-nowrap font-bold transition {{ $currentFilter === 'Done' ? 'bg-emerald-600 text-white shadow-sm' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                Selesai ({{ $counts['done'] }})
            </a>
            <a href="{{ route('teknisi.tugas', ['status' => 'Cancel', 'search' => $search]) }}"
               class="px-3 py-1.5 rounded-full whitespace-nowrap font-bold transition {{ $currentFilter === 'Cancel' ? 'bg-rose-600 text-white shadow-sm' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                Batal ({{ $counts['cancel'] }})
            </a>
        </div>
    </div>

    {{-- Daftar Kartu Tugas --}}
    @if ($pemesanans->isEmpty())
        <div class="rounded-2xl border border-dashed border-slate-200 bg-white p-10 text-center">
            <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 text-slate-400 mb-3">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </div>
            <h3 class="text-sm font-bold text-slate-800">Tidak ada tugas ditemukan</h3>
            <p class="text-xs text-slate-400 mt-0.5">Coba ubah kata kunci pencarian atau filter status Anda.</p>
        </div>
    @else
        <div class="space-y-3">
            @foreach ($pemesanans as $item)
                @php
                    $isNeedAction = $item->perluTindakanTeknisi();
                    $statusBadge = match($item->status_pengerjaan) {
                        'Pending' => 'bg-amber-100 text-amber-800 border-amber-200',
                        'On-Going' => 'bg-sky-100 text-sky-800 border-sky-200',
                        'Done' => 'bg-emerald-100 text-emerald-800 border-emerald-200',
                        'Cancel' => 'bg-rose-100 text-rose-800 border-rose-200',
                        default => 'bg-slate-100 text-slate-800 border-slate-200'
                    };
                @endphp
                <div class="rounded-2xl border {{ $isNeedAction ? 'border-2 border-rose-300 bg-rose-50/30' : 'border-slate-200 bg-white' }} p-4 shadow-sm hover:shadow-md transition">
                    <div class="flex items-start justify-between gap-2 border-b border-slate-100 pb-3">
                        <div>
                            <div class="flex items-center gap-1.5">
                                <span class="font-mono text-xs font-black text-slate-800 bg-slate-100 px-2.5 py-0.5 rounded-lg border border-slate-200">
                                    {{ $item->kode_pemesanan }}
                                </span>
                                @if ($isNeedAction)
                                    <span class="inline-flex items-center gap-1 rounded-full bg-rose-600 px-2 py-0.5 text-[10px] font-bold text-white shadow-sm">
                                        Perlu Tindakan
                                    </span>
                                @endif
                            </div>
                            <h3 class="text-base font-bold text-slate-900 mt-1.5">{{ $item->pelanggan->nama }}</h3>
                            <p class="text-xs text-slate-500">{{ $item->pelanggan->no_hp }} &bull; {{ $item->jenis_properti }} &bull; <b class="{{ ($item->tipe_area ?? 'Indoor') === 'Outdoor' ? 'text-amber-700' : 'text-emerald-700' }}">{{ $item->tipe_area ?? 'Indoor' }}</b></p>
                        </div>

                        <div class="text-right flex-shrink-0">
                            <span class="inline-block rounded-full border px-2.5 py-0.5 text-xs font-bold {{ $statusBadge }}">
                                {{ $item->status_pengerjaan }}
                            </span>
                            <span class="block text-[10px] text-slate-400 mt-1">
                                Tahap {{ $item->tahapPengerjaanTeknisi() }}/5
                            </span>
                        </div>
                    </div>

                    {{-- Informasi Lokasi & Jadwal --}}
                    <div class="mt-3 space-y-2 text-xs text-slate-600">
                        <div class="flex items-start gap-2">
                            <svg class="h-3.5 w-3.5 text-slate-400 mt-0.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            <span class="line-clamp-2 leading-relaxed">{{ $item->alamat_lokasi }}</span>
                        </div>

                        <div class="grid grid-cols-2 gap-2 bg-slate-50 p-2.5 rounded-xl border border-slate-100">
                            <div>
                                <span class="block text-[10px] uppercase font-bold text-slate-400">Jadwal Survei</span>
                                <span class="font-bold text-slate-800">
                                    {{ $item->jadwalPenugasan?->tgl_survei ? \Carbon\Carbon::parse($item->jadwalPenugasan->tgl_survei)->format('d M Y') : '-' }}
                                    <span class="text-slate-500 font-normal">({{ $item->jadwalPenugasan?->jam_survei ?? '-' }})</span>
                                </span>
                            </div>
                            <div>
                                <span class="block text-[10px] uppercase font-bold text-slate-400">Jadwal Pengerjaan</span>
                                <span class="font-bold text-slate-800">
                                    {{ $item->jadwalPenugasan?->tgl_pengerjaan ? \Carbon\Carbon::parse($item->jadwalPenugasan->tgl_pengerjaan)->format('d M Y') : 'Menunggu Survei' }}
                                    @if($item->jadwalPenugasan?->jam_pengerjaan)
                                        <span class="text-slate-500 font-normal">({{ $item->jadwalPenugasan->jam_pengerjaan }})</span>
                                    @endif
                                </span>
                            </div>
                        </div>

                        @if ($isNeedAction)
                            <div class="rounded-xl bg-rose-100/70 border border-rose-200 p-2.5 text-xs text-rose-900">
                                <p class="font-bold text-[11px] mb-1">Catatan Penolakan Admin:</p>
                                <ul class="list-disc list-inside space-y-0.5 text-[11px]">
                                    @foreach ($item->daftarAlasanPenolakan() as $alasan)
                                        <li><b>{{ $alasan['tahap'] }}:</b> {{ $alasan['alasan'] }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <div class="flex items-center justify-between pt-2 border-t border-slate-100">
                            <div class="text-xs">
                                <span class="text-slate-400">Harga Acuan/Final: </span>
                                <span class="font-extrabold text-slate-800">
                                    Rp {{ number_format($item->harga_final ?? $item->harga_estimasi, 0, ',', '.') }}
                                </span>
                            </div>

                            <a href="{{ route('teknisi.pemesanan.show', $item->id_pemesanan) }}"
                               class="rounded-xl bg-emerald-700 hover:bg-emerald-800 px-4 py-2 text-xs font-bold text-white shadow-sm transition">
                                Buka Detail &rarr;
                            </a>
                        </div>
                    </div>
                </div>
            @endforeach

            {{-- Paginasi --}}
            <div class="pt-2">
                {{ $pemesanans->links() }}
            </div>
        </div>
    @endif
</div>
@endsection
