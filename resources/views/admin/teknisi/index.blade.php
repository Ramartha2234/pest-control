@extends('layouts.admin')

@section('title', 'Kelola Teknisi - CV. Ambius')
@section('page_title', 'Kelola Teknisi')

@section('content')
<div class="space-y-6">
    {{-- Top Action Bar --}}
    <div class="flex flex-col sm:flex-row items-center justify-between gap-4 bg-white p-5 rounded-2xl border border-gray-200 shadow-sm">
        <div>
            <h3 class="text-base font-bold text-gray-900">Daftar Teknisi & Akun Lapangan</h3>
            <p class="text-xs text-gray-500 mt-1">Daftar teknisi/karyawan CV. Ambius yang bertugas melakukan treatment lapangan.</p>
        </div>
        <a href="{{ route('admin.teknisi.create') }}" 
           class="inline-flex items-center gap-1.5 rounded-xl bg-primary-600 hover:bg-primary-700 px-5 py-2.5 text-xs font-bold text-white shadow-sm transition whitespace-nowrap">
            <svg class="h-4 w-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
            </svg>
            <span>Tambah Teknisi</span>
        </a>
    </div>

    {{-- Search Bar --}}
    <div class="bg-white p-4 rounded-2xl border border-gray-200 shadow-sm flex flex-col sm:flex-row items-center justify-between gap-3">
        <form action="{{ route('admin.teknisi.index') }}" method="GET" class="flex items-center gap-2 w-full sm:w-auto">
            <div class="relative w-full sm:w-72">
                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                    <svg class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
                <input type="text" name="search" value="{{ $search ?? '' }}" placeholder="Cari nama atau no. telepon teknisi..."
                       class="w-full rounded-xl border border-gray-300 bg-white py-2 pl-9 pr-3 text-xs text-gray-900 focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500">
            </div>
            <button type="submit" class="rounded-xl bg-primary-600 px-4 py-2 text-xs font-semibold text-white shadow-sm hover:bg-primary-700 transition">
                Cari
            </button>
            @if (!empty($search))
                <a href="{{ route('admin.teknisi.index') }}" class="rounded-xl border border-gray-300 px-3 py-2 text-xs font-semibold text-gray-600 hover:bg-gray-50 transition">
                    Reset
                </a>
            @endif
        </form>
        @if (!empty($search))
            <span class="text-xs text-gray-400">Hasil pencarian untuk: <b>"{{ $search }}"</b> ({{ $teknisis->total() }} teknisi)</span>
        @endif
    </div>

    {{-- Table Card --}}
    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-gray-50 text-[11px] font-bold uppercase tracking-wider text-gray-500 border-b border-gray-200">
                    <tr>
                        <th class="px-6 py-3.5 whitespace-nowrap">ID</th>
                        <th class="px-6 py-3.5 whitespace-nowrap">Nama Teknisi</th>
                        <th class="px-6 py-3.5 whitespace-nowrap">No. Telp / WhatsApp</th>
                        <th class="px-6 py-3.5 text-center whitespace-nowrap">Status Keaktifan</th>
                        <th class="px-6 py-3.5 text-center whitespace-nowrap">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-gray-700">
                    @forelse ($teknisis as $teknisi)
                        <tr class="hover:bg-gray-50/70 transition">
                            <td class="px-6 py-3.5 font-mono font-bold text-gray-900 whitespace-nowrap">
                                #{{ $teknisi->id }}
                            </td>
                            <td class="px-6 py-3.5 font-bold text-gray-900 whitespace-nowrap">
                                {{ $teknisi->nama }}
                            </td>
                            <td class="px-6 py-3.5 font-mono font-medium text-gray-600 whitespace-nowrap">
                                {{ $teknisi->no_telp }}
                            </td>
                            <td class="px-6 py-3.5 text-center whitespace-nowrap">
                                @if ($teknisi->is_active)
                                    <span class="inline-block rounded-full px-2.5 py-0.5 text-[11px] font-bold bg-emerald-100 text-emerald-800">
                                        Aktif
                                    </span>
                                @else
                                    <span class="inline-block rounded-full px-2.5 py-0.5 text-[11px] font-bold bg-rose-100 text-rose-800">
                                        Nonaktif
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-3.5 text-center whitespace-nowrap">
                                <div class="inline-flex items-center gap-1.5 justify-center">
                                    <a href="{{ route('admin.teknisi.edit', $teknisi->id) }}" 
                                       class="rounded-xl bg-primary-50 px-3.5 py-1.5 font-bold text-primary-700 hover:bg-primary-100 transition inline-block">
                                        Edit
                                    </a>

                                    <form action="{{ route('admin.teknisi.destroy', $teknisi->id) }}" method="POST" 
                                          onsubmit="return confirm('Apakah Anda yakin ingin menghapus/menonaktifkan teknisi ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="rounded-xl bg-rose-50 px-3.5 py-1.5 font-bold text-rose-700 hover:bg-rose-100 transition inline-block">
                                            Hapus
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-gray-400">
                                Belum ada data teknisi terdaftar.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($teknisis->hasPages())
            <div class="p-4 border-t border-gray-100">
                {{ $teknisis->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
