<?php

use App\Models\Pemesanan;
use App\Models\Teknisi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('admin can view list of technicians', function () {
    $admin = User::factory()->create();
    Teknisi::factory()->create(['nama' => 'Supriyadi']);

    $this->actingAs($admin)
        ->get('/admin/teknisi')
        ->assertSuccessful()
        ->assertSee('Supriyadi');
});

test('admin can access create technician page', function () {
    $admin = User::factory()->create();

    $this->actingAs($admin)
        ->get('/admin/teknisi/create')
        ->assertSuccessful()
        ->assertSee('Tambah Teknisi Baru');
});

test('admin can store new technician successfully', function () {
    $admin = User::factory()->create();

    $response = $this->actingAs($admin)
        ->post('/admin/teknisi', [
            'nama' => 'Deden Kurnia',
            'no_telp' => '08988887777',
            'is_active' => '1',
        ]);

    $response->assertRedirect(route('admin.teknisi.index'));
    expect(Teknisi::count())->toBe(1);

    $teknisi = Teknisi::first();
    expect($teknisi->nama)->toBe('Deden Kurnia');
    expect($teknisi->is_active)->toBeTrue();
});

test('admin can update technician data successfully', function () {
    $admin = User::factory()->create();
    $teknisi = Teknisi::factory()->create(['nama' => 'Lama']);

    $response = $this->actingAs($admin)
        ->put("/admin/teknisi/{$teknisi->id}", [
            'nama' => 'Baru',
            'no_telp' => '081211112222',
            'is_active' => '0',
        ]);

    $response->assertRedirect(route('admin.teknisi.index'));
    $teknisi->refresh();
    expect($teknisi->nama)->toBe('Baru');
    expect($teknisi->is_active)->toBeFalse();
});

test('admin can delete technician who has no orders', function () {
    $admin = User::factory()->create();
    $teknisi = Teknisi::factory()->create();

    $response = $this->actingAs($admin)
        ->delete("/admin/teknisi/{$teknisi->id}");

    $response->assertRedirect(route('admin.teknisi.index'));
    expect(Teknisi::count())->toBe(0);
});

test('admin cannot delete technician who has orders and status is updated to inactive', function () {
    $admin = User::factory()->create();
    $teknisi = Teknisi::factory()->create(['is_active' => true]);
    Pemesanan::factory()->create(['teknisi_id' => $teknisi->id]);

    $response = $this->actingAs($admin)
        ->delete("/admin/teknisi/{$teknisi->id}");

    $response->assertRedirect(route('admin.teknisi.index'));
    $teknisi->refresh();
    expect($teknisi->is_active)->toBeFalse();
    expect(Teknisi::count())->toBe(1);
});
