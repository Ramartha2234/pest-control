<?php

use App\Models\BuktiKehadiran;
use App\Models\BuktiPembayaran;
use App\Models\HasilSurvei;
use App\Models\JadwalPenugasan;
use App\Models\Pelanggan;
use App\Models\Pemesanan;
use App\Models\TarifLayanan;
use App\Models\User;
use App\Models\WorkOrder;
use App\Services\PemesananStatusService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('local');

    $this->pelanggan = Pelanggan::create([
        'nama' => 'Bapak Hendra',
        'no_hp' => '081234567899',
        'email' => 'hendra@example.com',
        'alamat' => 'Komplek Permata Indah No. 42 Balikpapan',
    ]);

    $this->tarif = TarifLayanan::create([
        'jenis_layanan' => 'Pengendalian Rayap (Termite Control)',
        'satuan' => 'm2',
        'tarif_dasar' => 1500000,
        'status_aktif' => true,
    ]);

    $this->teknisi = User::create([
        'nama' => 'Agus Suprianto',
        'email' => 'agus@ambius.com',
        'password' => Hash::make('password123'),
        'no_hp' => '081398765432',
        'role' => 'teknisi',
        'status_aktif' => true,
    ]);

    $this->admin = User::create([
        'nama' => 'Admin CV. Ambius',
        'email' => 'admin@ambius.com',
        'password' => Hash::make('password123'),
        'no_hp' => '081211112222',
        'role' => 'admin',
        'status_aktif' => true,
    ]);
});

test('1. teknisi tidak bisa mengunggah bukti kehadiran survei sebelum tanggal jadwal (too early)', function () {
    $pemesanan = Pemesanan::create([
        'id_pelanggan' => $this->pelanggan->id_pelanggan,
        'id_tarif' => $this->tarif->id_tarif,
        'kode_pemesanan' => 'AMB-LOCK01',
        'alamat_lokasi' => 'Jl. Soekarno Hatta Km 5',
        'keluhan' => 'Rayap pada kusen pintu',
        'harga_estimasi' => 1500000,
        'status_pengerjaan' => 'Pending',
        'status_pembayaran' => 'Belum Lunas',
    ]);

    // Jadwal survei besok (belum waktunya)
    JadwalPenugasan::create([
        'id_pemesanan' => $pemesanan->id_pemesanan,
        'id_teknisi' => $this->teknisi->id_pengguna,
        'tgl_survei' => now()->addDays(2)->toDateString(),
        'jam_survei' => '10:00',
    ]);

    expect($pemesanan->statusWaktuSurvei())->toBe('too_early');
    expect($pemesanan->isSurveiLocked())->toBeTrue();

    // Halaman show teknisi menampilkan banner terkunci & tombol WA
    $this->actingAs($this->teknisi)
        ->get(route('teknisi.pemesanan.show', $pemesanan->id_pemesanan))
        ->assertSuccessful()
        ->assertSee('Aksi Terkunci: Belum Masuk Waktu Jadwal Survei')
        ->assertSee('Hubungi Pelanggan WhatsApp Langsung');

    // Percobaan POST kehadiran survei di backend langsung ditolak
    $foto = UploadedFile::fake()->image('survei_awal.jpg');
    $this->actingAs($this->teknisi)
        ->post(route('teknisi.pemesanan.kehadiran', $pemesanan->id_pemesanan), [
            'jenis_kehadiran' => 'Survei',
            'foto' => $foto,
        ])
        ->assertRedirect(route('teknisi.pemesanan.show', $pemesanan->id_pemesanan))
        ->assertSessionHas('error');

    expect(BuktiKehadiran::count())->toBe(0);
});

test('2. teknisi tidak bisa mengunggah bukti kehadiran survei jika jadwal telah terlewat (overdue)', function () {
    $pemesanan = Pemesanan::create([
        'id_pelanggan' => $this->pelanggan->id_pelanggan,
        'id_tarif' => $this->tarif->id_tarif,
        'kode_pemesanan' => 'AMB-LOCK02',
        'alamat_lokasi' => 'Jl. Ruhui Rahayu',
        'keluhan' => 'Kecoa di gudang',
        'harga_estimasi' => 1200000,
        'status_pengerjaan' => 'Pending',
        'status_pembayaran' => 'Belum Lunas',
    ]);

    // Jadwal survei 2 hari lalu (terlewat)
    JadwalPenugasan::create([
        'id_pemesanan' => $pemesanan->id_pemesanan,
        'id_teknisi' => $this->teknisi->id_pengguna,
        'tgl_survei' => now()->subDays(2)->toDateString(),
        'jam_survei' => '09:00',
    ]);

    expect($pemesanan->statusWaktuSurvei())->toBe('overdue');
    expect($pemesanan->isSurveiLocked())->toBeTrue();

    // Halaman show teknisi menampilkan banner jadwal terlewat
    $this->actingAs($this->teknisi)
        ->get(route('teknisi.pemesanan.show', $pemesanan->id_pemesanan))
        ->assertSuccessful()
        ->assertSee('Aksi Terkunci: Jadwal Survei Telah Terlewat (Perlu Reschedule)')
        ->assertSee('Perlu Reschedule');

    // POST kehadiran ditolak
    $foto = UploadedFile::fake()->image('survei_telat.jpg');
    $this->actingAs($this->teknisi)
        ->post(route('teknisi.pemesanan.kehadiran', $pemesanan->id_pemesanan), [
            'jenis_kehadiran' => 'Survei',
            'foto' => $foto,
        ])
        ->assertRedirect(route('teknisi.pemesanan.show', $pemesanan->id_pemesanan))
        ->assertSessionHas('error');

    expect(BuktiKehadiran::count())->toBe(0);
});

