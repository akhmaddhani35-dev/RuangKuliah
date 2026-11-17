<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## Sistem Manajemen Jadwal dan Tugas Kuliah

Aplikasi Laravel untuk membantu mahasiswa mengelola mata kuliah, jadwal, materi, dan tugas.

### Fitur utama

- Dashboard dengan ringkasan mata kuliah, jadwal hari ini, deadline terdekat, materi terbaru, dan progres tugas.
- Jadwal rutin mingguan hanya dapat dikelola admin; konfirmasi perubahan harian diumumkan melalui kanal kelas.
- Agenda UTS/UAS bertanggal khusus tampil di bagian terpisah di bawah jadwal rutin pada halaman Jadwal; agenda dikelola admin dan terlihat di dashboard mahasiswa.
- Status tugas personal mahasiswa: Belum dikerjakan, Sudah dikerjakan, dan Selesai. Status tiap mahasiswa tersimpan terpisah, termasuk untuk tugas yang dibagikan admin. Admin hanya membagikan informasi tugas dan tidak memiliki progres pengerjaan.
- Lampiran materi dan tugas berupa PDF, Word, atau PowerPoint hingga 20 MB. File disimpan privat dan hanya dapat diunduh oleh pengguna yang berhak.
- Tampilan kartu responsif dengan pilihan tema gelap/terang.
- Antarmuka React SPA pada `/app` yang mengakses REST API Laravel melalui sesi Laravel Sanctum.

### Menjalankan aplikasi

Setelah mengatur koneksi database dan file `.env`, jalankan migrasi dan aset:

```bash
php artisan migrate
npm install
npm run build
php artisan serve
```

Untuk pengembangan atau satu server aplikasi, gunakan `SESSION_DRIVER=file` dan `CACHE_STORE=file` agar sesi/cache tidak menambah round-trip ke PostgreSQL. Jika aplikasi dijalankan pada beberapa server, gunakan penyimpanan bersama seperti Redis.

Tidak perlu menjalankan `storage:link` untuk lampiran; file disimpan pada disk privat.

### API dan SPA

Login melalui `/login`, lalu buka aplikasi React di `/app`. Endpoint JSON versi 1 tersedia di `/api/v1`; endpoint memerlukan sesi pengguna terautentikasi dan terverifikasi. Sanctum memakai cookie/session, bukan token yang ditanam di frontend. Pengaturan origin SPA dapat diatur melalui `SANCTUM_STATEFUL_DOMAINS` dan `CORS_ALLOWED_ORIGINS` pada `.env`.

Dokumentasi endpoint dan skema OpenAPI ada di [`docs/api.md`](docs/api.md) dan [`docs/openapi.yaml`](docs/openapi.yaml).

Jalankan pengujian dengan:

```bash
php artisan test
```

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework. You can also check out [Laravel Learn](https://laravel.com/learn), where you will be guided through building a modern Laravel application.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Redberry](https://redberry.international/laravel-development)**
- **[Active Logic](https://activelogic.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
