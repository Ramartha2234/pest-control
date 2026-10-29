<?php

use App\Models\Pemesanan;
use App\Models\TarifLayanan;
use App\Services\PemesananStatusService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->tarif = TarifLayanan::create([
        'jenis_layanan' => 'Pengendalian Rayap (Termite Control)',
        'satuan' => 'm2',
        'tarif_dasar' => 1500000,
        'status_aktif' => true,
    ]);
});

test('pelanggan pertama berhasil membooking slot survei di tanggal dan jam tertentu', function () {
    $targetDate = now()->addDays(2)->format('Y-m-d');

    $response = $this->post(route('pemesanan.store'), [
        'nama' => 'Pelanggan Pertama',
        'no_hp' => '081234567890',
        'email' => 'pertama@example.com',
        'alamat_lokasi' => 'Jl. Mawar No. 1, Denpasar',
        'id_tarif' => $this->tarif->id_tarif,
        'jenis_properti' => 'Rumah Tinggal',
        'keluhan' => 'Rayap pada plafon',
        'ukuran_area' => 100,
        'tipe_area' => 'Indoor',
        'tgl_survei_diminta' => $targetDate,
        'jam_survei_diminta' => '09:00',
    ]);

    $response->assertSessionHasNoErrors();
    $pemesanan = Pemesanan::whereDate('tgl_survei_diminta', $targetDate)->first();

    expect($pemesanan)->not->toBeNull()
        ->and((string) $pemesanan->jam_survei_diminta)->toStartWith('09:00')
        ->and($pemesanan->status_pengerjaan)->toBe('Pending');

    $response->assertRedirect(route('pemesanan.confirmation', $pemesanan->id_pemesanan));
});

test('pelanggan kedua ditolak saat mencoba booking slot jam dan hari yang sama dengan pelanggan pertama', function () {
    $targetDate = now()->addDays(2)->format('Y-m-d');

    // Pelanggan pertama booking jam 09:00
    Pemesanan::create([
        'id_tarif' => $this->tarif->id_tarif,
        'jenis_properti' => 'Rumah Tinggal',
        'alamat_lokasi' => 'Jl. Mawar No. 1',
        'keluhan' => 'Ada rayap',
        'tgl_survei_diminta' => $targetDate,
        'jam_survei_diminta' => '09:00',
        'harga_estimasi' => 1500000,
        'status_pengerjaan' => 'Pending',
        'status_pembayaran' => 'Belum Lunas',
    ]);

    // Pelanggan kedua mencoba booking jam 09:00 di tanggal yang sama
    $response = $this->from(route('pemesanan.create'))->post(route('pemesanan.store'), [
        'nama' => 'Pelanggan Kedua',
        'no_hp' => '081299998888',
        'email' => 'kedua@example.com',
        'alamat_lokasi' => 'Jl. Melati No. 2, Denpasar',
        'id_tarif' => $this->tarif->id_tarif,
        'jenis_properti' => 'Rumah Tinggal',
        'keluhan' => 'Kecoa di dapur',
        'ukuran_area' => 100,
        'tipe_area' => 'Indoor',
        'tgl_survei_diminta' => $targetDate,
        'jam_survei_diminta' => '09:00',
    ]);

    $response->assertSessionHasErrors('jam_survei_diminta');
    $errors = session('errors')->get('jam_survei_diminta');
    expect($errors[0])->toContain('sudah dibooking oleh pelanggan lain');
});

test('pelanggan kedua bisa booking di hari yang sama asalkan slot jam berbeda', function () {
    $targetDate = now()->addDays(2)->format('Y-m-d');

    // Pelanggan pertama booking jam 09:00
    Pemesanan::create([
        'id_tarif' => $this->tarif->id_tarif,
        'jenis_properti' => 'Rumah Tinggal',
        'alamat_lokasi' => 'Jl. Mawar No. 1',
        'keluhan' => 'Ada rayap',
        'tgl_survei_diminta' => $targetDate,
        'jam_survei_diminta' => '09:00',
        'harga_estimasi' => 1500000,
        'status_pengerjaan' => 'Pending',
        'status_pembayaran' => 'Belum Lunas',
    ]);

    // Pelanggan kedua booking jam 10:00 (slot jam berbeda)
    $response = $this->post(route('pemesanan.store'), [
        'nama' => 'Pelanggan Kedua',
        'no_hp' => '081299998888',
        'email' => 'kedua@example.com',
        'alamat_lokasi' => 'Jl. Melati No. 2',
        'id_tarif' => $this->tarif->id_tarif,
        'jenis_properti' => 'Rumah Tinggal',
        'keluhan' => 'Kecoa di dapur',
        'ukuran_area' => 100,
        'tipe_area' => 'Indoor',
        'tgl_survei_diminta' => $targetDate,
        'jam_survei_diminta' => '10:00',
    ]);

    $response->assertSessionHasNoErrors();
    expect(Pemesanan::whereDate('tgl_survei_diminta', $targetDate)->count())->toBe(2);
});