test('3. setelah admin reschedule jadwal survei ke hari ini, form survei otomatis aktif dan berhasil diunggah', function () {
    $pemesanan = Pemesanan::create([
        'id_pelanggan' => $this->pelanggan->id_pelanggan,
        'id_tarif' => $this->tarif->id_tarif,
        'kode_pemesanan' => 'AMB-LOCK03',
        'alamat_lokasi' => 'Jl. MT Haryono',
        'keluhan' => 'Semut hitam',
        'harga_estimasi' => 1000000,
        'status_pengerjaan' => 'Pending',
        'status_pembayaran' => 'Belum Lunas',
    ]);

    // Awalnya terlewat
    JadwalPenugasan::create([
        'id_pemesanan' => $pemesanan->id_pemesanan,
        'id_teknisi' => $this->teknisi->id_pengguna,
        'tgl_survei' => now()->subDay()->toDateString(),
        'jam_survei' => '14:00',
    ]);

    // Admin reschedule jadwal ke hari ini
    $this->actingAs($this->admin)->post(route('admin.pemesanan.ubah-jadwal', $pemesanan->id_pemesanan), [
        'tgl_survei' => now()->toDateString(),
        'jam_survei' => '14:00',
    ])->assertRedirect();

    $pemesanan->refresh();
    expect($pemesanan->statusWaktuSurvei())->toBe('on_time');
    expect($pemesanan->isSurveiLocked())->toBeFalse();

    // Halaman show teknisi menampilkan form aktif hari H
    $this->actingAs($this->teknisi)
        ->get(route('teknisi.pemesanan.show', $pemesanan->id_pemesanan))
        ->assertSuccessful()
        ->assertSee('Hari H Pelaksanaan Survei');

    // Teknisi mengunggah bukti survei hari ini -> BERHASIL
    $foto = UploadedFile::fake()->image('survei_ontime.jpg');
    $this->actingAs($this->teknisi)
        ->post(route('teknisi.pemesanan.kehadiran', $pemesanan->id_pemesanan), [
            'jenis_kehadiran' => 'Survei',
            'foto' => $foto,
            'catatan' => 'Survei tepat waktu',
        ])
        ->assertRedirect(route('teknisi.pemesanan.show', $pemesanan->id_pemesanan))
        ->assertSessionHas('success');

    expect(BuktiKehadiran::count())->toBe(1);
    expect($pemesanan->latestBuktiSurvei()->status_verifikasi)->toBe('Menunggu');
});

