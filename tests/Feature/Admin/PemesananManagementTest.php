<?php

use App\Enums\StatusPelunasan;
use App\Enums\StatusPengerjaan;
use App\Models\Pemesanan;
use App\Models\Teknisi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

test('admin can view list of bookings', function () {
    $admin = User::factory()->create();
    $pemesanan = Pemesanan::factory()->create(['kode_unik' => 'PC-TEST-LIST']);

    $this->actingAs($admin)
        ->get('/admin/pemesanan')
        ->assertSuccessful()
        ->assertSee('PC-TEST-LIST');
});

test('admin can update order details successfully', function () {
    $admin = User::factory()->create();
    $teknisi = Teknisi::factory()->create();
    $pemesanan = Pemesanan::factory()->create([
        'status_pengerjaan' => StatusPengerjaan::Pending,
        'is_lunas' => StatusPelunasan::BelumLunas,
        'nominal' => 3000000,
    ]);

    $response = $this->actingAs($admin)
        ->put("/admin/pemesanan/{$pemesanan->id}", [
            'teknisi_id' => $teknisi->id,
            'status_pengerjaan' => StatusPengerjaan::Ongoing->value,
            'is_lunas' => StatusPelunasan::BelumLunas->value,
            'tanggal_survey' => $pemesanan->tanggal_survey->format('Y-m-d'),
            'tanggal_pengerjaan' => $pemesanan->tanggal_survey->addDays(1)->format('Y-m-d'),
            'nominal' => 3000000,
            'harga_final' => 3500000,
        ]);

    $response->assertRedirect(route('admin.pemesanan.show', $pemesanan));
    $pemesanan->refresh();
    expect($pemesanan->status_pengerjaan)->toBeIn([StatusPengerjaan::Ongoing, 'On-Going', 'ongoing']);
    expect($pemesanan->teknisi_id)->toBe($teknisi->id);
    expect($pemesanan->nominal)->toBe('3500000.00');
    expect($pemesanan->harga_final)->toBe('3500000.00');
});

test('admin cannot update status to Ongoing if tanggal_pengerjaan is missing', function () {
    $admin = User::factory()->create();
    $pemesanan = Pemesanan::factory()->create([
        'status_pengerjaan' => StatusPengerjaan::Pending,
        'is_lunas' => StatusPelunasan::BelumLunas,
        'nominal' => 2000000,
    ]);

    $response = $this->actingAs($admin)
        ->put("/admin/pemesanan/{$pemesanan->id}", [
            'status_pengerjaan' => StatusPengerjaan::Ongoing->value,
            'is_lunas' => StatusPelunasan::BelumLunas->value,
            'tanggal_survey' => $pemesanan->tanggal_survey->format('Y-m-d'),
            'tanggal_pengerjaan' => null, // missing/empty
            'nominal' => 2000000,
        ]);

    $response->assertSessionHasErrors(['tanggal_pengerjaan']);
    $pemesanan->refresh();
    expect($pemesanan->status_pengerjaan)->not->toBeIn([StatusPengerjaan::Ongoing, 'On-Going', 'ongoing']);
});

test('admin cannot update status to Done if payment is not lunas', function () {
    $admin = User::factory()->create();
    $pemesanan = Pemesanan::factory()->create([
        'status_pengerjaan' => StatusPengerjaan::Ongoing,
        'is_lunas' => StatusPelunasan::BelumLunas,
        'nominal' => 2000000,
    ]);

    $response = $this->actingAs($admin)
        ->put("/admin/pemesanan/{$pemesanan->id}", [
            'status_pengerjaan' => StatusPengerjaan::Done->value,
            'is_lunas' => StatusPelunasan::BelumLunas->value, // Not lunas
            'tanggal_survey' => $pemesanan->tanggal_survey->format('Y-m-d'),
            'nominal' => 2000000,
        ]);

    $response->assertSessionHasErrors(['status_pengerjaan']);
    $pemesanan->refresh();
    expect($pemesanan->status_pengerjaan)->not->toBeIn([StatusPengerjaan::Done, 'Done', 'done']);
});

test('admin can update status to Done if payment is lunas', function () {
    $admin = User::factory()->create();
    $pemesanan = Pemesanan::factory()->create([
        'status_pengerjaan' => StatusPengerjaan::Ongoing,
        'is_lunas' => StatusPelunasan::BelumLunas,
        'nominal' => 2000000,
    ]);

    $response = $this->actingAs($admin)
        ->put("/admin/pemesanan/{$pemesanan->id}", [
            'status_pengerjaan' => StatusPengerjaan::Done->value,
            'is_lunas' => StatusPelunasan::Lunas->value, // Lunas
            'tanggal_survey' => $pemesanan->tanggal_survey->format('Y-m-d'),
            'nominal' => 2000000,
        ]);

    $response->assertSessionHasNoErrors();
    $pemesanan->refresh();
    expect($pemesanan->status_pengerjaan)->toBeIn([StatusPengerjaan::Done, 'Done', 'done']);
    expect($pemesanan->is_lunas)->toBe(StatusPelunasan::Lunas);
});

