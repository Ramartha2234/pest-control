<?php

use App\Models\BuktiKehadiran;
use App\Models\BuktiPembayaran;
use App\Models\HasilSurvei;
use App\Models\Pelanggan;
use App\Models\Pemesanan;
use App\Models\Pengguna;
use App\Models\TarifArea;
use App\Models\TarifLayanan;
use App\Models\Teknisi;
use App\Models\WorkOrder;
use App\Services\PemesananStatusService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('local');

    TarifArea::firstOrCreate(
        ['kode_area' => 'indoor'],
        ['nama_area' => 'Indoor (Dalam Ruangan)', 'biaya_tambahan' => 0, 'status_aktif' => true]
    );
    TarifArea::firstOrCreate(
        ['kode_area' => 'outdoor'],
        ['nama_area' => 'Outdoor (Luar Ruangan)', 'biaya_tambahan' => 100000, 'status_aktif' => true]
    );

    // Buat tarif layanan dasar
    $this->tarif = TarifLayanan::create([
        'jenis_layanan' => 'Pengendalian Rayap (Termite Control)',
        'jenis_properti' => 'Rumah Tinggal',
        'ukuran_area_min' => 1,
        'ukuran_area_max' => 200,
        'satuan' => 'm2',
        'tarif_dasar' => 15000,
        'nama_paket' => 'Paket Rumah Residensial Standar',
        'deskripsi' => 'Injeksi kimia anti-rayap garansi 3 tahun.',
        'status_aktif' => true,
    ]);

    // Buat admin
    $this->admin = Pengguna::create([
        'nama' => 'Owner Admin',
        'email' => 'admin_test@ambius.com',
        'password' => bcrypt('password'),
        'role' => 'admin',
        'status_aktif' => true,
    ]);

    // Buat teknisi 1
    $this->teknisi1 = Pengguna::create([
        'nama' => 'Teknisi Joko',
        'email' => 'joko@ambius.com',
        'no_hp' => '08123456789',
        'password' => bcrypt('password'),
        'role' => 'teknisi',
        'status_aktif' => true,
    ]);

    // Buat teknisi 2
    $this->teknisi2 = Pengguna::create([
        'nama' => 'Teknisi Rudi',
        'email' => 'rudi@ambius.com',
        'no_hp' => '08987654321',
        'password' => bcrypt('password'),
        'role' => 'teknisi',
        'status_aktif' => true,
    ]);
});

test('alur 1: pelanggan pesan online tanpa login, sistem hitung estimasi harga otomatis dan generate kode AMB-XXXXXX', function () {
    $response = $this->post(route('pemesanan.store'), [
        'nama' => 'Ahmad Pelanggan',
        'no_hp' => '081399998888',
        'email' => 'ahmad@example.com',
        'nama_usaha' => 'Kopi Sejahtera',
        'jenis_properti' => 'Ruko/Restoran',
        'alamat_lokasi' => 'Jl. Sudirman No. 45, Jakarta Pusat',
        'keluhan' => 'Banyak rayap di plafon dan kitchen set',
        'id_tarif' => $this->tarif->id_tarif,
        'ukuran_area' => 100,
        'tgl_survei_diminta' => now()->addDay()->format('Y-m-d'),
        'jam_survei_diminta' => '10:00',
    ]);

    $pemesanan = Pemesanan::first();
    expect($pemesanan)->not->toBeNull();
    $response->assertRedirect(route('pemesanan.confirmation', $pemesanan->id_pemesanan));

    expect($pemesanan->kode_pemesanan)->toStartWith('AMB-');
    expect(strlen($pemesanan->kode_pemesanan))->toBe(10); // AMB- + 6 chars
    expect($pemesanan->status_pengerjaan)->toBe('Pending');
    expect($pemesanan->status_pembayaran)->toBe('Belum Lunas');
    expect((float) $pemesanan->harga_estimasi)->toBe(1500000.0);
    expect($pemesanan->batas_konfirmasi->isFuture())->toBeTrue();

    $pelanggan = Pelanggan::first();
    expect($pelanggan->nama)->toBe('Ahmad Pelanggan');
    expect($pelanggan->nama_usaha)->toBe('Kopi Sejahtera');
});

test('sistem menghitung estimasi biaya otomatis perkalian tarif dasar dengan estimasi luas area', function () {
    $service = app(PemesananStatusService::class);
    $pemesanan = $service->buatPemesanan([
        'nama' => 'Test Hitung',
        'no_hp' => '081234567899',
        'email' => 'hitung@example.com',
        'alamat_lokasi' => 'Jl. Bali No. 1',
        'keluhan' => 'Kecoa',
        'id_tarif' => $this->tarif->id_tarif,
        'jenis_properti' => 'Rumah Tinggal',
        'ukuran_area' => 20, // 20 m2
        'tgl_survei_diminta' => now()->addDay()->format('Y-m-d'),
        'jam_survei_diminta' => '13:30',
    ]);

    // 15.000 x 20 m2 = 300.000
    expect((float) $pemesanan->harga_estimasi)->toBe(300000.0);
});