test('4. teknisi tidak bisa mengunggah kehadiran treatment sebelum tanggal jadwal (too early)', function () {
    $service = app(PemesananStatusService::class);

    $pemesanan = Pemesanan::create([
        'id_pelanggan' => $this->pelanggan->id_pelanggan,
        'id_tarif' => $this->tarif->id_tarif,
        'kode_pemesanan' => 'AMB-LOCK04',
        'alamat_lokasi' => 'Jl. Sudirman',
        'keluhan' => 'Rayap',
        'harga_estimasi' => 2000000,
        'status_pengerjaan' => 'Pending',
        'status_pembayaran' => 'Belum Lunas',
    ]);

    JadwalPenugasan::create([
        'id_pemesanan' => $pemesanan->id_pemesanan,
        'id_teknisi' => $this->teknisi->id_pengguna,
        'tgl_survei' => now()->toDateString(),
        'jam_survei' => '09:00',
    ]);

    // Bukti survei disetujui
    $buktiSurvei = $service->submitBuktiKehadiranSurvei($pemesanan, $this->teknisi->id_pengguna, 'kehadiran/survei.jpg');
    $service->verifikasiBuktiKehadiran($buktiSurvei, $this->admin->id_pengguna, true);

    // Hasil survei diajukan untuk 3 hari mendatang & disetujui admin
    $hasilSurvei = $service->submitHasilSurvei($pemesanan, $this->teknisi->id_pengguna, now()->addDays(3)->toDateString(), '10:00', 2200000);
    $service->verifikasiHasilSurvei($hasilSurvei, $this->admin->id_pengguna, true);

    $pemesanan->refresh();
    expect($pemesanan->statusWaktuPengerjaan())->toBe('too_early');
    expect($pemesanan->isPengerjaanLocked())->toBeTrue();
    expect($pemesanan->tahapPengerjaanTeknisi())->toBe(3);

    // Halaman show teknisi: Tahap 3 dan Tahap 4 sama-sama terkunci
    $this->actingAs($this->teknisi)
        ->get(route('teknisi.pemesanan.show', $pemesanan->id_pemesanan))
        ->assertSuccessful()
        ->assertSee('Aksi Terkunci: Belum Masuk Waktu Jadwal Treatment')
        ->assertSee('Tahap Terkunci: Belum Masuk Waktu Jadwal Treatment')
        ->assertSee('Form dokumen WO terkunci')
        ->assertSee('Form bukti pembayaran terkunci');

    // Teknisi mencoba POST kehadiran pengerjaan -> ditolak
    $fotoKerja = UploadedFile::fake()->image('kerja_awal.jpg');
    $this->actingAs($this->teknisi)
        ->post(route('teknisi.pemesanan.kehadiran', $pemesanan->id_pemesanan), [
            'jenis_kehadiran' => 'Pengerjaan',
            'foto' => $fotoKerja,
        ])
        ->assertRedirect(route('teknisi.pemesanan.show', $pemesanan->id_pemesanan))
        ->assertSessionHas('error');

    // Teknisi mencoba POST WO saat jadwal treatment belum tiba -> ditolak
    $fileWo = UploadedFile::fake()->create('wo_awal.pdf', 500, 'application/pdf');
    $this->actingAs($this->teknisi)
        ->post(route('teknisi.pemesanan.wo', $pemesanan->id_pemesanan), [
            'file_wo' => $fileWo,
        ])
        ->assertRedirect(route('teknisi.pemesanan.show', $pemesanan->id_pemesanan))
        ->assertSessionHas('error');

    // Teknisi mencoba POST Bukti Pembayaran saat jadwal treatment belum tiba -> ditolak
    $fileBayar = UploadedFile::fake()->image('bayar_awal.jpg');
    $this->actingAs($this->teknisi)
        ->post(route('teknisi.pemesanan.pembayaran', $pemesanan->id_pemesanan), [
            'file_bukti' => $fileBayar,
            'metode_pembayaran' => 'Cash',
        ])
        ->assertRedirect(route('teknisi.pemesanan.show', $pemesanan->id_pemesanan))
        ->assertSessionHas('error');

    expect(BuktiKehadiran::where('jenis_kehadiran', 'Pengerjaan')->count())->toBe(0);
    expect(WorkOrder::count())->toBe(0);
    expect(BuktiPembayaran::count())->toBe(0);
});

test('5. teknisi tidak bisa mengunggah kehadiran treatment jika jadwal terlewat (overdue)', function () {
    $service = app(PemesananStatusService::class);

    $pemesanan = Pemesanan::create([
        'id_pelanggan' => $this->pelanggan->id_pelanggan,
        'id_tarif' => $this->tarif->id_tarif,
        'kode_pemesanan' => 'AMB-LOCK05',
        'alamat_lokasi' => 'Jl. Ahmad Yani',
        'keluhan' => 'Tikus',
        'harga_estimasi' => 1500000,
        'status_pengerjaan' => 'Pending',
        'status_pembayaran' => 'Belum Lunas',
    ]);

    JadwalPenugasan::create([
        'id_pemesanan' => $pemesanan->id_pemesanan,
        'id_teknisi' => $this->teknisi->id_pengguna,
        'tgl_survei' => now()->subDays(5)->toDateString(),
        'jam_survei' => '09:00',
        'tgl_pengerjaan' => now()->subDay()->toDateString(), // Terlewat kemarin
        'jam_pengerjaan' => '10:00',
    ]);

    // Bukti survei & hasil survei disetujui
    $buktiSurvei = $service->submitBuktiKehadiranSurvei($pemesanan, $this->teknisi->id_pengguna, 'kehadiran/survei.jpg');
    $service->verifikasiBuktiKehadiran($buktiSurvei, $this->admin->id_pengguna, true);
    $hasilSurvei = $service->submitHasilSurvei($pemesanan, $this->teknisi->id_pengguna, now()->subDay()->toDateString(), '10:00', 1600000);
    $service->verifikasiHasilSurvei($hasilSurvei, $this->admin->id_pengguna, true);

    $pemesanan->refresh();
    expect($pemesanan->statusWaktuPengerjaan())->toBe('overdue');

    // Halaman show teknisi menampilkan banner terkunci & reschedule
    $this->actingAs($this->teknisi)
        ->get(route('teknisi.pemesanan.show', $pemesanan->id_pemesanan))
        ->assertSuccessful()
        ->assertSee('Aksi Terkunci: Jadwal Pengerjaan Telah Terlewat (Perlu Reschedule)');

    // POST kehadiran ditolak
    $fotoKerja = UploadedFile::fake()->image('kerja_telat.jpg');
    $this->actingAs($this->teknisi)
        ->post(route('teknisi.pemesanan.kehadiran', $pemesanan->id_pemesanan), [
            'jenis_kehadiran' => 'Pengerjaan',
            'foto' => $fotoKerja,
        ])
        ->assertRedirect(route('teknisi.pemesanan.show', $pemesanan->id_pemesanan))
        ->assertSessionHas('error');
});

