# Product Requirements Document (PRD)
## Sistem Pemesanan Jasa Pest Control Berbasis Web pada CV. Ambius

---

## 1. Ringkasan Produk

**Nama Produk:** Sistem Pemesanan Jasa Pest Control CV. Ambius (Web-based)
**Framework:** Laravel
**Metode Pengujian:** Black Box Testing
**Target Pengguna:** Owner CV. Ambius (sebagai Admin) dan Calon Client / Client CV. Ambius (sebagai User)

Sistem ini adalah sebuah sistem informasi berbasis web yang memfasilitasi proses pemesanan jasa pest control secara online, mulai dari pengisian form pemesanan, pemantauan status pengerjaan secara real-time (live tracking), pemantauan status pembayaran, hingga pengunduhan bukti pengerjaan setelah treatment selesai dilakukan. Sistem ini murni berfungsi sebagai media informasi dan manajemen pemesanan — **tidak menangani proses transaksi pembayaran digital/online** (pembayaran dilakukan di luar sistem, status pembayaran hanya dicatat/diupdate secara manual oleh admin).

**Keunikan Sistem:** Calon client **tidak perlu login maupun membuat akun** untuk melakukan pemesanan jasa. Setiap pemesanan yang berhasil dibuat akan menghasilkan sebuah **kode unik pemesanan**, yang kemudian dapat digunakan client kapan saja untuk mengecek status pengerjaan dan status pembayaran melalui website, cukup dengan memasukkan kode tersebut pada halaman pengecekan status.

---

## 2. Latar Belakang & Tujuan

**Latar Belakang:**
Proses pemesanan jasa pest control pada CV. Ambius saat ini masih dilakukan secara manual/konvensional (kemungkinan via WhatsApp atau datang langsung), sehingga menyulitkan proses pencatatan, monitoring status pekerjaan, dan transparansi informasi antara client dan pihak CV. Ambius.

**Tujuan:**
1. Mempermudah calon client dalam melakukan pemesanan jasa pest control secara online.
2. Memberikan transparansi status pengerjaan dan status pembayaran kepada client secara real-time.
3. Mempermudah admin (owner CV. Ambius) dalam mengelola pemesanan, teknisi, dan laporan pendapatan.
4. Menyediakan bukti pengerjaan digital yang dapat diunduh oleh client sebagai dokumentasi.

---

## 3. Ruang Lingkup (Scope)

### 3.1 Termasuk dalam Sistem (In-Scope)
- Form pemesanan jasa (booking) **tanpa perlu login/membuat akun** untuk client.
- Generate kode unik otomatis untuk setiap pemesanan yang berhasil dibuat.
- Fitur pengecekan status pengerjaan & status pembayaran menggunakan kode unik (tanpa login).
- Fitur tracking status pengerjaan secara live.
- Fitur informasi status pembayaran (dicatat manual oleh admin).
- Fitur upload & unduh bukti pengerjaan (foto).
- Fitur pengelolaan pemesanan oleh admin.
- Fitur pengelolaan data teknisi (karyawan) oleh admin.
- Fitur laporan pendapatan per bulan/tahun untuk admin.
- Link kontak WhatsApp admin pada website.
- Login hanya berlaku untuk Admin (Owner CV. Ambius), bukan untuk client.

### 3.2 Tidak Termasuk dalam Sistem (Out-of-Scope)
- Proses transaksi pembayaran online (payment gateway).
- Integrasi pembayaran digital (contoh: e-wallet, virtual account, dsb).
- Pembayaran dilakukan secara manual di luar sistem; sistem hanya mencatat **status** pembayaran.

---

## 4. Aktor / Peran Pengguna

| Role | Deskripsi |
|---|---|
| **User (Client/Calon Client)** | Pengguna yang melakukan pemesanan jasa **tanpa perlu login/membuat akun**, dan memantau status pesanan menggunakan kode unik pemesanan |
| **Admin (Owner CV. Ambius)** | Pengelola sistem yang login menggunakan akun, dan mengatur pemesanan, teknisi, bukti kerja, dan status |

---

## 5. Kebutuhan Fungsional