test('sistem menambahkan biaya 100k jika memilih Outdoor dan harga tetap jika memilih Indoor', function () {
    $service = app(PemesananStatusService::class);

    // Test Indoor: 15.000 x 20 m2 = 300.000 (tanpa biaya tambahan)
    $pemesananIndoor = $service->buatPemesanan([
        'nama' => 'Test Indoor',
        'no_hp' => '081234567801',
        'email' => 'indoor@example.com',
        'alamat_lokasi' => 'Jl. Dalam Ruangan No. 1',
        'keluhan' => 'Semut di dapur',
        'id_tarif' => $this->tarif->id_tarif,
        'jenis_properti' => 'Rumah Tinggal',
        'tipe_area' => 'Indoor',
        'ukuran_area' => 20,
        'tgl_survei_diminta' => now()->addDay()->format('Y-m-d'),
        'jam_survei_diminta' => '10:00',
    ]);

    expect($pemesananIndoor->tipe_area)->toBe('Indoor');
    expect((float) $pemesananIndoor->harga_estimasi)->toBe(300000.0);

    // Test Outdoor: (15.000 x 20 m2) + 100.000 = 400.000 (+100k)
    $pemesananOutdoor = $service->buatPemesanan([
        'nama' => 'Test Outdoor',
        'no_hp' => '081234567802',
        'email' => 'outdoor@example.com',
        'alamat_lokasi' => 'Jl. Luar Ruangan No. 2',
        'keluhan' => 'Rayap di pekarangan',
        'id_tarif' => $this->tarif->id_tarif,
        'jenis_properti' => 'Rumah Tinggal',
        'tipe_area' => 'Outdoor',
        'ukuran_area' => 20,
        'tgl_survei_diminta' => now()->addDay()->format('Y-m-d'),
        'jam_survei_diminta' => '10:00',
    ]);

    expect($pemesananOutdoor->tipe_area)->toBe('Outdoor');
    expect((float) $pemesananOutdoor->harga_estimasi)->toBe(400000.0);
});

test('admin dan teknisi dapat melihat informasi tipe area outdoor dan indoor pada portal masing-masing', function () {
    $service = app(PemesananStatusService::class);

    $pemesanan = $service->buatPemesanan([
        'nama' => 'Pelanggan Outdoor',
        'no_hp' => '081234567803',
        'email' => 'pelanggan.outdoor@example.com',
        'alamat_lokasi' => 'Jl. Taman Indah No. 5',
        'keluhan' => 'Pemberantasan nyamuk taman',
        'id_tarif' => $this->tarif->id_tarif,
        'jenis_properti' => 'Vila Bali',
        'tipe_area' => 'Outdoor',
        'ukuran_area' => 50,
        'tgl_survei_diminta' => now()->addDay()->format('Y-m-d'),
        'jam_survei_diminta' => '14:00',
    ]);

    // Tugaskan teknisi1
    $this->actingAs($this->admin)->post(route('admin.pemesanan.verifikasi-awal', $pemesanan->id_pemesanan), [
        'id_teknisi' => $this->teknisi1->id_pengguna,
        'tgl_survei' => now()->addDay()->format('Y-m-d'),
        'jam_survei' => '14:00',
    ]);

    // 1. Admin melihat di halaman daftar dan detail pemesanan
    $this->actingAs($this->admin)->get(route('admin.pemesanan.index'))
        ->assertOk()
        ->assertSee('Outdoor');

    $this->actingAs($this->admin)->get(route('admin.pemesanan.show', $pemesanan->id_pemesanan))
        ->assertOk()
        ->assertSee('Outdoor (+Rp 100.000)');

    // 2. Teknisi melihat di halaman detail tugas
    $this->actingAs($this->teknisi1)->get(route('teknisi.pemesanan.show', $pemesanan->id_pemesanan))
        ->assertOk()
        ->assertSee('Outdoor (+100k)');

    // 3. Teknisi melihat di daftar tugas
    $this->actingAs($this->teknisi1)->get(route('teknisi.tugas'))
        ->assertOk()
        ->assertSee('Outdoor');
});

