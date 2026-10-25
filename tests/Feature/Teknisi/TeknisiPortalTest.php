<?php

use App\Models\BuktiKehadiran;
use App\Models\JadwalPenugasan;
use App\Models\Pelanggan;
use App\Models\Pemesanan;
use App\Models\TarifLayanan;
use App\Models\User;
use App\Services\PemesananStatusService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('local');

    $this->pelanggan = Pelanggan::create([
        'nama' => 'Budi Santoso',
        'no_hp' => '081234567890',
        'email' => 'budi@example.com',
        'alamat' => 'Jl. Merdeka No. 10 Jakarta',
    ]);

    $this->tarif = TarifLayanan::create([
        'jenis_layanan' => 'Pengendalian Rayap (Termite Control)',
        'satuan' => 'm2',
        'tarif_dasar' => 1500000,
        'status_aktif' => true,
    ]);

    $this->teknisi = User::create([
        'nama' => 'Teknisi Satu',
        'email' => 'teknisi1@ambius.com',
        'password' => Hash::make('password123'),
        'no_hp' => '081299887766',
        'role' => 'teknisi',
        'status_aktif' => true,
    ]);

    $this->teknisiLain = User::create([
        'nama' => 'Teknisi Lain',
        'email' => 'teknisi2@ambius.com',
        'password' => Hash::make('password123'),
        'no_hp' => '081299887700',
        'role' => 'teknisi',
        'status_aktif' => true,
    ]);

    $this->admin = User::create([
        'nama' => 'Admin Ambius',
        'email' => 'admin@ambius.com',
        'password' => Hash::make('password123'),
        'no_hp' => '081211223344',
        'role' => 'admin',
        'status_aktif' => true,
    ]);
});

test('1. login teknisi aktif berhasil diarahkan ke dashboard dan nonaktif ditolak', function () {
    // Teknisi aktif berhasil login
    $response = $this->post('/login', [
        'email' => 'teknisi1@ambius.com',
        'password' => 'password123',
    ]);

    $response->assertRedirect(route('teknisi.dashboard'));
    $this->assertAuthenticatedAs($this->teknisi);

    // Logout
    $this->post('/logout')->assertRedirect(route('home'));
    $this->assertGuest();

    // Teknisi nonaktif ditolak
    $this->teknisi->update(['status_aktif' => false]);

    $responseInactive = $this->post('/login', [
        'email' => 'teknisi1@ambius.com',
        'password' => 'password123',
    ]);

    $responseInactive->assertSessionHasErrors(['email']);
    $this->assertGuest();
});

test('2. dashboard teknisi menampilkan metrik tugas hari ini, perlu tindakan, dan ongoing', function () {
    // Buat pesanan hari ini yang ditugaskan ke teknisi 1
    $pemesananHariIni = Pemesanan::create([
        'id_pelanggan' => $this->pelanggan->id_pelanggan,
        'id_tarif' => $this->tarif->id_tarif,
        'kode_pemesanan' => 'AMB-TEST01',
        'alamat_lokasi' => 'Jl. Tebet Barat',
        'keluhan' => 'Banyak rayap',
        'harga_estimasi' => 1500000,
        'status_pengerjaan' => 'Pending',
        'status_pembayaran' => 'Belum Lunas',
        'tgl_pemesanan' => now(),
        'batas_konfirmasi' => now()->addDays(3),
    ]);

    JadwalPenugasan::create([
        'id_pemesanan' => $pemesananHariIni->id_pemesanan,
        'id_teknisi' => $this->teknisi->id_pengguna,
        'tgl_survei' => now()->toDateString(),
        'jam_survei' => '10:00',
    ]);

    // Pesanan dengan pengajuan ditolak (perlu tindakan)
    $pemesananDitolak = Pemesanan::create([
        'id_pelanggan' => $this->pelanggan->id_pelanggan,
        'id_tarif' => $this->tarif->id_tarif,
        'kode_pemesanan' => 'AMB-TEST02',
        'alamat_lokasi' => 'Jl. Sudirman',
        'keluhan' => 'Kecoa',
        'harga_estimasi' => 2000000,
        'status_pengerjaan' => 'Pending',
        'status_pembayaran' => 'Belum Lunas',
        'tgl_pemesanan' => now(),
        'batas_konfirmasi' => now()->addDays(3),
    ]);

    JadwalPenugasan::create([
        'id_pemesanan' => $pemesananDitolak->id_pemesanan,
        'id_teknisi' => $this->teknisi->id_pengguna,
        'tgl_survei' => now()->addDays(2)->toDateString(),
        'jam_survei' => '14:00',
    ]);

    BuktiKehadiran::create([
        'id_pemesanan' => $pemesananDitolak->id_pemesanan,
        'id_teknisi' => $this->teknisi->id_pengguna,
        'jenis_kehadiran' => 'Survei',
        'foto' => 'private/kehadiran/fake.jpg',
        'status_verifikasi' => 'Ditolak',
        'alasan_penolakan' => 'Foto tidak jelas dan gelap',
    ]);

    $this->actingAs($this->teknisi)
        ->get('/teknisi')
        ->assertSuccessful()
        ->assertSee('AMB-TEST01')
        ->assertSee('AMB-TEST02')
        ->assertSee('Perhatian: Ada 1 Pengajuan Ditolak!')
        ->assertSee('Budi Santoso');
});