test('6. admin reschedule jadwal pengerjaan ke hari ini -> teknisi berhasil mengunggah kehadiran treatment', function () {
    $service = app(PemesananStatusService::class);

    $pemesanan = Pemesanan::create([
        'id_pelanggan' => $this->pelanggan->id_pelanggan,
        'id_tarif' => $this->tarif->id_tarif,
        'kode_pemesanan' => 'AMB-LOCK06',
        'alamat_lokasi' => 'Jl. Syarifuddin Yoes',
        'keluhan' => 'Rayap',
        'harga_estimasi' => 1800000,
        'status_pengerjaan' => 'Pending',
        'status_pembayaran' => 'Belum Lunas',
    ]);

    JadwalPenugasan::create([
        'id_pemesanan' => $pemesanan->id_pemesanan,
        'id_teknisi' => $this->teknisi->id_pengguna,
        'tgl_survei' => now()->toDateString(),
        'jam_survei' => '09:00',
        'tgl_pengerjaan' => now()->subDays(2)->toDateString(), // Terlewat
        'jam_pengerjaan' => '10:00',
    ]);

    $buktiSurvei = $service->submitBuktiKehadiranSurvei($pemesanan, $this->teknisi->id_pengguna, 'kehadiran/survei.jpg');
    $service->verifikasiBuktiKehadiran($buktiSurvei, $this->admin->id_pengguna, true);
    $hasilSurvei = $service->submitHasilSurvei($pemesanan, $this->teknisi->id_pengguna, now()->subDays(2)->toDateString(), '10:00', 1800000);
    $service->verifikasiHasilSurvei($hasilSurvei, $this->admin->id_pengguna, true);

    // Admin reschedule pengerjaan ke hari ini
    $this->actingAs($this->admin)->post(route('admin.pemesanan.ubah-jadwal', $pemesanan->id_pemesanan), [
        'tgl_pengerjaan' => now()->toDateString(),
        'jam_pengerjaan' => '10:00',
    ])->assertRedirect();

    $pemesanan->refresh();
    expect($pemesanan->statusWaktuPengerjaan())->toBe('on_time');

    // Teknisi mengunggah foto mulai pengerjaan -> Berhasil!
    $fotoKerja = UploadedFile::fake()->image('kerja_hari_ini.jpg');
    $this->actingAs($this->teknisi)
        ->post(route('teknisi.pemesanan.kehadiran', $pemesanan->id_pemesanan), [
            'jenis_kehadiran' => 'Pengerjaan',
            'foto' => $fotoKerja,
        ])
        ->assertRedirect(route('teknisi.pemesanan.show', $pemesanan->id_pemesanan))
        ->assertSessionHas('success');

    expect(BuktiKehadiran::where('jenis_kehadiran', 'Pengerjaan')->count())->toBe(1);
});

test('7. teknisi tidak bisa mengunggah work order dan bukti bayar jika jadwal treatment telah terlewat tanpa sinkronisasi jadwal', function () {
    $pemesanan = Pemesanan::create([
        'id_pelanggan' => $this->pelanggan->id_pelanggan,
        'id_tarif' => $this->tarif->id_tarif,
        'kode_pemesanan' => 'AMB-LOCK07',
        'alamat_lokasi' => 'Jl. Pupuk Raya',
        'keluhan' => 'Rayap',
        'harga_estimasi' => 2000000,
        'status_pengerjaan' => 'On-Going',
        'status_pembayaran' => 'Belum Lunas',
    ]);

    // Jadwal pengerjaan kemarin (terlewat)
    JadwalPenugasan::create([
        'id_pemesanan' => $pemesanan->id_pemesanan,
        'id_teknisi' => $this->teknisi->id_pengguna,
        'tgl_survei' => now()->subDays(3)->toDateString(),
        'jam_survei' => '09:00',
        'tgl_pengerjaan' => now()->subDay()->toDateString(),
        'jam_pengerjaan' => '10:00',
    ]);

    expect($pemesanan->statusWaktuPengerjaan())->toBe('overdue');

    // Teknisi mencoba upload WO pada hari yang terlewat -> ditolak
    $fileWo = UploadedFile::fake()->create('wo_telat.pdf', 500, 'application/pdf');
    $this->actingAs($this->teknisi)
        ->post(route('teknisi.pemesanan.wo', $pemesanan->id_pemesanan), [
            'file_wo' => $fileWo,
        ])
        ->assertRedirect(route('teknisi.pemesanan.show', $pemesanan->id_pemesanan))
        ->assertSessionHas('error');

    // Teknisi mencoba upload Bukti Bayar -> ditolak
    $fileBayar = UploadedFile::fake()->image('bayar_telat.jpg');
    $this->actingAs($this->teknisi)
        ->post(route('teknisi.pemesanan.pembayaran', $pemesanan->id_pemesanan), [
            'file_bukti' => $fileBayar,
            'metode_pembayaran' => 'Cash',
        ])
        ->assertRedirect(route('teknisi.pemesanan.show', $pemesanan->id_pemesanan))
        ->assertSessionHas('error');
});

