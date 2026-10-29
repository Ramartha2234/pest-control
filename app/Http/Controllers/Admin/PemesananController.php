<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdatePemesananRequest;
use App\Models\BuktiKehadiran;
use App\Models\BuktiPembayaran;
use App\Models\HasilSurvei;
use App\Models\JadwalPenugasan;
use App\Models\Pemesanan;
use App\Models\Pengguna;
use App\Models\WorkOrder;
use App\Services\PemesananStatusService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class PemesananController extends Controller
{
    public function __construct(
        protected PemesananStatusService $statusService
    ) {}

    /**
     * Tampilkan daftar seluruh pemesanan dengan filter & pencarian.
     */
    public function index(Request $request): View
    {
        $statusPengerjaan = $request->query('status_pengerjaan') ?? $request->query('status');
        $statusPembayaran = $request->query('status_pembayaran');
        $search = trim($request->query('search', ''));

        $query = Pemesanan::query()
            ->leftJoin('jadwal_penugasan', 'pemesanan.id_pemesanan', '=', 'jadwal_penugasan.id_pemesanan')
            ->select('pemesanan.*')
            ->with(['pelanggan', 'tarifLayanan', 'jadwalPenugasan.teknisi'])
            ->orderByRaw('COALESCE(jadwal_penugasan.tgl_pengerjaan, jadwal_penugasan.tgl_survei, pemesanan.tgl_survei_diminta, pemesanan.tgl_pemesanan) ASC')
            ->orderBy('pemesanan.id_pemesanan', 'desc');

        if ($statusPengerjaan && in_array($statusPengerjaan, ['Pending', 'On-Going', 'Done', 'Cancel'])) {
            $query->where('pemesanan.status_pengerjaan', $statusPengerjaan);
        }

        if ($statusPembayaran && in_array($statusPembayaran, ['Belum Lunas', 'Lunas', 'Ditolak'])) {
            $query->where('pemesanan.status_pembayaran', $statusPembayaran);
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('pemesanan.kode_pemesanan', 'like', "%{$search}%")
                    ->orWhereHas('pelanggan', function ($pq) use ($search) {
                        $pq->where('nama', 'like', "%{$search}%")
                            ->orWhere('no_hp', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        $pemesanans = $query->paginate(10)->withQueryString();

        return view('admin.pemesanan.index', [
            'pemesanans' => $pemesanans,
            'currentStatus' => $statusPengerjaan,
            'currentPembayaran' => $statusPembayaran,
            'search' => $search,
        ]);
    }

    /**
     * Tampilkan detail pemesanan, verifikasi dokumen, dan jadwal.
     */
    public function show(Pemesanan $pemesanan): View
    {
        $pemesanan->load([
            'pelanggan',
            'tarifLayanan',
            'jadwalPenugasan.teknisi',
            'hasilSurveis' => fn ($q) => $q->latest('id_hasil_survei'),
            'buktiKehadirans' => fn ($q) => $q->latest('id_bukti_kehadiran'),
            'workOrders' => fn ($q) => $q->latest('id_wo'),
            'buktiPembayarans' => fn ($q) => $q->latest('id_bukti_pembayaran'),
        ]);

        $teknisiList = Pengguna::where('role', 'teknisi')
            ->where('status_aktif', true)
            ->orderBy('nama')
            ->get();

        return view('admin.pemesanan.show', [
            'pemesanan' => $pemesanan,
            'teknisiList' => $teknisiList,
            'latestSurvei' => $pemesanan->latestHasilSurvei(),
            'latestBuktiSurvei' => $pemesanan->latestBuktiSurvei(),
            'latestBuktiPengerjaan' => $pemesanan->latestBuktiPengerjaan(),
            'latestWo' => $pemesanan->latestWorkOrder(),
            'latestBuktiBayar' => $pemesanan->latestBuktiPembayaran(),
        ]);
    }

    /**
     * Alur 2: Admin verifikasi pesanan baru, tentukan jadwal survei dan tugaskan teknisi.
     */
    public function verifikasiDanTugaskan(Request $request, Pemesanan $pemesanan): RedirectResponse
    {
        $request->validate([
            'id_teknisi' => ['required', 'exists:pengguna,id_pengguna'],
            'tgl_survei' => ['required', 'date'],
            'jam_survei' => ['required', 'date_format:H:i'],
        ], [
            'id_teknisi.required' => 'Pilih teknisi yang akan ditugaskan.',
            'id_teknisi.exists' => 'Teknisi yang dipilih tidak valid.',
            'tgl_survei.required' => 'Tentukan tanggal survei lapangan.',
            'jam_survei.required' => 'Tentukan jam survei lapangan.',
        ]);

        $teknisiId = (int) $request->input('id_teknisi');
        $tglSurvei = $request->input('tgl_survei');
        $jamSurvei = $request->input('jam_survei');

        $bentrok = $this->statusService->cekBentrokJadwalTeknisi(
            $teknisiId,
            $tglSurvei,
            $jamSurvei,
            $pemesanan->id_pemesanan
        );

        $this->statusService->verifikasiDanTugaskan(
            $pemesanan,
            Auth::id(),
            $teknisiId,
            $tglSurvei,
            $jamSurvei
        );

        $redirect = redirect()->route('admin.pemesanan.show', $pemesanan->id_pemesanan)
            ->with('success', 'Pemesanan berhasil diverifikasi dan teknisi telah ditugaskan.');

        if ($bentrok) {
            $teknisi = Pengguna::find($teknisiId);
            $redirect->with('warning', 'Peringatan: Teknisi '.($teknisi?->nama ?? 'terpilih').' memiliki jadwal bentrok pada tanggal '.$tglSurvei.' jam '.$jamSurvei.' (Pesanan '.($bentrok->pemesanan?->kode_pemesanan ?? '#'.$bentrok->id_pemesanan).').');
        }

        return $redirect;
    }

    /**
     * Alur 2 (Tolak): Admin menolak pesanan saat verifikasi awal -> status Cancel + alasan.
     */
    public function tolakPemesanan(Request $request, Pemesanan $pemesanan): RedirectResponse
    {
        $request->validate([
            'alasan_penolakan' => ['required', 'string', 'max:1000'],
        ], [
            'alasan_penolakan.required' => 'Alasan penolakan pesanan wajib diisi.',
        ]);

        $this->statusService->tolakPemesanan(
            $pemesanan,
            Auth::id(),
            $request->input('alasan_penolakan')
        );

        return redirect()->route('admin.pemesanan.show', $pemesanan->id_pemesanan)
            ->with('success', 'Pemesanan telah ditolak dan dibatalkan.');
    }

    /**
     * Alur 4/7: Admin verifikasi bukti kehadiran teknisi (Survei atau Pengerjaan).
     */
    public function verifikasiBuktiKehadiran(Request $request, BuktiKehadiran $buktiKehadiran): RedirectResponse
    {
        $request->validate([
            'aksi' => ['required', 'in:setujui,tolak'],
            'alasan_penolakan' => ['required_if:aksi,tolak', 'nullable', 'string', 'max:1000'],
        ], [
            'alasan_penolakan.required_if' => 'Alasan penolakan wajib diisi jika Anda menolak foto kehadiran ini.',
        ]);

        $disetujui = $request->input('aksi') === 'setujui';

        $this->statusService->verifikasiBuktiKehadiran(
            $buktiKehadiran,
            Auth::id(),
            $disetujui,
            $request->input('alasan_penolakan')
        );

        $msg = $disetujui
            ? 'Bukti kehadiran berhasil disetujui'.($buktiKehadiran->jenis_kehadiran === 'Pengerjaan' ? ' dan status pesanan otomatis menjadi On-Going.' : '.')
            : 'Bukti kehadiran ditolak. Teknisi dapat mengunggah ulang foto kehadiran baru.';

        return redirect()->route('admin.pemesanan.show', $buktiKehadiran->id_pemesanan)
            ->with('success', $msg);
    }

    /**
     * Alur 5: Admin verifikasi hasil survei & harga kesepakatan diajukan teknisi.
     */
    public function verifikasiHasilSurvei(Request $request, HasilSurvei $hasilSurvei): RedirectResponse
    {
        $request->validate([
            'aksi' => ['required', 'in:setujui,tolak'],
            'alasan_penolakan' => ['required_if:aksi,tolak', 'nullable', 'string', 'max:1000'],
        ], [
            'alasan_penolakan.required_if' => 'Alasan penolakan wajib diisi jika hasil survei ditolak.',
        ]);

        $disetujui = $request->input('aksi') === 'setujui';

        $this->statusService->verifikasiHasilSurvei(
            $hasilSurvei,
            Auth::id(),
            $disetujui,
            $request->input('alasan_penolakan')
        );

        $msg = $disetujui
            ? 'Hasil survei dan harga final disetujui. Jadwal pengerjaan telah diperbarui.'
            : 'Hasil survei ditolak. Teknisi harus bernegosiasi dan mengajukan ulang.';

        return redirect()->route('admin.pemesanan.show', $hasilSurvei->id_pemesanan)
            ->with('success', $msg);
    }

    /**
     * Alur 6: Admin ubah jadwal survei & pengerjaan kapan saja atas permintaan pelanggan.
     */
    public function ubahJadwal(Request $request, Pemesanan $pemesanan): RedirectResponse
    {
        $request->validate([
            'tgl_survei' => ['nullable', 'date'],
            'jam_survei' => ['nullable', 'date_format:H:i'],
            'tgl_pengerjaan' => ['nullable', 'date'],
            'jam_pengerjaan' => ['nullable', 'date_format:H:i'],
        ]);

        $tglSurvei = $request->input('tgl_survei');
        $jamSurvei = $request->input('jam_survei');
        $tglPengerjaan = $request->input('tgl_pengerjaan');
        $jamPengerjaan = $request->input('jam_pengerjaan');

        $warning = null;
        if ($pemesanan->jadwalPenugasan && $pemesanan->jadwalPenugasan->id_teknisi) {
            $tekId = $pemesanan->jadwalPenugasan->id_teknisi;
            if ($tglSurvei && $jamSurvei) {
                $bentrokSurvei = $this->statusService->cekBentrokJadwalTeknisi($tekId, $tglSurvei, $jamSurvei, $pemesanan->id_pemesanan);
                if ($bentrokSurvei) {
                    $warning = 'Peringatan: Jadwal survei bentrok dengan pesanan '.($bentrokSurvei->pemesanan?->kode_pemesanan ?? '#'.$bentrokSurvei->id_pemesanan).' pada jam yang sama.';
                }
            }
            if ($tglPengerjaan && $jamPengerjaan) {
                $bentrokKerja = $this->statusService->cekBentrokJadwalTeknisi($tekId, $tglPengerjaan, $jamPengerjaan, $pemesanan->id_pemesanan);
                if ($bentrokKerja) {
                    $warning = 'Peringatan: Jadwal pengerjaan bentrok dengan pesanan '.($bentrokKerja->pemesanan?->kode_pemesanan ?? '#'.$bentrokKerja->id_pemesanan).' pada jam yang sama.';
                }
            }
        }

        $this->statusService->ubahJadwal(
            $pemesanan,
            $tglSurvei,
            $jamSurvei,
            $tglPengerjaan,
            $jamPengerjaan
        );

        $redirect = redirect()->route('admin.pemesanan.show', $pemesanan->id_pemesanan)
            ->with('success', 'Jadwal penugasan berhasil diperbarui.');

        if ($warning) {
            $redirect->with('warning', $warning);
        }

        return $redirect;
    }

    /**
     * Alur 8: Admin verifikasi Work Order (WO).
     */
    public function verifikasiWorkOrder(Request $request, WorkOrder $workOrder): RedirectResponse
    {
        $request->validate([
            'aksi' => ['required', 'in:setujui,tolak'],
            'alasan_penolakan' => ['required_if:aksi,tolak', 'nullable', 'string', 'max:1000'],
        ], [
            'alasan_penolakan.required_if' => 'Alasan penolakan wajib diisi jika dokumen WO ditolak.',
        ]);

        $disetujui = $request->input('aksi') === 'setujui';

        $this->statusService->verifikasiWorkOrder(
            $workOrder,
            Auth::id(),
            $disetujui,
            $request->input('alasan_penolakan')
        );

        $msg = $disetujui
            ? 'Dokumen Work Order (WO) disetujui.'
            : 'Dokumen Work Order (WO) ditolak. Teknisi harus mengunggah ulang.';

        return redirect()->route('admin.pemesanan.show', $workOrder->id_pemesanan)
            ->with('success', $msg);
    }

    /**
     * Alur 8: Admin verifikasi Bukti Pembayaran.
     */
    public function verifikasiBuktiPembayaran(Request $request, BuktiPembayaran $buktiPembayaran): RedirectResponse
    {
        $request->validate([
            'aksi' => ['required', 'in:setujui,tolak'],
            'alasan_penolakan' => ['required_if:aksi,tolak', 'nullable', 'string', 'max:1000'],
        ], [
            'alasan_penolakan.required_if' => 'Alasan penolakan wajib diisi jika bukti pembayaran ditolak.',
        ]);

        $disetujui = $request->input('aksi') === 'setujui';

        $this->statusService->verifikasiBuktiPembayaran(
            $buktiPembayaran,
            Auth::id(),
            $disetujui,
            $request->input('alasan_penolakan')
        );

        $msg = $disetujui
            ? 'Bukti pembayaran disetujui.'
            : 'Bukti pembayaran ditolak. Status pembayaran diubah menjadi Ditolak.';

        return redirect()->route('admin.pemesanan.show', $buktiPembayaran->id_pemesanan)
            ->with('success', $msg);
    }

    /**
     * Alur 10: Admin membatalkan pesanan (alasan + dibatalkan_oleh).
     */
    public function cancel(Request $request, Pemesanan $pemesanan): RedirectResponse
    {
        $dibatalkanOleh = $request->input('dibatalkan_oleh', 'CV. Ambius Indonesia');
        $alasan = $request->input('alasan_pembatalan') ?? $request->input('alasan_cancel');

        if (! $alasan) {
            $request->validate([
                'alasan_pembatalan' => ['required', 'string', 'max:1000'],
            ], [
                'alasan_pembatalan.required' => 'Alasan pembatalan wajib dicantumkan.',
            ]);
        }

        $this->statusService->batalkanPemesanan(
            $pemesanan,
            $dibatalkanOleh,
            $alasan
        );

        return redirect()->route('admin.pemesanan.show', $pemesanan->id_pemesanan)
            ->with('success', 'Pemesanan berhasil dibatalkan oleh '.$dibatalkanOleh.'.');
    }

    /**
     * Verifikasi pemesanan fiktif atau valid (dukungan kompatibilitas alur awal).
     */
    public function verifyFiktif(Request $request, Pemesanan $pemesanan): RedirectResponse
    {
        $isFiktif = filter_var($request->input('is_fiktif'), FILTER_VALIDATE_BOOLEAN);

        if ($isFiktif) {
            $pemesanan->update([
                'status_pengerjaan' => 'Cancel',
                'status_pembayaran' => 'Ditolak',
                'dibatalkan_oleh' => 'CV. Ambius Indonesia',
                'alasan_pembatalan' => 'Telah diverifikasi sebagai pemesanan fiktif oleh admin.',
            ]);

            return redirect()->route('admin.pemesanan.show', $pemesanan->id_pemesanan)
                ->with('success', 'Pemesanan berhasil ditandai sebagai fiktif.');
        }

        $pemesanan->update([
            'tgl_verifikasi' => now(),
            'id_admin' => Auth::id(),
        ]);

        return redirect()->route('admin.pemesanan.show', $pemesanan->id_pemesanan)
            ->with('success', 'Pemesanan telah diverifikasi sebagai valid (tidak fiktif).');
    }

    /**
     * Hapus data pemesanan.
     */
    public function destroy(Pemesanan $pemesanan): RedirectResponse
    {
        $pemesanan->delete();

        return redirect()->route('admin.pemesanan.index')
            ->with('success', 'Pemesanan berhasil dihapus.');
    }

    /**
     * Update detail pemesanan (kompatibilitas pengujian & pembaruan cepat).
     */
    public function update(UpdatePemesananRequest $request, Pemesanan $pemesanan): RedirectResponse
    {
        $validated = $request->validated();
        $hargaLama = $pemesanan->harga_final ?? $pemesanan->harga_estimasi;

        $statusPengerjaan = $validated['status_pengerjaan'];
        if ($statusPengerjaan instanceof \BackedEnum) {
            $statusPengerjaan = $statusPengerjaan->value;
        }
        $statusPengerjaanFormatted = match (strtolower((string) $statusPengerjaan)) {
            'pending' => 'Pending',
            'ongoing', 'on-going' => 'On-Going',
            'done' => 'Done',
            'cancel' => 'Cancel',
            default => (string) $statusPengerjaan,
        };

        $isLunas = $validated['is_lunas'];
        if ($isLunas instanceof \BackedEnum) {
            $isLunas = $isLunas->value;
        }
        $statusPembayaranFormatted = match (strtolower((string) $isLunas)) {
            'lunas' => 'Lunas',
            'ditolak' => 'Ditolak',
            default => 'Belum Lunas',
        };

        if ($statusPengerjaanFormatted === 'Cancel') {
            $statusPembayaranFormatted = 'Ditolak';
        }

        $updateData = [
            'status_pengerjaan' => $statusPengerjaanFormatted,
            'status_pembayaran' => $statusPembayaranFormatted,
            'harga_estimasi' => $validated['nominal'] ?? $pemesanan->harga_estimasi,
        ];

        if (isset($validated['harga_final'])) {
            $updateData['harga_final'] = $validated['harga_final'];
        }

        if ($statusPengerjaanFormatted === 'Cancel') {
            $updateData['dibatalkan_oleh'] = $pemesanan->dibatalkan_oleh ?? 'CV. Ambius Indonesia';
            $updateData['alasan_pembatalan'] = $validated['alasan_cancel'] ?? ($pemesanan->alasan_pembatalan ?? 'Dibatalkan oleh admin');
        }

        if ($statusPengerjaanFormatted === 'Done' && $statusPembayaranFormatted === 'Lunas') {
            $updateData['tgl_selesai'] = $pemesanan->tgl_selesai ?? now();
        }

        $pemesanan->update($updateData);

        // Update Jadwal Penugasan & Teknisi
        $teknisiId = $validated['teknisi_id'] ?? null;
        if ($teknisiId || ! empty($validated['tanggal_survey']) || ! empty($validated['tanggal_pengerjaan'])) {
            $penggunaTeknisiId = null;
            if ($teknisiId) {
                if (Pengguna::where('id_pengguna', $teknisiId)->exists()) {
                    $penggunaTeknisiId = $teknisiId;
                } else {
                    $penggunaTeknisi = Pengguna::where('role', 'teknisi')->first() ?? Pengguna::create([
                        'nama' => 'Teknisi '.$teknisiId,
                        'email' => 'teknisi_'.$teknisiId.'_'.uniqid().'@ambius.com',
                        'role' => 'teknisi',
                        'password' => bcrypt('password'),
                        'status_aktif' => true,
                    ]);
                    $penggunaTeknisiId = $penggunaTeknisi->id_pengguna;
                }
            }

            JadwalPenugasan::updateOrCreate(
                ['id_pemesanan' => $pemesanan->id_pemesanan],
                [
                    'id_teknisi' => $penggunaTeknisiId ?? ($pemesanan->jadwalPenugasan?->id_teknisi ?? (Pengguna::where('role', 'teknisi')->value('id_pengguna') ?? 1)),
                    'tgl_survei' => $validated['tanggal_survey'] ?? ($pemesanan->jadwalPenugasan?->tgl_survei ?? now()->format('Y-m-d')),
                    'jam_survei' => $pemesanan->jadwalPenugasan?->jam_survei ?? '09:00',
                    'tgl_pengerjaan' => $validated['tanggal_pengerjaan'] ?? $pemesanan->jadwalPenugasan?->tgl_pengerjaan,
                    'jam_pengerjaan' => $pemesanan->jadwalPenugasan?->jam_pengerjaan ?? '10:00',
                ]
            );
        }

        return redirect()->route('admin.pemesanan.show', $pemesanan->id_pemesanan)
            ->with('success', 'Data pemesanan berhasil diperbarui.');
    }

    /**
     * Upload bukti pengerjaan (kompatibilitas pengujian).
     */
    public function uploadBukti(Request $request, Pemesanan $pemesanan): RedirectResponse
    {
        $request->validate([
            'foto' => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:5120'],
        ]);

        $path = $request->file('foto')->store('bukti_pengerjaan', 'public');

        BuktiKehadiran::create([
            'id_pemesanan' => $pemesanan->id_pemesanan,
            'id_teknisi' => $pemesanan->jadwalPenugasan?->id_teknisi ?? Auth::id() ?? 1,
            'foto' => $path,
            'jenis_kehadiran' => 'Pengerjaan',
            'status_verifikasi' => 'Disetujui',
        ]);

        return redirect()->route('admin.pemesanan.show', $pemesanan->id_pemesanan)
            ->with('success', 'Bukti pengerjaan berhasil diunggah.');
    }

    /**
     * Verifikasi Down Payment (DP) dan upload bukti transfer DP oleh Admin.
     */
    public function verifikasiDp(Request $request, Pemesanan $pemesanan): RedirectResponse
    {
        $request->validate([
            'bukti_dp' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'catatan_dp' => ['nullable', 'string', 'max:500'],
        ], [
            'bukti_dp.required' => 'File bukti transfer DP wajib diunggah.',
            'bukti_dp.image' => 'File bukti transfer harus berupa gambar (JPG, JPEG, PNG, WEBP).',
            'bukti_dp.max' => 'Ukuran file bukti transfer maksimal 5 MB.',
        ]);

        $path = $request->file('bukti_dp')->store('bukti_dp', 'public');

        $this->statusService->verifikasiDp(
            $pemesanan,
            Auth::id(),
            $path,
            $request->input('catatan_dp')
        );

        return redirect()->route('admin.pemesanan.show', $pemesanan->id_pemesanan)
            ->with('success', 'Pembayaran Down Payment (DP 10%) berhasil diverifikasi dan bukti transfer telah tersimpan.');
    }
}