test('admin can cancel order with reason', function () {
    $admin = User::factory()->create();
    $pemesanan = Pemesanan::factory()->create(['status_pengerjaan' => StatusPengerjaan::Pending]);

    $response = $this->actingAs($admin)
        ->post("/admin/pemesanan/{$pemesanan->id}/cancel", [
            'alasan_cancel' => 'Client membatalkan karena pindah jadwal',
        ]);

    $response->assertRedirect(route('admin.pemesanan.show', $pemesanan));
    $pemesanan->refresh();
    expect($pemesanan->status_pengerjaan)->toBeIn([StatusPengerjaan::Cancel, 'Cancel', 'cancel']);
    expect($pemesanan->alasan_cancel)->toBe('Client membatalkan karena pindah jadwal');
    expect($pemesanan->is_lunas)->toBe(StatusPelunasan::Ditolak);
});

test('updating order status to cancel automatically rejects settlement status', function () {
    $admin = User::factory()->create();
    $pemesanan = Pemesanan::factory()->create([
        'status_pengerjaan' => StatusPengerjaan::Pending,
        'is_lunas' => StatusPelunasan::BelumLunas,
        'nominal' => 2000000,
    ]);

    $response = $this->actingAs($admin)
        ->put("/admin/pemesanan/{$pemesanan->id}", [
            'status_pengerjaan' => StatusPengerjaan::Cancel->value,
            'is_lunas' => StatusPelunasan::BelumLunas->value, // Even if requested as belum_lunas
            'tanggal_survey' => $pemesanan->tanggal_survey->format('Y-m-d'),
            'nominal' => 2000000,
            'alasan_cancel' => 'Dibatalkan oleh admin',
        ]);

    $response->assertRedirect(route('admin.pemesanan.show', $pemesanan));
    $pemesanan->refresh();
    expect($pemesanan->status_pengerjaan)->toBeIn([StatusPengerjaan::Cancel, 'Cancel', 'cancel']);
    expect($pemesanan->is_lunas)->toBe(StatusPelunasan::Ditolak);
});

test('admin can upload treatment photo evidence', function () {
    Storage::fake('public');
    $admin = User::factory()->create();
    $pemesanan = Pemesanan::factory()->create();
    $file = UploadedFile::fake()->image('treatment.jpg');

    $response = $this->actingAs($admin)
        ->post("/admin/pemesanan/{$pemesanan->id}/upload-bukti", [
            'foto' => $file,
        ]);

    $response->assertRedirect(route('admin.pemesanan.show', $pemesanan));
    expect($pemesanan->buktiPengerjaans()->count())->toBe(1);

    $bukti = $pemesanan->buktiPengerjaans->first();
    Storage::disk('public')->assertExists($bukti->foto);
});

test('admin can delete booking', function () {
    $admin = User::factory()->create();
    $pemesanan = Pemesanan::factory()->create();

    $response = $this->actingAs($admin)
        ->delete("/admin/pemesanan/{$pemesanan->id}");

    $response->assertRedirect(route('admin.pemesanan.index'));
    expect(Pemesanan::count())->toBe(0);
});

test('admin list of bookings is sorted by closest date', function () {
    $admin = User::factory()->create();

    // Create three bookings with different dates
    // Booking 1: Pengerjaan is in 5 days, Survey was 2 days ago
    $booking1 = Pemesanan::factory()->create([
        'kode_unik' => 'PC-DATE-3',
        'tanggal_survey' => now()->subDays(2),
        'tanggal_pengerjaan' => now()->addDays(5),
    ]);

    // Booking 2: Pengerjaan is null, Survey is tomorrow (1 day)
    $booking2 = Pemesanan::factory()->create([
        'kode_unik' => 'PC-DATE-1',
        'tanggal_survey' => now()->addDays(1),
        'tanggal_pengerjaan' => null,
    ]);

    // Booking 3: Pengerjaan is in 3 days, Survey was 1 day ago
    $booking3 = Pemesanan::factory()->create([
        'kode_unik' => 'PC-DATE-2',
        'tanggal_survey' => now()->subDays(1),
        'tanggal_pengerjaan' => now()->addDays(3),
    ]);

    $response = $this->actingAs($admin)
        ->get('/admin/pemesanan')
        ->assertSuccessful();

    // The order should be:
    // 1. $booking2 (COALESCE is tomorrow, i.e., 1 day)
    // 2. $booking3 (COALESCE is 3 days)
    // 3. $booking1 (COALESCE is 5 days)
    $response->assertSeeInOrder([
        'PC-DATE-1',
        'PC-DATE-2',
        'PC-DATE-3',
    ]);
});