test('admin dapat mengubah tarif area outdoor dari 100k ke 150k dan sistem otomatis menerapkan tarif baru', function () {
    $areaOutdoor = TarifArea::where('kode_area', 'outdoor')->first();
    expect($areaOutdoor)->not->toBeNull();

    // 1. Admin mengubah tarif outdoor dari 100k ke 150k
    $response = $this->actingAs($this->admin)->put(route('admin.tarif-area.update', $areaOutdoor->id_tarif_area), [
        'nama_area' => 'Outdoor (Luar Ruangan)',
        'biaya_tambahan' => 150000,
        'keterangan' => 'Halaman, taman, perimeter luar, pekarangan',
        'status_aktif' => 1,
    ]);

    $response->assertRedirect(route('admin.tarif.index'));
    expect((float) $areaOutdoor->fresh()->biaya_tambahan)->toBe(150000.0);

    // 2. Pemesanan baru dengan Outdoor sekarang otomatis menambahkan 150k
    $service = app(PemesananStatusService::class);
    $pemesanan = $service->buatPemesanan([
        'nama' => 'Test Outdoor 150k',
        'no_hp' => '081234567888',
        'email' => 'outdoor150@example.com',
        'alamat_lokasi' => 'Jl. Kebun Raya No. 10',
        'keluhan' => 'Tawon',
        'id_tarif' => $this->tarif->id_tarif,
        'jenis_properti' => 'Rumah Tinggal',
        'tipe_area' => 'Outdoor',
        'ukuran_area' => 20, // 15.000 x 20 = 300.000 + 150.000 = 450.000
        'tgl_survei_diminta' => now()->addDay()->format('Y-m-d'),
        'jam_survei_diminta' => '10:00',
    ]);

    expect((float) $pemesanan->harga_estimasi)->toBe(450000.0);

    // 3. Halaman form pemesanan pelanggan menampilkan + Rp 150.000
    $this->get('/pesan')
        ->assertOk()
        ->assertSee('+ Rp 150.000');
});

test('alur 2: admin verifikasi pesanan, tentukan jadwal survei dan tugaskan teknisi', function () {
    $service = app(PemesananStatusService::class);
    $pemesanan = $service->buatPemesanan([
        'nama' => 'Budi Client',
        'no_hp' => '081233334444',
        'alamat_lokasi' => 'Jl. Merdeka No. 10',
        'keluhan' => 'Sarang tikus',
        'id_tarif' => $this->tarif->id_tarif,
        'jenis_properti' => 'Rumah Tinggal',
        'ukuran_area' => 100,
        'tgl_survei_diminta' => now()->addDays(2)->format('Y-m-d'),
        'jam_survei_diminta' => '09:00',
    ]);

    $response = $this->actingAs($this->admin)->post(route('admin.pemesanan.verifikasi-awal', $pemesanan->id_pemesanan), [
        'id_teknisi' => $this->teknisi1->id_pengguna,
        'tgl_survei' => now()->addDays(2)->format('Y-m-d'),
        'jam_survei' => '10:30',
    ]);

    $response->assertRedirect(route('admin.pemesanan.show', $pemesanan->id_pemesanan));
    $pemesanan->refresh();

    expect($pemesanan->tgl_verifikasi)->not->toBeNull();
    expect($pemesanan->id_admin)->toBe($this->admin->id_pengguna);
    expect($pemesanan->jadwalPenugasan)->not->toBeNull();
    expect($pemesanan->jadwalPenugasan->id_teknisi)->toBe($this->teknisi1->id_pengguna);
    expect($pemesanan->jadwalPenugasan->jam_survei)->toBe('10:30');
});

test('alur 2 (tolak): admin tolak pesanan saat verifikasi -> status Cancel + dibatalkan_oleh CV. Ambius + status_pembayaran Ditolak', function () {
    $service = app(PemesananStatusService::class);
    $pemesanan = $service->buatPemesanan([
        'nama' => 'Fiktif User',
        'no_hp' => '081999999999',
        'alamat_lokasi' => 'Alamat tidak jelas',
        'keluhan' => 'Hama',
        'id_tarif' => $this->tarif->id_tarif,
        'jenis_properti' => 'Rumah Tinggal',
        'ukuran_area' => 50,
        'tgl_survei_diminta' => now()->addDay()->format('Y-m-d'),
        'jam_survei_diminta' => '09:00',
    ]);

    $this->actingAs($this->admin)->post(route('admin.pemesanan.tolak-awal', $pemesanan->id_pemesanan), [
        'alasan_penolakan' => 'Lokasi berada di luar radius operasional CV. Ambius.',
    ]);

    $pemesanan->refresh();
    expect($pemesanan->status_pengerjaan)->toBe('Cancel');
    expect($pemesanan->status_pembayaran)->toBe('Ditolak');
    expect($pemesanan->dibatalkan_oleh)->toBe('CV. Ambius Indonesia');
    expect($pemesanan->alasan_pembatalan)->toContain('di luar radius');
});

