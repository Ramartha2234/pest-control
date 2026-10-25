<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tarif_area', function (Blueprint $table) {
            $table->id('id_tarif_area');
            $table->string('kode_area', 50)->unique();
            $table->string('nama_area', 100);
            $table->decimal('biaya_tambahan', 14, 2)->default(0);
            $table->text('keterangan')->nullable();
            $table->boolean('status_aktif')->default(true);
            $table->timestamps();
        });

        // Seed data awal untuk Indoor dan Outdoor
        DB::table('tarif_area')->insert([
            [
                'kode_area' => 'indoor',
                'nama_area' => 'Indoor (Dalam Ruangan)',
                'biaya_tambahan' => 0.00,
                'keterangan' => 'Rumah, kantor, kamar, restoran, gudang',
                'status_aktif' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'kode_area' => 'outdoor',
                'nama_area' => 'Outdoor (Luar Ruangan)',
                'biaya_tambahan' => 100000.00,
                'keterangan' => 'Halaman, taman, perimeter luar, pekarangan',
                'status_aktif' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tarif_area');
    }
};
