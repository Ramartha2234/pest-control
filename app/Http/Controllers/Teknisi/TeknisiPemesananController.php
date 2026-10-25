<?php

namespace App\Http\Controllers\Teknisi;

use App\Http\Controllers\Controller;
use App\Models\Pemesanan;
use App\Services\PemesananStatusService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class TeknisiPemesananController extends Controller
{
    public function __construct(
        protected PemesananStatusService $statusService
    ) {}

    /**
     * Dashboard ringkasan khusus teknisi yang sedang login.
     * Metrik: tugas hari ini, tugas menunggu tindakan (ditolak admin), tugas On-Going, dan jadwal terdekat.
     */
    public function index(): View
    {
        $teknisiId = Auth::id();
        $today = Carbon::today()->toDateString();

        // Koleksi semua pemesanan teknisi yang aktif (bukan Done & bukan Cancel)
        $activeOrders = Pemesanan::whereHas('jadwalPenugasan', fn ($q) => $q->where('id_teknisi', $teknisiId))
            ->whereNotIn('status_pengerjaan', ['Done', 'Cancel'])
            ->with(['pelanggan', 'tarifLayanan', 'jadwalPenugasan', 'hasilSurveis', 'buktiKehadirans', 'workOrders', 'buktiPembayarans'])
            ->get();

        // 1. Tugas hari ini (Survei hari ini ATAU Pengerjaan hari ini)
        $tugasHariIni = $activeOrders->filter(function ($p) use ($today) {
            $jadwal = $p->jadwalPenugasan;
            if (! $jadwal) {
                return false;
            }
            $tglSurvei = $jadwal->tgl_survei ? Carbon::parse($jadwal->tgl_survei)->toDateString() : null;
            $tglKerja = $jadwal->tgl_pengerjaan ? Carbon::parse($jadwal->tgl_pengerjaan)->toDateString() : null;

            return $tglSurvei === $today || $tglKerja === $today;
        });

        // 2. Tugas menunggu tindakan teknisi (pengajuan ditolak admin dan perlu diupload ulang)
        $tugasMenungguTindakan = $activeOrders->filter(fn ($p) => $p->perluTindakanTeknisi());

        // 3. Tugas On-Going
        $tugasOngoing = $activeOrders->where('status_pengerjaan', 'On-Going');

        // 4. Jadwal terdekat (Survei atau Treatment mendatang)
        $jadwalTerdekat = Pemesanan::whereHas('jadwalPenugasan', fn ($q) => $q->where('id_teknisi', $teknisiId))
            ->whereNotIn('status_pengerjaan', ['Done', 'Cancel'])
            ->leftJoin('jadwal_penugasan', 'pemesanan.id_pemesanan', '=', 'jadwal_penugasan.id_pemesanan')
            ->select('pemesanan.*')
            ->with(['pelanggan', 'tarifLayanan', 'jadwalPenugasan'])
            ->orderByRaw('COALESCE(jadwal_penugasan.tgl_pengerjaan, jadwal_penugasan.tgl_survei, pemesanan.tgl_survei_diminta) ASC')
            ->take(5)
            ->get();

        // Total riwayat selesai
        $totalSelesai = Pemesanan::whereHas('jadwalPenugasan', fn ($q) => $q->where('id_teknisi', $teknisiId))
            ->where('status_pengerjaan', 'Done')
            ->count();

        return view('teknisi.dashboard', [
            'countHariIni' => $tugasHariIni->count(),
            'countPerluTindakan' => $tugasMenungguTindakan->count(),
            'countOngoing' => $tugasOngoing->count(),
            'countTotalAktif' => $activeOrders->count(),
            'countSelesai' => $totalSelesai,
            'tugasHariIni' => $tugasHariIni,
            'tugasMenungguTindakan' => $tugasMenungguTindakan,
            'jadwalTerdekat' => $jadwalTerdekat,
        ]);
    }

    /**
     * Daftar seluruh tugas pemesanan milik teknisi dengan filter & pencarian.
     */
    public function tugas(Request $request): View
    {
        $teknisiId = Auth::id();
        $statusFilter = $request->query('status');
        $search = trim($request->query('search', ''));

        $query = Pemesanan::whereHas('jadwalPenugasan', function ($q) use ($teknisiId) {
            $q->where('id_teknisi', $teknisiId);
        })
            ->with(['pelanggan', 'tarifLayanan', 'jadwalPenugasan', 'hasilSurveis', 'buktiKehadirans', 'workOrders', 'buktiPembayarans'])
            ->leftJoin('jadwal_penugasan', 'pemesanan.id_pemesanan', '=', 'jadwal_penugasan.id_pemesanan')
            ->select('pemesanan.*')
            ->orderByRaw('COALESCE(jadwal_penugasan.tgl_pengerjaan, jadwal_penugasan.tgl_survei, pemesanan.tgl_survei_diminta) ASC');

        if ($statusFilter && in_array($statusFilter, ['Pending', 'On-Going', 'Done', 'Cancel'])) {
            $query->where('pemesanan.status_pengerjaan', $statusFilter);
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('pemesanan.kode_pemesanan', 'like', "%{$search}%")
                    ->orWhereHas('pelanggan', function ($pq) use ($search) {
                        $pq->where('nama', 'like', "%{$search}%")
                            ->orWhere('no_hp', 'like', "%{$search}%")
                            ->orWhere('alamat', 'like', "%{$search}%");
                    });
            });
        }

        $pemesanans = $query->paginate(10)->withQueryString();

        $counts = [
            'total' => Pemesanan::whereHas('jadwalPenugasan', fn ($q) => $q->where('id_teknisi', $teknisiId))->count(),
            'pending' => Pemesanan::whereHas('jadwalPenugasan', fn ($q) => $q->where('id_teknisi', $teknisiId))->where('status_pengerjaan', 'Pending')->count(),
            'ongoing' => Pemesanan::whereHas('jadwalPenugasan', fn ($q) => $q->where('id_teknisi', $teknisiId))->where('status_pengerjaan', 'On-Going')->count(),
            'done' => Pemesanan::whereHas('jadwalPenugasan', fn ($q) => $q->where('id_teknisi', $teknisiId))->where('status_pengerjaan', 'Done')->count(),
            'cancel' => Pemesanan::whereHas('jadwalPenugasan', fn ($q) => $q->where('id_teknisi', $teknisiId))->where('status_pengerjaan', 'Cancel')->count(),
        ];

        return view('teknisi.tugas', [
            'pemesanans' => $pemesanans,
            'counts' => $counts,
            'currentFilter' => $statusFilter,
            'search' => $search,
        ]);
    }

    /**
     * Riwayat pekerjaan yang telah selesai (Done) milik teknisi.
     */
    public function riwayat(Request $request): View
    {
        $teknisiId = Auth::id();
        $search = trim($request->query('search', ''));

        $query = Pemesanan::whereHas('jadwalPenugasan', function ($q) use ($teknisiId) {
            $q->where('id_teknisi', $teknisiId);
        })
            ->where('status_pengerjaan', 'Done')
            ->with(['pelanggan', 'tarifLayanan', 'jadwalPenugasan', 'workOrders', 'buktiPembayarans'])
            ->latest('tgl_selesai');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('kode_pemesanan', 'like', "%{$search}%")
                    ->orWhereHas('pelanggan', fn ($pq) => $pq->where('nama', 'like', "%{$search}%"));
            });
        }

        $riwayats = $query->paginate(10)->withQueryString();

        return view('teknisi.riwayat', [
            'riwayats' => $riwayats,
            'search' => $search,
        ]);
    }

    /**
     * Detail penugasan pemesanan dengan stepper alur 5 tahap.
     */
    public function show(Pemesanan $pemesanan): View
    {
        $this->authorizeTeknisiAssignment($pemesanan);

        $pemesanan->load([
            'pelanggan',
            'tarifLayanan',
            'jadwalPenugasan',
            'hasilSurveis' => fn ($q) => $q->latest('id_hasil_survei'),
            'buktiKehadirans' => fn ($q) => $q->latest('id_bukti_kehadiran'),
            'workOrders' => fn ($q) => $q->latest('id_wo'),
            'buktiPembayarans' => fn ($q) => $q->latest('id_bukti_pembayaran'),
        ]);

        return view('teknisi.show', [
            'pemesanan' => $pemesanan,
            'tahapAktif' => $pemesanan->tahapPengerjaanTeknisi(),
            'perluTindakan' => $pemesanan->perluTindakanTeknisi(),
            'daftarAlasan' => $pemesanan->daftarAlasanPenolakan(),
            'latestSurvei' => $pemesanan->latestHasilSurvei(),
            'latestBuktiSurvei' => $pemesanan->latestBuktiSurvei(),
            'latestBuktiPengerjaan' => $pemesanan->latestBuktiPengerjaan(),
            'latestWo' => $pemesanan->latestWorkOrder(),
            'latestBuktiBayar' => $pemesanan->latestBuktiPembayaran(),
        ]);
    }

    /**
     * Tahap 1 / Tahap 3: Upload bukti kehadiran teknisi (Survei atau Pengerjaan).
     * Validasi urutan tahap di backend.
     */
    public function uploadBuktiKehadiran(Request $request, Pemesanan $pemesanan): RedirectResponse
    {
        $this->authorizeTeknisiAssignment($pemesanan);

        if ($pemesanan->status_pengerjaan === 'Cancel') {
            return redirect()->route('teknisi.pemesanan.show', $pemesanan->id_pemesanan)
                ->with('error', 'Pemesanan ini telah dibatalkan ('.$pemesanan->alasan_pembatalan.'). Tidak dapat mengunggah bukti kehadiran.');
        }

        if ($pemesanan->status_pengerjaan === 'Done') {
            return redirect()->route('teknisi.pemesanan.show', $pemesanan->id_pemesanan)
                ->with('error', 'Pemesanan ini telah selesai. Tidak dapat mengunggah bukti kehadiran baru.');
        }

        $request->validate([
            'jenis_kehadiran' => ['required', 'in:Survei,Pengerjaan'],
            'foto' => ['required', 'file', 'mimes:jpg,jpeg,png', 'max:15360'],
            'catatan' => ['nullable', 'string', 'max:1000'],
        ], [
            'jenis_kehadiran.required' => 'Pilih jenis kehadiran (Survei atau Pengerjaan).',
            'foto.required' => 'Foto bukti kehadiran wajib diunggah.',
            'foto.uploaded' => 'Ukuran foto terlalu besar atau gagal diunggah ke server.',
            'foto.mimes' => 'Format foto harus berupa JPG atau PNG.',
            'foto.max' => 'Ukuran foto maksimal adalah 15 MB.',
        ]);

        $jenis = $request->input('jenis_kehadiran');

        // Validasi urutan tahap untuk kehadiran Pengerjaan
        if ($jenis === 'Pengerjaan') {
            $hasilSurvei = $pemesanan->latestHasilSurvei();
            if (! $hasilSurvei || $hasilSurvei->status_verifikasi !== 'Disetujui') {
                return redirect()->route('teknisi.pemesanan.show', $pemesanan->id_pemesanan)
                    ->with('error', 'Urutan tidak valid: Anda harus mengajukan hasil survei dan disetujui admin sebelum memulai pengerjaan.');
            }

            // Cegah upload ulang jika foto pengerjaan sebelumnya masih Menunggu
            $latestPengerjaan = $pemesanan->latestBuktiPengerjaan();
            if ($latestPengerjaan && $latestPengerjaan->status_verifikasi === 'Menunggu') {
                return redirect()->route('teknisi.pemesanan.show', $pemesanan->id_pemesanan)
                    ->with('error', 'Bukti kehadiran pengerjaan sebelumnya masih menunggu verifikasi admin.');
            }
        } else {
            // Cegah upload ulang jika foto survei sebelumnya masih Menunggu
            $latestSurvei = $pemesanan->latestBuktiSurvei();
            if ($latestSurvei && $latestSurvei->status_verifikasi === 'Menunggu') {
                return redirect()->route('teknisi.pemesanan.show', $pemesanan->id_pemesanan)
                    ->with('error', 'Bukti kehadiran survei sebelumnya masih menunggu verifikasi admin.');
            }
        }

        $path = $request->file('foto')->store('private/kehadiran', 'local');

        if ($jenis === 'Survei') {
            $this->statusService->submitBuktiKehadiranSurvei(
                $pemesanan,
                Auth::id(),
                $path,
                $request->input('catatan')
            );
        } else {
            $this->statusService->submitBuktiKehadiranPengerjaan(
                $pemesanan,
                Auth::id(),
                $path,
                $request->input('catatan')
            );
        }

        return redirect()->route('teknisi.pemesanan.show', $pemesanan->id_pemesanan)
            ->with('success', 'Foto bukti kehadiran ('.$jenis.') berhasil diunggah dan menunggu verifikasi admin.');
    }

    /**
     * Tahap 2: Input hasil survei (tanggal pengerjaan, jam, harga kesepakatan).
     * Validasi urutan tahap di backend.
     */
    public function submitHasilSurvei(Request $request, Pemesanan $pemesanan): RedirectResponse
    {
        $this->authorizeTeknisiAssignment($pemesanan);

        if ($pemesanan->status_pengerjaan === 'Cancel') {
            return redirect()->route('teknisi.pemesanan.show', $pemesanan->id_pemesanan)
                ->with('error', 'Pemesanan ini telah dibatalkan. Tidak dapat mengajukan hasil survei.');
        }

        // Validasi urutan tahap: Bukti kehadiran survei harus sudah DISETUJUI admin!
        $buktiSurvei = $pemesanan->latestBuktiSurvei();
        if (! $buktiSurvei || $buktiSurvei->status_verifikasi !== 'Disetujui') {
            return redirect()->route('teknisi.pemesanan.show', $pemesanan->id_pemesanan)
                ->with('error', 'Urutan tidak valid: Bukti kehadiran survei harus telah disetujui oleh admin terlebih dahulu sebelum mengajukan hasil survei.');
        }

        // Cegah spam jika pengajuan sebelumnya masih Menunggu
        $latestHasil = $pemesanan->latestHasilSurvei();
        if ($latestHasil && $latestHasil->status_verifikasi === 'Menunggu') {
            return redirect()->route('teknisi.pemesanan.show', $pemesanan->id_pemesanan)
                ->with('error', 'Pengajuan hasil survei sebelumnya masih menunggu verifikasi admin.');
        }

        $request->validate([
            'tgl_pengerjaan_diajukan' => ['required', 'date', 'after_or_equal:today'],
            'jam_pengerjaan_diajukan' => ['required', 'date_format:H:i'],
            'harga_diajukan' => ['required', 'numeric', 'min:10000'],
            'catatan_survei' => ['nullable', 'string', 'max:1000'],
        ], [
            'tgl_pengerjaan_diajukan.required' => 'Tanggal pengerjaan hasil kesepakatan wajib diisi.',
            'tgl_pengerjaan_diajukan.after_or_equal' => 'Tanggal pengerjaan tidak boleh di masa lampau.',
            'jam_pengerjaan_diajukan.required' => 'Jam pengerjaan wajib diisi.',
            'harga_diajukan.required' => 'Nominal harga kesepakatan wajib diisi.',
            'harga_diajukan.numeric' => 'Nominal harga harus berupa angka.',
            'harga_diajukan.min' => 'Nominal harga minimal Rp 10.000.',
        ]);

        $this->statusService->submitHasilSurvei(
            $pemesanan,
            Auth::id(),
            $request->input('tgl_pengerjaan_diajukan'),
            $request->input('jam_pengerjaan_diajukan'),
            (float) $request->input('harga_diajukan'),
            $request->input('catatan_survei')
        );

        return redirect()->route('teknisi.pemesanan.show', $pemesanan->id_pemesanan)
            ->with('success', 'Hasil survei dan kesepakatan harga berhasil diajukan untuk verifikasi admin.');
    }

    /**
     * Tahap 4: Upload Dokumen Work Order (WO).
     * Validasi urutan tahap di backend.
     */
    public function uploadWorkOrder(Request $request, Pemesanan $pemesanan): RedirectResponse
    {
        $this->authorizeTeknisiAssignment($pemesanan);

        if ($pemesanan->status_pengerjaan === 'Cancel') {
            return redirect()->route('teknisi.pemesanan.show', $pemesanan->id_pemesanan)
                ->with('error', 'Pemesanan ini telah dibatalkan. Tidak dapat mengunggah dokumen Work Order.');
        }

        // Validasi urutan tahap: Status pengerjaan harus On-Going
        if ($pemesanan->status_pengerjaan !== 'On-Going') {
            return redirect()->route('teknisi.pemesanan.show', $pemesanan->id_pemesanan)
                ->with('error', 'Urutan tidak valid: Anda hanya dapat mengunggah Work Order setelah pengerjaan berstatus On-Going (bukti kehadiran pengerjaan telah disetujui admin).');
        }

        // Cegah spam jika WO sebelumnya masih Menunggu
        $latestWo = $pemesanan->latestWorkOrder();
        if ($latestWo && $latestWo->status_verifikasi === 'Menunggu') {
            return redirect()->route('teknisi.pemesanan.show', $pemesanan->id_pemesanan)
                ->with('error', 'Dokumen Work Order sebelumnya masih menunggu verifikasi admin.');
        }

        $request->validate([
            'file_wo' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:15360'],
            'catatan' => ['nullable', 'string', 'max:1000'],
        ], [
            'file_wo.required' => 'File dokumen Work Order (WO) wajib diunggah.',
            'file_wo.uploaded' => 'Ukuran file WO terlalu besar atau gagal diunggah ke server.',
            'file_wo.mimes' => 'Format file Work Order harus PDF, JPG, atau PNG.',
            'file_wo.max' => 'Ukuran file Work Order maksimal adalah 15 MB.',
        ]);

        $path = $request->file('file_wo')->store('private/work_orders', 'local');

        $this->statusService->submitWorkOrder(
            $pemesanan,
            Auth::id(),
            $path,
            $request->input('catatan')
        );

        return redirect()->route('teknisi.pemesanan.show', $pemesanan->id_pemesanan)
            ->with('success', 'Dokumen Work Order (WO) berhasil diunggah dan menunggu verifikasi admin.');
    }

    /**
     * Tahap 4: Upload Bukti Pembayaran (Cash / Transfer).
     * Validasi urutan tahap di backend.
     */
    public function uploadBuktiPembayaran(Request $request, Pemesanan $pemesanan): RedirectResponse
    {
        $this->authorizeTeknisiAssignment($pemesanan);

        if ($pemesanan->status_pengerjaan === 'Cancel') {
            return redirect()->route('teknisi.pemesanan.show', $pemesanan->id_pemesanan)
                ->with('error', 'Pemesanan ini telah dibatalkan. Tidak dapat mengunggah bukti pembayaran.');
        }

        // Validasi urutan tahap: Status pengerjaan harus On-Going
        if ($pemesanan->status_pengerjaan !== 'On-Going') {
            return redirect()->route('teknisi.pemesanan.show', $pemesanan->id_pemesanan)
                ->with('error', 'Urutan tidak valid: Anda hanya dapat mengunggah bukti pembayaran setelah pengerjaan berstatus On-Going.');
        }

        // Cegah spam jika bukti bayar sebelumnya masih Menunggu
        $latestBukti = $pemesanan->latestBuktiPembayaran();
        if ($latestBukti && $latestBukti->status_verifikasi === 'Menunggu') {
            return redirect()->route('teknisi.pemesanan.show', $pemesanan->id_pemesanan)
                ->with('error', 'Bukti pembayaran sebelumnya masih menunggu verifikasi admin.');
        }

        $request->validate([
            'file_bukti' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:15360'],
            'metode_pembayaran' => ['required', 'in:Cash,Transfer'],
            'jumlah_bayar' => ['nullable', 'numeric', 'min:0'],
            'catatan' => ['nullable', 'string', 'max:1000'],
        ], [
            'file_bukti.required' => 'Foto atau file bukti pembayaran wajib diunggah.',
            'file_bukti.uploaded' => 'Ukuran file bukti pembayaran terlalu besar atau gagal diunggah ke server.',
            'file_bukti.mimes' => 'Format bukti pembayaran harus PDF, JPG, atau PNG.',
            'file_bukti.max' => 'Ukuran file bukti pembayaran maksimal adalah 15 MB.',
            'metode_pembayaran.required' => 'Pilih metode pembayaran (Cash atau Transfer).',
            'metode_pembayaran.in' => 'Metode pembayaran harus berupa Cash atau Transfer.',
        ]);

        $path = $request->file('file_bukti')->store('private/pembayaran', 'local');

        $this->statusService->submitBuktiPembayaran(
            $pemesanan,
            Auth::id(),
            $path,
            $request->input('metode_pembayaran'),
            $request->filled('jumlah_bayar') ? (float) $request->input('jumlah_bayar') : null,
            $request->input('catatan')
        );

        return redirect()->route('teknisi.pemesanan.show', $pemesanan->id_pemesanan)
            ->with('success', 'Bukti pembayaran ('.$request->input('metode_pembayaran').') berhasil diunggah dan menunggu verifikasi admin.');
    }

    /**
     * Otorisasi kepemilikan: Teknisi HANYA boleh mengakses pesanan yang ditugaskan kepadanya.
     */
    protected function authorizeTeknisiAssignment(Pemesanan $pemesanan): void
    {
        $assignedTeknisiId = $pemesanan->jadwalPenugasan?->id_teknisi;

        if (! $assignedTeknisiId || (int) $assignedTeknisiId !== (int) Auth::id()) {
            abort(403, 'Akses ditolak: Anda tidak memiliki penugasan aktif untuk pemesanan ini.');
        }
    }
}