test('3. teknisi hanya dapat melihat penugasan miliknya dan mendapat 403 untuk pesanan teknisi lain', function () {
    $pemesananMilikTeknisiLain = Pemesanan::create([
        'id_pelanggan' => $this->pelanggan->id_pelanggan,
        'id_tarif' => $this->tarif->id_tarif,
        'kode_pemesanan' => 'AMB-LAIN01',
        'alamat_lokasi' => 'Jl. Kebon Jeruk',
        'keluhan' => 'Tikus',
        'harga_estimasi' => 1200000,
        'status_pengerjaan' => 'Pending',
        'status_pembayaran' => 'Belum Lunas',
        'tgl_pemesanan' => now(),
        'batas_konfirmasi' => now()->addDays(3),
    ]);

    JadwalPenugasan::create([
        'id_pemesanan' => $pemesananMilikTeknisiLain->id_pemesanan,
        'id_teknisi' => $this->teknisiLain->id_pengguna,
        'tgl_survei' => now()->toDateString(),
        'jam_survei' => '11:00',
    ]);

    // Teknisi 1 mencoba membuka tugas teknisi 2 -> harus 403
    $this->actingAs($this->teknisi)
        ->get(route('teknisi.pemesanan.show', $pemesananMilikTeknisiLain->id_pemesanan))
        ->assertStatus(403);

    // Di daftar tugas teknisi 1, pesanan teknisi 2 tidak muncul
    $this->actingAs($this->teknisi)
        ->get(route('teknisi.tugas'))
        ->assertSuccessful()
        ->assertDontSee('AMB-LAIN01');
});