test('alur 3: auto-cancel scheduler membatalkan pesanan yang belum diverifikasi admin setelah 3 hari', function () {
    $service = app(PemesananStatusService::class);
    $pemesanan = $service->buatPemesanan([
        'nama' => 'Expired Client',
        'no_hp' => '081233330000',
        'alamat_lokasi' => 'Jl. Kebon Jeruk',
        'keluhan' => 'Kecoa',
        'id_tarif' => $this->tarif->id_tarif,
        'jenis_properti' => 'Rumah Tinggal',
        'ukuran_area' => 80,
        'tgl_survei_diminta' => now()->addDay()->format('Y-m-d'),
        'jam_survei_diminta' => '09:00',
    ]);

    // Simulasikan sudah lewat 3 hari tanpa verifikasi
    $pemesanan->update([
        'batas_konfirmasi' => now()->subHour(),
        'tgl_verifikasi' => null,
    ]);

    Artisan::call('pemesanan:auto-cancel');

    $pemesanan->refresh();
    expect($pemesanan->status_pengerjaan)->toBe('Cancel');
    expect($pemesanan->status_pembayaran)->toBe('Ditolak');
    expect($pemesanan->dibatalkan_oleh)->toBe('Sistem');
});

test('alur 4 & aturan teknis: teknisi upload kehadiran survei, re-upload jika ditolak disimpan sebagai baris baru', function () {
    $service = app(PemesananStatusService::class);
    $pemesanan = $service->buatPemesanan([
        'nama' => 'Client Kehadiran',
        'no_hp' => '081212121212',
        'alamat_lokasi' => 'Jl. Anggrek No. 12',
        'keluhan' => 'Rayap',
        'id_tarif' => $this->tarif->id_tarif,
        'jenis_properti' => 'Rumah Tinggal',
        'ukuran_area' => 100,
        'tgl_survei_diminta' => now()->addDay()->format('Y-m-d'),
        'jam_survei_diminta' => '09:00',
    ]);
    $service->verifikasiDanTugaskan($pemesanan, $this->admin->id_pengguna, $this->teknisi1->id_pengguna, now()->addDay()->format('Y-m-d'), '09:00');

    // 1. Teknisi upload foto survei pertama
    $file1 = UploadedFile::fake()->image('survei_blur.jpg');
    $this->actingAs($this->teknisi1)->post(route('teknisi.pemesanan.kehadiran', $pemesanan->id_pemesanan), [
        'jenis_kehadiran' => 'Survei',
        'foto' => $file1,
        'catatan' => 'Tiba di lokasi',
    ]);

    expect(BuktiKehadiran::count())->toBe(1);
    $bukti1 = BuktiKehadiran::first();
    expect($bukti1->status_verifikasi)->toBe('Menunggu');

    // 2. Admin tolak foto pertama karena buram
    $this->actingAs($this->admin)->post(route('admin.verifikasi.kehadiran', $bukti1->id_bukti_kehadiran), [
        'aksi' => 'tolak',
        'alasan_penolakan' => 'Foto terlalu buram dan plat nomor rumah tidak terlihat jelas.',
    ]);

    $bukti1->refresh();
    expect($bukti1->status_verifikasi)->toBe('Ditolak');
    expect($bukti1->alasan_penolakan)->toContain('Foto terlalu buram');

    // 3. Teknisi upload ulang -> disimpan sebagai BARIS BARU (riwayat tidak terhapus)
    $file2 = UploadedFile::fake()->image('survei_clear.jpg');
    $this->actingAs($this->teknisi1)->post(route('teknisi.pemesanan.kehadiran', $pemesanan->id_pemesanan), [
        'jenis_kehadiran' => 'Survei',
        'foto' => $file2,
        'catatan' => 'Foto ulang jelas',
    ]);

    expect(BuktiKehadiran::count())->toBe(2);
    $latestBukti = $pemesanan->latestBuktiSurvei();
    expect($latestBukti->id_bukti_kehadiran)->not->toBe($bukti1->id_bukti_kehadiran);
    expect($latestBukti->status_verifikasi)->toBe('Menunggu');

    // 4. Admin menyetujui foto kedua
    $this->actingAs($this->admin)->post(route('admin.verifikasi.kehadiran', $latestBukti->id_bukti_kehadiran), [
        'aksi' => 'setujui',
    ]);

    $latestBukti->refresh();
    expect($latestBukti->status_verifikasi)->toBe('Disetujui');
});

