<?php

use App\Models\JadwalPenugasan;
use App\Models\Pelanggan;
use App\Models\Pemesanan;
use App\Models\Pengaturan;
use App\Models\Pengguna;
use App\Models\TarifLayanan;
use App\Services\PemesananStatusService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

test('pesanan di bawah 10 juta tidak mewajibkan down payment (DP)', function () {
    $tarif = TarifLayanan::create([
        'jenis_layanan' => 'Pengendalian Kecoa (Cockroach Control)',
        'satuan' => 'm2',
        'tarif_dasar' => 50000,
        'status_aktif' => true,
    ]);

    $service = app(PemesananStatusService::class);
    $pemesanan = $service->buatPemesanan([
        'nama' => 'Budi Client',
        'no_hp' => '081234567890',
        'id_tarif' => $tarif->id_tarif,
        'jenis_properti' => 'Rumah Tinggal',
        'tipe_area' => 'Indoor',
        'alamat_lokasi' => 'Jl. Mawar No. 10',
        'keluhan' => 'Kecoa di dapur',
        'ukuran_area' => 50, // 50 * 50.000 = 2.500.000 (< 10jt)
        'tgl_survei_diminta' => now()->addDay()->toDateString(),
        'jam_survei_diminta' => '09:00',
    ]);

    expect($pemesanan->wajib_dp)->toBeFalse();
    expect((float) $pemesanan->nominal_dp)->toBe(0.0);
    expect($pemesanan->status_dp)->toBeNull();
    expect($pemesanan->canScheduleSurvei())->toBeTrue();
});

test('pesanan nominal 10 juta ke atas otomatis mewajibkan DP 10% dengan status Belum Dibayar', function () {
    $tarif = TarifLayanan::create([
        'jenis_layanan' => 'Pengendalian Rayap Industri (Termite Control)',
        'satuan' => 'm2',
        'tarif_dasar' => 100000,
        'status_aktif' => true,
    ]);

    $service = app(PemesananStatusService::class);
    $pemesanan = $service->buatPemesanan([
        'nama' => 'PT. Mega Properti',
        'no_hp' => '081298765432',
        'id_tarif' => $tarif->id_tarif,
        'jenis_properti' => 'Gudang/Pabrik',
        'tipe_area' => 'Outdoor', // +100.000
        'alamat_lokasi' => 'Kawasan Industri MM2100',
        'keluhan' => 'Rayap pada palet kayu gudang besar',
        'ukuran_area' => 150, // 150 * 100.000 + 100.000 = 15.100.000 (>= 10jt)
        'tgl_survei_diminta' => now()->addDays(2)->toDateString(),
        'jam_survei_diminta' => '10:00',
    ]);

    expect($pemesanan->wajib_dp)->toBeTrue();
    expect((float) $pemesanan->harga_estimasi)->toBe(15100000.0);
    expect((float) $pemesanan->nominal_dp)->toBe(1510000.0); // 10%
    expect($pemesanan->status_dp)->toBe('Belum Dibayar');
    expect($pemesanan->canScheduleSurvei())->toBeFalse();
});