test('4. validasi urutan 5 tahap teknisi di backend dan stepper show', function () {
    $service = app(PemesananStatusService::class);

    $pemesanan = Pemesanan::create([
        'id_pelanggan' => $this->pelanggan->id_pelanggan,
        'id_tarif' => $this->tarif->id_tarif,
        'kode_pemesanan' => 'AMB-STEP01',
        'alamat_lokasi' => 'Jl. Fatmawati',
        'keluhan' => 'Rayap pondasi',
        'harga_estimasi' => 3000000,
        'status_pengerjaan' => 'Pending',
        'status_pembayaran' => 'Belum Lunas',
        'tgl_pemesanan' => now(),
        'batas_konfirmasi' => now()->addDays(3),
    ]);

    JadwalPenugasan::create([
        'id_pemesanan' => $pemesanan->id_pemesanan,
        'id_teknisi' => $this->teknisi->id_pengguna,
        'tgl_survei' => now()->toDateString(),
        'jam_survei' => '09:00',
    ]);

    // A. Coba melompat langsung ke Tahap 2 (Hasil Survei) sebelum Tahap 1 disetujui -> Ditolak!
    $this->actingAs($this->teknisi)
        ->post(route('teknisi.pemesanan.hasil-survei', $pemesanan->id_pemesanan), [
            'tgl_pengerjaan_diajukan' => now()->addDay()->toDateString(),
            'jam_pengerjaan_diajukan' => '10:00',
            'harga_diajukan' => 3200000,
        ])
        ->assertRedirect(route('teknisi.pemesanan.show', $pemesanan->id_pemesanan))
        ->assertSessionHas('error');

    // B. Tahap 1: Upload bukti kehadiran Survei
    $fotoSurvei = UploadedFile::fake()->image('survei.jpg');
    $this->actingAs($this->teknisi)
        ->post(route('teknisi.pemesanan.kehadiran', $pemesanan->id_pemesanan), [
            'jenis_kehadiran' => 'Survei',
            'foto' => $fotoSurvei,
            'catatan' => 'Tiba di lokasi tepat waktu',
        ])
        ->assertRedirect(route('teknisi.pemesanan.show', $pemesanan->id_pemesanan))
        ->assertSessionHas('success');

    $buktiSurvei = $pemesanan->latestBuktiSurvei();
    expect($buktiSurvei)->not->toBeNull();
    expect($buktiSurvei->status_verifikasi)->toBe('Menunggu');

    // Coba ajukan hasil survei saat bukti survei masih Menunggu -> harus ditolak
    $this->actingAs($this->teknisi)
        ->post(route('teknisi.pemesanan.hasil-survei', $pemesanan->id_pemesanan), [
            'tgl_pengerjaan_diajukan' => now()->addDay()->toDateString(),
            'jam_pengerjaan_diajukan' => '10:00',
            'harga_diajukan' => 3200000,
        ])
        ->assertSessionHas('error');

    // Admin menolak bukti survei pertama (teknisi harus bisa upload ulang)
    $service->verifikasiBuktiKehadiran($buktiSurvei, $this->admin->id_pengguna, false, 'Foto kurang jelas');
    expect($buktiSurvei->fresh()->status_verifikasi)->toBe('Ditolak');

    // Teknisi upload ulang bukti survei -> baris baru dibuat
    $fotoSurvei2 = UploadedFile::fake()->image('survei2.jpg');
    $this->actingAs($this->teknisi)
        ->post(route('teknisi.pemesanan.kehadiran', $pemesanan->id_pemesanan), [
            'jenis_kehadiran' => 'Survei',
            'foto' => $fotoSurvei2,
        ]);
    expect($pemesanan->buktiKehadirans()->where('jenis_kehadiran', 'Survei')->count())->toBe(2);

    // Admin menyetujui foto survei kedua
    $buktiSurvei2 = $pemesanan->latestBuktiSurvei();
    $service->verifikasiBuktiKehadiran($buktiSurvei2, $this->admin->id_pengguna, true);

    // C. Tahap 2: Input hasil survei & jadwal pengerjaan
    $this->actingAs($this->teknisi)
        ->post(route('teknisi.pemesanan.hasil-survei', $pemesanan->id_pemesanan), [
            'tgl_pengerjaan_diajukan' => now()->addDays(2)->toDateString(),
            'jam_pengerjaan_diajukan' => '13:00',
            'harga_diajukan' => 3500000,
            'catatan_survei' => 'Disepakati garansi 3 tahun',
        ])
        ->assertSessionHas('success');

    $hasilSurvei = $pemesanan->latestHasilSurvei();
    expect($hasilSurvei->status_verifikasi)->toBe('Menunggu');

    // Coba upload foto kehadiran pengerjaan sebelum hasil survei disetujui -> Ditolak!
    $fotoKerja = UploadedFile::fake()->image('kerja.jpg');
    $this->actingAs($this->teknisi)
        ->post(route('teknisi.pemesanan.kehadiran', $pemesanan->id_pemesanan), [
            'jenis_kehadiran' => 'Pengerjaan',
            'foto' => $fotoKerja,
        ])
        ->assertSessionHas('error');

    // Admin menyetujui hasil survei
    $service->verifikasiHasilSurvei($hasilSurvei, $this->admin->id_pengguna, true);
    $pemesanan->refresh();
    expect($pemesanan->harga_final)->toBe('3500000.00');

    // D. Tahap 3: Upload foto kehadiran pengerjaan
    $this->actingAs($this->teknisi)
        ->post(route('teknisi.pemesanan.kehadiran', $pemesanan->id_pemesanan), [
            'jenis_kehadiran' => 'Pengerjaan',
            'foto' => $fotoKerja,
        ])
        ->assertSessionHas('success');

    $buktiKerja = $pemesanan->latestBuktiPengerjaan();
    // Admin approve bukti pengerjaan -> status pengerjaan otomatis jadi On-Going!
    $service->verifikasiBuktiKehadiran($buktiKerja, $this->admin->id_pengguna, true);
    $pemesanan->refresh();
    expect($pemesanan->status_pengerjaan)->toBe('On-Going');

    // E. Tahap 4: Upload WO dan Bukti Bayar terpisah
    $fileWo = UploadedFile::fake()->create('work_order.pdf', 500, 'application/pdf');
    $this->actingAs($this->teknisi)
        ->post(route('teknisi.pemesanan.wo', $pemesanan->id_pemesanan), [
            'file_wo' => $fileWo,
        ])
        ->assertSessionHas('success');

    $fileBayar = UploadedFile::fake()->image('bayar_cash.jpg');
    $this->actingAs($this->teknisi)
        ->post(route('teknisi.pemesanan.pembayaran', $pemesanan->id_pemesanan), [
            'file_bukti' => $fileBayar,
            'metode_pembayaran' => 'Cash',
            'jumlah_bayar' => 3500000,
        ])
        ->assertSessionHas('success');

    $wo = $pemesanan->latestWorkOrder();
    $bayar = $pemesanan->latestBuktiPembayaran();

    // Verifikasi masing-masing dokumen
    $service->verifikasiWorkOrder($wo, $this->admin->id_pengguna, true);
    expect($pemesanan->fresh()->status_pengerjaan)->toBe('On-Going'); // Masih On-Going karena pembayaran belum disetujui

    $service->verifikasiBuktiPembayaran($bayar, $this->admin->id_pengguna, true);
    // Keduanya disetujui -> Otomatis status jadi Done dan status_pembayaran jadi Lunas!
    $pemesanan->refresh();
    expect($pemesanan->status_pengerjaan)->toBe('Done');
    expect($pemesanan->status_pembayaran)->toBe('Lunas');
    expect($pemesanan->tgl_selesai)->not->toBeNull();

    // F. Tahap 5: Muncul di Riwayat Pekerjaan
    $this->actingAs($this->teknisi)
        ->get(route('teknisi.riwayat'))
        ->assertSuccessful()
        ->assertSee('AMB-STEP01')
        ->assertSee('Budi Santoso')
        ->assertSee('Selesai')
        ->assertSee('Lunas');
});

