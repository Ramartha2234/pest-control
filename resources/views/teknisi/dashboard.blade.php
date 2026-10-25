@extends('layouts.teknisi')

@section('title', 'Ringkasan Tugas - Portal Teknisi Ambius')

@section('content')
<div class="space-y-4">
    {{-- Header Selamat Bekerja --}}
    <div class="rounded-3xl bg-gradient-to-br from-emerald-800 via-emerald-700 to-teal-800 p-6 text-white shadow-lg">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <span class="inline-block rounded-full bg-emerald-900/60 px-3 py-1 text-[11px] font-semibold text-emerald-200">
                    Teknisi Lapangan
                </span>
                <h2 class="text-xl sm:text-2xl font-black mt-1.5">{{ Auth::user()->nama }}</h2>
                <p class="text-xs text-emerald-100 mt-1 max-w-lg leading-relaxed">
                    Selamat bertugas. Cek jadwal survei & treatment hari ini dan segera tindak lanjuti pengajuan yang ditolak admin.
                </p>
            </div>
            <div class="hidden sm:block text-right">
                <span class="inline-block rounded-xl bg-white/10 backdrop-blur-md px-3.5 py-2 text-xs font-semibold text-emerald-100">
                    {{ \Carbon\Carbon::today()->translatedFormat('l, d F Y') }}
                </span>
            </div>
        </div>
    </div>

    {{-- Banner Notifikasi: Perlu Tindakan (Jika ada pengajuan ditolak admin) --}}
    @if ($countPerluTindakan > 0)
        <div class="rounded-2xl border-2 border-rose-300 bg-rose-50 p-4 shadow-sm animate-pulse">
            <div class="flex items-start gap-3">
                <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-xl bg-rose-600 text-white shadow-sm">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
                <div class="flex-1">
                    <h3 class="text-sm font-black text-rose-900">Perhatian: Ada {{ $countPerluTindakan }} Pengajuan Ditolak!</h3>
                    <p class="text-xs text-rose-700 mt-0.5">Admin menolak bukti foto / hasil survei / dokumen WO / bukti bayar. Harap segera periksa dan unggah ulang.</p>
                    <div class="mt-2.5">
                        <a href="{{ route('teknisi.tugas') }}" class="inline-flex items-center gap-1.5 rounded-xl bg-rose-600 px-3.5 py-1.5 text-xs font-bold text-white shadow-sm hover:bg-rose-700 transition">
                            Lihat Tugas yang Ditolak &rarr;
                        </a>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Kartu Metrik Utama (Mobile-First 3-Grid) --}}
    <div class="grid grid-cols-3 gap-2.5 sm:gap-4">
        {{-- 1. Tugas Hari Ini --}}
        <div class="rounded-2xl border border-slate-200 bg-white p-3.5 sm:p-4 text-center shadow-sm">
            <span class="block text-[11px] font-bold uppercase tracking-wider text-slate-500">Hari Ini</span>
            <div class="text-2xl sm:text-3xl font-black text-emerald-600 mt-1">{{ $countHariIni }}</div>
            <span class="block text-[10px] text-slate-400 mt-0.5 font-medium">Survei / Treatment</span>
        </div>

        {{-- 2. Perlu Tindakan --}}
        <div class="rounded-2xl border {{ $countPerluTindakan > 0 ? 'border-rose-300 bg-rose-50/50' : 'border-slate-200 bg-white' }} p-3.5 sm:p-4 text-center shadow-sm">
            <span class="block text-[11px] font-bold uppercase tracking-wider text-rose-600">Perlu Aksi</span>
            <div class="text-2xl sm:text-3xl font-black text-rose-600 mt-1">{{ $countPerluTindakan }}</div>
            <span class="block text-[10px] text-slate-400 mt-0.5 font-medium">Revisi Ditolak</span>
        </div>

        {{-- 3. Sedang On-Going --}}
        <div class="rounded-2xl border border-slate-200 bg-white p-3.5 sm:p-4 text-center shadow-sm">
            <span class="block text-[11px] font-bold uppercase tracking-wider text-sky-600">On-Going</span>
            <div class="text-2xl sm:text-3xl font-black text-sky-600 mt-1">{{ $countOngoing }}</div>
            <span class="block text-[10px] text-slate-400 mt-0.5 font-medium">Sedang Kerja</span>
        </div>
    </div>

    {{-- Tombol Cepat Navigasi Tugas --}}
    <div class="grid grid-cols-2 gap-2.5 sm:gap-4">
        <a href="{{ route('teknisi.tugas') }}" class="flex items-center justify-between p-4 rounded-2xl bg-white border border-slate-200 hover:border-emerald-400 hover:shadow-md shadow-xs transition group">
            <div>
                <span class="block text-xs font-bold text-slate-800 group-hover:text-emerald-700">Semua Tugas Aktif</span>
                <span class="block text-[11px] text-slate-500 mt-0.5">{{ $countTotalAktif }} penugasan aktif</span>
            </div>
            <span class="text-slate-400 group-hover:text-emerald-700 font-bold text-sm transition group-hover:translate-x-0.5">&rarr;</span>
        </a>

        <a href="{{ route('teknisi.riwayat') }}" class="flex items-center justify-between p-4 rounded-2xl bg-white border border-slate-200 hover:border-emerald-400 hover:shadow-md shadow-xs transition group">
            <div>
                <span class="block text-xs font-bold text-slate-800 group-hover:text-emerald-700">Riwayat Selesai</span>
                <span class="block text-[11px] text-slate-500 mt-0.5">{{ $countSelesai }} tugas Done</span>
            </div>
            <span class="text-slate-400 group-hover:text-emerald-700 font-bold text-sm transition group-hover:translate-x-0.5">&rarr;</span>
        </a>
    </div>

    {{-- Seksi Tugas Hari Ini --}}
    @if ($tugasHariIni->isNotEmpty())
        <div class="space-y-2">
            <div class="flex items-center justify-between px-1">
                <h3 class="text-xs font-extrabold uppercase tracking-wider text-slate-700 flex items-center gap-1.5">
                    <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                    Jadwal Hari Ini ({{ \Carbon\Carbon::today()->format('d M Y') }})
                </h3>
            </div>

            <div class="space-y-2.5">
                @foreach ($tugasHariIni as $task)
                    <div class="rounded-2xl border-2 border-emerald-500/30 bg-white p-4 shadow-sm hover:shadow-md transition">
                        <div class="flex items-start justify-between gap-2 pb-2 border-b border-slate-100">
                            <div>
                                <span class="font-mono text-[11px] font-black text-emerald-800 bg-emerald-50 px-2.5 py-0.5 rounded-lg border border-emerald-200">
                                    {{ $task->kode_pemesanan }}
                                </span>
                                <h4 class="text-sm font-bold text-slate-900 mt-1">{{ $task->pelanggan->nama }}</h4>
                                <p class="text-[11px] text-slate-500">{{ $task->tarifLayanan->jenis_layanan ?? 'Pest Control' }} &bull; <b class="{{ ($task->tipe_area ?? 'Indoor') === 'Outdoor' ? 'text-amber-700' : 'text-emerald-700' }}">{{ $task->tipe_area ?? 'Indoor' }}</b></p>
                            </div>
                            <span class="rounded-full px-2.5 py-0.5 text-[11px] font-bold {{ $task->status_pengerjaan === 'On-Going' ? 'bg-sky-100 text-sky-800' : 'bg-amber-100 text-amber-800' }}">
                                {{ $task->status_pengerjaan }}
                            </span>
                        </div>

                        <div class="mt-2.5 space-y-1.5 text-xs text-slate-600">
                            <p class="flex items-start gap-1.5">
                                <svg class="h-3.5 w-3.5 text-slate-400 mt-0.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                <span class="line-clamp-2 leading-relaxed">{{ $task->alamat_lokasi }}</span>
                            </p>
                            <div class="grid grid-cols-2 gap-2 pt-1 font-medium">
                                <div class="bg-slate-50 p-2 rounded-xl text-[11px]">
                                    <span class="block text-[10px] text-slate-400 uppercase font-bold">Survei</span>
                                    {{ $task->jadwalPenugasan?->tgl_survei ? \Carbon\Carbon::parse($task->jadwalPenugasan->tgl_survei)->format('d M') : '-' }} ({{ $task->jadwalPenugasan?->jam_survei ?? '-' }})
                                </div>
                                <div class="bg-slate-50 p-2 rounded-xl text-[11px]">
                                    <span class="block text-[10px] text-slate-400 uppercase font-bold">Treatment</span>
                                    {{ $task->jadwalPenugasan?->tgl_pengerjaan ? \Carbon\Carbon::parse($task->jadwalPenugasan->tgl_pengerjaan)->format('d M') : 'Menunggu' }}
                                    @if ($task->jadwalPenugasan?->jam_pengerjaan)
                                        ({{ $task->jadwalPenugasan->jam_pengerjaan }})
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="mt-3 pt-2 border-t border-slate-100 flex items-center justify-between">
                            <span class="text-[11px] text-slate-500">Estimasi: <b>Rp {{ number_format($task->harga_estimasi, 0, ',', '.') }}</b></span>
                            <a href="{{ route('teknisi.pemesanan.show', $task->id_pemesanan) }}" class="rounded-xl bg-emerald-600 hover:bg-emerald-700 px-4 py-2 text-xs font-bold text-white shadow-sm transition">
                                Buka Detail &rarr;
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Seksi Jadwal Terdekat Mendatang --}}
    <div class="space-y-2">
        <div class="flex items-center justify-between px-1">
            <h3 class="text-xs font-extrabold uppercase tracking-wider text-slate-700">
                Jadwal Lapangan Terdekat
            </h3>
            <a href="{{ route('teknisi.tugas') }}" class="text-xs font-bold text-emerald-700 hover:underline">
                Lihat Semua &rarr;
            </a>
        </div>

        @if ($jadwalTerdekat->isEmpty())
            <div class="rounded-2xl border border-dashed border-slate-200 bg-white p-8 text-center">
                <p class="text-xs font-bold text-slate-700">Tidak ada jadwal aktif mendatang.</p>
                <p class="text-[11px] text-slate-500 mt-1">Semua penugasan telah selesai atau belum ada penugasan baru.</p>
            </div>
        @else
            <div class="space-y-2.5">
                @foreach ($jadwalTerdekat as $item)
                    @php
                        $isNeedAction = $item->perluTindakanTeknisi();
                    @endphp
                    <div class="rounded-2xl border {{ $isNeedAction ? 'border-rose-300 bg-rose-50/40' : 'border-slate-200 bg-white' }} p-4 shadow-sm hover:shadow-md transition">
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <div class="flex items-center gap-1.5">
                                    <span class="font-mono text-[11px] font-black text-slate-800 bg-slate-100 px-2 py-0.5 rounded">
                                        {{ $item->kode_pemesanan }}
                                    </span>
                                    @if ($isNeedAction)
                                        <span class="inline-flex items-center gap-1 rounded-full bg-rose-500 px-2 py-0.5 text-[10px] font-bold text-white shadow-sm">
                                            Perlu Aksi
                                        </span>
                                    @endif
                                </div>
                                <h4 class="text-sm font-bold text-slate-900 mt-1">{{ $item->pelanggan->nama }}</h4>
                                <p class="text-[11px] text-slate-500">{{ $item->jenis_properti }} &bull; {{ $item->tarifLayanan->jenis_layanan ?? '-' }}</p>
                            </div>
                            <span class="rounded-full px-2.5 py-0.5 text-[11px] font-bold {{ $item->status_pengerjaan === 'On-Going' ? 'bg-sky-100 text-sky-800' : 'bg-amber-100 text-amber-800' }}">
                                {{ $item->status_pengerjaan }}
                            </span>
                        </div>

                        <div class="mt-2.5 grid grid-cols-2 gap-2 text-xs text-slate-600 bg-slate-50 p-2.5 rounded-xl border border-slate-100">
                            <div>
                                <span class="block text-[10px] text-slate-400 uppercase font-bold">Survei</span>
                                <span class="font-semibold text-slate-800">
                                    {{ $item->jadwalPenugasan?->tgl_survei ? \Carbon\Carbon::parse($item->jadwalPenugasan->tgl_survei)->format('d M Y') : '-' }}
                                    ({{ $item->jadwalPenugasan?->jam_survei ?? '-' }})
                                </span>
                            </div>
                            <div>
                                <span class="block text-[10px] text-slate-400 uppercase font-bold">Treatment</span>
                                <span class="font-semibold text-slate-800">
                                    {{ $item->jadwalPenugasan?->tgl_pengerjaan ? \Carbon\Carbon::parse($item->jadwalPenugasan->tgl_pengerjaan)->format('d M Y') : 'Menunggu' }}
                                    @if ($item->jadwalPenugasan?->jam_pengerjaan)
                                        ({{ $item->jadwalPenugasan->jam_pengerjaan }})
                                    @endif
                                </span>
                            </div>
                        </div>

                        <div class="mt-3 flex items-center justify-between pt-2 border-t border-slate-100">
                            <span class="text-xs text-slate-500">Estimasi: <b>Rp {{ number_format($item->harga_estimasi, 0, ',', '.') }}</b></span>
                            <a href="{{ route('teknisi.pemesanan.show', $item->id_pemesanan) }}" class="rounded-xl bg-emerald-600 hover:bg-emerald-700 px-3.5 py-1.5 text-xs font-bold text-white shadow-sm transition">
                                Buka Tugas &rarr;
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
@endsection
