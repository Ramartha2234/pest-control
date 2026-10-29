<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('pemesanan', function (Blueprint $table) {
            $table->boolean('wajib_dp')->default(false)->index()->after('harga_final');
            $table->decimal('nominal_dp', 15, 2)->default(0)->after('wajib_dp');
            $table->string('status_dp')->nullable()->default('Belum Dibayar')->index()->after('nominal_dp'); // Belum Dibayar, Lunas
            $table->string('bukti_dp')->nullable()->after('status_dp');
            $table->dateTime('tgl_bayar_dp')->nullable()->after('bukti_dp');
            $table->text('catatan_dp')->nullable()->after('tgl_bayar_dp');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pemesanan', function (Blueprint $table) {
            $table->dropColumn([
                'wajib_dp',
                'nominal_dp',
                'status_dp',
                'bukti_dp',
                'tgl_bayar_dp',
                'catatan_dp',
            ]);
        });
    }
};