### 5.1 Fitur untuk User (Client) — Tanpa Login/Akun
1. **Input Data Pemesanan** — User dapat mengisi form pemesanan jasa pest control (nama, no. telp, alamat, jenis layanan, dsb) **tanpa perlu login atau membuat akun terlebih dahulu**.
2. **Menerima Kode Unik Pemesanan** — Setelah form berhasil disubmit, sistem otomatis membuat dan menampilkan **kode unik** yang menjadi identitas pemesanan tersebut.
3. **Cek Status Pengerjaan via Kode Unik** — User memasukkan kode unik pada halaman pengecekan status untuk memantau status pekerjaan secara live (lihat detail status di poin 6).
4. **Cek Status Pembayaran via Kode Unik** — User dapat melihat status pembayaran pesanannya menggunakan kode unik yang sama (lihat detail di poin 7).
5. **Mengunduh Bukti Pengerjaan** — User dapat mengunduh foto/bukti pengerjaan setelah treatment selesai (status *done*), diakses melalui kode unik.
6. **Kontak Admin via WhatsApp** — User dapat mengakses link WhatsApp admin langsung dari website untuk komunikasi lanjutan.

### 5.2 Fitur untuk Admin (Owner CV. Ambius)
1. **Input Bukti Pengerjaan** — Admin mengunggah foto bukti pengerjaan setelah treatment.
2. **Mengubah Status Pengerjaan** — Admin dapat mengubah status pekerjaan (Pending, On-going, Done, Cancel) serta menetapkan nama teknisi yang mengerjakan.
3. **Mengubah Status Pembayaran** — Admin dapat mengubah/memperbarui status pembayaran pesanan (Lunas, Belum Lunas).
4. **Membatalkan Pengerjaan** — Admin dapat membatalkan pesanan dengan mencantumkan alasan (dari pihak client atau dari pihak CV. Ambius).
5. **Melihat Laporan Pendapatan** — Admin dapat melihat rekap pendapatan per bulan atau per tahun.
6. **Mengelola Data Teknisi (Karyawan)** — Admin dapat menambah, mengubah, dan menghapus data teknisi/karyawan.
7. **Mengelola Seluruh Data Pemesanan** — Admin memiliki akses penuh (CRUD) terhadap data pemesanan yang masuk.

---

## 6. Business Logic — Status Pengerjaan

Status pengerjaan terdiri dari **4 status** (disebut "3 tahap" pada requirement awal, namun *Cancel* merupakan status tambahan di luar alur normal):

| Status | Deskripsi / Kondisi |
|---|---|
| **Pending** | Kondisi sedang survey lokasi atau terjadi pengunduran waktu pengerjaan |
| **On-going** | Pekerjaan sedang berlangsung, setelah proses survey disepakati (deal) |
| **Done** | Pekerjaan telah selesai dikerjakan. **Syarat khusus:** Pesanan **wajib dilunasi terlebih dahulu** sebelum status dapat diubah menjadi *Done* |
| **Cancel** | Pesanan dibatalkan — baik atas permintaan client, maupun karena pihak CV. Ambius tidak dapat mengambil pekerjaan tersebut |

**Catatan tambahan:** Sistem menampilkan nama teknisi yang ditugaskan untuk setiap pemesanan.

**Validasi/Aturan Sistem:**
- Status *Done* **tidak dapat dipilih** oleh admin apabila status pembayaran belum lunas.
- Perubahan ke status *Cancel* sebaiknya disertai kolom alasan pembatalan (client atau CV. Ambius).

### 6.1 Kode Unik Pemesanan (Tanpa Login)

Karena client tidak memiliki akun/login, sistem membutuhkan **kode unik** sebagai pengganti sesi login untuk mengakses status pesanan.

- Kode unik **digenerate otomatis oleh sistem** setiap kali form pemesanan berhasil disubmit.
- Kode unik bersifat **unik per pemesanan** (tidak boleh duplikat), disarankan kombinasi huruf & angka (contoh format: `PC-20260713-XXXXX`).
- Kode unik ditampilkan ke client setelah submit form, dan sebaiknya juga dikirim melalui notifikasi (misalnya ditampilkan di layar dan/atau dicatat client secara mandiri, karena sistem tidak menangani pengiriman email/WA otomatis).
- Kode unik digunakan client untuk mengakses **Halaman Cek Status Pemesanan**, yang menampilkan status pengerjaan, status pembayaran, nama teknisi, dan tombol unduh bukti pengerjaan (jika status *Done*).
- Validasi: jika kode unik yang dimasukkan tidak ditemukan/salah, sistem menampilkan pesan error tanpa membocorkan data pemesanan lain.

