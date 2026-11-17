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
        Schema::table('pemesanan', function (Blueprint $table) {
            $table->dateTime('tgl_verifikasi_dp')->nullable()->after('tgl_bayar_dp');
        });

        // Backfill data lama yang sudah memiliki tgl_bayar_dp
        DB::table('pemesanan')
            ->whereNotNull('tgl_bayar_dp')
            ->whereNull('tgl_verifikasi_dp')
            ->update(['tgl_verifikasi_dp' => DB::raw('tgl_bayar_dp')]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pemesanan', function (Blueprint $table) {
            $table->dropColumn('tgl_verifikasi_dp');
        });
    }
};
