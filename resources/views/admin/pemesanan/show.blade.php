@extends('layouts.admin')

@section('title', 'Detail Pemesanan: ' . $pemesanan->kode_pemesanan . ' - CV. Ambius')
@section('page_title', 'Kelola Detail Pemesanan')

@section('content')
<div class="space-y-6">
    {{-- Top Back & Meta Bar --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <a href="{{ route('admin.pemesanan.index') }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-primary-600 hover:text-primary-800">
            &larr; Kembali ke Daftar Pemesanan
        </a>
        <div class="flex items-center gap-2">
            <span class="rounded-lg bg-gray-200 px-3 py-1 font-mono text-xs font-extrabold text-gray-800">
                {{ $pemesanan->kode_pemesanan }}
            </span>
            @php
                $stColor = match($pemesanan->status_pengerjaan) {
                    'Pending' => 'bg-amber-100 text-amber-800 border-amber-200',
                    'On-Going' => 'bg-sky-100 text-sky-800 border-sky-200',
                    'Done' => 'bg-emerald-100 text-emerald-800 border-emerald-200',
                    'Cancel' => 'bg-rose-100 text-rose-800 border-rose-200',
                    default => 'bg-gray-100 text-gray-800 border-gray-200'
                };
                $pbColor = match($pemesanan->status_pembayaran) {
                    'Lunas' => 'bg-emerald-100 text-emerald-800 border-emerald-200',
                    'Ditolak' => 'bg-rose-100 text-rose-800 border-rose-200',
                    default => 'bg-amber-100 text-amber-800 border-amber-200'
                };
            @endphp
            <span class="rounded-full border px-3 py-1 text-xs font-extrabold {{ $stColor }}">
                {{ $pemesanan->status_pengerjaan }}
            </span>
            <span class="rounded-full border px-3 py-1 text-xs font-extrabold {{ $pbColor }}">
                {{ $pemesanan->status_pembayaran }}
            </span>
            @if ($pemesanan->tgl_verifikasi)
                <span class="inline-flex items-center gap-1 rounded-full border border-emerald-300 bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-800 shadow-2xs">
                    <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                    <span>ACC: {{ $pemesanan->tgl_verifikasi->translatedFormat('d M Y, H:i') }} WITA</span>
                </span>
            @endif
            @if ($pemesanan->wajib_dp && $pemesanan->status_dp === 'Lunas' && ($pemesanan->tgl_verifikasi_dp ?? $pemesanan->tgl_bayar_dp))
                <span class="inline-flex items-center gap-1 rounded-full border border-emerald-300 bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-800 shadow-2xs">
                    <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                    <span>ACC DP: {{ ($pemesanan->tgl_verifikasi_dp ?? $pemesanan->tgl_bayar_dp)->translatedFormat('d M Y, H:i') }} WITA</span>
                </span>
            @endif
        </div>
    </div>

    {{-- Alert Batas Waktu / Status Cancel --}}
    @if ($pemesanan->status_pengerjaan === 'Cancel')
        <div class="rounded-2xl border border-rose-200 bg-rose-50 p-4 text-xs text-rose-800 space-y-1">
            <h4 class="font-bold text-sm text-rose-900">
                Pemesanan Dibatalkan
            </h4>
            <p class="mt-1">Dibatalkan oleh: <b>{{ $pemesanan->dibatalkan_oleh ?: 'Sistem' }}</b></p>
            <p class="mt-0.5">Alasan: <span class="italic">{{ $pemesanan->alasan_pembatalan ?: 'Tidak ada alasan' }}</span></p>
        </div>
    @elseif ($pemesanan->status_pengerjaan === 'Pending' && !$pemesanan->tgl_verifikasi)
        <div class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-xs text-amber-800 space-y-1">
            <h4 class="font-bold text-sm text-amber-900">Pemesanan Baru Perlu Diverifikasi</h4>
            <p class="mt-0.5 leading-relaxed">
                Batas waktu konfirmasi: <b>{{ $pemesanan->batas_konfirmasi->translatedFormat('l, d F Y H:i') }}</b>
                ({{ $pemesanan->sisa_waktu_verifikasi }}).
                Jika tidak diverifikasi hingga batas waktu, sistem otomatis membatalkan pemesanan ini (Auto-Cancel).
            </p>
        </div>
    @endif

    {{-- Perhitungan Deviasi Jadwal vs Realisasi --}}
    @php
        $jadwalKerjaRaw = $pemesanan->jadwalPenugasan?->tgl_pengerjaan ? \Carbon\Carbon::parse($pemesanan->jadwalPenugasan->tgl_pengerjaan)->toDateString() : null;
        $realisasiKerjaRaw = $pemesanan->tgl_selesai 
            ? \Carbon\Carbon::parse($pemesanan->tgl_selesai)->toDateString() 
            : ($latestBuktiPengerjaan ? $latestBuktiPengerjaan->created_at->toDateString() : ($latestWo?->tgl_pengerjaan ? \Carbon\Carbon::parse($latestWo->tgl_pengerjaan)->toDateString() : null));
        $isDeviasiJadwal = $jadwalKerjaRaw && $realisasiKerjaRaw && ($jadwalKerjaRaw !== $realisasiKerjaRaw);
        $diffHariKerja = $isDeviasiJadwal ? \Carbon\Carbon::parse($realisasiKerjaRaw)->diffInDays(\Carbon\Carbon::parse($jadwalKerjaRaw), false) : 0;
        $realisasiDisplayDate = $realisasiKerjaRaw ? \Carbon\Carbon::parse($realisasiKerjaRaw)->locale('id')->translatedFormat('l, d F Y') : '';
        $jadwalDisplayDate = $jadwalKerjaRaw ? \Carbon\Carbon::parse($jadwalKerjaRaw)->locale('id')->translatedFormat('l, d F Y') : '';

        $jadwalSurveiRaw = $pemesanan->jadwalPenugasan?->tgl_survei ? \Carbon\Carbon::parse($pemesanan->jadwalPenugasan->tgl_survei)->toDateString() : null;
        $realisasiSurveiRaw = $latestBuktiSurvei ? $latestBuktiSurvei->created_at->toDateString() : ($latestSurvei ? $latestSurvei->created_at->toDateString() : null);
        $isDeviasiSurvei = $jadwalSurveiRaw && $realisasiSurveiRaw && ($jadwalSurveiRaw !== $realisasiSurveiRaw);
        $diffHariSurvei = $isDeviasiSurvei ? \Carbon\Carbon::parse($realisasiSurveiRaw)->diffInDays(\Carbon\Carbon::parse($jadwalSurveiRaw), false) : 0;
        $realisasiSurveiDisplayDate = $realisasiSurveiRaw ? \Carbon\Carbon::parse($realisasiSurveiRaw)->locale('id')->translatedFormat('l, d F Y') : '';
        $jadwalSurveiDisplayDate = $jadwalSurveiRaw ? \Carbon\Carbon::parse($jadwalSurveiRaw)->locale('id')->translatedFormat('l, d F Y') : '';

        $isSurveiOverdueIncomplete = $jadwalSurveiRaw 
            && $jadwalSurveiRaw < now()->toDateString() 
            && (! $latestBuktiSurvei || $latestBuktiSurvei->status_verifikasi !== 'Disetujui') 
            && ! in_array($pemesanan->status_pengerjaan, ['Done', 'Cancel']);

        $isKerjaOverdueIncomplete = $jadwalKerjaRaw 
            && $jadwalKerjaRaw < now()->toDateString() 
            && ! in_array($pemesanan->status_pengerjaan, ['Done', 'Cancel']);
    @endphp

    {{-- Banner Peringatan Jadwal Survei Terlewat (Aksi Teknisi Terkunci) --}}
    @if ($isSurveiOverdueIncomplete)
        <div class="rounded-2xl border border-rose-300 bg-rose-50/90 p-4 shadow-sm space-y-2">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div class="flex items-start sm:items-center gap-2.5">
                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-rose-600 text-white shadow-2xs">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h5 class="text-xs sm:text-sm font-bold text-rose-950">
                                Perhatian: Jadwal Survei Terlewat &bull; Aksi Teknisi Terkunci Otomatis
                            </h5>
                            <span class="rounded-full px-2 py-0.5 text-[10px] font-bold bg-rose-100 text-rose-800 border border-rose-200">
                                Perlu Reschedule
                            </span>
                        </div>
                        <p class="text-[11px] text-rose-900 mt-0.5">
                            Jadwal survei tercatat: <b>{{ $jadwalSurveiDisplayDate }}</b> telah terlewat. Form teknisi terkunci sampai tanggal jadwal diperbarui.
                        </p>
                    </div>
                </div>

                @if ($pemesanan->jadwalPenugasan)
                    <div class="shrink-0">
                        <button type="button" 
                                onclick="rescheduleSurveiToToday()"
                                style="background-color: #e11d48 !important; color: #ffffff !important;"
                                class="inline-flex items-center gap-1.5 rounded-xl bg-rose-600 hover:bg-rose-700 text-white px-3.5 py-2 text-xs font-bold shadow-sm transition cursor-pointer active:scale-95">
                            <svg class="w-4 h-4 text-white shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            <span class="text-white font-bold" style="color: #ffffff !important;">Ubah / Reschedule Jadwal Survei</span>
                        </button>
                    </div>
                @endif
            </div>
        </div>
    @endif

    {{-- Banner Peringatan Jadwal Treatment Terlewat (Aksi Teknisi Terkunci) --}}
    @if ($isKerjaOverdueIncomplete)
        <div class="rounded-2xl border border-rose-300 bg-rose-50/90 p-4 shadow-sm space-y-2">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div class="flex items-start sm:items-center gap-2.5">
                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-rose-600 text-white shadow-2xs">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h5 class="text-xs sm:text-sm font-bold text-rose-950">
                                Perhatian: Jadwal Pengerjaan Terlewat &bull; Aksi Teknisi Terkunci Otomatis
                            </h5>
                            <span class="rounded-full px-2 py-0.5 text-[10px] font-bold bg-rose-100 text-rose-800 border border-rose-200">
                                Perlu Reschedule
                            </span>
                        </div>
                        <p class="text-[11px] text-rose-900 mt-0.5">
                            Jadwal treatment tercatat: <b>{{ $jadwalDisplayDate }}</b> telah terlewat. Form teknisi terkunci sampai tanggal jadwal diselaraskan ke tanggal pengerjaan baru.
                        </p>
                    </div>
                </div>

                @if ($pemesanan->jadwalPenugasan)
                    <div class="shrink-0">
                        <button type="button" 
                                onclick="rescheduleTreatmentToToday()"
                                style="background-color: #e11d48 !important; color: #ffffff !important;"
                                class="inline-flex items-center gap-1.5 rounded-xl bg-rose-600 hover:bg-rose-700 text-white px-3.5 py-2 text-xs font-bold shadow-sm transition cursor-pointer active:scale-95">
                            <svg class="w-4 h-4 text-white shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            <span class="text-white font-bold" style="color: #ffffff !important;">Ubah / Reschedule Jadwal Treatment</span>
                        </button>
                    </div>
                @endif
            </div>
        </div>
    @endif

    {{-- Peringatan Deviasi Jadwal vs Realisasi Treatment --}}
    @if ($isDeviasiJadwal)
        <div class="rounded-2xl border border-amber-300 bg-amber-50/90 p-4 shadow-sm space-y-2">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div class="flex items-start sm:items-center gap-2.5">
                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-amber-600 text-white shadow-2xs">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h5 class="text-xs sm:text-sm font-bold text-amber-950">
                                Catatan: Deviasi Tanggal Realisasi Treatment Berbeda dengan Jadwal Awal
                            </h5>
                            <span class="rounded-full px-2 py-0.5 text-[10px] font-bold {{ $diffHariKerja > 0 ? 'bg-sky-100 text-sky-800 border border-sky-200' : 'bg-rose-100 text-rose-800 border border-rose-200' }}">
                                {{ $diffHariKerja > 0 ? abs($diffHariKerja) . ' Hari Lebih Cepat' : abs($diffHariKerja) . ' Hari Terlambat' }}
                            </span>
                        </div>
                        <p class="text-[11px] text-amber-900 mt-0.5">
                            Realisasi pengerjaan: <b>{{ $realisasiDisplayDate }}</b> &bull; Jadwal penugasan: <b>{{ $jadwalDisplayDate }}</b>.
                        </p>
                    </div>
                </div>

                @if ($pemesanan->jadwalPenugasan)
                    <div class="shrink-0">
                        <button type="button" 
                                onclick="syncModalTglKerja('{{ $realisasiKerjaRaw }}', '{{ $realisasiDisplayDate }}')"
                                style="background-color: #d97706 !important; color: #ffffff !important;"
                                class="inline-flex items-center gap-1.5 rounded-xl bg-amber-600 hover:bg-amber-700 text-white px-3.5 py-2 text-xs font-bold shadow-sm transition cursor-pointer active:scale-95">
                            <svg class="w-4 h-4 text-white shrink-0" style="color: #ffffff !important;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                            <span class="text-white font-bold" style="color: #ffffff !important;">Samakan Jadwal ke Tanggal Realisasi</span>
                        </button>
                    </div>
                @endif
            </div>
        </div>
    @endif

    {{-- Peringatan Deviasi Jadwal Survei Lapangan --}}
    @if ($isDeviasiSurvei)
        <div class="rounded-2xl border border-sky-300 bg-sky-50/90 p-4 shadow-sm space-y-2">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div class="flex items-start sm:items-center gap-2.5">
                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-sky-600 text-white shadow-2xs">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h5 class="text-xs sm:text-sm font-bold text-sky-950">
                                Catatan: Realisasi Survei Dilakukan Berbeda Hari dari Jadwal Awal
                            </h5>
                            <span class="rounded-full px-2 py-0.5 text-[10px] font-bold bg-sky-100 text-sky-800 border border-sky-200">
                                {{ $diffHariSurvei > 0 ? abs($diffHariSurvei) . ' Hari Lebih Awal' : abs($diffHariSurvei) . ' Hari Terlambat' }}
                            </span>
                        </div>
                        <p class="text-[11px] text-sky-900 mt-0.5">
                            Realisasi survei tercatat: <b>{{ $realisasiSurveiDisplayDate }}</b> &bull; Jadwal penugasan: <b>{{ $jadwalSurveiDisplayDate }}</b>.
                        </p>
                    </div>
                </div>

                @if ($pemesanan->jadwalPenugasan)
                    <div class="shrink-0">
                        <button type="button" 
                                onclick="syncModalTglSurvei('{{ $realisasiSurveiRaw }}', '{{ $realisasiSurveiDisplayDate }}')"
                                style="background-color: #0284c7 !important; color: #ffffff !important;"
                                class="inline-flex items-center gap-1.5 rounded-xl bg-sky-600 hover:bg-sky-700 text-white px-3.5 py-2 text-xs font-bold shadow-sm transition cursor-pointer active:scale-95">
                            <svg class="w-4 h-4 text-white shrink-0" style="color: #ffffff !important;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            <span class="text-white font-bold" style="color: #ffffff !important;">Sesuaikan Jadwal Survei</span>
                        </button>
                    </div>
                @endif
            </div>
        </div>
    @endif

    {{-- Grid 2 Kolom: Info Pelanggan & Jadwal/Harga --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Kolom Kiri (2 Span): Info Pelanggan & Alamat --}}
        <div class="lg:col-span-2 space-y-6">
            <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm space-y-4">
                <div class="flex items-start justify-between border-b border-gray-100 pb-4">
                    <div>
                        <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Pelanggan</span>
                        <h3 class="text-lg font-extrabold text-gray-900 mt-0.5">{{ $pemesanan->pelanggan->nama }}</h3>
                        @if ($pemesanan->pelanggan->nama_usaha)
                            <p class="text-xs font-semibold text-primary-700">{{ $pemesanan->pelanggan->nama_usaha }}</p>
                        @endif
                    </div>
                    <a href="https://wa.me/{{ preg_replace('/^0/', '62', preg_replace('/[^0-9]/', '', $pemesanan->pelanggan->no_hp)) }}"
                       target="_blank" rel="noopener"
                       class="rounded-xl bg-emerald-600 px-3.5 py-2 text-xs font-bold text-white shadow-sm hover:bg-emerald-700 transition flex items-center gap-1.5">
                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                        <span>Chat WA ({{ $pemesanan->pelanggan->no_hp }})</span>
                    </a>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                    <div>
                        <span class="text-gray-400 font-semibold block">Jenis Properti</span>
                        <span class="font-bold text-gray-800 block mt-0.5">{{ $pemesanan->jenis_properti }}</span>
                    </div>
                    <div>
                        <span class="text-gray-400 font-semibold block">Layanan Pest Control</span>
                        <span class="font-bold text-gray-800 block mt-0.5">{{ $pemesanan->tarifLayanan->jenis_layanan ?? '-' }}</span>
                    </div>
                    <div>
                        <span class="text-gray-400 font-semibold block">Tipe Area Penanganan</span>
                        <div class="mt-1">
                            @if (($pemesanan->tipe_area ?? 'Indoor') === 'Outdoor')
                                <span class="inline-flex items-center font-extrabold text-amber-800 bg-amber-100 border border-amber-200 px-2.5 py-0.5 rounded-lg">
                                    Outdoor (+Rp 100.000)
                                </span>
                            @else
                                <span class="inline-flex items-center font-extrabold text-emerald-800 bg-emerald-100 border border-emerald-200 px-2.5 py-0.5 rounded-lg">
                                    Indoor (Tarif Tetap)
                                </span>
                            @endif
                        </div>
                    </div>
                    <div>
                        <span class="text-gray-400 font-semibold block">Estimasi Luas Area</span>
                        <span class="font-bold text-gray-800 block mt-0.5">{{ $pemesanan->ukuran_area ? $pemesanan->ukuran_area . ' m2' : 'Sesuai Standar Paket' }}</span>
                    </div>
                    <div>
                        <span class="text-gray-400 font-semibold block">Jadwal Diminta Pelanggan</span>
                        <span class="font-bold text-gray-800 block mt-0.5">
                            {{ $pemesanan->tgl_survei_diminta ? \Carbon\Carbon::parse($pemesanan->tgl_survei_diminta)->format('d M Y') : '-' }}
                            ({{ $pemesanan->jam_survei_diminta ?? '-' }})
                        </span>
                    </div>
                </div>

                <div class="text-xs pt-2 border-t border-gray-100">
                    <span class="text-gray-400 font-semibold block">Alamat Lengkap Lokasi</span>
                    <p class="mt-1 bg-gray-50 p-3 rounded-xl border border-gray-100 text-gray-800 leading-relaxed">{{ $pemesanan->alamat_lokasi }}</p>
                </div>

                <div class="text-xs">
                    <span class="text-gray-400 font-semibold block">Keluhan / Masalah Hama</span>
                    <p class="mt-1 bg-amber-50/50 p-3 rounded-xl border border-amber-100 text-gray-800 italic leading-relaxed">"{{ $pemesanan->keluhan }}"</p>
                </div>
            </div>

            {{-- ======================================================== --}}
            {{-- DOWN PAYMENT (DP 10%) - VERIFIKASI BUKTI TRANSFER        --}}
            {{-- ======================================================== --}}
            @if ($pemesanan->wajib_dp)
                @php
                    $isDpLunas = $pemesanan->status_dp === 'Lunas';
                    $isDpMenunggu = $pemesanan->status_dp === 'Menunggu Verifikasi';
                    $isDpDitolak = $pemesanan->status_dp === 'Ditolak';
                @endphp

                <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm space-y-4">
                    <div class="flex items-center justify-between pb-3 border-b border-gray-100 flex-wrap gap-2">
                        <div>
                            <h4 class="text-base font-bold text-gray-900">
                                Verifikasi Down Payment (DP 10%)
                            </h4>
                            <p class="text-xs text-gray-500 mt-0.5">
                                Pemesanan berskala besar (≥ Rp 10.000.000) wajib DP 10% sebelum jadwal survei dibuka.
                            </p>
                        </div>
                        <span class="rounded-full px-2.5 py-0.5 text-[11px] font-bold {{ $isDpLunas ? 'bg-emerald-100 text-emerald-800' : ($isDpDitolak ? 'bg-rose-100 text-rose-800' : ($isDpMenunggu ? 'bg-amber-100 text-amber-800' : 'bg-gray-100 text-gray-600')) }}">
                            @if ($isDpLunas)
                                DP Lunas
                            @elseif ($isDpDitolak)
                                Bukti Ditolak
                            @elseif ($isDpMenunggu)
                                Menunggu Verifikasi
                            @else
                                Belum Diunggah
                            @endif
                        </span>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
                        {{-- 1. Rincian Tagihan DP --}}
                        <div class="rounded-xl border border-gray-200 bg-gray-50/70 p-4 space-y-3">
                            <span class="font-bold text-gray-800 block">1. Rincian Down Payment</span>
                            <div class="space-y-2">
                                <div class="flex items-center justify-between">
                                    <span class="text-gray-500">Total Estimasi Pesanan:</span>
                                    <span class="font-bold text-gray-800">Rp {{ number_format($pemesanan->harga_estimasi, 0, ',', '.') }}</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-gray-500">Nominal DP 10% Wajib:</span>
                                    <span class="text-sm font-black text-emerald-700">Rp {{ number_format($pemesanan->nominal_dp, 0, ',', '.') }}</span>
                                </div>
                                <div class="flex items-center justify-between text-[11px] pt-2 border-t border-gray-200 text-gray-500">
                                    <span>Rekening Tujuan:</span>
                                    <span class="font-bold text-gray-700">BCA 123-456-7890</span>
                                </div>
                            </div>

                            @if ($isDpLunas)
                                <div class="rounded-xl bg-emerald-50 border-2 border-emerald-300 p-4 text-xs text-emerald-950 space-y-3 shadow-2xs">
                                    <div class="flex items-center gap-2 font-black text-emerald-900 text-xs sm:text-sm">
                                        <span class="flex h-5 w-5 items-center justify-center rounded-full bg-emerald-600 text-white shrink-0">
                                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                                            </svg>
                                        </span>
                                        <span>DP Telah Di-ACC & Terverifikasi</span>
                                    </div>
                                    <div class="space-y-2 text-xs pt-2.5 border-t border-emerald-200">
                                        <div class="bg-white/80 rounded-xl p-2.5 border border-emerald-200/80">
                                            <span class="text-[11px] font-semibold text-emerald-800 block">Waktu ACC Admin:</span>
                                            <strong class="text-emerald-950 font-black block text-xs sm:text-sm mt-0.5">
                                                {{ ($pemesanan->tgl_verifikasi_dp ?? $pemesanan->tgl_bayar_dp)?->translatedFormat('d F Y') }} pukul {{ ($pemesanan->tgl_verifikasi_dp ?? $pemesanan->tgl_bayar_dp)?->format('H:i') }} WITA
                                            </strong>
                                        </div>
                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-[11px]">
                                            <div class="bg-white/80 rounded-xl p-2.5 border border-emerald-200/80">
                                                <span class="text-gray-500 block">Di-ACC oleh Admin:</span>
                                                <strong class="text-emerald-900 font-bold block truncate mt-0.5">{{ $pemesanan->admin?->nama ?? 'Admin' }}</strong>
                                            </div>
                                            <div class="bg-white/80 rounded-xl p-2.5 border border-emerald-200/80">
                                                <span class="text-gray-500 block">Nominal DP Lunas:</span>
                                                <strong class="text-emerald-900 font-black block mt-0.5">Rp {{ number_format($pemesanan->nominal_dp, 0, ',', '.') }}</strong>
                                            </div>
                                        </div>
                                    </div>
                                    @if ($pemesanan->catatan_dp)
                                        <div class="text-gray-600 pt-1 text-[11px] bg-white/60 rounded-xl p-2.5 border border-emerald-200/70">
                                            <b class="text-emerald-900">Catatan:</b> {{ $pemesanan->catatan_dp }}
                                        </div>
                                    @endif
                                </div>
                            @elseif ($isDpDitolak)
                                <div class="rounded-lg bg-rose-50 border border-rose-200 p-2.5 text-[11px] text-rose-950 space-y-1">
                                    <div class="font-bold text-rose-800">✕ Bukti DP Ditolak:</div>
                                    <div class="text-rose-900 leading-relaxed">{{ $pemesanan->alasan_penolakan_dp ?? ($pemesanan->catatan_dp ?? 'Bukti transfer tidak valid atau belum masuk mutasi rekening.') }}</div>
                                    <div class="text-rose-600 italic">Menunggu pelanggan mengunggah ulang bukti transfer baru.</div>
                                </div>
                            @elseif ($isDpMenunggu)
                                <div class="rounded-lg bg-amber-50 border border-amber-200 p-2.5 text-[11px] text-amber-900 leading-relaxed">
                                    Pelanggan telah mengunggah bukti transfer DP. Silakan periksa struk di sebelah kanan dan cocokkan dengan mutasi rekening sebelum menyetujui.
                                </div>
                            @else
                                <div class="rounded-lg bg-gray-100 border border-gray-200 p-2.5 text-[11px] text-gray-600 leading-relaxed">
                                    Menunggu pelanggan mengunggah bukti transfer DP 10% melalui halaman pelacakan pesanan atau konfirmasi.
                                </div>
                            @endif
                        </div>

                        {{-- 2. Bukti Transfer DP & Aksi Verifikasi --}}
                        <div class="rounded-xl border border-gray-200 bg-gray-50/70 p-4 space-y-3">
                            <div class="flex items-center justify-between">
                                <span class="font-bold text-gray-800">2. Bukti Transfer DP</span>
                                @if ($pemesanan->bukti_dp)
                                    <span class="rounded-full px-2.5 py-0.5 text-[11px] font-bold {{ $isDpLunas ? 'bg-emerald-100 text-emerald-800' : ($isDpDitolak ? 'bg-rose-100 text-rose-800' : 'bg-amber-100 text-amber-800') }}">
                                        {{ $isDpLunas ? 'Disetujui' : ($isDpDitolak ? 'Ditolak' : 'Menunggu') }}
                                    </span>
                                @else
                                    <span class="text-gray-400">Belum diunggah</span>
                                @endif
                            </div>

                            @if ($pemesanan->bukti_dp)
                                <div class="space-y-2">
                                    <div class="relative overflow-hidden rounded-lg border border-gray-200 bg-gray-100 group max-h-48 flex items-center justify-center">
                                        <img src="{{ route('dokumen.dp', $pemesanan->id_pemesanan) }}" alt="Bukti Transfer DP" class="w-full h-40 object-cover object-center group-hover:scale-105 transition-transform" onerror="this.onerror=null; this.parentElement.innerHTML='<div class=\'p-3 text-center text-gray-500 text-xs\'>Berkas bukti (klik untuk membuka): <a href=\'{{ route('dokumen.dp', $pemesanan->id_pemesanan) }}\' target=\'_blank\' class=\'font-bold text-primary-700 underline\'>Buka Dokumen</a></div>'">
                                        <a href="{{ route('dokumen.dp', $pemesanan->id_pemesanan) }}" target="_blank" class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center text-white font-bold text-xs gap-1">
                                            <span>🔍 Perbesar Bukti DP</span>
                                        </a>
                                    </div>
                                    <div class="flex items-center justify-between text-gray-600 text-[11px]">
                                        <span>File Bukti Transfer Pelanggan</span>
                                        <a href="{{ route('dokumen.dp', $pemesanan->id_pemesanan) }}" target="_blank" class="font-bold text-primary-700 underline">Lihat / Unduh Dokumen</a>
                                    </div>
                                </div>

                                @if ($isDpMenunggu || (!$isDpLunas && !$isDpDitolak))
                                    <form action="{{ route('admin.pemesanan.verifikasi-dp', $pemesanan->id_pemesanan) }}" method="POST" class="pt-2 border-t border-gray-200 space-y-2">
                                        @csrf
                                        <input type="text" name="alasan_penolakan" placeholder="Alasan jika menolak bukti DP..." class="w-full rounded-xl border-gray-300 px-4 py-2.5 text-xs sm:text-sm text-gray-800 placeholder-gray-400 shadow-sm focus:border-primary-500 focus:ring-primary-500 transition">
                                        <div class="flex gap-2">
                                            <button type="submit" name="aksi" value="setujui" class="flex-1 py-2 rounded-xl bg-emerald-600 font-bold text-xs sm:text-sm text-white hover:bg-emerald-700 shadow-sm transition cursor-pointer">Setujui DP</button>
                                            <button type="submit" name="aksi" value="tolak" class="flex-1 py-2 rounded-xl bg-rose-600 font-bold text-xs sm:text-sm text-white hover:bg-rose-700 shadow-sm transition cursor-pointer">Tolak DP</button>
                                        </div>
                                    </form>
                                @endif
                            @else
                                <div class="flex flex-col items-center justify-center py-8 text-gray-400 text-center space-y-2">
                                    <svg class="h-10 w-10 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                    </svg>
                                    <p class="text-xs text-gray-500">Belum ada bukti transfer yang diunggah oleh pelanggan.</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endif

            {{-- ======================================================== --}}
            {{-- ALUR 2: VERIFIKASI AWAL & PENUGASAN TEKNISI             --}}
            {{-- ======================================================== --}}
            @if (!$pemesanan->tgl_verifikasi && $pemesanan->status_pengerjaan === 'Pending')
                <div class="rounded-2xl border-2 border-primary-200 bg-white p-6 shadow-sm space-y-4">
                    <div class="flex items-center gap-2">
                        <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-primary-600 text-white font-extrabold text-xs">2</span>
                        <h4 class="text-base font-bold text-gray-900">Verifikasi Pemesanan & Tugaskan Teknisi</h4>
                    </div>
                    <p class="text-xs text-gray-500">
                        Admin dapat menyetujui pemesanan dengan menentukan tanggal/jam survei dan teknisi yang ditugaskan, atau menolak pemesanan.
                    </p>

                    @php
                        $rawAdminJam = $pemesanan->jam_survei_diminta ? \Carbon\Carbon::parse($pemesanan->jam_survei_diminta)->format('H:i') : '09:00';
                        $adminJamParts = explode(':', $rawAdminJam);
                        $defaultAdminHour = str_pad($adminJamParts[0] ?? '09', 2, '0', STR_PAD_LEFT);
                        $defaultAdminMinute = str_pad($adminJamParts[1] ?? '00', 2, '0', STR_PAD_LEFT);
                    @endphp

                    @if ($pemesanan->wajib_dp && $pemesanan->status_dp !== 'Lunas')
                        <div class="rounded-xl border border-amber-300 bg-amber-50 p-4 text-xs text-amber-900 space-y-2">
                            <div class="font-bold text-amber-950">
                                Penugasan Survei Terkunci (Wajib Verifikasi DP)
                            </div>
                            <p class="leading-relaxed">
                                Pesanan ini berskala besar (nominal ≥ Rp 10.000.000) dan mewajibkan Down Payment (DP 10%). Harap verifikasi mutasi bank dan unggah bukti transfer DP pada panel di atas terlebih dahulu untuk membuka formulir penugasan teknisi dan survei lapangan.
                            </p>
                            <div class="pt-2 border-t border-amber-200">
                                <button type="button" onclick="document.getElementById('modal-tolak-awal').classList.remove('hidden')"
                                        class="rounded-xl border border-rose-300 bg-white px-4 py-2 font-bold text-rose-700 hover:bg-rose-50 transition text-xs">
                                    Tolak Pemesanan Ini
                                </button>
                            </div>
                        </div>
                    @else
                        <form action="{{ route('admin.pemesanan.verifikasi-awal', $pemesanan->id_pemesanan) }}" method="POST" class="space-y-4 text-xs">
                            @csrf
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                @php
                                    $selectedTeknisiId = old('id_teknisi', $pemesanan->jadwalPenugasan?->id_teknisi);
                                    $selectedTeknisi = $teknisiList->firstWhere('id_pengguna', $selectedTeknisiId);
                                @endphp
                                <div class="relative picker-container" id="admin_teknisi_picker_container">
                                    <label class="block font-semibold text-gray-700 mb-1">
                                        Pilih Teknisi Lapangan <span class="text-rose-500">*</span>
                                    </label>

                                    {{-- Trigger Button Teknisi (Desain identik dengan Pemilihan Jasa Hama) --}}
                                    <button type="button" id="admin_teknisi_picker_trigger"
                                            class="w-full flex items-center justify-between rounded-xl border border-gray-300 bg-white px-3.5 py-2 text-xs sm:text-sm text-gray-800 shadow-sm hover:border-emerald-500 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 focus:outline-none transition text-left">
                                        <div class="flex flex-col pr-1 min-w-0">
                                            <span id="display_admin_teknisi_name" class="font-bold text-gray-900 truncate text-xs sm:text-sm">
                                                {{ $selectedTeknisi ? $selectedTeknisi->nama : '-- Pilih Teknisi Lapangan --' }}
                                            </span>
                                            <span id="display_admin_teknisi_sub" class="text-[11px] text-gray-500 truncate mt-0.5">
                                                {{ $selectedTeknisi ? ($selectedTeknisi->no_hp ? 'No. HP: ' . $selectedTeknisi->no_hp : 'Teknisi Lapangan') : 'Klik untuk memilih teknisi' }}
                                            </span>
                                        </div>
                                        <span class="shrink-0 flex items-center gap-1 text-[11px] font-bold text-emerald-700 bg-emerald-50 hover:bg-emerald-100 px-2 py-0.5 rounded-lg transition ml-2">
                                            <span>Pilih</span>
                                            <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/>
                                            </svg>
                                        </span>
                                    </button>

                                    {{-- Input hidden asli untuk submit form & validasi --}}
                                    <input type="hidden" name="id_teknisi" id="input_admin_id_teknisi" value="{{ $selectedTeknisiId ?? '' }}" required>

                                    {{-- Popover Dropdown Kustom (Desain kartu rapi serasi dengan pilihan jasa hama) --}}
                                    <div id="admin_teknisi_picker_popover" class="picker-popover hidden absolute left-0 right-0 sm:min-w-[320px] z-50 mt-1.5 rounded-2xl border border-gray-200 bg-white p-2.5 shadow-2xl max-h-80 overflow-y-auto space-y-1.5 scroll-smooth">
                                        <div class="px-2.5 py-1.5 border-b border-gray-100 flex items-center justify-between mb-1">
                                            <span class="text-[11px] font-bold text-gray-800">Daftar Teknisi Lapangan</span>
                                            <span class="text-[10px] text-gray-400">Pilih salah satu</span>
                                        </div>
                                        @foreach ($teknisiList as $t)
                                            @php $isOptSelected = ($selectedTeknisiId == $t->id_pengguna); @endphp
                                            <div data-teknisi-id="{{ $t->id_pengguna }}"
                                                 data-nama="{{ $t->nama }}"
                                                 data-sub="{{ $t->no_hp ? 'No. HP: ' . $t->no_hp : 'Teknisi Lapangan' }}"
                                                 class="teknisi-option-item cursor-pointer p-2.5 rounded-xl border transition flex items-center justify-between gap-3 {{ $isOptSelected ? 'border-emerald-500 bg-emerald-50/70 ring-1 ring-emerald-500' : 'border-gray-100 hover:border-emerald-300 hover:bg-emerald-50/40 bg-white' }}">
                                                <div class="min-w-0 flex-1">
                                                    <div class="flex items-center gap-1.5 flex-wrap">
                                                        <span class="text-xs font-bold text-gray-900 leading-snug">{{ $t->nama }}</span>
                                                        <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full bg-slate-100 text-slate-600">
                                                            Teknisi
                                                        </span>
                                                    </div>
                                                    <p class="text-[11px] text-gray-500 mt-1 flex items-center gap-1.5">
                                                        <svg class="w-3.5 h-3.5 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                                        </svg>
                                                        <span>{{ $t->no_hp ?: 'Tidak ada nomor HP' }}</span>
                                                    </p>
                                                </div>
                                                <div class="shrink-0 flex items-center">
                                                    <span class="check-icon {{ $isOptSelected ? 'text-emerald-600' : 'text-transparent' }}">
                                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                                    </span>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                    @error('id_teknisi')
                                        <p class="mt-1 text-[11px] text-rose-600">{{ $message }}</p>
                                    @enderror
                                </div>
                                @php
                                    $adminTglVal = $pemesanan->tgl_survei_diminta ? \Carbon\Carbon::parse($pemesanan->tgl_survei_diminta)->format('Y-m-d') : date('Y-m-d');
                                    $adminTglText = \Carbon\Carbon::parse($adminTglVal)->locale('id')->translatedFormat('l, d F Y');
                                @endphp
                                <div class="relative picker-container" id="container_admin_tgl_survei">
                                    <label class="block font-semibold text-gray-700 mb-1">Tanggal Survei <span class="text-rose-500">*</span></label>
                                    <button type="button" id="trigger_admin_tgl_survei"
                                            class="w-full flex items-center justify-between rounded-xl border border-gray-300 bg-white px-3.5 py-2.5 text-xs sm:text-sm text-gray-800 shadow-sm hover:border-emerald-500 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition text-left">
                                        <span id="display_admin_tgl_survei" class="font-bold text-gray-900 truncate pr-1">
                                            {{ $adminTglText }}
                                        </span>
                                        <span class="shrink-0 flex items-center gap-1 text-[11px] font-bold text-emerald-700 bg-emerald-50 hover:bg-emerald-100 px-2 py-0.5 rounded-lg transition">
                                            <span>Pilih</span>
                                            <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/>
                                            </svg>
                                        </span>
                                    </button>
                                    <input type="hidden" name="tgl_survei" id="input_admin_tgl_survei" value="{{ $adminTglVal }}" required>

                                    {{-- Popover Kalender --}}
                                    <div id="popover_admin_tgl_survei" class="picker-popover hidden absolute left-0 z-50 mt-1.5 w-72 rounded-2xl border border-gray-200 bg-white p-3 shadow-2xl text-xs">
                                        <div class="flex items-center justify-between pb-2 border-b border-gray-100 mb-2">
                                            <button type="button" class="btn-prev-month p-1 rounded-lg hover:bg-gray-100 text-gray-600 transition">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/></svg>
                                            </button>
                                            <span class="calendar-month-year text-xs font-bold text-gray-800"></span>
                                            <button type="button" class="btn-next-month p-1 rounded-lg hover:bg-gray-100 text-gray-600 transition">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
                                            </button>
                                        </div>
                                        <div class="grid grid-cols-7 gap-1 text-center text-[10px] font-bold text-gray-400 mb-1">
                                            <span>Min</span><span>Sen</span><span>Sel</span><span>Rab</span><span>Kam</span><span>Jum</span><span>Sab</span>
                                        </div>
                                        <div class="calendar-days-grid grid grid-cols-7 gap-1 text-center"></div>
                                        <div class="pt-2 mt-2 border-t border-gray-100 flex items-center justify-between">
                                            <span class="calendar-selected-preview text-[11px] font-bold text-emerald-700 truncate pr-1"></span>
                                            <button type="button" class="btn-confirm-date px-2.5 py-1 bg-emerald-600 hover:bg-emerald-700 text-white text-[10px] font-bold rounded-lg shadow-sm transition">Selesai</button>
                                        </div>
                                    </div>
                                </div>
                                <div class="relative" id="admin_time_picker_container">
                                    <label class="block font-semibold text-gray-700 mb-1">
                                        Jam Survei (24 Jam - WITA) <span class="text-rose-500">*</span>
                                    </label>

                                    {{-- Trigger Button serasi dengan form pelanggan --}}
                                    <button type="button" id="admin_time_picker_trigger"
                                            class="w-full flex items-center justify-between rounded-xl border border-gray-300 bg-white px-3.5 py-2.5 text-xs sm:text-sm text-gray-900 shadow-sm hover:border-emerald-500 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 focus:outline-none transition text-left">
                                        <span id="admin_display_time_btn" class="font-extrabold text-gray-900 text-xs sm:text-sm">
                                            {{ $defaultAdminHour }}:{{ $defaultAdminMinute }} WITA
                                        </span>
                                        <span class="flex items-center gap-1 text-[11px] font-bold text-emerald-700 bg-emerald-50 hover:bg-emerald-100 px-2 py-0.5 rounded-lg transition">
                                            <span>Pilih Jam</span>
                                            <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/>
                                            </svg>
                                        </span>
                                    </button>

                                    {{-- Hidden input yang dikirim ke backend --}}
                                    <input type="hidden" name="jam_survei" id="admin_jam_survei" value="{{ $defaultAdminHour }}:{{ $defaultAdminMinute }}">

                                    {{-- Popover Dropdown Scrollable Time Picker (Tanpa AM/PM, Full 24 Jam) --}}
                                    <div id="admin_time_picker_popover" class="hidden absolute right-0 z-40 mt-1.5 w-72 sm:w-80 rounded-2xl border border-gray-200 bg-white p-3 shadow-2xl">
                                        <div class="flex items-center justify-between pb-2 border-b border-gray-100 mb-2">
                                            <div>
                                                <span class="text-[11px] font-bold text-gray-800 block">
                                                    Gulir / Pilih Waktu (24 Jam)
                                                </span>
                                                <span id="admin_slot_status_badge" class="inline-block mt-0.5 text-[9px] font-extrabold text-emerald-700 bg-emerald-100/80 px-1.5 py-0.5 rounded">
                                                    Memeriksa Slot...
                                                </span>
                                            </div>
                                            <span class="text-[10px] font-black text-emerald-700 bg-emerald-100/80 px-2 py-0.5 rounded-md self-start">WITA</span>
                                        </div>

                                        <div class="grid grid-cols-2 gap-2 text-center">
                                            {{-- Kolom Scroll Jam (06:00 sampai 22:00) --}}
                                            <div>
                                                <span class="text-[10px] font-bold text-gray-500 uppercase tracking-wider block mb-1">Jam</span>
                                                <div id="admin_scroll_col_jam" class="h-44 overflow-y-auto space-y-1 p-1 bg-gray-50/70 rounded-xl border border-gray-100 scroll-smooth">
                                                    @for ($h = 6; $h <= 22; $h++)
                                                        @php $hVal = sprintf('%02d', $h); @endphp
                                                        <button type="button" data-hour="{{ $hVal }}"
                                                                class="admin-hour-opt w-full py-1.5 px-2 text-xs font-bold rounded-lg transition flex items-center justify-between {{ $defaultAdminHour === $hVal ? 'bg-emerald-600 text-white shadow-sm' : 'text-gray-700 hover:bg-gray-200/80' }}">
                                                            <span>{{ $hVal }}:00</span>
                                                            <span class="admin-hour-slot-badge hidden text-[9px] font-black px-1.5 py-0.5 rounded"></span>
                                                        </button>
                                                    @endfor
                                                </div>
                                            </div>

                                            {{-- Kolom Scroll Menit (:00 sampai :55) --}}
                                            <div>
                                                <span class="text-[10px] font-bold text-gray-500 uppercase tracking-wider block mb-1">Menit</span>
                                                <div id="admin_scroll_col_menit" class="h-44 overflow-y-auto space-y-1 p-1 bg-gray-50/70 rounded-xl border border-gray-100 scroll-smooth">
                                                    @foreach (['00', '05', '10', '15', '20', '25', '30', '35', '40', '45', '50', '55'] as $mVal)
                                                        <button type="button" data-minute="{{ $mVal }}"
                                                                class="admin-minute-opt w-full py-1.5 text-xs font-bold rounded-lg transition {{ $defaultAdminMinute === $mVal ? 'bg-emerald-600 text-white shadow-sm' : 'text-gray-700 hover:bg-gray-200/80' }}">
                                                            :{{ $mVal }}
                                                        </button>
                                                    @endforeach
                                                </div>
                                            </div>
                                        </div>

                                        <div class="pt-2.5 mt-2.5 border-t border-gray-100 flex items-center justify-between">
                                            <div class="text-[11px] text-gray-500">
                                                Terpilih: <strong id="admin_popover_preview_text" class="text-emerald-700 font-extrabold">{{ $defaultAdminHour }}:{{ $defaultAdminMinute }} WITA</strong>
                                            </div>
                                            <button type="button" id="admin_btn_confirm_time"
                                                    class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-extrabold rounded-xl shadow-sm transition">
                                                Selesai
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Kotak Rincian Jadwal Teknisi Lapangan Terpilih --}}
                            <div id="admin_teknisi_jadwal_info_box" class="rounded-xl border border-slate-200 bg-slate-50/80 p-3 text-xs transition">
                                <div class="flex items-center justify-between mb-1">
                                    <span class="font-bold text-slate-800 flex items-center gap-1.5">
                                        <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                        </svg>
                                        <span id="admin_info_box_title">Jadwal Teknisi Lapangan</span>
                                    </span>
                                    <span id="admin_info_box_status" class="text-[10px] font-bold text-slate-600 bg-white border border-slate-200 px-2 py-0.5 rounded-md">
                                        Memeriksa...
                                    </span>
                                </div>
                                <div id="admin_info_box_content" class="text-[11px] text-slate-600 mt-1">
                                    Pilih teknisi dan tanggal untuk memeriksa ketersediaan jam penugasan lapangan.
                                </div>
                            </div>

                            <div class="pt-3 border-t border-gray-100 flex items-center justify-between">
                                <button type="button" onclick="document.getElementById('modal-tolak-awal').classList.remove('hidden')"
                                        class="rounded-xl border border-rose-300 px-4 py-2 font-bold text-rose-700 hover:bg-rose-50 transition">
                                    Tolak Pemesanan
                                </button>
                                <button type="submit" class="rounded-xl bg-primary-600 px-5 py-2 font-bold text-white shadow-sm hover:bg-primary-700 transition">
                                    Verifikasi & Tugaskan Teknisi
                                </button>
                            </div>
                        </form>
                    @endif
                </div>
            @elseif ($pemesanan->tgl_verifikasi)
                <div class="rounded-2xl border-2 border-emerald-300 bg-emerald-50/60 p-6 shadow-sm space-y-4">
                    <div class="flex items-center justify-between pb-3 border-b border-emerald-200/80 flex-wrap gap-2">
                        <div class="flex items-center gap-2">
                            <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-emerald-600 text-white font-extrabold text-xs">✓</span>
                            <div>
                                <h4 class="text-base font-extrabold text-gray-900">Pemesanan Telah Di-ACC / Diverifikasi</h4>
                                <p class="text-xs text-emerald-800">Pesanan telah disetujui admin dan penugasan teknisi telah dikonfirmasi.</p>
                            </div>
                        </div>
                        <span class="inline-flex items-center gap-1 rounded-full px-3 py-1 text-xs font-black bg-emerald-100 text-emerald-800 border border-emerald-300 shadow-2xs">
                            <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                            <span>Terverifikasi</span>
                        </span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                        <div class="rounded-xl bg-white p-4 border border-emerald-200 shadow-2xs space-y-2">
                            <span class="text-[11px] font-bold text-gray-400 uppercase tracking-wider block">Waktu ACC & Petugas</span>
                            <div class="space-y-1">
                                <div>
                                    <span class="text-gray-500 block text-[11px]">Tanggal & Jam di-ACC:</span>
                                    <span class="text-sm font-black text-emerald-950 block">
                                        {{ $pemesanan->tgl_verifikasi->translatedFormat('d F Y') }} pukul {{ $pemesanan->tgl_verifikasi->format('H:i') }} WITA
                                    </span>
                                </div>
                                <div class="pt-1 border-t border-gray-100 flex items-center justify-between">
                                    <span class="text-gray-500 text-[11px]">Di-ACC oleh:</span>
                                    <span class="font-bold text-gray-900">{{ $pemesanan->admin?->nama ?? 'Admin' }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="rounded-xl bg-white p-4 border border-emerald-200 shadow-2xs space-y-2">
                            <span class="text-[11px] font-bold text-gray-400 uppercase tracking-wider block">Teknisi & Jadwal Survei</span>
                            <div class="space-y-1">
                                <div>
                                    <span class="text-gray-500 block text-[11px]">Teknisi Lapangan:</span>
                                    <span class="text-sm font-bold text-gray-900 block">
                                        {{ $pemesanan->jadwalPenugasan?->teknisi?->nama ?? 'Belum Ditugaskan' }}
                                    </span>
                                </div>
                                <div class="pt-1 border-t border-gray-100 flex items-center justify-between">
                                    <span class="text-gray-500 text-[11px]">Jadwal Survei:</span>
                                    <span class="font-bold text-emerald-800">
                                        {{ $pemesanan->jadwalPenugasan?->tgl_survei ? \Carbon\Carbon::parse($pemesanan->jadwalPenugasan->tgl_survei)->translatedFormat('d F Y') : '-' }} ({{ $pemesanan->jadwalPenugasan?->jam_survei ?? '-' }} WITA)
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            {{-- ======================================================== --}}
            {{-- ALUR 4 & 7: VERIFIKASI BUKTI KEHADIRAN (SURVEI/PENGERJAAN) --}}
            {{-- ======================================================== --}}
            <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm space-y-4">
                <h4 class="text-base font-bold text-gray-900">
                    Verifikasi Foto Kehadiran Teknisi
                </h4>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
                    {{-- Kehadiran Survei --}}
                    <div class="rounded-xl border border-gray-200 bg-gray-50/70 p-4 space-y-3">
                        <div class="flex items-center justify-between gap-2">
                            <span class="font-bold text-gray-800">1. Kehadiran Survei</span>
                            @if ($latestBuktiSurvei)
                                <span class="rounded-full px-2.5 py-0.5 text-[11px] font-bold {{ $latestBuktiSurvei->status_verifikasi === 'Disetujui' ? 'bg-emerald-100 text-emerald-800' : ($latestBuktiSurvei->status_verifikasi === 'Ditolak' ? 'bg-rose-100 text-rose-800' : 'bg-amber-100 text-amber-800') }}">
                                    {{ $latestBuktiSurvei->status_verifikasi }}
                                </span>
                            @else
                                <span class="text-gray-400">Belum diunggah</span>
                            @endif
                        </div>

                        @if ($latestBuktiSurvei)
                            <div class="space-y-2">
                                <div class="relative rounded-xl overflow-hidden border border-gray-200 bg-gray-900 group">
                                    <img src="{{ route('dokumen.kehadiran', $latestBuktiSurvei->id_bukti_kehadiran) }}" alt="Foto Kehadiran Survei" class="h-32 w-full object-cover">
                                    <a href="{{ route('dokumen.kehadiran', $latestBuktiSurvei->id_bukti_kehadiran) }}" target="_blank" class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 flex items-center justify-center text-white font-bold text-xs transition">
                                        Buka Gambar Penuh
                                    </a>
                                </div>
                                <div class="flex items-center justify-between text-[11px]">
                                    <span class="text-gray-500">Preview Kehadiran Survei</span>
                                    <a href="{{ route('dokumen.kehadiran', $latestBuktiSurvei->id_bukti_kehadiran) }}" target="_blank" class="font-bold text-primary-700 underline">Unduh / Tab Baru</a>
                                </div>
                            </div>

                            @if ($latestBuktiSurvei->catatan)
                                <p class="text-gray-600 bg-white p-2 rounded-lg border border-gray-100">Catatan: {{ $latestBuktiSurvei->catatan }}</p>
                            @endif

                            @if ($latestBuktiSurvei->status_verifikasi === 'Menunggu')
                                <form action="{{ route('admin.verifikasi.kehadiran', $latestBuktiSurvei->id_bukti_kehadiran) }}" method="POST" class="pt-2 border-t border-gray-200 space-y-2">
                                    @csrf
                                    <input type="text" name="alasan_penolakan" placeholder="Alasan jika menolak..." class="w-full rounded-xl border-gray-300 px-4 py-2.5 text-xs sm:text-sm text-gray-800 placeholder-gray-400 shadow-sm focus:border-primary-500 focus:ring-primary-500 transition">
                                    <div class="flex gap-2">
                                        <button type="submit" name="aksi" value="setujui" class="flex-1 py-2 rounded-xl bg-emerald-600 font-bold text-xs sm:text-sm text-white hover:bg-emerald-700 shadow-sm transition">Setujui</button>
                                        <button type="submit" name="aksi" value="tolak" class="flex-1 py-2 rounded-xl bg-rose-600 font-bold text-xs sm:text-sm text-white hover:bg-rose-700 shadow-sm transition">Tolak</button>
                                    </div>
                                </form>
                            @endif
                        @endif
                    </div>

                    {{-- Kehadiran Pengerjaan --}}
                    <div class="rounded-xl border border-gray-200 bg-gray-50/70 p-4 space-y-3">
                        <div class="flex items-center justify-between gap-2">
                            <span class="font-bold text-gray-800">2. Kehadiran Pengerjaan</span>
                            @if ($latestBuktiPengerjaan)
                                <span class="rounded-full px-2.5 py-0.5 text-[11px] font-bold {{ $latestBuktiPengerjaan->status_verifikasi === 'Disetujui' ? 'bg-emerald-100 text-emerald-800' : ($latestBuktiPengerjaan->status_verifikasi === 'Ditolak' ? 'bg-rose-100 text-rose-800' : 'bg-amber-100 text-amber-800') }}">
                                    {{ $latestBuktiPengerjaan->status_verifikasi }}
                                </span>
                            @else
                                <span class="text-gray-400">Belum diunggah</span>
                            @endif
                        </div>

                        @if ($latestBuktiPengerjaan)
                            <div class="space-y-2">
                                <div class="relative rounded-xl overflow-hidden border border-gray-200 bg-gray-900 group">
                                    <img src="{{ route('dokumen.kehadiran', $latestBuktiPengerjaan->id_bukti_kehadiran) }}" alt="Foto Kehadiran Pengerjaan" class="h-32 w-full object-cover">
                                    <a href="{{ route('dokumen.kehadiran', $latestBuktiPengerjaan->id_bukti_kehadiran) }}" target="_blank" class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 flex items-center justify-center text-white font-bold text-xs transition">
                                        Buka Gambar Penuh
                                    </a>
                                </div>
                                <div class="flex items-center justify-between text-[11px]">
                                    <span class="text-gray-500">Preview Kehadiran Treatment</span>
                                    <a href="{{ route('dokumen.kehadiran', $latestBuktiPengerjaan->id_bukti_kehadiran) }}" target="_blank" class="font-bold text-primary-700 underline">Unduh / Tab Baru</a>
                                </div>
                            </div>

                            @if ($latestBuktiPengerjaan->status_verifikasi === 'Menunggu')
                                <div class="rounded-lg bg-blue-50 border border-blue-200 p-2 text-[11px] text-blue-800">
                                    <b>Aturan Otomatis:</b> Menyetujui foto kehadiran pengerjaan akan mengubah status pengerjaan pesanan ini menjadi <b>On-Going</b>!
                                </div>
                                <form action="{{ route('admin.verifikasi.kehadiran', $latestBuktiPengerjaan->id_bukti_kehadiran) }}" method="POST" class="pt-2 border-t border-gray-200 space-y-2">
                                    @csrf
                                    <input type="text" name="alasan_penolakan" placeholder="Alasan jika menolak..." class="w-full rounded-xl border-gray-300 px-4 py-2.5 text-xs sm:text-sm text-gray-800 placeholder-gray-400 shadow-sm focus:border-primary-500 focus:ring-primary-500 transition">
                                    <div class="flex gap-2">
                                        <button type="submit" name="aksi" value="setujui" class="flex-1 py-2 rounded-xl bg-emerald-600 font-bold text-xs sm:text-sm text-white hover:bg-emerald-700 shadow-sm transition">Setujui (On-Going)</button>
                                        <button type="submit" name="aksi" value="tolak" class="flex-1 py-2 rounded-xl bg-rose-600 font-bold text-xs sm:text-sm text-white hover:bg-rose-700 shadow-sm transition">Tolak</button>
                                    </div>
                                </form>
                            @endif
                        @endif
                    </div>
                </div>
            </div>

            {{-- ======================================================== --}}
            {{-- ALUR 5: VERIFIKASI HASIL SURVEI & KESEPAKATAN HARGA       --}}
            {{-- ======================================================== --}}
            <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm space-y-4">
                <div class="flex items-center justify-between">
                    <h4 class="text-base font-bold text-gray-900">
                        Hasil Survei & Kesepakatan Pengerjaan
                    </h4>
                    @if ($latestSurvei)
                        <span class="rounded-full px-3 py-0.5 text-xs font-bold {{ $latestSurvei->status_verifikasi === 'Disetujui' ? 'bg-emerald-100 text-emerald-800' : ($latestSurvei->status_verifikasi === 'Ditolak' ? 'bg-rose-100 text-rose-800' : 'bg-amber-100 text-amber-800') }}">
                            {{ $latestSurvei->status_verifikasi }}
                        </span>
                    @else
                        <span class="text-xs text-gray-400">Belum diajukan teknisi</span>
                    @endif
                </div>

                @if ($latestSurvei)
                    <div class="rounded-xl bg-gray-50 p-4 text-xs space-y-2">
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <div>
                                <span class="text-gray-400 font-semibold block">Tanggal Treatment Diajukan</span>
                                <span class="font-bold text-gray-800">{{ \Carbon\Carbon::parse($latestSurvei->tgl_pengerjaan_diajukan)->format('d M Y') }}</span>
                            </div>
                            <div>
                                <span class="text-gray-400 font-semibold block">Jam Pengerjaan</span>
                                <span class="font-bold text-gray-800">{{ $latestSurvei->jam_pengerjaan_diajukan }}</span>
                            </div>
                            <div>
                                <span class="text-gray-400 font-semibold block">Harga Deal Diajukan (Total Keseluruhan)</span>
                                <span class="font-extrabold text-emerald-700 text-sm">Rp {{ number_format($latestSurvei->harga_diajukan, 0, ',', '.') }}</span>
                                @if ($pemesanan->wajib_dp && $pemesanan->status_dp === 'Lunas')
                                    <div class="text-[11px] text-emerald-800 font-semibold mt-1 space-y-0.5">
                                        <div>DP Terbayar: <span class="font-bold text-emerald-700">-Rp {{ number_format($pemesanan->nominal_dp, 0, ',', '.') }}</span></div>
                                        <div>Sisa Ditagih: <b class="font-black text-emerald-900">Rp {{ number_format($pemesanan->hitungSisaDariPengajuan($latestSurvei->harga_diajukan), 0, ',', '.') }}</b></div>
                                    </div>
                                @endif
                            </div>
                        </div>

                        @if ($pemesanan->catatan_survei)
                            <div class="pt-2 border-t border-gray-200">
                                <span class="text-gray-500 font-semibold">Catatan Lapangan:</span>
                                <p class="text-gray-700 mt-0.5">{{ $latestSurvei->catatan_survei }}</p>
                            </div>
                        @endif

                        @if ($pemesanan->wajib_dp && $pemesanan->status_dp === 'Lunas')
                            <div class="rounded-xl bg-emerald-50 border border-emerald-200 p-3 text-xs text-emerald-950 space-y-1">
                                <div class="flex items-center justify-between font-bold">
                                    <span>Penjelasan Kalkulasi Pelunasan:</span>
                                    <span class="px-2 py-0.5 rounded-full bg-white text-emerald-800 text-[10px] font-extrabold border border-emerald-200 shadow-2xs">DP 10% Terverifikasi</span>
                                </div>
                                <p class="text-[11px] text-emerald-800 leading-relaxed">
                                    Total harga kesepakatan survei yang diajukan adalah <b>Rp {{ number_format($latestSurvei->harga_diajukan, 0, ',', '.') }}</b>. Karena pelanggan sudah melunasi DP 10% sebesar <b>Rp {{ number_format($pemesanan->nominal_dp, 0, ',', '.') }}</b> via transfer bank kantor, maka <b>sisa pelunasan yang akan ditagihkan teknisi di lapangan adalah Rp {{ number_format($pemesanan->hitungSisaDariPengajuan($latestSurvei->harga_diajukan), 0, ',', '.') }}</b>.
                                </p>
                            </div>
                        @endif

                        @if ($latestSurvei->status_verifikasi === 'Menunggu')
                            @php
                                $statusService = app(\App\Services\PemesananStatusService::class);
                                $bentrokPengerjaan = $statusService->cekBentrokJadwalTeknisi(
                                    $latestSurvei->id_teknisi,
                                    (string) $latestSurvei->tgl_pengerjaan_diajukan,
                                    (string) $latestSurvei->jam_pengerjaan_diajukan,
                                    $pemesanan->id_pemesanan
                                );
                            @endphp

                            @if ($bentrokPengerjaan)
                                <div class="rounded-xl border-2 border-rose-300 bg-rose-50 p-3.5 space-y-1.5 text-xs text-rose-950">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <span class="rounded-full bg-rose-200 text-rose-900 border border-rose-300 px-2.5 py-0.5 text-[10px] font-black">
                                            Jadwal Bertabrakan / Penuh
                                        </span>
                                        <span class="font-extrabold text-xs sm:text-sm">Perhatian: Teknisi Sudah Memiliki Pekerjaan di Waktu Ini!</span>
                                    </div>
                                    <p class="leading-relaxed text-[11px] text-rose-900">
                                        Teknisi <b>{{ $latestSurvei->teknisi?->nama ?? 'terpilih' }}</b> sudah memiliki jadwal penugasan pada <b>{{ \Carbon\Carbon::parse($latestSurvei->tgl_pengerjaan_diajukan)->locale('id')->translatedFormat('l, d F Y') }}</b> pukul <b>{{ substr((string)$latestSurvei->jam_pengerjaan_diajukan, 0, 5) }} WITA</b> (Pesanan <b>{{ $bentrokPengerjaan->pemesanan?->kode_pemesanan ?? '#'.$bentrokPengerjaan->id_pemesanan }}</b>).
                                    </p>
                                    <p class="text-[11px] text-rose-800 italic bg-white/80 p-2 rounded-lg border border-rose-200 leading-relaxed">
                                        Sistem mencegah 1 teknisi bertugas pada waktu yang sama. Silakan tolak pengajuan ini dengan memberikan catatan alasan agar teknisi bernegosiasi memilih jadwal lain bersama pelanggan.
                                    </p>
                                </div>
                            @endif

                            <form action="{{ route('admin.verifikasi.hasil-survei', $latestSurvei->id_hasil_survei) }}" method="POST" class="pt-3 border-t border-gray-200 space-y-2.5">
                                @csrf
                                <input type="text" name="alasan_penolakan" placeholder="{{ $bentrokPengerjaan ? 'Wajib diisi (contoh: Jadwal bentrok dengan pesanan lain, harap ajukan jam/hari lain)...' : 'Wajib diisi jika menolak hasil survei ini...' }}" class="w-full rounded-xl border-gray-300 px-4 py-2.5 text-xs sm:text-sm text-gray-800 placeholder-gray-400 shadow-sm focus:border-primary-500 focus:ring-primary-500 transition">
                                <div class="flex flex-wrap justify-end gap-2 pt-1">
                                    <button type="submit" name="aksi" value="tolak" class="px-4 py-2 rounded-xl bg-rose-600 font-bold text-xs sm:text-sm text-white hover:bg-rose-700 shadow-sm transition">Tolak Kesepakatan</button>
                                    @if ($bentrokPengerjaan)
                                        <button type="button" class="px-5 py-2 rounded-xl bg-gray-400 font-bold text-xs sm:text-sm text-white cursor-not-allowed opacity-60 shadow-sm" disabled title="Jadwal teknisi bertabrakan dengan pesanan {{ $bentrokPengerjaan->pemesanan?->kode_pemesanan ?? '#'.$bentrokPengerjaan->id_pemesanan }}">
                                            Setujui (Jadwal Bentrok)
                                        </button>
                                    @else
                                        <button type="submit" name="aksi" value="setujui" class="px-5 py-2 rounded-xl bg-emerald-600 font-bold text-xs sm:text-sm text-white hover:bg-emerald-700 shadow-sm transition">Setujui & Perbarui Jadwal Treatment</button>
                                    @endif
                                </div>
                            </form>
                        @endif
                    </div>
                @endif
            </div>

            {{-- ======================================================== --}}
            {{-- ALUR 8: VERIFIKASI WORK ORDER & BUKTI PEMBAYARAN          --}}
            {{-- ======================================================== --}}
            <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm space-y-4">
                <div>
                    <h4 class="text-base font-bold text-gray-900">
                        Verifikasi Work Order (WO) & Bukti Pembayaran
                    </h4>
                    <p class="text-xs text-gray-500 mt-1">
                        <b>Aturan Sistem:</b> Status pengerjaan menjadi <b>Done</b> dan status pembayaran menjadi <b>Lunas</b> secara OTOMATIS jika dan hanya jika dokumen WO dan Bukti Pembayaran KEDUANYA telah disetujui!
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
                    {{-- Verifikasi WO --}}
                    <div class="rounded-xl border border-gray-200 bg-gray-50/70 p-4 space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="font-bold text-gray-800">Dokumen Work Order</span>
                            @if ($latestWo)
                                <span class="rounded-full px-2.5 py-0.5 text-[11px] font-bold {{ $latestWo->status_verifikasi === 'Disetujui' ? 'bg-emerald-100 text-emerald-800' : ($latestWo->status_verifikasi === 'Ditolak' ? 'bg-rose-100 text-rose-800' : 'bg-amber-100 text-amber-800') }}">
                                    {{ $latestWo->status_verifikasi }}
                                </span>
                            @else
                                <span class="text-gray-400">Belum diunggah</span>
                            @endif
                        </div>

                        @if ($latestWo)
                            @php
                                $extWo = strtolower(pathinfo($latestWo->file_wo, PATHINFO_EXTENSION));
                                $isImageWo = in_array($extWo, ['jpg', 'jpeg', 'png', 'webp', 'gif']);
                            @endphp

                            <div class="space-y-2">
                                @if ($isImageWo)
                                    <div class="relative overflow-hidden rounded-lg border border-gray-200 bg-gray-100 group max-h-48 flex items-center justify-center">
                                        <img src="{{ route('dokumen.wo', $latestWo->id_wo) }}" alt="Dokumen Work Order" class="w-full h-40 object-cover object-center group-hover:scale-105 transition-transform" onerror="this.onerror=null; this.parentElement.innerHTML='<div class=\'p-3 text-center text-gray-500 text-xs\'>Berkas WO (klik untuk membuka): <a href=\'{{ route('dokumen.wo', $latestWo->id_wo) }}\' target=\'_blank\' class=\'font-bold text-primary-700 underline\'>Buka Dokumen</a></div>'">
                                        <a href="{{ route('dokumen.wo', $latestWo->id_wo) }}" target="_blank" class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center text-white font-bold text-xs gap-1">
                                            <span>🔍 Perbesar Dokumen WO</span>
                                        </a>
                                    </div>
                                @else
                                    <div class="rounded-lg border border-gray-200 bg-white p-3 flex items-center justify-between">
                                        <div class="flex items-center gap-2">
                                            <span class="text-xl">📄</span>
                                            <div>
                                                <span class="font-bold text-gray-800 block text-xs">Dokumen PDF (WO)</span>
                                                <span class="text-[10px] text-gray-400 uppercase">{{ $extWo ?: 'PDF' }} File</span>
                                            </div>
                                        </div>
                                        <a href="{{ route('dokumen.wo', $latestWo->id_wo) }}" target="_blank" class="px-2.5 py-1 rounded-lg bg-primary-50 text-primary-700 hover:bg-primary-100 font-bold text-[11px] transition">
                                            Buka Dokumen &rarr;
                                        </a>
                                    </div>
                                @endif

                                <div class="text-gray-600 space-y-1">
                                    <div class="flex items-center justify-between">
                                        <span>Berkas WO</span>
                                        <a href="{{ route('dokumen.wo', $latestWo->id_wo) }}" target="_blank" class="font-bold text-primary-700 underline">Lihat / Unduh Dokumen</a>
                                    </div>
                                    @if ($latestWo->catatan)
                                        <div class="text-gray-500 italic text-[11px]">Catatan: "{{ $latestWo->catatan }}"</div>
                                    @endif
                                </div>
                            </div>

                            @if ($latestWo->status_verifikasi === 'Menunggu')
                                <form action="{{ route('admin.verifikasi.wo', $latestWo->id_wo) }}" method="POST" class="pt-2 border-t border-gray-200 space-y-2">
                                    @csrf
                                    <input type="text" name="alasan_penolakan" placeholder="Alasan jika menolak WO..." class="w-full rounded-xl border-gray-300 px-4 py-2.5 text-xs sm:text-sm text-gray-800 placeholder-gray-400 shadow-sm focus:border-primary-500 focus:ring-primary-500 transition">
                                    <div class="flex gap-2">
                                        <button type="submit" name="aksi" value="setujui" class="flex-1 py-2 rounded-xl bg-emerald-600 font-bold text-xs sm:text-sm text-white hover:bg-emerald-700 shadow-sm transition">Setujui WO</button>
                                        <button type="submit" name="aksi" value="tolak" class="flex-1 py-2 rounded-xl bg-rose-600 font-bold text-xs sm:text-sm text-white hover:bg-rose-700 shadow-sm transition">Tolak WO</button>
                                    </div>
                                </form>
                            @endif
                        @endif
                    </div>

                    {{-- Verifikasi Bukti Pembayaran --}}
                    <div class="rounded-xl border border-gray-200 bg-gray-50/70 p-4 space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="font-bold text-gray-800">Bukti Pembayaran</span>
                            @if ($latestBuktiBayar)
                                <span class="rounded-full px-2.5 py-0.5 text-[11px] font-bold {{ $latestBuktiBayar->status_verifikasi === 'Disetujui' ? 'bg-emerald-100 text-emerald-800' : ($latestBuktiBayar->status_verifikasi === 'Ditolak' ? 'bg-rose-100 text-rose-800' : 'bg-amber-100 text-amber-800') }}">
                                    {{ $latestBuktiBayar->status_verifikasi }}
                                </span>
                            @else
                                <span class="text-gray-400">Belum diunggah</span>
                            @endif
                        </div>

                        @if ($latestBuktiBayar)
                            <div class="space-y-2">
                                <div class="relative overflow-hidden rounded-lg border border-gray-200 bg-gray-100 group max-h-48 flex items-center justify-center">
                                    <img src="{{ route('dokumen.pembayaran', $latestBuktiBayar->id_bukti_pembayaran) }}" alt="Bukti Pembayaran" class="w-full h-40 object-cover object-center group-hover:scale-105 transition-transform" onerror="this.onerror=null; this.parentElement.innerHTML='<div class=\'p-3 text-center text-gray-500 text-xs\'>Berkas bukti (klik untuk membuka): <a href=\'{{ route('dokumen.pembayaran', $latestBuktiBayar->id_bukti_pembayaran) }}\' target=\'_blank\' class=\'font-bold text-primary-700 underline\'>Buka Dokumen</a></div>'">
                                    <a href="{{ route('dokumen.pembayaran', $latestBuktiBayar->id_bukti_pembayaran) }}" target="_blank" class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center text-white font-bold text-xs gap-1">
                                        <span>🔍 Perbesar Bukti</span>
                                    </a>
                                </div>
                                <div class="text-gray-600 space-y-1">
                                    <div class="flex items-center justify-between">
                                        <span>Metode: <b>{{ $latestBuktiBayar->metode_pembayaran }}</b></span>
                                        <a href="{{ route('dokumen.pembayaran', $latestBuktiBayar->id_bukti_pembayaran) }}" target="_blank" class="font-bold text-primary-700 underline">Lihat Bukti</a>
                                    </div>
                                    <div>Nominal Tertera: <b>Rp {{ number_format($latestBuktiBayar->jumlah_bayar, 0, ',', '.') }}</b></div>
                                </div>

                                @if ($pemesanan->wajib_dp && $pemesanan->status_dp === 'Lunas')
                                    <div class="rounded-xl bg-emerald-50 border border-emerald-200 p-3 text-xs text-emerald-950 space-y-1.5">
                                        <div class="flex items-center justify-between font-bold text-[11px]">
                                            <span>Target Nominal Pelunasan Lapangan:</span>
                                            <span class="text-xs font-black text-emerald-800">Rp {{ number_format($pemesanan->sisa_pelunasan, 0, ',', '.') }}</span>
                                        </div>
                                        <div class="text-[10px] text-emerald-700 leading-relaxed border-t border-emerald-200/80 pt-1">
                                            <b>Keterangan:</b> Total final <b>Rp {{ number_format($pemesanan->harga_final ?? $pemesanan->harga_estimasi, 0, ',', '.') }}</b> dikurangi DP 10% (<b>Rp {{ number_format($pemesanan->nominal_dp, 0, ',', '.') }}</b>) yang sudah masuk rekening kantor. Bukti pembayaran dari teknisi ini sah jika senilai sisa pelunasan (<b>Rp {{ number_format($pemesanan->sisa_pelunasan, 0, ',', '.') }}</b>).
                                        </div>
                                    </div>
                                @endif
                            </div>

                            @if ($latestBuktiBayar->status_verifikasi === 'Menunggu')
                                <form action="{{ route('admin.verifikasi.pembayaran', $latestBuktiBayar->id_bukti_pembayaran) }}" method="POST" class="pt-2 border-t border-gray-200 space-y-2">
                                    @csrf
                                    <input type="text" name="alasan_penolakan" placeholder="Alasan jika menolak bukti pembayaran..." class="w-full rounded-xl border-gray-300 px-4 py-2.5 text-xs sm:text-sm text-gray-800 placeholder-gray-400 shadow-sm focus:border-primary-500 focus:ring-primary-500 transition">
                                    <div class="flex gap-2">
                                        <button type="submit" name="aksi" value="setujui" class="flex-1 py-2 rounded-xl bg-emerald-600 font-bold text-xs sm:text-sm text-white hover:bg-emerald-700 shadow-sm transition">Setujui Pembayaran</button>
                                        <button type="submit" name="aksi" value="tolak" class="flex-1 py-2 rounded-xl bg-rose-600 font-bold text-xs sm:text-sm text-white hover:bg-rose-700 shadow-sm transition">Tolak Pembayaran</button>
                                    </div>
                                </form>
                            @endif
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Kolom Kanan (1 Span): Ringkasan Jadwal, Harga, & Aksi Tambahan --}}
        <div class="space-y-6">
            {{-- Kartu Ringkasan Keuangan --}}
            <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm space-y-4">
                <h4 class="text-sm font-bold text-gray-900 border-b border-gray-100 pb-3">Ringkasan Biaya</h4>
                <div class="space-y-3 text-xs">
                    <div class="flex items-center justify-between">
                        <span class="text-gray-500">Estimasi Awal:</span>
                        <span class="font-bold text-gray-800">Rp {{ number_format($pemesanan->harga_estimasi, 0, ',', '.') }}</span>
                    </div>
                    <div class="flex items-center justify-between text-[11px] text-gray-500">
                        <span>Pilihan Area:</span>
                        <span class="font-semibold {{ ($pemesanan->tipe_area ?? 'Indoor') === 'Outdoor' ? 'text-amber-700' : 'text-emerald-700' }}">
                            {{ $pemesanan->tipe_area ?? 'Indoor' }} {{ ($pemesanan->tipe_area ?? 'Indoor') === 'Outdoor' ? '(+Rp 100.000)' : '(Normal)' }}
                        </span>
                    </div>

                    @if ($pemesanan->wajib_dp)
                        <div class="flex items-center justify-between text-xs py-1.5 border-t border-gray-100">
                            <span class="text-gray-500 font-medium">Down Payment (DP 10%):</span>
                            <span class="font-bold text-gray-900">Rp {{ number_format($pemesanan->nominal_dp, 0, ',', '.') }}</span>
                        </div>
                        <div class="flex items-center justify-between text-[11px] pb-1">
                            <span class="text-gray-500 font-medium">Status DP:</span>
                            <div class="text-right">
                                <span class="font-extrabold px-2.5 py-0.5 rounded-full {{ $pemesanan->status_dp === 'Lunas' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">
                                    {{ $pemesanan->status_dp === 'Lunas' ? 'Lunas' : 'Belum Dibayar' }}
                                </span>
                                @if ($pemesanan->status_dp === 'Lunas' && ($pemesanan->tgl_verifikasi_dp ?? $pemesanan->tgl_bayar_dp))
                                    <span class="block text-[10px] text-emerald-700 font-bold mt-1">
                                        Di-ACC: {{ ($pemesanan->tgl_verifikasi_dp ?? $pemesanan->tgl_bayar_dp)->translatedFormat('d/m/Y H:i') }} WITA
                                    </span>
                                @endif
                            </div>
                        </div>
                    @endif
                    <div class="flex items-center justify-between pt-2 border-t border-gray-100">
                        <span class="font-semibold text-gray-700">Harga Final Kesepakatan:</span>
                        <span class="text-base font-extrabold text-primary-700">
                            {{ $pemesanan->harga_final ? 'Rp ' . number_format($pemesanan->harga_final, 0, ',', '.') : 'Menunggu Survei' }}
                        </span>
                    </div>

                    @if ($pemesanan->wajib_dp && $pemesanan->status_dp === 'Lunas')
                        <div class="rounded-xl bg-emerald-50/80 border border-emerald-200 p-3 text-xs space-y-1.5 text-emerald-950">
                            <div class="flex items-center justify-between text-[11px] text-emerald-800">
                                <span>Potongan DP 10% (Lunas):</span>
                                <span class="font-bold">-Rp {{ number_format($pemesanan->nominal_dp, 0, ',', '.') }}</span>
                            </div>
                            <div class="flex items-center justify-between font-extrabold text-xs text-emerald-950 pt-1.5 border-t border-emerald-200">
                                <span>Sisa Pelunasan Lapangan:</span>
                                <span class="text-sm font-black text-emerald-800">Rp {{ number_format($pemesanan->sisa_pelunasan, 0, ',', '.') }}</span>
                            </div>
                            <p class="text-[10px] text-emerald-700 leading-tight pt-0.5">
                                * Dari total {{ $pemesanan->harga_final ? 'harga final' : 'estimasi' }} Rp {{ number_format($pemesanan->harga_final ?? $pemesanan->harga_estimasi, 0, ',', '.') }}, pelanggan tinggal melunasi <b>Rp {{ number_format($pemesanan->sisa_pelunasan, 0, ',', '.') }}</b> karena DP Rp {{ number_format($pemesanan->nominal_dp, 0, ',', '.') }} sudah diterima di awal.
                            </p>
                        </div>
                    @endif

                    <div class="flex items-center justify-between pt-2 border-t border-gray-100">
                        <span class="text-gray-500">Status Bayar:</span>
                        <span class="font-bold {{ $pemesanan->status_pembayaran === 'Lunas' ? 'text-emerald-700' : ($pemesanan->status_pembayaran === 'Ditolak' ? 'text-rose-700' : 'text-amber-700') }}">
                            {{ $pemesanan->status_pembayaran }}
                        </span>
                    </div>
                </div>
            </div>

            {{-- Kartu Jadwal & Teknisi Penugasan (Alur 6: Ubah Jadwal) --}}
            <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm space-y-4">
                <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                    <h4 class="text-sm font-bold text-gray-900">Jadwal & Teknisi</h4>
                    @if ($pemesanan->jadwalPenugasan)
                        <button type="button" onclick="document.getElementById('modal-ubah-jadwal').classList.remove('hidden')"
                                class="text-xs font-bold text-primary-600 hover:text-primary-800">
                            Ubah Jadwal
                        </button>
                    @endif
                </div>

                <div class="space-y-3 text-xs">
                    <div>
                        <span class="text-gray-400 font-semibold block">Teknisi Ditugaskan</span>
                        <span class="font-bold text-gray-800 block mt-0.5">
                            {{ $pemesanan->jadwalPenugasan?->teknisi?->nama ?? 'Belum Ditugaskan' }}
                        </span>
                        @if ($pemesanan->jadwalPenugasan?->teknisi)
                            <span class="text-[11px] text-gray-500 block">{{ $pemesanan->jadwalPenugasan->teknisi->no_hp }}</span>
                        @endif
                    </div>

                    <div class="pt-2 border-t border-gray-100">
                        <div class="flex items-center justify-between">
                            <span class="text-gray-400 font-semibold block">Jadwal Survei</span>
                            @if ($isDeviasiSurvei)
                                <span class="rounded-full px-2 py-0.5 text-[10px] font-bold bg-sky-100 text-sky-800 border border-sky-300">
                                    Deviasi Survei
                                </span>
                            @endif
                        </div>
                        <span class="font-bold text-gray-800 block mt-0.5">
                            {{ $pemesanan->jadwalPenugasan?->tgl_survei ? \Carbon\Carbon::parse($pemesanan->jadwalPenugasan->tgl_survei)->format('d M Y') : '-' }}
                            ({{ $pemesanan->jadwalPenugasan?->jam_survei ?? '-' }})
                        </span>
                        @if ($isDeviasiSurvei)
                            <div class="mt-1 text-[11px] text-sky-800 bg-sky-50 p-2 rounded-lg border border-sky-200">
                                Realisasi survei: <b>{{ $realisasiSurveiDisplayDate }}</b>.
                                <a href="javascript:void(0)" onclick="syncModalTglSurvei('{{ $realisasiSurveiRaw }}', '{{ $realisasiSurveiDisplayDate }}')" class="font-bold text-sky-700 underline block mt-0.5">
                                    Sesuaikan jadwal survei &rarr;
                                </a>
                            </div>
                        @endif
                    </div>

                    <div class="pt-2 border-t border-gray-100">
                        <div class="flex items-center justify-between">
                            <span class="text-gray-400 font-semibold block">Jadwal Pengerjaan (Treatment)</span>
                            @if ($isDeviasiJadwal)
                                <span class="rounded-full px-2 py-0.5 text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-300">
                                    Deviasi Jadwal
                                </span>
                            @endif
                        </div>
                        <span class="font-bold text-gray-800 block mt-0.5">
                            {{ $pemesanan->jadwalPenugasan?->tgl_pengerjaan ? \Carbon\Carbon::parse($pemesanan->jadwalPenugasan->tgl_pengerjaan)->format('d M Y') : 'Menunggu deal survei' }}
                            @if ($pemesanan->jadwalPenugasan?->jam_pengerjaan)
                                ({{ $pemesanan->jadwalPenugasan->jam_pengerjaan }})
                            @endif
                        </span>
                        @php
                            $statusService = app(\App\Services\PemesananStatusService::class);
                            $bentrokTreatmentAktif = null;
                            if ($pemesanan->jadwalPenugasan?->id_teknisi && $pemesanan->jadwalPenugasan?->tgl_pengerjaan && $pemesanan->jadwalPenugasan?->jam_pengerjaan) {
                                $bentrokTreatmentAktif = $statusService->cekBentrokJadwalTeknisi(
                                    $pemesanan->jadwalPenugasan->id_teknisi,
                                    (string) $pemesanan->jadwalPenugasan->tgl_pengerjaan,
                                    (string) $pemesanan->jadwalPenugasan->jam_pengerjaan,
                                    $pemesanan->id_pemesanan
                                );
                            }
                        @endphp
                        @if ($bentrokTreatmentAktif)
                            <div class="mt-1 text-[11px] text-rose-800 bg-rose-50 p-2 rounded-lg border border-rose-200">
                                <b>Jadwal Bentrok:</b> Teknisi memiliki pekerjaan lain di pesanan <b>{{ $bentrokTreatmentAktif->pemesanan?->kode_pemesanan ?? '#'.$bentrokTreatmentAktif->id_pemesanan }}</b> pada waktu yang sama.
                                @if (in_array($pemesanan->status_pengerjaan, ['Menunggu Treatment', 'Treatment Berlangsung', 'Menunggu Konfirmasi']))
                                    <button type="button" onclick="document.getElementById('modal-ubah-jadwal').classList.remove('hidden')" class="font-bold text-rose-700 underline block mt-0.5">
                                        Ubah / Reschedule Jadwal &rarr;
                                    </button>
                                @endif
                            </div>
                        @endif
                        @if ($isDeviasiJadwal)
                            <div class="mt-1 text-[11px] text-amber-800 bg-amber-50 p-2 rounded-lg border border-amber-200">
                                Realisasi pengerjaan: <b>{{ $realisasiDisplayDate }}</b>.
                                <a href="javascript:void(0)" onclick="syncModalTglKerja('{{ $realisasiKerjaRaw }}', '{{ $realisasiDisplayDate }}')" class="font-bold text-primary-700 underline block mt-0.5">
                                    Samakan jadwal sekarang &rarr;
                                </a>
                            </div>
                        @endif
                    </div>

                    @if ($pemesanan->tgl_verifikasi)
                        <div class="pt-2 border-t border-gray-100">
                            <span class="text-gray-400 font-semibold block">Waktu ACC Pesanan</span>
                            <span class="font-bold text-emerald-800 block mt-0.5">
                                {{ $pemesanan->tgl_verifikasi->translatedFormat('d M Y') }} pukul {{ $pemesanan->tgl_verifikasi->format('H:i') }} WITA
                            </span>
                            <span class="text-[11px] text-gray-500 block">Diverifikasi oleh: {{ $pemesanan->admin?->nama ?? 'Admin' }}</span>
                        </div>
                    @endif

                    @if ($pemesanan->wajib_dp && $pemesanan->status_dp === 'Lunas' && ($pemesanan->tgl_verifikasi_dp ?? $pemesanan->tgl_bayar_dp))
                        <div class="pt-2 border-t border-gray-100">
                            <span class="text-gray-400 font-semibold block">Waktu ACC DP</span>
                            <span class="font-bold text-emerald-800 block mt-0.5">
                                {{ ($pemesanan->tgl_verifikasi_dp ?? $pemesanan->tgl_bayar_dp)->translatedFormat('d M Y') }} pukul {{ ($pemesanan->tgl_verifikasi_dp ?? $pemesanan->tgl_bayar_dp)->format('H:i') }} WITA
                            </span>
                            <span class="text-[11px] text-gray-500 block">Nominal: Rp {{ number_format($pemesanan->nominal_dp, 0, ',', '.') }}</span>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Kartu Batalkan Pemesanan (Alur 10) --}}
            @if ($pemesanan->status_pengerjaan !== 'Cancel' && $pemesanan->status_pengerjaan !== 'Done')
                <div class="rounded-2xl border border-rose-200 bg-white p-6 shadow-sm space-y-3">
                    <h4 class="text-sm font-bold text-rose-900">Batalkan Pemesanan</h4>
                    <p class="text-xs text-gray-500">
                        Jika pelanggan mengajukan pembatalan atau pihak CV. Ambius tidak dapat melayani, batalkan pesanan di sini.
                    </p>
                    <button type="button" onclick="document.getElementById('modal-cancel').classList.remove('hidden')"
                            class="w-full py-2.5 px-4 rounded-xl border border-rose-300 font-bold text-xs text-rose-700 hover:bg-rose-50 transition">
                        Batalkan Pemesanan Ini
                    </button>
                </div>
            @endif
        </div>
    </div>
