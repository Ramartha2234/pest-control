@extends('layouts.admin')

@section('title', 'Pengaturan Sistem - CV. Ambius')
@section('page_title', 'Pengaturan Sistem & Akun')

@section('content')
<div class="w-full">
    <div class="bg-white rounded-xl border border-gray-200 p-6 sm:p-8 shadow-sm">
        <form id="form-pengaturan" action="{{ route('admin.profile.update') }}" method="POST" class="space-y-8">
            @csrf
            @method('PUT')

            <!-- Section 1: Rekening Pembayaran DP Perusahaan -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 pb-6 border-b border-gray-200">
                <div class="space-y-1">
                    <h3 class="text-sm font-bold text-gray-900 uppercase tracking-wider">Rekening Bank Perusahaan</h3>
                    <p class="text-xs text-gray-500">
                        Rekening resmi CV. Ambius Indonesia untuk transfer manual Down Payment (DP 10%) pesanan skala besar.
                    </p>
                    <div class="mt-3 rounded-lg bg-emerald-50 border border-emerald-200 p-3 text-[11px] text-emerald-800 space-y-1">
                        <span class="font-bold block">Sinkronisasi Otomatis</span>
                        <p class="text-emerald-700">Perubahan nomor rekening dan kontak WA akan langsung tampil pada halaman konfirmasi pembayaran pelanggan.</p>
                    </div>
                </div>
                <div class="lg:col-span-2 grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="bank_name" class="block text-xs font-bold uppercase text-gray-400 tracking-wider">Nama Bank</label>
                        <input type="text" name="bank_name" id="bank_name" value="{{ old('bank_name', $bankName) }}" required
                               class="mt-2 block w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-900 shadow-sm focus:border-primary-500 focus:ring-primary-500 focus:outline-none"
                               placeholder="Contoh: Bank Central Asia (BCA)">
                        @error('bank_name')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="bank_account_number" class="block text-xs font-bold uppercase text-gray-400 tracking-wider">Nomor Rekening</label>
                        <input type="text" name="bank_account_number" id="bank_account_number" value="{{ old('bank_account_number', $bankAccountNumber) }}" required
                               class="mt-2 block w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-mono text-gray-900 shadow-sm focus:border-primary-500 focus:ring-primary-500 focus:outline-none"
                               placeholder="Contoh: 123-456-7890">
                        @error('bank_account_number')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="bank_account_holder" class="block text-xs font-bold uppercase text-gray-400 tracking-wider">Atas Nama Rekening</label>
                        <input type="text" name="bank_account_holder" id="bank_account_holder" value="{{ old('bank_account_holder', $bankAccountHolder) }}" required
                               class="mt-2 block w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-900 shadow-sm focus:border-primary-500 focus:ring-primary-500 focus:outline-none"
                               placeholder="Contoh: CV. Ambius Indonesia">
                        @error('bank_account_holder')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="whatsapp_number" class="block text-xs font-bold uppercase text-gray-400 tracking-wider">No. WhatsApp Resmi</label>
                        <input type="text" name="whatsapp_number" id="whatsapp_number" value="{{ old('whatsapp_number', $whatsappNumber) }}" required
                               class="mt-2 block w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-mono text-gray-900 shadow-sm focus:border-primary-500 focus:ring-primary-500 focus:outline-none"
                               placeholder="Contoh: 6281234567890">
                        <span class="text-[10px] text-gray-400 mt-1 block">Format: 628xxx (tanpa tanda + atau spasi)</span>
                        @error('whatsapp_number')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Section 2: Penandatangan Laporan Keuangan (PDF) -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 pb-6 border-b border-gray-200">
                <div class="space-y-1">
                    <h3 class="text-sm font-bold text-gray-900 uppercase tracking-wider">Penandatangan Laporan (PDF)</h3>
                    <p class="text-xs text-gray-500">
                        Pengaturan nama pejabat resmi pada lembar pengesahan tanda tangan (TTD) di bagian bawah dokumen Cetak / Ekspor PDF Laporan Pendapatan.
                    </p>
                    <div class="mt-3 rounded-lg bg-blue-50 border border-blue-200 p-3 text-[11px] text-blue-800 space-y-1">
                        <span class="font-bold block">Posisi Lembar Pengesahan</span>
                        <p class="text-blue-700">• Pojok Kiri Bawah: <strong>Accounting</strong></p>
                        <p class="text-blue-700">• Pojok Kanan Bawah: <strong>Direktur</strong></p>
                    </div>
                </div>
                <div class="lg:col-span-2 grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="nama_accounting" class="block text-xs font-bold uppercase text-gray-400 tracking-wider">Nama Accounting (Pojok Kiri Bawah)</label>
                        <input type="text" name="nama_accounting" id="nama_accounting" value="{{ old('nama_accounting', $namaAccounting) }}" required
                               class="mt-2 block w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-900 shadow-sm focus:border-primary-500 focus:ring-primary-500 focus:outline-none"
                               placeholder="Contoh: Siti Rahmawati, S.Ak.">
                        @error('nama_accounting')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="nama_direktur" class="block text-xs font-bold uppercase text-gray-400 tracking-wider">Nama Direktur (Pojok Kanan Bawah)</label>
                        <input type="text" name="nama_direktur" id="nama_direktur" value="{{ old('nama_direktur', $namaDirektur) }}" required
                               class="mt-2 block w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-900 shadow-sm focus:border-primary-500 focus:ring-primary-500 focus:outline-none"
                               placeholder="Contoh: Budi Santoso, S.T.">
                        @error('nama_direktur')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Section 3: Informasi Akun Admin -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 pb-6 border-b border-gray-200">
                <div class="space-y-1">
                    <h3 class="text-sm font-bold text-gray-900 uppercase tracking-wider">Akun Administrator</h3>
                    <p class="text-xs text-gray-500">Perbarui nama lengkap dan alamat email akun admin Anda.</p>
                </div>
                <div class="lg:col-span-2 space-y-4">
                    <div>
                        <label for="name" class="block text-xs font-bold uppercase text-gray-400 tracking-wider">Nama Lengkap</label>
                        <input type="text" name="name" id="name" value="{{ old('name', $user->name) }}" required
                               class="mt-2 block w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-900 shadow-sm focus:border-primary-500 focus:ring-primary-500 focus:outline-none"
                               placeholder="Contoh: Owner CV. Ambius">
                        @error('name')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="email" class="block text-xs font-bold uppercase text-gray-400 tracking-wider">Alamat Email</label>
                        <input type="email" name="email" id="email" value="{{ old('email', $user->email) }}" required
                               class="mt-2 block w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-900 shadow-sm focus:border-primary-500 focus:ring-primary-500 focus:outline-none"
                               placeholder="admin@ambius.com">
                        @error('email')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Section 4: Keamanan / Ubah Password -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 pb-6 border-b border-gray-200">
                <div class="space-y-1">
                    <h3 class="text-sm font-bold text-gray-900 uppercase tracking-wider">Ubah Password</h3>
                    <p class="text-xs text-gray-500">Biarkan kosong jika Anda tidak ingin mengubah password saat ini.</p>
                </div>
                <div class="lg:col-span-2 grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="password" class="block text-xs font-bold uppercase text-gray-400 tracking-wider">Password Baru</label>
                        <input type="password" name="password" id="password"
                               class="mt-2 block w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-900 shadow-sm focus:border-primary-500 focus:ring-primary-500 focus:outline-none"
                               placeholder="Min. 8 karakter">
                        @error('password')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="password_confirmation" class="block text-xs font-bold uppercase text-gray-400 tracking-wider">Konfirmasi Password Baru</label>
                        <input type="password" name="password_confirmation" id="password_confirmation"
                               class="mt-2 block w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-900 shadow-sm focus:border-primary-500 focus:ring-primary-500 focus:outline-none"
                               placeholder="Ulangi password baru">
                    </div>
                </div>
            </div>

            <!-- Action Button -->
            <div class="flex justify-end pt-2">
                <button type="button"
                        onclick="confirmSimpanPengaturan()"
                        class="rounded-xl bg-primary-500 hover:bg-primary-600 px-8 py-3 text-sm font-bold text-white shadow-md transition hover:scale-[1.01] flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                    </svg>
                    <span>Simpan Pengaturan</span>
                </button>
            </div>
        </form>
    </div>