test('pemesanan yang dibatalkan membuat slot jadwal terbuka kembali untuk dibooking', function () {
    $targetDate = now()->addDays(2)->format('Y-m-d');

    // Pemesanan sebelumnya yang sudah di-Cancel (misal ditolak/fiktif/batal)
    Pemesanan::create([
        'id_tarif' => $this->tarif->id_tarif,
        'jenis_properti' => 'Rumah Tinggal',
        'alamat_lokasi' => 'Jl. Mawar No. 1',
        'keluhan' => 'Ada rayap',
        'tgl_survei_diminta' => $targetDate,
        'jam_survei_diminta' => '09:00',
        'harga_estimasi' => 1500000,
        'status_pengerjaan' => 'Cancel',
        'status_pembayaran' => 'Ditolak',
        'alasan_pembatalan' => 'Dibatalkan oleh pelanggan',
    ]);

    // Pelanggan baru mencoba booking slot jam 09:00 di tanggal tersebut -> Berhasil karena pesanan lama sudah Cancel!
    $response = $this->post(route('pemesanan.store'), [
        'nama' => 'Pelanggan Baru',
        'no_hp' => '087711223344',
        'email' => 'baru@example.com',
        'alamat_lokasi' => 'Jl. Dahlia No. 5',
        'id_tarif' => $this->tarif->id_tarif,
        'jenis_properti' => 'Rumah Tinggal',
        'keluhan' => 'Rayap kusen',
        'ukuran_area' => 80,
        'tipe_area' => 'Indoor',
        'tgl_survei_diminta' => $targetDate,
        'jam_survei_diminta' => '09:00',
    ]);

    $response->assertSessionHasNoErrors();
    $pemesananAktif = Pemesanan::whereDate('tgl_survei_diminta', $targetDate)
        ->where('status_pengerjaan', 'Pending')
        ->first();

    expect($pemesananAktif)->not->toBeNull()
        ->and((string) $pemesananAktif->jam_survei_diminta)->toStartWith('09:00');
});

test('endpoint cek-slot mengembalikan daftar jam terisi dan mengabaikan pemesanan cancel', function () {
    $targetDate = now()->addDays(3)->format('Y-m-d');

    // 1 pemesanan aktif jam 09:00
    Pemesanan::create([
        'id_tarif' => $this->tarif->id_tarif,
        'jenis_properti' => 'Rumah Tinggal',
        'alamat_lokasi' => 'Jl. Mawar No. 1',
        'keluhan' => 'Rayap',
        'tgl_survei_diminta' => $targetDate,
        'jam_survei_diminta' => '09:00',
        'harga_estimasi' => 1500000,
        'status_pengerjaan' => 'Pending',
    ]);

    // 1 pemesanan aktif jam 14:00
    Pemesanan::create([
        'id_tarif' => $this->tarif->id_tarif,
        'jenis_properti' => 'Rumah Tinggal',
        'alamat_lokasi' => 'Jl. Mawar No. 2',
        'keluhan' => 'Tikus',
        'tgl_survei_diminta' => $targetDate,
        'jam_survei_diminta' => '14:00',
        'harga_estimasi' => 1500000,
        'status_pengerjaan' => 'On-Going',
    ]);

    // 1 pemesanan BATAL di jam 11:00 (seharusnya TIDAK masuk slot_terisi)
    Pemesanan::create([
        'id_tarif' => $this->tarif->id_tarif,
        'jenis_properti' => 'Rumah Tinggal',
        'alamat_lokasi' => 'Jl. Mawar No. 3',
        'keluhan' => 'Kecoa',
        'tgl_survei_diminta' => $targetDate,
        'jam_survei_diminta' => '11:00',
        'harga_estimasi' => 1500000,
        'status_pengerjaan' => 'Cancel',
    ]);

    $response = $this->getJson(route('pemesanan.cek-slot', ['tanggal' => $targetDate]));

    $response->assertSuccessful()
        ->assertJson([
            'success' => true,
            'tanggal' => $targetDate,
        ]);

    $slotTerisi = $response->json('slot_terisi');
    expect($slotTerisi)->toContain('09')
        ->and($slotTerisi)->toContain('14')
        ->and($slotTerisi)->not->toContain('11');
});

test('service buatPemesanan melempar exception jika slot sudah diambil pelanggan lain', function () {
    $targetDate = now()->addDays(2)->format('Y-m-d');

    // Pesanan 1
    Pemesanan::create([
        'id_tarif' => $this->tarif->id_tarif,
        'jenis_properti' => 'Rumah Tinggal',
        'alamat_lokasi' => 'Jl. Mawar No. 1',
        'keluhan' => 'Rayap',
        'tgl_survei_diminta' => $targetDate,
        'jam_survei_diminta' => '09:00',
        'harga_estimasi' => 1500000,
        'status_pengerjaan' => 'Pending',
    ]);

    $service = app(PemesananStatusService::class);

    expect(fn () => $service->buatPemesanan([
        'nama' => 'Pelanggan Duplikat',
        'no_hp' => '081234567899',
        'email' => 'duplikat@example.com',
        'alamat_lokasi' => 'Jl. Mawar No. 2',
        'id_tarif' => $this->tarif->id_tarif,
        'jenis_properti' => 'Rumah Tinggal',
        'keluhan' => 'Rayap',
        'tgl_survei_diminta' => $targetDate,
        'jam_survei_diminta' => '09:00',
        'validasi_slot' => true,
    ]))->toThrow(InvalidArgumentException::class, 'sudah dibooking oleh pelanggan lain');
});
