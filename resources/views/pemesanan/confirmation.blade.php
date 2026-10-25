@extends('layouts.app')

@section('title', 'Pemesanan Berhasil - CV. Ambius Indonesia')

@section('content')
<div class="py-16 bg-slate-50 min-h-screen flex items-center justify-center">
    <div class="mx-auto max-w-xl px-4 sm:px-6 w-full">
        <div class="relative rounded-3xl border border-slate-200 bg-white p-6 sm:p-10 text-center shadow-2xl overflow-hidden">
            <div class="absolute inset-x-0 top-0 h-2 bg-gradient-to-r from-emerald-500 via-teal-500 to-primary-600"></div>

            {{-- Checkmark Icon --}}
            <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-600 mb-5 shadow-sm">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>

            <h1 class="text-2xl font-black text-slate-900 tracking-tight">Pemesanan Anda Berhasil Dicatat!</h1>
            <p class="text-xs text-slate-500 mt-2 max-w-md mx-auto leading-relaxed">
                Terima kasih atas kepercayaan Anda kepada CV. Ambius Indonesia. Data survei Anda telah masuk antrean verifikasi teknisi.
            </p>

            {{-- Unique Code Highlight --}}
            @php
                $kode = $pemesanan->kode_pemesanan ?? $pemesanan->kode_unik;
            @endphp
            <div class="mt-6 rounded-2xl bg-gradient-to-br from-emerald-50 to-teal-50 p-6 border border-emerald-200 space-y-3">
                <span class="text-[11px] font-bold uppercase tracking-wider text-emerald-800">Kode Unik Pelacakan Anda</span>
                <div class="flex items-center justify-center gap-3">
                    <span id="kodeUnik" class="text-2xl sm:text-3xl font-black font-mono tracking-widest text-emerald-950 select-all">{{ $kode }}</span>
                    <button onclick="copyToClipboard()" class="rounded-xl bg-white p-2 text-emerald-700 hover:bg-emerald-100 shadow-sm transition border border-emerald-200" title="Salin Kode">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3" />
                        </svg>
                    </button>
                </div>
                <div id="copyFeedback" class="hidden text-xs text-emerald-700 font-bold">Kode berhasil disalin!</div>
            </div>

            {{-- Estimasi & Detail Ringkas --}}
            <div class="mt-4 rounded-2xl bg-slate-50/90 p-4 border border-slate-200 grid grid-cols-1 sm:grid-cols-3 gap-3 sm:gap-0 divide-y sm:divide-y-0 sm:divide-x divide-slate-200">
                <div class="flex flex-col items-center justify-center text-center pb-3 sm:pb-0 sm:px-3">
                    <span class="text-[10px] text-slate-400 font-bold uppercase tracking-wider block mb-1">Layanan Dipilih</span>
                    <span class="font-bold text-slate-800 text-xs sm:text-sm block leading-snug">{{ $pemesanan->tarifLayanan->jenis_layanan ?? '-' }}</span>
                </div>
                <div class="flex flex-col items-center justify-center text-center py-3 sm:py-0 sm:px-3">
                    <span class="text-[10px] text-slate-400 font-bold uppercase tracking-wider block mb-1">Pilihan Area</span>
                    <span class="inline-flex items-center text-xs font-bold px-3 py-1 rounded-lg border {{ ($pemesanan->tipe_area ?? 'Indoor') === 'Outdoor' ? 'bg-amber-100 text-amber-800 border-amber-200' : 'bg-emerald-100 text-emerald-800 border-emerald-200' }}">
                        {{ ($pemesanan->tipe_area ?? 'Indoor') === 'Outdoor' ? 'Outdoor (+Rp 100.000)' : 'Indoor' }}
                    </span>
                </div>
                <div class="flex flex-col items-center justify-center text-center pt-3 sm:pt-0 sm:px-3">
                    <span class="text-[10px] text-slate-400 font-bold uppercase tracking-wider block mb-1">Estimasi Biaya</span>
                    <span class="font-extrabold text-emerald-700 text-sm sm:text-base block tracking-tight">Rp {{ number_format($pemesanan->harga_estimasi, 0, ',', '.') }}</span>
                </div>
            </div>

            {{-- Peringatan 3 Hari Auto-Cancel --}}
            <div class="mt-4 rounded-xl bg-amber-50 p-4 border border-amber-200 text-left text-xs text-amber-900 space-y-1">
                <strong class="block font-bold">
                    Ketentuan Konfirmasi (Maksimal 3 Hari):
                </strong>
                <ul class="list-disc pl-4 space-y-1 mt-1 text-[11px] text-amber-800">
                    <li>Simpan atau catat <b>Kode Pemesanan</b> di atas untuk mengecek status secara berkala.</li>
                    <li>Admin kami akan menghubungi Anda via WhatsApp. Anda juga dapat langsung mengonfirmasi lewat tombol hijau di bawah.</li>
                    <li><b>Batas waktu konfirmasi adalah 3 hari</b> (sampai {{ $pemesanan->batas_konfirmasi->translatedFormat('d F Y H:i') }} WITA). Pemesanan yang belum terkonfirmasi setelah batas waktu akan dibatalkan otomatis oleh sistem.</li>
                </ul>
            </div>

            {{-- WhatsApp Confirmation Button (Alur 2) --}}
            <div class="mt-6">
                <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', config('pestcontrol.whatsapp_number')) }}?text=Halo%20Admin%20CV.%20Ambius%20Indonesia,%20saya%20ingin%20konfirmasi%20pemesanan%20jasa%20pest%20control%20dengan%20Kode%20Pemesanan:%20{{ $kode }}"
                   target="_blank" rel="noopener"
                   class="w-full inline-flex items-center justify-center gap-2 rounded-2xl bg-emerald-600 hover:bg-emerald-700 px-6 py-3.5 text-xs sm:text-sm font-extrabold text-white shadow-lg transition">
                    <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                    Konfirmasi via WhatsApp Sekarang
                </a>
            </div>

            {{-- Nav Actions --}}
            <div class="mt-6 flex flex-col sm:flex-row gap-2 justify-center">
                <a href="{{ route('status.index', ['code' => $kode]) }}" class="rounded-xl border border-slate-300 bg-white px-5 py-2.5 text-xs font-bold text-slate-700 hover:bg-slate-50 transition">
                    Cek Status Pesanan
                </a>
                <a href="{{ route('home') }}" class="rounded-xl border border-transparent px-5 py-2.5 text-xs font-semibold text-slate-500 hover:text-slate-800 transition">
                    Kembali ke Beranda
                </a>
            </div>
        </div>
    </div>
</div>

<script>
    function copyToClipboard() {
        const text = document.getElementById('kodeUnik').innerText.trim();
        navigator.clipboard.writeText(text).then(() => {
            const feedback = document.getElementById('copyFeedback');
            feedback.classList.remove('hidden');
            setTimeout(() => {
                feedback.classList.add('hidden');
            }, 2500);
        });
    }
</script>
@endsection