test('alur 5: teknisi input hasil survei dan harga kesepakatan -> admin verifikasi mengisi jadwal pengerjaan dan harga final', function () {
    $service = app(PemesananStatusService::class);
    $pemesanan = $service->buatPemesanan([
        'nama' => 'Client Deal',
        'no_hp' => '081299990000',
        'alamat_lokasi' => 'Jl. Mawar No. 5',
        'keluhan' => 'Rayap',
        'id_tarif' => $this->tarif->id_tarif,
        'jenis_properti' => 'Rumah Tinggal',
        'ukuran_area' => 100,
        'tgl_survei_diminta' => now()->addDay()->format('Y-m-d'),
        'jam_survei_diminta' => '09:00',
    ]);
    $service->verifikasiDanTugaskan($pemesanan, $this->admin->id_pengguna, $this->teknisi1->id_pengguna, now()->addDay()->format('Y-m-d'), '09:00');

    // Prasyarat tahap: bukti survei telah disetujui admin
    $buktiSurvei = $service->submitBuktiKehadiranSurvei($pemesanan, $this->teknisi1->id_pengguna, 'kehadiran/survei.jpg');
    $service->verifikasiBuktiKehadiran($buktiSurvei, $this->admin->id_pengguna, true);

    // Teknisi input hasil survei
    $this->actingAs($this->teknisi1)->post(route('teknisi.pemesanan.hasil-survei', $pemesanan->id_pemesanan), [
        'tgl_pengerjaan_diajukan' => now()->addDays(3)->format('Y-m-d'),
        'jam_pengerjaan_diajukan' => '13:00',
        'harga_diajukan' => 1800000,
        'catatan_survei' => 'Rayap menyebar ke plafon, disepakati paket tambahan injeksi.',
    ]);

    $hasilSurvei = HasilSurvei::first();
    expect($hasilSurvei)->not->toBeNull();
    expect($hasilSurvei->status_verifikasi)->toBe('Menunggu');

    // Admin approve hasil survei
    $this->actingAs($this->admin)->post(route('admin.verifikasi.hasil-survei', $hasilSurvei->id_hasil_survei), [
        'aksi' => 'setujui',
    ]);

    $pemesanan->refresh();
    expect((float) $pemesanan->harga_final)->toBe(1800000.0);
    expect($pemesanan->jadwalPenugasan->tgl_pengerjaan->format('Y-m-d'))->toBe(now()->addDays(3)->format('Y-m-d'));
    expect($pemesanan->jadwalPenugasan->jam_pengerjaan)->toBe('13:00');
});

test('alur 6: admin bisa ubah jadwal survei dan tanggal pengerjaan kapan saja', function () {
    $service = app(PemesananStatusService::class);
    $pemesanan = $service->buatPemesanan([
        'nama' => 'Reschedule Client',
        'no_hp' => '081277778888',
        'alamat_lokasi' => 'Jl. Kenanga',
        'keluhan' => 'Tikus',
        'id_tarif' => $this->tarif->id_tarif,
        'jenis_properti' => 'Rumah Tinggal',
        'ukuran_area' => 100,
        'tgl_survei_diminta' => now()->addDay()->format('Y-m-d'),
        'jam_survei_diminta' => '09:00',
    ]);
    $service->verifikasiDanTugaskan($pemesanan, $this->admin->id_pengguna, $this->teknisi1->id_pengguna, now()->addDay()->format('Y-m-d'), '09:00');

    $this->actingAs($this->admin)->post(route('admin.pemesanan.ubah-jadwal', $pemesanan->id_pemesanan), [
        'tgl_survei' => now()->addDays(5)->format('Y-m-d'),
        'jam_survei' => '14:00',
        'tgl_pengerjaan' => now()->addDays(7)->format('Y-m-d'),
        'jam_pengerjaan' => '08:30',
    ]);

    $pemesanan->refresh();
    expect($pemesanan->jadwalPenugasan->tgl_survei->format('Y-m-d'))->toBe(now()->addDays(5)->format('Y-m-d'));
    expect($pemesanan->jadwalPenugasan->jam_survei)->toBe('14:00');
    expect($pemesanan->jadwalPenugasan->tgl_pengerjaan->format('Y-m-d'))->toBe(now()->addDays(7)->format('Y-m-d'));
    expect($pemesanan->jadwalPenugasan->jam_pengerjaan)->toBe('08:30');
});