</div>

{{-- MODAL DECISION KONFIRMASI: SIMPAN PERUBAHAN DATA? (YA / TIDAK) --}}
<div id="modal-confirm-simpan" class="fixed inset-0 z-50 hidden bg-black/50 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl space-y-5 animate-in fade-in duration-200" onclick="event.stopPropagation()">
        <div class="flex items-start gap-4">
            <div class="h-12 w-12 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-600 flex items-center justify-center flex-shrink-0">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <div>
                <h3 class="text-base font-extrabold text-gray-900">Simpan Perubahan Data?</h3>
                <p class="text-xs text-gray-500 mt-1 leading-relaxed">
                    Apakah Anda yakin ingin menyimpan seluruh perubahan pada Pengaturan Sistem &amp; Akun ini?
                </p>
            </div>
        </div>

        <div class="rounded-xl bg-gray-50 border border-gray-100 p-3.5 text-xs text-gray-600 space-y-1.5">
            <p class="font-bold text-gray-700">Perubahan data yang disimpan mencakup:</p>
            <ul class="list-disc list-inside text-gray-500 text-[11px] space-y-1">
                <li>Informasi rekening bank &amp; nomor WhatsApp resmi perusahaan</li>
                <li>Pejabat penandatangan laporan PDF (Accounting &amp; Direktur)</li>
                <li>Profil akun administrator (nama, email, dan password bila diisi)</li>
            </ul>
        </div>

        <div class="flex items-center justify-end gap-3 pt-3 border-t border-gray-100">
            <button type="button"
                    onclick="closeConfirmModal()"
                    class="rounded-xl border border-gray-300 px-5 py-2.5 text-xs font-bold text-gray-700 hover:bg-gray-100 transition">
                Tidak
            </button>
            <button type="button"
                    onclick="submitPengaturanForm()"
                    class="rounded-xl bg-primary-600 hover:bg-primary-700 px-6 py-2.5 text-xs font-bold text-white shadow-md transition hover:scale-[1.01] flex items-center gap-1.5">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                </svg>
                <span>Ya, Simpan</span>
            </button>
        </div>
    </div>
</div>

<script>
    function confirmSimpanPengaturan() {
        const form = document.getElementById('form-pengaturan');
        if (!form) return;

        // Validasi form HTML5 sebelum modal muncul
        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }

        const modal = document.getElementById('modal-confirm-simpan');
        if (modal) {
            modal.classList.remove('hidden');
        }
    }

    function closeConfirmModal() {
        const modal = document.getElementById('modal-confirm-simpan');
        if (modal) {
            modal.classList.add('hidden');
        }
    }

    function submitPengaturanForm() {
        const form = document.getElementById('form-pengaturan');
        if (form) {
            form.submit();
        }
    }

    // Menutup modal dengan tombol Escape atau klik di luar modal (backdrop)
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeConfirmModal();
        }
    });

    document.getElementById('modal-confirm-simpan')?.addEventListener('click', function(e) {
        if (e.target === this) {
            closeConfirmModal();
        }
    });
</script>
@endsection