test('5. pesanan berstatus Cancel menolak semua aksi pengajuan teknisi', function () {
    $pemesanan = Pemesanan::create([
        'id_pelanggan' => $this->pelanggan->id_pelanggan,
        'id_tarif' => $this->tarif->id_tarif,
        'kode_pemesanan' => 'AMB-CANCEL',
        'alamat_lokasi' => 'Jl. Kemang Raya',
        'keluhan' => 'Rayap',
        'harga_estimasi' => 2000000,
        'status_pengerjaan' => 'Cancel',
        'status_pembayaran' => 'Ditolak',
        'alasan_pembatalan' => 'Pelanggan membatalkan sepihak',
        'dibatalkan_oleh' => 'Pelanggan',
        'tgl_pemesanan' => now(),
        'batas_konfirmasi' => now()->addDays(3),
    ]);

    JadwalPenugasan::create([
        'id_pemesanan' => $pemesanan->id_pemesanan,
        'id_teknisi' => $this->teknisi->id_pengguna,
        'tgl_survei' => now()->toDateString(),
        'jam_survei' => '10:00',
    ]);

    $foto = UploadedFile::fake()->image('survei.jpg');

    $this->actingAs($this->teknisi)
        ->post(route('teknisi.pemesanan.kehadiran', $pemesanan->id_pemesanan), [
            'jenis_kehadiran' => 'Survei',
            'foto' => $foto,
        ])
        ->assertRedirect(route('teknisi.pemesanan.show', $pemesanan->id_pemesanan))
        ->assertSessionHas('error');

    $this->actingAs($this->teknisi)
        ->get(route('teknisi.pemesanan.show', $pemesanan->id_pemesanan))
        ->assertSuccessful()
        ->assertSee('Pemesanan Ini Telah Dibatalkan')
        ->assertSee('Pelanggan membatalkan sepihak');
});

test('6. teknisi dapat memperbarui kontak dan mengubah password akun', function () {
    $this->actingAs($this->teknisi);

    // Halaman profil (data kontak read-only dan hanya dapat diubah oleh admin)
    $this->get(route('teknisi.profil.edit'))
        ->assertSuccessful()
        ->assertSee('Teknisi Satu')
        ->assertSee('teknisi1@ambius.com')
        ->assertSee('Hanya Dapat Diubah Admin');

    // Teknisi tidak dapat mengubah kontak mandiri
    $this->put(route('teknisi.profil.update'), [
        'nama' => 'Teknisi Satu Berpengalaman',
        'no_hp' => '089911223344',
    ])
        ->assertRedirect(route('teknisi.profil.edit'))
        ->assertSessionHas('error');

    expect($this->teknisi->fresh()->nama)->toBe('Teknisi Satu');

    // Update password (tetap dapat diubah mandiri oleh teknisi)
    $this->put(route('teknisi.profil.password'), [
        'current_password' => 'password123',
        'password' => 'newpassword456',
        'password_confirmation' => 'newpassword456',
    ])
        ->assertRedirect(route('teknisi.profil.edit'))
        ->assertSessionHas('success');

    expect(Hash::check('newpassword456', $this->teknisi->fresh()->password))->toBeTrue();
});
