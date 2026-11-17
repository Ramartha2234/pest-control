<?php

use App\Models\JadwalPenugasan;
use App\Models\Pelanggan;
use App\Models\Pemesanan;
use App\Models\Pengguna;
use App\Models\TarifLayanan;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

test('saat admin meng-acc pesanan, sistem menyimpan dan menampilkan tanggal serta jam di-acc', function () {
    $admin = Pengguna::create([
        'nama' => 'Admin Penguji',
        'email' => 'admin_uji@ambius.com',
        'password' => bcrypt('password'),
        'role' => 'admin',
        'status_aktif' => true,
    ]);

    $teknisi = Pengguna::create([
        'nama' => 'Budi Teknisi',
        'email' => 'teknisi_uji@ambius.com',
        'password' => bcrypt('password'),
        'role' => 'teknisi',
        'no_hp' => '081234567899',
        'status_aktif' => true,
    ]);

    $pelanggan = Pelanggan::create([
        'nama' => 'Ahmad Pelanggan',
        'no_hp' => '08122334455',
    ]);

    $tarif = TarifLayanan::create([
        'jenis_layanan' => 'Pengendalian Rayap (Termite Control)',
        'satuan' => 'm2',
        'tarif_dasar' => 1500000,
        'status_aktif' => true,
    ]);

    $pemesanan = Pemesanan::create([
        'id_pelanggan' => $pelanggan->id_pelanggan,
        'id_tarif' => $tarif->id_tarif,
        'kode_pemesanan' => 'AMB-TESTACC1',
        'jenis_properti' => 'Rumah Tinggal',
        'tipe_area' => 'Indoor',
        'alamat_lokasi' => 'Jl. Pengujian No. 1',
        'keluhan' => 'Banyak hama',
        'tgl_survei_diminta' => now()->addDay()->toDateString(),
        'jam_survei_diminta' => '09:00',
        'harga_estimasi' => 1500000,
        'wajib_dp' => false,
        'nominal_dp' => 0,
        'status_pengerjaan' => 'Pending',
        'status_pembayaran' => 'Belum Lunas',
        'tgl_pemesanan' => now(),
        'batas_konfirmasi' => now()->addDays(3),
    ]);

    // Freeze time agar pengujian tanggal dan jam presisi
    $waktuAcc = Carbon::create(2026, 11, 12, 14, 30, 0);
    Carbon::setTestNow($waktuAcc);

    // Admin verifikasi dan tugaskan teknisi (Alur 2: ACC Pesanan)
    $response = $this->actingAs($admin)
        ->post(route('admin.pemesanan.verifikasi-awal', $pemesanan->id_pemesanan), [
            'id_teknisi' => $teknisi->id_pengguna,
            'tgl_survei' => now()->addDay()->toDateString(),
            'jam_survei' => '10:00',
        ]);

    $response->assertRedirect(route('admin.pemesanan.show', $pemesanan->id_pemesanan));
    $response->assertSessionHas('success');

    $pemesanan->refresh();
    expect($pemesanan->tgl_verifikasi)->not->toBeNull();
    expect($pemesanan->tgl_verifikasi->format('Y-m-d H:i'))->toBe('2026-11-12 14:30');
    expect($pemesanan->tgl_acc_pesanan)->not->toBeNull();

    // 1. Cek tampilan detail di halaman admin (admin.pemesanan.show)
    $adminShowResponse = $this->actingAs($admin)->get(route('admin.pemesanan.show', $pemesanan->id_pemesanan));
    $adminShowResponse->assertSuccessful();
    $adminShowResponse->assertSee('Pemesanan Telah Di-ACC / Diverifikasi');
    $adminShowResponse->assertSee('12 November 2026');
    $adminShowResponse->assertSee('14:30 WITA');
    $adminShowResponse->assertSee('Admin Penguji');

    // 2. Cek tampilan di daftar pesanan admin (admin.pemesanan.index)
    $adminIndexResponse = $this->actingAs($admin)->get(route('admin.pemesanan.index'));
    $adminIndexResponse->assertSuccessful();
    $adminIndexResponse->assertSee('ACC: 12/11/2026 14:30');

    // 3. Cek tampilan di halaman pelacakan status pelanggan (status.index)
    $statusResponse = $this->get(route('status.index', ['code' => 'AMB-TESTACC1']));
    $statusResponse->assertSuccessful();
    $statusResponse->assertSee('Pesanan Telah Di-ACC oleh Admin');
    $statusResponse->assertSee('12 November 2026');
    $statusResponse->assertSee('14:30 WITA');

    Carbon::setTestNow(); // Reset time
});

