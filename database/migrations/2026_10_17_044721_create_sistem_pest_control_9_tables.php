<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Arsitektur 9 Tabel Utama Sistem Pemesanan Pest Control CV. Ambius:
     * 1. pengguna (Admin & Teknisi dengan login & role)
     * 2. pelanggan (Data pemesan tanpa registrasi/login)
     * 3. tarif_layanan (Katalog tarif aktif/nonaktif untuk kalkulasi otomatis estimasi harga)
     * 4. pemesanan (Transaksi pemesanan jasa pest control)
     * 5. jadwal_penugasan (Jadwal survei dan pengerjaan teknisi)
     * 6. hasil_survei (Hasil kesepakatan survei yang diajukan teknisi & diverifikasi admin)
     * 7. bukti_kehadiran (Foto kehadiran teknisi pada tahap Survei & Pengerjaan)
     * 8. work_order (Dokumen work order pengerjaan pasca treatment)
     * 9. bukti_pembayaran (Dokumen/foto bukti pembayaran cash/transfer yang diverifikasi admin)
     */
    public function up(): void
    {
        // 1. Tabel pengguna
        Schema::create('pengguna', function (Blueprint $table) {
            $table->id('id_pengguna');
            $table->string('nama');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('no_hp')->nullable();
            $table->string('role')->default('teknisi')->index(); // 'admin', 'teknisi'
            $table->boolean('status_aktif')->default(true)->index();
            $table->rememberToken();
            $table->timestamps();
        });

        // 2. Tabel pelanggan (tanpa registrasi/login)
        Schema::create('pelanggan', function (Blueprint $table) {
            $table->id('id_pelanggan');
            $table->string('nama');
            $table->string('no_hp')->index();
            $table->string('email')->nullable();
            $table->string('nama_usaha')->nullable();
            $table->text('alamat')->nullable();
            $table->timestamps();
        });

        // 3. Tabel tarif_layanan
        Schema::create('tarif_layanan', function (Blueprint $table) {
            $table->id('id_tarif');
            $table->string('jenis_layanan')->index(); // Rayap, Tikus, Kecoa, Nyamuk, Lalat
            $table->string('jenis_properti')->nullable(); // Rumah Tinggal, Ruko/Restoran, Kantor/Gedung, Gudang/Pabrik
            $table->integer('ukuran_area_min')->nullable();
            $table->integer('ukuran_area_max')->nullable();
            $table->string('satuan')->default('m2');
            $table->decimal('tarif_dasar', 15, 2);
            $table->string('nama_paket')->nullable();
            $table->text('deskripsi')->nullable();
            $table->boolean('status_aktif')->default(true)->index();
            $table->timestamps();
        });

        // 4. Tabel pemesanan
        Schema::create('pemesanan', function (Blueprint $table) {
            $table->id('id_pemesanan');
            $table->foreignId('id_admin')->nullable()->constrained('pengguna', 'id_pengguna')->nullOnDelete();
            $table->foreignId('id_pelanggan')->constrained('pelanggan', 'id_pelanggan')->cascadeOnDelete();
            $table->foreignId('id_tarif')->constrained('tarif_layanan', 'id_tarif');
            $table->string('kode_pemesanan')->unique(); // AMB-XXXXXX
            $table->string('jenis_properti');
            $table->text('alamat_lokasi');
            $table->text('keluhan');
            $table->decimal('ukuran_area', 10, 2)->nullable();
            $table->date('tgl_survei_diminta');
            $table->time('jam_survei_diminta');
            $table->decimal('harga_estimasi', 15, 2);
            $table->decimal('harga_final', 15, 2)->nullable();
            $table->string('status_pengerjaan')->default('Pending')->index(); // Pending, On-Going, Done, Cancel
            $table->string('status_pembayaran')->default('Belum Lunas')->index(); // Belum Lunas, Lunas, Ditolak
            $table->text('alasan_pembatalan')->nullable();
            $table->string('dibatalkan_oleh')->nullable(); // Pelanggan, CV. Ambius Indonesia, Sistem
            $table->dateTime('batas_konfirmasi');
            $table->dateTime('tgl_pemesanan');
            $table->dateTime('tgl_selesai')->nullable();
            $table->dateTime('tgl_verifikasi')->nullable();
            $table->timestamps();
        });

        // 5. Tabel jadwal_penugasan
        Schema::create('jadwal_penugasan', function (Blueprint $table) {
            $table->id('id_jadwal');
            $table->foreignId('id_pemesanan')->constrained('pemesanan', 'id_pemesanan')->cascadeOnDelete();
            $table->foreignId('id_teknisi')->constrained('pengguna', 'id_pengguna');
            $table->date('tgl_survei');
            $table->time('jam_survei');
            $table->date('tgl_pengerjaan')->nullable();
            $table->time('jam_pengerjaan')->nullable();
            $table->timestamps();
        });

        // 6. Tabel hasil_survei
        Schema::create('hasil_survei', function (Blueprint $table) {
            $table->id('id_hasil_survei');
            $table->foreignId('id_pemesanan')->constrained('pemesanan', 'id_pemesanan')->cascadeOnDelete();
            $table->foreignId('id_teknisi')->constrained('pengguna', 'id_pengguna');
            $table->foreignId('id_admin')->nullable()->constrained('pengguna', 'id_pengguna')->nullOnDelete();
            $table->date('tgl_pengerjaan_diajukan');
            $table->time('jam_pengerjaan_diajukan');
            $table->decimal('harga_diajukan', 15, 2);
            $table->text('catatan_survei')->nullable();
            $table->string('status_verifikasi')->default('Menunggu')->index(); // Menunggu, Disetujui, Ditolak
            $table->text('alasan_penolakan')->nullable();
            $table->dateTime('tgl_verifikasi')->nullable();
            $table->timestamps();
        });

        // 7. Tabel bukti_kehadiran
        Schema::create('bukti_kehadiran', function (Blueprint $table) {
            $table->id('id_bukti_kehadiran');
            $table->foreignId('id_pemesanan')->constrained('pemesanan', 'id_pemesanan')->cascadeOnDelete();
            $table->foreignId('id_teknisi')->constrained('pengguna', 'id_pengguna');
            $table->foreignId('id_admin')->nullable()->constrained('pengguna', 'id_pengguna')->nullOnDelete();
            $table->string('jenis_kehadiran')->index(); // Survei, Pengerjaan
            $table->string('foto');
            $table->text('catatan')->nullable();
            $table->string('status_verifikasi')->default('Menunggu')->index(); // Menunggu, Disetujui, Ditolak
            $table->text('alasan_penolakan')->nullable();
            $table->dateTime('tgl_verifikasi')->nullable();
            $table->timestamps();
        });

        // 8. Tabel work_order
        Schema::create('work_order', function (Blueprint $table) {
            $table->id('id_wo');
            $table->foreignId('id_pemesanan')->constrained('pemesanan', 'id_pemesanan')->cascadeOnDelete();
            $table->foreignId('id_teknisi')->constrained('pengguna', 'id_pengguna');
            $table->foreignId('id_admin')->nullable()->constrained('pengguna', 'id_pengguna')->nullOnDelete();
            $table->string('file_wo');
            $table->text('catatan')->nullable();
            $table->string('status_verifikasi')->default('Menunggu')->index(); // Menunggu, Disetujui, Ditolak
            $table->text('alasan_penolakan')->nullable();
            $table->dateTime('tgl_verifikasi')->nullable();
            $table->timestamps();
        });

        // 9. Tabel bukti_pembayaran
        Schema::create('bukti_pembayaran', function (Blueprint $table) {
            $table->id('id_bukti_pembayaran');
            $table->foreignId('id_pemesanan')->constrained('pemesanan', 'id_pemesanan')->cascadeOnDelete();
            $table->foreignId('id_teknisi')->constrained('pengguna', 'id_pengguna');
            $table->foreignId('id_admin')->nullable()->constrained('pengguna', 'id_pengguna')->nullOnDelete();
            $table->string('file_bukti');
            $table->string('metode_pembayaran')->default('Transfer'); // Cash, Transfer
            $table->decimal('jumlah_bayar', 15, 2)->nullable();
            $table->text('catatan')->nullable();
            $table->string('status_verifikasi')->default('Menunggu')->index(); // Menunggu, Disetujui, Ditolak
            $table->text('alasan_penolakan')->nullable();
            $table->dateTime('tgl_verifikasi')->nullable();
            $table->timestamps();
        });

        // Seed data dasar tarif_layanan
        $defaultTarif = [
            [
                'jenis_layanan' => 'Pengendalian Rayap (Termite Control)',
                'jenis_properti' => 'Rumah Tinggal',
                'ukuran_area_min' => 1,
                'ukuran_area_max' => 200,
                'satuan' => 'm2',
                'tarif_dasar' => 1500000,
                'nama_paket' => 'Paket Rumah Residensial Standar',
                'deskripsi' => 'Injeksi kimia anti-rayap perimeter dan pondasi garansi 3 tahun.',
                'status_aktif' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'jenis_layanan' => 'Pengendalian Tikus (Rodent Control)',
                'jenis_properti' => 'Rumah Tinggal',
                'ukuran_area_min' => 1,
                'ukuran_area_max' => 150,
                'satuan' => 'm2',
                'tarif_dasar' => 850000,
                'nama_paket' => 'Paket Rodent Baiting & Trapping',
                'deskripsi' => 'Pemasangan bait station, perangkap lem tikus, dan sanitasi jalur tikus.',
                'status_aktif' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'jenis_layanan' => 'Pengendalian Kecoa (Cockroach Control)',
                'jenis_properti' => 'Rumah Tinggal',
                'ukuran_area_min' => 1,
                'ukuran_area_max' => 150,
                'satuan' => 'm2',
                'tarif_dasar' => 650000,
                'nama_paket' => 'Paket Gel Baiting & Cold Fogging Kecoa',
                'deskripsi' => 'Aplikasi gel baiting di celah dapur & cold fogging area tersembunyi.',
                'status_aktif' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'jenis_layanan' => 'Pengendalian Nyamuk (Mosquito Control)',
                'jenis_properti' => 'Rumah Tinggal',
                'ukuran_area_min' => 1,
                'ukuran_area_max' => 300,
                'satuan' => 'm2',
                'tarif_dasar' => 750000,
                'nama_paket' => 'Paket Thermal Fogging & Larvasida',
                'deskripsi' => 'Pengasapan area luar (taman/drainase) dan larvasida pada genangan air.',
                'status_aktif' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'jenis_layanan' => 'Pengendalian Lalat (Fly Control)',
                'jenis_properti' => 'Ruko/Restoran',
                'ukuran_area_min' => 1,
                'ukuran_area_max' => 150,
                'satuan' => 'm2',
                'tarif_dasar' => 600000,
                'nama_paket' => 'Paket Fly Baiting & Residual Spray',
                'deskripsi' => 'Penyemprotan residual ramah lingkungan dan umpan lalat organik.',
                'status_aktif' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'jenis_layanan' => 'Pengendalian Rayap (Termite Control)',
                'jenis_properti' => 'Ruko/Restoran',
                'ukuran_area_min' => 100,
                'ukuran_area_max' => 500,
                'satuan' => 'm2',
                'tarif_dasar' => 2500000,
                'nama_paket' => 'Paket Komersial & Bisnis',
                'deskripsi' => 'Perlakuan anti rayap menyeluruh untuk bangunan komersial bersertifikat.',
                'status_aktif' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('tarif_layanan')->insert($defaultTarif);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bukti_pembayaran');
        Schema::dropIfExists('work_order');
        Schema::dropIfExists('bukti_kehadiran');
        Schema::dropIfExists('hasil_survei');
        Schema::dropIfExists('jadwal_penugasan');
        Schema::dropIfExists('pemesanan');
        Schema::dropIfExists('tarif_layanan');
        Schema::dropIfExists('pelanggan');
        Schema::dropIfExists('pengguna');
    }
};