</div>

{{-- MODAL UBAH JADWAL (Alur 6) --}}
@if ($pemesanan->jadwalPenugasan)
    @php
        $jadwal = $pemesanan->jadwalPenugasan;
        $surveiDateObj = $jadwal->tgl_survei ? \Carbon\Carbon::parse($jadwal->tgl_survei) : null;
        $surveiDateVal = $surveiDateObj ? $surveiDateObj->format('Y-m-d') : '';
        $surveiDateText = $surveiDateObj ? $surveiDateObj->locale('id')->translatedFormat('l, d F Y') : '-- Pilih Tanggal --';

        $surveiTimeRaw = $jadwal->jam_survei ? \Carbon\Carbon::parse($jadwal->jam_survei)->format('H:i') : '';
        $surveiTimeText = $surveiTimeRaw ? $surveiTimeRaw . ' WITA' : '--:-- WITA';

        $kerjaDateObj = $jadwal->tgl_pengerjaan ? \Carbon\Carbon::parse($jadwal->tgl_pengerjaan) : null;
        $kerjaDateVal = $kerjaDateObj ? $kerjaDateObj->format('Y-m-d') : '';
        $kerjaDateText = $kerjaDateObj ? $kerjaDateObj->locale('id')->translatedFormat('l, d F Y') : '-- Pilih Tanggal --';

        $kerjaTimeRaw = $jadwal->jam_pengerjaan ? \Carbon\Carbon::parse($jadwal->jam_pengerjaan)->format('H:i') : '';
        $kerjaTimeText = $kerjaTimeRaw ? $kerjaTimeRaw . ' WITA' : '--:-- WITA';
    @endphp

    <div id="modal-ubah-jadwal" class="fixed inset-0 z-50 hidden bg-black/50 backdrop-blur-sm flex items-center justify-center p-4 overflow-y-auto">
        <div class="w-full max-w-lg rounded-2xl bg-white p-6 shadow-xl space-y-4 my-8">
            <h3 class="text-base font-bold text-gray-900 border-b border-gray-100 pb-3">Ubah Jadwal Survei / Treatment</h3>
            <form action="{{ route('admin.pemesanan.ubah-jadwal', $pemesanan->id_pemesanan) }}" method="POST" class="space-y-4 text-xs">
                @csrf
                
                {{-- Group 1: Jadwal Survei --}}
                <div class="rounded-xl border border-gray-100 bg-gray-50/60 p-3.5 space-y-2">
                    <span class="font-bold text-gray-800 text-[11px] uppercase tracking-wider block">1. Jadwal Survei Lapangan</span>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        {{-- Tanggal Survei --}}
                        <div class="relative picker-container" id="container_modal_tgl_survei">
                            <label class="block font-semibold text-gray-700 mb-1">Tanggal Survei</label>
                            <button type="button" id="trigger_modal_tgl_survei"
                                    class="w-full flex items-center justify-between rounded-xl border border-gray-300 bg-white px-3.5 py-2.5 text-xs text-gray-900 shadow-sm hover:border-emerald-500 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition text-left">
                                <span id="display_modal_tgl_survei" class="font-bold text-gray-900 truncate pr-1">
                                    {{ $surveiDateText }}
                                </span>
                                <span class="shrink-0 flex items-center gap-1 text-[11px] font-bold text-emerald-700 bg-emerald-50 hover:bg-emerald-100 px-2 py-0.5 rounded-lg transition">
                                    <span>Pilih</span>
                                    <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/>
                                    </svg>
                                </span>
                            </button>
                            <input type="hidden" name="tgl_survei" id="input_modal_tgl_survei" value="{{ $surveiDateVal }}">

                            {{-- Popover Kalender --}}
                            <div id="popover_modal_tgl_survei" class="picker-popover hidden absolute left-0 z-50 mt-1.5 w-72 rounded-2xl border border-gray-200 bg-white p-3 shadow-2xl text-xs">
                                <div class="flex items-center justify-between pb-2 border-b border-gray-100 mb-2">
                                    <button type="button" class="btn-prev-month p-1 rounded-lg hover:bg-gray-100 text-gray-600 transition">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/></svg>
                                    </button>
                                    <span class="calendar-month-year text-xs font-bold text-gray-800"></span>
                                    <button type="button" class="btn-next-month p-1 rounded-lg hover:bg-gray-100 text-gray-600 transition">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
                                    </button>
                                </div>
                                <div class="grid grid-cols-7 gap-1 text-center text-[10px] font-bold text-gray-400 mb-1">
                                    <span>Min</span><span>Sen</span><span>Sel</span><span>Rab</span><span>Kam</span><span>Jum</span><span>Sab</span>
                                </div>
                                <div class="calendar-days-grid grid grid-cols-7 gap-1 text-center"></div>
                                <div class="pt-2 mt-2 border-t border-gray-100 flex items-center justify-between">
                                    <span class="calendar-selected-preview text-[11px] font-bold text-emerald-700 truncate pr-1"></span>
                                    <div class="flex items-center gap-1">
                                        <button type="button" class="btn-clear-date px-2 py-1 text-gray-500 hover:text-rose-600 text-[10px] font-bold transition">Hapus</button>
                                        <button type="button" class="btn-confirm-date px-2.5 py-1 bg-emerald-600 hover:bg-emerald-700 text-white text-[10px] font-bold rounded-lg shadow-sm transition">Selesai</button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Jam Survei --}}
                        <div class="relative picker-container" id="container_modal_jam_survei">
                            <label class="block font-semibold text-gray-700 mb-1">Jam Survei (24 Jam - WITA)</label>
                            <button type="button" id="trigger_modal_jam_survei"
                                    class="w-full flex items-center justify-between rounded-xl border border-gray-300 bg-white px-3.5 py-2.5 text-xs text-gray-900 shadow-sm hover:border-emerald-500 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition text-left">
                                <span id="display_modal_jam_survei" class="font-extrabold text-gray-900">
                                    {{ $surveiTimeText }}
                                </span>
                                <span class="shrink-0 flex items-center gap-1 text-[11px] font-bold text-emerald-700 bg-emerald-50 hover:bg-emerald-100 px-2 py-0.5 rounded-lg transition">
                                    <span>Pilih</span>
                                    <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/>
                                    </svg>
                                </span>
                            </button>
                            <input type="hidden" name="jam_survei" id="input_modal_jam_survei" value="{{ $surveiTimeRaw }}">

                            {{-- Popover Jam --}}
                            <div id="popover_modal_jam_survei" class="picker-popover hidden absolute right-0 z-50 mt-1.5 w-60 rounded-2xl border border-gray-200 bg-white p-3 shadow-2xl text-xs">
                                <div class="flex items-center justify-between pb-2 border-b border-gray-100 mb-2">
                                    <span class="text-[11px] font-bold text-gray-800">Pilih Jam (24 Jam)</span>
                                    <span class="text-[10px] font-black text-emerald-700 bg-emerald-100/80 px-2 py-0.5 rounded-md">WITA</span>
                                </div>
                                <div class="grid grid-cols-2 gap-2 text-center">
                                    <div>
                                        <span class="text-[10px] font-bold text-gray-500 uppercase tracking-wider block mb-1">Jam</span>
                                        <div class="col-jam h-36 overflow-y-auto space-y-1 p-1 bg-gray-50/70 rounded-xl border border-gray-100 scroll-smooth">
                                            @for ($h = 6; $h <= 22; $h++)
                                                @php $hVal = sprintf('%02d', $h); @endphp
                                                <button type="button" data-hour="{{ $hVal }}" class="btn-hour-opt w-full py-1 text-xs font-bold rounded-lg transition text-gray-700 hover:bg-gray-200/80">
                                                    {{ $hVal }}:00
                                                </button>
                                            @endfor
                                        </div>
                                    </div>
                                    <div>
                                        <span class="text-[10px] font-bold text-gray-500 uppercase tracking-wider block mb-1">Menit</span>
                                        <div class="col-menit h-36 overflow-y-auto space-y-1 p-1 bg-gray-50/70 rounded-xl border border-gray-100 scroll-smooth">
                                            @foreach (['00', '05', '10', '15', '20', '25', '30', '35', '40', '45', '50', '55'] as $mVal)
                                                <button type="button" data-minute="{{ $mVal }}" class="btn-minute-opt w-full py-1 text-xs font-bold rounded-lg transition text-gray-700 hover:bg-gray-200/80">
                                                    :{{ $mVal }}
                                                </button>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                                <div class="pt-2 mt-2 border-t border-gray-100 flex items-center justify-between">
                                    <span class="preview-time-text text-[11px] font-extrabold text-emerald-700"></span>
                                    <div class="flex items-center gap-1">
                                        <button type="button" class="btn-clear-time px-2 py-1 text-gray-500 hover:text-rose-600 text-[10px] font-bold transition">Hapus</button>
                                        <button type="button" class="btn-confirm-time px-2.5 py-1 bg-emerald-600 hover:bg-emerald-700 text-white text-[10px] font-bold rounded-lg shadow-sm transition">Selesai</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Group 2: Jadwal Pengerjaan (Treatment) --}}
                <div class="rounded-xl border border-gray-100 bg-gray-50/60 p-3.5 space-y-2">
                    <span class="font-bold text-gray-800 text-[11px] uppercase tracking-wider block">2. Jadwal Pengerjaan (Treatment)</span>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        {{-- Tanggal Pengerjaan --}}
                        <div class="relative picker-container" id="container_modal_tgl_pengerjaan">
                            <label class="block font-semibold text-gray-700 mb-1">Tanggal Pengerjaan</label>
                            <button type="button" id="trigger_modal_tgl_pengerjaan"
                                    class="w-full flex items-center justify-between rounded-xl border border-gray-300 bg-white px-3.5 py-2.5 text-xs text-gray-900 shadow-sm hover:border-emerald-500 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition text-left">
                                <span id="display_modal_tgl_pengerjaan" class="font-bold text-gray-900 truncate pr-1">
                                    {{ $kerjaDateText }}
                                </span>
                                <span class="shrink-0 flex items-center gap-1 text-[11px] font-bold text-emerald-700 bg-emerald-50 hover:bg-emerald-100 px-2 py-0.5 rounded-lg transition">
                                    <span>Pilih</span>
                                    <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/>
                                    </svg>
                                </span>
                            </button>
                            <input type="hidden" name="tgl_pengerjaan" id="input_modal_tgl_pengerjaan" value="{{ $kerjaDateVal }}">

                            {{-- Popover Kalender --}}
                            <div id="popover_modal_tgl_pengerjaan" class="picker-popover hidden absolute left-0 z-50 mt-1.5 w-72 rounded-2xl border border-gray-200 bg-white p-3 shadow-2xl text-xs">
                                <div class="flex items-center justify-between pb-2 border-b border-gray-100 mb-2">
                                    <button type="button" class="btn-prev-month p-1 rounded-lg hover:bg-gray-100 text-gray-600 transition">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/></svg>
                                    </button>
                                    <span class="calendar-month-year text-xs font-bold text-gray-800"></span>
                                    <button type="button" class="btn-next-month p-1 rounded-lg hover:bg-gray-100 text-gray-600 transition">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
                                    </button>
                                </div>
                                <div class="grid grid-cols-7 gap-1 text-center text-[10px] font-bold text-gray-400 mb-1">
                                    <span>Min</span><span>Sen</span><span>Sel</span><span>Rab</span><span>Kam</span><span>Jum</span><span>Sab</span>
                                </div>
                                <div class="calendar-days-grid grid grid-cols-7 gap-1 text-center"></div>
                                <div class="pt-2 mt-2 border-t border-gray-100 flex items-center justify-between">
                                    <span class="calendar-selected-preview text-[11px] font-bold text-emerald-700 truncate pr-1"></span>
                                    <div class="flex items-center gap-1">
                                        <button type="button" class="btn-clear-date px-2 py-1 text-gray-500 hover:text-rose-600 text-[10px] font-bold transition">Hapus</button>
                                        <button type="button" class="btn-confirm-date px-2.5 py-1 bg-emerald-600 hover:bg-emerald-700 text-white text-[10px] font-bold rounded-lg shadow-sm transition">Selesai</button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Jam Pengerjaan --}}
                        <div class="relative picker-container" id="container_modal_jam_pengerjaan">
                            <label class="block font-semibold text-gray-700 mb-1">Jam Pengerjaan (24 Jam - WITA)</label>
                            <button type="button" id="trigger_modal_jam_pengerjaan"
                                    class="w-full flex items-center justify-between rounded-xl border border-gray-300 bg-white px-3.5 py-2.5 text-xs text-gray-900 shadow-sm hover:border-emerald-500 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition text-left">
                                <span id="display_modal_jam_pengerjaan" class="font-extrabold text-gray-900">
                                    {{ $kerjaTimeText }}
                                </span>
                                <span class="shrink-0 flex items-center gap-1 text-[11px] font-bold text-emerald-700 bg-emerald-50 hover:bg-emerald-100 px-2 py-0.5 rounded-lg transition">
                                    <span>Pilih</span>
                                    <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/>
                                    </svg>
                                </span>
                            </button>
                            <input type="hidden" name="jam_pengerjaan" id="input_modal_jam_pengerjaan" value="{{ $kerjaTimeRaw }}">

                            {{-- Popover Jam --}}
                            <div id="popover_modal_jam_pengerjaan" class="picker-popover hidden absolute right-0 z-50 mt-1.5 w-60 rounded-2xl border border-gray-200 bg-white p-3 shadow-2xl text-xs">
                                <div class="flex items-center justify-between pb-2 border-b border-gray-100 mb-2">
                                    <span class="text-[11px] font-bold text-gray-800">Pilih Jam (24 Jam)</span>
                                    <span class="text-[10px] font-black text-emerald-700 bg-emerald-100/80 px-2 py-0.5 rounded-md">WITA</span>
                                </div>
                                <div class="grid grid-cols-2 gap-2 text-center">
                                    <div>
                                        <span class="text-[10px] font-bold text-gray-500 uppercase tracking-wider block mb-1">Jam</span>
                                        <div class="col-jam h-36 overflow-y-auto space-y-1 p-1 bg-gray-50/70 rounded-xl border border-gray-100 scroll-smooth">
                                            @for ($h = 6; $h <= 22; $h++)
                                                @php $hVal = sprintf('%02d', $h); @endphp
                                                <button type="button" data-hour="{{ $hVal }}" class="btn-hour-opt w-full py-1 text-xs font-bold rounded-lg transition text-gray-700 hover:bg-gray-200/80">
                                                    {{ $hVal }}:00
                                                </button>
                                            @endfor
                                        </div>
                                    </div>
                                    <div>
                                        <span class="text-[10px] font-bold text-gray-500 uppercase tracking-wider block mb-1">Menit</span>
                                        <div class="col-menit h-36 overflow-y-auto space-y-1 p-1 bg-gray-50/70 rounded-xl border border-gray-100 scroll-smooth">
                                            @foreach (['00', '05', '10', '15', '20', '25', '30', '35', '40', '45', '50', '55'] as $mVal)
                                                <button type="button" data-minute="{{ $mVal }}" class="btn-minute-opt w-full py-1 text-xs font-bold rounded-lg transition text-gray-700 hover:bg-gray-200/80">
                                                    :{{ $mVal }}
                                                </button>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                                <div class="pt-2 mt-2 border-t border-gray-100 flex items-center justify-between">
                                    <span class="preview-time-text text-[11px] font-extrabold text-emerald-700"></span>
                                    <div class="flex items-center gap-1">
                                        <button type="button" class="btn-clear-time px-2 py-1 text-gray-500 hover:text-rose-600 text-[10px] font-bold transition">Hapus</button>
                                        <button type="button" class="btn-confirm-time px-2.5 py-1 bg-emerald-600 hover:bg-emerald-700 text-white text-[10px] font-bold rounded-lg shadow-sm transition">Selesai</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="pt-3 border-t border-gray-100 flex justify-end gap-2">
                    <button type="button" onclick="document.getElementById('modal-ubah-jadwal').classList.add('hidden')" class="px-4 py-2 rounded-xl border border-gray-300 font-semibold text-gray-600 hover:bg-gray-50 transition">Batal</button>
                    <button type="submit" class="px-5 py-2 rounded-xl bg-primary-600 font-bold text-white shadow-sm hover:bg-primary-700 transition">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
