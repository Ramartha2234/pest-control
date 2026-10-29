<?php

namespace Database\Factories;

use App\Models\Pelanggan;
use App\Models\Pemesanan;
use App\Models\TarifLayanan;
use App\Models\Teknisi;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Pemesanan>
 */
class PemesananFactory extends Factory
{
    protected $model = Pemesanan::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $pelanggan = Pelanggan::first() ?? Pelanggan::create([
            'nama' => fake()->name(),
            'no_hp' => fake()->phoneNumber(),
            'email' => fake()->safeEmail(),
            'alamat' => fake()->address(),
        ]);

        $tarif = TarifLayanan::first() ?? TarifLayanan::create([
            'jenis_layanan' => 'Pengendalian Rayap (Termite Control)',
            'jenis_properti' => 'Rumah Tinggal',
            'satuan' => 'm2',
            'tarif_dasar' => 1500000,
            'status_aktif' => true,
        ]);

        $nominal = fake()->randomFloat(2, 500_000, 20_000_000);

        return [
            'id_pelanggan' => $pelanggan->id_pelanggan,
            'id_tarif' => $tarif->id_tarif,
            'kode_pemesanan' => Pemesanan::generateKodePemesanan(),
            'jenis_properti' => 'Rumah Tinggal',
            'tipe_area' => fake()->randomElement(['Indoor', 'Outdoor']),
            'alamat_lokasi' => fake()->address(),
            'keluhan' => 'Pemeriksaan rutin dan penanganan hama rayap',
            'ukuran_area' => 120,
            'tgl_survei_diminta' => fake()->dateTimeBetween('-30 days', '+30 days')->format('Y-m-d'),
            'jam_survei_diminta' => '09:00',
            'harga_estimasi' => $nominal,
            'harga_final' => null,
            'status_pengerjaan' => 'Pending',
            'status_pembayaran' => 'Belum Lunas',
            'tgl_pemesanan' => now(),
            'batas_konfirmasi' => now()->addDays(3),
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (Pemesanan $pemesanan) {
            // Map legacy attributes if set
            if ($pemesanan->getRawOriginal('kode_unik')) {
                $pemesanan->kode_pemesanan = $pemesanan->getRawOriginal('kode_unik');
            }
            if ($pemesanan->getRawOriginal('tanggal_survey')) {
                $pemesanan->tanggal_survey = $pemesanan->getRawOriginal('tanggal_survey');
            }
            if ($pemesanan->getRawOriginal('tanggal_pengerjaan')) {
                $pemesanan->tanggal_pengerjaan = $pemesanan->getRawOriginal('tanggal_pengerjaan');
            }
        });
    }

    public function ongoing(): static
    {
        return $this->state(fn (array $attributes) => [
            'status_pengerjaan' => 'On-Going',
            'tgl_verifikasi' => now(),
        ]);
    }

    public function done(): static
    {
        return $this->state(fn (array $attributes) => [
            'status_pengerjaan' => 'Done',
            'status_pembayaran' => 'Lunas',
            'tgl_verifikasi' => now()->subDays(2),
            'tgl_selesai' => now(),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status_pengerjaan' => 'Cancel',
            'status_pembayaran' => 'Ditolak',
            'dibatalkan_oleh' => 'CV. Ambius Indonesia',
            'alasan_pembatalan' => fake()->sentence(),
        ]);
    }

    public function withTeknisi(?Teknisi $teknisi = null): static
    {
        return $this->state(fn (array $attributes) => [
            'teknisi_id' => $teknisi?->id ?? 1,
        ]);
    }

    public function highValue(): static
    {
        return $this->state(fn (array $attributes) => [
            'harga_estimasi' => fake()->randomFloat(2, 5_000_000, 50_000_000),
            'harga_final' => fake()->randomFloat(2, 5_000_000, 50_000_000),
        ]);
    }
}