test('alur 7: teknisi upload kehadiran pengerjaan -> admin approve -> status_pengerjaan jadi On-Going OTOMATIS', function () {
    $service = app(PemesananStatusService::class);
    $pemesanan = $service->buatPemesanan([
        'nama' => 'Client Ongoing',
        'no_hp' => '081255556666',
        'alamat_lokasi' => 'Jl. Melati',
        'keluhan' => 'Rayap',
        'id_tarif' => $this->tarif->id_tarif,
        'jenis_properti' => 'Rumah Tinggal',
        'ukuran_area' => 100,
        'tgl_survei_diminta' => now()->addDay()->format('Y-m-d'),
        'jam_survei_diminta' => '09:00',
    ]);
    $service->verifikasiDanTugaskan($pemesanan, $this->admin->id_pengguna, $this->teknisi1->id_pengguna, now()->addDay()->format('Y-m-d'), '09:00');

    // Prasyarat tahap: bukti survei dan hasil survei telah disetujui admin
    $buktiSurvei = $service->submitBuktiKehadiranSurvei($pemesanan, $this->teknisi1->id_pengguna, 'kehadiran/survei.jpg');
    $service->verifikasiBuktiKehadiran($buktiSurvei, $this->admin->id_pengguna, true);
    $hasilSurvei = $service->submitHasilSurvei($pemesanan, $this->teknisi1->id_pengguna, now()->addDays(2)->toDateString(), '09:00', 1500000);
    $service->verifikasiHasilSurvei($hasilSurvei, $this->admin->id_pengguna, true);

    expect($pemesanan->status_pengerjaan)->toBe('Pending');

    // Teknisi upload bukti kehadiran pengerjaan
    $filePengerjaan = UploadedFile::fake()->image('hadir_pengerjaan.jpg');
    $this->actingAs($this->teknisi1)->post(route('teknisi.pemesanan.kehadiran', $pemesanan->id_pemesanan), [
        'jenis_kehadiran' => 'Pengerjaan',
        'foto' => $filePengerjaan,
    ]);

    $buktiPengerjaan = BuktiKehadiran::where('jenis_kehadiran', 'Pengerjaan')->first();
    expect($buktiPengerjaan)->not->toBeNull();

    // Sebelum di-approve admin, tetap Pending
    expect($pemesanan->fresh()->status_pengerjaan)->toBe('Pending');

    // Admin approve foto kehadiran pengerjaan
    $this->actingAs($this->admin)->post(route('admin.verifikasi.kehadiran', $buktiPengerjaan->id_bukti_kehadiran), [
        'aksi' => 'setujui',
    ]);

    // Status pengerjaan OTOMATIS berubah menjadi On-Going!
    expect($pemesanan->fresh()->status_pengerjaan)->toBe('On-Going');
});

test('alur 8: selesai kerja, teknisi upload WO dan bukti pembayaran, ketika KEDUANYA disetujui -> status Done dan Lunas OTOMATIS', function () {
    $service = app(PemesananStatusService::class);
    $pemesanan = $service->buatPemesanan([
        'nama' => 'Client Selesai',
        'no_hp' => '081244445555',
        'alamat_lokasi' => 'Jl. Flamboyan',
        'keluhan' => 'Rayap',
        'id_tarif' => $this->tarif->id_tarif,
        'jenis_properti' => 'Rumah Tinggal',
        'ukuran_area' => 100,
        'tgl_survei_diminta' => now()->addDay()->format('Y-m-d'),
        'jam_survei_diminta' => '09:00',
    ]);
    $service->verifikasiDanTugaskan($pemesanan, $this->admin->id_pengguna, $this->teknisi1->id_pengguna, now()->addDay()->format('Y-m-d'), '09:00');

    // Set kondisi sedang On-Going
    $pemesanan->update(['status_pengerjaan' => 'On-Going', 'status_pembayaran' => 'Belum Lunas']);

    // 1. Teknisi upload Work Order (WO)
    $fileWo = UploadedFile::fake()->create('work_order.pdf', 500, 'application/pdf');
    $this->actingAs($this->teknisi1)->post(route('teknisi.pemesanan.wo', $pemesanan->id_pemesanan), [
        'file_wo' => $fileWo,
        'catatan' => 'Pekerjaan selesai 100%',
    ]);

    // 2. Teknisi upload Bukti Pembayaran (Transfer)
    $fileBukti = UploadedFile::fake()->image('bukti_transfer.jpg');
    $this->actingAs($this->teknisi1)->post(route('teknisi.pemesanan.pembayaran', $pemesanan->id_pemesanan), [
        'file_bukti' => $fileBukti,
        'metode_pembayaran' => 'Transfer',
        'jumlah_bayar' => 1500000,
        'catatan' => 'Transfer BCA',
    ]);

    $wo = WorkOrder::first();
    $bukti = BuktiPembayaran::first();

    // 3. Admin hanya approve WO -> status masih On-Going & Belum Lunas
    $this->actingAs($this->admin)->post(route('admin.verifikasi.wo', $wo->id_wo), [
        'aksi' => 'setujui',
    ]);

    $pemesanan->refresh();
    expect($pemesanan->status_pengerjaan)->toBe('On-Going');
    expect($pemesanan->status_pembayaran)->toBe('Belum Lunas');

    // 4. Admin approve Bukti Pembayaran -> KEDUANYA disetujui -> OTOMATIS Done & Lunas!
    $this->actingAs($this->admin)->post(route('admin.verifikasi.pembayaran', $bukti->id_bukti_pembayaran), [
        'aksi' => 'setujui',
    ]);

    $pemesanan->refresh();
    expect($pemesanan->status_pengerjaan)->toBe('Done');
    expect($pemesanan->status_pembayaran)->toBe('Lunas');
    expect($pemesanan->tgl_selesai)->not->toBeNull();
});

