# Toko Yafi — Sistem Manajemen Inventaris

Sistem manajemen inventaris untuk toko kecil. Backend Laravel + FrankenPHP, frontend Vue 3, database MySQL, semua berjalan di Docker Compose.

## Prasyarat

- Docker dengan Compose v2

## Menjalankan Sistem

1. Salin dan isi `.env`:
   ```bash
   cp .env.example .env
   ```
   Isi semua nilai kosong. Buat `APP_KEY`:
   ```bash
   docker run --rm php:8.4-cli php -r "echo 'base64:'.base64_encode(random_bytes(32)).PHP_EOL;"
   ```

2. Jalankan:
   ```bash
   docker compose up -d --build
   ```

3. Akses:
   - Web: http://localhost:8080
   - API: http://localhost:8000/api

## Menghentikan

```bash
docker compose down        # data tetap ada
docker compose down -v     # reset semua data
```

## Bukti FrankenPHP

```bash
docker compose logs backend
```
Cari baris `FrankenPHP started` dan log akses `http.log.access`.

## Menjalankan Test Backend

```bash
cd backend
php artisan test
```

## Dev Frontend (dengan backend Docker berjalan)

```bash
cd frontend
npm run dev
```

## Alur CI/CD

- Push ke `feature/**` atau `dev`: `backend-test` dan `frontend-build` berjalan
- Push ke `main` (via PR dari `dev`): tambah job `deploy` yang push image ke ghcr.io dengan tag SHA commit

## Aturan Git

Lihat `PRD.md` bagian 13.
