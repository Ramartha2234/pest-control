<?php

use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\LaporanController as AdminLaporanController;
use App\Http\Controllers\Admin\PelangganController as AdminPelangganController;
use App\Http\Controllers\Admin\PemesananController as AdminPemesananController;
use App\Http\Controllers\Admin\ProfileController as AdminProfileController;
use App\Http\Controllers\Admin\TarifLayananController as AdminTarifLayananController;
use App\Http\Controllers\Admin\TeknisiController as AdminTeknisiController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DokumenController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PemesananController;
use App\Http\Controllers\StatusController;
use App\Http\Controllers\Teknisi\TeknisiPemesananController;
use App\Http\Controllers\Teknisi\TeknisiProfileController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes - CV. Ambius Indonesia
|--------------------------------------------------------------------------
*/

// Public routes (Pelanggan - TANPA registrasi/login)
Route::get('/', [HomeController::class, 'index'])->name('home');

Route::get('/pesan', [PemesananController::class, 'create'])->name('pemesanan.create');
Route::get('/pesan/cek-slot', [PemesananController::class, 'cekSlot'])->name('pemesanan.cek-slot');
Route::post('/pesan', [PemesananController::class, 'store'])->name('pemesanan.store');
Route::get('/pesan/konfirmasi/{pemesanan}', [PemesananController::class, 'confirmation'])->name('pemesanan.confirmation');

// Halaman tracking (Throttle 10 percobaan/menit/IP)
Route::get('/cek-status', [StatusController::class, 'index'])->name('status.index')->middleware('throttle:10,1');
Route::post('/cek-status', [StatusController::class, 'show'])->name('status.show')->middleware('throttle:10,1');

// Unduh / lihat dokumen privat (dengan verifikasi hak akses)
Route::get('/dokumen/kehadiran/{buktiKehadiran}', [DokumenController::class, 'showBuktiKehadiran'])->name('dokumen.kehadiran');
Route::get('/dokumen/work-order/{workOrder}', [DokumenController::class, 'showWorkOrder'])->name('dokumen.wo');
Route::get('/dokumen/pembayaran/{buktiPembayaran}', [DokumenController::class, 'showBuktiPembayaran'])->name('dokumen.pembayaran');
Route::get('/dokumen/dp/{pemesanan}', [DokumenController::class, 'showBuktiDp'])->name('dokumen.dp');

// Auth routes (Admin & Teknisi login)
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login'])->name('login.post');
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// Admin routes (HANYA peran admin)
Route::prefix('admin')->name('admin.')->middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');

    // Manajemen Pemesanan
    Route::resource('pemesanan', AdminPemesananController::class)->except(['create', 'store']);
    Route::post('pemesanan/{pemesanan}/verifikasi-dp', [AdminPemesananController::class, 'verifikasiDp'])->name('pemesanan.verifikasi-dp');
    Route::post('pemesanan/{pemesanan}/verifikasi-awal', [AdminPemesananController::class, 'verifikasiDanTugaskan'])->name('pemesanan.verifikasi-awal');
    Route::post('pemesanan/{pemesanan}/tolak-awal', [AdminPemesananController::class, 'tolakPemesanan'])->name('pemesanan.tolak-awal');
    Route::post('pemesanan/{pemesanan}/cancel', [AdminPemesananController::class, 'cancel'])->name('pemesanan.cancel');
    Route::post('pemesanan/{pemesanan}/ubah-jadwal', [AdminPemesananController::class, 'ubahJadwal'])->name('pemesanan.ubah-jadwal');
    Route::post('pemesanan/{pemesanan}/verify-fiktif', [AdminPemesananController::class, 'verifyFiktif'])->name('pemesanan.verify-fiktif');
    Route::post('pemesanan/{pemesanan}/upload-bukti', [AdminPemesananController::class, 'uploadBukti'])->name('pemesanan.upload-bukti');

    // Verifikasi Dokumen & Pengajuan Lapangan
    Route::post('verifikasi/kehadiran/{buktiKehadiran}', [AdminPemesananController::class, 'verifikasiBuktiKehadiran'])->name('verifikasi.kehadiran');
    Route::post('verifikasi/hasil-survei/{hasilSurvei}', [AdminPemesananController::class, 'verifikasiHasilSurvei'])->name('verifikasi.hasil-survei');
    Route::post('verifikasi/work-order/{workOrder}', [AdminPemesananController::class, 'verifikasiWorkOrder'])->name('verifikasi.wo');
    Route::post('verifikasi/pembayaran/{buktiPembayaran}', [AdminPemesananController::class, 'verifikasiBuktiPembayaran'])->name('verifikasi.pembayaran');

    // CRUD Teknisi & Akun
    Route::resource('teknisi', AdminTeknisiController::class);

    // Data Pelanggan
    Route::get('pelanggan', [AdminPelangganController::class, 'index'])->name('pelanggan.index');
    Route::get('pelanggan/{pelanggan}', [AdminPelangganController::class, 'show'])->name('pelanggan.show');

    // Katalog Tarif Layanan
    Route::resource('tarif', AdminTarifLayananController::class)->except(['show']);
    Route::post('tarif/{tarif}/toggle', [AdminTarifLayananController::class, 'toggleStatus'])->name('tarif.toggle');
    Route::put('tarif-area/{tarifArea}', [AdminTarifLayananController::class, 'updateArea'])->name('tarif-area.update');
    Route::post('tarif-area/{tarifArea}/toggle', [AdminTarifLayananController::class, 'toggleAreaStatus'])->name('tarif-area.toggle');

    // Laporan Pendapatan (Hanya Done + Lunas)
    Route::get('laporan', [AdminLaporanController::class, 'index'])->name('laporan.index');

    // Profil Admin
    Route::get('profile', [AdminProfileController::class, 'edit'])->name('profile.edit');
    Route::put('profile', [AdminProfileController::class, 'update'])->name('profile.update');
});

// Teknisi routes (HANYA peran teknisi & penugasan miliknya)
Route::prefix('teknisi')->name('teknisi.')->middleware(['auth', 'role:teknisi'])->group(function () {
    Route::get('/', [TeknisiPemesananController::class, 'index'])->name('dashboard');
    Route::get('/tugas', [TeknisiPemesananController::class, 'tugas'])->name('tugas');
    Route::get('/riwayat', [TeknisiPemesananController::class, 'riwayat'])->name('riwayat');

    // Profil & Ubah Password
    Route::get('/profil', [TeknisiProfileController::class, 'edit'])->name('profil.edit');
    Route::put('/profil', [TeknisiProfileController::class, 'update'])->name('profil.update');
    Route::put('/profil/password', [TeknisiProfileController::class, 'updatePassword'])->name('profil.password');

    // Detail & Pengajuan Tugas
    Route::get('/pemesanan/{pemesanan}', [TeknisiPemesananController::class, 'show'])->name('pemesanan.show');
    Route::post('/pemesanan/{pemesanan}/kehadiran', [TeknisiPemesananController::class, 'uploadBuktiKehadiran'])->name('pemesanan.kehadiran');
    Route::post('/pemesanan/{pemesanan}/hasil-survei', [TeknisiPemesananController::class, 'submitHasilSurvei'])->name('pemesanan.hasil-survei');
    Route::post('/pemesanan/{pemesanan}/work-order', [TeknisiPemesananController::class, 'uploadWorkOrder'])->name('pemesanan.wo');
    Route::post('/pemesanan/{pemesanan}/pembayaran', [TeknisiPemesananController::class, 'uploadBuktiPembayaran'])->name('pemesanan.pembayaran');
});
