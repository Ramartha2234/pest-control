@extends('layouts.app')

@section('title', '419 - Sesi Kedaluwarsa - CV. Ambius')

@section('content')
<div class="py-24 bg-gray-50 flex items-center justify-center min-h-[65vh]">
    <div class="mx-auto w-full max-w-lg px-4 text-center">
        <div class="rounded-2xl border border-gray-100 bg-white p-8 shadow-xl">
            <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-amber-100 text-amber-600 mb-6 shadow-sm">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>

            <span class="inline-block rounded-full bg-amber-50 px-3 py-1 text-xs font-bold text-amber-700 ring-1 ring-inset ring-amber-200 mb-3">
                Error 419 &bull; Halaman Kedaluwarsa
            </span>

            <h1 class="text-2xl font-extrabold text-gray-900 tracking-tight mb-2">
                Sesi Anda Telah Berakhir
            </h1>

            <p class="text-sm text-gray-500 mb-8 leading-relaxed">
                Halaman ini telah terbuka terlalu lama tanpa aktivitas atau token keamanan telah kedaluwarsa. Silakan muat ulang halaman untuk melanjutkan.
            </p>

            <div class="flex flex-col sm:flex-row items-center justify-center gap-3">
                <button type="button" onclick="window.location.reload();" class="w-full sm:w-auto rounded-xl bg-primary-500 px-6 py-3 text-sm font-bold text-white shadow-lg transition hover:bg-primary-600 hover:shadow-xl focus:outline-none">
                    Muat Ulang Halaman
                </button>
                <a href="{{ route('login') }}" class="w-full sm:w-auto rounded-xl border border-gray-300 bg-white px-6 py-3 text-sm font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50 focus:outline-none">
                    Kembali ke Login
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
