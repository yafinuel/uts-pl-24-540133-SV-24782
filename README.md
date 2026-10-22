# Toko Yafi — Sistem Manajemen Inventaris

Sistem manajemen inventaris untuk toko kecil. Backend Laravel 13 + FrankenPHP, frontend Vue 3 + shadcn-vue, database MySQL 8.4, semua berjalan di Docker Compose.

## Prasyarat

- Docker dengan Compose v2

## Menyiapkan dan Menjalankan

1. **Salin dan isi `.env`:**
   ```bash
   cp .env.example .env
   ```
   Buat `APP_KEY`:
   ```bash
   docker run --rm php:8.4-cli php -r "echo 'base64:'.base64_encode(random_bytes(32)).PHP_EOL;"
   ```
   Isi semua nilai kosong di `.env` (hindari karakter `$` dan spasi pada password).

2. **Jalankan sistem:**
   ```bash
   docker compose up -d --build
   ```
   Sistem akan otomatis menjalankan migrasi dan seeder saat pertama kali start.

3. **Akses:**
   - 🌐 Web: http://localhost:8080
   - 🔌 API: http://localhost:8000/api

## Kredensial Default

Login dengan email dan password yang diisi di `.env`:
- Email: nilai `ADMIN_EMAIL`
- Password: nilai `ADMIN_PASSWORD`

## Menghentikan

```bash
docker compose down          # data tetap ada
docker compose down -v       # reset semua data (termasuk database)
```

## Bukti FrankenPHP

```bash
docker compose logs backend
```
Cari baris `FrankenPHP started` dan log akses `http.log.access` yang berisi `"uri":"/api/..."`.

## Menjalankan Test Backend

```bash
cd backend
php artisan test
```

## Dev Frontend

Pastikan backend Docker sudah berjalan, lalu:
```bash
cd frontend
npm run dev
```
Frontend dev akan tersedia di http://localhost:5173 dengan proxy `/api` ke `http://localhost:8000`.

## Alur CI/CD

| Branch | Apa yang terjadi |
|---|---|
| `feature/**` | `backend-test` + `frontend-build` berjalan |
| `dev` (push/PR) | `backend-test` + `frontend-build` berjalan |
| `main` (push via PR dari `dev`) | + job `deploy` — build & push image ke ghcr.io dengan tag SHA commit |

Image tersedia di tab **Packages** repositori GitHub setelah deploy pertama.

## Aturan Git

Lihat `PRD.md` bagian 13. Ringkasan:
- Kerja di `feature/<nama>`, dibuat dari `dev`
- PR ke `dev`, lalu PR `dev` → `main`
- Tidak boleh push langsung ke `dev` atau `main`
- Commit mengikuti [Conventional Commits](https://www.conventionalcommits.org/en/v1.0.0/)
