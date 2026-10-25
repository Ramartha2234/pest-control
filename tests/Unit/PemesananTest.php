<?php

use App\Enums\StatusPelunasan;
use App\Models\Pemesanan;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('kode_unik is automatically generated on creation', function () {
    $pemesanan = Pemesanan::factory()->create([
        'kode_unik' => null,
    ]);

    expect($pemesanan->kode_unik)->not->toBeNull();
    expect($pemesanan->kode_unik)->toStartWith('AMB-');
    expect(strlen($pemesanan->kode_unik))->toBe(10); // AMB-XXXXXX
});

test('canBeMarkedDone returns true only if is_lunas is lunas', function () {
    $unpaidOrder = Pemesanan::factory()->make([
        'is_lunas' => StatusPelunasan::BelumLunas,
    ]);
    expect($unpaidOrder->canBeMarkedDone())->toBeFalse();

    $rejectedOrder = Pemesanan::factory()->make([
        'is_lunas' => StatusPelunasan::Ditolak,
    ]);
    expect($rejectedOrder->canBeMarkedDone())->toBeFalse();

    $paidOrder = Pemesanan::factory()->make([
        'is_lunas' => StatusPelunasan::Lunas,
    ]);
    expect($paidOrder->canBeMarkedDone())->toBeTrue();
});
