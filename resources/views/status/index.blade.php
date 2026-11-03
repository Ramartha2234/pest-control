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
                {{-- Flash Messages --}}
                @if (session('success'))
                    <div class="rounded-2xl bg-emerald-50 border border-emerald-300 p-4 text-xs font-bold text-emerald-900">
                        {{ session('success') }}
                    </div>
                @endif
                @if (session('warning'))
                    <div class="rounded-2xl bg-amber-50 border border-amber-300 p-4 text-xs font-bold text-amber-900">
                        {{ session('warning') }}
                    </div>
                @endif
                @if ($errors->any())
                    <div class="rounded-2xl bg-rose-50 border border-rose-300 p-4 text-xs font-bold text-rose-900">
                        <ul class="list-disc pl-4 space-y-1">
                            @foreach ($errors->all() as $err)
                                <li>{{ $err }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

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

                {{-- Alert Khusus Down Payment (DP 10%) untuk Pesanan >= 10 Juta --}}
                @if ($pemesanan->wajib_dp && $stVal !== 'Cancel')
                    @if ($pemesanan->status_dp === 'Lunas')
                        <div class="rounded-2xl border border-emerald-300 bg-gradient-to-br from-emerald-50 to-teal-50 p-5 sm:p-6 text-xs text-emerald-950 shadow-sm space-y-3">
                            <div class="flex flex-col items-center justify-center text-center gap-2 pb-2.5 border-b border-emerald-200/80">
                                <h4 class="font-extrabold text-sm sm:text-base text-emerald-950">
                                    Pembayaran Down Payment (DP 10%) Terverifikasi
                                </h4>
                                <span class="inline-flex items-center justify-center px-3.5 py-1 rounded-full bg-white text-emerald-800 text-xs font-bold border border-emerald-200 shadow-2xs tracking-wide">DP Lunas</span>
                            </div>
                            <p class="text-emerald-800 leading-relaxed text-center max-w-xl mx-auto">
                                Pembayaran DP sebesar <b>Rp {{ number_format($pemesanan->nominal_dp, 0, ',', '.') }}</b> telah diverifikasi dan dicocokkan dengan mutasi bank oleh admin pada <b>{{ $pemesanan->tgl_bayar_dp?->translatedFormat('d F Y H:i') ?? '-' }} WITA</b>.
                            </p>
                            @if ($pemesanan->bukti_dp)
                                <div class="pt-1 flex justify-center">
                                    <a href="{{ route('dokumen.dp', ['pemesanan' => $pemesanan->id_pemesanan, 'kode' => $kodePemesanan]) }}" target="_blank"
                                       class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-700 hover:bg-emerald-800 px-5 py-2.5 text-xs font-bold text-white shadow-sm transition active:scale-95">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                        Lihat / Unduh Bukti Transfer DP Resmi
                                    </a>
                                </div>
                            @endif
                        </div>
                    @elseif ($pemesanan->status_dp === 'Menunggu Verifikasi')
                        <div class="rounded-2xl border-2 border-blue-300 bg-gradient-to-br from-blue-50 via-sky-50 to-blue-100/70 p-5 sm:p-6 text-xs text-blue-950 shadow-sm space-y-4">
                            <div class="flex flex-col items-center justify-center text-center gap-2 pb-2.5 border-b border-blue-200/80">
                                <h4 class="font-extrabold text-sm sm:text-base text-blue-950">
                                    Bukti Transfer DP Sedang Diverifikasi Admin
                                </h4>
                                <span class="inline-flex items-center justify-center px-3.5 py-1 rounded-full bg-white text-blue-800 text-xs font-bold border border-blue-200 shadow-2xs tracking-wide">
                                    Menunggu Verifikasi
                                </span>
                            </div>
                            <p class="text-blue-800 leading-relaxed text-center max-w-xl mx-auto">
                                Bukti transfer DP sebesar <b>Rp {{ number_format($pemesanan->nominal_dp, 0, ',', '.') }}</b> yang Anda unggah sedang dalam proses pemeriksaan dan pencocokan mutasi bank oleh admin kami. Jadwal survei lokasi akan langsung dibuka setelah verifikasi disetujui.
                            </p>
                            @if ($pemesanan->bukti_dp)
                                <div class="pt-1 flex justify-center">
                                    <a href="{{ route('dokumen.dp', ['pemesanan' => $pemesanan->id_pemesanan, 'kode' => $kodePemesanan]) }}" target="_blank"
                                       class="inline-flex items-center gap-1.5 rounded-xl bg-blue-700 hover:bg-blue-800 px-5 py-2.5 text-xs font-bold text-white shadow-sm transition active:scale-95">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                        Lihat Bukti Transfer yang Anda Unggah
                                    </a>
                                </div>
                            @endif

                            <div class="max-w-md mx-auto pt-2">
                                <details class="text-[11px] text-slate-600 bg-white/80 p-3 rounded-xl border border-blue-200">
                                    <summary class="cursor-pointer font-bold text-blue-700 hover:underline">Unggah Ulang Bukti Transfer (Jika salah file)</summary>
                                    <form action="{{ route('pemesanan.upload-dp', $pemesanan->id_pemesanan) }}" method="POST" enctype="multipart/form-data" class="mt-3 space-y-2.5">
                                        @csrf
                                        <input type="hidden" name="kode_pemesanan" value="{{ $kodePemesanan }}">
                                        <input type="file" name="bukti_dp" accept="image/*" required
                                               class="block w-full text-xs text-slate-500 file:mr-3 file:py-2 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-blue-100 file:text-blue-800 hover:file:bg-blue-200 cursor-pointer">
                                        <button type="submit" class="px-3.5 py-1.5 rounded-lg bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs transition cursor-pointer">
                                            Kirim Ulang Bukti Transfer
                                        </button>
                                    </form>
                                </details>
                            </div>
                        </div>
                    @else
                        {{-- Ditolak atau Belum Dibayar --}}
                        <div class="rounded-2xl border-2 {{ $pemesanan->status_dp === 'Ditolak' ? 'border-rose-300 bg-gradient-to-br from-rose-50 via-pink-50 to-rose-100/70' : 'border-amber-300 bg-gradient-to-br from-amber-50 via-orange-50 to-amber-100/70' }} p-5 sm:p-6 text-xs text-slate-900 shadow-sm space-y-4">
                            <div class="flex flex-col items-center justify-center text-center gap-2 pb-2.5 border-b {{ $pemesanan->status_dp === 'Ditolak' ? 'border-rose-200/80' : 'border-amber-200/80' }}">
                                <h4 class="font-extrabold text-sm sm:text-base {{ $pemesanan->status_dp === 'Ditolak' ? 'text-rose-950' : 'text-amber-950' }}">
                                    {{ $pemesanan->status_dp === 'Ditolak' ? 'Bukti Transfer DP Ditolak Admin' : 'Menunggu Pembayaran Down Payment (DP 10%)' }}
                                </h4>
                                <span class="inline-flex items-center justify-center px-3.5 py-1 rounded-full bg-white {{ $pemesanan->status_dp === 'Ditolak' ? 'text-rose-800 border-rose-200' : 'text-amber-800 border-amber-200' }} text-xs font-bold border shadow-2xs tracking-wide">
                                    {{ $pemesanan->status_dp === 'Ditolak' ? 'Bukti Ditolak' : 'Wajib DP' }}
                                </span>
                            </div>

                            @if ($pemesanan->status_dp === 'Ditolak')
                                <div class="rounded-xl bg-white p-3.5 border border-rose-200 text-rose-900 space-y-1.5 max-w-md mx-auto text-left shadow-2xs">
                                    <span class="text-[10px] font-bold text-rose-600 uppercase tracking-wider block">Alasan Penolakan dari Admin:</span>
                                    <p class="text-xs font-semibold text-rose-950 leading-relaxed">
                                        {{ $pemesanan->alasan_penolakan_dp ?? ($pemesanan->catatan_dp ?? 'Bukti transfer tidak valid atau belum masuk mutasi rekening.') }}
                                    </p>
                                    <p class="text-[11px] text-rose-700 pt-1 border-t border-rose-100">
                                        Silakan pastikan dana telah berhasil ditransfer dan unggah kembali bukti transfer yang jelas di bawah.
                                    </p>
                                </div>
                            @else
                                <p class="text-amber-800 leading-relaxed text-center max-w-xl mx-auto">
                                    Pesanan ini bernilai skala besar (≥ Rp 10.000.000). Sesuai ketentuan, <b>survei lokasi teknisi baru dapat dijadwalkan setelah pembayaran DP 10% sebesar <span class="inline-flex items-center px-2 py-0.5 rounded-md font-black text-amber-950 bg-amber-200/90 border border-amber-300 font-mono text-xs">Rp {{ number_format($pemesanan->nominal_dp, 0, ',', '.') }}</span> diterima & diverifikasi admin</b>.
                                </p>
                            @endif

                            {{-- Kotak Rincian Nominal DP & Rekening Pembayaran Resmi --}}
                            <div class="rounded-2xl bg-white p-4 sm:p-5 border border-slate-200 shadow-sm space-y-3.5 max-w-md mx-auto text-left">
                                {{-- Highlight Nominal DP --}}
                                <div class="rounded-xl bg-gradient-to-r from-emerald-50 to-teal-50 p-3.5 border border-emerald-200 flex items-center justify-between gap-3">
                                    <div>
                                        <span class="text-[10px] font-extrabold uppercase tracking-wider text-emerald-800 block">Nominal DP 10% yang Harus Ditransfer</span>
                                        <span class="text-[11px] text-emerald-700">Harap transfer tepat sesuai nominal</span>
                                    </div>
                                    <div class="text-right shrink-0">
                                        <span class="text-base sm:text-lg font-black text-emerald-700 font-mono tracking-tight block">
                                            Rp {{ number_format($pemesanan->nominal_dp, 0, ',', '.') }}
                                        </span>
                                    </div>
                                </div>

                                {{-- Detail Rekening Bank --}}
                                <div class="space-y-2.5 pt-1">
                                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Tujuan Transfer Bank:</span>
                                    <div class="flex items-center justify-between bg-slate-50 p-3 rounded-xl border border-slate-200 gap-2">
                                        <div class="min-w-0">
                                            <span class="text-[11px] font-bold text-slate-600 block">
                                                {{ \App\Models\Pengaturan::get('bank_name', config('pestcontrol.bank_name', 'Bank Central Asia (BCA)')) }}
                                            </span>
                                            <span id="noRekStatus" class="text-sm sm:text-base font-mono font-black text-slate-900 select-all tracking-wider block">
                                                {{ \App\Models\Pengaturan::get('bank_account_number', config('pestcontrol.bank_account_number', '123-456-7890')) }}
                                            </span>
                                        </div>
                                        <button type="button" onclick="copyRekeningStatus()"
                                                class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg bg-white hover:bg-slate-100 text-slate-700 text-[11px] font-bold border border-slate-300 shadow-2xs transition active:scale-95 shrink-0 cursor-pointer"
                                                title="Salin No. Rekening">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3" />
                                            </svg>
                                            <span>Salin</span>
                                        </button>
                                    </div>
                                    <div id="copyRekStatusFeedback" class="hidden text-[11px] text-center text-emerald-700 font-bold bg-emerald-50 py-1 rounded-lg border border-emerald-200">
                                        Nomor rekening berhasil disalin!
                                    </div>

                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-[11px] pt-1">
                                        <div class="p-2.5 rounded-xl bg-slate-50 border border-slate-100">
                                            <span class="text-slate-400 block text-[10px]">Atas Nama Rekening:</span>
                                            <span class="font-bold text-slate-800">{{ \App\Models\Pengaturan::get('bank_account_holder', config('pestcontrol.bank_account_holder', 'CV. Ambius Indonesia')) }}</span>
                                        </div>
                                        <div class="p-2.5 rounded-xl bg-slate-50 border border-slate-100">
                                            <span class="text-slate-400 block text-[10px]">Berita / Keterangan Transfer:</span>
                                            <span class="font-mono font-black text-emerald-700">{{ $kodePemesanan }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Formulir Upload Bukti Transfer DP --}}
                            <div class="rounded-xl bg-white p-4 border {{ $pemesanan->status_dp === 'Ditolak' ? 'border-rose-300' : 'border-amber-300' }} space-y-3 max-w-md mx-auto text-left shadow-2xs">
                                <span class="block font-bold text-slate-900 text-xs">
                                    {{ $pemesanan->status_dp === 'Ditolak' ? 'Unggah Ulang Bukti Transfer DP 10%' : 'Unggah Bukti Transfer DP 10%' }}
                                </span>
                                <p class="text-[11px] text-slate-500">
                                    Setelah mentransfer DP sebesar <b>Rp {{ number_format($pemesanan->nominal_dp, 0, ',', '.') }}</b>, silakan unggah foto/screenshot struk bukti transfer Anda:
                                </p>
                                <form action="{{ route('pemesanan.upload-dp', $pemesanan->id_pemesanan) }}" method="POST" enctype="multipart/form-data" class="space-y-3">
                                    @csrf
                                    <input type="hidden" name="kode_pemesanan" value="{{ $kodePemesanan }}">
                                    <div>
                                        <input type="file" name="bukti_dp" accept="image/*" required
                                               class="block w-full text-xs text-slate-500 file:mr-3 file:py-2 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-emerald-100 file:text-emerald-800 hover:file:bg-emerald-200 cursor-pointer"
                                               onchange="previewImageStatus(this, 'preview-dp-status')">
                                        <p class="text-[10px] text-slate-400 mt-1">Format gambar: JPG, JPEG, PNG, WEBP (Maksimal 5 MB).</p>
                                        <div id="preview-dp-status" class="hidden mt-2 rounded-xl overflow-hidden max-h-48 border border-slate-200"></div>
                                    </div>
                                    <button type="submit"
                                            class="w-full inline-flex items-center justify-center gap-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 px-4 py-2 text-xs font-bold text-white shadow transition cursor-pointer">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                                        <span>Kirim Bukti Transfer DP</span>
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endif
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

                            @if ($pemesanan->wajib_dp)
                                <div class="flex items-center justify-between py-1 border-t border-slate-100 pt-2">
                                    <div>
                                        <span class="text-slate-700 font-bold block">Down Payment (DP 10%)</span>
                                        <span class="text-[10px] text-amber-700 font-medium block">Wajib (Nominal ≥ 10jt)</span>
                                    </div>
                                    <span class="text-xs font-black text-amber-900">
                                        Rp {{ number_format($pemesanan->nominal_dp, 0, ',', '.') }}
                                    </span>
                                </div>
                                <div class="flex items-center justify-between py-1">
                                    <span class="text-slate-500 font-medium">Status Verifikasi DP</span>
                                    <span class="rounded-full border px-2.5 py-0.5 text-[10px] font-bold {{ $pemesanan->status_dp === 'Lunas' ? 'bg-emerald-100 text-emerald-800 border-emerald-200' : 'bg-amber-100 text-amber-800 border-amber-200' }}">
                                        {{ $pemesanan->status_dp === 'Lunas' ? 'Lunas & Terverifikasi' : 'Menunggu Pembayaran' }}
                                    </span>
                                </div>
                            @endif

                            <div class="flex items-center justify-between pt-2 border-t border-slate-100">
                                <span class="text-slate-500 font-medium">Status Pelunasan Akhir</span>
                                <span class="rounded-full border px-2.5 py-0.5 text-[11px] font-bold {{ $pbColor }}">
                                    {{ $pbVal }}
                                </span>
                            </div>
                        </div>

                        {{-- Dokumen Unduh Resmi (HANYA status Disetujui via route controller) --}}
                        <div class="pt-4 border-t border-slate-100 space-y-2.5">
                            <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider">Unduh Dokumen Resmi</span>

                            {{-- 0. Bukti Transfer Down Payment (DP 10%) jika ada --}}
                            @if ($pemesanan->wajib_dp)
                                @if ($pemesanan->bukti_dp && $pemesanan->status_dp === 'Lunas')
                                    <a href="{{ route('dokumen.dp', ['pemesanan' => $pemesanan->id_pemesanan, 'kode' => $kodePemesanan]) }}" target="_blank"
                                       class="group w-full flex items-center justify-between p-2.5 rounded-xl bg-amber-50 hover:bg-amber-100 text-amber-950 border border-amber-300 transition text-xs">
                                        <div>
                                            <span class="font-bold text-amber-950 block">Bukti Transfer DP (10%)</span>
                                            <span class="text-[10px] text-amber-800 block">Diverifikasi oleh Admin</span>
                                        </div>
                                        <span class="inline-flex items-center justify-center rounded-lg bg-white border border-amber-400 px-3 py-1 text-[11px] font-bold text-amber-800 shadow-2xs group-hover:bg-amber-700 group-hover:text-white group-hover:border-amber-700 transition">
                                            Lihat Bukti
                                        </span>
                                    </a>
                                @else
                                    <div class="w-full flex items-center justify-between p-2.5 rounded-xl bg-slate-50 border border-slate-200 text-slate-400 text-xs">
                                        <span class="font-medium text-slate-500">Bukti Transfer DP (10%)</span>
                                        <span class="text-[11px] font-semibold text-slate-400 italic">Belum Diverifikasi</span>
                                    </div>
                                @endif
                            @endif

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
    function copyRekeningStatus() {
        const text = document.getElementById('noRekStatus')?.innerText.trim();
        if (!text) return;
        navigator.clipboard.writeText(text).then(() => {
            const feedback = document.getElementById('copyRekStatusFeedback');
            if (feedback) {
                feedback.classList.remove('hidden');
                setTimeout(() => {
                    feedback.classList.add('hidden');
                }, 2500);
            }
        });
    }

    function previewImageStatus(input, previewId) {
        const preview = document.getElementById(previewId);
        if (!preview) return;
        preview.innerHTML = '';
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                preview.innerHTML = `<img src="${e.target.result}" class="w-full h-auto max-h-48 object-cover rounded-xl shadow-xs" alt="Preview Bukti Transfer">`;
                preview.classList.remove('hidden');
            };
            reader.readAsDataURL(input.files[0]);
        } else {
            preview.classList.add('hidden');
        }
    }

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
