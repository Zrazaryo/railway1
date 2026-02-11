# Deploy ke Vercel

Panduan singkat agar aplikasi Arsip Loker bisa di-host di Vercel tanpa kendala.

## Yang sudah disiapkan

- **vercel.json** – Konfigurasi PHP runtime dan routing (semua request ke front controller kecuali `/assets`, `/uploads`, `/api/*`).
- **api/index.php** – Front controller yang meneruskan URL ke file PHP yang sesuai.
- **Session di database** – Di Vercel (serverless) session disimpan di MySQL, bukan file. Tabel `php_sessions` dipakai; dibuat otomatis saat pertama kali session dipakai, atau bisa dijalankan manual: `migrations/create_php_sessions_table.sql`.
- **Environment variables** – Database bisa dikonfigurasi lewat env (lihat bawah).

## Langkah deploy

### 1. Push ke Git (GitHub/GitLab/Bitbucket)

Pastikan proyek sudah di-repo dan push.

### 2. Import project di Vercel

1. Buka [vercel.com](https://vercel.com), login.
2. **Add New** → **Project**.
3. Import repo yang berisi proyek ini.
4. **Framework Preset**: pilih **Other** (bukan Next.js).
5. **Root Directory**: kosongkan (root repo).
6. Jangan ubah **Build Command** / **Output Directory** (biarkan default).

### 3. Environment variables

Di **Settings → Environment Variables** tambahkan variabel database. Pilih salah satu:

**Opsi A – Satu variabel (dari Railway)**  
- Name: `MYSQL_PUBLIC_URL`  
- Value: `mysql://root:password@host:port/railway`  
  (copy dari Railway → MySQL service → **Connect** → **Public URL**)

**Opsi B – Per komponen**  
- `DB_HOST` = host Railway (mis. `xxx.proxy.rlwy.net`)  
- `DB_PORT` = `49077` (atau port yang dipakai)  
- `DB_USER` = `root`  
- `DB_PASS` = password database  
- `DB_NAME` = `railway`

Lalu **Save** dan **Redeploy** project.

### 4. Tabel session (sekali saja)

Tabel session bisa dibuat otomatis saat pertama login. Jika ingin buat manual, jalankan di database Railway (MySQL client / phpMyAdmin):

```sql
-- Isi dari migrations/create_php_sessions_table.sql
CREATE TABLE IF NOT EXISTS php_sessions (
    id VARCHAR(128) PRIMARY KEY,
    data TEXT,
    last_activity INT UNSIGNED NOT NULL,
    INDEX idx_last_activity (last_activity)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### 5. Deploy

Klik **Deploy** (atau push ke branch yang terhubung). Setelah selesai, buka URL yang diberikan Vercel (mis. `https://nama-project.vercel.app`).

---

## Hal penting

### File upload

Di Vercel filesystem bersifat **read-only** dan **ephemeral**. File yang di-upload ke folder `uploads/` **tidak akan tersimpan permanen**. Untuk production yang butuh penyimpanan file tetap, gunakan penyimpanan eksternal (mis. **Vercel Blob**, **Cloudinary**, atau **S3**) dan sesuaikan kode upload untuk menyimpan ke sana.

### Static assets

`/assets/` dan `/uploads/` di-route sebagai static; pastikan file CSS/JS/gambar ada di repo agar bisa diakses.

### Cookie & HTTPS

Di Vercel, cookie session memakai `secure` (HTTPS). Tidak perlu ubah jika hanya akses lewat domain Vercel.

---

## Troubleshooting

- **500 / Koneksi database gagal**  
  Cek env (`MYSQL_PUBLIC_URL` atau `DB_*`) dan bahwa database Railway bisa diakses dari internet (public).

- **Login tidak persist / session hilang**  
  Pastikan tabel `php_sessions` sudah ada dan env database benar (session disimpan di MySQL).

- **404 untuk halaman**  
  Pastikan URL memakai path yang sama seperti di lokal (mis. `/landing.php`, `/auth/login.php`). Front controller di `api/index.php` memetakan path ke file PHP yang sesuai.

- **Upload gagal**  
  Di Vercel, menulis ke disk tidak persisten. Untuk production, integrasikan dengan Vercel Blob atau storage eksternal.
