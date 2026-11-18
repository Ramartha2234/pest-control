<?php

use App\Models\BuktiKehadiran;
use App\Models\BuktiPembayaran;
use App\Models\HasilSurvei;
use App\Models\JadwalPenugasan;
use App\Models\Pelanggan;
use App\Models\Pemesanan;
use App\Models\Pengguna;
use App\Models\TarifLayanan;
use App\Models\User;
use App\Models\WorkOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->create([
        'email' => 'admin@ambius.com',
        'password' => bcrypt('password'),
    ]);

    $this->teknisi = Pengguna::create([
        'nama' => 'Teknisi Lapangan',
        'email' => 'teknisi@ambius.com',
        'no_hp' => '081234567891',
        'password' => bcrypt('password'),
        'peran' => 'Teknisi',
        'status_aktif' => true,
    ]);

    $this->tarif = TarifLayanan::create([
        'jenis_layanan' => 'Pengendalian Rayap (Termite Control)',
        'satuan' => 'm2',
        'tarif_dasar' => 1500000,
        'status_aktif' => true,
    ]);

    $this->pelanggan = Pelanggan::create([
        'nama' => 'Pelanggan Uji',
        'no_hp' => '081299998888',
        'email' => 'pelanggan@example.com',
        'alamat' => 'Jl. Sudirman No. 1',
    ]);
});

test('dashboard menampilkan label Jadwal Survey dan Jadwal Pengerjaan serta menyaring pesanan yang sudah selesai survei atau pengerjaan', function () {
    // 1. Pesanan belum selesai survei -> harus ada di Jadwal Survey, TIDAK di Jadwal Pengerjaan
    $belumSurvei = Pemesanan::create([
        'id_pelanggan' => $this->pelanggan->id_pelanggan,
        'id_tarif' => $this->tarif->id_tarif,
        'kode_pemesanan' => 'AMB-SURV01',
        'jenis_properti' => 'Rumah Tinggal',
        'tipe_area' => 'Indoor',
        'alamat_lokasi' => 'Jl. Mawar 1',
        'keluhan' => 'Rayap',
        'tgl_survei_diminta' => now()->addDays(2)->toDateString(),
        'jam_survei_diminta' => '09:00:00',
        'harga_estimasi' => 2000000,
        'status_pengerjaan' => 'Pending',
        'status_pembayaran' => 'Belum Lunas',
        'tgl_verifikasi' => now(),
    ]);
    JadwalPenugasan::create([
        'id_pemesanan' => $belumSurvei->id_pemesanan,
        'id_teknisi' => $this->teknisi->id_pengguna,
        'tgl_survei' => now()->addDays(2)->toDateString(),
        'jam_survei' => '09:00:00',
        'tgl_pengerjaan' => null,
        'jam_pengerjaan' => null,
    ]);

    // 2. Pesanan sudah selesai survei & punya jadwal pengerjaan -> TIDAK boleh ada di Jadwal Survey, HARUS ada di Jadwal Pengerjaan
    $sudahSurvei = Pemesanan::create([
        'id_pelanggan' => $this->pelanggan->id_pelanggan,
        'id_tarif' => $this->tarif->id_tarif,
        'kode_pemesanan' => 'AMB-KERJ01',
        'jenis_properti' => 'Rumah Tinggal',
        'tipe_area' => 'Indoor',
        'alamat_lokasi' => 'Jl. Mawar 2',
        'keluhan' => 'Rayap',
        'tgl_survei_diminta' => now()->toDateString(),
        'jam_survei_diminta' => '09:00:00',
        'harga_estimasi' => 5000000,
        'harga_final' => 5000000,
        'status_pengerjaan' => 'Pending',
        'status_pembayaran' => 'Belum Lunas',
        'tgl_verifikasi' => now()->subDay(),
    ]);
    JadwalPenugasan::create([
        'id_pemesanan' => $sudahSurvei->id_pemesanan,
        'id_teknisi' => $this->teknisi->id_pengguna,
        'tgl_survei' => now()->toDateString(),
        'jam_survei' => '09:00:00',
        'tgl_pengerjaan' => now()->addDays(3)->toDateString(),
        'jam_pengerjaan' => '10:00:00',
    ]);
    HasilSurvei::create([
        'id_pemesanan' => $sudahSurvei->id_pemesanan,
        'id_teknisi' => $this->teknisi->id_pengguna,
        'tgl_pengerjaan_diajukan' => now()->addDays(3)->toDateString(),
        'jam_pengerjaan_diajukan' => '10:00:00',
        'harga_diajukan' => 5000000,
        'status_verifikasi' => 'Disetujui',
    ]);

    // 3. Pesanan sudah selesai total (Done) -> TIDAK boleh ada di Jadwal Survey maupun Jadwal Pengerjaan
    $sudahDone = Pemesanan::create([
        'id_pelanggan' => $this->pelanggan->id_pelanggan,
        'id_tarif' => $this->tarif->id_tarif,
        'kode_pemesanan' => 'AMB-DONE01',
        'jenis_properti' => 'Rumah Tinggal',
        'tipe_area' => 'Indoor',
        'alamat_lokasi' => 'Jl. Mawar 3',
        'keluhan' => 'Rayap',
        'tgl_survei_diminta' => now()->subDays(2)->toDateString(),
        'jam_survei_diminta' => '09:00:00',
        'harga_estimasi' => 3000000,
        'harga_final' => 3000000,
        'status_pengerjaan' => 'Done',
        'status_pembayaran' => 'Lunas',
        'tgl_verifikasi' => now()->subDays(3),
        'tgl_selesai' => now(),
    ]);
    JadwalPenugasan::create([
        'id_pemesanan' => $sudahDone->id_pemesanan,
        'id_teknisi' => $this->teknisi->id_pengguna,
        'tgl_survei' => now()->subDays(2)->toDateString(),
        'jam_survei' => '09:00:00',
        'tgl_pengerjaan' => now()->toDateString(),
        'jam_pengerjaan' => '10:00:00',
    ]);

    $response = $this->actingAs($this->admin)->get(route('admin.dashboard'));

    $response->assertSuccessful()
        ->assertSee('Jadwal Survey')
        ->assertDontSee('Jadwal Survey / Belum Survey')
        ->assertSee('Jadwal Pengerjaan')
        ->assertDontSee('Jadwal Pengerjaan / Treatment');

    $surveyCodes = $response->viewData('surveyBookings')->pluck('kode_pemesanan')->all();
    $scheduledCodes = $response->viewData('scheduledBookings')->pluck('kode_pemesanan')->all();

    expect($surveyCodes)->toContain('AMB-SURV01')
        ->not->toContain('AMB-KERJ01')
        ->not->toContain('AMB-DONE01');

    expect($scheduledCodes)->toContain('AMB-KERJ01')
        ->not->toContain('AMB-SURV01')
        ->not->toContain('AMB-DONE01');
});