@endif

{{-- MODAL TOLAK AWAL (Alur 2) --}}
<div id="modal-tolak-awal" class="fixed inset-0 z-50 hidden bg-black/50 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl space-y-4">
        <h3 class="text-base font-bold text-rose-900 border-b border-gray-100 pb-3">Tolak Pemesanan Masuk</h3>
        <p class="text-xs text-gray-600">Menolak pemesanan akan mengubah status menjadi Cancel dan status pembayaran menjadi Ditolak.</p>
        <form action="{{ route('admin.pemesanan.tolak-awal', $pemesanan->id_pemesanan) }}" method="POST" class="space-y-3 text-xs">
            @csrf
            <div>
                <label class="block font-semibold text-gray-700 mb-1">Alasan Penolakan <span class="text-rose-500">*</span></label>
                <textarea name="alasan_penolakan" rows="3" required placeholder="Contoh: Lokasi di luar jangkauan operasional..." class="w-full rounded-xl border-gray-300 px-4 py-2.5 text-xs sm:text-sm text-gray-800 placeholder-gray-400 shadow-sm focus:border-rose-500 focus:ring-rose-500 transition"></textarea>
            </div>
            <div class="pt-3 border-t border-gray-100 flex justify-end gap-2">
                <button type="button" onclick="document.getElementById('modal-tolak-awal').classList.add('hidden')" class="px-4 py-2 rounded-xl border border-gray-300 font-semibold text-gray-600">Batal</button>
                <button type="submit" class="px-4 py-2 rounded-xl bg-rose-600 font-bold text-white shadow-sm hover:bg-rose-700">Tolak Pemesanan</button>
            </div>
        </form>
    </div>
