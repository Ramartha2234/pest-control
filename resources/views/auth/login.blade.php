@extends('layouts.app')

@section('title', 'Login Admin - CV. Ambius')

@section('content')
<div class="py-20 bg-gray-50 flex items-center justify-center min-h-[70vh]">
    <div class="mx-auto w-full max-w-md px-4">
        {{-- Card --}}
        <div class="relative rounded-2xl border border-gray-100 bg-white p-8 shadow-xl">
            <div class="absolute inset-x-0 top-0 h-1.5 bg-gradient-to-r from-primary-400 to-primary-600 rounded-t-2xl"></div>

            {{-- Header --}}
            <div class="text-center mb-8">
                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-xl bg-primary-100 text-primary-700 mb-4 shadow-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg>
                </div>
                <h1 class="text-xl font-extrabold text-gray-900 tracking-tight">Login Portal Admin</h1>
                <p class="text-xs text-gray-500 mt-1">Khusus bagi pengelola / owner CV. Ambius</p>
            </div>

            {{-- Form --}}
            <form action="{{ route('login.post') }}" method="POST" class="space-y-5">
                @csrf

                <div>
                    <label for="email" class="block text-sm font-semibold text-gray-700">Alamat Email</label>
                    <input type="email" name="email" id="email" value="{{ old('email') }}" required autocomplete="email" autofocus
                           class="mt-1 block w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-900 shadow-sm focus:border-primary-500 focus:ring-primary-500 focus:outline-none @error('email') border-red-300 ring-1 ring-red-300 @enderror"
                           placeholder="admin@ambius.com">
                    @error('email')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <div class="flex items-center justify-between">
                        <label for="password" class="block text-sm font-semibold text-gray-700">Password</label>
                    </div>
                    <div class="relative mt-1">
                        <input type="password" name="password" id="password" required autocomplete="current-password"
                               class="block w-full rounded-lg border border-gray-300 pl-4 pr-12 py-2.5 text-sm text-gray-900 shadow-sm focus:border-primary-500 focus:ring-primary-500 focus:outline-none @error('password') border-red-300 ring-1 ring-red-300 @enderror"
                               placeholder="••••••••">
                        <button type="button" id="toggle-password" class="absolute inset-y-0 right-0 z-10 flex w-12 items-center justify-center text-gray-400 hover:text-primary-600 focus:outline-none bg-transparent border-0 p-0">
                            {{-- Icon Mata (Show) --}}
                            <svg xmlns="http://www.w3.org/2000/svg" id="eye-icon-show" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                            {{-- Icon Mata Coret (Hide) --}}
                            <svg xmlns="http://www.w3.org/2000/svg" id="eye-icon-hide" class="h-5 w-5 hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.542-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" />
                            </svg>
                        </button>
                    </div>
                    @error('password')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-center">
                    <input type="checkbox" name="remember" id="remember" value="1" {{ old('remember') ? 'checked' : '' }} class="h-4 w-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                    <label for="remember" class="ml-2 block text-sm text-gray-600">Ingat Saya</label>
                </div>

                <button type="submit" class="w-full rounded-xl bg-primary-500 px-5 py-3 text-sm font-bold text-white shadow-lg transition hover:bg-primary-600 hover:shadow-xl focus:outline-none">
                    Masuk ke Dashboard
                </button>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    window.addEventListener('pageshow', function (event) {
        if (event.persisted) {
            window.location.reload();
        }
    });

    document.addEventListener('DOMContentLoaded', function () {
        const passwordInput = document.getElementById('password');
        const toggleBtn = document.getElementById('toggle-password');
        const showIcon = document.getElementById('eye-icon-show');
        const hideIcon = document.getElementById('eye-icon-hide');

        toggleBtn.addEventListener('click', function () {
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                showIcon.classList.add('hidden');
                hideIcon.classList.remove('hidden');
            } else {
                passwordInput.type = 'password';
                showIcon.classList.remove('hidden');
                hideIcon.classList.add('hidden');
            }
        });
    });
</script>
@endpush