test('halaman konfirmasi menampilkan no rekening dan nominal DP 10% untuk pesanan >= 10 juta', function () {
    $tarif = TarifLayanan::create([
        'jenis_layanan' => 'Pengendalian Hama Gudang',
        'satuan' => 'm2',
        'tarif_dasar' => 12000000,
        'status_aktif' => true,
    ]);

    $pelanggan = Pelanggan::create([
        'nama' => 'Fauzi Rahman',
        'no_hp' => '081345678912',
    ]);

    $pemesanan = Pemesanan::create([
        'id_pelanggan' => $pelanggan->id_pelanggan,
        'id_tarif' => $tarif->id_tarif,
        'kode_pemesanan' => 'AMB-TESTDP1',
        'jenis_properti' => 'Gudang/Pabrik',
        'tipe_area' => 'Indoor',
        'alamat_lokasi' => 'Jl. Gatot Subroto No. 88',
        'keluhan' => 'Penanganan rayap gedung',
        'tgl_survei_diminta' => now()->addDay()->toDateString(),
        'jam_survei_diminta' => '09:00',
        'harga_estimasi' => 12000000,
        'wajib_dp' => true,
        'nominal_dp' => 1200000,
        'status_dp' => 'Belum Dibayar',
        'status_pengerjaan' => 'Pending',
        'status_pembayaran' => 'Belum Lunas',
        'tgl_pemesanan' => now(),
        'batas_konfirmasi' => now()->addDays(3),
    ]);

    $response = $this->get(route('pemesanan.confirmation', $pemesanan->id_pemesanan));

    $response->assertSuccessful();
    $response->assertSee('Pemesanan Skala Besar (Wajib DP 10%)');
    $response->assertSee('1.200.000');
    $response->assertSee(config('pestcontrol.bank_account_number'));
    $response->assertSee(config('pestcontrol.bank_name'));
    $response->assertSee('Konfirmasi Pembayaran DP via WhatsApp');
});

test('admin tidak dapat menugaskan teknisi dan menjadwalkan survei jika DP belum lunas', function () {
    $admin = Pengguna::create([
        'nama' => 'Admin Test',
        'email' => 'admin_test@ambius.com',
        'password' => bcrypt('password'),
        'role' => 'admin',
        'status_aktif' => true,
    ]);

    $teknisi = Pengguna::create([
        'nama' => 'Teknisi Test',
        'email' => 'teknisi_test@ambius.com',
        'password' => bcrypt('password'),
        'role' => 'teknisi',
        'status_aktif' => true,
    ]);

    $pemesanan = Pemesanan::factory()->create([
        'harga_estimasi' => 15000000,
        'wajib_dp' => true,
        'nominal_dp' => 1500000,
        'status_dp' => 'Belum Dibayar',
        'status_pengerjaan' => 'Pending',
        'tgl_verifikasi' => null,
    ]);

    $service = app(PemesananStatusService::class);

    expect(fn () => $service->verifikasiDanTugaskan(
        $pemesanan,
        $admin->id_pengguna,
        $teknisi->id_pengguna,
        now()->addDay()->toDateString(),
        '09:00'
    ))->toThrow(InvalidArgumentException::class);
});

test('admin dapat mengunggah bukti transfer DP dan memverifikasi DP', function () {
    Storage::fake('public');

    $admin = Pengguna::create([
        'nama' => 'Admin Ambius',
        'email' => 'admin_dp@ambius.com',
        'password' => bcrypt('password'),
        'role' => 'admin',
        'status_aktif' => true,
    ]);

    $pemesanan = Pemesanan::factory()->create([
        'kode_pemesanan' => 'AMB-DPVERIF',
        'harga_estimasi' => 18000000,
        'wajib_dp' => true,
        'nominal_dp' => 1800000,
        'status_dp' => 'Belum Dibayar',
        'status_pengerjaan' => 'Pending',
    ]);

    $file = UploadedFile::fake()->image('bukti_transfer_dp.jpg');

    $response = $this->actingAs($admin)
        ->post(route('admin.pemesanan.verifikasi-dp', $pemesanan->id_pemesanan), [
            'bukti_dp' => $file,
            'catatan_dp' => 'Mutasi BCA a.n Pelanggan Rp 1.800.000 sudah masuk.',
        ]);

    $response->assertRedirect(route('admin.pemesanan.show', $pemesanan->id_pemesanan));
    $response->assertSessionHas('success');

    $pemesanan->refresh();
    expect($pemesanan->status_dp)->toBe('Lunas');
    expect($pemesanan->bukti_dp)->not->toBeNull();
    expect($pemesanan->tgl_bayar_dp)->not->toBeNull();
    expect($pemesanan->catatan_dp)->toBe('Mutasi BCA a.n Pelanggan Rp 1.800.000 sudah masuk.');
    expect($pemesanan->canScheduleSurvei())->toBeTrue();

    Storage::disk('public')->assertExists($pemesanan->bukti_dp);
});

