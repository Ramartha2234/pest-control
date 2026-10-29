@extends('layouts.admin')

@section('title', 'Laporan Pendapatan - CV. Ambius Indonesia')
@section('page_title', 'Laporan Pendapatan Keuangan')

@section('content')
<div class="space-y-6">
    {{-- Header & Filter Periode --}}
    <div class="flex flex-col lg:flex-row items-center justify-between gap-4 bg-white p-6 rounded-2xl border border-gray-200 shadow-sm">
        <div>
            <h3 class="text-base font-bold text-gray-900">Rekap Pendapatan {{ $viewType === 'yearly' ? 'Tahunan' : 'Bulanan' }}</h3>
            <p class="text-xs text-gray-500 mt-1">Laporan resmi seluruh pekerjaan pest control yang telah selesai dan pembayarannya terverifikasi lunas.</p>
        </div>

        <form action="{{ route('admin.laporan.index') }}" method="GET" class="flex flex-wrap items-center gap-2">
            {{-- Toggle Bulanan / Tahunan --}}
            <div class="inline-flex rounded-xl bg-gray-100 p-1 border border-gray-200 text-xs">
                <a href="{{ route('admin.laporan.index', ['view_type' => 'monthly', 'year' => $selectedYear, 'month' => $selectedMonth]) }}"
                   class="px-3 py-1.5 rounded-lg font-bold transition {{ $viewType === 'monthly' ? 'bg-white text-primary-700 shadow-sm' : 'text-gray-600 hover:text-gray-900' }}">
                    Bulanan
                </a>
                <a href="{{ route('admin.laporan.index', ['view_type' => 'yearly', 'year' => $selectedYear]) }}"
                   class="px-3 py-1.5 rounded-lg font-bold transition {{ $viewType === 'yearly' ? 'bg-white text-primary-700 shadow-sm' : 'text-gray-600 hover:text-gray-900' }}">
                    Tahunan
                </a>
            </div>

            <input type="hidden" name="view_type" value="{{ $viewType }}">

            @if ($viewType === 'monthly')
                <select name="month" class="rounded-xl border-gray-300 text-xs focus:border-primary-500 focus:ring-primary-500">
                    @for ($m = 1; $m <= 12; $m++)
                        @php $mPad = str_pad($m, 2, '0', STR_PAD_LEFT); @endphp
                        <option value="{{ $mPad }}" {{ $selectedMonth == $mPad ? 'selected' : '' }}>
                            {{ \Carbon\Carbon::create(2026, $m, 1)->translatedFormat('F') }}
                        </option>
                    @endfor
                </select>
            @endif

            <select name="year" class="rounded-xl border-gray-300 text-xs focus:border-primary-500 focus:ring-primary-500">
                @foreach ($availableYears as $yr)
                    <option value="{{ $yr }}" {{ $selectedYear == $yr ? 'selected' : '' }}>Tahun {{ $yr }}</option>
                @endforeach
            </select>

            <button type="submit" class="rounded-xl bg-primary-600 px-4 py-2 text-xs font-semibold text-white shadow-sm hover:bg-primary-700 transition">
                Tampilkan
            </button>

            <button type="button" onclick="window.print()" class="rounded-xl border border-gray-300 bg-white hover:bg-gray-50 px-4 py-2 text-xs font-bold text-gray-700 shadow-sm transition inline-flex items-center justify-center whitespace-nowrap">
                Cetak / Ekspor PDF
            </button>
        </form>
    </div>

    {{-- Kartu Ringkasan Finansial --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        {{-- Card Total Pendapatan (Highlight Berbeda) --}}
        <div class="rounded-2xl border border-primary-700/60 bg-gradient-to-br from-primary-950 via-primary-900 to-emerald-900 p-6 text-white shadow-lg flex flex-col justify-between print:bg-white print:text-gray-900 print:border print:border-gray-300">
            <div class="my-auto py-2 text-center">
                <span class="text-xs font-bold text-emerald-200 uppercase tracking-wider block print:text-gray-600">Total Pendapatan Terverifikasi</span>
                <span class="text-2xl sm:text-3xl font-black text-white block mt-2.5 tracking-tight print:text-emerald-700">Rp {{ number_format($totalRevenue, 0, ',', '.') }}</span>
                <span class="text-xs text-emerald-200/80 mt-1.5 block font-medium print:text-gray-500">Periode {{ $viewType === 'yearly' ? 'Tahun ' . $selectedYear : \Carbon\Carbon::createFromDate($selectedYear, (int)$selectedMonth, 1)->translatedFormat('F Y') }}</span>
            </div>
            @if (($totalDp ?? 0) > 0)
                <div class="mt-4 pt-3 border-t border-white/10 flex items-center justify-between text-[11px] text-emerald-200/90 print:border-gray-200 print:text-gray-600">
                    <span>DP Bank: <strong class="text-white print:text-gray-900 font-bold">Rp {{ number_format($totalDp, 0, ',', '.') }}</strong></span>
                    <span>Pelunasan: <strong class="text-white print:text-gray-900 font-bold">Rp {{ number_format($totalPelunasan, 0, ',', '.') }}</strong></span>
                </div>
            @else
                <div class="mt-4 pt-3 border-t border-white/10 flex items-center justify-between text-[11px] text-emerald-200/80 print:border-gray-200 print:text-gray-600">
                    <span>Penerimaan</span>
                    <span class="font-bold text-emerald-100 print:text-gray-800">100% Lunas Terverifikasi</span>
                </div>
            @endif
        </div>

        {{-- Card Jumlah Pekerjaan Selesai --}}
        <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm flex flex-col justify-between">
            <div class="my-auto py-2 text-center">
                <span class="text-xs font-bold text-gray-500 uppercase tracking-wider block">Jumlah Pekerjaan Selesai</span>
                <span class="text-2xl sm:text-3xl font-black text-gray-900 block mt-2.5">{{ $totalOrders }} Pesanan</span>
                <span class="text-xs text-gray-400 mt-1.5 block font-medium">Periode {{ $viewType === 'yearly' ? 'Tahun ' . $selectedYear : \Carbon\Carbon::createFromDate($selectedYear, (int)$selectedMonth, 1)->translatedFormat('F Y') }}</span>
            </div>
            <div class="mt-4 pt-3 border-t border-gray-100 flex items-center justify-between text-[11px] text-gray-500 font-medium">
                <span>Status Pekerjaan</span>
                <span class="font-bold text-emerald-700 bg-emerald-50 border border-emerald-200 px-2 py-0.5 rounded-md">Done & Lunas</span>
            </div>
        </div>
    </div>

    {{-- Tabel Rincian Lengkap Laporan --}}
    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
        <div class="p-5 border-b border-gray-100">
            <h4 class="font-bold text-gray-900 text-sm">
                Daftar Rincian Pekerjaan Selesai ({{ $pemesanans->total() }})
            </h4>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-gray-50 text-[11px] font-bold uppercase tracking-wider text-gray-500 border-b border-gray-200">
                    <tr>
                        <th class="px-4 py-3">Kode & Selesai</th>
                        <th class="px-4 py-3">Pelanggan</th>
                        <th class="px-4 py-3">Layanan & Properti</th>
                        <th class="px-4 py-3">Teknisi & Jadwal</th>
                        <th class="px-4 py-3 text-right">Pendapatan</th>
                        <th class="px-4 py-3 text-center print:hidden">Dokumen & Bukti</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-gray-700">
                    @forelse ($pemesanans as $item)
                        @php
                            $wo = $item->workOrders->first();
                            $bukti = $item->buktiPembayarans->first();
                            $nominal = (float) ($item->harga_final ?? $item->harga_estimasi);
                        @endphp
                        <tr class="hover:bg-gray-50/70 transition">
                            <td class="px-4 py-3 align-top">
                                <span class="font-mono font-bold text-gray-900 block">{{ $item->kode_pemesanan }}</span>
                                <span class="text-[11px] text-gray-400 block mt-0.5 whitespace-nowrap">
                                    {{ $item->tgl_selesai ? \Carbon\Carbon::parse($item->tgl_selesai)->format('d M Y') : '-' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 align-top">
                                <span class="font-bold text-gray-900 block leading-tight">{{ $item->pelanggan->nama }}</span>
                                <span class="text-[11px] text-gray-400 block mt-0.5">{{ $item->pelanggan->no_hp }}</span>
                            </td>
                            <td class="px-4 py-3 align-top">
                                <span class="font-semibold text-gray-800 block leading-tight">{{ $item->tarifLayanan->jenis_layanan ?? '-' }}</span>
                                <div class="flex items-center gap-1.5 mt-1 text-[11px]">
                                    <span class="text-gray-500">{{ $item->jenis_properti }}</span>
                                    <span class="text-gray-300">•</span>
                                    <span class="inline-flex items-center text-[10px] font-bold px-1.5 py-0.2 rounded {{ ($item->tipe_area ?? 'Indoor') === 'Outdoor' ? 'bg-amber-100 text-amber-800 border border-amber-200' : 'bg-emerald-100 text-emerald-800 border border-emerald-200' }}">
                                        {{ ($item->tipe_area ?? 'Indoor') === 'Outdoor' ? 'Outdoor' : 'Indoor' }}
                                    </span>
                                </div>
                            </td>
                            <td class="px-4 py-3 align-top">
                                <span class="font-semibold text-gray-900 block leading-tight">
                                    {{ $item->jadwalPenugasan?->teknisi?->nama ?? 'N/A' }}
                                </span>
                                <div class="text-[10px] text-gray-500 mt-1 space-y-0.5">
                                    <div><span class="text-blue-600 font-semibold">Survei:</span> {{ $item->jadwalPenugasan?->tgl_survei ? \Carbon\Carbon::parse($item->jadwalPenugasan->tgl_survei)->format('d/m/Y') : '-' }}</div>
                                    <div><span class="text-emerald-600 font-semibold">Treatment:</span> {{ $item->jadwalPenugasan?->tgl_pengerjaan ? \Carbon\Carbon::parse($item->jadwalPenugasan->tgl_pengerjaan)->format('d/m/Y') : '-' }}</div>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-right align-top">
                                <div class="font-extrabold text-emerald-700 text-sm whitespace-nowrap">
                                    Rp {{ number_format($nominal, 0, ',', '.') }}
                                </div>
                                @if ($item->wajib_dp && $item->status_dp === 'Lunas')
                                    <div class="text-[10px] text-gray-500 font-medium space-y-0.5 mt-1 whitespace-nowrap">
                                        <div>DP 10%: <span class="font-bold text-emerald-800">Rp {{ number_format($item->nominal_dp, 0, ',', '.') }}</span></div>
                                        <div>Pelunasan: <span class="font-bold text-gray-700">Rp {{ number_format($item->sisa_pelunasan, 0, ',', '.') }}</span></div>
                                    </div>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center align-top print:hidden">
                                <div class="flex flex-col items-center justify-center gap-1.5">
                                    @if ($wo)
                                        <a href="{{ route('dokumen.wo', $wo->id_wo) }}" target="_blank"
                                           class="inline-flex items-center justify-center rounded-md bg-emerald-50 border border-emerald-200 px-2.5 py-0.5 text-[10px] font-bold text-emerald-700 hover:bg-emerald-100 transition whitespace-nowrap"
                                           title="Unduh Work Order">
                                            Unduh WO
                                        </a>
                                    @endif

                                    @if ($item->wajib_dp && $item->status_dp === 'Lunas')
                                        @if ($item->bukti_dp)
                                            <a href="{{ route('dokumen.dp', $item->id_pemesanan) }}" target="_blank"
                                               class="inline-flex items-center justify-center rounded-md bg-amber-50 border border-amber-200 px-2.5 py-0.5 text-[10px] font-bold text-amber-800 hover:bg-amber-100 transition whitespace-nowrap"
                                               title="Unduh Struk Transfer DP 10% (Rp {{ number_format($item->nominal_dp, 0, ',', '.') }})">
                                                Bukti DP (10%)
                                            </a>
                                        @else
                                            <span class="inline-flex items-center justify-center text-[10px] font-bold text-amber-700 bg-amber-50 border border-amber-200 px-2.5 py-0.5 rounded-md whitespace-nowrap">
                                                DP Lunas
                                            </span>
                                        @endif
                                    @endif

                                    @if ($bukti)
                                        <a href="{{ route('dokumen.pembayaran', $bukti->id_bukti_pembayaran) }}" target="_blank"
                                           class="inline-flex items-center justify-center rounded-md bg-blue-50 border border-blue-200 px-2.5 py-0.5 text-[10px] font-bold text-blue-700 hover:bg-blue-100 transition whitespace-nowrap"
                                           title="Unduh Bukti Pelunasan Lapangan">
                                            {{ $item->wajib_dp ? 'Pelunasan (' . $bukti->metode_pembayaran . ')' : $bukti->metode_pembayaran }}
                                        </a>
                                    @elseif (!$wo && (!$item->wajib_dp || $item->status_dp !== 'Lunas'))
                                        <span class="text-gray-400 text-[11px]">-</span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-12 text-center text-gray-400">
                                Belum ada transaksi pengerjaan selesai dan lunas untuk periode ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-4 border-t border-gray-100 print:hidden">
            {{ $pemesanans->links() }}
        </div>
    </div>

    {{-- Lembar Pengesahan / Tanda Tangan (Ekspor PDF & Cetak) --}}
    <div class="ttd-container mt-6 bg-white p-6 rounded-2xl border border-gray-200 shadow-sm print:shadow-none print:border-none print:p-0 print:mt-12">
        <div class="flex items-start justify-between text-xs text-gray-800">
            {{-- Pojok Kiri Bawah: Accounting --}}
            <div class="text-center w-64">
                <p class="font-medium text-gray-500 print:text-gray-700">Dibuat &amp; Diverifikasi,</p>
                <p class="font-bold uppercase tracking-wider text-gray-900 mt-1">Accounting</p>
                
                {{-- Ruang Kosong Tanda Tangan --}}
                <div style="height: 80px; min-height: 80px;"></div>

                <p class="font-bold text-gray-900 border-b border-gray-800 inline-block pb-0.5 px-4 min-w-[180px]">
                    {{ $namaAccounting }}
                </p>
                <p class="text-[11px] text-gray-500 mt-1">Bagian Keuangan</p>
            </div>

            {{-- Pojok Kanan Bawah: Direktur --}}
            <div class="text-center w-64">
                <p class="font-medium text-gray-500 print:text-gray-700">
                    {{ \Carbon\Carbon::now()->locale('id')->translatedFormat('d F Y') }}
                </p>
                <p class="font-bold uppercase tracking-wider text-gray-900 mt-1">
                    Direktur
                </p>

                {{-- Ruang Kosong Tanda Tangan --}}
                <div style="height: 80px; min-height: 80px;"></div>

                <p class="font-bold text-gray-900 border-b border-gray-800 inline-block pb-0.5 px-4 min-w-[180px]">
                    {{ $namaDirektur }}
                </p>
                <p class="text-[11px] text-gray-500 mt-1">Direktur Utama</p>
            </div>
        </div>
    </div>
</div>

<style>
@media print {
    aside, header, button, form, .print\:hidden, .no-print {
        display: none !important;
    }
    th.print\:hidden, td.print\:hidden {
        display: none !important;
    }
    body, main {
        background: white !important;
        padding: 0 !important;
        margin: 0 !important;
    }
    .shadow-sm, .shadow-md {
        box-shadow: none !important;
        border: 1px solid #e5e7eb !important;
    }
    .ttd-container {
        page-break-inside: avoid !important;
        break-inside: avoid !important;
    }
}
</style>
@endsection
