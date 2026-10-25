<?php

use App\Enums\StatusPelunasan;
use App\Enums\StatusPengerjaan;
use App\Models\Pemesanan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('authenticated admin can view dashboard stats', function () {
    $admin = User::factory()->create();

    // Create some orders
    Pemesanan::factory()->create([
        'status_pengerjaan' => StatusPengerjaan::Pending,
        'nominal' => 2000000,
        'is_lunas' => StatusPelunasan::BelumLunas,
        'tanggal_survey' => now(),
    ]);

    Pemesanan::factory()->create([
        'status_pengerjaan' => StatusPengerjaan::Ongoing,
        'nominal' => 3000000,
        'is_lunas' => StatusPelunasan::BelumLunas,
        'tanggal_survey' => now(),
    ]);

    Pemesanan::factory()->create([
        'status_pengerjaan' => StatusPengerjaan::Done,
        'nominal' => 5000000,
        'is_lunas' => StatusPelunasan::Lunas,
        'tanggal_survey' => now(),
    ]);

    $this->actingAs($admin)
        ->get('/admin')
        ->assertSuccessful()
        ->assertSee('Dashboard Ringkasan')
        // Check MTD Revenue (lunas)
        ->assertSee('Rp 5.000.000')
        // Check order count
        ->assertSee('3 Order')
        // Check status counts
        ->assertSee('Belum Survey')
        ->assertSee('Sudah Survey')
        ->assertSee('Jadwal Pengerjaan / Treatment')
        ->assertSee('Jadwal Survey / Belum Survey')
        ->assertSee('Pemesanan Butuh Verifikasi (Respon WA)')
        ->assertSee('1');
});

test('dashboard shows correct tomorrow agenda reminders', function () {
    $admin = User::factory()->create();

    // No agenda initially
    $this->actingAs($admin)
        ->get('/admin')
        ->assertSuccessful()
        ->assertSee('Tidak ada agenda survey atau pengerjaan untuk esok hari.');

    // Create tomorrow's survey
    Pemesanan::factory()->create([
        'status_pengerjaan' => StatusPengerjaan::Pending,
        'tanggal_survey' => now()->addDay(),
        'tanggal_pengerjaan' => null,
        'is_fiktif' => false,
    ]);

    // Create tomorrow's treatment
    Pemesanan::factory()->create([
        'status_pengerjaan' => StatusPengerjaan::Ongoing,
        'tanggal_pengerjaan' => now()->addDay(),
        'is_fiktif' => false,
    ]);

    $this->actingAs($admin)
        ->get('/admin')
        ->assertSuccessful()
        ->assertSee('Agenda Hari Besok')
        ->assertSee('2 Agenda')
        ->assertSee('Survey:')
        ->assertSee('Pengerjaan:');
});