test('saat admin meng-acc DP, sistem menyimpan dan menampilkan tanggal serta jam di-acc', function () {
    Storage::fake('public');

    $admin = Pengguna::create([
        'nama' => 'Admin Keuangan',
        'email' => 'admin_keuangan_acc@ambius.com',
        'password' => bcrypt('password'),
        'role' => 'admin',
        'status_aktif' => true,
    ]);

    $pelanggan = Pelanggan::create([
        'nama' => 'Siti Pelanggan',
        'no_hp' => '08199887766',
    ]);

    $tarif = TarifLayanan::create([
        'jenis_layanan' => 'Pengendalian Rayap Industri (Termite Control)',
        'satuan' => 'm2',
        'tarif_dasar' => 100000,
        'status_aktif' => true,
    ]);

    $pemesanan = Pemesanan::create([
        'id_pelanggan' => $pelanggan->id_pelanggan,
        'id_tarif' => $tarif->id_tarif,
        'kode_pemesanan' => 'AMB-TESTACCDP',
        'jenis_properti' => 'Gudang/Pabrik',
        'tipe_area' => 'Indoor',
        'alamat_lokasi' => 'Kawasan Industri',
        'keluhan' => 'Rayap struktur',
        'tgl_survei_diminta' => now()->addDay()->toDateString(),
        'jam_survei_diminta' => '09:00',
        'harga_estimasi' => 15000000,
        'wajib_dp' => true,
        'nominal_dp' => 1500000,
        'status_dp' => 'Menunggu Verifikasi',
        'bukti_dp' => 'bukti_dp/struk_transfer.jpg',
        'status_pengerjaan' => 'Pending',
        'status_pembayaran' => 'Belum Lunas',
        'tgl_pemesanan' => now(),
        'batas_konfirmasi' => now()->addDays(3),
    ]);

    // Freeze time untuk verifikasi DP
    $waktuAccDp = Carbon::create(2026, 11, 12, 15, 45, 0);
    Carbon::setTestNow($waktuAccDp);

    // Admin ACC DP
    $response = $this->actingAs($admin)
        ->post(route('admin.pemesanan.verifikasi-dp', $pemesanan->id_pemesanan), [
            'aksi' => 'setujui',
            'catatan_dp' => 'Mutasi bank 1.500.000 cocok.',
        ]);

    $response->assertRedirect(route('admin.pemesanan.show', $pemesanan->id_pemesanan));
    $response->assertSessionHas('success');

    $pemesanan->refresh();
    expect($pemesanan->status_dp)->toBe('Lunas');
    expect($pemesanan->tgl_verifikasi_dp)->not->toBeNull();
    expect($pemesanan->tgl_verifikasi_dp->format('Y-m-d H:i'))->toBe('2026-11-12 15:45');
    expect($pemesanan->tgl_acc_dp)->not->toBeNull();
    expect($pemesanan->tgl_acc_dp->format('Y-m-d H:i'))->toBe('2026-11-12 15:45');

    // 1. Cek tampilan detail di halaman admin (admin.pemesanan.show)
    $adminShowResponse = $this->actingAs($admin)->get(route('admin.pemesanan.show', $pemesanan->id_pemesanan));
    $adminShowResponse->assertSuccessful();
    $adminShowResponse->assertSee('DP Telah Di-ACC');
    $adminShowResponse->assertSee('12 November 2026');
    $adminShowResponse->assertSee('15:45 WITA');
    $adminShowResponse->assertSee('Admin Keuangan');

    // 2. Cek tampilan di daftar pesanan admin (admin.pemesanan.index)
    $adminIndexResponse = $this->actingAs($admin)->get(route('admin.pemesanan.index'));
    $adminIndexResponse->assertSuccessful();
    $adminIndexResponse->assertSee('ACC DP: 12/11/2026 15:45');

    // 3. Cek tampilan di halaman pelacakan status pelanggan (status.index)
    $statusResponse = $this->get(route('status.index', ['code' => 'AMB-TESTACCDP']));
    $statusResponse->assertSuccessful();
    $statusResponse->assertSee('Pembayaran Down Payment (DP 10%) Terverifikasi');
    $statusResponse->assertSee('12 November 2026');
    $statusResponse->assertSee('15:45 WITA');

    // 4. Cek tampilan di halaman konfirmasi pelanggan (pemesanan.confirmation)
    $confirmResponse = $this->get(route('pemesanan.confirmation', $pemesanan->id_pemesanan));
    $confirmResponse->assertSuccessful();
    $confirmResponse->assertSee('12 November 2026');
    $confirmResponse->assertSee('15:45 WITA');

    Carbon::setTestNow(); // Reset time
});

