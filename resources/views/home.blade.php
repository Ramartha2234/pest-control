@extends('layouts.app')

@section('title', 'CV. Ambius Indonesia - Solusi Profesional Pengendalian Hama')

@section('content')
    {{-- Hero Section --}}
    <section class="relative overflow-hidden bg-gradient-to-br from-primary-900 via-primary-800 to-gray-950 py-20 text-white sm:py-32">
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_right,var(--color-primary-600),transparent_45%)] opacity-30"></div>
        <div class="relative mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 gap-12 lg:grid-cols-2 lg:items-center">
                <div class="space-y-8 text-center lg:text-left">
                    <h1 class="text-4xl font-extrabold tracking-tight sm:text-5xl lg:text-6xl">
                        Bebaskan Rumah Anda Dari <span class="bg-gradient-to-r from-primary-300 to-accent-400 bg-clip-text text-transparent">Gangguan Hama</span>
                    </h1>
                    <p class="mx-auto max-w-xl text-lg text-gray-300 lg:mx-0">
                        CV. Ambius menyediakan jasa pest control profesional, cepat, aman, dan bergaransi untuk hunian, kantor, bisnis kuliner, dan pergudangan Anda.
                    </p>
                    <div class="flex flex-col justify-center gap-4 sm:flex-row lg:justify-start">
                        <a href="{{ route('pemesanan.create') }}" class="rounded-xl bg-primary-500 px-8 py-4 text-base font-bold text-white shadow-lg transition hover:bg-primary-600 hover:scale-105">
                            Pesan Jasa Sekarang
                        </a>
                        <a href="{{ route('status.index') }}" class="rounded-xl border border-white/20 bg-white/5 px-8 py-4 text-base font-bold text-white backdrop-blur transition hover:bg-white/10 hover:scale-105">
                            Cek Status Pesanan
                        </a>
                    </div>
                </div>

                {{-- Interactive Card Visual --}}
                <div class="relative mx-auto max-w-md lg:max-w-none">
                    <div class="absolute -inset-1 rounded-2xl bg-gradient-to-r from-primary-500 to-accent-500 opacity-35 blur-lg"></div>
                    <div class="relative rounded-2xl border border-white/10 bg-gray-900/60 p-8 backdrop-blur-xl shadow-2xl">
                        <div class="flex items-center justify-between pb-6 border-b border-white/10">
                            <div>
                                <h3 class="text-lg font-bold text-white">CV. Ambius Indonesia</h3>
                                <p class="text-xs text-gray-400">Order Online Instant</p>
                            </div>
                            <span class="h-2 w-2 rounded-full bg-green-400 animate-pulse"></span>
                        </div>

                        <div class="mt-6 space-y-4">
                            <div class="flex items-start gap-4">
                                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-primary-500/20 text-primary-400">
                                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                    </svg>
                                </div>
                                <div>
                                    <h4 class="text-sm font-semibold text-white">Tanpa Buat Akun</h4>
                                    <p class="text-xs text-gray-400">Cukup isi data lokasi dan pilih layanan untuk memesan secara langsung.</p>
                                </div>
                            </div>

                            <div class="flex items-start gap-4">
                                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-primary-500/20 text-primary-400">
                                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                                    </svg>
                                </div>
                                <div>
                                    <h4 class="text-sm font-semibold text-white">Kode Unik & Tracking Real-time</h4>
                                    <p class="text-xs text-gray-400">Dapatkan kode unik instan untuk memantau status kerja teknisi secara live.</p>
                                </div>
                            </div>

                            <div class="flex items-start gap-4">
                                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-primary-500/20 text-primary-400">
                                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                    </svg>
                                </div>
                                <div>
                                    <h4 class="text-sm font-semibold text-white">Unduh Bukti Pekerjaan</h4>
                                    <p class="text-xs text-gray-400">Dokumentasi hasil treatment yang rapi berupa foto bukti kerja siap unduh.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Services Section --}}
    <section class="py-24 bg-white">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-3xl mx-auto space-y-4">
                <span class="text-sm font-semibold tracking-wider text-primary-600 uppercase">Layanan Kami</span>
                <h2 class="text-3xl font-bold tracking-tight text-gray-900 sm:text-4xl">Jenis Layanan Pengendalian Hama</h2>
                <p class="text-lg text-gray-600">CV. Ambius menangani berbagai macam masalah hama dengan metode teruji dan aman untuk kesehatan keluarga/karyawan Anda.</p>
            </div>

            <div class="mt-16 flex flex-wrap justify-center gap-8">
                {{-- Service 1 --}}
                <div class="group relative w-full sm:w-[calc(50%-1rem)] lg:w-[calc(33.333%-1.35rem)] shrink-0 rounded-2xl border border-slate-200 bg-slate-50/80 p-7 shadow-2xs transition duration-200 hover:bg-white hover:shadow-md hover:border-emerald-300 hover:-translate-y-1 flex flex-col items-center text-center">
                    <h3 class="text-lg sm:text-xl font-bold text-slate-900 group-hover:text-emerald-700 transition">
                        Pengendalian Rayap
                        <span class="block text-sm font-semibold text-emerald-700 mt-1">(Termite Control)</span>
                    </h3>
                    <p class="mt-3.5 text-sm text-slate-600 leading-relaxed">
                        Metode proteksi dan pembasmian koloni rayap tanah secara menyeluruh untuk melindungi struktur bangunan kayu Anda.
                    </p>
                </div>

                {{-- Service 2 --}}
                <div class="group relative w-full sm:w-[calc(50%-1rem)] lg:w-[calc(33.333%-1.35rem)] shrink-0 rounded-2xl border border-slate-200 bg-slate-50/80 p-7 shadow-2xs transition duration-200 hover:bg-white hover:shadow-md hover:border-emerald-300 hover:-translate-y-1 flex flex-col items-center text-center">
                    <h3 class="text-lg sm:text-xl font-bold text-slate-900 group-hover:text-emerald-700 transition">
                        Pengendalian Tikus
                        <span class="block text-sm font-semibold text-emerald-700 mt-1">(Rodent Control)</span>
                    </h3>
                    <p class="mt-3.5 text-sm text-slate-600 leading-relaxed">
                        Instalasi baiting station dan trapping professional untuk membasmi perkembangbiakan tikus got/atap di area properti.
                    </p>
                </div>

                {{-- Service 3 --}}
                <div class="group relative w-full sm:w-[calc(50%-1rem)] lg:w-[calc(33.333%-1.35rem)] shrink-0 rounded-2xl border border-slate-200 bg-slate-50/80 p-7 shadow-2xs transition duration-200 hover:bg-white hover:shadow-md hover:border-emerald-300 hover:-translate-y-1 flex flex-col items-center text-center">
                    <h3 class="text-lg sm:text-xl font-bold text-slate-900 group-hover:text-emerald-700 transition">
                        Pengendalian Kecoa
                        <span class="block text-sm font-semibold text-emerald-700 mt-1">(Cockroach Control)</span>
                    </h3>
                    <p class="mt-3.5 text-sm text-slate-600 leading-relaxed">
                        Umpan gel ramah lingkungan dan cold fogging untuk membasmi kecoa jerman maupun kecoa amerika hingga ke sarangnya.
                    </p>
                </div>

                {{-- Service 4 --}}
                <div class="group relative w-full sm:w-[calc(50%-1rem)] lg:w-[calc(33.333%-1.35rem)] shrink-0 rounded-2xl border border-slate-200 bg-slate-50/80 p-7 shadow-2xs transition duration-200 hover:bg-white hover:shadow-md hover:border-emerald-300 hover:-translate-y-1 flex flex-col items-center text-center">
                    <h3 class="text-lg sm:text-xl font-bold text-slate-900 group-hover:text-emerald-700 transition">
                        Pengendalian Nyamuk
                        <span class="block text-sm font-semibold text-emerald-700 mt-1">(Mosquito Control)</span>
                    </h3>
                    <p class="mt-3.5 text-sm text-slate-600 leading-relaxed">
                        Metode fogging termal (thermal fogging) dan misting untuk menekan populasi nyamuk penyebar penyakit DBD/Malaria.
                    </p>
                </div>

                {{-- Service 5 --}}
                <div class="group relative w-full sm:w-[calc(50%-1rem)] lg:w-[calc(33.333%-1.35rem)] shrink-0 rounded-2xl border border-slate-200 bg-slate-50/80 p-7 shadow-2xs transition duration-200 hover:bg-white hover:shadow-md hover:border-emerald-300 hover:-translate-y-1 flex flex-col items-center text-center">
                    <h3 class="text-lg sm:text-xl font-bold text-slate-900 group-hover:text-emerald-700 transition">
                        Pengendalian Lalat
                        <span class="block text-sm font-semibold text-emerald-700 mt-1">(Fly Control)</span>
                    </h3>
                    <p class="mt-3.5 text-sm text-slate-600 leading-relaxed">
                        Pemasangan fly trap (insect trap) dan misting untuk mengendalikan populasi lalat di area komersial maupun hunian.
                    </p>
                </div>
            </div>
        </div>
    </section>

    {{-- Order Flow Section --}}
    <section class="py-24 bg-gray-50">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-3xl mx-auto space-y-4">
                <span class="text-sm font-semibold tracking-wider text-primary-600 uppercase">Alur Layanan</span>
                <h2 class="text-3xl font-bold tracking-tight text-gray-900 sm:text-4xl">Cara Melakukan Pemesanan</h2>
                <p class="text-lg text-gray-600">Hanya butuh 4 langkah mudah untuk menyelesaikan pengerjaan hama properti Anda.</p>
            </div>

            <div class="mt-16 grid grid-cols-1 gap-8 md:grid-cols-4 relative">
                <div class="hidden md:block absolute top-1/2 left-4 right-4 h-0.5 bg-primary-100 -z-10 -translate-y-1/2"></div>
                
                {{-- Step 1 --}}
                <div class="flex flex-col items-center text-center p-6 bg-white rounded-2xl border border-gray-100 shadow-sm">
                    <div class="flex h-12 w-12 items-center justify-center rounded-full bg-primary-500 text-white font-bold text-lg mb-4">1</div>
                    <h3 class="font-bold text-gray-900">Isi Form Booking</h3>
                    <p class="mt-2 text-xs text-gray-500">Tentukan alamat pengerjaan, estimasi biaya, dan jenis hama tanpa login.</p>
                </div>

                {{-- Step 2 --}}
                <div class="flex flex-col items-center text-center p-6 bg-white rounded-2xl border border-gray-100 shadow-sm">
                    <div class="flex h-12 w-12 items-center justify-center rounded-full bg-primary-500 text-white font-bold text-lg mb-4">2</div>
                    <h3 class="font-bold text-gray-900">Simpan Kode Unik</h3>
                    <p class="mt-2 text-xs text-gray-500">Gunakan kode unik pemesanan Anda untuk memantau status pesanan kapan saja.</p>
                </div>

                {{-- Step 3 --}}
                <div class="flex flex-col items-center text-center p-6 bg-white rounded-2xl border border-gray-100 shadow-sm">
                    <div class="flex h-12 w-12 items-center justify-center rounded-full bg-primary-500 text-white font-bold text-lg mb-4">3</div>
                    <h3 class="font-bold text-gray-900">Survey & Treatment</h3>
                    <p class="mt-2 text-xs text-gray-500">Teknisi kami datang melakukan survei, pengerjaan, dan update status on-going.</p>
                </div>

                {{-- Step 4 --}}
                <div class="flex flex-col items-center text-center p-6 bg-white rounded-2xl border border-gray-100 shadow-sm">
                    <div class="flex h-12 w-12 items-center justify-center rounded-full bg-primary-500 text-white font-bold text-lg mb-4">4</div>
                    <h3 class="font-bold text-gray-900">Lunas & Unduh Bukti</h3>
                    <p class="mt-2 text-xs text-gray-500">Setelah dilunasi, status berubah jadi Done & Anda bisa unduh bukti.</p>
                </div>
            </div>
        </div>
    </section>

    {{-- Call to Action Section --}}
    <section class="bg-primary-900 py-16 text-white text-center relative overflow-hidden">
        <div class="absolute inset-0 bg-primary-800/50 mix-blend-overlay"></div>
        <div class="relative mx-auto max-w-4xl px-4 sm:px-6 lg:px-8 space-y-6">
            <h2 class="text-3xl font-extrabold sm:text-4xl">Siap membasmi hama mengganggu hari ini?</h2>
            <p class="text-lg text-primary-200">Tidak perlu repot membuat akun. Cukup isi form pemesanan kami dan serahkan ke tim ahli CV. Ambius.</p>
            <div class="flex flex-col justify-center gap-4 sm:flex-row pt-4">
                <a href="{{ route('pemesanan.create') }}" class="rounded-xl bg-white px-8 py-4 text-base font-bold text-primary-900 shadow-lg transition hover:bg-gray-100 hover:scale-105">
                    Mulai Pesan Jasa
                </a>
                <a href="https://wa.me/{{ config('pestcontrol.whatsapp_number') }}" target="_blank" rel="noopener" class="rounded-xl border border-primary-400 bg-primary-800 px-8 py-4 text-base font-bold text-white transition hover:bg-primary-700 hover:scale-105 flex items-center justify-center gap-2">
                    Hubungi via WhatsApp
                </a>
            </div>
        </div>
    </section>
@endsection
