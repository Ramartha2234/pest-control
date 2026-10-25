<?php

use App\Enums\StatusPelunasan;
use App\Models\Pemesanan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('authenticated admin can verify a booking as valid (tidak fiktif)', function () {
    $admin = User::factory()->create();
    $pemesanan = Pemesanan::factory()->create([
        'is_fiktif' => null,
        'status_pengerjaan' => 'Pending',
        'is_lunas' => StatusPelunasan::BelumLunas,
    ]);

    $response = $this->actingAs($admin)
        ->post(route('admin.pemesanan.verify-fiktif', $pemesanan->id), [
            'is_fiktif' => 'false',
        ]);

    $response->assertRedirect(route('admin.pemesanan.show', $pemesanan->id));
    $response->assertSessionHas('success', 'Pemesanan telah diverifikasi sebagai valid (tidak fiktif).');

    $pemesanan->refresh();
    expect($pemesanan->is_fiktif)->toBeFalse();
    expect($pemesanan->status_pengerjaan)->toBe('Pending');
});

test('authenticated admin can mark a booking as fiktif (fake)', function () {
    $admin = User::factory()->create();
    $pemesanan = Pemesanan::factory()->create([
        'is_fiktif' => null,
        'status_pengerjaan' => 'Pending',
        'is_lunas' => StatusPelunasan::BelumLunas,
    ]);

    $response = $this->actingAs($admin)
        ->post(route('admin.pemesanan.verify-fiktif', $pemesanan->id), [
            'is_fiktif' => 'true',
        ]);

    $response->assertRedirect(route('admin.pemesanan.show', $pemesanan->id));
    $response->assertSessionHas('success', 'Pemesanan berhasil ditandai sebagai fiktif.');

    $pemesanan->refresh();
    expect($pemesanan->is_fiktif)->toBeTrue();
    expect($pemesanan->status_pengerjaan)->toBe('Cancel');
    expect($pemesanan->status_pembayaran)->toBe('Ditolak');
    expect($pemesanan->alasan_cancel)->toBe('Telah diverifikasi sebagai pemesanan fiktif oleh admin.');
});

test('artisan command auto-cancels unverified bookings after 3 days', function () {
    // 1. Unverified booking (> 3 days old) -> Should be auto-cancelled
    $expiredUnverified = Pemesanan::factory()->create([
        'is_fiktif' => null,
        'status_pengerjaan' => 'Pending',
        'is_lunas' => StatusPelunasan::BelumLunas,
        'created_at' => now()->subDays(4),
        'batas_konfirmasi' => now()->subDay(),
    ]);

    // 2. Verified valid booking (> 3 days old) -> Should NOT be auto-cancelled
    $expiredValid = Pemesanan::factory()->create([
        'is_fiktif' => false,
        'status_pengerjaan' => 'Pending',
        'is_lunas' => StatusPelunasan::BelumLunas,
        'tgl_verifikasi' => now()->subDays(4),
        'created_at' => now()->subDays(4),
    ]);

    // 3. Unverified booking (< 3 days old) -> Should NOT be auto-cancelled
    $recentUnverified = Pemesanan::factory()->create([
        'is_fiktif' => null,
        'status_pengerjaan' => 'Pending',
        'is_lunas' => StatusPelunasan::BelumLunas,
        'created_at' => now()->subDays(2),
        'batas_konfirmasi' => now()->addDay(),
    ]);

    // Run the command
    $this->artisan('pemesanan:auto-cancel')
        ->expectsOutput('Successfully cancelled 1 expired/fake bookings.')
        ->assertExitCode(0);

    // Refresh models
    $expiredUnverified->refresh();
    $expiredValid->refresh();
    $recentUnverified->refresh();

    // Assert expired unverified is cancelled & marked fiktif/ditolak
    expect($expiredUnverified->is_fiktif)->toBeTrue();
    expect($expiredUnverified->status_pengerjaan)->toBe('Cancel');
    expect($expiredUnverified->status_pembayaran)->toBe('Ditolak');

    // Assert expired valid is NOT cancelled
    expect($expiredValid->is_fiktif)->toBeFalse();
    expect($expiredValid->status_pengerjaan)->toBe('Pending');
    expect($expiredValid->status_pembayaran)->toBe('Belum Lunas');

    // Assert recent unverified is NOT cancelled
    expect($recentUnverified->is_fiktif)->toBeNull();
    expect($recentUnverified->status_pengerjaan)->toBe('Pending');
    expect($recentUnverified->status_pembayaran)->toBe('Belum Lunas');
});