test('aturan teknis: teknisi HANYA boleh mengakses pemesanan yang ditugaskan kepadanya', function () {
    $service = app(PemesananStatusService::class);
    $pemesanan = $service->buatPemesanan([
        'nama' => 'Client Joko Saja',
        'no_hp' => '081299991111',
        'alamat_lokasi' => 'Jl. Cempaka',
        'keluhan' => 'Kecoa',
        'id_tarif' => $this->tarif->id_tarif,
        'jenis_properti' => 'Rumah Tinggal',
        'ukuran_area' => 80,
        'tgl_survei_diminta' => now()->addDay()->format('Y-m-d'),
        'jam_survei_diminta' => '09:00',
    ]);
    // Ditugaskan ke teknisi1 (Joko)
    $service->verifikasiDanTugaskan($pemesanan, $this->admin->id_pengguna, $this->teknisi1->id_pengguna, now()->addDay()->format('Y-m-d'), '09:00');

    // Teknisi1 (Joko) bisa mengakses
    $this->actingAs($this->teknisi1)
        ->get(route('teknisi.pemesanan.show', $pemesanan->id_pemesanan))
        ->assertSuccessful();

    // Teknisi2 (Rudi) TIDAK BOLEH mengakses -> 403 Forbidden!
    $this->actingAs($this->teknisi2)
        ->get(route('teknisi.pemesanan.show', $pemesanan->id_pemesanan))
        ->assertForbidden();
});

test('aturan teknis: middleware role membatasi admin hanya ke /admin dan teknisi hanya ke /teknisi', function () {
    // Teknisi mencoba akses /admin -> redirect ke /teknisi
    $this->actingAs($this->teknisi1)
        ->get(route('admin.dashboard'))
        ->assertRedirect(route('teknisi.dashboard'));

    // Admin mencoba akses /teknisi -> redirect ke /admin
    $this->actingAs($this->admin)
        ->get(route('teknisi.dashboard'))
        ->assertRedirect(route('admin.dashboard'));
});

test('alur 10: laporan keuangan strictly hanya menampilkan transaksi Done dan Lunas', function () {
    $service = app(PemesananStatusService::class);

    // Pesanan 1: Done & Lunas
    $p1 = $service->buatPemesanan([
        'nama' => 'Lunas Client',
        'no_hp' => '081234567890',
        'alamat_lokasi' => 'Jl. Sukses',
        'keluhan' => 'Rayap',
        'id_tarif' => $this->tarif->id_tarif,
        'jenis_properti' => 'Rumah Tinggal',
        'ukuran_area' => 100,
        'tgl_survei_diminta' => now()->format('Y-m-d'),
        'jam_survei_diminta' => '09:00',
    ]);
    $p1->update([
        'status_pengerjaan' => 'Done',
        'status_pembayaran' => 'Lunas',
        'harga_final' => 2500000,
        'tgl_selesai' => now(),
    ]);

    // Pesanan 2: On-Going & Belum Lunas (Tidak boleh masuk laporan)
    $p2 = $service->buatPemesanan([
        'nama' => 'Ongoing Client',
        'no_hp' => '081234567891',
        'alamat_lokasi' => 'Jl. Belum Selesai',
        'keluhan' => 'Rayap',
        'id_tarif' => $this->tarif->id_tarif,
        'jenis_properti' => 'Rumah Tinggal',
        'ukuran_area' => 100,
        'tgl_survei_diminta' => now()->format('Y-m-d'),
        'jam_survei_diminta' => '09:00',
    ]);
    $p2->update([
        'status_pengerjaan' => 'On-Going',
        'status_pembayaran' => 'Belum Lunas',
    ]);

    $response = $this->actingAs($this->admin)->get(route('admin.laporan.index'));
    $response->assertSuccessful();
    $response->assertSee('Lunas Client');
    $response->assertSee('Rp 2.500.000');
    $response->assertDontSee('Ongoing Client');
});

