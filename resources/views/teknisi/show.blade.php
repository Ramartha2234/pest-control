@extends('layouts.teknisi')

@section('title', 'Penugasan: ' . $pemesanan->kode_pemesanan . ' - Teknisi')

@section('content')
<div class="space-y-4">
    {{-- Tombol Navigasi Kembali --}}
    <div class="flex items-center justify-between">
        <a href="{{ route('teknisi.tugas') }}" class="inline-flex items-center gap-1.5 text-xs font-bold text-emerald-800 hover:text-emerald-900 bg-white border border-slate-200 px-3 py-1.5 rounded-xl shadow-sm transition">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
            Kembali ke Daftar Tugas
        </a>
        <span class="rounded-xl bg-slate-800 px-3 py-1 font-mono text-xs font-black text-white shadow-sm">
            {{ $pemesanan->kode_pemesanan }}
        </span>
    </div>

    {{-- Banner Pembatalan (Jika Pesanan Cancel) --}}
    @if ($pemesanan->status_pengerjaan === 'Cancel')
        <div class="rounded-2xl border-2 border-rose-300 bg-rose-50 p-4 shadow-sm">
            <div class="flex items-start gap-3">
                <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-xl bg-rose-600 text-white shadow-sm">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-sm font-black text-rose-900">Pemesanan Ini Telah Dibatalkan</h3>
                    <p class="text-xs text-rose-700 mt-0.5">
                        Dibatalkan oleh: <b>{{ $pemesanan->dibatalkan_oleh ?? 'CV. Ambius Indonesia' }}</b>
                    </p>
                    <p class="text-xs text-rose-800 mt-1 italic bg-white/70 p-2 rounded-lg border border-rose-200">
                        "{{ $pemesanan->alasan_pembatalan ?? 'Tidak ada keterangan pembatalan.' }}"
                    </p>
                    <p class="text-[11px] text-rose-600 mt-1.5 font-semibold">
                        Semua form pengajuan dan aksi teknisi dinonaktifkan untuk pesanan ini.
                    </p>
                </div>
            </div>
        </div>
    @endif

    {{-- Informasi Ringkasan Pelanggan & Lokasi --}}
    <div class="rounded-3xl border border-slate-200 bg-white p-4 sm:p-5 shadow-sm space-y-3">
        <div class="flex items-start justify-between gap-2 border-b border-slate-100 pb-3">
            <div>
                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Pelanggan</span>
                <h2 class="text-lg font-black text-slate-900 leading-tight mt-0.5">{{ $pemesanan->pelanggan->nama }}</h2>
                @if ($pemesanan->pelanggan->nama_usaha)
                    <p class="text-xs font-semibold text-emerald-700">{{ $pemesanan->pelanggan->nama_usaha }}</p>
                @endif
                <p class="text-xs text-slate-500 mt-0.5 font-medium">{{ $pemesanan->pelanggan->no_hp }}</p>
            </div>

            @php
                $statusColor = match($pemesanan->status_pengerjaan) {
                    'Pending' => 'bg-amber-100 text-amber-800 border-amber-200',
                    'On-Going' => 'bg-sky-100 text-sky-800 border-sky-200',
                    'Done' => 'bg-emerald-100 text-emerald-800 border-emerald-200',
                    'Cancel' => 'bg-rose-100 text-rose-800 border-rose-200',
                    default => 'bg-slate-100 text-slate-800 border-slate-200'
                };
            @endphp
            <div class="text-right">
                <span class="inline-block rounded-full border px-3 py-1 text-xs font-extrabold {{ $statusColor }}">
                    {{ $pemesanan->status_pengerjaan }}
                </span>
                <span class="block text-[11px] text-slate-500 mt-1">
                    Pembayaran: <b class="{{ $pemesanan->status_pembayaran === 'Lunas' ? 'text-emerald-700' : 'text-amber-700' }}">{{ $pemesanan->status_pembayaran }}</b>
                </span>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 text-xs text-slate-600">
            <div class="bg-slate-50 p-2.5 rounded-xl border border-slate-100">
                <span class="block text-[10px] text-slate-400 uppercase font-bold">Layanan & Properti</span>
                <p class="font-bold text-slate-800 mt-0.5">{{ $pemesanan->tarifLayanan->jenis_layanan ?? '-' }}</p>
                <div class="flex items-center gap-1.5 mt-1 flex-wrap">
                    <span class="text-slate-500">{{ $pemesanan->jenis_properti }} &bull; Area: {{ $pemesanan->ukuran_area ?? '-' }} m²</span>
                    <span class="inline-flex items-center text-[10px] font-bold px-1.5 py-0.5 rounded {{ ($pemesanan->tipe_area ?? 'Indoor') === 'Outdoor' ? 'bg-amber-100 text-amber-800 border border-amber-200' : 'bg-emerald-100 text-emerald-800 border border-emerald-200' }}">
                        {{ ($pemesanan->tipe_area ?? 'Indoor') === 'Outdoor' ? 'Outdoor (+100k)' : 'Indoor' }}
                    </span>
                </div>
            </div>

            <div class="bg-slate-50 p-2.5 rounded-xl border border-slate-100">
                <span class="block text-[10px] text-slate-400 uppercase font-bold">Acuan Estimasi / Final</span>
                <p class="font-extrabold text-emerald-800 text-sm mt-0.5">
                    @if ($pemesanan->harga_final)
                        Rp {{ number_format($pemesanan->harga_final, 0, ',', '.') }} <span class="text-[10px] font-normal text-emerald-600">(Harga Final Disetujui)</span>
                    @else
                        Rp {{ number_format($pemesanan->harga_estimasi, 0, ',', '.') }} <span class="text-[10px] font-normal text-slate-500">({{ ($pemesanan->tipe_area ?? 'Indoor') === 'Outdoor' ? 'Termasuk Outdoor +100k' : 'Tarif Dasar' }})</span>
                    @endif
                </p>
            </div>
        </div>

        <div class="bg-slate-50 p-3 rounded-2xl border border-slate-100 text-xs">
            <span class="block text-[10px] text-slate-400 uppercase font-bold mb-1">Alamat Lokasi Pengerjaan</span>
            <p class="font-semibold text-slate-800 leading-relaxed">{{ $pemesanan->alamat_lokasi }}</p>
            @if ($pemesanan->keluhan)
                <p class="mt-2 text-slate-600 italic bg-white p-2 rounded-xl border border-slate-200">
                    "{{ $pemesanan->keluhan }}"
                </p>
            @endif
        </div>

        {{-- Hubungi Pelanggan via WhatsApp --}}
        @php
            $waPhone = preg_replace('/^0/', '62', preg_replace('/[^0-9]/', '', $pemesanan->pelanggan->no_hp));
            $waMessage = urlencode("Halo Bapak/Ibu {$pemesanan->pelanggan->nama}, saya " . Auth::user()->nama . " (Teknisi CV. Ambius Indonesia) terkait pemesanan Anda dengan kode {$pemesanan->kode_pemesanan}.");
        @endphp
        <a href="https://wa.me/{{ $waPhone }}?text={{ $waMessage }}"
           target="_blank" rel="noopener"
           class="w-full py-3 px-4 rounded-2xl bg-emerald-700 hover:bg-emerald-800 text-white font-bold text-xs flex items-center justify-center gap-2 shadow-sm transition">
            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
            Hubungi Pelanggan WhatsApp Langsung
        </a>
    </div>

    {{-- STEPPER ALUR 5 TAHAP PENGERJAAN TEKNISI --}}
    <div class="rounded-3xl border border-slate-200 bg-white p-4 sm:p-5 shadow-sm space-y-4">
        <div class="border-b border-slate-100 pb-3">
            <h3 class="text-sm font-black text-slate-900 flex items-center justify-between">
                <span>Alur Tahapan Penugasan</span>
                <span class="rounded-full bg-emerald-100 text-emerald-800 px-2.5 py-0.5 text-[11px] font-bold">
                    Tahap {{ $tahapAktif }} dari 5
                </span>
            </h3>
            <p class="text-xs text-slate-500 mt-0.5">Tombol pengajuan hanya aktif berurutan sesuai tahap yang telah diverifikasi admin.</p>
        </div>

        {{-- Visual Stepper Bar --}}
        <div class="grid grid-cols-5 gap-1 text-center text-[10px] font-bold">
            <div class="p-2 rounded-xl {{ $tahapAktif >= 1 ? 'bg-emerald-700 text-white' : 'bg-slate-100 text-slate-400' }}">
                1. Survei
            </div>
            <div class="p-2 rounded-xl {{ $tahapAktif >= 2 ? 'bg-emerald-700 text-white' : 'bg-slate-100 text-slate-400' }}">
                2. Hasil
            </div>
            <div class="p-2 rounded-xl {{ $tahapAktif >= 3 ? 'bg-emerald-700 text-white' : 'bg-slate-100 text-slate-400' }}">
                3. Kerja
            </div>
            <div class="p-2 rounded-xl {{ $tahapAktif >= 4 ? 'bg-emerald-700 text-white' : 'bg-slate-100 text-slate-400' }}">
                4. WO & Bayar
            </div>
            <div class="p-2 rounded-xl {{ $tahapAktif === 5 ? 'bg-emerald-700 text-white' : 'bg-slate-100 text-slate-400' }}">
                5. Selesai
            </div>
        </div>

        {{-- ========================================================================= --}}
        {{-- TAHAP 1: KEHADIRAN SURVEI LAPANGAN --}}
        {{-- ========================================================================= --}}
        <div class="rounded-2xl border {{ $tahapAktif === 1 ? 'border-2 border-emerald-500 bg-emerald-50/20' : 'border-slate-200 bg-slate-50/60' }} p-4 space-y-3">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="flex h-7 w-7 items-center justify-center rounded-full {{ $latestBuktiSurvei?->status_verifikasi === 'Disetujui' ? 'bg-emerald-600 text-white' : ($tahapAktif === 1 ? 'bg-emerald-700 text-white' : 'bg-slate-300 text-slate-600') }} font-bold text-xs">
                        1
                    </span>
                    <h4 class="text-sm font-bold text-slate-800">Foto Kehadiran Survei</h4>
                </div>

                @if ($latestBuktiSurvei)
                    @php
                        $badgeSurvei = match($latestBuktiSurvei->status_verifikasi) {
                            'Disetujui' => 'bg-emerald-100 text-emerald-800 border-emerald-200',
                            'Ditolak' => 'bg-rose-100 text-rose-800 border-rose-200',
                            default => 'bg-amber-100 text-amber-800 border-amber-200',
                        };
                    @endphp
                    <span class="rounded-full border px-2.5 py-0.5 text-[11px] font-bold {{ $badgeSurvei }}">
                        {{ $latestBuktiSurvei->status_verifikasi }}
                    </span>
                @else
                    <span class="text-[11px] text-slate-400 font-semibold">Belum Diunggah</span>
                @endif
            </div>

            {{-- Detail Bukti Survei Terakhir --}}
            @if ($latestBuktiSurvei)
                <div class="rounded-xl bg-white p-3 border border-slate-200 text-xs space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-slate-500">Diupload: {{ $latestBuktiSurvei->created_at->format('d M Y, H:i') }}</span>
                        <a href="{{ route('dokumen.kehadiran', $latestBuktiSurvei->id_bukti_kehadiran) }}" target="_blank" class="font-bold text-emerald-700 hover:underline">
                            Lihat Foto &rarr;
                        </a>
                    </div>
                    @if ($latestBuktiSurvei->status_verifikasi === 'Ditolak')
                        <div class="rounded-lg bg-rose-50 border border-rose-200 p-2.5 text-rose-900">
                            <span class="font-bold block text-[11px]">Alasan Penolakan Admin:</span>
                            <p class="mt-0.5">{{ $latestBuktiSurvei->alasan_penolakan ?? 'Foto tidak jelas / tidak valid.' }}</p>
                            <span class="block mt-1 font-semibold text-rose-700 text-[10px]">Silakan ambil dan unggah ulang foto kehadiran survei di bawah.</span>
                        </div>
                    @endif
                </div>
            @endif

            {{-- Form Upload Bukti Survei (Hanya aktif jika pesanan bukan Cancel, dan belum ada atau Ditolak) --}}
            @if ($pemesanan->status_pengerjaan !== 'Cancel' && (! $latestBuktiSurvei || $latestBuktiSurvei->status_verifikasi === 'Ditolak'))
                <form action="{{ route('teknisi.pemesanan.kehadiran', $pemesanan->id_pemesanan) }}" method="POST" enctype="multipart/form-data" class="space-y-2.5 pt-2 border-t border-slate-200" onsubmit="showLoading(this)">
                    @csrf
                    <input type="hidden" name="jenis_kehadiran" value="Survei">

                    <label class="block text-xs font-bold text-slate-700">
                        {{ $latestBuktiSurvei ? 'Unggah Ulang Foto Kehadiran Survei (Kamera HP)' : 'Ambil Foto Kehadiran di Lokasi Survei (Kamera HP)' }}
                    </label>

                    {{-- File Input Kamera Langsung + Preview --}}
                    <div>
                        <input type="file" name="foto" accept="image/*" capture="environment" required
                            class="block w-full text-xs text-slate-500 file:mr-3 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-emerald-100 file:text-emerald-800 hover:file:bg-emerald-200 cursor-pointer"
                            onchange="previewImage(this, 'preview-survei')">
                        <p class="text-[10px] text-slate-400 mt-1">Format: JPG/PNG, Maksimal 15 MB. Di smartphone otomatis membuka kamera.</p>
                        <div id="preview-survei" class="hidden mt-2 rounded-xl overflow-hidden max-h-48 border border-slate-200"></div>
                    </div>

                    <div>
                        <input type="text" name="catatan" placeholder="Catatan opsional kehadiran survei..."
                            class="w-full rounded-xl border-slate-300 px-4 py-2.5 text-xs sm:text-sm text-slate-800 placeholder-slate-400 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 transition">
                    </div>

                    <button type="submit" class="w-full py-3 px-4 rounded-xl bg-emerald-700 hover:bg-emerald-800 text-white font-bold text-xs shadow-md transition flex items-center justify-center gap-2">
                        <span>{{ $latestBuktiSurvei ? 'Kirim Revisi Foto Survei' : 'Kirim Foto Kehadiran Survei' }}</span>
                    </button>
                </form>
            @elseif($latestBuktiSurvei?->status_verifikasi === 'Menunggu')
                <div class="rounded-xl bg-amber-50 p-2.5 border border-amber-200 text-xs text-amber-900 font-medium">
                    Foto kehadiran survei sedang menunggu verifikasi admin. Tahap selanjutnya akan terbuka otomatis setelah disetujui.
                </div>
            @endif
        </div>

        {{-- ========================================================================= --}}
        {{-- TAHAP 2: PENGAJUAN HASIL SURVEI & KESEPAKATAN HARGA --}}
        {{-- ========================================================================= --}}
        <div class="rounded-2xl border {{ $tahapAktif === 2 ? 'border-2 border-emerald-500 bg-emerald-50/20' : 'border-slate-200 bg-slate-50/60' }} p-4 space-y-3">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="flex h-7 w-7 items-center justify-center rounded-full {{ $latestSurvei?->status_verifikasi === 'Disetujui' ? 'bg-emerald-600 text-white' : ($tahapAktif === 2 ? 'bg-emerald-700 text-white' : 'bg-slate-300 text-slate-600') }} font-bold text-xs">
                        2
                    </span>
                    <h4 class="text-sm font-bold text-slate-800">Hasil Survei & Jadwal Pengerjaan</h4>
                </div>

                @if ($latestSurvei)
                    @php
                        $badgeHasil = match($latestSurvei->status_verifikasi) {
                            'Disetujui' => 'bg-emerald-100 text-emerald-800 border-emerald-200',
                            'Ditolak' => 'bg-rose-100 text-rose-800 border-rose-200',
                            default => 'bg-amber-100 text-amber-800 border-amber-200',
                        };
                    @endphp
                    <span class="rounded-full border px-2.5 py-0.5 text-[11px] font-bold {{ $badgeHasil }}">
                        {{ $latestSurvei->status_verifikasi }}
                    </span>
                @else
                    <span class="text-[11px] text-slate-400 font-semibold">Terkunci</span>
                @endif
            </div>

            @if ($latestSurvei)
                <div class="rounded-xl bg-white p-3 border border-slate-200 text-xs space-y-1.5">
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <span class="text-slate-400 block text-[10px]">Tgl Pengerjaan Diajukan</span>
                            <span class="font-bold text-slate-800">{{ \Carbon\Carbon::parse($latestSurvei->tgl_pengerjaan_diajukan)->format('d M Y') }} ({{ $latestSurvei->jam_pengerjaan_diajukan }})</span>
                        </div>
                        <div>
                            <span class="text-slate-400 block text-[10px]">Harga Kesepakatan Diajukan</span>
                            <span class="font-bold text-emerald-700">Rp {{ number_format($latestSurvei->harga_diajukan, 0, ',', '.') }}</span>
                        </div>
                    </div>

                    @if ($latestSurvei->status_verifikasi === 'Ditolak')
                        <div class="rounded-lg bg-rose-50 border border-rose-200 p-2.5 text-rose-900 mt-2">
                            <span class="font-bold block text-[11px]">Alasan Penolakan Admin:</span>
                            <p class="mt-0.5">{{ $latestSurvei->alasan_penolakan ?? 'Harga atau tanggal tidak sesuai kesepakatan kantor.' }}</p>
                            <span class="block mt-1 font-semibold text-rose-700 text-[10px]">Silakan diskusikan kembali dengan pelanggan dan ajukan ulang di bawah.</span>
                        </div>
                    @endif
                </div>
            @endif

            {{-- Form Input Hasil Survei (Hanya aktif jika bukti survei Disetujui, dan belum ada hasil atau Ditolak) --}}
            @if ($pemesanan->status_pengerjaan !== 'Cancel' && $latestBuktiSurvei?->status_verifikasi === 'Disetujui' && (! $latestSurvei || $latestSurvei->status_verifikasi === 'Ditolak'))
                <form action="{{ route('teknisi.pemesanan.hasil-survei', $pemesanan->id_pemesanan) }}" method="POST" class="space-y-3 pt-2 border-t border-slate-200" onsubmit="showLoading(this)">
                    @csrf
                    <div class="rounded-xl bg-emerald-50/80 p-2.5 border border-emerald-100 text-xs text-emerald-900">
                        <div class="flex items-center justify-between">
                            <span class="font-bold block text-[11px]">Acuan Negosiasi Pelanggan:</span>
                            <span class="text-[10px] font-extrabold px-2 py-0.5 rounded border {{ ($pemesanan->tipe_area ?? 'Indoor') === 'Outdoor' ? 'bg-amber-100 text-amber-800 border-amber-300' : 'bg-emerald-100 text-emerald-800 border-emerald-300' }}">
                                {{ ($pemesanan->tipe_area ?? 'Indoor') === 'Outdoor' ? 'Area Outdoor' : 'Area Indoor' }}
                            </span>
                        </div>
                        <span class="block mt-1">Estimasi awal: <b>Rp {{ number_format($pemesanan->harga_estimasi, 0, ',', '.') }}</b>. Masukkan nominal harga yang disepakati dengan pelanggan saat survei di lapangan.</span>
                    </div>

                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-[11px] font-bold text-slate-700 mb-1">Tgl Pengerjaan *</label>
                            <input type="date" name="tgl_pengerjaan_diajukan" min="{{ date('Y-m-d') }}" required
                                value="{{ old('tgl_pengerjaan_diajukan', $latestSurvei?->tgl_pengerjaan_diajukan) }}"
                                class="w-full rounded-xl border-slate-300 px-3.5 py-2.5 text-xs text-slate-800 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-slate-700 mb-1">Jam Pengerjaan *</label>
                            <input type="time" name="jam_pengerjaan_diajukan" required
                                value="{{ old('jam_pengerjaan_diajukan', $latestSurvei?->jam_pengerjaan_diajukan ?? '09:00') }}"
                                class="w-full rounded-xl border-slate-300 px-3.5 py-2.5 text-xs text-slate-800 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                        </div>
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold text-slate-700 mb-1">Harga Kesepakatan Final (Rp) *</label>
                        <input type="number" name="harga_diajukan" step="10000" min="10000" required
                            value="{{ old('harga_diajukan', $latestSurvei?->harga_diajukan ?? $pemesanan->harga_estimasi) }}"
                            class="w-full rounded-xl border-slate-300 px-4 py-2.5 text-xs sm:text-sm font-bold text-emerald-900 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                    </div>

                    <div>
                        <input type="text" name="catatan_survei" placeholder="Catatan kesepakatan teknis (opsional)..."
                            class="w-full rounded-xl border-slate-300 px-4 py-2.5 text-xs sm:text-sm text-slate-800 placeholder-slate-400 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 transition">
                    </div>

                    <button type="submit" class="w-full py-3 px-4 rounded-xl bg-emerald-700 hover:bg-emerald-800 text-white font-bold text-xs shadow-md transition flex items-center justify-center gap-2">
                        <span>{{ $latestSurvei ? 'Kirim Revisi Hasil Survei & Harga' : 'Ajukan Hasil Survei & Harga Kesepakatan' }}</span>
                    </button>
                </form>
            @elseif($latestSurvei?->status_verifikasi === 'Menunggu')
                <div class="rounded-xl bg-amber-50 p-2.5 border border-amber-200 text-xs text-amber-900 font-medium">
                    Pengajuan hasil survei dan jadwal pengerjaan sedang menunggu persetujuan admin.
                </div>
            @endif
        </div>

        {{-- ========================================================================= --}}
        {{-- TAHAP 3: MULAI PENGERJAAN (FOTO KEHADIRAN PENGERJAAN) --}}
        {{-- ========================================================================= --}}
        <div class="rounded-2xl border {{ $tahapAktif === 3 ? 'border-2 border-emerald-500 bg-emerald-50/20' : 'border-slate-200 bg-slate-50/60' }} p-4 space-y-3">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="flex h-7 w-7 items-center justify-center rounded-full {{ $latestBuktiPengerjaan?->status_verifikasi === 'Disetujui' || $pemesanan->status_pengerjaan === 'On-Going' ? 'bg-emerald-600 text-white' : ($tahapAktif === 3 ? 'bg-emerald-700 text-white' : 'bg-slate-300 text-slate-600') }} font-bold text-xs">
                        3
                    </span>
                    <h4 class="text-sm font-bold text-slate-800">Mulai Pengerjaan (Treatment)</h4>
                </div>

                @if ($latestBuktiPengerjaan)
                    @php
                        $badgeKerja = match($latestBuktiPengerjaan->status_verifikasi) {
                            'Disetujui' => 'bg-emerald-100 text-emerald-800 border-emerald-200',
                            'Ditolak' => 'bg-rose-100 text-rose-800 border-rose-200',
                            default => 'bg-amber-100 text-amber-800 border-amber-200',
                        };
                    @endphp
                    <span class="rounded-full border px-2.5 py-0.5 text-[11px] font-bold {{ $badgeKerja }}">
                        {{ $latestBuktiPengerjaan->status_verifikasi }}
                    </span>
                @else
                    <span class="text-[11px] text-slate-400 font-semibold">Terkunci</span>
                @endif
            </div>

            @if ($latestBuktiPengerjaan)
                <div class="rounded-xl bg-white p-3 border border-slate-200 text-xs space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-slate-500">Diupload: {{ $latestBuktiPengerjaan->created_at->format('d M Y, H:i') }}</span>
                        <a href="{{ route('dokumen.kehadiran', $latestBuktiPengerjaan->id_bukti_kehadiran) }}" target="_blank" class="font-bold text-emerald-700 hover:underline">
                            Lihat Foto Pengerjaan &rarr;
                        </a>
                    </div>
                    @if ($latestBuktiPengerjaan->status_verifikasi === 'Ditolak')
                        <div class="rounded-lg bg-rose-50 border border-rose-200 p-2.5 text-rose-900">
                            <span class="font-bold block text-[11px]">Alasan Penolakan Admin:</span>
                            <p class="mt-0.5">{{ $latestBuktiPengerjaan->alasan_penolakan ?? 'Foto kehadiran pengerjaan ditolak admin.' }}</p>
                            <span class="block mt-1 font-semibold text-rose-700 text-[10px]">Silakan unggah ulang foto di lokasi pengerjaan.</span>
                        </div>
                    @endif
                </div>
            @endif

            {{-- Form Upload Bukti Pengerjaan (Hanya aktif jika hasil survei Disetujui, dan belum ada atau Ditolak) --}}
            @if ($pemesanan->status_pengerjaan !== 'Cancel' && $latestSurvei?->status_verifikasi === 'Disetujui' && (! $latestBuktiPengerjaan || $latestBuktiPengerjaan->status_verifikasi === 'Ditolak'))
                <form action="{{ route('teknisi.pemesanan.kehadiran', $pemesanan->id_pemesanan) }}" method="POST" enctype="multipart/form-data" class="space-y-2.5 pt-2 border-t border-slate-200" onsubmit="showLoading(this)">
                    @csrf
                    <input type="hidden" name="jenis_kehadiran" value="Pengerjaan">

                    <label class="block text-xs font-bold text-slate-700">
                        Foto Kehadiran Saat Mulai Bekerja (Kamera HP) *
                    </label>

                    <div>
                        <input type="file" name="foto" accept="image/*" capture="environment" required
                            class="block w-full text-xs text-slate-500 file:mr-3 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-emerald-100 file:text-emerald-800 hover:file:bg-emerald-200 cursor-pointer"
                            onchange="previewImage(this, 'preview-pengerjaan')">
                        <p class="text-[10px] text-slate-400 mt-1">Setelah disetujui admin, status pesanan otomatis berubah menjadi <b>On-Going</b>.</p>
                        <div id="preview-pengerjaan" class="hidden mt-2 rounded-xl overflow-hidden max-h-48 border border-slate-200"></div>
                    </div>

                    <div>
                        <input type="text" name="catatan" placeholder="Catatan mulai pengerjaan..."
                            class="w-full rounded-xl border-slate-300 px-4 py-2.5 text-xs sm:text-sm text-slate-800 placeholder-slate-400 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 transition">
                    </div>

                    <button type="submit" class="w-full py-3 px-4 rounded-xl bg-sky-700 hover:bg-sky-800 text-white font-bold text-xs shadow-md transition flex items-center justify-center gap-2">
                        <span>{{ $latestBuktiPengerjaan ? 'Kirim Revisi Foto Pengerjaan' : 'Kirim Foto Mulai Pengerjaan' }}</span>
                    </button>
                </form>
            @elseif($latestBuktiPengerjaan?->status_verifikasi === 'Menunggu')
                <div class="rounded-xl bg-amber-50 p-2.5 border border-amber-200 text-xs text-amber-900 font-medium">
                    Bukti kehadiran pengerjaan sedang menunggu persetujuan admin. Status akan otomatis menjadi <b>On-Going</b> saat disetujui.
                </div>
            @endif
        </div>

        {{-- ========================================================================= --}}
        {{-- TAHAP 4: SELESAI PENGERJAAN (WORK ORDER & BUKTI BAYAR TERPISAH) --}}
        {{-- ========================================================================= --}}
        <div class="rounded-2xl border {{ $tahapAktif === 4 ? 'border-2 border-emerald-500 bg-emerald-50/20' : 'border-slate-200 bg-slate-50/60' }} p-4 space-y-4">
            <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                <div class="flex items-center gap-2">
                    <span class="flex h-7 w-7 items-center justify-center rounded-full {{ ($latestWo?->status_verifikasi === 'Disetujui' && $latestBuktiBayar?->status_verifikasi === 'Disetujui') ? 'bg-emerald-600 text-white' : ($tahapAktif === 4 ? 'bg-emerald-700 text-white' : 'bg-slate-300 text-slate-600') }} font-bold text-xs">
                        4
                    </span>
                    <h4 class="text-sm font-bold text-slate-800">Selesai Kerja: WO & Bukti Bayar</h4>
                </div>
                <span class="text-[11px] text-slate-500 font-semibold">After Treatment</span>
            </div>

            <p class="text-xs text-slate-500">Unggah dokumen Work Order (WO) dan bukti pembayaran secara terpisah. Verifikasi dilakukan mandiri untuk masing-masing dokumen.</p>

            {{-- Sub-bagian 4A: Work Order (WO) --}}
            <div class="rounded-2xl bg-white p-3.5 border border-slate-200 space-y-2.5">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-slate-800">
                        Dokumen Work Order (WO)
                    </span>
                    @if ($latestWo)
                        @php
                            $badgeWo = match($latestWo->status_verifikasi) {
                                'Disetujui' => 'bg-emerald-100 text-emerald-800 border-emerald-200',
                                'Ditolak' => 'bg-rose-100 text-rose-800 border-rose-200',
                                default => 'bg-amber-100 text-amber-800 border-amber-200',
                            };
                        @endphp
                        <span class="rounded-full border px-2.5 py-0.5 text-[10px] font-bold {{ $badgeWo }}">
                            {{ $latestWo->status_verifikasi }}
                        </span>
                    @else
                        <span class="text-[10px] text-slate-400 font-semibold">Belum Diunggah</span>
                    @endif
                </div>

                @if ($latestWo)
                    <div class="text-xs space-y-1 bg-slate-50 p-2 rounded-xl">
                        <div class="flex items-center justify-between">
                            <span class="text-slate-500">Diupload: {{ $latestWo->created_at->format('d M Y, H:i') }}</span>
                            <a href="{{ route('dokumen.wo', $latestWo->id_wo) }}" target="_blank" class="font-bold text-emerald-700 hover:underline">
                                Unduh/Buka File WO &rarr;
                            </a>
                        </div>
                        @if ($latestWo->status_verifikasi === 'Ditolak')
                            <div class="rounded-lg bg-rose-50 border border-rose-200 p-2 text-rose-900 mt-1">
                                <span class="font-bold block text-[10px]">Alasan Penolakan WO:</span>
                                <p class="text-[11px]">{{ $latestWo->alasan_penolakan ?? 'Dokumen WO belum ditandatangani atau tidak lengkap.' }}</p>
                            </div>
                        @endif
                    </div>
                @endif

                {{-- Form Upload WO (Hanya aktif jika status On-Going, dan belum ada atau Ditolak) --}}
                @if ($pemesanan->status_pengerjaan === 'On-Going' && (! $latestWo || $latestWo->status_verifikasi === 'Ditolak'))
                    <form action="{{ route('teknisi.pemesanan.wo', $pemesanan->id_pemesanan) }}" method="POST" enctype="multipart/form-data" class="space-y-2 pt-1" onsubmit="showLoading(this)">
                        @csrf
                        <input type="file" name="file_wo" accept=".pdf,image/*" capture="environment" required
                            class="block w-full text-xs text-slate-500 file:mr-3 file:py-2 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-emerald-100 file:text-emerald-800 hover:file:bg-emerald-200 cursor-pointer">
                        <input type="text" name="catatan" placeholder="Catatan dokumen WO (opsional)..."
                            class="w-full rounded-xl border-slate-300 px-4 py-2.5 text-xs sm:text-sm text-slate-800 placeholder-slate-400 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 transition">
                        <button type="submit" class="w-full py-2.5 px-4 rounded-xl bg-slate-800 hover:bg-slate-900 text-white font-bold text-xs shadow-md transition flex items-center justify-center gap-2">
                            <span>{{ $latestWo ? 'Unggah Ulang File WO' : 'Unggah Dokumen WO' }}</span>
                        </button>
                    </form>
                @elseif($latestWo?->status_verifikasi === 'Menunggu')
                    <p class="text-[11px] text-amber-800 bg-amber-50 p-2 rounded-xl">Dokumen WO sedang menunggu verifikasi admin.</p>
                @endif
            </div>

            {{-- Sub-bagian 4B: Bukti Pembayaran (Cash / Transfer) --}}
            <div class="rounded-2xl bg-white p-3.5 border border-slate-200 space-y-2.5">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-slate-800">
                        Bukti Pembayaran Pelanggan
                    </span>
                    @if ($latestBuktiBayar)
                        @php
                            $badgeBayar = match($latestBuktiBayar->status_verifikasi) {
                                'Disetujui' => 'bg-emerald-100 text-emerald-800 border-emerald-200',
                                'Ditolak' => 'bg-rose-100 text-rose-800 border-rose-200',
                                default => 'bg-amber-100 text-amber-800 border-amber-200',
                            };
                        @endphp
                        <span class="rounded-full border px-2.5 py-0.5 text-[10px] font-bold {{ $badgeBayar }}">
                            {{ $latestBuktiBayar->status_verifikasi }}
                        </span>
                    @else
                        <span class="text-[10px] text-slate-400 font-semibold">Belum Diunggah</span>
                    @endif
                </div>

                @if ($latestBuktiBayar)
                    <div class="text-xs space-y-1 bg-slate-50 p-2 rounded-xl">
                        <div class="flex items-center justify-between">
                            <span class="text-slate-700">Metode: <b>{{ $latestBuktiBayar->metode_pembayaran }}</b> ({{ $latestBuktiBayar->created_at->format('d M Y, H:i') }})</span>
                            <a href="{{ route('dokumen.pembayaran', $latestBuktiBayar->id_bukti_pembayaran) }}" target="_blank" class="font-bold text-emerald-700 hover:underline">
                                Lihat Bukti &rarr;
                            </a>
                        </div>
                        @if ($latestBuktiBayar->status_verifikasi === 'Ditolak')
                            <div class="rounded-lg bg-rose-50 border border-rose-200 p-2 text-rose-900 mt-1">
                                <span class="font-bold block text-[10px]">Alasan Penolakan Pembayaran:</span>
                                <p class="text-[11px]">{{ $latestBuktiBayar->alasan_penolakan ?? 'Nominal tidak sesuai atau bukti transfer tidak valid.' }}</p>
                            </div>
                        @endif
                    </div>
                @endif

                {{-- Form Upload Bukti Pembayaran (Hanya aktif jika status On-Going, dan belum ada atau Ditolak) --}}
                @if ($pemesanan->status_pengerjaan === 'On-Going' && (! $latestBuktiBayar || $latestBuktiBayar->status_verifikasi === 'Ditolak'))
                    <form action="{{ route('teknisi.pemesanan.pembayaran', $pemesanan->id_pemesanan) }}" method="POST" enctype="multipart/form-data" class="space-y-2.5 pt-1" onsubmit="showLoading(this)">
                        @csrf
                        <div>
                            <label class="block text-[11px] font-bold text-slate-700 mb-1">Metode Pembayaran *</label>
                            <div class="grid grid-cols-2 gap-2">
                                <label class="flex items-center gap-2 p-2.5 rounded-xl border border-slate-200 cursor-pointer hover:bg-slate-50">
                                    <input type="radio" name="metode_pembayaran" value="Cash" checked class="text-emerald-600 focus:ring-emerald-500">
                                    <span class="text-xs font-bold text-slate-800">Cash (Tunai)</span>
                                </label>
                                <label class="flex items-center gap-2 p-2.5 rounded-xl border border-slate-200 cursor-pointer hover:bg-slate-50">
                                    <input type="radio" name="metode_pembayaran" value="Transfer" class="text-emerald-600 focus:ring-emerald-500">
                                    <span class="text-xs font-bold text-slate-800">Transfer Bank</span>
                                </label>
                            </div>
                        </div>

                        <div>
                            <label class="block text-[11px] font-bold text-slate-700 mb-1">Foto Bukti / Kwitansi *</label>
                            <input type="file" name="file_bukti" accept="image/*,.pdf" capture="environment" required
                                class="block w-full text-xs text-slate-500 file:mr-3 file:py-2 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-emerald-100 file:text-emerald-800 hover:file:bg-emerald-200 cursor-pointer"
                                onchange="previewImage(this, 'preview-bayar')">
                            <div id="preview-bayar" class="hidden mt-2 rounded-xl overflow-hidden max-h-48 border border-slate-200"></div>
                        </div>

                        <div>
                            <input type="text" name="catatan" placeholder="Catatan pembayaran (opsional)..."
                                class="w-full rounded-xl border-slate-300 px-4 py-2.5 text-xs sm:text-sm text-slate-800 placeholder-slate-400 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 transition">
                        </div>

                        <button type="submit" class="w-full py-2.5 px-4 rounded-xl bg-emerald-700 hover:bg-emerald-800 text-white font-bold text-xs shadow-md transition flex items-center justify-center gap-2">
                            <span>{{ $latestBuktiBayar ? 'Unggah Ulang Bukti Pembayaran' : 'Unggah Bukti Pembayaran' }}</span>
                        </button>
                    </form>
                @elseif($latestBuktiBayar?->status_verifikasi === 'Menunggu')
                    <p class="text-[11px] text-amber-800 bg-amber-50 p-2 rounded-xl">Bukti pembayaran sedang diverifikasi admin.</p>
                @endif
            </div>
        </div>

        {{-- ========================================================================= --}}
        {{-- TAHAP 5: PEKERJAAN SELESAI TOTAL (DONE & LUNAS) --}}
        {{-- ========================================================================= --}}
        <div class="rounded-2xl border {{ $pemesanan->status_pengerjaan === 'Done' ? 'border-2 border-emerald-500 bg-emerald-50' : 'border-slate-200 bg-slate-50/60' }} p-4">
            <div class="flex items-center gap-3">
                <span class="flex h-8 w-8 items-center justify-center rounded-full {{ $pemesanan->status_pengerjaan === 'Done' ? 'bg-emerald-600 text-white' : 'bg-slate-300 text-slate-600' }} font-black text-sm">
                    5
                </span>
                <div>
                    <h4 class="text-sm font-black text-slate-900">Pekerjaan Selesai & Lunas</h4>
                    <p class="text-xs text-slate-600 mt-0.5">
                        Otomatis aktif ketika <b>Dokumen WO</b> dan <b>Bukti Pembayaran</b> keduanya telah disetujui admin.
                    </p>
                </div>
            </div>

            @if ($pemesanan->status_pengerjaan === 'Done')
                <div class="mt-3 rounded-2xl bg-white p-3.5 border border-emerald-200 space-y-1.5 text-xs">
                    <div class="flex items-center justify-between font-bold">
                        <span class="text-emerald-800">Status Pekerjaan: DONE (SELESAI)</span>
                        <span class="rounded-full bg-emerald-100 text-emerald-800 px-3 py-0.5">LUNAS</span>
                    </div>
                    <p class="text-slate-500">Selesai pada: {{ $pemesanan->tgl_selesai ? \Carbon\Carbon::parse($pemesanan->tgl_selesai)->format('d M Y, H:i') : '-' }}</p>
                    <p class="text-slate-700">Harga Final: <b>Rp {{ number_format($pemesanan->harga_final ?? $pemesanan->harga_estimasi, 0, ',', '.') }}</b></p>
                    <div class="pt-2 flex items-center gap-2">
                        @if ($latestWo)
                            <a href="{{ route('dokumen.wo', $latestWo->id_wo) }}" target="_blank" class="rounded-xl bg-slate-100 hover:bg-slate-200 px-3 py-1.5 font-bold text-slate-800 transition">
                                Dokumen WO
                            </a>
                        @endif
                        @if ($latestBuktiBayar)
                            <a href="{{ route('dokumen.pembayaran', $latestBuktiBayar->id_bukti_pembayaran) }}" target="_blank" class="rounded-xl bg-slate-100 hover:bg-slate-200 px-3 py-1.5 font-bold text-slate-800 transition">
                                Bukti Bayar
                            </a>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

@push('scripts')
<script>
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

    function showLoading(form) {
        const btn = form.querySelector('button[type="submit"]');
        if (btn) {
            btn.disabled = true;
            btn.classList.add('opacity-75', 'cursor-not-allowed', 'flex', 'items-center', 'justify-center', 'gap-2');
            btn.innerHTML = `<svg class="animate-spin h-4 w-4 text-white shrink-0" width="16" height="16" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg><span>Mengunggah & Memproses...</span>`;
        }
    }
</script>
@endpush
@endsection
