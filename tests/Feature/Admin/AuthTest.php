<?php

use App\Models\User;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;

uses(RefreshDatabase::class);

test('guest can see the login form', function () {
    $this->get('/login')
        ->assertSuccessful()
        ->assertSee('Login Portal Admin');
});

test('admin can log in with valid credentials', function () {
    $admin = User::factory()->create([
        'email' => 'admin@ambius.com',
        'password' => bcrypt('password'),
    ]);

    $response = $this->post('/login', [
        'email' => 'admin@ambius.com',
        'password' => 'password',
    ]);

    $this->assertAuthenticatedAs($admin);
    $response->assertRedirect(route('admin.dashboard'));
});

test('admin cannot log in with invalid credentials', function () {
    User::factory()->create([
        'email' => 'admin@ambius.com',
        'password' => bcrypt('password'),
    ]);

    $response = $this->post('/login', [
        'email' => 'admin@ambius.com',
        'password' => 'wrong-password',
    ]);

    $this->assertGuest();
    $response->assertSessionHasErrors(['email']);
});

test('authenticated admin can log out', function () {
    $admin = User::factory()->create();

    $response = $this->actingAs($admin)->post('/logout');

    $this->assertGuest();
    $response->assertRedirect(route('home'));
});

test('guest cannot access admin dashboard', function () {
    $this->get('/admin')
        ->assertRedirect('/login');
});

test('admin can log in with remember me option', function () {
    $admin = User::factory()->create([
        'email' => 'admin@ambius.com',
        'password' => bcrypt('password'),
    ]);

    $response = $this->post('/login', [
        'email' => 'admin@ambius.com',
        'password' => 'password',
        'remember' => '1',
    ]);

    $this->assertAuthenticatedAs($admin);
    expect($admin->fresh()->remember_token)->not->toBeNull();
});

test('token mismatch exception redirects back with error message', function () {
    $request = Request::create('/login', 'POST', ['_token' => 'invalid', 'email' => 'admin@ambius.com']);
    $session = session()->driver();
    $request->setLaravelSession($session);

    $exception = new TokenMismatchException('CSRF token mismatch.');
    $response = app()->make(ExceptionHandler::class)->render($request, $exception);

    expect($response->isRedirect())->toBeTrue();
    expect($session->get('error'))->toContain('kedaluwarsa');
});
