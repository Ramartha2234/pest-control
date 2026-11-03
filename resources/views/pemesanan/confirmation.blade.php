@extends('layouts.app')

@section('title', 'Pemesanan Berhasil - CV. Ambius Indonesia')

@section('content')
<div class="py-16 bg-slate-50 min-h-screen flex items-center justify-center">
    <div class="mx-auto max-w-xl px-4 sm:px-6 w-full">
        <div class="relative rounded-3xl border border-slate-200 bg-white p-6 sm:p-10 text-center shadow-2xl overflow-hidden">
            <div class="absolute inset-x-0 top-0 h-2 bg-gradient-to-r from-emerald-500 via-teal-500 to-primary-600"></div>

            {{-- Checkmark Icon --}}
            <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-600 mb-5 shadow-sm">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>

            <h1 class="text-2xl font-black text-slate-900 tracking-tight">Pemesanan Anda Berhasil Dicatat!</h1>
            <p class="text-xs text-slate-500 mt-2 max-w-md mx-auto leading-relaxed">
                Terima kasih atas kepercayaan Anda kepada CV. Ambius Indonesia. Data survei Anda telah masuk antrean verifikasi teknisi.
            </p>

            {{-- Flash Messages --}}
            @if (session('success'))
                <div class="mt-4 rounded-2xl bg-emerald-50 border border-emerald-300 p-4 text-xs font-bold text-emerald-900 text-left">
                    {{ session('success') }}
                </div>
            @endif
            @if (session('error'))
                <div class="mt-4 rounded-2xl bg-rose-50 border border-rose-300 p-4 text-xs font-bold text-rose-900 text-left">
                    {{ session('error') }}
                </div>
            @endif
            @if (session('warning'))
                <div class="mt-4 rounded-2xl bg-amber-50 border border-amber-300 p-4 text-xs font-bold text-amber-900 text-left">
                    {{ session('warning') }}
                </div>
            @endif
            @if ($errors->any())
                <div class="mt-4 rounded-2xl bg-rose-50 border border-rose-300 p-4 text-xs font-bold text-rose-900 text-left">
                    <ul class="list-disc pl-4 space-y-1">
                        @foreach ($errors->all() as $err)
                            <li>{{ $err }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- Unique Code Highlight --}}
            @php
                $kode = $pemesanan->kode_pemesanan ?? $pemesanan->kode_unik;
            @endphp
            <div class="mt-6 rounded-2xl bg-gradient-to-br from-emerald-50 to-teal-50 p-6 border border-emerald-200 space-y-3">
                <span class="text-[11px] font-bold uppercase tracking-wider text-emerald-800">Kode Unik Pelacakan Anda</span>
                <div class="flex items-center justify-center gap-3">
                    <span id="kodeUnik" class="text-2xl sm:text-3xl font-black font-mono tracking-widest text-emerald-950 select-all">{{ $kode }}</span>
                    <button onclick="copyToClipboard()" class="rounded-xl bg-white p-2 text-emerald-700 hover:bg-emerald-100 shadow-sm transition border border-emerald-200" title="Salin Kode">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3" />
                        </svg>
                    </button>
                </div>
                <div id="copyFeedback" class="hidden text-xs text-emerald-700 font-bold">Kode berhasil disalin!</div>
            </div>

            {{-- Estimasi & Detail Ringkas --}}
            <div class="mt-4 rounded-2xl bg-slate-50/90 p-4 border border-slate-200 grid grid-cols-1 sm:grid-cols-3 gap-3 sm:gap-0 divide-y sm:divide-y-0 sm:divide-x divide-slate-200">
                <div class="flex flex-col items-center justify-center text-center pb-3 sm:pb-0 sm:px-3">
                    <span class="text-[10px] text-slate-400 font-bold uppercase tracking-wider block mb-1">Layanan Dipilih</span>
                    <span class="font-bold text-slate-800 text-xs sm:text-sm block leading-snug">{{ $pemesanan->tarifLayanan->jenis_layanan ?? '-' }}</span>
                </div>
                <div class="flex flex-col items-center justify-center text-center py-3 sm:py-0 sm:px-3">
                    <span class="text-[10px] text-slate-400 font-bold uppercase tracking-wider block mb-1">Pilihan Area</span>
                    <span class="inline-flex items-center text-xs font-bold px-3 py-1 rounded-lg border {{ ($pemesanan->tipe_area ?? 'Indoor') === 'Outdoor' ? 'bg-amber-100 text-amber-800 border-amber-200' : 'bg-emerald-100 text-emerald-800 border-emerald-200' }}">
                        {{ ($pemesanan->tipe_area ?? 'Indoor') === 'Outdoor' ? 'Outdoor (+Rp 100.000)' : 'Indoor' }}
                    </span>
                </div>
                <div class="flex flex-col items-center justify-center text-center pt-3 sm:pt-0 sm:px-3">
                    <span class="text-[10px] text-slate-400 font-bold uppercase tracking-wider block mb-1">Estimasi Biaya</span>
                    <span class="font-extrabold text-emerald-700 text-sm sm:text-base block tracking-tight">Rp {{ number_format($pemesanan->harga_estimasi, 0, ',', '.') }}</span>
                </div>
            </div>

            {{-- Box Khusus Down Payment (DP 10%) Jika Pesanan >= 10 Juta --}}
            @if ($pemesanan->wajib_dp)
                <div class="mt-6 rounded-2xl border border-amber-200/90 bg-amber-50/40 p-5 sm:p-6 text-left shadow-sm space-y-4">
                    {{-- Header DP --}}
                    <div class="flex items-start sm:items-center justify-between gap-3 pb-3 border-b border-amber-200/80">
                        <div>
                            <span class="text-[10px] font-bold uppercase tracking-wider text-amber-800 block">Verifikasi Pembayaran</span>
                            <h4 class="text-sm sm:text-base font-extrabold text-gray-900 mt-0.5">Pemesanan Skala Besar (Wajib DP 10%)</h4>
                        </div>
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-extrabold bg-amber-100 text-amber-900 border border-amber-300 whitespace-nowrap shrink-0">
                            Menunggu DP
                        </span>
                    </div>

                    {{-- Rincian Biaya & DP --}}
                    <div class="rounded-xl bg-white p-4 border border-slate-200 shadow-sm space-y-2.5">
                        <div class="flex items-center justify-between text-xs text-slate-500">
                            <span>Total Estimasi Biaya Pesanan</span>
                            <span class="font-bold text-slate-800 whitespace-nowrap">Rp {{ number_format($pemesanan->harga_estimasi, 0, ',', '.') }}</span>
                        </div>
                        <div class="pt-2.5 border-t border-slate-100 flex items-center justify-between gap-3">
                            <div>
                                <span class="block text-xs font-bold text-slate-800">Down Payment (DP 10%) Wajib</span>
                                <span class="block text-[10px] text-slate-400">Diperlukan sebelum jadwal survei dibuka</span>
                            </div>
                            <span class="text-base sm:text-lg font-black text-emerald-700 whitespace-nowrap shrink-0">
                                Rp {{ number_format($pemesanan->nominal_dp, 0, ',', '.') }}
                            </span>
                        </div>
                    </div>

                    {{-- Informasi Rekening Perusahaan --}}
                    <div class="rounded-xl bg-white p-4 border border-slate-200 shadow-sm space-y-3">
                        <div class="flex flex-col items-center justify-center text-center gap-1.5 pb-2.5 border-b border-slate-100">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Rekening Resmi Transfer DP</span>
                            <span class="inline-flex items-center text-xs font-extrabold text-primary-800 bg-primary-50 px-3 py-1 rounded-lg border border-primary-200/80 whitespace-nowrap">
                                {{ \App\Models\Pengaturan::get('bank_name', config('pestcontrol.bank_name', 'Bank Central Asia (BCA)')) }}
                            </span>
                        </div>

                        <div class="flex items-center justify-between bg-slate-50 p-3 rounded-xl border border-slate-100 gap-2">
                            <div class="min-w-0">
                                <span class="text-[10px] text-slate-400 font-semibold block uppercase">Nomor Rekening</span>
                                <span id="noRek" class="text-base sm:text-lg font-mono font-black text-slate-900 select-all tracking-wider block truncate">
                                    {{ \App\Models\Pengaturan::get('bank_account_number', config('pestcontrol.bank_account_number', '123-456-7890')) }}
                                </span>
                            </div>
                            <button type="button" onclick="copyRekening()"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-white hover:bg-slate-100 text-slate-700 text-xs font-bold border border-slate-200 shadow-sm transition active:scale-95 shrink-0 cursor-pointer"
                                    title="Salin No. Rekening">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3" />
                                </svg>
                                <span>Salin</span>
                            </button>
                        </div>

                        <div class="flex items-center justify-between text-xs text-slate-600 px-0.5">
                            <span class="text-[11px] text-slate-400">Atas Nama:</span>
                            <span class="font-bold text-slate-800 text-[11px] sm:text-xs">{{ \App\Models\Pengaturan::get('bank_account_holder', config('pestcontrol.bank_account_holder', 'CV. Ambius Indonesia')) }}</span>
                        </div>

                        <div id="copyRekFeedback" class="hidden text-xs text-center text-emerald-700 font-bold bg-emerald-50 py-1.5 rounded-xl border border-emerald-200">
                            Nomor rekening berhasil disalin!
                        </div>
                    </div>

                    {{-- Petunjuk Alur --}}
                    <div class="rounded-xl bg-amber-100/70 p-4 border border-amber-200/90 text-xs text-amber-950 space-y-2">
                        <span class="font-extrabold text-amber-900 block text-xs">Petunjuk Pembayaran & Konfirmasi:</span>
                        <ol class="list-decimal pl-4 space-y-1.5 text-[11px] text-amber-900/90 leading-relaxed">
                            <li>Transfer DP sebesar <strong class="text-amber-950 whitespace-nowrap">Rp {{ number_format($pemesanan->nominal_dp, 0, ',', '.') }}</strong> ke rekening di atas.</li>
                            <li>Sertakan kode pesanan <strong class="text-amber-950">{{ $kode }}</strong> pada berita/catatan transfer.</li>
                            <li>Unggah foto/tangkapan layar bukti transfer pada formulir di bawah ini.</li>
                            <li>Admin akan memverifikasi mutasi bank dan menyetujui pembayaran DP agar jadwal survei langsung dibuka.</li>
                        </ol>
                    </div>

                    {{-- Status & Formulir Upload Bukti Transfer DP oleh Pelanggan --}}
                    @if ($pemesanan->status_dp === 'Lunas')
                        <div class="rounded-xl bg-emerald-50 p-4 border border-emerald-300 text-xs text-emerald-950 space-y-2">
                            <div class="flex items-center gap-2 text-emerald-800 font-extrabold text-sm">
                                <svg class="h-5 w-5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                <span>Pembayaran DP 10% Berhasil Diverifikasi!</span>
                            </div>
                            <p class="text-[11px] text-emerald-800">
                                Pembayaran DP sebesar <strong>Rp {{ number_format($pemesanan->nominal_dp, 0, ',', '.') }}</strong> telah disetujui admin. Jadwal survei lokasi sedang diproses.
                            </p>
                            @if ($pemesanan->bukti_dp)
                                <div class="pt-1">
                                    <a href="{{ route('dokumen.dp', ['pemesanan' => $pemesanan->id_pemesanan, 'kode' => $kode]) }}" target="_blank"
                                       class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-emerald-700 hover:bg-emerald-800 text-white font-bold text-xs shadow-sm transition">
                                        Lihat Bukti Transfer DP Resmi
                                    </a>
                                </div>
                            @endif
                        </div>
                    @elseif ($pemesanan->status_dp === 'Menunggu Verifikasi')
                        <div class="rounded-xl bg-blue-50 p-4 border border-blue-200 text-xs text-blue-950 space-y-3">
                            <div class="flex items-center justify-between gap-2">
                                <div class="flex items-center gap-2 text-blue-900 font-extrabold text-sm">
                                    <svg class="h-5 w-5 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    <span>Bukti Transfer DP Berhasil Diunggah</span>
                                </div>
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-extrabold bg-blue-100 text-blue-800 border border-blue-300">
                                    Menunggu Verifikasi Admin
                                </span>
                            </div>
                            <p class="text-[11px] text-blue-800 leading-relaxed">
                                Bukti transfer DP Anda telah kami terima dan sedang dalam antrean verifikasi admin. Jadwal survei akan dibuka segera setelah pembayaran disetujui.
                            </p>
                            @if ($pemesanan->bukti_dp)
                                <div class="pt-1">
                                    <a href="{{ route('dokumen.dp', ['pemesanan' => $pemesanan->id_pemesanan, 'kode' => $kode]) }}" target="_blank"
                                       class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-blue-700 hover:bg-blue-800 text-white font-bold text-xs shadow-sm transition">
                                        Lihat Bukti yang Anda Unggah
                                    </a>
                                </div>
                            @endif

                            <details class="text-[11px] text-slate-600 pt-2 border-t border-blue-100">
                                <summary class="cursor-pointer font-bold text-blue-700 hover:underline">Unggah Ulang Bukti Transfer (Jika salah file)</summary>
                                <form action="{{ route('pemesanan.upload-dp', $pemesanan->id_pemesanan) }}" method="POST" enctype="multipart/form-data" class="mt-3 space-y-2.5">
                                    @csrf
                                    <input type="hidden" name="kode_pemesanan" value="{{ $kode }}">
                                    <input type="file" name="bukti_dp" accept="image/*" required
                                           class="block w-full text-xs text-slate-500 file:mr-3 file:py-2 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-blue-100 file:text-blue-800 hover:file:bg-blue-200 cursor-pointer">
                                    <button type="submit" class="px-3.5 py-1.5 rounded-lg bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs transition cursor-pointer">
                                        Kirim Ulang Bukti Transfer
                                    </button>
                                </form>
                            </details>
                        </div>
                    @else
                        {{-- Belum Dibayar atau Ditolak --}}
                        @if ($pemesanan->status_dp === 'Ditolak')
                            <div class="rounded-xl bg-rose-50 p-4 border border-rose-300 text-xs text-rose-950 space-y-2">
                                <div class="flex items-center gap-2 text-rose-800 font-extrabold text-sm">
                                    <svg class="h-5 w-5 text-rose-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    <span>Bukti Transfer DP Ditolak Admin</span>
                                </div>
                                <p class="text-[11px] text-rose-800">
                                    <strong>Alasan Penolakan:</strong> {{ $pemesanan->alasan_penolakan_dp ?? ($pemesanan->catatan_dp ?? 'Bukti transfer tidak sesuai atau mutasi bank belum masuk.') }}
                                </p>
                                <p class="text-[11px] text-rose-700">
                                    Silakan lakukan transfer ulang atau unggah kembali bukti transfer yang valid di bawah ini.
                                </p>
                            </div>
                        @endif

                        <div class="rounded-xl bg-white p-4 border border-amber-300 shadow-sm space-y-3">
                            <div class="flex items-center gap-2">
                                <span class="flex h-6 w-6 items-center justify-center rounded-lg bg-emerald-600 text-white font-bold text-xs">
                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                                </span>
                                <h5 class="font-extrabold text-slate-900 text-xs sm:text-sm">
                                    {{ $pemesanan->status_dp === 'Ditolak' ? 'Unggah Ulang Bukti Transfer DP 10%' : 'Unggah Bukti Transfer DP 10%' }}
                                </h5>
                            </div>
                            <p class="text-[11px] text-slate-500">
                                Setelah mentransfer DP sebesar <b>Rp {{ number_format($pemesanan->nominal_dp, 0, ',', '.') }}</b>, silakan unggah foto/screenshot struk bukti transfer Anda:
                            </p>

                            <form action="{{ route('pemesanan.upload-dp', $pemesanan->id_pemesanan) }}" method="POST" enctype="multipart/form-data" class="space-y-3">
                                @csrf
                                <input type="hidden" name="kode_pemesanan" value="{{ $kode }}">
                                <div>
                                    <input type="file" name="bukti_dp" accept="image/*" required
                                           class="block w-full text-xs text-slate-500 file:mr-3 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-emerald-100 file:text-emerald-800 hover:file:bg-emerald-200 cursor-pointer"
                                           onchange="previewImageConfirmation(this, 'preview-dp-confirm')">
                                    <p class="text-[10px] text-slate-400 mt-1">Format gambar: JPG, JPEG, PNG, WEBP (Maksimal 5 MB).</p>
                                    <div id="preview-dp-confirm" class="hidden mt-2 rounded-xl overflow-hidden max-h-48 border border-slate-200"></div>
                                </div>
                                <button type="submit"
                                        class="w-full inline-flex items-center justify-center gap-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 px-4 py-2.5 text-xs font-extrabold text-white shadow-md transition active:scale-[0.99] cursor-pointer">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                                    <span>Kirim Bukti Transfer DP</span>
                                </button>
                            </form>
                        </div>
                    @endif
                </div>

                {{-- WhatsApp Confirmation Button (Khusus DP) --}}
                <div class="mt-6">
                    <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', \App\Models\Pengaturan::get('whatsapp_number', config('pestcontrol.whatsapp_number'))) }}?text=Halo%20Admin%20CV.%20Ambius%20Indonesia,%20saya%20sudah%20transfer%20DP%2010%25%20sebesar%20Rp%20{{ number_format($pemesanan->nominal_dp, 0, ',', '.') }}%20untuk%20Kode%20Pemesanan:%20{{ $kode }}.%20Berikut%20saya%20lampirkan%20bukti%20transfernya."
                        target="_blank" rel="noopener"
                        class="w-full inline-flex items-center justify-center gap-2 rounded-2xl bg-emerald-600 hover:bg-emerald-700 px-3 sm:px-6 py-3.5 text-[11px] min-[360px]:text-xs sm:text-sm font-extrabold text-white shadow-lg transition active:scale-[0.99] whitespace-nowrap">
                        <svg class="h-4 w-4 sm:h-5 sm:w-5 shrink-0" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                        <span>Konfirmasi Pembayaran DP via WhatsApp</span>
                    </a>
                </div>
            @else
                {{-- Peringatan 3 Hari Auto-Cancel untuk Pesanan Biasa --}}
                <div class="mt-4 rounded-xl bg-amber-50 p-4 border border-amber-200 text-left text-xs text-amber-900 space-y-1">
                    <strong class="block font-bold">
                        Ketentuan Konfirmasi (Maksimal 3 Hari):
                    </strong>
                    <ul class="list-disc pl-4 space-y-1 mt-1 text-[11px] text-amber-800">
                        <li>Simpan atau catat <b>Kode Pemesanan</b> di atas untuk mengecek status secara berkala.</li>
                        <li>Admin kami akan menghubungi Anda via WhatsApp. Anda juga dapat langsung mengonfirmasi lewat tombol hijau di bawah.</li>
                        <li><b>Batas waktu konfirmasi adalah 3 hari</b> (sampai {{ $pemesanan->batas_konfirmasi->translatedFormat('d F Y H:i') }} WITA). Pemesanan yang belum terkonfirmasi setelah batas waktu akan dibatalkan otomatis oleh sistem.</li>
                    </ul>
                </div>

                {{-- WhatsApp Confirmation Button (Alur Biasa) --}}
                <div class="mt-6">
                    <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', \App\Models\Pengaturan::get('whatsapp_number', config('pestcontrol.whatsapp_number'))) }}?text=Halo%20Admin%20CV.%20Ambius%20Indonesia,%20saya%20ingin%20konfirmasi%20pemesanan%20jasa%20pest%20control%20dengan%20Kode%20Pemesanan:%20{{ $kode }}"
                        target="_blank" rel="noopener"
                        class="w-full inline-flex items-center justify-center gap-2 rounded-2xl bg-emerald-600 hover:bg-emerald-700 px-3 sm:px-6 py-3.5 text-[11px] min-[360px]:text-xs sm:text-sm font-extrabold text-white shadow-lg transition active:scale-[0.99] whitespace-nowrap">
                        <svg class="h-4 w-4 sm:h-5 sm:w-5 shrink-0" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                        <span>Konfirmasi via WhatsApp Sekarang</span>
                    </a>
                </div>
            @endif

            {{-- Nav Actions --}}
            <div class="mt-6 flex flex-col sm:flex-row gap-2 justify-center">
                <a href="{{ route('status.index', ['code' => $kode]) }}" class="rounded-xl border border-slate-300 bg-white px-5 py-2.5 text-xs font-bold text-slate-700 hover:bg-slate-50 transition">
                    Cek Status Pesanan
                </a>
                <a href="{{ route('home') }}" class="rounded-xl border border-transparent px-5 py-2.5 text-xs font-semibold text-slate-500 hover:text-slate-800 transition">
                    Kembali ke Beranda
                </a>
            </div>
        </div>
    </div>
</div>

<script>
    function copyToClipboard() {
        const text = document.getElementById('kodeUnik').innerText.trim();
        navigator.clipboard.writeText(text).then(() => {
            const feedback = document.getElementById('copyFeedback');
            feedback.classList.remove('hidden');
            setTimeout(() => {
                feedback.classList.add('hidden');
            }, 2500);
        });
    }

    function copyRekening() {
        const text = document.getElementById('noRek')?.innerText.trim();
        if (!text) return;
        navigator.clipboard.writeText(text).then(() => {
            const feedback = document.getElementById('copyRekFeedback');
            if (feedback) {
                feedback.classList.remove('hidden');
                setTimeout(() => {
                    feedback.classList.add('hidden');
                }, 2500);
            }
        });
    }

    function previewImageConfirmation(input, previewId) {
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
</script>
@endsection