test('saat terjadi deviasi jadwal dan realisasi treatment, web admin menampilkan peringatan dan tombol penyesuaian jadwal', function () {
    $admin = Pengguna::create([
        'nama' => 'Admin Penguji Deviasi',
        'email' => 'admin_deviasi@ambius.com',
        'password' => bcrypt('password'),
        'role' => 'admin',
        'status_aktif' => true,
    ]);

    $teknisi = Pengguna::create([
        'nama' => 'Teknisi Handal',
        'email' => 'teknisi_handal@ambius.com',
        'password' => bcrypt('password'),
        'role' => 'teknisi',
        'no_hp' => '081234567888',
        'status_aktif' => true,
    ]);

    $pelanggan = Pelanggan::create([
        'nama' => 'Bambang Deviasi',
        'no_hp' => '08133445566',
    ]);

    $tarif = TarifLayanan::create([
        'jenis_layanan' => 'Pengendalian Hama Umum (General Pest)',
        'satuan' => 'titik',
        'tarif_dasar' => 500000,
        'status_aktif' => true,
    ]);

    // Pemesanan selesai pada 10 November 2026, namun jadwal tercatat 12 November 2026 (lebih cepat 2 hari)
    $pemesanan = Pemesanan::create([
        'id_pelanggan' => $pelanggan->id_pelanggan,
        'id_tarif' => $tarif->id_tarif,
        'kode_pemesanan' => 'AMB-DEV-001',
        'jenis_properti' => 'Kantor',
        'tipe_area' => 'Indoor',
        'alamat_lokasi' => 'Jl. Merdeka No. 10',
        'keluhan' => 'Kecoak',
        'harga_estimasi' => 500000,
        'status_pengerjaan' => 'Done',
        'status_pembayaran' => 'Lunas',
        'tgl_pemesanan' => Carbon::create(2026, 11, 5, 8, 0, 0),
        'tgl_verifikasi' => Carbon::create(2026, 11, 5, 9, 0, 0),
        'tgl_selesai' => Carbon::create(2026, 11, 10, 14, 0, 0),
    ]);

    $jadwal = JadwalPenugasan::create([
        'id_pemesanan' => $pemesanan->id_pemesanan,
        'id_teknisi' => $teknisi->id_pengguna,
        'tgl_survei' => '2026-11-06',
        'jam_survei' => '09:00',
        'tgl_pengerjaan' => '2026-11-12', // Terjadwal 12 Nov, realisasi 10 Nov
        'jam_pengerjaan' => '10:00',
    ]);

    // 1. Kunjungi show admin: Harus muncul peringatan deviasi jadwal
    $response = $this->actingAs($admin)->get(route('admin.pemesanan.show', $pemesanan->id_pemesanan));
    $response->assertSuccessful();
    $response->assertSee('Peringatan: Deviasi Tanggal Realisasi Treatment Berbeda dengan Jadwal Awal');
    $response->assertSee('2 Hari Lebih Cepat');
    $response->assertSee('Samakan Jadwal ke Tanggal Realisasi');
    $response->assertSee('Petunjuk Sinkronisasi Lembar Laporan');
    $response->assertSee('Deviasi Jadwal');

    // 2. Lakukan sinkronisasi jadwal melalui ubahJadwal
    $syncResponse = $this->actingAs($admin)->post(route('admin.pemesanan.ubah-jadwal', $pemesanan->id_pemesanan), [
        'tgl_survei' => '2026-11-06',
        'jam_survei' => '09:00',
        'tgl_pengerjaan' => '2026-11-10',
        'jam_pengerjaan' => '10:00',
    ]);

    $syncResponse->assertRedirect(route('admin.pemesanan.show', $pemesanan->id_pemesanan));
    $syncResponse->assertSessionHas('success');

    $jadwal->refresh();
    $pemesanan->refresh();
    expect($jadwal->tgl_pengerjaan->toDateString())->toBe('2026-11-10');
    expect($pemesanan->tgl_selesai->toDateString())->toBe('2026-11-10');

    // 3. Setelah disinkronkan, peringatan deviasi hilang
    $responseAfter = $this->actingAs($admin)->get(route('admin.pemesanan.show', $pemesanan->id_pemesanan));
    $responseAfter->assertSuccessful();
    $responseAfter->assertDontSee('Peringatan: Deviasi Tanggal Realisasi Treatment Berbeda dengan Jadwal Awal');
});

