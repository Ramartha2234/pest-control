<?php

namespace App\Http\Requests\Admin;

use App\Models\Pengguna;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTeknisiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $teknisi = $this->route('teknisi');
        $teknisiId = $teknisi instanceof Pengguna ? $teknisi->id_pengguna : $teknisi;

        return [
            'nama' => ['required', 'string', 'max:255'],
            'email' => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('pengguna', 'email')->ignore($teknisiId, 'id_pengguna'),
            ],
            'no_telp' => ['required', 'string', 'max:50'],
            'password' => ['nullable', 'string', 'min:6'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nama.required' => 'Nama lengkap teknisi wajib diisi.',
            'no_telp.required' => 'Nomor telepon/WhatsApp wajib diisi.',
            'email.required' => 'Alamat email teknisi wajib diisi.',
            'email.email' => 'Format alamat email tidak valid.',
            'email.unique' => 'Alamat email ini sudah terdaftar untuk pengguna lain.',
            'password.min' => 'Password baru minimal terdiri dari 6 karakter.',
        ];
    }
}
