<?php

use App\Models\HasilSurvei;
use App\Models\Pelanggan;
use App\Models\Pemesanan;
use App\Models\Pengguna;
use App\Models\TarifLayanan;
use App\Models\WorkOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

test('landing page loads successfully', function () {
    $this->get('/')
        ->assertSuccessful()
        ->assertSee('CV. Ambius')
        ->assertSee('Bebaskan Rumah Anda Dari');
});

test('booking form page loads successfully', function () {
    $this->get('/pesan')
        ->assertSuccessful()
        ->assertSee('Form Pemesanan Jasa')
        ->assertSee('Pilihan Area Penanganan')
        ->assertSee('Indoor (Dalam Ruangan)')
        ->assertSee('Outdoor (Luar Ruangan)');
});

test('status check page loads successfully without code', function () {
    $this->get('/cek-status')
        ->assertSuccessful()
        ->assertSee('Pantau Status Pemesanan');
});

test('client can book pest control service successfully', function () {
    $tarif = TarifLayanan::first() ?? TarifLayanan::create([
        'jenis_layanan' => 'Pengendalian Rayap (Termite Control)',
        'jenis_properti' => 'Rumah Tinggal',
        'satuan' => 'm2',
        'tarif_dasar' => 1500000,
        'status_aktif' => true,
    ]);

    $data = [
        'nama' => 'Ahmad Fauzi',
        'no_hp' => '081299998888',
        'email' => 'ahmad.fauzi@example.com',
        'alamat_lokasi' => 'Jl. Merdeka No. 10, Jakarta',
        'id_tarif' => $tarif->id_tarif,
        'jenis_properti' => 'Rumah Tinggal',
        'keluhan' => 'Banyak rayap di plafon',
        'ukuran_area' => 100,
        'tgl_survei_diminta' => now()->addDays(2)->format('Y-m-d'),
        'jam_survei_diminta' => '09:00',
    ];

    $response = $this->post(route('pemesanan.store'), $data);

    $pemesanan = Pemesanan::first();
    expect($pemesanan)->not->toBeNull();
    expect($pemesanan->nama_client)->toBe('Ahmad Fauzi');
    expect($pemesanan->status_pengerjaan)->toBe('Pending');
    expect($pemesanan->kode_pemesanan)->toStartWith('AMB-');

    $response->assertRedirect(route('pemesanan.confirmation', $pemesanan->id_pemesanan));
});

test('client can check status via booking code', function () {
    $pemesanan = Pemesanan::factory()->create([
        'kode_unik' => 'PC-20260713-ABCDE',
        'nama_client' => 'Fauzi',
        'nominal' => 2000000,
        'harga_estimasi' => 2000000,
        'harga_final' => null,
    ]);

    $this->get('/cek-status?code=PC-20260713-ABCDE')
        ->assertSuccessful()
        ->assertSee('PC-20260713-ABCDE')
        ->assertSee('Fauzi')
        ->assertSee('Harga Estimasi Awal')
        ->assertSee('Rp 2.000.000')
        ->assertDontSee('Harga Final');
});

test('client can check status and see updated price label when harga_final is different', function () {
    $pemesanan = Pemesanan::factory()->create([
        'kode_unik' => 'PC-20260713-XYZ12',
        'nama_client' => 'Fauzi',
        'harga_estimasi' => 2000000,
        'harga_final' => 2500000,
    ]);

    $this->get('/cek-status?code=PC-20260713-XYZ12')
        ->assertSuccessful()
        ->assertSee('PC-20260713-XYZ12')
        ->assertSee('Harga Estimasi Awal')
        ->assertSee('Rp 2.000.000')
        ->assertSee('Harga Final')
        ->assertSee('Rp 2.500.000')
        ->assertSee('Harga telah diperbarui');
});

test('checking status with invalid code redirects with error', function () {
    $response = $this->get('/cek-status?code=PC-INVALID');

    $response->assertRedirect(route('status.index'));
    $response->assertSessionHas('error');
});

