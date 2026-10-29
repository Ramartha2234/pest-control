<?php

return [
    'whatsapp_number' => env('WHATSAPP_NUMBER', '6281234567890'),
    'company_name' => 'CV. Ambius Indonesia',
    'company_tagline' => 'Solusi Profesional Pengendalian Hama',

    // Pengaturan Down Payment (DP) untuk pesanan bernominal tinggi (>= 10 juta)
    'dp_threshold' => (float) env('DP_THRESHOLD', 10000000), // Nominal minimal wajib DP (Rp 10.000.000)
    'dp_percentage' => (float) env('DP_PERCENTAGE', 10), // Persentase DP (10%)

    // Informasi Rekening Perusahaan untuk Transfer Manual
    'bank_name' => env('COMPANY_BANK_NAME', 'Bank Central Asia (BCA)'),
    'bank_account_number' => env('COMPANY_BANK_ACCOUNT_NUMBER', '123-456-7890'),
    'bank_account_holder' => env('COMPANY_BANK_ACCOUNT_HOLDER', 'CV. Ambius Indonesia'),

    // Pejabat Penandatangan Laporan (Cetak / Ekspor PDF)
    'nama_accounting' => env('NAMA_ACCOUNTING', 'Siti Rahmawati, S.Ak.'),
    'nama_direktur' => env('NAMA_DIREKTUR', 'Budi Santoso, S.T.'),
];