test('admin: deteksi bentrok jadwal teknisi pada tanggal dan jam yang sama memunculkan warning', function () {
    $service = app(PemesananStatusService::class);

    $p1 = $service->buatPemesanan([
        'nama' => 'Klien 1',
        'no_hp' => '081234567801',
        'alamat_lokasi' => 'Jl. Mawar',
        'keluhan' => 'Rayap',
        'id_tarif' => $this->tarif->id_tarif,
        'jenis_properti' => 'Rumah Tinggal',
        'ukuran_area' => 100,
        'tgl_survei_diminta' => '2026-08-01',
        'jam_survei_diminta' => '10:00',
    ]);
    $service->verifikasiDanTugaskan($p1, $this->admin->id_pengguna, $this->teknisi1->id_pengguna, '2026-08-01', '10:00');

    $p2 = $service->buatPemesanan([
        'nama' => 'Klien 2',
        'no_hp' => '081234567802',
        'alamat_lokasi' => 'Jl. Melati',
        'keluhan' => 'Tikus',
        'id_tarif' => $this->tarif->id_tarif,
        'jenis_properti' => 'Rumah Tinggal',
        'ukuran_area' => 100,
        'tgl_survei_diminta' => '2026-08-01',
        'jam_survei_diminta' => '10:00',
    ]);

    // Admin tugaskan teknisi1 di tanggal & jam yang sama -> muncul session warning bentrok
    $response = $this->actingAs($this->admin)->post(route('admin.pemesanan.verifikasi-awal', $p2->id_pemesanan), [
        'id_teknisi' => $this->teknisi1->id_pengguna,
        'tgl_survei' => '2026-08-01',
        'jam_survei' => '10:00',
    ]);

    $response->assertRedirect(route('admin.pemesanan.show', $p2->id_pemesanan));
    $response->assertSessionHas('warning');
});

test('admin: pencarian teknisi berdasarkan nama dan no hp', function () {
    Teknisi::create(['nama' => 'Teknisi Joko', 'no_telp' => '08123456789', 'is_active' => true]);
    Teknisi::create(['nama' => 'Teknisi Rudi', 'no_telp' => '08987654321', 'is_active' => true]);

    $this->actingAs($this->admin)
        ->get(route('admin.teknisi.index', ['search' => 'Joko']))
        ->assertSuccessful()
        ->assertSee('Teknisi Joko')
        ->assertDontSee('Teknisi Rudi');

    $this->actingAs($this->admin)
        ->get(route('admin.teknisi.index', ['search' => '08987654321']))
        ->assertSuccessful()
        ->assertSee('Teknisi Rudi')
        ->assertDontSee('Teknisi Joko');
});

test('admin: menonaktifkan tarif layanan tidak menghapus data dan tidak merusak harga estimasi pemesanan lama', function () {
    $service = app(PemesananStatusService::class);
    $pemesanan = $service->buatPemesanan([
        'nama' => 'Pelanggan Lama',
        'no_hp' => '081234567803',
        'alamat_lokasi' => 'Jl. Kenanga',
        'keluhan' => 'Rayap',
        'id_tarif' => $this->tarif->id_tarif,
        'jenis_properti' => 'Rumah Tinggal',
        'ukuran_area' => 100,
        'tgl_survei_diminta' => '2026-08-01',
        'jam_survei_diminta' => '10:00',
    ]);

    // Admin menonaktifkan tarif (destroy)
    $response = $this->actingAs($this->admin)->delete(route('admin.tarif.destroy', $this->tarif->id_tarif));
    $response->assertRedirect(route('admin.tarif.index'));

    $this->tarif->refresh();
    expect($this->tarif->status_aktif)->toBeFalse();
    expect(TarifLayanan::where('id_tarif', $this->tarif->id_tarif)->exists())->toBeTrue();

    // Pemesanan lama tetap menyimpan snapshot harga estimasi
    $pemesanan->refresh();
    expect((float) $pemesanan->harga_estimasi)->toBe(1500000.0);
});

test('admin: auto-cancel membatalkan pemesanan pending lewat batas konfirmasi dengan alasan dan status yang benar', function () {
    $service = app(PemesananStatusService::class);
    $expired = $service->buatPemesanan([
        'nama' => 'Pelanggan Expired',
        'no_hp' => '081234567804',
        'alamat_lokasi' => 'Jl. Anggrek',
        'keluhan' => 'Rayap',
        'id_tarif' => $this->tarif->id_tarif,
        'jenis_properti' => 'Rumah Tinggal',
        'ukuran_area' => 100,
        'tgl_survei_diminta' => now()->subDays(5)->format('Y-m-d'),
        'jam_survei_diminta' => '10:00',
    ]);

    // Set batas_konfirmasi ke masa lalu dan id_admin null
    $expired->update([
        'batas_konfirmasi' => now()->subHour(),
        'id_admin' => null,
        'status_pengerjaan' => 'Pending',
    ]);

    Artisan::call('pemesanan:auto-cancel');

    $expired->refresh();
    expect($expired->status_pengerjaan)->toBe('Cancel');
    expect($expired->status_pembayaran)->toBe('Ditolak');
    expect($expired->dibatalkan_oleh)->toBe('Sistem');
    expect($expired->alasan_pembatalan)->toBe('Tidak ada konfirmasi dalam 3 hari');
});