test('pada lembar cetak laporan dan index laporan, tanggal treatment dan tanggal selesai ditampilkan sama persis', function () {
    $admin = Pengguna::create([
        'nama' => 'Admin Penguji Laporan',
        'email' => 'admin_laporan_sync@ambius.com',
        'password' => bcrypt('password'),
        'role' => 'admin',
        'status_aktif' => true,
    ]);

    $teknisi = Pengguna::create([
        'nama' => 'Teknisi Ahli',
        'email' => 'teknisi_ahli@ambius.com',
        'password' => bcrypt('password'),
        'role' => 'teknisi',
        'no_hp' => '081234567777',
        'status_aktif' => true,
    ]);

    $pelanggan = Pelanggan::create([
        'nama' => 'Customer Laporan',
        'no_hp' => '08177665544',
    ]);

    $tarif = TarifLayanan::create([
        'jenis_layanan' => 'Pengendalian Tikus (Rodent Control)',
        'satuan' => 'm2',
        'tarif_dasar' => 750000,
        'status_aktif' => true,
    ]);

    $pemesanan = Pemesanan::create([
        'id_pelanggan' => $pelanggan->id_pelanggan,
        'id_tarif' => $tarif->id_tarif,
        'kode_pemesanan' => 'AMB-LAP-001',
        'jenis_properti' => 'Restoran',
        'tipe_area' => 'Indoor',
        'alamat_lokasi' => 'Jl. Kuliner No. 8',
        'keluhan' => 'Tikus dapur',
        'harga_estimasi' => 750000,
        'harga_final' => 750000,
        'status_pengerjaan' => 'Done',
        'status_pembayaran' => 'Lunas',
        'tgl_pemesanan' => Carbon::create(2026, 11, 1, 9, 0, 0),
        'tgl_verifikasi' => Carbon::create(2026, 11, 1, 10, 0, 0),
        'tgl_selesai' => Carbon::create(2026, 11, 10, 16, 0, 0),
    ]);

    JadwalPenugasan::create([
        'id_pemesanan' => $pemesanan->id_pemesanan,
        'id_teknisi' => $teknisi->id_pengguna,
        'tgl_survei' => '2026-11-02',
        'jam_survei' => '09:00',
        'tgl_pengerjaan' => '2026-11-12', // sengaja beda di db lama, tapi view harus menampilkan tanggal treatment disamakan dengan tgl selesainya (10/11/2026)
        'jam_pengerjaan' => '10:00',
    ]);

    // 1. Cek index laporan
    $indexResponse = $this->actingAs($admin)->get(route('admin.laporan.index', [
        'view_type' => 'monthly',
        'year' => 2026,
        'month' => '11',
    ]));
    $indexResponse->assertSuccessful();
    $indexResponse->assertSee('AMB-LAP-001');
    $indexResponse->assertSee('Selesai: 10 Nov 2026');
    $indexResponse->assertSee('10/11/2026'); // Tanggal treatment disamakan dengan tgl selesainya (10 Nov 2026)
    $indexResponse->assertDontSee('Treatment: 12/11/2026');

    // 2. Cek cetak lembar dokumen resmi (PDF view)
    $cetakResponse = $this->actingAs($admin)->get(route('admin.laporan.cetak', [
        'view_type' => 'monthly',
        'year' => 2026,
        'month' => '11',
    ]));
    $cetakResponse->assertSuccessful();
    $cetakResponse->assertSee('AMB-LAP-001');
    $cetakResponse->assertSee('Selesai: 10 Nov 2026');
    $cetakResponse->assertSee('Treatment: 10/11/2026');
    $cetakResponse->assertDontSee('Treatment: 12/11/2026');
});
