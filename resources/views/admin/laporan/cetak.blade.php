<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan_Pendapatan_{{ $selectedYear }}_{{ $selectedMonth }}_CV_Ambius</title>
    <style>
        /* Pengaturan Halaman Cetak Standar A4 */
        @page {
            size: A4 portrait;
            margin: 0; /* Menghilangkan header & footer otomatis dari peramban (URL, tanggal, judul) */
        }

        * {
            box-sizing: border-box;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        body {
            margin: 0;
            padding: 0;
            font-family: Arial, Helvetica, sans-serif;
            color: #0f172a;
            background-color: #f1f5f9;
            font-size: 11px;
            line-height: 1.4;
        }

        /* Bilah Aksi di Layar Komputer (Tidak Ikut Tercetak) */
        .toolbar {
            position: sticky;
            top: 0;
            z-index: 100;
            background: #1e293b;
            color: #f8fafc;
            padding: 12px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        .toolbar-info {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 12px;
        }

        .toolbar-title {
            font-weight: bold;
            font-size: 13px;
        }

        .toolbar-tip {
            color: #94a3b8;
            font-size: 11px;
        }

        .toolbar-actions {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .btn-print {
            background-color: #059669;
            color: #ffffff;
            border: none;
            padding: 8px 18px;
            border-radius: 8px;
            font-weight: bold;
            font-size: 12px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: background-color 0.15s ease;
        }

        .btn-print:hover {
            background-color: #047857;
        }

        .btn-close {
            background-color: #475569;
            color: #ffffff;
            border: none;
            padding: 8px 14px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.15s ease;
        }

        .btn-close:hover {
            background-color: #334155;
        }

        /* Struktur Lembaran Kertas A4 */
        .a4-sheet {
            width: 210mm;
            height: 296mm; /* Presisi 1 halaman penuh A4 */
            margin: 24px auto;
            background: #ffffff;
            padding: 16mm 16mm 12mm 16mm;
            position: relative;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
        }

        .sheet-body {
            flex: 1;
        }

        /* Kop Surat Resmi */
        .kop-surat {
            text-align: center;
            margin-bottom: 12px;
        }

        .kop-brand {
            font-size: 18px;
            font-weight: 900;
            letter-spacing: 0.5px;
            color: #064e3b;
            margin: 0;
            text-transform: uppercase;
        }

        .kop-tagline {
            font-size: 10.5px;
            font-weight: bold;
            color: #047857;
            margin: 2px 0;
            letter-spacing: 0.3px;
            text-transform: uppercase;
        }

        .kop-address {
            font-size: 9.5px;
            color: #475569;
            margin: 2px 0 0 0;
        }

        .kop-line-thick {
            height: 2.5px;
            background-color: #064e3b;
            margin-top: 8px;
        }

        .kop-line-thin {
            height: 0.8px;
            background-color: #064e3b;
            margin-top: 2px;
            margin-bottom: 14px;
        }

        /* Lembar Lanjutan Header */
        .continued-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            border-bottom: 2px solid #064e3b;
            padding-bottom: 6px;
            margin-bottom: 14px;
        }

        .continued-title {
            font-size: 12px;
            font-weight: bold;
            color: #064e3b;
            text-transform: uppercase;
        }

        .continued-sub {
            font-size: 9.5px;
            color: #64748b;
        }

        .continued-badge {
            font-size: 9.5px;
            font-weight: bold;
            color: #475569;
            background-color: #f1f5f9;
            padding: 2px 8px;
            border-radius: 4px;
            border: 1px solid #cbd5e1;
        }

        /* Judul Dokumen & Metadata */
        .doc-title-block {
            text-align: center;
            margin-bottom: 12px;
        }

        .doc-title {
            font-size: 14px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #0f172a;
            margin: 0;
        }

        .doc-meta {
            font-size: 10px;
            color: #475569;
            margin: 4px 0 0 0;
        }

        /* Kotak Ringkasan Finansial */
        .summary-grid {
            display: flex;
            border: 1px solid #cbd5e1;
            background-color: #f8fafc;
            border-radius: 6px;
            margin-bottom: 14px;
        }

        .summary-col {
            flex: 1;
            padding: 8px 12px;
            border-right: 1px solid #cbd5e1;
        }

        .summary-col:last-child {
            border-right: none;
        }

        .summary-label {
            font-size: 8.5px;
            font-weight: bold;
            text-transform: uppercase;
            color: #64748b;
            display: block;
            margin-bottom: 2px;
        }

        .summary-val {
            font-size: 13px;
            font-weight: 900;
            color: #0f172a;
        }

        .summary-val.emerald {
            color: #047857;
        }

        .summary-detail {
            font-size: 9px;
            color: #475569;
            margin-top: 1px;
        }

        /* Tabel Data Laporan */
        .report-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9.5px;
            line-height: 1.35;
        }

        .report-table th {
            background-color: #f1f5f9;
            color: #334155;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 9px;
            letter-spacing: 0.3px;
            border: 1px solid #94a3b8;
            padding: 6px 6px;
            text-align: left;
        }

        .report-table td {
            border: 1px solid #cbd5e1;
            padding: 5px 6px;
            vertical-align: top;
            color: #1e293b;
        }

        .report-table tr:nth-child(even) td {
            background-color: #fbfcfe;
        }

        .total-row td {
            background-color: #f1f5f9 !important;
            border-top: 2px solid #334155 !important;
            border-bottom: 2px solid #334155 !important;
            padding: 6px 6px;
        }

        /* Lembar Tanda Tangan */
        .ttd-container {
            margin-top: 20px;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            page-break-inside: avoid;
            break-inside: avoid;
        }

        .ttd-col {
            width: 220px;
            text-align: center;
        }

        .ttd-action {
            font-size: 9.5px;
            color: #475569;
            margin: 0;
        }

        .ttd-role {
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
            color: #0f172a;
            margin: 2px 0 0 0;
            letter-spacing: 0.3px;
        }

        .ttd-space {
            height: 65px;
        }

        .ttd-name {
            font-size: 10.5px;
            font-weight: bold;
            color: #0f172a;
            margin: 0;
            border-bottom: 1px solid #0f172a;
            display: inline-block;
            padding-bottom: 1px;
            min-width: 170px;
        }

        .ttd-title {
            font-size: 9.5px;
            color: #64748b;
            margin: 3px 0 0 0;
        }

        /* Footer Halaman A4 */
        .sheet-footer {
            border-top: 1px solid #cbd5e1;
            padding-top: 6px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 9px;
            color: #64748b;
        }

        .footer-page-num {
            font-weight: bold;
            color: #334155;
            background: #f1f5f9;
            padding: 1px 8px;
            border-radius: 4px;
            border: 1px solid #cbd5e1;
        }

        /* Media Print Khusus Peramban */
        @media print {
            .no-print {
                display: none !important;
            }

            body {
                background: #ffffff !important;
                margin: 0 !important;
                padding: 0 !important;
            }

            .a4-sheet {
                box-shadow: none !important;
                margin: 0 !important;
                border: none !important;
                page-break-inside: avoid !important;
            }

            .a4-sheet:not(:last-child) {
                page-break-after: always !important;
                break-after: page !important;
            }

            .a4-sheet:last-child {
                page-break-after: auto !important;
                break-after: auto !important;
            }
        }
    </style>
</head>
<body>

    {{-- Bilah Toolbar Aksi (Hanya Tampil di Layar Monitor) --}}
    <div class="toolbar no-print">
        <div class="toolbar-info">
            <span class="toolbar-title">📄 Dokumen Cetak / Ekspor PDF A4</span>
            <span class="toolbar-tip">&bull;&nbsp; Header/footer URL browser dijamin bersih. Simpan dokumen dengan memilih tujuan <strong>"Save as PDF"</strong>.</span>
        </div>
        <div class="toolbar-actions">
            <button type="button" onclick="window.print()" class="btn-print">
                <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                <span>Cetak / Ekspor PDF</span>
            </button>
            <button type="button" onclick="window.close()" class="btn-close">
                Tutup
            </button>
        </div>
    </div>

    {{-- Perulangan Tiap Lembar Kertas A4 --}}
    @foreach ($chunks as $chunk)
        <div class="a4-sheet">
            <div class="sheet-body">
                @if ($chunk['has_header'])
                    {{-- 1. Kop Surat Resmi Perusahaan --}}
                    <div class="kop-surat">
                        <h1 class="kop-brand">CV. AMBIUS INDONESIA</h1>
                        <p class="kop-tagline">SOLUSI PROFESIONAL PENGENDALIAN HAMA &amp; RAYAP</p>
                        <p class="kop-address">
                            Layanan Termite Control, Pest Control, Rodent Control &amp; Fumigasi Terstandar<br>
                            Kontak / WhatsApp: <strong>{{ \App\Models\Pengaturan::get('whatsapp_number', config('pestcontrol.whatsapp_number', '081234567890')) }}</strong>
                            &bull; Rekening Operasional: {{ \App\Models\Pengaturan::get('bank_name', config('pestcontrol.bank_name', 'Bank BCA')) }} ({{ \App\Models\Pengaturan::get('bank_account_number', config('pestcontrol.bank_account_number', '-')) }}) a.n {{ \App\Models\Pengaturan::get('bank_account_holder', config('pestcontrol.bank_account_holder', 'CV. Ambius Indonesia')) }}
                        </p>
                        <div class="kop-line-thick"></div>
                        <div class="kop-line-thin"></div>
                    </div>

                    {{-- 2. Judul Dokumen & Periode --}}
                    <div class="doc-title-block">
                        <h2 class="doc-title">LAPORAN PENDAPATAN KEUANGAN</h2>
                        <p class="doc-meta">
                            Periode: <strong>{{ $viewType === 'yearly' ? 'Tahun ' . $selectedYear : 'Bulan ' . \Carbon\Carbon::createFromDate($selectedYear, (int)$selectedMonth, 1)->locale('id')->translatedFormat('F Y') }}</strong>
                            &nbsp;&bull;&nbsp;
                            Kategori: <strong>Pekerjaan Selesai (Done) &amp; Pembayaran Lunas (100%)</strong>
                        </p>
                    </div>

                    {{-- 3. Ringkasan Finansial Resmi --}}
                    <div class="summary-grid">
                        <div class="summary-col">
                            <span class="summary-label">Total Pendapatan Terverifikasi</span>
                            <span class="summary-val emerald">Rp {{ number_format($totalRevenue, 0, ',', '.') }}</span>
                            <div class="summary-detail">100% Dana Masuk Selesai</div>
                        </div>
                        <div class="summary-col">
                            <span class="summary-label">Jumlah Pekerjaan Selesai</span>
                            <span class="summary-val">{{ $totalOrders }} Pesanan</span>
                            <div class="summary-detail">Pengerjaan Selesai &amp; Lunas</div>
                        </div>
                        @if ($totalDp > 0)
                            <div class="summary-col">
                                <span class="summary-label">Rincian Penerimaan</span>
                                <div class="summary-detail" style="margin-top: 2px;">
                                    DP Bank (10%): <strong style="color: #0f172a;">Rp {{ number_format($totalDp, 0, ',', '.') }}</strong>
                                </div>
                                <div class="summary-detail">
                                    Pelunasan Lapangan: <strong style="color: #0f172a;">Rp {{ number_format($totalPelunasan, 0, ',', '.') }}</strong>
                                </div>
                            </div>
                        @endif
                    </div>
                @else
                    {{-- Header untuk Lembar Lanjutan --}}
                    <div class="continued-header">
                        <div>
                            <span class="continued-title">CV. AMBIUS INDONESIA &mdash; LAPORAN PENDAPATAN KEUANGAN</span>
                            <span class="continued-sub" style="display: block;">
                                Periode: {{ $viewType === 'yearly' ? 'Tahun ' . $selectedYear : 'Bulan ' . \Carbon\Carbon::createFromDate($selectedYear, (int)$selectedMonth, 1)->locale('id')->translatedFormat('F Y') }}
                            </span>
                        </div>
                        <div class="continued-badge">
                            Lembar Lanjutan
                        </div>
                    </div>
                @endif

                {{-- 4. Tabel Rincian Data Transaksi --}}
                <table class="report-table">
                    <thead>
                        <tr>
                            <th style="width: 26px; text-align: center;">No</th>
                            <th style="width: 95px;">Kode &amp; Selesai</th>
                            <th style="width: 140px;">Pelanggan</th>
                            <th style="width: 135px;">Layanan &amp; Properti</th>
                            <th style="width: 130px;">Teknisi &amp; Jadwal</th>
                            <th style="width: 110px; text-align: right;">Pendapatan (Rp)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($chunk['items'] as $item)
                            @php
                                $nominal = (float) ($item->harga_final ?? $item->harga_estimasi);
                            @endphp
                            <tr>
                                <td style="text-align: center; color: #64748b;">
                                    {{ $loop->iteration + ($chunk['offset'] ?? 0) }}
                                </td>
                                <td>
                                    <strong style="font-family: monospace; font-size: 10px;">{{ $item->kode_pemesanan }}</strong>
                                    <span style="display: block; font-size: 8.5px; color: #64748b; margin-top: 1px;">
                                        {{ $item->tgl_selesai ? \Carbon\Carbon::parse($item->tgl_selesai)->translatedFormat('d M Y') : '-' }}
                                    </span>
                                </td>
                                <td>
                                    <strong>{{ $item->pelanggan->nama }}</strong>
                                    <span style="display: block; font-size: 8.5px; color: #64748b; margin-top: 1px;">
                                        {{ $item->pelanggan->no_hp }}
                                    </span>
                                </td>
                                <td>
                                    <strong style="color: #1e293b;">{{ $item->tarifLayanan->jenis_layanan ?? '-' }}</strong>
                                    <span style="display: block; font-size: 8.5px; color: #64748b; margin-top: 1px;">
                                        {{ $item->jenis_properti }} &bull; {{ $item->tipe_area ?? 'Indoor' }}
                                    </span>
                                </td>
                                <td>
                                    <strong>{{ $item->jadwalPenugasan?->teknisi?->nama ?? 'N/A' }}</strong>
                                    <span style="display: block; font-size: 8.5px; color: #64748b; margin-top: 1px;">
                                        Treatment: {{ $item->jadwalPenugasan?->tgl_pengerjaan ? \Carbon\Carbon::parse($item->jadwalPenugasan->tgl_pengerjaan)->format('d/m/Y') : '-' }}
                                    </span>
                                </td>
                                <td style="text-align: right; font-weight: bold; color: #047857;">
                                    Rp {{ number_format($nominal, 0, ',', '.') }}
                                    @if ($item->wajib_dp && $item->status_dp === 'Lunas')
                                        <span style="display: block; font-size: 8px; font-weight: normal; color: #64748b; margin-top: 1px;">
                                            DP: Rp {{ number_format($item->nominal_dp, 0, ',', '.') }}
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" style="text-align: center; padding: 24px; color: #94a3b8;">
                                    Belum ada transaksi pengerjaan selesai dan lunas untuk periode ini.
                                </td>
                            </tr>
                        @endforelse

                        @if ($chunk['has_ttd'])
                            <tr class="total-row">
                                <td colspan="5" style="text-align: right; font-weight: bold; color: #1e293b;">
                                    TOTAL PENDAPATAN BERSIH TERVERIFIKASI:
                                </td>
                                <td style="text-align: right; font-weight: 900; color: #047857; font-size: 11px;">
                                    Rp {{ number_format($totalRevenue, 0, ',', '.') }}
                                </td>
                            </tr>
                        @endif
                    </tbody>
                </table>

                {{-- 5. Lembar Pengesahan Tanda Tangan --}}
                @if ($chunk['has_ttd'])
                    <div class="ttd-container">
                        {{-- Pojok Kiri Bawah: Accounting --}}
                        <div class="ttd-col">
                            <p class="ttd-action">Dibuat &amp; Diverifikasi,</p>
                            <p class="ttd-role">Accounting</p>
                            <div class="ttd-space"></div>
                            <p class="ttd-name">{{ $namaAccounting }}</p>
                            <p class="ttd-title">Bagian Keuangan</p>
                        </div>

                        {{-- Pojok Kanan Bawah: Direktur --}}
                        <div class="ttd-col">
                            <p class="ttd-action">{{ \Carbon\Carbon::now()->locale('id')->translatedFormat('d F Y') }}</p>
                            <p class="ttd-role">Direktur</p>
                            <div class="ttd-space"></div>
                            <p class="ttd-name">{{ $namaDirektur }}</p>
                            <p class="ttd-title">Direktur Utama</p>
                        </div>
                    </div>
                @endif
            </div>

            {{-- 6. Footer Halaman Presisi A4 --}}
            <div class="sheet-footer">
                <span>CV. Ambius Indonesia &bull; Dokumen Resmi Laporan Keuangan</span>
                <span class="footer-page-num">Halaman {{ $loop->iteration }}</span>
            </div>
        </div>
    @endforeach

</body>
</html>