</div>

{{-- MODAL BATALKAN PESANAN (Alur 10) --}}
<div id="modal-cancel" class="fixed inset-0 z-50 hidden bg-black/50 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl space-y-4">
        <h3 class="text-base font-bold text-rose-900 border-b border-gray-100 pb-3">Batalkan Pemesanan</h3>
        <form action="{{ route('admin.pemesanan.cancel', $pemesanan->id_pemesanan) }}" method="POST" class="space-y-3 text-xs">
            @csrf
            <div>
                <label class="block font-semibold text-gray-700 mb-1">Dibatalkan Oleh <span class="text-rose-500">*</span></label>
                <select name="dibatalkan_oleh" required class="w-full rounded-xl border-gray-300 px-3.5 py-2.5 text-xs sm:text-sm text-gray-800 focus:border-rose-500 focus:ring-rose-500 shadow-sm transition">
                    <option value="Pelanggan">Pelanggan (Permintaan Client)</option>
                    <option value="CV. Ambius Indonesia">CV. Ambius Indonesia (Pihak Manajemen)</option>
                </select>
            </div>
            <div>
                <label class="block font-semibold text-gray-700 mb-1">Alasan Pembatalan <span class="text-rose-500">*</span></label>
                <textarea name="alasan_pembatalan" rows="3" required placeholder="Tuliskan alasan lengkap pembatalan..." class="w-full rounded-xl border-gray-300 px-4 py-2.5 text-xs sm:text-sm text-gray-800 placeholder-gray-400 shadow-sm focus:border-rose-500 focus:ring-rose-500 transition"></textarea>
            </div>
            <div class="pt-3 border-t border-gray-100 flex justify-end gap-2">
                <button type="button" onclick="document.getElementById('modal-cancel').classList.add('hidden')" class="px-4 py-2 rounded-xl border border-gray-300 font-semibold text-gray-600">Tutup</button>
                <button type="submit" class="px-4 py-2 rounded-xl bg-rose-600 font-bold text-white shadow-sm hover:bg-rose-700">Batalkan Sekarang</button>
            </div>
        </form>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        // --- Custom Date Picker & Time Picker Helper ---
        function initCustomDatePicker(config) {
            const container = document.getElementById(config.containerId);
            const trigger = document.getElementById(config.triggerId);
            const popover = document.getElementById(config.popoverId);
            const input = document.getElementById(config.inputId);
            const display = document.getElementById(config.displayId);
            if (!container || !trigger || !popover || !input || !display) return;

            const monthYearEl = popover.querySelector('.calendar-month-year');
            const daysGridEl = popover.querySelector('.calendar-days-grid');
            const previewEl = popover.querySelector('.calendar-selected-preview');
            const btnPrev = popover.querySelector('.btn-prev-month');
            const btnNext = popover.querySelector('.btn-next-month');
            const btnConfirm = popover.querySelector('.btn-confirm-date');
            const btnClear = popover.querySelector('.btn-clear-date');

            const indoMonths = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
            const indoDays = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];

            let selectedDate = input.value ? new Date(input.value + 'T00:00:00') : null;
            let viewDate = selectedDate ? new Date(selectedDate) : new Date();

            function formatDateString(d) {
                const y = d.getFullYear();
                const m = String(d.getMonth() + 1).padStart(2, '0');
                const day = String(d.getDate()).padStart(2, '0');
                return `${y}-${m}-${day}`;
            }

            function formatDisplayDate(d) {
                return `${indoDays[d.getDay()]}, ${d.getDate()} ${indoMonths[d.getMonth()]} ${d.getFullYear()}`;
            }

            function renderCalendar() {
                const y = viewDate.getFullYear();
                const m = viewDate.getMonth();
                if (monthYearEl) monthYearEl.textContent = `${indoMonths[m]} ${y}`;

                const firstDayIdx = new Date(y, m, 1).getDay();
                const lastDate = new Date(y, m + 1, 0).getDate();
                const prevLastDate = new Date(y, m, 0).getDate();

                if (!daysGridEl) return;
                daysGridEl.innerHTML = '';

                for (let i = firstDayIdx; i > 0; i--) {
                    const cell = document.createElement('div');
                    cell.className = 'text-gray-300 text-xs py-1.5 select-none';
                    cell.textContent = prevLastDate - i + 1;
                    daysGridEl.appendChild(cell);
                }

                const today = new Date();
                today.setHours(0,0,0,0);

                for (let day = 1; day <= lastDate; day++) {
                    const currDate = new Date(y, m, day);
                    currDate.setHours(0,0,0,0);
                    const isSelected = selectedDate && (currDate.getTime() === selectedDate.getTime());
                    const isToday = (currDate.getTime() === today.getTime());

                    const btn = document.createElement('button');
                    btn.type = 'button';
                    btn.textContent = day;

                    if (isSelected) {
                        btn.className = 'w-full py-1.5 text-xs font-black bg-emerald-600 text-white rounded-lg shadow-sm transition';
                    } else if (isToday) {
                        btn.className = 'w-full py-1.5 text-xs font-bold text-emerald-700 border border-emerald-400 rounded-lg hover:bg-emerald-50 transition';
                    } else {
                        btn.className = 'w-full py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-100 rounded-lg transition';
                    }

                    btn.addEventListener('click', function (e) {
                        e.preventDefault();
                        e.stopPropagation();
                        selectedDate = currDate;
                        input.value = formatDateString(selectedDate);
                        input.dispatchEvent(new Event('change'));
                        display.textContent = formatDisplayDate(selectedDate);
                        if (previewEl) previewEl.textContent = formatDisplayDate(selectedDate);
                        renderCalendar();
                        popover.classList.add('hidden');
                    });

                    daysGridEl.appendChild(btn);
                }

                if (previewEl) {
                    previewEl.textContent = selectedDate ? formatDisplayDate(selectedDate) : 'Belum dipilih';
                }
            }

            trigger.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                const isHidden = popover.classList.contains('hidden');
                document.querySelectorAll('.picker-popover').forEach(p => {
                    if (p !== popover) p.classList.add('hidden');
                });
                if (isHidden) {
                    if (input.value) {
                        selectedDate = new Date(input.value + 'T00:00:00');
                        viewDate = new Date(selectedDate);
                    } else {
                        selectedDate = null;
                        viewDate = new Date();
                    }
                    popover.classList.remove('hidden');
                    renderCalendar();
                } else {
                    popover.classList.add('hidden');
                }
            });

            input.addEventListener('change', function () {
                if (input.value) {
                    selectedDate = new Date(input.value + 'T00:00:00');
                    viewDate = new Date(selectedDate);
                } else {
                    selectedDate = null;
                    viewDate = new Date();
                }
                if (previewEl) {
                    previewEl.textContent = selectedDate ? formatDisplayDate(selectedDate) : 'Belum dipilih';
                }
                renderCalendar();
            });

            if (btnPrev) {
                btnPrev.addEventListener('click', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    viewDate.setMonth(viewDate.getMonth() - 1);
                    renderCalendar();
                });
            }

            if (btnNext) {
                btnNext.addEventListener('click', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    viewDate.setMonth(viewDate.getMonth() + 1);
                    renderCalendar();
                });
            }

            if (btnConfirm) {
                btnConfirm.addEventListener('click', function (e) {
                    e.preventDefault();
                    popover.classList.add('hidden');
                });
            }

            if (btnClear) {
                btnClear.addEventListener('click', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    selectedDate = null;
                    input.value = '';
                    input.dispatchEvent(new Event('change'));
                    display.textContent = '-- Pilih Tanggal --';
                    if (previewEl) previewEl.textContent = 'Belum dipilih';
                    renderCalendar();
                    popover.classList.add('hidden');
                });
            }
        }

        const semuaJadwalSibukTeknisi = @json($semuaJadwalSibukTeknisi ?? []);
        let adminJadwalSibukTeknisi = @json($jadwalSibukTeknisi ?? []);

        function initCustomTimePicker(config) {
            const container = document.getElementById(config.containerId);
            const trigger = document.getElementById(config.triggerId);
            const popover = document.getElementById(config.popoverId);
            const input = document.getElementById(config.inputId);
            const display = document.getElementById(config.displayId);
            const dateInput = config.dateInputId ? document.getElementById(config.dateInputId) : null;
            if (!container || !trigger || !popover || !input || !display) return;

            let parts = (input.value || '').split(':');
            let currentHour = parts[0] ? parts[0].padStart(2, '0') : '09';
            let currentMinute = parts[1] ? parts[1].padStart(2, '0') : '00';
            let hasValue = !!input.value;

            const previewEl = popover.querySelector('.preview-time-text');
            const btnConfirm = popover.querySelector('.btn-confirm-time');
            const btnClear = popover.querySelector('.btn-clear-time');

            function updateTimeDisplay() {
                if (hasValue) {
                    const formatted = `${currentHour}:${currentMinute}`;
                    input.value = formatted;
                    display.textContent = `${formatted} WITA`;
                    if (previewEl) previewEl.textContent = `${formatted} WITA`;
                } else {
                    input.value = '';
                    display.textContent = '--:-- WITA';
                    if (previewEl) previewEl.textContent = 'Belum dipilih';
                }
            }

            function syncButtons() {
                const dateVal = dateInput ? dateInput.value : '';
                const dayData = adminJadwalSibukTeknisi[dateVal] || { jam: [], detail: {} };
                const busyHours = dayData.jam || [];
                const busyDetails = dayData.detail || {};

                popover.querySelectorAll('.btn-hour-opt').forEach(btn => {
                    const hVal = btn.getAttribute('data-hour');
                    const isBusy = busyHours.includes(hVal);

                    if (isBusy) {
                        btn.disabled = true;
                        const det = busyDetails[hVal];
                        const detInfo = det ? (det.jam + ' - ' + det.kode) : (hVal + ':00');
                        btn.setAttribute('title', 'Slot jam ini sudah terisi penugasan ' + detInfo);
                        btn.className = 'btn-hour-opt w-full py-1 text-xs font-bold rounded-lg transition bg-gray-100 text-gray-400 cursor-not-allowed opacity-50 line-through';
                    } else {
                        btn.disabled = false;
                        btn.removeAttribute('title');
                        if (hVal === currentHour && hasValue) {
                            btn.className = 'btn-hour-opt w-full py-1 text-xs font-bold rounded-lg transition bg-emerald-600 text-white shadow-sm';
                        } else {
                            btn.className = 'btn-hour-opt w-full py-1 text-xs font-bold rounded-lg transition text-gray-700 hover:bg-gray-200/80';
                        }
                    }
                });

                if (hasValue && busyHours.includes(currentHour)) {
                    let firstFree = null;
                    popover.querySelectorAll('.btn-hour-opt').forEach(b => {
                        if (!b.disabled && !firstFree) firstFree = b;
                    });
                    if (firstFree) {
                        currentHour = firstFree.getAttribute('data-hour');
                        updateTimeDisplay();
                        syncButtons();
                        return;
                    }
                }

                popover.querySelectorAll('.btn-minute-opt').forEach(btn => {
                    if (btn.getAttribute('data-minute') === currentMinute && hasValue) {
                        btn.className = 'btn-minute-opt w-full py-1 text-xs font-bold rounded-lg transition bg-emerald-600 text-white shadow-sm';
                    } else {
                        btn.className = 'btn-minute-opt w-full py-1 text-xs font-bold rounded-lg transition text-gray-700 hover:bg-gray-200/80';
                    }
                });
            }

            if (dateInput) {
                dateInput.addEventListener('change', syncButtons);
            }

            trigger.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                const isHidden = popover.classList.contains('hidden');
                document.querySelectorAll('.picker-popover').forEach(p => {
                    if (p !== popover) p.classList.add('hidden');
                });
                if (isHidden) {
                    popover.classList.remove('hidden');
                    syncButtons();
                    setTimeout(() => {
                        const activeH = popover.querySelector('.btn-hour-opt.bg-emerald-600');
                        const activeM = popover.querySelector('.btn-minute-opt.bg-emerald-600');
                        if (activeH) activeH.scrollIntoView({ block: 'center', behavior: 'smooth' });
                        if (activeM) activeM.scrollIntoView({ block: 'center', behavior: 'smooth' });
                    }, 50);
                } else {
                    popover.classList.add('hidden');
                }
            });

            popover.querySelectorAll('.btn-hour-opt').forEach(btn => {
                btn.addEventListener('click', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    currentHour = this.getAttribute('data-hour');
                    hasValue = true;
                    updateTimeDisplay();
                    syncButtons();
                });
            });

            popover.querySelectorAll('.btn-minute-opt').forEach(btn => {
                btn.addEventListener('click', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    currentMinute = this.getAttribute('data-minute');
                    hasValue = true;
                    updateTimeDisplay();
                    syncButtons();
                });
            });

            if (btnConfirm) {
                btnConfirm.addEventListener('click', function (e) {
                    e.preventDefault();
                    if (!hasValue) {
                        hasValue = true;
                        updateTimeDisplay();
                    }
                    popover.classList.add('hidden');
                });
            }

            if (btnClear) {
                btnClear.addEventListener('click', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    hasValue = false;
                    updateTimeDisplay();
                    syncButtons();
                    popover.classList.add('hidden');
                });
            }

            if (dateInput) {
                dateInput.addEventListener('change', function () {
                    syncButtons();
                });
            }

            updateTimeDisplay();
            syncButtons();
        }

        // Global click outside to dismiss popovers
        document.addEventListener('click', function (e) {
            if (!e.target.closest('.picker-container') && !e.target.closest('#admin_time_picker_container')) {
                document.querySelectorAll('.picker-popover').forEach(p => p.classList.add('hidden'));
            }
        });

        // Initialize verification date picker
        initCustomDatePicker({
            containerId: 'container_admin_tgl_survei',
            triggerId: 'trigger_admin_tgl_survei',
            popoverId: 'popover_admin_tgl_survei',
            inputId: 'input_admin_tgl_survei',
            displayId: 'display_admin_tgl_survei'
        });

        // Initialize modal date & time pickers
        initCustomDatePicker({
            containerId: 'container_modal_tgl_survei',
            triggerId: 'trigger_modal_tgl_survei',
            popoverId: 'popover_modal_tgl_survei',
            inputId: 'input_modal_tgl_survei',
            displayId: 'display_modal_tgl_survei'
        });

        initCustomTimePicker({
            containerId: 'container_modal_jam_survei',
            triggerId: 'trigger_modal_jam_survei',
            popoverId: 'popover_modal_jam_survei',
            inputId: 'input_modal_jam_survei',
            displayId: 'display_modal_jam_survei',
            dateInputId: 'input_modal_tgl_survei'
        });

        initCustomDatePicker({
            containerId: 'container_modal_tgl_pengerjaan',
            triggerId: 'trigger_modal_tgl_pengerjaan',
            popoverId: 'popover_modal_tgl_pengerjaan',
            inputId: 'input_modal_tgl_pengerjaan',
            displayId: 'display_modal_tgl_pengerjaan'
        });

        initCustomTimePicker({
            containerId: 'container_modal_jam_pengerjaan',
            triggerId: 'trigger_modal_jam_pengerjaan',
            popoverId: 'popover_modal_jam_pengerjaan',
            inputId: 'input_modal_jam_pengerjaan',
            displayId: 'display_modal_jam_pengerjaan',
            dateInputId: 'input_modal_tgl_pengerjaan'
        });

        // --- Verifikasi Survei & Penugasan Teknisi (Alur 2) ---
        const adminTimePickerContainer = document.getElementById('admin_time_picker_container');
        const adminTimePickerTrigger = document.getElementById('admin_time_picker_trigger');
        const adminTimePickerPopover = document.getElementById('admin_time_picker_popover');
        const adminHiddenJam = document.getElementById('admin_jam_survei');
        const adminDisplayTimeBtn = document.getElementById('admin_display_time_btn');
        const adminPopoverPreviewText = document.getElementById('admin_popover_preview_text');
        const adminBtnConfirmTime = document.getElementById('admin_btn_confirm_time');
        const adminHiddenTgl = document.getElementById('input_admin_tgl_survei');
        const adminTeknisiContainer = document.getElementById('admin_teknisi_picker_container');
        const adminTeknisiTrigger = document.getElementById('admin_teknisi_picker_trigger');
        const adminTeknisiPopover = document.getElementById('admin_teknisi_picker_popover');
        const adminHiddenTeknisi = document.getElementById('input_admin_id_teknisi');
        const displayAdminTeknisiName = document.getElementById('display_admin_teknisi_name');
        const displayAdminTeknisiSub = document.getElementById('display_admin_teknisi_sub');

        let currentHour = "{{ $defaultAdminHour ?? '09' }}";
        let currentMinute = "{{ $defaultAdminMinute ?? '00' }}";

        function updateAdminTimeDisplay() {
            const timeFormatted = currentHour + ':' + currentMinute;
            if (adminHiddenJam) adminHiddenJam.value = timeFormatted;
            if (adminDisplayTimeBtn) adminDisplayTimeBtn.textContent = timeFormatted + ' WITA';
            if (adminPopoverPreviewText) adminPopoverPreviewText.textContent = timeFormatted + ' WITA';
        }

        function scrollAdminToActive() {
            if (!adminTimePickerPopover) return;
            const activeH = adminTimePickerPopover.querySelector('.admin-hour-opt.bg-emerald-600');
            const activeM = adminTimePickerPopover.querySelector('.admin-minute-opt.bg-emerald-600');
            if (activeH) activeH.scrollIntoView({ block: 'center', behavior: 'smooth' });
            if (activeM) activeM.scrollIntoView({ block: 'center', behavior: 'smooth' });
        }

        function syncAdminVerificationSlots() {
            if (!adminTimePickerPopover) return;
            const teknisiId = adminHiddenTeknisi ? adminHiddenTeknisi.value : null;
            const tglVal = adminHiddenTgl ? adminHiddenTgl.value : null;
            const statusBadge = document.getElementById('admin_slot_status_badge');
            const infoTitle = document.getElementById('admin_info_box_title');
            const infoStatus = document.getElementById('admin_info_box_status');
            const infoContent = document.getElementById('admin_info_box_content');
            const tekName = displayAdminTeknisiName ? displayAdminTeknisiName.textContent.trim() : 'Teknisi';

            if (!teknisiId || !tglVal) {
                if (statusBadge) {
                    statusBadge.textContent = 'Pilih teknisi & tanggal';
                    statusBadge.className = 'inline-block mt-0.5 text-[9px] font-bold text-slate-500 bg-slate-100 px-1.5 py-0.5 rounded';
                }
                if (infoContent) infoContent.innerHTML = 'Pilih teknisi dan tanggal untuk memeriksa slot jam penugasan yang tersedia.';
                return;
            }

            function applySlots(scheduleData) {
                const dayData = (scheduleData && scheduleData[tglVal]) ? scheduleData[tglVal] : { jam: [], detail: {} };
                const busyHours = Array.isArray(dayData.jam) ? dayData.jam : [];
                const busyDetails = dayData.detail || {};
                const hourBtns = adminTimePickerPopover.querySelectorAll('.admin-hour-opt');
                let bookedCount = 0;

                hourBtns.forEach(btn => {
                    const hourVal = btn.getAttribute('data-hour');
                    const badge = btn.querySelector('.admin-hour-slot-badge');
                    const isBusy = busyHours.includes(hourVal);

                    if (isBusy) {
                        bookedCount++;
                        btn.disabled = true;
                        const det = busyDetails[hourVal];
                        const detType = det ? det.tipe : 'Jadwal';
                        const detKode = det ? det.kode : '';
                        btn.setAttribute('title', `Teknisi sudah ada tugas ${detType} (${detKode}) pada jam ${hourVal}:00 WITA.`);
                        btn.className = 'admin-hour-opt w-full py-1.5 px-2 text-xs font-bold rounded-lg transition flex items-center justify-between bg-slate-100 text-slate-400 cursor-not-allowed opacity-60 line-through';
                        if (badge) {
                            badge.textContent = `Penuh: ${detType === 'Pengerjaan' ? 'Kerja' : 'Survei'}`;
                            badge.className = 'admin-hour-slot-badge text-[9px] font-black text-rose-600 bg-rose-50 border border-rose-200 px-1.5 py-0.5 rounded';
                            badge.classList.remove('hidden');
                        }
                    } else {
                        btn.disabled = false;
                        btn.removeAttribute('title');
                        if (hourVal === currentHour) {
                            btn.className = 'admin-hour-opt w-full py-1.5 px-2 text-xs font-bold rounded-lg transition flex items-center justify-between bg-emerald-600 text-white shadow-sm';
                        } else {
                            btn.className = 'admin-hour-opt w-full py-1.5 px-2 text-xs font-bold rounded-lg transition flex items-center justify-between text-gray-700 hover:bg-gray-200/80';
                        }
                        if (badge) {
                            badge.textContent = '';
                            badge.classList.add('hidden');
                        }
                    }
                });

                if (statusBadge) {
                    if (bookedCount > 0) {
                        statusBadge.textContent = `${bookedCount} Jam Penuh`;
                        statusBadge.className = 'inline-block mt-0.5 text-[9px] font-extrabold text-amber-700 bg-amber-100/80 px-1.5 py-0.5 rounded';
                    } else {
                        statusBadge.textContent = 'Semua Jam Tersedia';
                        statusBadge.className = 'inline-block mt-0.5 text-[9px] font-extrabold text-emerald-700 bg-emerald-100/80 px-1.5 py-0.5 rounded';
                    }
                }

                // Jika jam saat ini bentrok, otomatis alihkan ke jam pertama yang tersedia!
                if (busyHours.includes(currentHour)) {
                    let firstAvailable = null;
                    hourBtns.forEach(b => {
                        if (!b.disabled && !firstAvailable) firstAvailable = b;
                    });
                    if (firstAvailable) {
                        currentHour = firstAvailable.getAttribute('data-hour');
                        hourBtns.forEach(b => {
                            if (!b.disabled) {
                                if (b.getAttribute('data-hour') === currentHour) {
                                    b.className = 'admin-hour-opt w-full py-1.5 px-2 text-xs font-bold rounded-lg transition flex items-center justify-between bg-emerald-600 text-white shadow-sm';
                                } else {
                                    b.className = 'admin-hour-opt w-full py-1.5 px-2 text-xs font-bold rounded-lg transition flex items-center justify-between text-gray-700 hover:bg-gray-200/80';
                                }
                            }
                        });
                        updateAdminTimeDisplay();
                    }
                }

                // Update info summary box
                if (infoTitle) infoTitle.textContent = `Jadwal Teknisi: ${tekName}`;
                if (infoStatus) {
                    if (bookedCount > 0) {
                        infoStatus.textContent = `${bookedCount} Jam Terisi`;
                        infoStatus.className = 'text-[10px] font-bold text-amber-800 bg-amber-100 border border-amber-300 px-2 py-0.5 rounded-md';
                    } else {
                        infoStatus.textContent = 'Tersedia Penuh';
                        infoStatus.className = 'text-[10px] font-bold text-emerald-800 bg-emerald-100 border border-emerald-300 px-2 py-0.5 rounded-md';
                    }
                }
                if (infoContent) {
                    if (bookedCount > 0) {
                        let pillsHtml = '<div class="flex flex-wrap gap-2 mt-1.5">';
                        Object.keys(busyDetails).sort().forEach(h => {
                            const d = busyDetails[h];
                            pillsHtml += `<span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-[11px] font-bold border border-rose-200 bg-rose-50 text-rose-800">
                                <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                                <span>${d.jam} WITA • ${d.tipe} (${d.kode})</span>
                            </span>`;
                        });
                        pillsHtml += '</div>';
                        infoContent.innerHTML = `<p class="leading-relaxed font-semibold text-slate-700">Teknisi <b>${tekName}</b> memiliki ${bookedCount} penugasan pada tanggal ini:</p>${pillsHtml}<p class="mt-1 text-[10px] text-slate-500">Slot jam di atas otomatis dikunci dan diberi tanda 'Penuh' agar survei tidak saling bertabrakan.</p>`;
                    } else {
                        infoContent.innerHTML = `<p class="text-slate-600">Seluruh slot jam operasional (06:00 - 22:00 WITA) untuk teknisi <b>${tekName}</b> masih kosong dan siap ditugaskan pada tanggal ini.</p>`;
                    }
                }
            }

            if (semuaJadwalSibukTeknisi && semuaJadwalSibukTeknisi[teknisiId]) {
                applySlots(semuaJadwalSibukTeknisi[teknisiId]);
            } else {
                if (statusBadge) {
                    statusBadge.textContent = 'Memeriksa slot...';
                    statusBadge.className = 'inline-block mt-0.5 text-[9px] font-bold text-amber-700 bg-amber-100/80 px-1.5 py-0.5 rounded';
                }
                fetch(`{{ route('jadwal.slot-teknisi') }}?id_teknisi=${encodeURIComponent(teknisiId)}&id_pemesanan={{ $pemesanan->id_pemesanan }}`)
                    .then(res => res.json())
                    .then(data => {
                        const resJadwal = data.jadwal || {};
                        semuaJadwalSibukTeknisi[teknisiId] = resJadwal;
                        applySlots(resJadwal);
                    })
                    .catch(err => {
                        console.error('Gagal mengambil jadwal sibuk teknisi:', err);
                        applySlots({});
                    });
            }
        }

        if (adminTimePickerTrigger && adminTimePickerPopover) {
            adminTimePickerTrigger.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                const isHidden = adminTimePickerPopover.classList.contains('hidden');
                document.querySelectorAll('.picker-popover').forEach(p => p.classList.add('hidden'));
                if (isHidden) {
                    adminTimePickerPopover.classList.remove('hidden');
                    syncAdminVerificationSlots();
                    setTimeout(scrollAdminToActive, 60);
                } else {
                    adminTimePickerPopover.classList.add('hidden');
                }
            });

            document.addEventListener('click', function (e) {
                if (adminTimePickerContainer && !adminTimePickerContainer.contains(e.target)) {
                    adminTimePickerPopover.classList.add('hidden');
                }
            });

            if (adminBtnConfirmTime) {
                adminBtnConfirmTime.addEventListener('click', function (e) {
                    e.preventDefault();
                    adminTimePickerPopover.classList.add('hidden');
                });
            }

            adminTimePickerPopover.querySelectorAll('.admin-hour-opt').forEach(function (btn) {
                btn.addEventListener('click', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    if (this.disabled) return;
                    adminTimePickerPopover.querySelectorAll('.admin-hour-opt').forEach(function (b) {
                        if (!b.disabled) {
                            b.classList.remove('bg-emerald-600', 'text-white', 'shadow-sm');
                            b.classList.add('text-gray-700', 'hover:bg-gray-200/80');
                        }
                    });
                    this.classList.add('bg-emerald-600', 'text-white', 'shadow-sm');
                    this.classList.remove('text-gray-700', 'hover:bg-gray-200/80');
                    currentHour = this.getAttribute('data-hour');
                    updateAdminTimeDisplay();
                });
            });

            adminTimePickerPopover.querySelectorAll('.admin-minute-opt').forEach(function (btn) {
                btn.addEventListener('click', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    adminTimePickerPopover.querySelectorAll('.admin-minute-opt').forEach(function (b) {
                        b.classList.remove('bg-emerald-600', 'text-white', 'shadow-sm');
                        b.classList.add('text-gray-700', 'hover:bg-gray-200/80');
                    });
                    this.classList.add('bg-emerald-600', 'text-white', 'shadow-sm');
                    this.classList.remove('text-gray-700', 'hover:bg-gray-200/80');
                    currentMinute = this.getAttribute('data-minute');
                    updateAdminTimeDisplay();
                });
            });

            updateAdminTimeDisplay();
        }

        // --- Custom Teknisi Picker ---
        if (adminTeknisiTrigger && adminTeknisiPopover) {
            adminTeknisiTrigger.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                const isHidden = adminTeknisiPopover.classList.contains('hidden');
                document.querySelectorAll('.picker-popover').forEach(p => {
                    if (p !== adminTeknisiPopover) p.classList.add('hidden');
                });
                if (typeof adminTimePickerPopover !== 'undefined' && adminTimePickerPopover) {
                    adminTimePickerPopover.classList.add('hidden');
                }
                if (isHidden) {
                    adminTeknisiPopover.classList.remove('hidden');
                    const selectedItem = adminTeknisiPopover.querySelector('.teknisi-option-item.border-emerald-500');
                    if (selectedItem) {
                        selectedItem.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
                    }
                } else {
                    adminTeknisiPopover.classList.add('hidden');
                }
            });

            adminTeknisiPopover.querySelectorAll('.teknisi-option-item').forEach(function (item) {
                item.addEventListener('click', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    const teknisiId = this.getAttribute('data-teknisi-id');
                    const nama = this.getAttribute('data-nama');
                    const sub = this.getAttribute('data-sub');

                    adminTeknisiPopover.querySelectorAll('.teknisi-option-item').forEach(function (el) {
                        el.classList.remove('border-emerald-500', 'bg-emerald-50/70', 'ring-1', 'ring-emerald-500');
                        el.classList.add('border-gray-100', 'bg-white');
                        const check = el.querySelector('.check-icon');
                        if (check) {
                            check.classList.remove('text-emerald-600');
                            check.classList.add('text-transparent');
                        }
                    });

                    this.classList.add('border-emerald-500', 'bg-emerald-50/70', 'ring-1', 'ring-emerald-500');
                    this.classList.remove('border-gray-100', 'bg-white');
                    const myCheck = this.querySelector('.check-icon');
                    if (myCheck) {
                        myCheck.classList.remove('text-transparent');
                        myCheck.classList.add('text-emerald-600');
                    }

                    if (displayAdminTeknisiName) displayAdminTeknisiName.textContent = nama;
                    if (displayAdminTeknisiSub) displayAdminTeknisiSub.textContent = sub;

                    if (adminHiddenTeknisi) {
                        adminHiddenTeknisi.value = teknisiId;
                        adminHiddenTeknisi.dispatchEvent(new Event('change'));
                    }

                    if (adminTeknisiTrigger) {
                        adminTeknisiTrigger.classList.remove('border-rose-500', 'ring-1', 'ring-rose-500');
                    }

                    adminTeknisiPopover.classList.add('hidden');
                    syncAdminVerificationSlots();
                });
            });

            // Listener jika teknisi atau tanggal survei berubah
            if (adminHiddenTeknisi) {
                adminHiddenTeknisi.addEventListener('change', syncAdminVerificationSlots);
            }
            if (adminHiddenTgl) {
                adminHiddenTgl.addEventListener('change', syncAdminVerificationSlots);
            }

            // Validasi client-side saat submit
            if (adminTeknisiContainer) {
                const parentForm = adminTeknisiContainer.closest('form');
                if (parentForm) {
                    parentForm.addEventListener('submit', function (e) {
                        if (!adminHiddenTeknisi || !adminHiddenTeknisi.value) {
                            e.preventDefault();
                            adminTeknisiTrigger.classList.add('border-rose-500', 'ring-1', 'ring-rose-500');
                            adminTeknisiPopover.classList.remove('hidden');
                            adminTeknisiTrigger.focus();
                            return;
                        }

                        const tekId = adminHiddenTeknisi.value;
                        const tglVal = adminHiddenTgl ? adminHiddenTgl.value : null;
                        if (tekId && tglVal) {
                            const dayData = (semuaJadwalSibukTeknisi[tekId] && semuaJadwalSibukTeknisi[tekId][tglVal]) || { jam: [] };
                            const busyHours = dayData.jam || [];
                            if (busyHours.includes(currentHour)) {
                                e.preventDefault();
                                alert(`Jadwal bentrok: Jam ${currentHour}:00 WITA sudah penuh untuk teknisi terpilih pada tanggal ini. Silakan pilih jam atau teknisi lain.`);
                                if (adminTimePickerPopover) adminTimePickerPopover.classList.remove('hidden');
                            }
                        }
                    });
                }
            }

            // Inisialisasi awal slot ketersediaan teknisi saat load
            syncAdminVerificationSlots();
        }
    });

    function previewImage(input, containerId) {
        const container = document.getElementById(containerId);
        if (!container) return;

        container.innerHTML = '';
        if (input.files && input.files[0]) {
            const file = input.files[0];
            if (file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    img.className = 'w-full h-auto object-cover max-h-48 rounded-xl';
                    container.appendChild(img);
                    container.classList.remove('hidden');
                };
                reader.readAsDataURL(file);
            } else {
                container.classList.add('hidden');
            }
        } else {
            container.classList.add('hidden');
        }
    }

    window.getTodayFormatted = function () {
        const now = new Date();
        const y = now.getFullYear();
        const m = String(now.getMonth() + 1).padStart(2, '0');
        const d = String(now.getDate()).padStart(2, '0');
        const ymd = `${y}-${m}-${d}`;

        const indoMonths = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
        const indoDays = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
        const text = `${indoDays[now.getDay()]}, ${now.getDate()} ${indoMonths[now.getMonth()]} ${y}`;

        return { ymd, text };
    };

    window.rescheduleTreatmentToToday = function () {
        const today = window.getTodayFormatted();
        window.syncModalTglKerja(today.ymd, today.text);
    };

    window.rescheduleSurveiToToday = function () {
        const today = window.getTodayFormatted();
        window.syncModalTglSurvei(today.ymd, today.text);
    };

    window.syncModalTglKerja = function (ymd, text) {
        const modal = document.getElementById('modal-ubah-jadwal');
        if (modal) {
            modal.classList.remove('hidden');
        }
        const input = document.getElementById('input_modal_tgl_pengerjaan');
        const display = document.getElementById('display_modal_tgl_pengerjaan');
        if (input) {
            input.value = ymd;
            input.dispatchEvent(new Event('change'));
        }
        if (display) display.textContent = text;
        const container = document.getElementById('container_modal_tgl_pengerjaan');
        if (container) {
            container.scrollIntoView({ behavior: 'smooth', block: 'center' });
            container.classList.add('ring-2', 'ring-emerald-500', 'ring-offset-2', 'rounded-xl', 'transition-all');
            setTimeout(() => {
                container.classList.remove('ring-2', 'ring-emerald-500', 'ring-offset-2');
            }, 1800);
        }
    };

    window.syncModalTglSurvei = function (ymd, text) {
        const modal = document.getElementById('modal-ubah-jadwal');
        if (modal) {
            modal.classList.remove('hidden');
        }
        const input = document.getElementById('input_modal_tgl_survei');
        const display = document.getElementById('display_modal_tgl_survei');
        if (input) {
            input.value = ymd;
            input.dispatchEvent(new Event('change'));
        }
        if (display) display.textContent = text;
        const container = document.getElementById('container_modal_tgl_survei');
        if (container) {
            container.scrollIntoView({ behavior: 'smooth', block: 'center' });
            container.classList.add('ring-2', 'ring-emerald-500', 'ring-offset-2', 'rounded-xl', 'transition-all');
            setTimeout(() => {
                container.classList.remove('ring-2', 'ring-emerald-500', 'ring-offset-2');
            }, 1800);
        }
    };
</script>
@endsection
