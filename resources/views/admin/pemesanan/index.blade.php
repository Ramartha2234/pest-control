@extends('layouts.admin')

@section('title', 'Kelola Pemesanan - CV. Ambius Indonesia')
@section('page_title', 'Kelola Seluruh Pemesanan')

@section('content')
<div class="space-y-6">
    {{-- Search & Filters Bar --}}
    <div class="flex flex-col lg:flex-row items-center justify-between gap-4 bg-white p-5 rounded-2xl border border-gray-200 shadow-sm">
        {{-- Status Filter Pills --}}
        <div class="flex flex-wrap gap-2 w-full lg:w-auto">
            <a href="{{ route('admin.pemesanan.index') }}"
               class="rounded-full px-4 py-2 text-xs font-semibold transition {{ empty($currentStatus) ? 'bg-primary-600 text-white shadow-sm' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                Semua
            </a>
            @foreach (['Pending', 'On-Going', 'Done', 'Cancel'] as $st)
                <a href="{{ route('admin.pemesanan.index', array_merge(request()->query(), ['status_pengerjaan' => $st])) }}"
                   class="rounded-full px-4 py-2 text-xs font-semibold transition {{ $currentStatus === $st ? 'bg-primary-600 text-white shadow-sm' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                    {{ $st }}
                </a>
            @endforeach
        </div>

        {{-- Form Pencarian --}}
        <form action="{{ route('admin.pemesanan.index') }}" method="GET" class="flex w-full lg:w-auto items-center gap-2">
            @if ($currentStatus)
                <input type="hidden" name="status_pengerjaan" value="{{ $currentStatus }}">
            @endif
            <div class="relative w-full sm:w-64">
                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                    <svg class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
                <input type="text" name="search" value="{{ $search ?? '' }}" placeholder="Cari kode, nama, HP..."
                       class="w-full rounded-xl border border-gray-300 bg-white py-2 pl-9 pr-3 text-xs text-gray-900 focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500">
            </div>
            <button type="submit" class="rounded-xl bg-primary-600 px-4 py-2 text-xs font-semibold text-white shadow-sm hover:bg-primary-700 transition">
                Cari
            </button>
            @if (!empty($search))
                <a href="{{ route('admin.pemesanan.index') }}" class="rounded-xl border border-gray-300 px-3 py-2 text-xs font-semibold text-gray-600 hover:bg-gray-50 transition">
                    Reset
                </a>
            @endif
        </form>
    </div>

    {{-- Order List Table --}}
    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-gray-50 text-[11px] font-bold uppercase tracking-wider text-gray-500 border-b border-gray-200">
                    <tr>
                        <th class="px-5 py-3.5 whitespace-nowrap">Kode & Tanggal</th>
                        <th class="px-5 py-3.5 whitespace-nowrap">Pelanggan</th>
                        <th class="px-5 py-3.5 whitespace-nowrap">Layanan & Properti</th>
                        <th class="px-5 py-3.5 whitespace-nowrap">Jadwal Lapangan</th>
                        <th class="px-5 py-3.5 whitespace-nowrap">Teknisi</th>
                        <th class="px-5 py-3.5 text-right whitespace-nowrap">Nominal</th>
                        <th class="px-5 py-3.5 text-center whitespace-nowrap">Status Kerja</th>
                        <th class="px-5 py-3.5 text-center whitespace-nowrap">Pembayaran</th>
                        <th class="px-5 py-3.5 text-center whitespace-nowrap">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-gray-700">
                    @forelse ($pemesanans as $booking)
                        @php
                            $stVal = is_object($booking->status_pengerjaan) ? $booking->status_pengerjaan->value : $booking->status_pengerjaan;
                            $stColor = match(strtolower($stVal)) {
                                'pending' => 'bg-amber-100 text-amber-800',
                                'ongoing', 'on-going' => 'bg-sky-100 text-sky-800',
                                'done' => 'bg-emerald-100 text-emerald-800',
                                'cancel' => 'bg-rose-100 text-rose-800',
                                default => 'bg-gray-100 text-gray-800'
                            };

                            $pbVal = is_object($booking->status_pembayaran) ? $booking->status_pembayaran->value : $booking->status_pembayaran;
                            $pbColor = match(strtolower($pbVal)) {
                                'lunas' => 'bg-emerald-100 text-emerald-800',
                                'ditolak' => 'bg-rose-100 text-rose-800',
                                default => 'bg-amber-100 text-amber-800'
                            };

                            $orderId = $booking->id_pemesanan ?? $booking->id;
                            $kode = $booking->kode_pemesanan ?? $booking->kode_unik;
                            $clientNama = $booking->pelanggan?->nama ?? $booking->nama_client;
                            $clientHp = $booking->pelanggan?->no_hp ?? $booking->no_telp_client;
                            $layanan = $booking->tarifLayanan?->jenis_layanan ?? (is_object($booking->jenis_layanan) ? $booking->jenis_layanan->label() : $booking->jenis_layanan);
                            $teknisiNama = $booking->jadwalPenugasan?->teknisi?->nama ?? ($booking->teknisi?->nama ?? 'Belum Ditugaskan');
                        @endphp
                        <tr class="hover:bg-gray-50/70 transition">
                            <td class="px-5 py-3.5 whitespace-nowrap">
                                <span class="font-mono font-bold text-gray-900 block">{{ $kode }}</span>
                                <span class="text-[10px] text-gray-400 font-sans block">
                                    {{ $booking->tgl_pemesanan ? \Carbon\Carbon::parse($booking->tgl_pemesanan)->format('d M Y H:i') : $booking->created_at->format('d M Y H:i') }}
                                </span>
                            </td>
                            <td class="px-5 py-3.5">
                                <span class="font-bold text-gray-900 block">{{ $clientNama }}</span>
                                <span class="text-[11px] text-gray-400 block">{{ $clientHp }}</span>
                            </td>
                            <td class="px-5 py-3.5">
                                <span class="font-semibold text-gray-800 block">{{ $layanan }}</span>
                                <div class="flex items-center gap-1.5 mt-0.5">
                                    <span class="text-[11px] text-gray-400">{{ $booking->jenis_properti }}</span>
                                    <span class="inline-flex items-center text-[10px] font-bold px-1.5 py-0.2 rounded {{ ($booking->tipe_area ?? 'Indoor') === 'Outdoor' ? 'bg-amber-100 text-amber-800 border border-amber-200' : 'bg-emerald-100 text-emerald-800 border border-emerald-200' }}">
                                        {{ ($booking->tipe_area ?? 'Indoor') === 'Outdoor' ? 'Outdoor' : 'Indoor' }}
                                    </span>
                                </div>
                            </td>
                            <td class="px-5 py-3.5 whitespace-nowrap">
                                @if ($booking->jadwalPenugasan?->tgl_survei)
                                    <div class="text-[11px]">
                                        <span class="text-blue-600 font-semibold">Survei:</span>
                                        {{ \Carbon\Carbon::parse($booking->jadwalPenugasan->tgl_survei)->format('d M Y') }}
                                    </div>
                                @endif
                                @if ($booking->jadwalPenugasan?->tgl_pengerjaan)
                                    <div class="text-[11px]">
                                        <span class="text-emerald-600 font-semibold">Treatment:</span>
                                        {{ \Carbon\Carbon::parse($booking->jadwalPenugasan->tgl_pengerjaan)->format('d M Y') }}
                                    </div>
                                @elseif (!$booking->jadwalPenugasan?->tgl_survei)
                                    <span class="text-amber-600 font-medium">Perlu Penugasan</span>
                                @endif
                            </td>
                            <td class="px-5 py-3.5 font-medium text-gray-800">
                                {{ $teknisiNama }}
                            </td>
                            <td class="px-5 py-3.5 text-right font-extrabold text-gray-900 whitespace-nowrap">
                                Rp {{ number_format($booking->harga_final ?? ($booking->harga_estimasi ?? $booking->nominal), 0, ',', '.') }}
                            </td>
                            <td class="px-5 py-3.5 text-center whitespace-nowrap">
                                <span class="inline-block rounded-full px-2.5 py-0.5 text-[11px] font-bold {{ $stColor }}">
                                    {{ is_object($booking->status_pengerjaan) ? $booking->status_pengerjaan->label() : $booking->status_pengerjaan }}
                                </span>
                            </td>
                            <td class="px-5 py-3.5 text-center whitespace-nowrap">
                                <span class="inline-block rounded-full px-2.5 py-0.5 text-[11px] font-bold {{ $pbColor }}">
                                    {{ is_object($booking->status_pembayaran) ? $booking->status_pembayaran->label() : ($booking->status_pembayaran ?? $booking->is_lunas) }}
                                </span>
                            </td>
                            <td class="px-5 py-3.5 text-center whitespace-nowrap">
                                <a href="{{ route('admin.pemesanan.show', $orderId) }}"
                                   class="rounded-xl bg-primary-50 px-3.5 py-1.5 font-bold text-primary-700 hover:bg-primary-100 transition inline-block">
                                    Detail &rarr;
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-5 py-12 text-center text-gray-400">
                                Tidak ada data pemesanan yang sesuai dengan filter ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-4 border-t border-gray-100">
            {{ $pemesanans->links() }}
        </div>
    </div>
</div>
@endsection