---

## 7. Business Logic — Status Pembayaran

Status pembayaran terdiri dari **2 status**:

| Status | Deskripsi |
|---|---|
| **Belum Lunas** | Status awal saat pemesanan baru dibuat atau pembayaran belum diterima secara penuh |
| **Lunas** | Pembayaran telah diterima secara penuh |

**Aturan Bisnis:**
- Pelunasan wajib dicatat di sistem sebelum status pengerjaan dapat diubah menjadi *Done*.

---

## 8. Kebutuhan Non-Fungsional

| Aspek | Ketentuan |
|---|---|
| Framework | Laravel |
| Pengujian | Black Box Testing (memastikan fungsi sesuai kebutuhan tanpa melihat struktur kode internal) |
| Platform | Web-based (responsive, dapat diakses melalui browser desktop maupun mobile) |
| Keamanan | Autentikasi login **hanya untuk Admin**; akses client ke data pesanan diverifikasi melalui kode unik (bukan sesi login), validasi input form |
| Media Penyimpanan Bukti | Penyimpanan file gambar (bukti pengerjaan) pada server/storage Laravel |

---

## 9. Gambaran Model Data (Entitas Utama — Referensi Awal)

> Catatan: Struktur ini adalah gambaran awal untuk membantu proses generate database/model pada Antigravity, dapat disesuaikan lebih lanjut pada tahap perancangan sistem (BAB III/IV).

> Catatan: Tidak ada tabel akun untuk client, karena client tidak login/registrasi. Data diri client disimpan langsung pada tabel Pemesanan, diakses kembali melalui **kode_unik**.

- **Admin**: id, nama, email, password (akun login hanya untuk admin/owner)
- **Teknisi**: id, nama, no_telp, status_aktif
- **Pemesanan**: id, **kode_unik** (unik, auto-generate), nama_client, no_telp_client, alamat_pengerjaan, teknisi_id, jenis_layanan, tanggal_pemesanan, nominal, status_pengerjaan (pending/on-going/done/cancel), alasan_cancel, is_lunas (belum_lunas/lunas/ditolak)
- **Bukti_Pengerjaan**: id, pemesanan_id, foto, tanggal_upload
- **Pembayaran_Log** (opsional): id, pemesanan_id, jumlah_dibayar, tanggal_bayar, tipe_pembayaran

---

## 10. Struktur & Penjelasan Halaman (Page Breakdown)

Berikut rincian halaman yang perlu dibangun, dikelompokkan berdasarkan sisi User dan Admin. Bagian ini bisa langsung dipakai sebagai acuan routing/blade view saat prompting di Antigravity.

### 10.1 Halaman Publik (Tanpa Login — Diakses Client)
| Halaman | Fungsi |
|---|---|
| **Landing Page / Home** | Menampilkan profil singkat CV. Ambius, daftar layanan pest control, dan tombol CTA "Pesan Sekarang" serta "Cek Status Pesanan". Menampilkan juga tombol/link kontak WhatsApp admin. |
| **Halaman Form Pemesanan** | Form input data pemesanan jasa (nama, no. telp, alamat pengerjaan, jenis layanan, dsb) — **dapat langsung diisi tanpa login/registrasi**. |
| **Halaman Konfirmasi Pemesanan** | Muncul setelah form berhasil disubmit, menampilkan **kode unik pemesanan** yang wajib disimpan client untuk pengecekan status selanjutnya. |
| **Halaman Cek Status Pemesanan** | Form sederhana berisi input kode unik. Setelah kode dimasukkan dan valid, menampilkan detail pesanan: jenis layanan, nama teknisi, status pengerjaan (Pending/On-going/Done/Cancel), status pelunasan (Lunas/Belum Lunas/Ditolak), serta tombol unduh bukti pengerjaan (muncul jika status *Done*). |

> Catatan: Karena tidak ada login/akun client, halaman **Login**, **Register**, **Dashboard User**, **Daftar Pesanan Saya**, dan **Profil User** pada sisi client **ditiadakan**. Seluruh interaksi client cukup melalui Form Pemesanan dan Halaman Cek Status Pemesanan (kode unik).