test('pelanggan dapat melihat bukti DP pada halaman cek status via kode unik', function () {
    Storage::fake('public');

    $pemesanan = Pemesanan::factory()->create([
        'kode_pemesanan' => 'AMB-TRACKDP',
        'harga_estimasi' => 20000000,
        'wajib_dp' => true,
        'nominal_dp' => 2000000,
        'status_dp' => 'Lunas',
        'tgl_bayar_dp' => now(),
        'bukti_dp' => 'bukti_dp/fake_receipt.jpg',
    ]);

    Storage::disk('public')->put('bukti_dp/fake_receipt.jpg', 'fake image content');

    // 1. Cek halaman status publik dengan kode unik
    $response = $this->get(route('status.index', ['code' => 'AMB-TRACKDP']));
    $response->assertSuccessful();
    $response->assertSee('Pembayaran Down Payment (DP 10%) Terverifikasi');
    $response->assertSee('2.000.000');
    $response->assertSee('Lihat / Unduh Bukti Transfer DP Resmi');

    // 2. Akses file dokumen bukti DP dengan kode unik yang sah
    $docResponse = $this->get(route('dokumen.dp', ['pemesanan' => $pemesanan->id_pemesanan, 'kode' => 'AMB-TRACKDP']));
    $docResponse->assertSuccessful();

    // 3. Akses file tanpa kode unik atau kode salah akan ditolak (403)
    $deniedResponse = $this->get(route('dokumen.dp', ['pemesanan' => $pemesanan->id_pemesanan, 'kode' => 'WRONGCODE']));
    $deniedResponse->assertStatus(403);
});

test('kalkulasi sisa pelunasan memotong DP yang lunas dari harga kesepakatan namun tetap memakai estimasi penuh sebagai acuan negosiasi', function () {
    // Kasus: Pesanan 10jt, DP 10% = 1jt (Lunas).
    $pemesanan = Pemesanan::factory()->create([
        'harga_estimasi' => 10000000,
        'harga_final' => null,
        'wajib_dp' => true,
        'nominal_dp' => 1000000,
        'status_dp' => 'Lunas',
    ]);

    // Sebelum kesepakatan final, sisa pelunasan = 10jt - 1jt = 9jt
    expect($pemesanan->sisa_pelunasan)->toBe(9000000.0);

    // Teknisi nego harga lapangan menjadi 4jt (acuan nego tetap total 10jt, bukan 9jt)
    $estimasiSisaSetelahNego = $pemesanan->hitungSisaDariPengajuan(4000000);
    expect($estimasiSisaSetelahNego)->toBe(3000000.0); // 4jt - 1jt DP = 3jt sisa

    // Setelah disetujui admin dan harga_final menjadi 4jt
    $pemesanan->update(['harga_final' => 4000000]);
    $pemesanan->refresh();
    expect($pemesanan->sisa_pelunasan)->toBe(3000000.0);
});

test('halaman teknisi menampilkan informasi DP lunas dan sisa pelunasan dengan tetap menampilkan estimasi awal penuh sebagai acuan negosiasi', function () {
    $teknisi = Pengguna::create([
        'nama' => 'Teknisi Agi',
        'email' => 'teknisi_agi@ambius.com',
        'password' => bcrypt('password'),
        'role' => 'teknisi',
        'status_aktif' => true,
    ]);

    $pemesanan = Pemesanan::factory()->create([
        'harga_estimasi' => 10000000,
        'harga_final' => 4000000,
        'wajib_dp' => true,
        'nominal_dp' => 1000000,
        'status_dp' => 'Lunas',
        'status_pengerjaan' => 'On-Going',
    ]);

    JadwalPenugasan::create([
        'id_pemesanan' => $pemesanan->id_pemesanan,
        'id_teknisi' => $teknisi->id_pengguna,
        'tgl_survei' => now()->toDateString(),
        'jam_survei' => '09:00',
    ]);

    $response = $this->actingAs($teknisi)->get(route('teknisi.pemesanan.show', $pemesanan->id_pemesanan));
    $response->assertSuccessful();

    // 1. Acuan harga tetap menampilkan harga penuh (4.000.000 atau 10.000.000)
    $response->assertSee('4.000.000');
    // 2. Potongan DP 10%
    $response->assertSee('1.000.000');
    // 3. Sisa yang wajib ditagih
    $response->assertSee('3.000.000');
    $response->assertSee('Sisa yang Wajib Ditagih');
});

