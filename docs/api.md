# Dokumentasi API

API menggunakan JSON dan berada di bawah `/api`; endpoint versi saat ini berada di `/api/v1`. Semua rute pada `routes/api.php` memerlukan sesi pengguna yang sudah login dan email terverifikasi. Peran yang digunakan adalah `admin` dan `user` (mahasiswa).

## Autentikasi dan CORS

React SPA berjalan pada origin Laravel yang sama dan memakai sesi cookie Laravel Sanctum, bukan JWT. `POST`, `PUT`, `PATCH`, dan `DELETE` juga harus membawa token CSRF pada header `X-XSRF-TOKEN`. Untuk klien browser, ambil cookie CSRF terlebih dahulu melalui `GET /sanctum/csrf-cookie`; kemudian login melalui alur web, kirim cookie sesi bersama permintaan, dan gunakan `GET /api/user` untuk memeriksa sesi.

Jangan menyimpan cookie atau token autentikasi di source code. Untuk frontend pada origin terpisah, konfigurasi `.env` harus konsisten:

- `APP_URL`: URL publik Laravel.
- `SANCTUM_STATEFUL_DOMAINS`: hostname frontend beserta port jika ada, tanpa skema (contoh `localhost:5173,app.example.test`).
- `CORS_ALLOWED_ORIGINS`: origin lengkap frontend, dipisahkan koma (contoh `http://localhost:5173,https://app.example.test`).
- Gunakan HTTPS dan konfigurasi cookie sesi yang sesuai di produksi. CORS mengizinkan credentials; jangan memakai wildcard origin untuk cookie.

Jika frontend dan API memakai origin yang sama, browser tidak memerlukan CORS. Untuk domain/port berbeda, host frontend harus masuk dalam daftar stateful Sanctum dan origin lengkapnya harus masuk dalam daftar CORS. API tidak menerbitkan JWT; tabel personal access tokens tidak mengubah alur sesi SPA.

## Daftar endpoint

Semua endpoint berikut memerlukan autentikasi Sanctum dan email terverifikasi.

| Method | Path | Keterangan / izin |
|---|---|---|
| GET | `/api/user` | Identitas sesi (`id`, `name`, `email`, `role`, `program_studi`) |
| GET | `/api/v1/dashboard` | Ringkasan akademik, jadwal hari ini, tugas dekat, materi terbaru, ujian mendatang |
| PATCH | `/api/v1/profile` | Ubah profil pengguna aktif |
| PATCH | `/api/v1/profile/password` | Ubah sandi pengguna aktif |
| GET | `/api/v1/students` | Daftar mahasiswa; admin saja |
| PATCH | `/api/v1/students/{id}` | Ubah mahasiswa; admin saja |
| GET, POST | `/api/v1/mata-kuliahs` | Daftar mata kuliah; tambah admin saja |
| GET, PUT, PATCH, DELETE | `/api/v1/mata-kuliahs/{id}` | Lihat; ubah/hapus milik sendiri atau admin |
| GET, POST | `/api/v1/jadwals` | Daftar jadwal; tambah admin saja |
| GET, PUT, PATCH, DELETE | `/api/v1/jadwals/{id}` | Lihat; tambah/ubah/hapus admin saja |
| GET, POST | `/api/v1/agenda-ujians` | Daftar agenda; tambah admin saja |
| GET, PUT, PATCH, DELETE | `/api/v1/agenda-ujians/{id}` | Lihat; tambah/ubah/hapus admin saja |
| GET, POST | `/api/v1/materis` | Daftar dan tambah materi |
| GET, PUT, PATCH, DELETE | `/api/v1/materis/{id}` | Lihat; ubah/hapus milik sendiri atau admin |
| GET | `/api/v1/materis/{id}/download` | Unduh lampiran jika materi terlihat |
| GET, POST | `/api/v1/tugas` | Daftar dan tambah tugas |
| GET, PUT, PATCH, DELETE | `/api/v1/tugas/{id}` | Lihat; ubah/hapus milik sendiri atau admin |
| PATCH | `/api/v1/tugas/{id}/status` | Ubah status pribadi; mahasiswa saja |
| GET | `/api/v1/tugas/{id}/download` | Unduh lampiran jika tugas terlihat |

