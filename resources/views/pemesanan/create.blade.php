@extends('layouts.app')

@section('title', 'Form Pemesanan Jasa Pest Control - CV. Ambius')
@section('meta_description', 'Pesan jasa pest control profesional CV. Ambius tanpa registrasi/login. Dapatkan estimasi harga otomatis dan pantau status pengerjaan secara real-time.')

@section('content')
<div class="py-12 bg-slate-50 min-h-screen">
    <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
        {{-- Header Form --}}
        <div class="text-center mb-8 space-y-2">
            <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">Formulir Pemesanan Jasa Pest Control</h1>
            <p class="text-xs sm:text-sm text-slate-500 max-w-lg mx-auto">
                Isi data di bawah ini <b>tanpa perlu membuat akun/login</b>. Sistem akan otomatis menghitung estimasi biaya dan memberikan kode pemesanan unik untuk pelacakan.
            </p>
        </div>

        {{-- Form Card --}}
        <div class="rounded-3xl border border-slate-200 bg-white p-6 sm:p-10 shadow-xl relative overflow-hidden">
            <div class="absolute inset-x-0 top-0 h-2 bg-gradient-to-r from-emerald-500 via-teal-500 to-primary-600"></div>

            <form action="{{ route('pemesanan.store') }}" method="POST" class="space-y-6" id="bookingForm">
                @csrf

                {{-- Bagian 1: Data Kontak Pelanggan --}}
                <div class="border-b border-slate-100 pb-4">
                    <h2 class="text-base font-extrabold text-slate-900 flex items-center gap-2.5">
                        <span class="flex h-7 w-7 items-center justify-center rounded-xl bg-emerald-600 text-xs font-bold text-white shadow-sm">1</span>
                        Data Pelanggan & Pemohon
                    </h2>
                    <p class="text-xs text-slate-400 mt-1">Kami memerlukan informasi ini untuk konfirmasi WhatsApp dan koordinasi teknisi.</p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="nama" class="block text-xs font-bold text-slate-700 mb-1">
                            Nama Lengkap <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" name="nama" id="nama" value="{{ old('nama', old('nama_client')) }}" required
                               class="w-full rounded-xl border-slate-300 px-4 py-2.5 text-xs text-slate-900 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 @error('nama') border-rose-400 ring-1 ring-rose-400 @enderror"
                               placeholder="Contoh: Budi Santoso">
                        @error('nama')
                            <p class="mt-1 text-[11px] text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="no_hp" class="block text-xs font-bold text-slate-700 mb-1">
                            Nomor WhatsApp / HP Aktif <span class="text-rose-500">*</span>
                        </label>
                        <input type="tel" name="no_hp" id="no_hp" value="{{ old('no_hp', old('no_telp_client')) }}" required
                               class="w-full rounded-xl border-slate-300 px-4 py-2.5 text-xs text-slate-900 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 @error('no_hp') border-rose-400 ring-1 ring-rose-400 @enderror"
                               placeholder="Contoh: 081234567890">
                        @error('no_hp')
                            <p class="mt-1 text-[11px] text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="email" class="block text-xs font-bold text-slate-700 mb-1">
                            Alamat Email <span class="text-rose-500">*</span>
                        </label>
                        <input type="email" name="email" id="email" value="{{ old('email') }}" required
                               class="w-full rounded-xl border-slate-300 px-4 py-2.5 text-xs text-slate-900 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 @error('email') border-rose-400 ring-1 ring-rose-400 @enderror"
                               placeholder="alamat@email.com">
                        @error('email')
                            <p class="mt-1 text-[11px] text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="nama_usaha" class="block text-xs font-bold text-slate-700 mb-1">
                            Nama Usaha / Perusahaan <span class="text-slate-400 font-normal">(Opsional)</span>
                        </label>
                        <input type="text" name="nama_usaha" id="nama_usaha" value="{{ old('nama_usaha') }}"
                               class="w-full rounded-xl border-slate-300 px-4 py-2.5 text-xs text-slate-900 shadow-sm focus:border-emerald-500 focus:ring-emerald-500"
                               placeholder="Contoh: Kafe Kopi Senja, PT. Maju Bersama">
                    </div>
                </div>

                {{-- Bagian 2: Lokasi & Kendala Hama --}}
                <div class="border-b border-slate-100 pt-4 pb-4">
                    <h2 class="text-base font-extrabold text-slate-900 flex items-center gap-2.5">
                        <span class="flex h-7 w-7 items-center justify-center rounded-xl bg-emerald-600 text-xs font-bold text-white shadow-sm">2</span>
                        Lokasi & Keluhan Hama
                    </h2>
                    <p class="text-xs text-slate-400 mt-1">Detail lokasi dan jenis gangguan hama yang dialami.</p>
                </div>

                <div>
                    <label for="jenis_properti" class="block text-xs font-bold text-slate-700 mb-1">
                        Jenis Properti Bangunan <span class="text-rose-500">*</span>
                    </label>
                    <select name="jenis_properti" id="jenis_properti" required
                            class="w-full rounded-xl border-slate-300 px-4 py-2.5 text-xs text-slate-900 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 @error('jenis_properti') border-rose-400 ring-1 ring-rose-400 @enderror">
                        <option value="Rumah Tinggal" {{ old('jenis_properti') === 'Rumah Tinggal' ? 'selected' : '' }}>Rumah Tinggal / Residensial</option>
                        <option value="Ruko/Restoran" {{ old('jenis_properti') === 'Ruko/Restoran' ? 'selected' : '' }}>Ruko / Restoran / Kafe / F&B</option>
                        <option value="Kantor/Gedung" {{ old('jenis_properti') === 'Kantor/Gedung' ? 'selected' : '' }}>Kantor / Gedung Komersial</option>
                        <option value="Gudang/Pabrik" {{ old('jenis_properti') === 'Gudang/Pabrik' ? 'selected' : '' }}>Gudang / Pabrik / Industri</option>
                        <option value="Lainnya" {{ old('jenis_properti') === 'Lainnya' ? 'selected' : '' }}>Lainnya</option>
                    </select>
                    @error('jenis_properti')
                        <p class="mt-1 text-[11px] text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="alamat_lokasi" class="block text-xs font-bold text-slate-700 mb-1">
                        Alamat Lengkap Lokasi Pengerjaan <span class="text-rose-500">*</span>
                    </label>
                    <textarea name="alamat_lokasi" id="alamat_lokasi" rows="3" required
                              class="w-full rounded-xl border-slate-300 px-4 py-2.5 text-xs text-slate-900 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 @error('alamat_lokasi') border-rose-400 ring-1 ring-rose-400 @enderror"
                              placeholder="Cantumkan jalan, nomor rumah/ruko, RT/RW, kelurahan, kecamatan, dan patokan terdekat...">{{ old('alamat_lokasi', old('alamat_pengerjaan')) }}</textarea>
                    @error('alamat_lokasi')
                        <p class="mt-1 text-[11px] text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="keluhan" class="block text-xs font-bold text-slate-700 mb-1">
                        Keluhan & Gangguan Hama yang Ditemukan <span class="text-rose-500">*</span>
                    </label>
                    <textarea name="keluhan" id="keluhan" rows="2.5" required
                              class="w-full rounded-xl border-slate-300 px-4 py-2.5 text-xs text-slate-900 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 @error('keluhan') border-rose-400 ring-1 ring-rose-400 @enderror"
                              placeholder="Ceritakan kendala hama, misal: rayap di kusen pintu dan plafon, sarang tikus di dapur, kecoa di saluran pembuangan...">{{ old('keluhan') }}</textarea>
                    @error('keluhan')
                        <p class="mt-1 text-[11px] text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Bagian 3: Pilihan Layanan & Jadwal Survei --}}
                <div class="border-b border-slate-100 pt-4 pb-4">
                    <h2 class="text-base font-extrabold text-slate-900 flex items-center gap-2.5">
                        <span class="flex h-7 w-7 items-center justify-center rounded-xl bg-emerald-600 text-xs font-bold text-white shadow-sm">3</span>
                        Pilihan Layanan, Paket / Ukuran Area & Jadwal Survei
                    </h2>
                    <p class="text-xs text-slate-400 mt-1">Pilih jenis penanganan hama, paket / ukuran area, dan tanggal survei lokasi yang Anda kehendaki.</p>
                </div>

                <div>
                    <label for="id_tarif" class="block text-xs font-bold text-slate-700 mb-1">
                        Pilih Paket & Jenis Layanan Pest Control <span class="text-rose-500">*</span>
                    </label>
                    <select name="id_tarif" id="id_tarif" required
                            class="w-full rounded-xl border-slate-300 px-4 py-3 text-xs text-slate-900 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                        <option value="">-- Pilih Layanan Pest Control --</option>
                        @foreach ($tarifList as $tarif)
                            <option value="{{ $tarif->id_tarif }}"
                                    data-tarif="{{ (float)$tarif->tarif_dasar }}"
                                    data-max="{{ (int)($tarif->ukuran_area_max ?? 0) }}"
                                    data-satuan="{{ $tarif->satuan }}"
                                    {{ old('id_tarif') == $tarif->id_tarif ? 'selected' : '' }}>
                                {{ $tarif->jenis_layanan }} ({{ $tarif->nama_paket ?: 'Standar' }}) - Tarif Dasar: Rp {{ number_format($tarif->tarif_dasar, 0, ',', '.') }} / {{ $tarif->satuan }}
                            </option>
                        @endforeach
                    </select>
                    @error('id_tarif')
                        <p class="mt-1 text-[11px] text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="ukuran_area" class="block text-xs font-bold text-slate-700 mb-1">
                        Estimasi Luas Area (m2) <span class="text-rose-500">*</span>
                    </label>
                    <input type="number" name="ukuran_area" id="ukuran_area" value="{{ old('ukuran_area', 100) }}" min="1" step="any" required
                           class="w-full rounded-xl border-slate-300 px-4 py-2.5 text-xs text-slate-900 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 @error('ukuran_area') border-rose-400 ring-1 ring-rose-400 @enderror"
                           placeholder="Contoh: 120">
                    <p class="mt-1 text-[11px] text-slate-400">Estimasi luas area bangunan atau lahan yang membutuhkan penanganan hama.</p>
                    @error('ukuran_area')
                        <p class="mt-1 text-[11px] text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Pilihan Area Indoor / Outdoor --}}
                @php
                    $areaIndoor = $tarifAreas['indoor'] ?? null;
                    $areaOutdoor = $tarifAreas['outdoor'] ?? null;
                    $biayaIndoor = $areaIndoor ? (float) $areaIndoor->biaya_tambahan : 0;
                    $biayaOutdoor = $areaOutdoor ? (float) $areaOutdoor->biaya_tambahan : 100000;
                    $ketIndoor = $areaIndoor?->keterangan ?: 'Rumah, kantor, kamar, restoran, gudang';
                    $ketOutdoor = $areaOutdoor?->keterangan ?: 'Halaman, taman, perimeter luar, pekarangan';
                @endphp
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">
                        Pilihan Area Penanganan <span class="text-rose-500">*</span>
                    </label>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <label class="relative flex items-center justify-between p-3.5 rounded-2xl border-2 cursor-pointer transition select-none has-[:checked]:border-emerald-500 has-[:checked]:bg-emerald-50/60 border-slate-200 hover:border-slate-300 bg-white shadow-sm">
                            <div class="flex items-center gap-3">
                                <input type="radio" name="tipe_area" value="Indoor" class="h-4 w-4 text-emerald-600 focus:ring-emerald-500 border-slate-300" {{ old('tipe_area', 'Indoor') === 'Indoor' ? 'checked' : '' }}>
                                <div>
                                    <span class="block text-xs font-bold text-slate-900">Indoor (Dalam Ruangan)</span>
                                    <span class="block text-[11px] text-slate-500 mt-0.5">{{ $ketIndoor }}</span>
                                </div>
                            </div>
                            <span class="text-[10px] font-extrabold text-emerald-800 bg-emerald-100 px-2 py-0.5 rounded-md border border-emerald-200 whitespace-nowrap ml-2">
                                {{ $biayaIndoor > 0 ? '+ Rp ' . number_format($biayaIndoor, 0, ',', '.') : 'Tarif Tetap' }}
                            </span>
                        </label>

                        <label class="relative flex items-center justify-between p-3.5 rounded-2xl border-2 cursor-pointer transition select-none has-[:checked]:border-emerald-500 has-[:checked]:bg-emerald-50/60 border-slate-200 hover:border-slate-300 bg-white shadow-sm">
                            <div class="flex items-center gap-3">
                                <input type="radio" name="tipe_area" value="Outdoor" class="h-4 w-4 text-emerald-600 focus:ring-emerald-500 border-slate-300" {{ old('tipe_area') === 'Outdoor' ? 'checked' : '' }}>
                                <div>
                                    <span class="block text-xs font-bold text-slate-900">Outdoor (Luar Ruangan)</span>
                                    <span class="block text-[11px] text-slate-500 mt-0.5">{{ $ketOutdoor }}</span>
                                </div>
                            </div>
                            <span class="text-[10px] font-extrabold text-amber-800 bg-amber-100 px-2 py-0.5 rounded-md border border-amber-200 whitespace-nowrap ml-2">
                                + Rp {{ number_format($biayaOutdoor, 0, ',', '.') }}
                            </span>
                        </label>
                    </div>
                    <p class="mt-1 text-[11px] text-slate-400">
                        Pilih Outdoor jika lokasi penanganan mencakup area luar/terbuka (+Rp {{ number_format($biayaOutdoor, 0, ',', '.') }}).
                    </p>
                    @error('tipe_area')
                        <p class="mt-1 text-[11px] text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="tgl_survei_diminta" class="block text-xs font-bold text-slate-700 mb-1">
                            Tanggal Survei Lapangan yang Diinginkan <span class="text-rose-500">*</span>
                        </label>
                        <input type="date" name="tgl_survei_diminta" id="tgl_survei_diminta"
                               min="{{ date('Y-m-d') }}"
                               value="{{ old('tgl_survei_diminta', old('tanggal_survey', date('Y-m-d', strtotime('+1 day')))) }}" required
                               class="w-full rounded-xl border-slate-300 px-4 py-2.5 text-xs text-slate-900 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                        @error('tgl_survei_diminta')
                            <p class="mt-1 text-[11px] text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    @php
                        $rawJam = old('jam_survei_diminta', '09:00');
                        $parts = explode(':', $rawJam);
                        $defaultHour = str_pad($parts[0] ?? '09', 2, '0', STR_PAD_LEFT);
                        $defaultMinute = str_pad($parts[1] ?? '00', 2, '0', STR_PAD_LEFT);
                    @endphp

                    <div class="relative" id="time_picker_container">
                        <label class="block text-xs font-bold text-slate-700 mb-1">
                            Jam Kunjungan Survei (Format 24 Jam - WITA) <span class="text-rose-500">*</span>
                        </label>

                        {{-- Trigger Button yang kompak & serasi dengan input Tanggal --}}
                        <button type="button" id="time_picker_trigger"
                                class="w-full flex items-center justify-between rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-xs text-slate-900 shadow-sm hover:border-emerald-500 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition text-left">
                            <span id="display_time_btn" class="font-extrabold text-slate-900 text-xs">
                                {{ $defaultHour }}:{{ $defaultMinute }} WITA
                            </span>
                            <span class="flex items-center gap-1 text-[11px] font-bold text-emerald-700 bg-emerald-50 hover:bg-emerald-100 px-2.5 py-1 rounded-lg transition">
                                <span>Pilih Jam</span>
                                <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </span>
                        </button>

                        {{-- Hidden input yang dikirim ke backend --}}
                        <input type="hidden" name="jam_survei_diminta" id="jam_survei_diminta" value="{{ $defaultHour }}:{{ $defaultMinute }}">

                        {{-- Popover Dropdown Scrollable Time Picker (Tanpa AM/PM, Full 24 Jam) --}}
                        <div id="time_picker_popover" class="hidden absolute left-0 right-0 z-30 mt-1.5 rounded-2xl border border-slate-200 bg-white p-3 shadow-2xl">
                            <div class="flex items-center justify-between pb-2 border-b border-slate-100 mb-2">
                                <span class="text-[11px] font-bold text-slate-800">
                                    Gulir / Pilih Waktu (24 Jam)
                                </span>
                                <span class="text-[10px] font-black text-emerald-700 bg-emerald-100/80 px-2 py-0.5 rounded-md">WITA</span>
                            </div>

                            <div class="grid grid-cols-2 gap-3 text-center">
                                {{-- Kolom Scroll Jam (06:00 sampai 22:00) --}}
                                <div>
                                    <span class="text-[10px] font-bold text-slate-500 uppercase tracking-wider block mb-1">Jam</span>
                                    <div id="scroll_col_jam" class="h-44 overflow-y-auto space-y-1 p-1 bg-slate-50/70 rounded-xl border border-slate-100 scroll-smooth">
                                        @for ($h = 6; $h <= 22; $h++)
                                            @php $hVal = sprintf('%02d', $h); @endphp
                                            <button type="button" data-hour="{{ $hVal }}"
                                                    class="hour-opt w-full py-1.5 text-xs font-bold rounded-lg transition {{ $defaultHour === $hVal ? 'bg-emerald-600 text-white shadow-sm' : 'text-slate-700 hover:bg-slate-200/80' }}">
                                                {{ $hVal }}:00
                                            </button>
                                        @endfor
                                    </div>
                                </div>

                                {{-- Kolom Scroll Menit (:00 sampai :55) --}}
                                <div>
                                    <span class="text-[10px] font-bold text-slate-500 uppercase tracking-wider block mb-1">Menit</span>
                                    <div id="scroll_col_menit" class="h-44 overflow-y-auto space-y-1 p-1 bg-slate-50/70 rounded-xl border border-slate-100 scroll-smooth">
                                        @foreach (['00', '05', '10', '15', '20', '25', '30', '35', '40', '45', '50', '55'] as $mVal)
                                            <button type="button" data-minute="{{ $mVal }}"
                                                    class="minute-opt w-full py-1.5 text-xs font-bold rounded-lg transition {{ $defaultMinute === $mVal ? 'bg-emerald-600 text-white shadow-sm' : 'text-slate-700 hover:bg-slate-200/80' }}">
                                                :{{ $mVal }}
                                            </button>
                                        @endforeach
                                    </div>
                                </div>
                            </div>

                            <div class="pt-2.5 mt-2.5 border-t border-slate-100 flex items-center justify-between">
                                <div class="text-[11px] text-slate-500">
                                    Terpilih: <strong id="popover_preview_text" class="text-emerald-700 font-extrabold">{{ $defaultHour }}:{{ $defaultMinute }} WITA</strong>
                                </div>
                                <button type="button" id="btn_confirm_time"
                                        class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-extrabold rounded-xl shadow-sm transition">
                                    Selesai
                                </button>
                            </div>
                        </div>

                        <p class="mt-1 text-[11px] text-slate-400">Klik untuk menggulir jam & menit kedatangan teknisi (13:00, 14:00, dst.).</p>
                        @error('jam_survei_diminta')
                            <p class="mt-1 text-[11px] text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                {{-- Box Kalkulasi Estimasi Harga Otomatis --}}
                <div class="rounded-2xl border border-emerald-200 bg-gradient-to-br from-emerald-50 to-teal-50 p-5 shadow-sm">
                    <div class="flex items-center justify-between">
                        <div>
                            <span class="text-[11px] font-bold text-emerald-800 uppercase tracking-wider block">Estimasi Biaya Otomatis</span>
                            <p class="text-xs text-slate-600 mt-0.5">Dihitung otomatis: (tarif per m² × estimasi luas) + biaya area.</p>
                        </div>
                        <div class="text-right">
                            <div class="text-xl sm:text-2xl font-black text-emerald-800" id="displayEstimasi">Rp 0</div>
                            <span class="text-[10px] text-emerald-700 font-semibold block" id="displayKalkulasiBreakdown">Belum termasuk diskon khusus negosiasi</span>
                        </div>
                    </div>
                </div>

                {{-- Submit Button --}}
                <div class="pt-4 border-t border-slate-100 space-y-3">
                    <button type="submit" class="w-full py-3.5 px-6 rounded-2xl bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 text-white font-extrabold text-sm shadow-md hover:shadow-lg transition-all duration-200 flex items-center justify-center">
                        Kirim Pemesanan & Dapatkan Kode Unik
                    </button>
                    <p class="text-center text-[11px] text-slate-400">
                        Dengan mengirimkan formulir ini, Anda menyetujui jadwal survei lokasi oleh teknisi bersertifikat CV. Ambius Indonesia.
                    </p>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const selectTarif = document.getElementById('id_tarif');
        const inputUkuran = document.getElementById('ukuran_area');
        const radioTipeArea = document.querySelectorAll('input[name="tipe_area"]');
        const displayEstimasi = document.getElementById('displayEstimasi');
        const displayKalkulasiBreakdown = document.getElementById('displayKalkulasiBreakdown');

        const BIAYA_INDOOR = {{ $biayaIndoor }};
        const BIAYA_OUTDOOR = {{ $biayaOutdoor }};

        function getSelectedTipeArea() {
            for (const radio of radioTipeArea) {
                if (radio.checked) return radio.value;
            }
            return 'Indoor';
        }

        function updateEstimation() {
            const selectedOpt = selectTarif.options[selectTarif.selectedIndex];
            if (!selectedOpt || !selectedOpt.value) {
                displayEstimasi.textContent = 'Pilih Layanan';
                if (displayKalkulasiBreakdown) {
                    displayKalkulasiBreakdown.textContent = 'Silakan pilih layanan';
                }
                return;
            }

            const tarifDasar = parseFloat(selectedOpt.getAttribute('data-tarif')) || 0;
            const userArea = parseFloat(inputUkuran.value) || 0;
            const tipeArea = getSelectedTipeArea();
            const biayaTambahan = (tipeArea === 'Outdoor') ? BIAYA_OUTDOOR : BIAYA_INDOOR;

            let finalEst = 0;
            let breakdownText = '';

            if (userArea > 0) {
                const subtotal = tarifDasar * userArea;
                finalEst = subtotal + biayaTambahan;
                if (biayaTambahan > 0) {
                    breakdownText = 'Rp ' + Math.round(tarifDasar).toLocaleString('id-ID') + ' × ' + userArea + ' m² + Rp ' + Math.round(biayaTambahan).toLocaleString('id-ID') + ' (' + tipeArea + ')';
                } else {
                    breakdownText = 'Rp ' + Math.round(tarifDasar).toLocaleString('id-ID') + ' × ' + userArea + ' m²';
                }
            } else {
                finalEst = tarifDasar + biayaTambahan;
                if (biayaTambahan > 0) {
                    breakdownText = 'Tarif dasar + Rp ' + Math.round(biayaTambahan).toLocaleString('id-ID') + ' (' + tipeArea + ')';
                } else {
                    breakdownText = 'Tarif dasar (masukkan luas area)';
                }
            }

            displayEstimasi.textContent = 'Rp ' + Math.round(finalEst).toLocaleString('id-ID');
            if (displayKalkulasiBreakdown) {
                displayKalkulasiBreakdown.textContent = breakdownText;
            }
        }

        selectTarif.addEventListener('change', updateEstimation);
        inputUkuran.addEventListener('input', updateEstimation);
        inputUkuran.addEventListener('change', updateEstimation);
        radioTipeArea.forEach(r => r.addEventListener('change', updateEstimation));

        // Run initial
        updateEstimation();

        // Logic Popover Scrollable Time Picker (24 Jam - WITA)
        const timePickerContainer = document.getElementById('time_picker_container');
        const timePickerTrigger = document.getElementById('time_picker_trigger');
        const timePickerPopover = document.getElementById('time_picker_popover');
        const hiddenJam = document.getElementById('jam_survei_diminta');
        const displayTimeBtn = document.getElementById('display_time_btn');
        const popoverPreviewText = document.getElementById('popover_preview_text');
        const btnConfirmTime = document.getElementById('btn_confirm_time');

        let currentHour = "{{ $defaultHour }}";
        let currentMinute = "{{ $defaultMinute }}";

        function updateTimeDisplay() {
            const timeFormatted = currentHour + ':' + currentMinute;
            if (hiddenJam) hiddenJam.value = timeFormatted;
            if (displayTimeBtn) displayTimeBtn.textContent = timeFormatted + ' WITA';
            if (popoverPreviewText) popoverPreviewText.textContent = timeFormatted + ' WITA';
        }

        function scrollToActive() {
            if (!timePickerPopover) return;
            const activeH = timePickerPopover.querySelector('.hour-opt.bg-emerald-600');
            const activeM = timePickerPopover.querySelector('.minute-opt.bg-emerald-600');
            if (activeH) activeH.scrollIntoView({ block: 'center', behavior: 'smooth' });
            if (activeM) activeM.scrollIntoView({ block: 'center', behavior: 'smooth' });
        }

        if (timePickerTrigger && timePickerPopover) {
            timePickerTrigger.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                const isHidden = timePickerPopover.classList.contains('hidden');
                if (isHidden) {
                    timePickerPopover.classList.remove('hidden');
                    setTimeout(scrollToActive, 60);
                } else {
                    timePickerPopover.classList.add('hidden');
                }
            });

            document.addEventListener('click', function (e) {
                if (timePickerContainer && !timePickerContainer.contains(e.target)) {
                    timePickerPopover.classList.add('hidden');
                }
            });

            if (btnConfirmTime) {
                btnConfirmTime.addEventListener('click', function (e) {
                    e.preventDefault();
                    timePickerPopover.classList.add('hidden');
                });
            }

            // Klik Jam (Hour Option)
            timePickerPopover.querySelectorAll('.hour-opt').forEach(function (btn) {
                btn.addEventListener('click', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    timePickerPopover.querySelectorAll('.hour-opt').forEach(function (b) {
                        b.classList.remove('bg-emerald-600', 'text-white', 'shadow-sm');
                        b.classList.add('text-slate-700', 'hover:bg-slate-200/80');
                    });
                    this.classList.add('bg-emerald-600', 'text-white', 'shadow-sm');
                    this.classList.remove('text-slate-700', 'hover:bg-slate-200/80');
                    currentHour = this.getAttribute('data-hour');
                    updateTimeDisplay();
                });
            });

            // Klik Menit (Minute Option)
            timePickerPopover.querySelectorAll('.minute-opt').forEach(function (btn) {
                btn.addEventListener('click', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    timePickerPopover.querySelectorAll('.minute-opt').forEach(function (b) {
                        b.classList.remove('bg-emerald-600', 'text-white', 'shadow-sm');
                        b.classList.add('text-slate-700', 'hover:bg-slate-200/80');
                    });
                    this.classList.add('bg-emerald-600', 'text-white', 'shadow-sm');
                    this.classList.remove('text-slate-700', 'hover:bg-slate-200/80');
                    currentMinute = this.getAttribute('data-minute');
                    updateTimeDisplay();
                });
            });

            updateTimeDisplay();
        }
    });
</script>
@endpush
@endsection
