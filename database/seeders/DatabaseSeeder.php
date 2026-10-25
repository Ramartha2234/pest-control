<?php

namespace Database\Seeders;

use App\Enums\StatusPelunasan;
use App\Enums\StatusPengerjaan;
use App\Models\Pemesanan;
use App\Models\Teknisi;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Create admin user
        User::factory()->create([
            'name' => 'Admin CV. Ambius',
            'email' => 'admin@ambius.com',
            'password' => bcrypt('password'),
        ]);

        // Create teknisi
        $teknisis = Teknisi::factory()->count(5)->create();

        // Create inactive teknisi
        Teknisi::factory()->inactive()->create([
            'nama' => 'Budi (Nonaktif)',
        ]);

        // Create sample pemesanan with various statuses
        Pemesanan::factory()->count(3)->create([
            'status_pengerjaan' => StatusPengerjaan::Pending,
        ]);

        Pemesanan::factory()->ongoing()->count(4)->create([
            'teknisi_id' => $teknisis->random()->id,
        ]);

        Pemesanan::factory()->count(2)->create([
            'status_pengerjaan' => StatusPengerjaan::Done,
            'is_lunas' => StatusPelunasan::Lunas,
            'teknisi_id' => $teknisis->random()->id,
        ]);

        Pemesanan::factory()->cancelled()->create();

        // Create a high-value order
        Pemesanan::factory()->ongoing()->create([
            'nominal' => 8_000_000,
            'teknisi_id' => $teknisis->random()->id,
        ]);
    }
}
