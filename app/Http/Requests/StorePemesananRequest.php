<?php

namespace App\Http\Requests;

use App\Models\TarifLayanan;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePemesananRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Prepare inputs for validation to support both new and legacy field names.
     */
    protected function prepareForValidation(): void
    {
        $merge = [];

        if ($this->has('nama_client') && ! $this->has('nama')) {
            $merge['nama'] = $this->input('nama_client');
        }
        if ($this->has('no_telp_client') && ! $this->has('no_hp')) {
            $merge['no_hp'] = $this->input('no_telp_client');
        }
        if ($this->has('no_hp')) {
            // Bersihkan spasi, tanda minus, dan titik pada nomor telepon
            $merge['no_hp'] = preg_replace('/[\s\-\.]/', '', (string) $this->input('no_hp'));
        }
        if ($this->has('alamat_pengerjaan') && ! $this->has('alamat_lokasi')) {
            $merge['alamat_lokasi'] = $this->input('alamat_pengerjaan');
        }
        if ($this->has('tanggal_survey') && ! $this->has('tgl_survei_diminta')) {
            $merge['tgl_survei_diminta'] = $this->input('tanggal_survey');
        }
        if (! $this->has('jam_survei_diminta')) {
            $merge['jam_survei_diminta'] = '09:00';
        }
        if (! $this->has('jenis_properti')) {
            $merge['jenis_properti'] = 'Rumah Tinggal';
        }
        if (! $this->has('keluhan')) {
            $merge['keluhan'] = $this->input('paket_layanan', 'Permintaan survei dan penanganan hama.');
        }

        // Jika id_tarif belum disertakan (misalnya form lama), ambil tarif pertama yang aktif
        if (! $this->has('id_tarif')) {
            $defaultTarif = TarifLayanan::aktif()->first();
            if ($defaultTarif) {
                $merge['id_tarif'] = $defaultTarif->id_tarif;
            }
        }

        if (! $this->has('tipe_area') || empty($this->input('tipe_area'))) {
            $merge['tipe_area'] = 'Indoor';
        }

        if (! empty($merge)) {
            $this->merge($merge);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'nama' => ['required', 'string', 'max:255'],
            'no_hp' => [
                'required',
                'string',
                'regex:/^(?:\+62|62|0)8[1-9][0-9]{6,11}$/',
            ],
            'email' => ['required', 'email', 'max:255'],
            'nama_usaha' => ['nullable', 'string', 'max:255'],
            'jenis_properti' => ['required', 'string', 'max:100'],
            'alamat_lokasi' => ['required', 'string'],
            'keluhan' => ['required', 'string'],
            'id_tarif' => [
                'required',
                Rule::exists('tarif_layanan', 'id_tarif')->where(function ($query) {
                    $query->where('status_aktif', true);
                }),
            ],
            'ukuran_area' => ['nullable', 'numeric', 'min:1'],
            'tipe_area' => ['required', 'string', 'in:Indoor,Outdoor'],
            'tgl_survei_diminta' => ['required', 'date', 'after_or_equal:today'],
            'jam_survei_diminta' => ['required', 'date_format:H:i'],
        ];
    }

    /**
     * Custom Indonesian validation error messages.
     */
    public function messages(): array
    {
        return [
            'nama.required' => 'Nama lengkap wajib diisi.',
            'no_hp.required' => 'Nomor WhatsApp / HP wajib diisi.',
            'no_hp.regex' => 'Format nomor HP tidak valid. Masukkan nomor HP Indonesia yang aktif (contoh: 08123456789 atau +628123456789).',
            'email.required' => 'Alamat email wajib diisi.',
            'email.email' => 'Format alamat email tidak valid.',
            'jenis_properti.required' => 'Pilih jenis properti bangunan Anda.',
            'tipe_area.required' => 'Pilih tipe area penanganan (Indoor atau Outdoor).',
            'tipe_area.in' => 'Pilihan tipe area harus Indoor atau Outdoor.',
            'alamat_lokasi.required' => 'Alamat lokasi pengerjaan wajib diisi lengkap.',
            'keluhan.required' => 'Ceritakan keluhan atau kendala hama yang Anda hadapi.',
            'id_tarif.required' => 'Silakan pilih jenis layanan pest control yang diinginkan.',
            'id_tarif.exists' => 'Layanan yang dipilih tidak tersedia atau tidak aktif.',
            'ukuran_area.numeric' => 'Estimasi ukuran luas area harus berupa angka.',
            'tgl_survei_diminta.required' => 'Pilih tanggal survei yang Anda inginkan.',
            'tgl_survei_diminta.after_or_equal' => 'Tanggal survei tidak boleh di masa lampau.',
            'jam_survei_diminta.required' => 'Pilih jam survei yang diinginkan.',
            'jam_survei_diminta.date_format' => 'Format jam survei harus jam:menit (HH:mm).',
        ];
    }
}