`PUT` dan `PATCH` pada resource mata kuliah, jadwal, agenda ujian, materi, dan tugas menjalankan validasi payload lengkap (bukan partial update). Untuk pembaruan unggahan file, kirim `POST` multipart ke URL item dengan field `_method=PATCH`, sebagaimana dilakukan SPA Laravel.

## Parameter daftar

| Endpoint | Parameter | Keterangan |
|---|---|---|
| `/api/v1/mata-kuliahs` | `per_page` | Default 12, minimum 1, maksimum 100 |
| `/api/v1/students` | `per_page` | Admin saja; default 20, minimum 1, maksimum 100 |
| `/api/v1/materis` | `search`, `course_id`, `date_from`, `date_to`, `type` | `search` mencari judul, detail, dan nama mata kuliah; rentang tanggal memakai `created_at`; `type`: `File` atau `Link`; halaman tetap 12 item |
| `/api/v1/tugas` | `search`, `course_id`, `date_from`, `date_to`, `source`, `status` | Rentang tanggal memakai deadline; mahasiswa dapat memakai `source=shared|personal` dan status; filter `source`/`status` tidak berlaku untuk admin; halaman tetap 12 item |

Materi, tugas, mata kuliah, dan mahasiswa menggunakan paginasi. Daftar materi/tugas/mata kuliah menggunakan simple pagination Laravel; data mahasiswa menggunakan pagination bernomor dengan total. Jadwal dan agenda ujian mengembalikan seluruh item yang terlihat tanpa paginasi.

## Payload dan aturan

- **Mata kuliah**: `nama`, `kode_mk`, `dosen` (string, maks. 255), `sks` (integer minimal 1). Penambahan hanya untuk admin.
- **Jadwal**: `id_mata_kuliah`, `hari` (`Senin`–`Minggu`), `waktu_mulai`, `waktu_selesai` (format `HH:MM`, selesai harus setelah mulai), `ruang`, `dosen`, `mode` (`Offline` atau `Online`), `link_zoom` (opsional).
- **Agenda ujian**: `id_mata_kuliah`, `jenis` (`UTS`/`UAS`), `tanggal`, `waktu_mulai`, `waktu_selesai`, `mode`, serta opsional `ruang`, `catatan`, `link_zoom`. Tautan wajib dan harus URL valid jika `mode=Online`.
- **Materi**: `judul`, `pertemuan`, `tipe` (`File`/`Link`), `detail` (wajib kecuali file dikirim), dan salah satu `id_mata_kuliah` atau `nama_mata_kuliah`. Lampiran opsional: PDF, DOC, DOCX, PPT, atau PPTX hingga 20 MB. Saat update, `remove_file=true` menghapus lampiran lama.
- **Tugas**: `id_mata_kuliah`, `judul`, `deskripsi`, `deadline`, `prioritas` (`Tinggi`/`Sedang`/`Rendah`), dan lampiran opsional dengan batas tipe/ukuran yang sama. Saat update, `remove_file=true` menghapus lampiran lama.
- **Status tugas**: kirim `{"status":"Belum dikerjakan"}`, `{"status":"Sudah dikerjakan"}`, atau `{"status":"Selesai"}`. Status disimpan per mahasiswa dan tidak dapat diubah admin.
- **Profil**: `name` dan `email` wajib. Mengubah email menghapus status verifikasi sampai diverifikasi kembali.
- **Sandi**: `current_password`, `password`, dan `password_confirmation`; sandi baru mengikuti aturan Laravel.
- **Mahasiswa**: admin mengirim `name` dan `email`; `password` opsional tetapi harus dikirim bersama `password_confirmation` jika diubah.

Respons resource tunggal dan pembuatan membungkus objek di dalam `data`; pembuatan mengembalikan 201 dan penghapusan / ubah sandi mengembalikan 204. Kesalahan validasi mengembalikan 422 dengan `message` dan `errors`; sesi tidak valid 401; izin ditolak 403; data tidak ditemukan atau tidak terlihat 404; mata kuliah yang masih dipakai tidak dapat dihapus (409).

Respons materi dan tugas memuat `is_admin_shared` serta `can_manage`. Respons tugas juga memuat `my_status` untuk mahasiswa (default `Belum dikerjakan`, null untuk admin) dan `download_url` jika ada lampiran. Respons unduhan mengirim file privat, bukan JSON.

## Swagger / OpenAPI

Spesifikasi OpenAPI 3 tersedia pada [`openapi.yaml`](openapi.yaml) dan dapat diimpor ke Swagger Editor atau Postman.