test('dashboard menampilkan panel khusus pekerjaan yang berlangsung hari ini', function () {
    $today = now()->toDateString();

    // Survei hari ini (belum selesai survei)
    $surveiHariIni = Pemesanan::create([
        'id_pelanggan' => $this->pelanggan->id_pelanggan,
        'id_tarif' => $this->tarif->id_tarif,
        'kode_pemesanan' => 'AMB-TODAY1',
        'jenis_properti' => 'Rumah Tinggal',
        'tipe_area' => 'Indoor',
        'alamat_lokasi' => 'Jl. Melati 1',
        'keluhan' => 'Tikus',
        'tgl_survei_diminta' => $today,
        'jam_survei_diminta' => '09:00:00',
        'harga_estimasi' => 1500000,
        'status_pengerjaan' => 'Pending',
        'status_pembayaran' => 'Belum Lunas',
        'tgl_verifikasi' => now()->subDay(),
    ]);
    JadwalPenugasan::create([
        'id_pemesanan' => $surveiHariIni->id_pemesanan,
        'id_teknisi' => $this->teknisi->id_pengguna,
        'tgl_survei' => $today,
        'jam_survei' => '09:00:00',
    ]);

    // Pengerjaan On-Going hari ini
    $kerjaHariIni = Pemesanan::create([
        'id_pelanggan' => $this->pelanggan->id_pelanggan,
        'id_tarif' => $this->tarif->id_tarif,
        'kode_pemesanan' => 'AMB-TODAY2',
        'jenis_properti' => 'Rumah Tinggal',
        'tipe_area' => 'Indoor',
        'alamat_lokasi' => 'Jl. Melati 2',
        'keluhan' => 'Rayap',
        'tgl_survei_diminta' => now()->subDays(2)->toDateString(),
        'jam_survei_diminta' => '09:00:00',
        'harga_estimasi' => 4000000,
        'status_pengerjaan' => 'On-Going',
        'status_pembayaran' => 'Belum Lunas',
        'tgl_verifikasi' => now()->subDays(3),
    ]);
    JadwalPenugasan::create([
        'id_pemesanan' => $kerjaHariIni->id_pemesanan,
        'id_teknisi' => $this->teknisi->id_pengguna,
        'tgl_survei' => now()->subDays(2)->toDateString(),
        'jam_survei' => '09:00:00',
        'tgl_pengerjaan' => $today,
        'jam_pengerjaan' => '11:00:00',
    ]);

    // Pesanan besok -> tidak boleh muncul di hari ini
    $pesananBesok = Pemesanan::create([
        'id_pelanggan' => $this->pelanggan->id_pelanggan,
        'id_tarif' => $this->tarif->id_tarif,
        'kode_pemesanan' => 'AMB-TMRW01',
        'jenis_properti' => 'Rumah Tinggal',
        'tipe_area' => 'Indoor',
        'alamat_lokasi' => 'Jl. Melati 3',
        'keluhan' => 'Kecoa',
        'tgl_survei_diminta' => now()->addDay()->toDateString(),
        'jam_survei_diminta' => '14:00:00',
        'harga_estimasi' => 1500000,
        'status_pengerjaan' => 'Pending',
        'status_pembayaran' => 'Belum Lunas',
        'tgl_verifikasi' => now(),
    ]);
    JadwalPenugasan::create([
        'id_pemesanan' => $pesananBesok->id_pemesanan,
        'id_teknisi' => $this->teknisi->id_pengguna,
        'tgl_survei' => now()->addDay()->toDateString(),
        'jam_survei' => '14:00:00',
    ]);

    $response = $this->actingAs($this->admin)->get(route('admin.dashboard'));

    $response->assertSuccessful()
        ->assertSee('Pekerjaan Berlangsung Hari Ini')
        ->assertSee('AMB-TODAY1')
        ->assertSee('AMB-TODAY2');

    $todayCodes = $response->viewData('todayBookings')->pluck('kode_pemesanan')->all();

    expect($todayCodes)->toContain('AMB-TODAY1', 'AMB-TODAY2')
        ->not->toContain('AMB-TMRW01');
});