test('8. admin melihat banner peringatan jadwal terlewat pada halaman detail pemesanan admin ketika survei atau pengerjaan terlambat', function () {
    $pemesanan = Pemesanan::create([
        'id_pelanggan' => $this->pelanggan->id_pelanggan,
        'id_tarif' => $this->tarif->id_tarif,
        'kode_pemesanan' => 'AMB-LOCK08',
        'alamat_lokasi' => 'Jl. Beller',
        'keluhan' => 'Tikus',
        'harga_estimasi' => 1500000,
        'status_pengerjaan' => 'Pending',
        'status_pembayaran' => 'Belum Lunas',
    ]);

    JadwalPenugasan::create([
        'id_pemesanan' => $pemesanan->id_pemesanan,
        'id_teknisi' => $this->teknisi->id_pengguna,
        'tgl_survei' => now()->subDays(2)->toDateString(),
        'jam_survei' => '09:00',
    ]);

    // Admin membuka detail pemesanan
    $this->actingAs($this->admin)
        ->get(route('admin.pemesanan.show', $pemesanan->id_pemesanan))
        ->assertSuccessful()
        ->assertSee('Perhatian: Jadwal Survei Terlewat')
        ->assertSee('Ubah / Reschedule Jadwal Survei');
});

test('9. jika status pemesanan On-Going tapi jadwal pengerjaan ditetapkan di masa depan (too_early), tahap WO dan bukti bayar tetap ikut terkunci', function () {
    $pemesanan = Pemesanan::create([
        'id_pelanggan' => $this->pelanggan->id_pelanggan,
        'id_tarif' => $this->tarif->id_tarif,
        'kode_pemesanan' => 'AMB-LOCK09',
        'alamat_lokasi' => 'Jl. Balikpapan Baru',
        'keluhan' => 'Rayap',
        'harga_estimasi' => 2000000,
        'status_pengerjaan' => 'On-Going',
        'status_pembayaran' => 'Belum Lunas',
    ]);

    // Jadwal pengerjaan ditetapkan untuk besok (too_early)
    JadwalPenugasan::create([
        'id_pemesanan' => $pemesanan->id_pemesanan,
        'id_teknisi' => $this->teknisi->id_pengguna,
        'tgl_survei' => now()->subDay()->toDateString(),
        'jam_survei' => '09:00',
        'tgl_pengerjaan' => now()->addDay()->toDateString(),
        'jam_pengerjaan' => '10:00',
    ]);

    // Survei dan hasil survei telah disetujui admin
    BuktiKehadiran::create([
        'id_pemesanan' => $pemesanan->id_pemesanan,
        'id_teknisi' => $this->teknisi->id_pengguna,
        'id_admin' => $this->admin->id_pengguna,
        'jenis_kehadiran' => 'Survei',
        'foto' => 'kehadiran/survei.jpg',
        'status_verifikasi' => 'Disetujui',
        'tgl_verifikasi' => now()->subDay(),
    ]);

    HasilSurvei::create([
        'id_pemesanan' => $pemesanan->id_pemesanan,
        'id_teknisi' => $this->teknisi->id_pengguna,
        'id_admin' => $this->admin->id_pengguna,
        'tgl_pengerjaan_diajukan' => now()->addDay()->toDateString(),
        'jam_pengerjaan_diajukan' => '10:00',
        'harga_diajukan' => 2000000,
        'status_verifikasi' => 'Disetujui',
        'tgl_verifikasi' => now()->subDay(),
    ]);

    expect($pemesanan->statusWaktuPengerjaan())->toBe('too_early');
    expect($pemesanan->isPengerjaanLocked())->toBeTrue();
    expect($pemesanan->tahapPengerjaanTeknisi())->toBe(3);
    expect($pemesanan->isTahapSelesaiKerjaUnlocked())->toBeFalse();

    // Halaman show teknisi: Tahap 3 dan Tahap 4 sama-sama terkunci, tidak menampilkan form upload WO & bayar
    $this->actingAs($this->teknisi)
        ->get(route('teknisi.pemesanan.show', $pemesanan->id_pemesanan))
        ->assertSuccessful()
        ->assertSee('Aksi Terkunci: Belum Masuk Waktu Jadwal Treatment')
        ->assertSee('Tahap Terkunci: Belum Masuk Waktu Jadwal Treatment')
        ->assertDontSee('<span>Unggah Dokumen WO</span>', false)
        ->assertDontSee('<span>Unggah Bukti Pembayaran</span>', false);

    // Percobaan upload WO ditolak di backend
    $fileWo = UploadedFile::fake()->create('wo_dini.pdf', 500, 'application/pdf');
    $this->actingAs($this->teknisi)
        ->post(route('teknisi.pemesanan.wo', $pemesanan->id_pemesanan), [
            'file_wo' => $fileWo,
        ])
        ->assertRedirect(route('teknisi.pemesanan.show', $pemesanan->id_pemesanan))
        ->assertSessionHas('error');

    // Percobaan upload Bukti Bayar ditolak di backend
    $fileBayar = UploadedFile::fake()->image('bayar_dini.jpg');
    $this->actingAs($this->teknisi)
        ->post(route('teknisi.pemesanan.pembayaran', $pemesanan->id_pemesanan), [
            'file_bukti' => $fileBayar,
            'metode_pembayaran' => 'Cash',
        ])
        ->assertRedirect(route('teknisi.pemesanan.show', $pemesanan->id_pemesanan))
        ->assertSessionHas('error');
});

