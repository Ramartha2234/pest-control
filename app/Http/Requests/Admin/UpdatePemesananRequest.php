<?php

namespace App\Http\Requests\Admin;

use App\Enums\StatusPelunasan;
use App\Enums\StatusPengerjaan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Validator;

class UpdatePemesananRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Autentikasi dihandle di routing middleware
    }

    public function rules(): array
    {
        return [
            'teknisi_id' => ['nullable', 'exists:pengguna,id_pengguna'],
            'status_pengerjaan' => ['required', new Enum(StatusPengerjaan::class)],
            'is_lunas' => ['required', new Enum(StatusPelunasan::class)],
            'tanggal_survey' => ['required', 'date'],
            'tanggal_pengerjaan' => ['nullable', 'date', 'after_or_equal:tanggal_survey'],
            'nominal' => ['required', 'numeric', 'min:10000'],
            'harga_final' => ['nullable', 'numeric', 'min:10000'],
            'tipe_area' => ['nullable', 'string', 'in:indoor,outdoor'],
            'paket_layanan' => ['nullable', 'string', 'max:255'],
            'alasan_cancel' => ['nullable', 'required_if:status_pengerjaan,cancel', 'string', 'max:255'],
        ];
    }

    /**
     * Post-validation business rule hooks.
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                $statusPengerjaan = $this->input('status_pengerjaan');
                $isLunas = $this->input('is_lunas') === StatusPelunasan::Lunas->value;

                // 2. Status Done can only be selected if payment is lunas
                if ($statusPengerjaan === StatusPengerjaan::Done->value && ! $isLunas) {
                    $validator->errors()->add(
                        'status_pengerjaan',
                        'Pekerjaan tidak dapat diubah ke status Selesai (Done) karena status pembayaran belum lunas.'
                    );
                }

                // 3. Status Ongoing requires tanggal_pengerjaan
                if ($statusPengerjaan === StatusPengerjaan::Ongoing->value && empty($this->input('tanggal_pengerjaan'))) {
                    $validator->errors()->add(
                        'tanggal_pengerjaan',
                        'Tanggal pengerjaan/treatment wajib diisi jika status pengerjaan adalah On-going.'
                    );
                }
            },
        ];
    }
}