test('halaman admin menampilkan rincian dan penjelasan sisa pelunasan setelah dipotong DP 10%', function () {
    $admin = Pengguna::create([
        'nama' => 'Admin Keuangan',
        'email' => 'admin_keuangan@ambius.com',
        'password' => bcrypt('password'),
        'role' => 'admin',
        'status_aktif' => true,
    ]);

    $pemesanan = Pemesanan::factory()->create([
        'harga_estimasi' => 10000000,
        'harga_final' => 5000000,
        'wajib_dp' => true,
        'nominal_dp' => 1000000,
        'status_dp' => 'Lunas',
        'status_pengerjaan' => 'On-Going',
    ]);

    $response = $this->actingAs($admin)->get(route('admin.pemesanan.show', $pemesanan->id_pemesanan));
    $response->assertSuccessful();

    // 1. Harga final 5.000.000
    $response->assertSee('5.000.000');
    // 2. Potongan DP 10% (1.000.000)
    $response->assertSee('1.000.000');
    // 3. Sisa pelunasan 4.000.000
    $response->assertSee('4.000.000');
    $response->assertSee('Sisa Pelunasan Lapangan');
});

test('halaman laporan pendapatan menampilkan rincian DP 10% dan tautan berkas bukti transfer DP', function () {
    $admin = Pengguna::create([
        'nama' => 'Admin Laporan',
        'email' => 'admin_laporan@ambius.com',
        'password' => bcrypt('password'),
        'role' => 'admin',
        'status_aktif' => true,
    ]);

    $pemesanan = Pemesanan::factory()->create([
        'harga_estimasi' => 10000000,
        'harga_final' => 5000000,
        'wajib_dp' => true,
        'nominal_dp' => 1000000,
        'status_dp' => 'Lunas',
        'bukti_dp' => 'bukti_dp/struk_laporan.jpg',
        'status_pengerjaan' => 'Done',
        'status_pembayaran' => 'Lunas',
        'tgl_selesai' => now(),
    ]);

    $response = $this->actingAs($admin)->get(route('admin.laporan.index'));
    $response->assertSuccessful();

    // 1. Total pendapatan 5.000.000
    $response->assertSee('5.000.000');
    // 2. Rincian DP 10% 1.000.000
    $response->assertSee('1.000.000');
    // 3. Rincian pelunasan 4.000.000
    $response->assertSee('4.000.000');
    // 4. Subtotal ringkasan kartu
    $response->assertSee('DP Bank:');
    // 5. Tautan Bukti DP
    $response->assertSee('Bukti DP (10%)');
});

test('halaman konfirmasi pesanan menampilkan rekening bank yang diperbarui dari pengaturan sistem', function () {
    Pengaturan::set('bank_name', 'Bank Syariah Indonesia (BSI)');
    Pengaturan::set('bank_account_number', '555-444-3333');
    Pengaturan::set('bank_account_holder', 'CV. Ambius Berkah');

    $pemesanan = Pemesanan::factory()->create([
        'harga_estimasi' => 12000000,
        'wajib_dp' => true,
        'nominal_dp' => 1200000,
        'status_dp' => 'Belum Dibayar',
    ]);

    $response = $this->get(route('pemesanan.confirmation', $pemesanan->id_pemesanan));
    $response->assertSuccessful();
    $response->assertSee('Bank Syariah Indonesia (BSI)');
    $response->assertSee('555-444-3333');
    $response->assertSee('CV. Ambius Berkah');
});
