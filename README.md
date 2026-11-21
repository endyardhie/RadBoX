# RadBoX

Panel manajemen hotspot berbasis PHP native, FreeRADIUS 3.x, dan MariaDB/MySQL untuk MikroTik.

**Author: Endy Ardhie**

## Fitur

- Multi-tenant dengan role Super Admin, Tenant Owner, dan Technician.
- Manajemen tenant, pengguna, router/NAS, dan profil hotspot.
- Pembuatan voucher satuan/bulk, template cetak, masa berlaku, dan batas uptime.
- Sinkronisasi NAS, profil, serta voucher ke tabel FreeRADIUS.
- Pengguna aktif, disconnect melalui CoA, dan laporan pendapatan.
- Backup, restore, dan import database khusus Super Admin.

## Kebutuhan

PHP 8.2+ dengan PDO MySQL dan mbstring, Nginx/PHP-FPM, MariaDB/MySQL, FreeRADIUS 3.x dengan modul SQL, serta cron. Backup/restore memerlukan `mysqldump`, `mysql`, dan gzip. Antarmuka memakai Tailwind, Alpine.js, dan Lucide melalui CDN; tidak memerlukan Composer atau npm.

## Instalasi

1. Pasang dependency mengikuti [catatan Ubuntu/Debian](deploy/ubuntu/install-notes.md), lalu letakkan project di `/var/www/radbox`.
2. Siapkan konfigurasi lokal:

   ```bash
   cd /var/www/radbox
   cp .env.example.php .env.php
   chmod 640 .env.php
   ```

   Isi URL, timezone, dan credential database milik Anda. Gunakan akun database khusus aplikasi, bukan root. Atur pemilik/grup `.env.php` agar hanya administrator dan proses PHP yang dapat membacanya. Aplikasi menolak konfigurasi yang belum diisi.

3. Buat database `radius` dan akun `radbox` dengan password unik serta hak akses hanya pada database tersebut. Import schema resmi FreeRADIUS dari paket distro terlebih dahulu, kemudian **satu file instalasi RadBoX**:

   ```bash
   mysql -h 127.0.0.1 -u radbox -p radius < /etc/freeradius/3.0/mods-config/sql/main/mysql/schema.sql
   mysql -h 127.0.0.1 -u radbox -p radius < database/schema.sql
   php bin/create-admin.php admin "Endy Ardhie"
   ```

   Sesuaikan lokasi schema FreeRADIUS dengan distro. Password admin diminta secara tersembunyi di terminal (12–72 byte); tidak ada akun/password bawaan maupun data demo. Perintah ini membuat akun baru dan tidak menimpa akun lama.

4. Gunakan [contoh Nginx](deploy/nginx/radbox.conf), sesuaikan domain dan socket PHP-FPM, lalu aktifkan HTTPS. Document root wajib `/var/www/radbox/public`. Berikan proses PHP akses tulis hanya ke `storage/`.
5. Hubungkan modul SQL FreeRADIUS ke database yang sama, aktifkan accounting, dan terapkan [policy tenant](deploy/freeradius/README.md). Gunakan RADIUS secret unik per router. Batasi UDP 1812/1813 dan CoA 3799 ke IP router; jangan buka database ke internet.
6. Pasang [cron sinkronisasi voucher](deploy/cron/radbox-cron.txt), lalu login dan buat tenant, router, profil, serta voucher. Verifikasi autentikasi dan accounting sebelum dipakai.

## Database

- `database/schema.sql`: skema lengkap untuk instalasi baru; tidak berisi credential atau data pengguna.
- `database/migrations/001-technician-role.sql`: upgrade khusus database lama yang belum memiliki role Technician dan tabel `rb_user_routers`. Tidak diperlukan setelah instalasi schema terbaru.

Schema FreeRADIUS tetap berasal dari paket resminya. Jangan mengimpor ulang schema instalasi untuk meng-upgrade database produksi. Buat backup sebelum menjalankan migrasi.

## Backup dan restore

```bash
php bin/backup.php light
php bin/backup.php full
php bin/backup.php app
php bin/restore.php /path/backup.sql.gz
```

Backup tersimpan di `storage/backups/`. File ini berisi data sensitif termasuk hash password, credential voucher, dan RADIUS secret; simpan dengan akses terbatas dan jangan unggah ke GitHub.

## Keamanan repository

`.gitignore` mengecualikan `.env.php`, log, backup/dump database, file key, dan konfigurasi lokal. Hanya `.env.example.php` tanpa credential aktif yang disertakan. Periksa kembali file yang akan di-commit sebelum menambahkan data baru.

Jika pernah memakai akun demo pada instalasi lama, hapus/nonaktifkan akun tersebut atau ganti password-nya; perubahan source tidak mengubah database yang sudah berjalan. Rotasi juga secret yang pernah terpublikasi.

## Struktur

```text
app/        Logika aplikasi
bin/        Pembuatan admin, sinkronisasi voucher, backup/restore
config/     Pemuat konfigurasi lokal
database/   Schema instalasi dan migrasi versi lama
deploy/     Contoh konfigurasi server dan FreeRADIUS
public/     Document root dan aset
storage/    Data runtime lokal (tidak dipublikasikan)
views/      Template antarmuka
```
