@extends('layouts.admin')

@section('title', 'Admin Dashboard - CV. Ambius')
@section('page_title', 'Dashboard Ringkasan')

@section('content')
<div class="space-y-8">
    {{-- Monthly Stats Banner --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        {{-- MTD Revenue --}}
        <div class="rounded-2xl border border-primary-800 bg-gradient-to-br from-primary-950 to-primary-900 p-6 text-white shadow-md">
            <span class="text-xs font-semibold text-primary-100 uppercase tracking-wider block">Pendapatan Bulan Ini (Lunas)</span>
            <span class="text-3xl font-extrabold block mt-2">Rp {{ number_format($currentMonthRevenue, 0, ',', '.') }}</span>
            <span class="text-xs text-primary-200 mt-2 block font-medium">Periode: {{ $monthName }}</span>
        </div>

        {{-- MTD Order Count --}}
        <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-md">
            <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider block">Pemesanan Masuk Bulan Ini</span>
            <span class="text-2xl font-extrabold text-gray-900 block mt-2">{{ $currentMonthOrdersCount }} Order</span>
            <span class="text-[10px] text-gray-500 mt-2 block">Seluruh status pemesanan</span>
        </div>

        {{-- Quick WhatsApp --}}
        <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-md flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider block">Nomor Kontak WhatsApp</span>
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                        Aktif
                    </span>
                </div>
                <span class="text-2xl font-extrabold text-gray-900 block mt-2 tracking-tight">+{{ config('pestcontrol.whatsapp_number') }}</span>
            </div>
            <span class="text-[10px] text-gray-500 mt-2 block font-medium">Terhubung di Landing Page Pelanggan</span>
        </div>
    </div>

    {{-- Antrean Verifikasi Lapangan & Dokumen --}}
    <div>
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-bold text-gray-800 uppercase tracking-wider">
                Antrean Menunggu Verifikasi Admin
            </h3>
            <span class="text-xs bg-amber-50 text-amber-800 border border-amber-200 px-2.5 py-0.5 rounded-full font-bold">
                {{ $counts['pending_verifikasi'] + array_sum($pendingVerifications) }} Perlu Tindakan
            </span>
        </div>
        <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
            {{-- Pemesanan Baru --}}
            <a href="{{ route('admin.pemesanan.index', ['status' => 'Pending']) }}" class="rounded-xl border border-amber-200 bg-amber-50/60 p-4 shadow-sm hover:shadow-md transition block">
                <span class="text-[11px] font-bold text-amber-800 uppercase block">Pesanan Baru</span>
                <span class="text-2xl font-black text-amber-900 mt-1 block">{{ $counts['pending_verifikasi'] }}</span>
                <span class="text-[10px] text-amber-700 mt-0.5 block">Belum verifikasi awal</span>
            </a>

            {{-- Kehadiran --}}
            <div class="rounded-xl border border-gray-150 bg-white p-4 shadow-sm hover:shadow-md transition">
                <span class="text-[11px] font-bold text-gray-500 uppercase block">Foto Kehadiran</span>
                <span class="text-2xl font-black text-gray-900 mt-1 block">{{ $pendingVerifications['kehadiran'] }}</span>
                <span class="text-[10px] text-gray-400 mt-0.5 block">Survei & Pengerjaan</span>
            </div>

            {{-- Hasil Survei --}}
            <div class="rounded-xl border border-gray-150 bg-white p-4 shadow-sm hover:shadow-md transition">
                <span class="text-[11px] font-bold text-gray-500 uppercase block">Hasil Survei</span>
                <span class="text-2xl font-black text-gray-900 mt-1 block">{{ $pendingVerifications['hasil_survei'] }}</span>
                <span class="text-[10px] text-gray-400 mt-0.5 block">Nego harga & tanggal</span>
            </div>

            {{-- Work Order --}}
            <div class="rounded-xl border border-gray-150 bg-white p-4 shadow-sm hover:shadow-md transition">
                <span class="text-[11px] font-bold text-gray-500 uppercase block">Work Order (WO)</span>
                <span class="text-2xl font-black text-gray-900 mt-1 block">{{ $pendingVerifications['work_order'] }}</span>
                <span class="text-[10px] text-gray-400 mt-0.5 block">Laporan pengerjaan</span>
            </div>

            {{-- Bukti Pembayaran --}}
            <div class="rounded-xl border border-gray-150 bg-white p-4 shadow-sm hover:shadow-md transition col-span-2 md:col-span-1">
                <span class="text-[11px] font-bold text-gray-500 uppercase block">Bukti Pembayaran</span>
                <span class="text-2xl font-black text-gray-900 mt-1 block">{{ $pendingVerifications['pembayaran'] }}</span>
                <span class="text-[10px] text-gray-400 mt-0.5 block">Verifikasi mutasi/lunas</span>
            </div>
        </div>
    </div>

    {{-- Status Counts Grid --}}
    <div>
        <h3 class="text-sm font-bold text-gray-800 uppercase tracking-wider mb-4">Status Pemesanan Jasa</h3>
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4">
            {{-- Belum Survey Card --}}
            <div class="rounded-xl border border-amber-200 bg-amber-50/50 p-4 shadow-sm transition hover:shadow-md flex items-center justify-between">
                <div>
                    <span class="text-xs font-bold text-amber-800 block">Belum Survey</span>
                    <span class="text-2xl font-black text-amber-950 block mt-1">{{ $counts['pending_belum_survey'] }}</span>
                </div>
                <div class="h-3 w-3 rounded-full bg-amber-500 ring-4 ring-amber-100" title="Belum Survey"></div>
            </div>

            {{-- Sudah Survey Card --}}
            <div class="rounded-xl border border-orange-200 bg-orange-50/50 p-4 shadow-sm transition hover:shadow-md flex items-center justify-between">
                <div>
                    <span class="text-xs font-bold text-orange-800 block">Sudah Survey</span>
                    <span class="text-2xl font-black text-orange-950 block mt-1">{{ $counts['pending_sudah_survey'] }}</span>
                </div>
                <div class="h-3 w-3 rounded-full bg-orange-500 ring-4 ring-orange-100" title="Sudah Survey"></div>
            </div>

            {{-- Ongoing Card --}}
            <div class="rounded-xl border border-blue-200 bg-blue-50/50 p-4 shadow-sm transition hover:shadow-md flex items-center justify-between">
                <div>
                    <span class="text-xs font-bold text-blue-800 block">Sedang Dikerjakan</span>
                    <span class="text-2xl font-black text-blue-950 block mt-1">{{ $counts['ongoing'] }}</span>
                </div>
                <div class="h-3 w-3 rounded-full bg-blue-500 ring-4 ring-blue-100" title="Sedang Dikerjakan"></div>
            </div>

            {{-- Done Card --}}
            <div class="rounded-xl border border-emerald-200 bg-emerald-50/50 p-4 shadow-sm transition hover:shadow-md flex items-center justify-between">
                <div>
                    <span class="text-xs font-bold text-emerald-800 block">Selesai (Done)</span>
                    <span class="text-2xl font-black text-emerald-950 block mt-1">{{ $counts['done'] }}</span>
                </div>
                <div class="h-3 w-3 rounded-full bg-emerald-500 ring-4 ring-emerald-100" title="Selesai (Done)"></div>
            </div>

            {{-- Cancel Card --}}
            <div class="rounded-xl border border-rose-200 bg-rose-50/50 p-4 shadow-sm transition hover:shadow-md flex items-center justify-between col-span-2 md:col-span-1 lg:col-span-1">
                <div>
                    <span class="text-xs font-bold text-rose-800 block">Dibatalkan</span>
                    <span class="text-2xl font-black text-rose-950 block mt-1">{{ $counts['cancel'] }}</span>
                </div>
                <div class="h-3 w-3 rounded-full bg-rose-500 ring-4 ring-rose-100" title="Dibatalkan"></div>
            </div>
        </div>
    </div>

    {{-- Agenda Hari Besok List --}}
    <div class="rounded-2xl border border-indigo-150 bg-white p-6 shadow-md mb-8">
        <div class="flex items-center justify-between border-b border-gray-100 pb-4 mb-4">
            <h3 class="font-bold text-gray-900 text-sm uppercase tracking-wider">
                Agenda Hari Besok ({{ $tomorrowDate->translatedFormat('d F Y') }})
            </h3>
            <span class="text-xs bg-indigo-50 text-indigo-800 border border-indigo-200 px-2.5 py-0.5 rounded-full font-semibold">
                {{ $tomorrowBookings->count() }} Agenda
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-gray-500">
                <thead class="text-xs text-gray-400 uppercase bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 whitespace-nowrap">Kode Unik</th>
                        <th class="px-4 py-3 whitespace-nowrap">Pelanggan</th>
                        <th class="px-4 py-3 whitespace-nowrap">Layanan</th>
                        <th class="px-4 py-3 text-center whitespace-nowrap text-indigo-600">Tipe & Tanggal Agenda</th>
                        <th class="px-4 py-3 whitespace-nowrap">Teknisi</th>
                        <th class="px-4 py-3 text-right whitespace-nowrap">Nominal</th>
                        <th class="px-4 py-3 text-center whitespace-nowrap">Status Kerja</th>
                        <th class="px-4 py-3 text-right whitespace-nowrap">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-150">
                    @forelse ($tomorrowBookings as $booking)
                        <tr class="hover:bg-gray-50/50">
                            <td class="px-4 py-3.5 font-bold font-mono text-gray-900 whitespace-nowrap">{{ $booking->kode_unik }}</td>
                            <td class="px-4 py-3.5 font-semibold text-gray-900">
                                {{ $booking->nama_client }}
                            </td>
                            <td class="px-4 py-3.5 text-xs font-medium text-gray-700">{{ is_object($booking->jenis_layanan) ? $booking->jenis_layanan->label() : ($booking->tarifLayanan?->jenis_layanan ?? $booking->jenis_layanan) }}</td>
                            <td class="px-4 py-3.5 text-xs text-center whitespace-nowrap">
                                @if ($booking->tanggal_pengerjaan && $booking->tanggal_pengerjaan->isSameDay($tomorrowDate))
                                    <span class="font-bold text-primary-700 bg-primary-50 border border-primary-100 rounded px-2.5 py-1">
                                        Pengerjaan: {{ $booking->tanggal_pengerjaan->format('d M Y') }}
                                    </span>
                                @else
                                    <span class="font-bold text-blue-700 bg-blue-50 border border-blue-100 rounded px-2.5 py-1">
                                        Survey: {{ $booking->tanggal_survey ? $booking->tanggal_survey->format('d M Y') : '-' }}
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3.5 text-xs font-semibold text-primary-700">
                                {{ $booking->teknisi ? $booking->teknisi->nama : 'Belum Ditugaskan' }}
                            </td>
                            <td class="px-4 py-3.5 text-right font-bold text-gray-900 whitespace-nowrap">Rp {{ number_format($booking->nominal, 0, ',', '.') }}</td>
                            <td class="px-4 py-3.5 text-center whitespace-nowrap">
                                @php
                                    $rawStatus = is_object($booking->status_pengerjaan) ? $booking->status_pengerjaan->value : strtolower((string)$booking->status_pengerjaan);
                                    $lblStatus = is_object($booking->status_pengerjaan) ? $booking->status_pengerjaan->label() : $booking->status_pengerjaan;
                                    $color = match($rawStatus) {
                                        'pending' => 'bg-yellow-50 text-yellow-700 ring-yellow-600/20',
                                        'ongoing', 'on-going' => 'bg-blue-50 text-blue-700 ring-blue-600/20',
                                        'done' => 'bg-green-50 text-green-700 ring-green-600/20',
                                        'cancel' => 'bg-red-50 text-red-700 ring-red-600/20',
                                        default => 'bg-gray-50 text-gray-700 ring-gray-600/20',
                                    };
                                @endphp
                                <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset {{ $color }}">
                                    {{ $lblStatus }}
                                </span>
                            </td>
                            <td class="px-4 py-3.5 text-right whitespace-nowrap">
                                <a href="{{ route('admin.pemesanan.show', $booking->id) }}" class="rounded bg-indigo-50 px-2.5 py-1.5 text-xs font-bold text-indigo-650 transition hover:bg-indigo-600 hover:text-white">
                                    Detail / Kelola
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-6 text-gray-400">Tidak ada agenda survey atau pengerjaan untuk esok hari.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Unverified Bookings (Needs WA Verification) --}}
    <div class="rounded-2xl border border-gray-100 bg-white p-6 shadow-md mb-8">
        <div class="flex items-center justify-between border-b border-gray-100 pb-4 mb-4">
            <h3 class="font-bold text-gray-900 text-sm uppercase tracking-wider">
                Pemesanan Butuh Verifikasi (Respon WA)
            </h3>
            <span class="text-xs bg-yellow-50 text-yellow-800 border border-yellow-200 px-2.5 py-0.5 rounded-full font-semibold">Maks. 3 Hari</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-gray-500">
                <thead class="text-xs text-gray-400 uppercase bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 whitespace-nowrap">Kode Unik</th>
                        <th class="px-4 py-3 whitespace-nowrap">Pelanggan</th>
                        <th class="px-4 py-3 whitespace-nowrap">Layanan</th>
                        <th class="px-4 py-3 whitespace-nowrap text-amber-600">Sisa Waktu</th>
                        <th class="px-4 py-3 text-right whitespace-nowrap">Nominal</th>
                        <th class="px-4 py-3 text-center whitespace-nowrap">Status Kerja</th>
                        <th class="px-4 py-3 text-center whitespace-nowrap">Pembayaran</th>
                        <th class="px-4 py-3 text-right whitespace-nowrap">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-150">
                    @forelse ($unverifiedBookings as $booking)
                        <tr class="hover:bg-gray-50/50">
                            <td class="px-4 py-3.5 font-bold font-mono text-gray-900 whitespace-nowrap">{{ $booking->kode_unik }}</td>
                            <td class="px-4 py-3.5 font-semibold text-gray-900">
                                {{ $booking->nama_client }}
                            </td>
                            <td class="px-4 py-3.5 text-xs font-medium text-gray-700">{{ is_object($booking->jenis_layanan) ? $booking->jenis_layanan->label() : ($booking->tarifLayanan?->jenis_layanan ?? $booking->jenis_layanan) }}</td>
                            <td class="px-4 py-3.5 text-xs text-center"><span class="font-bold text-amber-700 whitespace-nowrap bg-amber-50 border border-amber-100 rounded px-2.5 py-1">{{ $booking->sisa_waktu_verifikasi }}</span></td>
                            <td class="px-4 py-3.5 text-right font-bold text-gray-900 whitespace-nowrap">Rp {{ number_format($booking->nominal, 0, ',', '.') }}</td>
                            <td class="px-4 py-3.5 text-center whitespace-nowrap">
                                <span class="inline-flex items-center rounded-full bg-yellow-50 px-2 py-0.5 text-xs font-semibold text-yellow-700 ring-1 ring-inset ring-yellow-600/20">
                                    {{ is_object($booking->status_pengerjaan) ? $booking->status_pengerjaan->label() : $booking->status_pengerjaan }}
                                </span>
                            </td>
                            <td class="px-4 py-3.5 text-center whitespace-nowrap">
                                <span class="inline-flex items-center rounded-full bg-amber-50 px-2 py-0.5 text-xs font-semibold text-amber-700 ring-1 ring-inset ring-amber-600/20">
                                    Belum Lunas
                                </span>
                            </td>
                            <td class="px-4 py-3.5 text-right whitespace-nowrap">
                                <a href="{{ route('admin.pemesanan.show', $booking->id) }}" class="rounded bg-primary-50 px-2.5 py-1.5 text-xs font-bold text-primary-600 transition hover:bg-primary-500 hover:text-white">
                                    Detail / Verifikasi
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-6 text-gray-400">Tidak ada pemesanan yang butuh verifikasi. Semua pemesanan valid!</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Jadwal Survey / Belum Survey --}}
    <div class="rounded-2xl border border-gray-100 bg-white p-6 shadow-md mb-8">
        <div class="flex items-center justify-between border-b border-gray-100 pb-4 mb-4">
            <h3 class="font-bold text-gray-900 text-sm uppercase tracking-wider">
                Jadwal Survey / Belum Survey
            </h3>
            <span class="text-xs bg-primary-50 text-primary-800 border border-primary-200 px-2.5 py-0.5 rounded-full font-semibold">Menunggu Survey</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-gray-500">
                <thead class="text-xs text-gray-400 uppercase bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 whitespace-nowrap">Kode Unik</th>
                        <th class="px-4 py-3 whitespace-nowrap">Pelanggan</th>
                        <th class="px-4 py-3 whitespace-nowrap">Layanan</th>
                        <th class="px-4 py-3 whitespace-nowrap text-primary-600 text-center">Tanggal Survey</th>
                        <th class="px-4 py-3 text-right whitespace-nowrap">Nominal</th>
                        <th class="px-4 py-3 text-center whitespace-nowrap">Status Kerja</th>
                        <th class="px-4 py-3 text-center whitespace-nowrap">Pembayaran</th>
                        <th class="px-4 py-3 text-right whitespace-nowrap">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-150">
                    @forelse ($surveyBookings as $booking)
                        <tr class="hover:bg-gray-50/50">
                            <td class="px-4 py-3.5 font-bold font-mono text-gray-900 whitespace-nowrap">{{ $booking->kode_unik }}</td>
                            <td class="px-4 py-3.5 font-semibold text-gray-900">
                                {{ $booking->nama_client }}
                            </td>
                            <td class="px-4 py-3.5 text-xs font-medium text-gray-700">{{ is_object($booking->jenis_layanan) ? $booking->jenis_layanan->label() : ($booking->tarifLayanan?->jenis_layanan ?? $booking->jenis_layanan) }}</td>
                            <td class="px-4 py-3.5 text-xs text-center"><span class="font-bold text-primary-700 whitespace-nowrap bg-primary-50 border border-primary-100 rounded px-2.5 py-1">{{ $booking->tanggal_survey ? $booking->tanggal_survey->format('d M Y') : '-' }}</span></td>
                            <td class="px-4 py-3.5 text-right font-bold text-gray-900 whitespace-nowrap">Rp {{ number_format($booking->nominal, 0, ',', '.') }}</td>
                            <td class="px-4 py-3.5 text-center whitespace-nowrap">
                                <span class="inline-flex items-center rounded-full bg-yellow-50 px-2 py-0.5 text-xs font-semibold text-yellow-700 ring-1 ring-inset ring-yellow-600/20">
                                    {{ is_object($booking->status_pengerjaan) ? $booking->status_pengerjaan->label() : $booking->status_pengerjaan }}
                                </span>
                            </td>
                            <td class="px-4 py-3.5 text-center whitespace-nowrap">
                                <span class="inline-flex items-center rounded-full bg-amber-50 px-2 py-0.5 text-xs font-semibold text-amber-700 ring-1 ring-inset ring-amber-600/20">
                                    {{ is_object($booking->is_lunas) ? $booking->is_lunas->label() : ($booking->status_pembayaran ?? $booking->is_lunas) }}
                                </span>
                            </td>
                            <td class="px-4 py-3.5 text-right whitespace-nowrap">
                                <a href="{{ route('admin.pemesanan.show', $booking->id) }}" class="rounded bg-primary-50 px-2.5 py-1.5 text-xs font-bold text-primary-600 transition hover:bg-primary-500 hover:text-white">
                                    Detail / Jadwalkan
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-6 text-gray-400">Tidak ada pemesanan yang menunggu survey.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>



    {{-- Scheduled Treatments --}}
    <div class="rounded-2xl border border-gray-100 bg-white p-6 shadow-md mt-8">
        <div class="flex items-center justify-between border-b border-gray-100 pb-4 mb-4">
            <h3 class="font-bold text-gray-900 text-sm uppercase tracking-wider">Jadwal Pengerjaan / Treatment</h3>
            <a href="{{ route('admin.pemesanan.index') }}" class="text-xs font-semibold text-primary-600 hover:text-primary-800 transition">
                Lihat Semua &rarr;
            </a>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-gray-500">
                <thead class="text-xs text-gray-400 uppercase bg-gray-50">
                    <tr>
                        <th class="px-4 py-3">Kode Unik</th>
                        <th class="px-4 py-3">Pelanggan</th>
                        <th class="px-4 py-3">Layanan</th>
                        <th class="px-4 py-3">Tanggal Pengerjaan</th>
                        <th class="px-4 py-3 text-right">Nominal</th>
                        <th class="px-4 py-3 text-center">Status Kerja</th>
                        <th class="px-4 py-3 text-center">Pembayaran</th>
                        <th class="px-4 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-150">
                    @forelse ($scheduledBookings as $booking)
                        <tr class="hover:bg-gray-50/50">
                            <td class="px-4 py-3.5 font-bold font-mono text-gray-900 whitespace-nowrap">{{ $booking->kode_unik }}</td>
                            <td class="px-4 py-3.5 font-semibold text-gray-900">
                                {{ $booking->nama_client }}
                            </td>
                            <td class="px-4 py-3.5 text-xs font-medium text-gray-700">{{ is_object($booking->jenis_layanan) ? $booking->jenis_layanan->label() : ($booking->tarifLayanan?->jenis_layanan ?? $booking->jenis_layanan) }}</td>
                            <td class="px-4 py-3.5 text-xs text-gray-600 whitespace-nowrap">{{ $booking->tanggal_pengerjaan ? $booking->tanggal_pengerjaan->format('d M Y') : '-' }}</td>
                            <td class="px-4 py-3.5 text-right font-bold text-gray-900 whitespace-nowrap">Rp {{ number_format($booking->nominal, 0, ',', '.') }}</td>
                            <td class="px-4 py-3.5 text-center whitespace-nowrap">
                                @php
                                    $rawStatus = is_object($booking->status_pengerjaan) ? $booking->status_pengerjaan->value : strtolower((string)$booking->status_pengerjaan);
                                    $lblStatus = is_object($booking->status_pengerjaan) ? $booking->status_pengerjaan->label() : $booking->status_pengerjaan;
                                    $color = match($rawStatus) {
                                        'pending' => 'bg-yellow-50 text-yellow-700 ring-yellow-600/20',
                                        'ongoing', 'on-going' => 'bg-blue-50 text-blue-700 ring-blue-600/20',
                                        'done' => 'bg-green-50 text-green-700 ring-green-600/20',
                                        'cancel' => 'bg-red-50 text-red-700 ring-red-600/20',
                                        default => 'bg-gray-50 text-gray-700 ring-gray-600/20',
                                    };
                                @endphp
                                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold ring-1 ring-inset {{ $color }}">
                                    {{ $lblStatus }}
                                </span>
                            </td>
                            <td class="px-4 py-3.5 text-center whitespace-nowrap">
                                @if ($booking->is_lunas === \App\Enums\StatusPelunasan::Lunas)
                                    <span class="inline-flex items-center rounded-full bg-green-50 px-2 py-0.5 text-xs font-semibold text-green-700 ring-1 ring-inset ring-green-600/20">
                                        Lunas
                                    </span>
                                @elseif ($booking->is_lunas === \App\Enums\StatusPelunasan::Ditolak)
                                    <span class="inline-flex items-center rounded-full bg-red-50 px-2 py-0.5 text-xs font-semibold text-red-700 ring-1 ring-inset ring-red-600/20">
                                        Ditolak
                                    </span>
                                @else
                                    <span class="inline-flex items-center rounded-full bg-amber-50 px-2 py-0.5 text-xs font-semibold text-amber-700 ring-1 ring-inset ring-amber-600/20">
                                        Belum Lunas
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3.5 text-right whitespace-nowrap">
                                <a href="{{ route('admin.pemesanan.show', $booking->id) }}" class="rounded bg-primary-50 px-2.5 py-1.5 text-xs font-bold text-primary-600 transition hover:bg-primary-500 hover:text-white">
                                    Detail
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-6 text-gray-400">Belum ada jadwal pengerjaan.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