test('10. alur berurutan mutlak: form WO dan bukti pembayaran terkunci jika tahap survei atau hasil survei belum selesai', function () {
    $pemesanan = Pemesanan::create([
        'id_pelanggan' => $this->pelanggan->id_pelanggan,
        'id_tarif' => $this->tarif->id_tarif,
        'kode_pemesanan' => 'AMB-SEQ01',
        'alamat_lokasi' => 'Jl. Sequential Test',
        'keluhan' => 'Kecoa',
        'harga_estimasi' => 500000,
        'status_pengerjaan' => 'Pending',
        'status_pembayaran' => 'Belum Lunas',
    ]);

    JadwalPenugasan::create([
        'id_pemesanan' => $pemesanan->id_pemesanan,
        'id_teknisi' => $this->teknisi->id_pengguna,
        'tgl_survei' => Carbon::today()->toDateString(),
        'jam_survei' => '09:00:00',
    ]);

    // Pada tahap awal (belum upload survei), Tahap 4 harus terkunci rapat
    expect($pemesanan->isTahapSelesaiKerjaUnlocked())->toBeFalse();

    $this->actingAs($this->teknisi)
        ->get(route('teknisi.pemesanan.show', $pemesanan->id_pemesanan))
        ->assertSuccessful()
        ->assertSee('Tahap ini terkunci')
        ->assertDontSee('<span>Unggah Dokumen WO</span>', false)
        ->assertDontSee('<span>Unggah Bukti Pembayaran</span>', false);

    // Percobaan POST WO ditolak oleh validasi urutan backend
    $fileWo = UploadedFile::fake()->create('wo_seq.pdf', 500, 'application/pdf');
    $this->actingAs($this->teknisi)
        ->post(route('teknisi.pemesanan.wo', $pemesanan->id_pemesanan), [
            'file_wo' => $fileWo,
        ])
        ->assertRedirect(route('teknisi.pemesanan.show', $pemesanan->id_pemesanan))
        ->assertSessionHas('error', 'Urutan tidak valid: Foto kehadiran survei (Tahap 1) belum diunggah atau belum disetujui admin.');

    // Percobaan POST Bukti Bayar ditolak oleh validasi urutan backend
    $fileBayar = UploadedFile::fake()->image('bayar_seq.jpg');
    $this->actingAs($this->teknisi)
        ->post(route('teknisi.pemesanan.pembayaran', $pemesanan->id_pemesanan), [
            'file_bukti' => $fileBayar,
            'metode_pembayaran' => 'Cash',
        ])
        ->assertRedirect(route('teknisi.pemesanan.show', $pemesanan->id_pemesanan))
        ->assertSessionHas('error', 'Urutan tidak valid: Foto kehadiran survei (Tahap 1) belum diunggah atau belum disetujui admin.');

    expect(WorkOrder::count())->toBe(0);
    expect(BuktiPembayaran::count())->toBe(0);
});

test('11. teknisi tidak bisa mengajukan hasil survei jika jadwal pengerjaan bentrok dengan pekerjaan teknisi lain pada waktu yang sama', function () {
    // Pesanan pertama yang sudah aktif di jam 09:00
    $pemesanan1 = Pemesanan::create([
        'id_pelanggan' => $this->pelanggan->id_pelanggan,
        'id_tarif' => $this->tarif->id_tarif,
        'kode_pemesanan' => 'AMB-BENTROK1',
        'alamat_lokasi' => 'Jl. Lokasi Satu',
        'keluhan' => 'Rayap',
        'harga_estimasi' => 1000000,
        'status_pengerjaan' => 'On-Going',
        'status_pembayaran' => 'Belum Lunas',
    ]);

    JadwalPenugasan::create([
        'id_pemesanan' => $pemesanan1->id_pemesanan,
        'id_teknisi' => $this->teknisi->id_pengguna,
        'tgl_survei' => Carbon::today()->toDateString(),
        'jam_survei' => '08:00:00',
        'tgl_pengerjaan' => Carbon::tomorrow()->toDateString(),
        'jam_pengerjaan' => '09:00:00',
    ]);

    // Pesanan kedua milik teknisi yang sama
    $pemesanan2 = Pemesanan::create([
        'id_pelanggan' => $this->pelanggan->id_pelanggan,
        'id_tarif' => $this->tarif->id_tarif,
        'kode_pemesanan' => 'AMB-BENTROK2',
        'alamat_lokasi' => 'Jl. Lokasi Dua',
        'keluhan' => 'Tikus',
        'harga_estimasi' => 1200000,
        'status_pengerjaan' => 'On-Going',
        'status_pembayaran' => 'Belum Lunas',
    ]);

    JadwalPenugasan::create([
        'id_pemesanan' => $pemesanan2->id_pemesanan,
        'id_teknisi' => $this->teknisi->id_pengguna,
        'tgl_survei' => Carbon::today()->toDateString(),
        'jam_survei' => '08:30:00',
    ]);

    // Teknisi mencoba ajukan hasil survei dengan tanggal dan jam yang sama dengan pemesanan1
    $response = $this->actingAs($this->teknisi)
        ->post(route('teknisi.pemesanan.hasil-survei', $pemesanan2->id_pemesanan), [
            'catatan' => 'Hasil survei aman terkendali',
            'harga_kesepakatan' => 1200000,
            'tgl_pengerjaan_diajukan' => Carbon::tomorrow()->toDateString(),
            'jam_pengerjaan_diajukan' => '09:00',
        ]);

    $response->assertRedirect(route('teknisi.pemesanan.show', $pemesanan2->id_pemesanan));
    $response->assertSessionHas('error');
    expect(HasilSurvei::where('id_pemesanan', $pemesanan2->id_pemesanan)->count())->toBe(0);
});

