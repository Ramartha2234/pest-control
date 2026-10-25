@extends('layouts.app')

@section('title', 'Cek Status Pemesanan - CV. Ambius Indonesia')
@section('meta_description', 'Pantau progres pengerjaan pest control dan status pembayaran Anda secara real-time tanpa registrasi dengan memasukkan kode unik pemesanan.')

@section('content')
<div class="py-12 bg-slate-50 min-h-[75vh]">
    <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
        {{-- Search Header --}}
        <div class="text-center mb-8 space-y-2">
            <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">Pantau Status Pemesanan Pest Control</h1>
            <p class="text-xs sm:text-sm text-slate-500 max-w-md mx-auto">
                Cukup masukkan <b>Kode Pemesanan</b> unik Anda (format AMB-XXXXXX) tanpa perlu login untuk memantau status pengerjaan & pembayaran secara langsung.
            </p>
        </div>

        {{-- Search Box --}}
        <div class="max-w-xl mx-auto mb-8">
            <form action="{{ route('status.show') }}" method="POST" class="relative">
                @csrf
                <div class="relative">
                    <input type="text" name="kode_unik" id="kode_unik" value="{{ $code ?? '' }}" required
                           class="w-full rounded-2xl border-slate-300 bg-white pl-4 pr-32 py-3.5 text-xs sm:text-sm font-mono uppercase tracking-wider text-slate-900 shadow-md focus:border-emerald-500 focus:ring-emerald-500"
                           placeholder="Contoh: AMB-XXXXXX">
                    <button type="submit" class="absolute right-2 top-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 px-5 py-2 text-xs font-bold text-white shadow-sm transition">
                        Cek Status
                    </button>
                </div>
            </form>
        </div>

        {{-- Flash / Error with WhatsApp CTA Button --}}
        @if (session('error'))
            <div class="max-w-xl mx-auto mb-8 rounded-2xl border border-rose-200 bg-rose-50 p-6 text-center shadow-sm space-y-3">
                <span class="text-3xl block">🔍</span>
                <h4 class="text-sm font-bold text-rose-900">Kode Tidak Ditemukan</h4>
                <p class="text-xs text-rose-700 leading-relaxed max-w-sm mx-auto">
                    {{ session('error') }}
                </p>
                <div class="pt-2">
                    <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', config('pestcontrol.whatsapp_number')) }}?text=Halo%20Admin%20CV.%20Ambius,%20kode%20pemesanan%20saya%20tidak%20ditemukan.%20Mohon%20bantuannya."
                       target="_blank" rel="noopener"
                       class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 px-5 py-2.5 text-xs font-bold text-white shadow transition">
                        <span>💬</span> Hubungi Admin via WhatsApp
                    </a>
                </div>
            </div>
        @endif

        {{-- Tampilan Hasil Pelacakan Pesanan --}}
        @if ($pemesanan)
            @php
                $wo = $pemesanan->latestWorkOrder();
                $buktiPengerjaan = $pemesanan->latestBuktiPengerjaan();
                $buktiSurvei = $pemesanan->latestBuktiSurvei();
                $hasilSurvei = $pemesanan->latestHasilSurvei();
                $buktiPembayaran = $pemesanan->latestBuktiPembayaran();
                $kodePemesanan = $pemesanan->kode_pemesanan ?? $pemesanan->kode_unik;

                $stVal = $pemesanan->status_pengerjaan;
                $stColor = match($stVal) {
                    'Pending' => 'bg-amber-100 text-amber-800 border-amber-200',
                    'On-Going' => 'bg-sky-100 text-sky-800 border-sky-200',
                    'Done' => 'bg-emerald-100 text-emerald-800 border-emerald-200',
                    'Cancel' => 'bg-rose-100 text-rose-800 border-rose-200',
                    default => 'bg-slate-100 text-slate-800 border-slate-200'
                };

                $pbVal = $pemesanan->status_pembayaran;
                $pbColor = match($pbVal) {
                    'Lunas' => 'bg-emerald-100 text-emerald-800 border-emerald-200',
                    'Ditolak' => 'bg-rose-100 text-rose-800 border-rose-200',
                    default => 'bg-amber-100 text-amber-800 border-amber-200'
                };

                // Data Kontak Pemesan (ditampilkan lengkap tanpa masking)
                $customerHp = $pemesanan->pelanggan?->no_hp ?? $pemesanan->legacy_no_telp_client ?? '-';
                $customerEmail = $pemesanan->pelanggan?->email ?? '-';
                $maskedHp = $customerHp;
                $maskedEmail = $customerEmail;
            @endphp

            <div class="space-y-6">
                {{-- Live Auto-Refresh Indicator Bar --}}
                <div class="flex flex-wrap items-center justify-between gap-3 bg-white px-5 py-3 rounded-2xl border border-slate-200 shadow-sm text-xs">
                    <div class="flex items-center gap-2 text-slate-600">
                        <span class="relative flex h-2.5 w-2.5">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-emerald-500"></span>
                        </span>
                        <span>Auto-refresh real-time dalam <b id="countdownTimer" class="text-emerald-700 font-mono">30</b> detik</span>
                    </div>
                    <button type="button" onclick="window.location.reload()"
                            class="inline-flex items-center justify-center rounded-xl bg-emerald-50 hover:bg-emerald-100 border border-emerald-200 px-3.5 py-1.5 text-xs font-bold text-emerald-800 shadow-sm transition active:scale-95 cursor-pointer">
                        Refresh Sekarang
                    </button>
                </div>

                {{-- Status Alert Box: Cancel --}}
                @if ($stVal === 'Cancel')
                    <div class="rounded-2xl border border-rose-200 bg-rose-50 p-5 text-xs text-rose-900 shadow-sm space-y-2">
                        <h4 class="font-bold text-sm text-rose-950">Pemesanan Dibatalkan</h4>
                        <p class="text-rose-800">Dibatalkan oleh: <b>{{ $pemesanan->dibatalkan_oleh ?: 'Sistem' }}</b></p>
                        @if ($pemesanan->alasan_pembatalan)
                            <p class="text-rose-800">Alasan: <span class="italic font-medium">{{ $pemesanan->alasan_pembatalan }}</span></p>
                        @endif
                        <div class="pt-1">
                            <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', config('pestcontrol.whatsapp_number')) }}?text=Halo%20Admin%20CV.%20Ambius,%20saya%20ingin%20bertanya%20mengenai%20pembatalan%20pesanan%20Kode:%20{{ $kodePemesanan }}"
                               target="_blank" rel="noopener" class="font-bold text-emerald-700 underline">
                                Hubungi Admin jika Anda ingin mengajukan ulang atau koordinasi &rarr;
                            </a>
                        </div>
                    </div>
                @elseif ($stVal === 'Pending' && ! $pemesanan->tgl_verifikasi)
                    <div class="rounded-2xl border border-amber-200 bg-amber-50 p-5 text-xs text-amber-900 shadow-sm space-y-2">
                        <h4 class="font-bold text-sm text-amber-950">Menunggu Verifikasi & Konfirmasi Admin</h4>
                        <p class="text-amber-800 leading-relaxed">
                            Pesanan Anda telah tercatat dan sedang menunggu verifikasi serta penetapan teknisi survei lapangan.
                        </p>
                        <p class="font-semibold text-amber-950">
                            Batas waktu konfirmasi: {{ $pemesanan->batas_konfirmasi?->translatedFormat('d F Y H:i') }} WITA (Pesanan akan dibatalkan otomatis jika tidak dikonfirmasi dalam 3 hari).
                        </p>
                        <div class="pt-1">
                            <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', config('pestcontrol.whatsapp_number')) }}?text=Halo%20Admin%20CV.%20Ambius,%20saya%20ingin%20konfirmasi%20pemesanan%20dengan%20Kode:%20{{ $kodePemesanan }}"
                               target="_blank" rel="noopener"
                               class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-600 px-4 py-2 font-bold text-white shadow-sm hover:bg-emerald-700 transition">
                                Konfirmasi via WhatsApp Sekarang
                            </a>
                        </div>
                    </div>
                @endif

                {{-- Alert Khusus: Nego Harga / Pengajuan Lapangan Ditolak Admin --}}
                @if ($hasilSurvei && $hasilSurvei->status_verifikasi === 'Ditolak')
                    <div class="rounded-2xl border border-amber-300 bg-amber-50 p-5 text-xs text-amber-900 shadow-sm space-y-2">
                        <h4 class="font-bold text-sm text-amber-950">Pengajuan / Nego Harga Survei Ditolak Admin</h4>
                        <p class="text-amber-800">Pengajuan estimasi harga dari hasil survei ditolak oleh admin dan sedang dalam peninjauan ulang.</p>
                        <p class="font-medium text-amber-900">
                            <b>Alasan Penolakan:</b> <span class="italic">{{ $hasilSurvei->alasan_penolakan ?: 'Nominal atau jadwal yang diajukan belum sesuai standar.' }}</span>
                        </p>
                        <div class="pt-1">
                            <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', config('pestcontrol.whatsapp_number')) }}?text=Halo%20Admin%20CV.%20Ambius,%20saya%20ingin%20bertanya%20mengenai%20penolakan%20harga%20survei%20untuk%20Kode:%20{{ $kodePemesanan }}"
                               target="_blank" rel="noopener"
                               class="inline-flex items-center gap-1.5 rounded-lg bg-amber-700 px-3.5 py-1.5 font-bold text-white text-[11px] hover:bg-amber-800 transition">
                                Diskusikan Nego Harga via WhatsApp
                            </a>
                        </div>
                    </div>
                @endif

                {{-- Alert Khusus: Bukti Pembayaran Ditolak Admin --}}
                @if ($pbVal === 'Ditolak' || ($buktiPembayaran && $buktiPembayaran->status_verifikasi === 'Ditolak'))
                    <div class="rounded-2xl border border-rose-300 bg-rose-50 p-5 text-xs text-rose-900 shadow-sm space-y-2">
                        <h4 class="font-bold text-sm text-rose-950">Bukti Pembayaran Ditolak Admin</h4>
                        <p class="text-rose-800">Verifikasi bukti pembayaran Anda tidak dapat disetujui.</p>
                        <p class="font-medium text-rose-900">
                            <b>Alasan Penolakan:</b> <span class="italic">{{ $buktiPembayaran?->alasan_penolakan ?: 'Bukti transfer tidak valid atau nominal tidak sesuai tagihan.' }}</span>
                        </p>
                        <div class="pt-1">
                            <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', config('pestcontrol.whatsapp_number')) }}?text=Halo%20Admin%20CV.%20Ambius,%20bukti%20pembayaran%20saya%20ditolak%20untuk%20Kode:%20{{ $kodePemesanan }}.%20Bagaimana%20cara%20konfirmasi%20ulang?"
                               target="_blank" rel="noopener"
                               class="inline-flex items-center gap-1.5 rounded-lg bg-rose-700 px-3.5 py-1.5 font-bold text-white text-[11px] hover:bg-rose-800 transition">
                                Konfirmasi Pembayaran via WhatsApp
                            </a>
                        </div>
                    </div>
                @endif

                {{-- Stepper Status Pengerjaan --}}
                <div class="rounded-3xl border border-slate-200 bg-white p-6 sm:p-8 shadow-sm">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-4 mb-6">
                        <div>
                            <span class="text-[10px] uppercase tracking-wider text-slate-400 font-bold">Progres Pengerjaan</span>
                            <h3 class="text-base font-extrabold text-slate-900">Alur Treatment Pest Control</h3>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="rounded-full border px-3 py-1 text-xs font-bold {{ $stColor }}">
                                {{ $stVal }}
                            </span>
                            <span class="rounded-full border px-3 py-1 text-xs font-bold {{ $pbColor }}">
                                {{ $pbVal }}
                            </span>
                        </div>
                    </div>

                    {{-- Stepper Visual Progress --}}
                    @php
                        $step1Done = in_array($stVal, ['Pending', 'On-Going', 'Done']);
                        $step2Done = in_array($stVal, ['On-Going', 'Done']);
                        $step3Done = ($stVal === 'Done');
                    @endphp
                    <div class="grid grid-cols-3 gap-2 text-center text-xs">
                        <div class="p-3 rounded-2xl border {{ $step1Done ? 'bg-emerald-50 border-emerald-300 text-emerald-900' : 'bg-slate-50 border-slate-200 text-slate-400' }}">
                            <div class="h-8 w-8 mx-auto rounded-full flex items-center justify-center font-bold text-xs {{ $step1Done ? 'bg-emerald-600 text-white' : 'bg-slate-200 text-slate-500' }}">
                                1
                            </div>
                            <span class="block font-bold mt-2 text-[11px]">Survei Lokasi</span>
                            <span class="block text-[10px] text-slate-500 mt-0.5">Inspeksi & Deal</span>
                        </div>
                        <div class="p-3 rounded-2xl border {{ $step2Done ? 'bg-sky-50 border-sky-300 text-sky-900' : 'bg-slate-50 border-slate-200 text-slate-400' }}">
                            <div class="h-8 w-8 mx-auto rounded-full flex items-center justify-center font-bold text-xs {{ $step2Done ? 'bg-sky-600 text-white' : 'bg-slate-200 text-slate-500' }}">
                                2
                            </div>
                            <span class="block font-bold mt-2 text-[11px]">Treatment</span>
                            <span class="block text-[10px] text-slate-500 mt-0.5">Pengerjaan Aktif</span>
                        </div>
                        <div class="p-3 rounded-2xl border {{ $step3Done ? 'bg-emerald-50 border-emerald-300 text-emerald-900' : 'bg-slate-50 border-slate-200 text-slate-400' }}">
                            <div class="h-8 w-8 mx-auto rounded-full flex items-center justify-center font-bold text-xs {{ $step3Done ? 'bg-emerald-600 text-white' : 'bg-slate-200 text-slate-500' }}">
                                3
                            </div>
                            <span class="block font-bold mt-2 text-[11px]">Selesai & Lunas</span>
                            <span class="block text-[10px] text-slate-500 mt-0.5">Dokumen & Pelunasan</span>
                        </div>
                    </div>
                </div>

                {{-- Detail Informasi Pesanan & Jadwal --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    {{-- Box Kiri: Layanan, Kontak, & Jadwal --}}
                    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm space-y-4 text-xs">
                        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                            <h4 class="font-extrabold text-sm text-slate-900">Informasi Pelayanan & Jadwal</h4>
                            <span class="rounded-full bg-slate-100 px-2.5 py-0.5 text-[11px] font-semibold text-slate-600">Detail Pesanan</span>
                        </div>

                        <div class="divide-y divide-slate-100">
                            <div class="flex items-center justify-between py-2.5">
                                <span class="text-slate-500 font-medium">Nama Pemesan</span>
                                <span class="font-bold text-slate-900 text-right">{{ $pemesanan->pelanggan?->nama ?? $pemesanan->nama_client }}</span>
                            </div>
                            <div class="flex items-center justify-between py-2.5">
                                <span class="text-slate-500 font-medium">Nomor WhatsApp</span>
                                <span class="font-mono font-bold text-slate-900 text-right">{{ $customerHp }}</span>
                            </div>
                            @if ($customerEmail && $customerEmail !== '-')
                                <div class="flex items-center justify-between py-2.5">
                                    <span class="text-slate-500 font-medium">Email</span>
                                    <span class="font-mono font-medium text-slate-800 text-right">{{ $customerEmail }}</span>
                                </div>
                            @endif
                            <div class="flex items-center justify-between py-2.5">
                                <span class="text-slate-500 font-medium">Jenis Layanan</span>
                                <span class="font-bold text-slate-900 text-right">{{ $pemesanan->tarifLayanan->jenis_layanan ?? '-' }}</span>
                            </div>
                            <div class="flex items-center justify-between py-2.5">
                                <span class="text-slate-500 font-medium">Ukuran Area</span>
                                <span class="font-semibold text-slate-800 text-right">
                                    {{ $pemesanan->ukuran_area ? $pemesanan->ukuran_area . ' ' . ($pemesanan->tarifLayanan->satuan ?? 'm2') : 'Sesuai Hasil Survei' }}
                                </span>
                            </div>
                            <div class="flex items-center justify-between py-2.5">
                                <span class="text-slate-500 font-medium">Jenis Properti</span>
                                <span class="font-semibold text-slate-800 text-right">{{ $pemesanan->jenis_properti }}</span>
                            </div>
                            <div class="flex items-center justify-between py-2.5">
                                <span class="text-slate-500 font-medium">Tipe Area</span>
                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-[11px] font-bold {{ ($pemesanan->tipe_area ?? 'Indoor') === 'Outdoor' ? 'bg-amber-50 text-amber-700 border border-amber-200' : 'bg-emerald-50 text-emerald-700 border border-emerald-200' }}">
                                    {{ ($pemesanan->tipe_area ?? 'Indoor') === 'Outdoor' ? 'Outdoor (+Rp 100.000)' : 'Indoor' }}
                                </span>
                            </div>
                            <div class="flex items-center justify-between py-2.5">
                                <span class="text-slate-500 font-medium">Teknisi Ditugaskan</span>
                                @if ($pemesanan->jadwalPenugasan?->teknisi?->nama)
                                    <span class="font-bold text-emerald-700 text-right">
                                        {{ $pemesanan->jadwalPenugasan->teknisi->nama }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center rounded-md bg-slate-100 px-2 py-0.5 text-[11px] font-medium text-slate-500">
                                        Belum Ditugaskan
                                    </span>
                                @endif
                            </div>
                            <div class="flex items-center justify-between py-2.5">
                                <span class="text-slate-500 font-medium">Jadwal Survei</span>
                                <span class="font-bold text-slate-900 text-right">
                                    @if ($pemesanan->jadwalPenugasan?->tgl_survei)
                                        {{ \Carbon\Carbon::parse($pemesanan->jadwalPenugasan->tgl_survei)->format('d M Y') }}
                                        @if ($pemesanan->jadwalPenugasan?->jam_survei)
                                            <span class="font-medium text-slate-500">({{ \Carbon\Carbon::parse($pemesanan->jadwalPenugasan->jam_survei)->format('H:i') }} WITA)</span>
                                        @endif
                                    @else
                                        <span class="italic text-slate-400 font-normal">Menunggu Verifikasi</span>
                                    @endif
                                </span>
                            </div>
                            <div class="flex items-center justify-between py-2.5">
                                <span class="text-slate-500 font-medium">Jadwal Pengerjaan</span>
                                <span class="font-bold text-slate-900 text-right">
                                    @if ($pemesanan->jadwalPenugasan?->tgl_pengerjaan)
                                        {{ \Carbon\Carbon::parse($pemesanan->jadwalPenugasan->tgl_pengerjaan)->format('d M Y') }}
                                        @if ($pemesanan->jadwalPenugasan?->jam_pengerjaan)
                                            <span class="font-medium text-slate-500">({{ \Carbon\Carbon::parse($pemesanan->jadwalPenugasan->jam_pengerjaan)->format('H:i') }} WITA)</span>
                                        @endif
                                    @else
                                        <span class="italic text-slate-400 font-normal">Menunggu Survei</span>
                                    @endif
                                </span>
                            </div>
                        </div>
                    </div>

                    {{-- Box Kanan: Biaya & Verifikasi --}}
                    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm space-y-4 text-xs">
                        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                            <h4 class="font-extrabold text-sm text-slate-900">Harga & Verifikasi Tagihan</h4>
                            <span class="rounded-full bg-slate-100 px-2.5 py-0.5 text-[11px] font-semibold text-slate-600">Keuangan</span>
                        </div>

                        <div class="space-y-3">
                            <div class="flex items-center justify-between py-1">
                                <span class="text-slate-500 font-medium">Harga Estimasi Awal</span>
                                <span class="font-bold text-slate-900">Rp {{ number_format($pemesanan->harga_estimasi, 0, ',', '.') }}</span>
                            </div>

                            {{-- Harga Pengerjaan / Pengajuan Survei + Status Verifikasi --}}
                            @if ($hasilSurvei)
                                <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200 space-y-2">
                                    <div class="flex items-center justify-between">
                                        <span class="font-semibold text-slate-700">Harga Pengerjaan (Survei)</span>
                                        @php
                                            $hvStatus = $hasilSurvei->status_verifikasi;
                                            $hvColor = match($hvStatus) {
                                                'Disetujui' => 'bg-emerald-100 text-emerald-800 border-emerald-200',
                                                'Ditolak' => 'bg-rose-100 text-rose-800 border-rose-200',
                                                default => 'bg-amber-100 text-amber-800 border-amber-200'
                                            };
                                        @endphp
                                        <span class="rounded-full border px-2.5 py-0.5 text-[10px] font-bold {{ $hvColor }}">
                                            {{ $hvStatus }}
                                        </span>
                                    </div>
                                    <div class="text-base font-extrabold text-slate-900">
                                        Rp {{ number_format($hasilSurvei->harga_diajukan, 0, ',', '.') }}
                                    </div>
                                </div>
                            @endif

                            @if ($pemesanan->harga_final)
                                <div class="flex items-center justify-between pt-2 border-t border-slate-100">
                                    <div>
                                        <span class="text-slate-700 font-bold block">Harga Final</span>
                                        <span class="text-[10px] text-emerald-600 font-semibold block">Harga telah diperbarui</span>
                                    </div>
                                    <span class="text-base font-black text-emerald-700">
                                        Rp {{ number_format($pemesanan->harga_final, 0, ',', '.') }}
                                    </span>
                                </div>
                            @endif

                            <div class="flex items-center justify-between pt-2 border-t border-slate-100">
                                <span class="text-slate-500 font-medium">Status Pembayaran</span>
                                <span class="rounded-full border px-2.5 py-0.5 text-[11px] font-bold {{ $pbColor }}">
                                    {{ $pbVal }}
                                </span>
                            </div>
                        </div>

                        {{-- Dokumen Unduh Resmi (HANYA status Disetujui via route controller) --}}
                        <div class="pt-4 border-t border-slate-100 space-y-2.5">
                            <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider">Unduh Dokumen Resmi</span>

                            {{-- 1. Work Order --}}
                            @if ($wo && $wo->status_verifikasi === 'Disetujui')
                                <a href="{{ route('dokumen.wo', $wo->id_wo) }}?kode={{ $kodePemesanan }}" target="_blank"
                                   class="group w-full flex items-center justify-between p-2.5 rounded-xl bg-emerald-50 hover:bg-emerald-100 text-emerald-900 border border-emerald-200 transition text-xs">
                                    <span class="font-bold text-emerald-900">Work Order Resmi (WO)</span>
                                    <span class="inline-flex items-center justify-center rounded-lg bg-white border border-emerald-300 px-3 py-1 text-[11px] font-bold text-emerald-700 shadow-2xs group-hover:bg-emerald-700 group-hover:text-white group-hover:border-emerald-700 transition">
                                        Unduh
                                    </span>
                                </a>
                            @else
                                <div class="w-full flex items-center justify-between p-2.5 rounded-xl bg-slate-50 border border-slate-200 text-slate-400 text-xs">
                                    <span class="font-medium text-slate-500">Work Order (WO)</span>
                                    <span class="text-[11px] font-semibold text-slate-400 italic">Belum Disetujui</span>
                                </div>
                            @endif

                            {{-- 2. Bukti Pembayaran --}}
                            @if ($buktiPembayaran && $buktiPembayaran->status_verifikasi === 'Disetujui')
                                <a href="{{ route('dokumen.pembayaran', $buktiPembayaran->id_bukti_pembayaran) }}?kode={{ $kodePemesanan }}" target="_blank"
                                   class="group w-full flex items-center justify-between p-2.5 rounded-xl bg-emerald-50 hover:bg-emerald-100 text-emerald-900 border border-emerald-200 transition text-xs">
                                    <span class="font-bold text-emerald-900">Bukti Pembayaran Resmi</span>
                                    <span class="inline-flex items-center justify-center rounded-lg bg-white border border-emerald-300 px-3 py-1 text-[11px] font-bold text-emerald-700 shadow-2xs group-hover:bg-emerald-700 group-hover:text-white group-hover:border-emerald-700 transition">
                                        Unduh
                                    </span>
                                </a>
                            @else
                                <div class="w-full flex items-center justify-between p-2.5 rounded-xl bg-slate-50 border border-slate-200 text-slate-400 text-xs">
                                    <span class="font-medium text-slate-500">Bukti Pembayaran</span>
                                    <span class="text-[11px] font-semibold text-slate-400 italic">Belum Disetujui</span>
                                </div>
                            @endif

                            {{-- 3. Foto Kehadiran Survei --}}
                            @if ($buktiSurvei && $buktiSurvei->status_verifikasi === 'Disetujui')
                                <a href="{{ route('dokumen.kehadiran', $buktiSurvei->id_bukti_kehadiran) }}?kode={{ $kodePemesanan }}" target="_blank"
                                   class="group w-full flex items-center justify-between p-2.5 rounded-xl bg-emerald-50 hover:bg-emerald-100 text-emerald-900 border border-emerald-200 transition text-xs">
                                    <span class="font-bold text-emerald-900">Foto Kehadiran Survei</span>
                                    <span class="inline-flex items-center justify-center rounded-lg bg-white border border-emerald-300 px-3 py-1 text-[11px] font-bold text-emerald-700 shadow-2xs group-hover:bg-emerald-700 group-hover:text-white group-hover:border-emerald-700 transition">
                                        Unduh
                                    </span>
                                </a>
                            @else
                                <div class="w-full flex items-center justify-between p-2.5 rounded-xl bg-slate-50 border border-slate-200 text-slate-400 text-xs">
                                    <span class="font-medium text-slate-500">Foto Kehadiran Survei</span>
                                    <span class="text-[11px] font-semibold text-slate-400 italic">Belum Disetujui</span>
                                </div>
                            @endif

                            {{-- 4. Foto Kehadiran Pengerjaan --}}
                            @if ($buktiPengerjaan && $buktiPengerjaan->status_verifikasi === 'Disetujui')
                                <a href="{{ route('dokumen.kehadiran', $buktiPengerjaan->id_bukti_kehadiran) }}?kode={{ $kodePemesanan }}" target="_blank"
                                   class="group w-full flex items-center justify-between p-2.5 rounded-xl bg-emerald-50 hover:bg-emerald-100 text-emerald-900 border border-emerald-200 transition text-xs">
                                    <span class="font-bold text-emerald-900">Foto Kehadiran Pengerjaan</span>
                                    <span class="inline-flex items-center justify-center rounded-lg bg-white border border-emerald-300 px-3 py-1 text-[11px] font-bold text-emerald-700 shadow-2xs group-hover:bg-emerald-700 group-hover:text-white group-hover:border-emerald-700 transition">
                                        Unduh
                                    </span>
                                </a>
                            @else
                                <div class="w-full flex items-center justify-between p-2.5 rounded-xl bg-slate-50 border border-slate-200 text-slate-400 text-xs">
                                    <span class="font-medium text-slate-500">Foto Kehadiran Pengerjaan</span>
                                    <span class="text-[11px] font-semibold text-slate-400 italic">Belum Disetujui</span>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- WhatsApp Support & Ubah Jadwal Bar (Selalu Tersedia) --}}
                <div class="rounded-2xl bg-gradient-to-r from-emerald-800 to-teal-800 p-5 text-white flex flex-col sm:flex-row items-center justify-between gap-4 shadow-md">
                    <div>
                        <h4 class="font-bold text-sm">Butuh Bantuan atau Ingin Ubah Jadwal?</h4>
                        <p class="text-xs text-emerald-100 mt-0.5">Admin CV. Ambius siap membantu penyesuaian jadwal survei/pengerjaan Anda kapan saja via WhatsApp.</p>
                    </div>
                    <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', config('pestcontrol.whatsapp_number')) }}?text=Halo%20Admin%20CV.%20Ambius,%20saya%20ingin%20mengajukan%20perubahan%20jadwal/pertanyaan%20untuk%20Pesanan%20Kode:%20{{ $kodePemesanan }}"
                       target="_blank" rel="noopener"
                       class="rounded-xl bg-white px-5 py-2.5 text-xs font-extrabold text-emerald-900 shadow hover:bg-emerald-50 transition whitespace-nowrap">
                        Hubungi WhatsApp Admin
                    </a>
                </div>
            </div>
        @endif
    </div>
</div>

@push('scripts')
@if ($pemesanan)
<script>
    // Auto-refresh data tiap 30 detik (polling ringan)
    document.addEventListener('DOMContentLoaded', function () {
        let timeLeft = 30;
        const countdownEl = document.getElementById('countdownTimer');

        const interval = setInterval(function () {
            timeLeft--;
            if (countdownEl) {
                countdownEl.textContent = timeLeft;
            }
            if (timeLeft <= 0) {
                clearInterval(interval);
                window.location.reload();
            }
        }, 1000);
    });
</script>
@endif
@endpush
@endsection