test('dashboard menampilkan panel khusus verifikasi bukti kehadiran, hasil survei, wo, pembayaran, dan dp', function () {
    $order = Pemesanan::create([
        'id_pelanggan' => $this->pelanggan->id_pelanggan,
        'id_tarif' => $this->tarif->id_tarif,
        'kode_pemesanan' => 'AMB-VERIF1',
        'jenis_properti' => 'Gudang',
        'tipe_area' => 'Indoor',
        'alamat_lokasi' => 'Jl. Raya Industri',
        'keluhan' => 'Rayap skala besar',
        'tgl_survei_diminta' => now()->toDateString(),
        'jam_survei_diminta' => '09:00:00',
        'harga_estimasi' => 15000000,
        'wajib_dp' => true,
        'nominal_dp' => 1500000,
        'status_dp' => 'Menunggu Verifikasi',
        'bukti_dp' => 'bukti-dp.jpg',
        'tgl_bayar_dp' => now(),
        'status_pengerjaan' => 'On-Going',
        'status_pembayaran' => 'Belum Lunas',
        'tgl_verifikasi' => now()->subDay(),
    ]);

    BuktiKehadiran::create([
        'id_pemesanan' => $order->id_pemesanan,
        'id_teknisi' => $this->teknisi->id_pengguna,
        'jenis_kehadiran' => 'Survei',
        'foto' => 'survei.jpg',
        'status_verifikasi' => 'Menunggu',
    ]);

    BuktiKehadiran::create([
        'id_pemesanan' => $order->id_pemesanan,
        'id_teknisi' => $this->teknisi->id_pengguna,
        'jenis_kehadiran' => 'Pengerjaan',
        'foto' => 'kerja.jpg',
        'status_verifikasi' => 'Menunggu',
    ]);

    HasilSurvei::create([
        'id_pemesanan' => $order->id_pemesanan,
        'id_teknisi' => $this->teknisi->id_pengguna,
        'tgl_pengerjaan_diajukan' => now()->addDays(2)->toDateString(),
        'jam_pengerjaan_diajukan' => '10:00:00',
        'harga_diajukan' => 15000000,
        'status_verifikasi' => 'Menunggu',
    ]);

    WorkOrder::create([
        'id_pemesanan' => $order->id_pemesanan,
        'id_teknisi' => $this->teknisi->id_pengguna,
        'file_wo' => 'wo.pdf',
        'status_verifikasi' => 'Menunggu',
    ]);

    BuktiPembayaran::create([
        'id_pemesanan' => $order->id_pemesanan,
        'id_teknisi' => $this->teknisi->id_pengguna,
        'file_bukti' => 'bayar.jpg',
        'metode_pembayaran' => 'Transfer Bank',
        'jumlah_bayar' => 13500000,
        'status_verifikasi' => 'Menunggu',
    ]);

    $response = $this->actingAs($this->admin)->get(route('admin.dashboard'));

    $response->assertSuccessful()
        ->assertSee('Verifikasi Bukti Kehadiran, WO, Pembayaran & DP', false)
        ->assertSee('Kehadiran Survei')
        ->assertSee('Kehadiran Pengerjaan')
        ->assertSee('Hasil Survei')
        ->assertSee('Work Order (WO)')
        ->assertSee('Bukti Pembayaran')
        ->assertSee('Bukti DP (10%)')
        ->assertSee('AMB-VERIF1');

    $queue = $response->viewData('verificationQueue');
    $counts = $response->viewData('pendingVerifications');

    expect($queue)->toHaveCount(6)
        ->and($counts['kehadiran'])->toBe(2)
        ->and($counts['hasil_survei'])->toBe(1)
        ->and($counts['work_order'])->toBe(1)
        ->and($counts['pembayaran'])->toBe(1)
        ->and($counts['dp'])->toBe(1);
});