test('12. admin tidak bisa menyetujui (ACC) hasil survei jika jadwal pengerjaan bertabrakan dengan jadwal aktif teknisi tersebut', function () {
    // Pesanan pertama yang sudah aktif di jam 10:00
    $pemesanan1 = Pemesanan::create([
        'id_pelanggan' => $this->pelanggan->id_pelanggan,
        'id_tarif' => $this->tarif->id_tarif,
        'kode_pemesanan' => 'AMB-ACC-BTK1',
        'alamat_lokasi' => 'Jl. Acc Satu',
        'keluhan' => 'Semut',
        'harga_estimasi' => 800000,
        'status_pengerjaan' => 'On-Going',
        'status_pembayaran' => 'Belum Lunas',
    ]);

    JadwalPenugasan::create([
        'id_pemesanan' => $pemesanan1->id_pemesanan,
        'id_teknisi' => $this->teknisi->id_pengguna,
        'tgl_survei' => Carbon::today()->toDateString(),
        'jam_survei' => '08:00:00',
        'tgl_pengerjaan' => Carbon::tomorrow()->toDateString(),
        'jam_pengerjaan' => '10:00:00',
    ]);

    // Pesanan kedua yang mengajukan jam yang sama
    $pemesanan2 = Pemesanan::create([
        'id_pelanggan' => $this->pelanggan->id_pelanggan,
        'id_tarif' => $this->tarif->id_tarif,
        'kode_pemesanan' => 'AMB-ACC-BTK2',
        'alamat_lokasi' => 'Jl. Acc Dua',
        'keluhan' => 'Kecoa',
        'harga_estimasi' => 900000,
        'status_pengerjaan' => 'On-Going',
        'status_pembayaran' => 'Belum Lunas',
    ]);

    JadwalPenugasan::create([
        'id_pemesanan' => $pemesanan2->id_pemesanan,
        'id_teknisi' => $this->teknisi->id_pengguna,
        'tgl_survei' => Carbon::today()->toDateString(),
        'jam_survei' => '08:30:00',
    ]);

    $hasilSurvei = HasilSurvei::create([
        'id_pemesanan' => $pemesanan2->id_pemesanan,
        'id_teknisi' => $this->teknisi->id_pengguna,
        'catatan' => 'Siap treatment besok jam 10',
        'harga_diajukan' => 900000,
        'tgl_pengerjaan_diajukan' => Carbon::tomorrow()->toDateString(),
        'jam_pengerjaan_diajukan' => '10:00:00',
        'status_verifikasi' => 'Menunggu',
    ]);

    // Halaman show admin menampilkan peringatan tabrakan & tombol disable
    $this->actingAs($this->admin)
        ->get(route('admin.pemesanan.show', $pemesanan2->id_pemesanan))
        ->assertSuccessful()
        ->assertSee('Jadwal Bertabrakan / Penuh')
        ->assertSee('Perhatian: Teknisi Sudah Memiliki Pekerjaan di Waktu Ini!')
        ->assertSee('Setujui (Jadwal Bentrok)');

    // Admin mencoba submit 'setujui' di backend tetap diblokir
    $response = $this->actingAs($this->admin)
        ->post(route('admin.verifikasi.hasil-survei', $hasilSurvei->id_hasil_survei), [
            'aksi' => 'setujui',
        ]);

    $response->assertRedirect(route('admin.pemesanan.show', $pemesanan2->id_pemesanan));
    $response->assertSessionHas('error');

    $hasilSurvei->refresh();
    expect($hasilSurvei->status_verifikasi)->toBe('Menunggu');
    expect($pemesanan2->jadwalPenugasan->tgl_pengerjaan)->toBeNull();
});

