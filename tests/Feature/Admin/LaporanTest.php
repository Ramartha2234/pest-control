<?php

use App\Enums\StatusPelunasan;
use App\Models\Pemesanan;
use App\Models\Pengaturan;
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

test('admin can update company bank and whatsapp settings successfully', function () {
    $admin = User::factory()->create();

    $response = $this->actingAs($admin)
        ->put('/admin/profile', [
            'name' => $admin->name,
            'email' => $admin->email,
            'bank_name' => 'Bank Mandiri',
            'bank_account_number' => '987-654-3210',
            'bank_account_holder' => 'CV. Ambius Mandiri',
            'whatsapp_number' => '6289999999999',
        ]);

    $response->assertRedirect(route('admin.profile.edit'));
    expect(Pengaturan::get('bank_name'))->toBe('Bank Mandiri');
    expect(Pengaturan::get('bank_account_number'))->toBe('987-654-3210');
    expect(Pengaturan::get('bank_account_holder'))->toBe('CV. Ambius Mandiri');
    expect(Pengaturan::get('whatsapp_number'))->toBe('6289999999999');
});

test('admin can update report signature names in system settings and see them on laporan', function () {
    $admin = User::factory()->create();

    $response = $this->actingAs($admin)
        ->put('/admin/profile', [
            'name' => $admin->name,
            'email' => $admin->email,
            'nama_accounting' => 'Dewi Sartika, S.E.',
            'nama_direktur' => 'Hendra Kusuma, M.M.',
        ]);

    $response->assertRedirect(route('admin.profile.edit'));
    expect(Pengaturan::get('nama_accounting'))->toBe('Dewi Sartika, S.E.');
    expect(Pengaturan::get('nama_direktur'))->toBe('Hendra Kusuma, M.M.');

    // Cek pada halaman cetak laporan keuangan (PDF) bahwa nama pejabat penandatangan ditampilkan
    $this->actingAs($admin)
        ->get(route('admin.laporan.cetak'))
        ->assertSuccessful()
        ->assertSee('Dewi Sartika, S.E.')
        ->assertSee('Hendra Kusuma, M.M.')
        ->assertSee('Accounting')
        ->assertSee('Direktur');

    // Pastikan tanda tangan sudah dipindahkan dan tidak tampil lagi di layar web dashboard laporan
    $this->actingAs($admin)
        ->get(route('admin.laporan.index'))
        ->assertSuccessful()
        ->assertDontSee('Dewi Sartika, S.E.')
        ->assertDontSee('Hendra Kusuma, M.M.');
});

test('admin can access standalone a4 print view for report', function () {
    $admin = User::factory()->create();

    Pemesanan::factory()->create([
        'nominal' => 3500000,
        'is_lunas' => StatusPelunasan::Lunas,
        'tanggal_survey' => now(),
    ]);

    $this->actingAs($admin)
        ->get(route('admin.laporan.cetak', ['view_type' => 'monthly']))
        ->assertSuccessful()
        ->assertSee('LAPORAN PENDAPATAN KEUANGAN')
        ->assertSee('Halaman 1')
        ->assertSee('CV. AMBIUS INDONESIA')
        ->assertSee('Rp 3.500.000');
});
