<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Portal Teknisi - CV. Ambius Indonesia')</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-100 font-sans text-slate-800 antialiased flex flex-col pb-20 md:pb-6">
    @php
        $badgePerluTindakan = Auth::check() && Auth::user()->isTeknisi() ? Auth::user()->countPerluTindakan() : 0;
    @endphp

    {{-- Top Header --}}
    <header class="sticky top-0 z-40 bg-emerald-800 text-white shadow-md">
        <div class="mx-auto max-w-4xl px-4 py-2.5 sm:px-6 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <a href="{{ route('teknisi.dashboard') }}" class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-600 text-white shadow-sm hover:bg-emerald-500 transition">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                    </svg>
                </a>
                <div>
                    <h1 class="text-sm font-bold tracking-tight leading-tight">Teknisi Lapangan</h1>
                    <p class="text-[11px] text-emerald-200">CV. Ambius Indonesia</p>
                </div>
            </div>

            {{-- Desktop Navigation Links --}}
            <nav class="hidden md:flex items-center gap-1 text-xs font-semibold">
                <a href="{{ route('teknisi.dashboard') }}" class="px-3 py-1.5 rounded-lg transition {{ request()->routeIs('teknisi.dashboard') ? 'bg-emerald-700 text-white' : 'text-emerald-100 hover:bg-emerald-700/60' }}">
                    Dashboard
                </a>
                <a href="{{ route('teknisi.tugas') }}" class="relative px-3 py-1.5 rounded-lg transition flex items-center gap-1.5 {{ request()->routeIs('teknisi.tugas*') ? 'bg-emerald-700 text-white' : 'text-emerald-100 hover:bg-emerald-700/60' }}">
                    Daftar Tugas
                    @if ($badgePerluTindakan > 0)
                        <span class="inline-flex items-center justify-center rounded-full bg-rose-500 px-1.5 py-0.5 text-[10px] font-black text-white leading-none animate-pulse">
                            {{ $badgePerluTindakan }}
                        </span>
                    @endif
                </a>
                <a href="{{ route('teknisi.riwayat') }}" class="px-3 py-1.5 rounded-lg transition {{ request()->routeIs('teknisi.riwayat*') ? 'bg-emerald-700 text-white' : 'text-emerald-100 hover:bg-emerald-700/60' }}">
                    Riwayat Selesai
                </a>
                <a href="{{ route('teknisi.profil.edit') }}" class="px-3 py-1.5 rounded-lg transition {{ request()->routeIs('teknisi.profil*') ? 'bg-emerald-700 text-white' : 'text-emerald-100 hover:bg-emerald-700/60' }}">
                    Profil & Sandi
                </a>
            </nav>

            {{-- User info & Mobile Actions --}}
            <div class="flex items-center gap-2">
                @if ($badgePerluTindakan > 0)
                    <a href="{{ route('teknisi.tugas', ['status' => 'Pending']) }}" class="md:hidden flex items-center gap-1 px-2 py-1 rounded-lg bg-rose-500 text-white text-[11px] font-bold shadow-sm animate-pulse" title="Perlu tindakan segera">
                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                        <span>{{ $badgePerluTindakan }} Perlu Aksi</span>
                    </a>
                @endif

                <div class="text-right hidden sm:block">
                    <span class="block text-xs font-semibold leading-tight">{{ Auth::user()->nama }}</span>
                    <span class="block text-[10px] text-emerald-200">{{ Auth::user()->no_hp ?? 'Teknisi' }}</span>
                </div>

                <form action="{{ route('logout') }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" class="p-2 rounded-lg bg-emerald-900/70 hover:bg-emerald-900 text-emerald-100 transition text-xs font-medium flex items-center gap-1.5" title="Keluar">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                        </svg>
                        <span class="hidden sm:inline">Keluar</span>
                    </button>
                </form>
            </div>
        </div>
    </header>

    {{-- Flash Notifications --}}
    <div class="mx-auto w-full max-w-4xl px-4 pt-3 sm:px-6">
        @if (session('success'))
            <div class="mb-3 rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-xs sm:text-sm text-emerald-900 shadow-sm flex items-start gap-3">
                <svg class="h-5 w-5 text-emerald-600 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <div class="flex-1 font-semibold">{{ session('success') }}</div>
            </div>
        @endif

        @if (session('error'))
            <div class="mb-3 rounded-2xl border border-rose-200 bg-rose-50 p-4 text-xs sm:text-sm text-rose-900 shadow-sm flex items-start gap-3">
                <svg class="h-5 w-5 text-rose-600 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <div class="flex-1 font-semibold leading-relaxed">{{ session('error') }}</div>
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-3 rounded-2xl border border-rose-200 bg-rose-50 p-4 text-xs sm:text-sm text-rose-900 shadow-sm">
                <p class="font-bold mb-1.5 flex items-center gap-1.5 text-rose-800">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    Mohon periksa data berikut:
                </p>
                <ul class="list-disc list-inside space-y-1 text-xs text-rose-800">
                    @foreach ($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>

    {{-- Main Container --}}
    <main class="flex-1 mx-auto w-full max-w-4xl px-4 py-2 sm:px-6">
        @yield('content')
    </main>

    {{-- Mobile Bottom Navigation Bar (Touch-friendly & Fixed) --}}
    <nav class="md:hidden fixed bottom-0 left-0 right-0 z-40 bg-white border-t border-slate-200 px-3 py-2 flex items-center justify-around shadow-2xl">
        {{-- 1. Dashboard --}}
        <a href="{{ route('teknisi.dashboard') }}" class="flex flex-col items-center gap-1 text-[11px] font-medium transition {{ request()->routeIs('teknisi.dashboard') ? 'text-emerald-700 font-bold' : 'text-slate-500 hover:text-slate-800' }}">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
            </svg>
            <span>Ringkasan</span>
        </a>

        {{-- 2. Daftar Tugas --}}
        <a href="{{ route('teknisi.tugas') }}" class="relative flex flex-col items-center gap-1 text-[11px] font-medium transition {{ request()->routeIs('teknisi.tugas*') || request()->routeIs('teknisi.pemesanan.show') ? 'text-emerald-700 font-bold' : 'text-slate-500 hover:text-slate-800' }}">
            <div class="relative">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                </svg>
                @if ($badgePerluTindakan > 0)
                    <span class="absolute -top-1 -right-2.5 flex h-4 min-w-[16px] items-center justify-center rounded-full bg-rose-500 px-1 text-[10px] font-bold text-white shadow-sm animate-pulse">
                        {{ $badgePerluTindakan }}
                    </span>
                @endif
            </div>
            <span>Tugas</span>
        </a>

        {{-- 3. Riwayat Selesai --}}
        <a href="{{ route('teknisi.riwayat') }}" class="flex flex-col items-center gap-1 text-[11px] font-medium transition {{ request()->routeIs('teknisi.riwayat*') ? 'text-emerald-700 font-bold' : 'text-slate-500 hover:text-slate-800' }}">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span>Riwayat</span>
        </a>

        {{-- 4. Profil & Sandi --}}
        <a href="{{ route('teknisi.profil.edit') }}" class="flex flex-col items-center gap-1 text-[11px] font-medium transition {{ request()->routeIs('teknisi.profil*') ? 'text-emerald-700 font-bold' : 'text-slate-500 hover:text-slate-800' }}">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
            </svg>
            <span>Profil</span>
        </a>
    </nav>

    @stack('scripts')
</body>
</html>