test('13. endpoint jadwal slot teknisi mengembalikan daftar jam sibuk teknisi yang akurat dan memblokir jam di halaman teknisi show', function () {
    $pemesanan1 = Pemesanan::create([
        'id_pelanggan' => $this->pelanggan->id_pelanggan,
        'id_tarif' => $this->tarif->id_tarif,
        'kode_pemesanan' => 'AMB-SLOT-01',
        'alamat_lokasi' => 'Jl. Slot Satu',
        'keluhan' => 'Tikus',
        'harga_estimasi' => 1000000,
        'status_pengerjaan' => 'On-Going',
        'status_pembayaran' => 'Belum Lunas',
    ]);

    JadwalPenugasan::create([
        'id_pemesanan' => $pemesanan1->id_pemesanan,
        'id_teknisi' => $this->teknisi->id_pengguna,
        'tgl_survei' => Carbon::today()->toDateString(),
        'jam_survei' => '08:00:00',
        'tgl_pengerjaan' => Carbon::tomorrow()->toDateString(),
        'jam_pengerjaan' => '09:00:00',
    ]);

    $response = $this->getJson(route('jadwal.slot-teknisi', [
        'id_teknisi' => $this->teknisi->id_pengguna,
    ]));

    $response->assertSuccessful();
    $tomorrowStr = Carbon::tomorrow()->toDateString();
    $response->assertJsonPath("jadwal.{$tomorrowStr}.jam.0", '09');

    // Halaman show teknisi memuat jadwal sibuk teknisi
    $pemesanan2 = Pemesanan::create([
        'id_pelanggan' => $this->pelanggan->id_pelanggan,
        'id_tarif' => $this->tarif->id_tarif,
        'kode_pemesanan' => 'AMB-SLOT-02',
        'alamat_lokasi' => 'Jl. Slot Dua',
        'keluhan' => 'Kecoa',
        'harga_estimasi' => 800000,
        'status_pengerjaan' => 'On-Going',
        'status_pembayaran' => 'Belum Lunas',
    ]);

    JadwalPenugasan::create([
        'id_pemesanan' => $pemesanan2->id_pemesanan,
        'id_teknisi' => $this->teknisi->id_pengguna,
        'tgl_survei' => Carbon::today()->toDateString(),
        'jam_survei' => '13:00:00',
    ]);

    $this->actingAs($this->teknisi)
        ->get(route('teknisi.pemesanan.show', $pemesanan2->id_pemesanan))
        ->assertSuccessful()
        ->assertSee('teknisi_slot_summary_badge', false)
        ->assertSee('syncTeknisiBusyHours', false);
});

test('halaman admin show memuat semuaJadwalSibukTeknisi dan memblokir bentrok slot survei teknisi', function () {
    $pemesanan1 = Pemesanan::create([
        'id_pelanggan' => $this->pelanggan->id_pelanggan,
        'id_tarif' => $this->tarif->id_tarif,
        'kode_pemesanan' => 'AMB-ADM-01',
        'alamat_lokasi' => 'Jl. Admin Satu',
        'keluhan' => 'Rayap',
        'harga_estimasi' => 1500000,
        'status_pengerjaan' => 'On-Going',
        'status_pembayaran' => 'Belum Lunas',
    ]);

    // Teknisi memiliki tugas survei jam 14:00 pada besok
    $tglBesok = Carbon::tomorrow()->toDateString();
    JadwalPenugasan::create([
        'id_pemesanan' => $pemesanan1->id_pemesanan,
        'id_teknisi' => $this->teknisi->id_pengguna,
        'tgl_survei' => $tglBesok,
        'jam_survei' => '14:00:00',
    ]);

    // Pemesanan baru yang belum diverifikasi (Pending)
    $pemesananPending = Pemesanan::create([
        'id_pelanggan' => $this->pelanggan->id_pelanggan,
        'id_tarif' => $this->tarif->id_tarif,
        'kode_pemesanan' => 'AMB-PEND-01',
        'alamat_lokasi' => 'Jl. Pending Satu',
        'keluhan' => 'Tawon',
        'harga_estimasi' => 500000,
        'status_pengerjaan' => 'Pending',
        'status_pembayaran' => 'Belum Bayar',
        'tgl_survei_diminta' => $tglBesok,
        'jam_survei_diminta' => '14:00',
    ]);

    $response = $this->actingAs($this->admin)
        ->get(route('admin.pemesanan.show', $pemesananPending->id_pemesanan));

    $response->assertSuccessful();
    $response->assertViewHas('semuaJadwalSibukTeknisi');
    $response->assertSee('admin_slot_status_badge', false);
    $response->assertSee('admin_teknisi_jadwal_info_box', false);
    $response->assertSee('syncAdminVerificationSlots', false);

    $semua = $response->viewData('semuaJadwalSibukTeknisi');
    expect($semua)->toBeArray();
    expect($semua[$this->teknisi->id_pengguna][$tglBesok]['jam'])->toContain('14');
    expect($semua[$this->teknisi->id_pengguna][$tglBesok]['detail']['14']['tipe'])->toBe('Survei');
});