test('booking fails if phone number format is not Indonesian', function () {
    $tarif = TarifLayanan::first() ?? TarifLayanan::create([
        'jenis_layanan' => 'Pengendalian Rayap',
        'jenis_properti' => 'Rumah Tinggal',
        'satuan' => 'm2',
        'tarif_dasar' => 1500000,
        'status_aktif' => true,
    ]);

    $data = [
        'nama' => 'Budi Luar Negeri',
        'no_hp' => '12345678', // Invalid Indonesian format
        'email' => 'budi@example.com',
        'alamat_lokasi' => 'Jl. Sudirman No. 1',
        'id_tarif' => $tarif->id_tarif,
        'jenis_properti' => 'Rumah Tinggal',
        'keluhan' => 'Semut',
        'ukuran_area' => 50,
        'tgl_survei_diminta' => now()->addDays(2)->format('Y-m-d'),
        'jam_survei_diminta' => '09:00',
    ];

    $response = $this->post(route('pemesanan.store'), $data);
    $response->assertSessionHasErrors(['no_hp']);
});

test('booking fails if email is missing or invalid', function () {
    $tarif = TarifLayanan::first() ?? TarifLayanan::create([
        'jenis_layanan' => 'Pengendalian Rayap',
        'jenis_properti' => 'Rumah Tinggal',
        'satuan' => 'm2',
        'tarif_dasar' => 1500000,
        'status_aktif' => true,
    ]);

    $data = [
        'nama' => 'Ahmad Fauzi',
        'no_hp' => '081299998888',
        'email' => '', // missing
        'alamat_lokasi' => 'Jl. Merdeka No. 10, Jakarta',
        'id_tarif' => $tarif->id_tarif,
        'jenis_properti' => 'Rumah Tinggal',
        'keluhan' => 'Banyak rayap di plafon',
        'ukuran_area' => 100,
        'tgl_survei_diminta' => now()->addDays(2)->format('Y-m-d'),
        'jam_survei_diminta' => '09:00',
    ];

    $response = $this->post(route('pemesanan.store'), $data);
    $response->assertSessionHasErrors(['email']);
});

test('same customer phone number does not create duplicate pelanggan record', function () {
    $tarif = TarifLayanan::first() ?? TarifLayanan::create([
        'jenis_layanan' => 'Pengendalian Rayap',
        'jenis_properti' => 'Rumah Tinggal',
        'satuan' => 'm2',
        'tarif_dasar' => 1500000,
        'status_aktif' => true,
    ]);

    $data1 = [
        'nama' => 'Pelanggan Setia',
        'no_hp' => '081299991111',
        'email' => 'setia1@example.com',
        'alamat_lokasi' => 'Jl. Pahlawan No. 1',
        'id_tarif' => $tarif->id_tarif,
        'jenis_properti' => 'Rumah Tinggal',
        'keluhan' => 'Tikus',
        'ukuran_area' => 100,
        'tgl_survei_diminta' => now()->addDays(2)->format('Y-m-d'),
        'jam_survei_diminta' => '09:00',
    ];

    $this->post(route('pemesanan.store'), $data1)->assertRedirect();

    $initialCount = Pelanggan::count();

    // Pemesanan kedua dengan nomor HP yang sama tapi format diawali +62
    $data2 = [
        'nama' => 'Pelanggan Setia Baru',
        'no_hp' => '+6281299991111',
        'email' => 'setia2@example.com',
        'alamat_lokasi' => 'Jl. Pahlawan No. 2',
        'id_tarif' => $tarif->id_tarif,
        'jenis_properti' => 'Rumah Tinggal',
        'keluhan' => 'Rayap',
        'ukuran_area' => 120,
        'tgl_survei_diminta' => now()->addDays(3)->format('Y-m-d'),
        'jam_survei_diminta' => '11:00',
    ];

    $this->post(route('pemesanan.store'), $data2)->assertRedirect();

    // Total record pelanggan tidak boleh bertambah
    expect(Pelanggan::count())->toBe($initialCount);
});

