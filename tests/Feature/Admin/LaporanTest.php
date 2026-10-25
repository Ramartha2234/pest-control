<?php

use App\Enums\StatusPelunasan;
use App\Models\Pemesanan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

test('admin can access monthly report page', function () {
    $admin = User::factory()->create();

    Pemesanan::factory()->create([
        'nominal' => 4500000,
        'is_lunas' => StatusPelunasan::Lunas,
        'tanggal_survey' => now(),
    ]);

    $this->actingAs($admin)
        ->get('/admin/laporan?view_type=monthly')
        ->assertSuccessful()
        ->assertSee('Rekap Pendapatan Bulanan')
        ->assertSee('Rp 4.500.000');
});

test('admin can access yearly report page', function () {
    $admin = User::factory()->create();

    Pemesanan::factory()->create([
        'nominal' => 7000000,
        'is_lunas' => StatusPelunasan::Lunas,
        'tanggal_survey' => now(),
    ]);

    $this->actingAs($admin)
        ->get('/admin/laporan?view_type=yearly')
        ->assertSuccessful()
        ->assertSee('Rekap Pendapatan Tahunan')
        ->assertSee('Rp 7.000.000');
});

test('admin can view profile edit page', function () {
    $admin = User::factory()->create([
        'name' => 'John Doe',
        'email' => 'john@ambius.com',
    ]);

    $this->actingAs($admin)
        ->get('/admin/profile')
        ->assertSuccessful()
        ->assertSee('John Doe')
        ->assertSee('john@ambius.com');
});

test('admin can update name and email successfully', function () {
    $admin = User::factory()->create([
        'name' => 'Old Name',
        'email' => 'old@ambius.com',
    ]);

    $response = $this->actingAs($admin)
        ->put('/admin/profile', [
            'name' => 'New Name',
            'email' => 'new@ambius.com',
        ]);

    $response->assertRedirect(route('admin.profile.edit'));
    $admin->refresh();
    expect($admin->name)->toBe('New Name');
    expect($admin->email)->toBe('new@ambius.com');
});

test('admin can update password successfully', function () {
    $admin = User::factory()->create([
        'password' => Hash::make('old-password'),
    ]);

    $response = $this->actingAs($admin)
        ->put('/admin/profile', [
            'name' => $admin->name,
            'email' => $admin->email,
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ]);

    $response->assertRedirect(route('admin.profile.edit'));
    $admin->refresh();
    expect(Hash::check('new-password-123', $admin->password))->toBeTrue();
});
