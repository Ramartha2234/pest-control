<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Admin Dashboard - CV. Ambius Indonesia')</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-gray-100 font-sans text-gray-800 antialiased flex flex-col md:flex-row">
    {{-- Sidebar --}}
    <aside class="w-full md:w-64 md:sticky md:top-0 md:h-screen md:overflow-y-auto bg-primary-900 text-white flex-shrink-0 flex flex-col border-r border-primary-950 shadow-md">
        <div class="px-6 py-4 flex items-center justify-between border-b border-primary-800">
            <div class="flex items-center gap-3">
                <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-primary-500 text-white shadow-sm">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                    </svg>
                </div>
                <div>
                    <span class="font-bold text-sm block">CV. Ambius Indonesia</span>
                    <span class="text-[10px] text-primary-300 block">Admin Portal</span>
                </div>
            </div>

            {{-- Collapsible mobile menu --}}
            <button id="admin-menu-btn" class="p-2 rounded-lg text-primary-100 hover:bg-primary-800 md:hidden" aria-label="Toggle Menu">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
            </button>
        </div>

        {{-- Nav Links --}}
        <nav id="admin-menu" class="hidden md:flex flex-col flex-1 px-4 py-5 space-y-1.5">
            <a href="{{ route('admin.dashboard') }}"
               class="block px-4 py-2.5 rounded-xl text-xs font-semibold tracking-wide transition {{ request()->routeIs('admin.dashboard') ? 'bg-primary-800 text-white font-bold shadow-sm ring-1 ring-white/10' : 'text-primary-100 hover:bg-primary-800/60 hover:text-white' }}">
                Dashboard
            </a>
            <a href="{{ route('admin.pemesanan.index') }}"
               class="block px-4 py-2.5 rounded-xl text-xs font-semibold tracking-wide transition {{ request()->routeIs('admin.pemesanan.*') ? 'bg-primary-800 text-white font-bold shadow-sm ring-1 ring-white/10' : 'text-primary-100 hover:bg-primary-800/60 hover:text-white' }}">
                Kelola Pemesanan
            </a>
            <a href="{{ route('admin.teknisi.index') }}"
               class="block px-4 py-2.5 rounded-xl text-xs font-semibold tracking-wide transition {{ request()->routeIs('admin.teknisi.*') ? 'bg-primary-800 text-white font-bold shadow-sm ring-1 ring-white/10' : 'text-primary-100 hover:bg-primary-800/60 hover:text-white' }}">
                Teknisi & Akun
            </a>
            <a href="{{ route('admin.pelanggan.index') }}"
               class="block px-4 py-2.5 rounded-xl text-xs font-semibold tracking-wide transition {{ request()->routeIs('admin.pelanggan.*') ? 'bg-primary-800 text-white font-bold shadow-sm ring-1 ring-white/10' : 'text-primary-100 hover:bg-primary-800/60 hover:text-white' }}">
                Data Pelanggan
            </a>
            <a href="{{ route('admin.tarif.index') }}"
               class="block px-4 py-2.5 rounded-xl text-xs font-semibold tracking-wide transition {{ request()->routeIs('admin.tarif.*') ? 'bg-primary-800 text-white font-bold shadow-sm ring-1 ring-white/10' : 'text-primary-100 hover:bg-primary-800/60 hover:text-white' }}">
                Tarif Layanan
            </a>
            <a href="{{ route('admin.laporan.index') }}"
               class="block px-4 py-2.5 rounded-xl text-xs font-semibold tracking-wide transition {{ request()->routeIs('admin.laporan.*') ? 'bg-primary-800 text-white font-bold shadow-sm ring-1 ring-white/10' : 'text-primary-100 hover:bg-primary-800/60 hover:text-white' }}">
                Laporan Pendapatan
            </a>
            <a href="{{ route('admin.profile.edit') }}"
               class="block px-4 py-2.5 rounded-xl text-xs font-semibold tracking-wide transition {{ request()->routeIs('admin.profile.edit') ? 'bg-primary-800 text-white font-bold shadow-sm ring-1 ring-white/10' : 'text-primary-100 hover:bg-primary-800/60 hover:text-white' }}">
                Profil Akun
            </a>

            {{-- Bottom Actions: Buka Halaman Publik & Logout --}}
            <div class="pt-4 mt-6 md:mt-auto border-t border-primary-800/80 space-y-2">
                <a href="{{ route('home') }}" target="_blank"
                   class="flex items-center w-full px-4 py-2.5 rounded-xl text-xs font-bold text-white bg-primary-800 hover:bg-primary-700/90 border border-primary-700/70 shadow-sm transition">
                    Buka Halaman Publik
                </a>

                <form action="{{ route('logout') }}" method="POST" class="w-full">
                    @csrf
                    <button type="submit"
                            class="flex items-center w-full px-4 py-2.5 rounded-xl text-xs font-bold text-rose-200 bg-rose-950/60 hover:bg-rose-900/80 hover:text-white border border-rose-800/60 shadow-sm transition">
                        Keluar (Logout)
                    </button>
                </form>
            </div>
        </nav>
    </aside>

    {{-- Main Area --}}
    <div class="flex-1 flex flex-col min-h-screen">
        {{-- Top Bar --}}
        <header class="bg-white border-b border-gray-200 shadow-sm py-4 px-6 sm:px-8 flex items-center justify-between">
            <h2 class="text-lg font-bold text-gray-800">@yield('page_title', 'Dashboard')</h2>
            <div class="flex items-center gap-3">
                <span class="text-xs font-semibold text-primary-700 bg-primary-50 border border-primary-200 px-3 py-1 rounded-full">
                    Admin / Owner CV. Ambius
                </span>
                <span class="text-sm font-semibold text-gray-700">{{ Auth::user()->nama ?? Auth::user()->name }}</span>
            </div>
        </header>

        {{-- Flash Messages --}}
        @if (session('success'))
            <div class="mt-4 mx-6 sm:mx-8">
                <div class="rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-700">
                    {{ session('success') }}
                </div>
            </div>
        @endif
        @if (session('error'))
            <div class="mt-4 mx-6 sm:mx-8">
                <div class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700">
                    {{ session('error') }}
                </div>
            </div>
        @endif
        @if (session('warning'))
            <div class="mt-4 mx-6 sm:mx-8">
                <div class="rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800 font-medium flex items-center gap-2">
                    <span>⚠️</span>
                    <span>{{ session('warning') }}</span>
                </div>
            </div>
        @endif

        {{-- Content Area --}}
        <main class="flex-1 p-6 sm:p-8">
            @yield('content')
        </main>
    </div>

    {{-- Menu Toggle Script --}}
    <script>
        document.getElementById('admin-menu-btn')?.addEventListener('click', function () {
            document.getElementById('admin-menu')?.classList.toggle('hidden');
        });
    </script>
</body>
</html>