test('status tracking page displays customer contact information', function () {
    $pelanggan = Pelanggan::create([
        'nama' => 'Siti Rahma',
        'no_hp' => '081234567890',
        'email' => 'siti.rahma@gmail.com',
    ]);

    $pemesanan = Pemesanan::factory()->create([
        'id_pelanggan' => $pelanggan->id_pelanggan,
        'kode_pemesanan' => 'AMB-SECURE',
        'kode_unik' => 'AMB-SECURE',
    ]);

    $response = $this->get('/cek-status?code=AMB-SECURE')
        ->assertSuccessful()
        ->assertSee('Siti Rahma')
        ->assertSee('081234567890')
        ->assertSee('siti.rahma@gmail.com');
});

test('status tracking page displays survey price rejection reason and cancellation info', function () {
    $pemesanan = Pemesanan::factory()->create([
        'kode_pemesanan' => 'AMB-REJECT',
        'kode_unik' => 'AMB-REJECT',
        'status_pengerjaan' => 'Cancel',
        'dibatalkan_oleh' => 'Sistem',
        'alasan_pembatalan' => 'Pesanan dibatalkan otomatis karena tidak dikonfirmasi dalam 3 hari.',
    ]);

    $teknisi = Pengguna::create([
        'nama' => 'Teknisi Survei',
        'email' => 'teknisi.survei@ambius.com',
        'password' => bcrypt('password'),
        'role' => 'teknisi',
        'status_aktif' => true,
    ]);

    HasilSurvei::create([
        'id_pemesanan' => $pemesanan->id_pemesanan,
        'id_teknisi' => $teknisi->id_pengguna,
        'tgl_pengerjaan_diajukan' => now()->addDays(4)->toDateString(),
        'jam_pengerjaan_diajukan' => '10:00',
        'harga_diajukan' => 2500000,
        'status_verifikasi' => 'Ditolak',
        'alasan_penolakan' => 'Harga terlalu tinggi untuk ukuran area tersebut',
    ]);

    $this->get('/cek-status?code=AMB-REJECT')
        ->assertSuccessful()
        ->assertSee('Pemesanan Dibatalkan')
        ->assertSee('Sistem')
        ->assertSee('Pesanan dibatalkan otomatis karena tidak dikonfirmasi dalam 3 hari.')
        ->assertSee('Pengajuan / Nego Harga Survei Ditolak Admin')
        ->assertSee('Harga terlalu tinggi untuk ukuran area tersebut');
});

test('customer cannot download unapproved document and can download approved document', function () {
    Storage::fake('local');
    $fakePath = 'work_orders/test_wo.pdf';
    Storage::disk('local')->put($fakePath, 'PDF CONTENT SAMPLE');

    $teknisi = Pengguna::create([
        'nama' => 'Teknisi Uji',
        'email' => 'teknisi.uji@ambius.com',
        'password' => bcrypt('password'),
        'role' => 'teknisi',
        'status_aktif' => true,
    ]);

    $pemesanan = Pemesanan::factory()->create([
        'kode_pemesanan' => 'AMB-DOCS01',
        'kode_unik' => 'AMB-DOCS01',
    ]);

    $wo = WorkOrder::create([
        'id_pemesanan' => $pemesanan->id_pemesanan,
        'id_teknisi' => $teknisi->id_pengguna,
        'file_wo' => $fakePath,
        'catatan' => 'Test WO',
        'status_verifikasi' => 'Menunggu',
    ]);

    // Customer mencoba unduh saat status masih Menunggu -> harus 403
    $this->get(route('dokumen.wo', $wo->id_wo).'?kode=AMB-DOCS01')
        ->assertForbidden();

    // Ketika admin menyetujui WO
    $wo->update(['status_verifikasi' => 'Disetujui']);

    // Customer mencoba unduh saat sudah Disetujui -> harus 200 OK
    $this->get(route('dokumen.wo', $wo->id_wo).'?kode=AMB-DOCS01')
        ->assertSuccessful();
});