### 10.3 Halaman Sisi Admin (Owner CV. Ambius)
| Halaman | Fungsi |
|---|---|
| **Dashboard Admin** | Ringkasan jumlah pesanan berdasarkan status, serta ringkasan pendapatan bulan berjalan. |
| **Halaman Kelola Pemesanan (List)** | Menampilkan seluruh pesanan masuk dengan filter berdasarkan status pengerjaan/pembayaran. |
| **Halaman Detail & Kelola Pemesanan** | Admin dapat: menetapkan teknisi, mengubah status pengerjaan, mengubah status pembayaran, upload foto bukti pengerjaan, serta membatalkan pesanan beserta alasannya. Validasi status *Done* mengacu pada aturan pelunasan (bagian 6 & 7). |
| **Halaman Kelola Teknisi** | CRUD data teknisi/karyawan (tambah, edit, nonaktifkan/hapus). |
| **Halaman Laporan Pendapatan** | Menampilkan rekap pendapatan per bulan atau per tahun, bisa dalam bentuk tabel dan/atau grafik. |
| **Halaman Profil Admin** | Pengaturan akun admin (nama, email, password). |

### 10.4 Catatan Navigasi
- Client mengakses sistem tanpa login sama sekali — cukup lewat Landing Page → Form Pemesanan → dapat kode unik → Cek Status Pemesanan kapan pun dibutuhkan.
- Hanya Admin yang melalui proses login, lalu diarahkan ke Dashboard Admin.
- Halaman Cek Status Pemesanan (sisi Client) dan Halaman Detail & Kelola Pemesanan (sisi Admin) merupakan halaman inti yang menghubungkan seluruh business logic status pengerjaan dan status pembayaran pada bagian 6 dan 7.

---

## 11. Ringkasan Alur Pengguna (User Flow Singkat)

**Alur Client (Tanpa Login):**
1. Client mengisi form pemesanan **tanpa perlu login/registrasi** → data tersimpan dengan status default *Pending*.
2. Sistem menampilkan **kode unik pemesanan** kepada client sebagai bukti dan alat akses status.
3. Client memasukkan kode unik kapan saja pada Halaman Cek Status Pemesanan untuk memantau status pengerjaan & status pembayaran.
4. Setelah status *Done*, client dapat mengunduh bukti pengerjaan melalui halaman yang sama.
5. Client dapat menghubungi admin via WhatsApp kapan saja.

**Alur Admin:**
1. Admin menerima pemesanan baru dari client.
2. Admin melakukan survey → menentukan teknisi → update status ke *On-going*.
3. Admin memperbarui status pembayaran sesuai skema yang disepakati.
4. Setelah treatment selesai, admin mengunggah bukti pengerjaan.
5. Admin mengubah status ke *Done* (jika syarat pelunasan terpenuhi) atau *Cancel* jika dibatalkan.
6. Admin dapat memantau laporan pendapatan bulanan/tahunan kapan saja.

---

## 12. Kriteria Keberhasilan (Success Criteria)

- Seluruh fitur pada poin 5 berjalan sesuai fungsinya dan lolos pengujian black box.
- Status pengerjaan dan status pembayaran dapat diperbarui secara real-time dan konsisten dengan aturan bisnis pada poin 6 & 7.
- Admin dapat mengelola pemesanan dan teknisi tanpa error.
- Client dapat mengakses seluruh informasi pesanan miliknya dengan jelas.
- Client dapat membuat pemesanan dan mengecek status **tanpa perlu login/membuat akun**.
- Setiap pemesanan menghasilkan kode unik yang valid, tidak duplikat, dan berhasil digunakan untuk mengakses status pesanan yang benar.

---

## 13. Catatan Penggunaan untuk Antigravity

Dokumen ini dapat langsung digunakan sebagai *system prompt* / *project brief* saat memulai proyek di Antigravity, dengan urutan prompting yang disarankan:
1. Prompt pertama: minta Antigravity membuat struktur project Laravel + skema database berdasarkan bagian 9.
2. Prompt kedua: minta implementasi fitur User (bagian 5.1) + business logic status (bagian 6 & 7).
3. Prompt ketiga: minta implementasi fitur Admin (bagian 5.2).
4. Prompt keempat: minta setup testing black box (bagian 8) untuk validasi seluruh fitur.

Menjaga dokumen ini tetap dilampirkan/direferensikan pada setiap prompt lanjutan akan membantu Antigravity menjaga konsistensi aturan bisnis (terutama syarat pelunasan sebelum status *Done*, dan syarat nominal DP 50%).
