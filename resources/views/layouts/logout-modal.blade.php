{{-- Modal Konfirmasi Logout (Admin & Teknisi) --}}
<div id="logout-confirm-modal"
     role="dialog"
     aria-modal="true"
     aria-labelledby="logout-modal-title"
     class="fixed inset-0 z-50 hidden bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="w-full max-w-sm rounded-2xl bg-white p-6 shadow-2xl border border-slate-100">
        {{-- Ikon Keluar --}}
        <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-rose-50 text-rose-600 ring-8 ring-rose-50/60">
            <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
            </svg>
        </div>

        {{-- Teks Konfirmasi --}}
        <div class="mt-4 text-center">
            <h3 id="logout-modal-title" class="text-base font-extrabold text-slate-900">
                Konfirmasi Log Out
            </h3>
            <p class="mt-1.5 text-xs sm:text-sm text-slate-500 leading-relaxed">
                Apakah Anda yakin ingin log out dari akun Anda?
            </p>
            @auth
                <div class="mt-3 inline-flex items-center gap-1.5 rounded-full bg-slate-50 border border-slate-200 px-3 py-1 text-[11px] font-semibold text-slate-600">
                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                    <span>{{ Auth::user()->nama ?? Auth::user()->name }}</span>
                </div>
            @endauth
        </div>

        {{-- Tombol Aksi --}}
        <div class="mt-6 grid grid-cols-2 gap-2.5">
            <button type="button"
                    onclick="closeLogoutModal()"
                    class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-xs font-bold text-slate-700 hover:bg-slate-50 transition cursor-pointer">
                Batal
            </button>

            <form action="{{ route('logout') }}" method="POST" class="w-full">
                @csrf
                <button type="submit"
                        style="background-color: #e11d48 !important; color: #ffffff !important;"
                        class="w-full rounded-xl bg-rose-600 hover:bg-rose-700 px-4 py-2.5 text-xs font-bold text-white shadow-sm transition cursor-pointer active:scale-95">
                    Ya, Log Out
                </button>
            </form>
        </div>
    </div>
</div>

<script>
    window.openLogoutModal = function () {
        const modal = document.getElementById('logout-confirm-modal');
        if (modal) {
            modal.classList.remove('hidden');
        }
    };

    window.closeLogoutModal = function () {
        const modal = document.getElementById('logout-confirm-modal');
        if (modal) {
            modal.classList.add('hidden');
        }
    };

    document.getElementById('logout-confirm-modal')?.addEventListener('click', function (e) {
        if (e.target === this) {
            window.closeLogoutModal();
        }
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            window.closeLogoutModal();
        }
    });
</script>
