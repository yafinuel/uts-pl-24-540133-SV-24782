# PRD: Toko Yafi — Sistem Manajemen Inventaris (MVP)

> Dokumen ini ditujukan sebagai instruksi untuk agentic AI dan kontributor manusia. Ikuti spesifikasi persis seperti tertulis. Jangan menambah fitur, halaman, dependensi, service, atau lapisan arsitektur di luar yang tercantum.

## 1. Ringkasan

**Toko Yafi** adalah sistem manajemen inventaris untuk toko kecil. Terdiri dari:

- **Backend**: REST API Laravel, database MySQL, dilayani oleh FrankenPHP.
- **Frontend**: aplikasi web Vue.js dengan tema orange.
- **Infrastruktur**: seluruh sistem berjalan di Docker Compose dengan satu perintah. CI/CD memakai GitHub Actions dan image diunggah ke GitHub Container Registry (ghcr.io).

Admin toko login, lalu mengelola **Kategori** dan **Produk** dari browser. Stok dicatat langsung pada produk.

Semua kode berada dalam **satu repositori (monorepo)** dengan folder `backend/` dan `frontend/` di root.

## 2. Tujuan MVP

- Login dan logout.
- CRUD Kategori dan Produk (API + halaman web), data tersimpan di MySQL.
- Semua request/response berformat JSON dengan kode status HTTP yang sesuai; semua endpoint dapat diuji dengan Postman.
- Seluruh sistem berjalan dengan `docker compose up -d --build`, data bertahan setelah `docker compose down` lalu `up -d`.
- Pipeline GitHub Actions hijau: uji backend, build frontend, dan (hanya di `main`) build serta push image ke ghcr.io.

## 3. Di Luar Lingkup (JANGAN dibuat)

- Role/permission, multi-user, multi-toko; register, lupa password, halaman profil
- Riwayat/mutasi stok, supplier, transaksi penjualan, laporan
- Dashboard, statistik, grafik
- Upload gambar/file, export/import, cetak
- Queue, cache, event, listener, job, notifikasi, email, Redis
- Repository pattern, service layer, DTO, action class, interface abstraksi
- Soft delete, audit log, versioning API (`/v1`)
- Dark mode, multi-bahasa (i18n), mobile-first, PWA, SSR/Nuxt
- Library state management (Pinia/Vuex), data fetching (TanStack Query), form/validasi (vee-validate/zod/yup), axios
- Unit/e2e test frontend (frontend cukup lolos build dan diuji manual)
- Kubernetes, HTTPS/TLS/domain, Laravel Octane, FrankenPHP worker mode
- Deploy ke server/VPS: job `deploy` hanya membangun dan mengunggah image ke ghcr.io
- Animasi dekoratif, ilustrasi, hero section, landing page

## 4. Struktur Repositori

```
toko-yafi/
├── .github/
│   └── workflows/
│       └── ci.yml
├── backend/                      (Laravel, lihat 7.4)
├── frontend/                     (Vue, lihat 8.8)
├── docker-compose.yml
├── .env.example                  (contoh variabel untuk docker compose)
├── .gitignore                    (minimal berisi .env)
├── .gitattributes                (*.sh text eol=lf)
├── toko-yafi.postman_collection.json
├── README.md
└── PRD.md
```

Catatan: folder GitHub Actions yang benar adalah `.github/workflows/` (jamak). GitHub tidak membaca `.github/workflow/`.

`.gitattributes` berisi `*.sh text eol=lf` agar skrip shell tidak rusak oleh line ending CRLF di Windows.

## 5. Tech Stack

| Komponen | Pilihan |
|---|---|
| Backend framework | Laravel 13 (stabil terbaru, minimal PHP 8.3) |
| PHP | 8.4 (versi yang sama di Dockerfile dan CI) |
| Application server | FrankenPHP (menggantikan `php artisan serve` maupun php-fpm + nginx) |
| Database | MySQL 8.4 |
| Autentikasi | Laravel Sanctum (token Bearer) |
| Frontend | Vue 3, Composition API, `<script setup lang="ts">`, Vite, TypeScript, Vue Router |
| UI | shadcn-vue + Tailwind CSS, ikon lucide-vue-next, toast Sonner |
| HTTP client frontend | `fetch` bawaan browser |
| Web server frontend | Nginx (menyajikan hasil build dan meneruskan `/api` ke backend) |
| Container | Docker, Docker Compose v2 |
| CI/CD | GitHub Actions, GitHub Container Registry (ghcr.io) |
| Pengujian backend | PHPUnit bawaan Laravel (`php artisan test`) |
| Pengujian manual | Postman |
| Package manager frontend | npm (`package-lock.json` wajib di-commit) |

Hanya gunakan paket bawaan Laravel + Sanctum di backend. Di frontend, dependensi yang ikut terpasang oleh CLI shadcn-vue diperbolehkan; dependensi lain tidak boleh ditambah. `composer.lock` dan `package-lock.json` wajib di-commit.

## 6. Prinsip Kode (WAJIB)

Hindari "AI slop" dan overengineering, di kode maupun di konfigurasi:

**Umum**
1. Sederhana dan langsung. Tidak ada abstraksi "untuk masa depan", method kosong, kode mati, atau `TODO` placeholder.
2. Komentar hanya untuk hal yang tidak obvious. Dilarang docblock boilerplate yang mengulang nama method atau komentar yang mengulang isi kode.
3. Gunakan fitur bawaan framework daripada menulis ulang (hash password, validasi, paginasi, dll.).
4. Identifier dalam bahasa Inggris, pesan untuk pengguna dalam bahasa Indonesia.
5. Jangan mengarang endpoint, field, atau perilaku yang tidak ada di dokumen ini.

**Backend**
6. Logika ditaruh di controller selama masih pendek. Satu controller per resource, hanya method yang dipakai.
7. Validasi lewat Form Request, format output lewat API Resource, route model binding. Jangan mengembalikan model mentah.

**Frontend**
8. Logika fetch ditulis di view (ref + fungsi async). Jangan membuat composable/store kecuali dipakai di dua tempat atau lebih.
9. Setiap panggilan API menangani loading, error, dan sukses. Dilarang `catch` kosong. Hindari `any`.

**Infrastruktur**
10. Dockerfile, compose, dan workflow ditulis minimal: tanpa `ARG` yang tidak dipakai, tanpa langkah duplikat, tanpa blok yang dikomentari.

## 7. Backend

### 7.1 Model Data (MySQL)

**users**
| Kolom | Tipe | Catatan |
|---|---|---|
| id | bigint PK | |
| name | string | |
| email | string | unique |
| password | string | di-hash |
| timestamps | | |

**categories**
| Kolom | Tipe | Catatan |
|---|---|---|
| id | bigint PK | |
| name | string(100) | unique, wajib |
| timestamps | | |

**products**
| Kolom | Tipe | Catatan |
|---|---|---|
| id | bigint PK | |
| category_id | FK → categories.id | wajib, `restrictOnDelete`. Gunakan `foreignId()->constrained()` agar tipe kolom cocok di MySQL. |
| sku | string(50) | unique, wajib |
| name | string(150) | wajib |
| price | unsignedInteger | harga Rupiah bilangan bulat, min 0 |
| stock | unsignedInteger | default 0, min 0 |
| timestamps | | |

Relasi: `Category hasMany Product`, `Product belongsTo Category`. Tabel `personal_access_tokens` dibuat oleh Sanctum. Migrasi bawaan Laravel dibiarkan apa adanya.

### 7.2 Spesifikasi API

Base URL lokal (container backend): `http://localhost:8000/api`

Header wajib semua request: `Accept: application/json`, `Content-Type: application/json`. Endpoint terproteksi menambah `Authorization: Bearer {token}`.

#### Autentikasi

| Method | Endpoint | Auth | Deskripsi |
|---|---|---|---|
| POST | `/login` | Tidak | Login, mengembalikan token |
| POST | `/logout` | Ya | Menghapus token yang sedang dipakai |

Tidak ada endpoint register. Satu akun admin dibuat oleh seeder dari variabel environment (lihat 7.4 dan bagian 9).

**POST /login** — request:
```json
{ "email": "admin@tokoyafi.test", "password": "..." }
```
Respons `200 OK`:
```json
{
  "message": "Login berhasil",
  "data": {
    "token": "1|abcdef...",
    "user": { "id": 1, "name": "Admin Toko Yafi", "email": "admin@tokoyafi.test" }
  }
}
```
Kredensial salah → `401`: `{ "message": "Email atau password salah" }`. Validasi gagal → `422`.

**POST /logout** → `200`: `{ "message": "Logout berhasil" }`. Tanpa token / token tidak valid → `401`.

#### Kategori (semua terproteksi)

| Method | Endpoint | Deskripsi | Sukses |
|---|---|---|---|
| GET | `/categories` | Semua kategori, tanpa paginasi | 200 |
| POST | `/categories` | Buat kategori | 201 |
| GET | `/categories/{id}` | Detail | 200 |
| PUT | `/categories/{id}` | Ubah | 200 |
| DELETE | `/categories/{id}` | Hapus | 200 |

Body POST/PUT: `{ "name": "Minuman" }`. Validasi: `name` wajib, string, maks 100, unique (abaikan record sendiri saat PUT).

Respons item: `{ "data": { "id": 1, "name": "Minuman" } }`. Respons daftar: `{ "data": [ ... ] }`.

Hapus kategori yang masih punya produk → `409`: `{ "message": "Kategori masih memiliki produk dan tidak dapat dihapus" }`.

#### Produk (semua terproteksi)

| Method | Endpoint | Deskripsi | Sukses |
|---|---|---|---|
| GET | `/products` | Daftar produk (paginasi) | 200 |
| POST | `/products` | Buat produk | 201 |
| GET | `/products/{id}` | Detail | 200 |
| PUT | `/products/{id}` | Ubah | 200 |
| DELETE | `/products/{id}` | Hapus | 200 |

Body POST/PUT:
```json
{ "category_id": 1, "sku": "MNM-001", "name": "Teh Botol 450ml", "price": 5000, "stock": 24 }
```
Validasi:
- `category_id`: wajib, integer, exists di `categories`
- `sku`: wajib, string, maks 50, unique (abaikan record sendiri saat PUT)
- `name`: wajib, string, maks 150
- `price`: wajib, integer, min 0
- `stock`: wajib, integer, min 0

Query opsional `GET /products`: `category_id` (filter), `search` (pencarian parsial pada `name` atau `sku`), `page`. Paginasi 15 item per halaman memakai `paginate()` bawaan Laravel.

Respons daftar (format paginasi bawaan Laravel dengan API Resource):
```json
{
  "data": [
    { "id": 1, "sku": "MNM-001", "name": "Teh Botol 450ml", "price": 5000, "stock": 24,
      "category": { "id": 1, "name": "Minuman" } }
  ],
  "links": { },
  "meta": { "current_page": 1, "last_page": 3, "per_page": 15, "total": 38 }
}
```
Respons item (POST/PUT/GET detail): `{ "data": { ...item } }`.

#### Respons hapus
```json
{ "message": "Data berhasil dihapus" }
```

### 7.3 Kode Status HTTP

| Kode | Pemakaian |
|---|---|
| 200 | GET / PUT / DELETE / login / logout berhasil |
| 201 | POST berhasil membuat data |
| 401 | Belum login, token tidak valid, atau kredensial salah |
| 404 | Data tidak ditemukan |
| 409 | Kategori masih punya produk |
| 422 | Validasi gagal |

Format error validasi (default Laravel): `{ "message": "...", "errors": { "field": ["pesan"] } }`.

Error `401` dan `404` pada `/api/*` harus selalu berupa JSON, termasuk bila header `Accept` tidak dikirim. Atur di `bootstrap/app.php` (mis. `shouldRenderJsonWhen` untuk path `api/*`) agar tidak ada redirect ke route `login` yang tidak ada.

### 7.4 Struktur Berkas Backend

```
backend/
├── app/
│   ├── Http/
│   │   ├── Controllers/   AuthController, CategoryController, ProductController
│   │   ├── Requests/      LoginRequest, CategoryRequest, ProductRequest
│   │   └── Resources/     CategoryResource, ProductResource
│   └── Models/            Category, Product, User
├── bootstrap/app.php
├── database/
│   ├── factories/         CategoryFactory, ProductFactory (UserFactory bawaan)
│   ├── migrations/
│   └── seeders/DatabaseSeeder.php
├── routes/api.php
├── tests/Feature/         AuthTest, CategoryTest, ProductTest
├── Caddyfile
├── docker-entrypoint.sh
├── Dockerfile
├── .dockerignore
├── .env.example
├── composer.json
└── composer.lock
```

Jangan menambah folder lain (`Services`, `Repositories`, `Actions`, dll.).

**DatabaseSeeder** (idempotent, aman dijalankan setiap container start):
- Membuat akun admin dengan `firstOrCreate` berdasarkan `ADMIN_EMAIL`; nama dari `ADMIN_NAME`, password dari `ADMIN_PASSWORD` (di-hash). Nilai dibaca dari environment, tidak ditulis di kode. Seeder gagal dengan jelas bila `ADMIN_PASSWORD` kosong.
- Data contoh (3 kategori, sekitar 8 produk) hanya dibuat **ketika admin baru dibuat**, yaitu pada start pertama. Dengan begitu data yang dihapus pengguna tidak muncul lagi setelah container restart.
- Data contoh ditulis eksplisit. Seeder tidak boleh memakai Factory/Faker karena paket dev tidak ada di image produksi.

### 7.5 Pengujian Backend

Feature test dengan `RefreshDatabase`, memakai SQLite in-memory (default `phpunit.xml`) sehingga CI tidak membutuhkan service MySQL. Test tidak memanggil `DatabaseSeeder`; autentikasi memakai `Sanctum::actingAs` dengan `User::factory()`.

Cakupan minimal, satu test atau lebih untuk tiap poin:
- Login sukses (200 + token), login salah (401), login tanpa field (422)
- Logout menghapus token dari tabel `personal_access_tokens`
- Endpoint kategori dan produk menolak akses tanpa token (401)
- CRUD kategori dan CRUD produk dengan kode status sesuai 7.3
- Validasi: field wajib, `sku`/`name` unique, `category_id` tidak valid (422)
- Hapus kategori yang punya produk (409)
- ID tidak ada (404 JSON)
- `GET /products` dengan filter `category_id`, `search`, dan paginasi

## 8. Frontend

Frontend memakai kontrak API pada bagian 7.2. Base path request selalu relatif: `/api`. Di Docker, Nginx meneruskan `/api` ke service `backend`; di mode dev, proxy Vite meneruskan `/api` ke `http://localhost:8000` (container backend yang sedang berjalan). Dengan begitu tidak ada alamat IP/host backend di kode frontend dan CORS tidak diperlukan.

### 8.1 Routing dan Autentikasi

| Path | Halaman | Akses |
|---|---|---|
| `/login` | LoginView | Hanya tamu; jika sudah login → `/products` |
| `/` | — | Redirect ke `/products` |
| `/products` | ProductsView | Wajib login |
| `/categories` | CategoriesView | Wajib login |

Satu `beforeEach` guard sederhana: tanpa token → `/login`. Rute tidak dikenal → `/products`.

- Token dan data user disimpan di `localStorage` (risiko XSS diterima untuk MVP). Tidak ada endpoint "me"; nama user diambil dari data saat login.
- Logout: panggil `POST /logout`, lalu hapus token dan arahkan ke `/login` **apa pun hasil request-nya**.
- Klien API tipis di `src/lib/api.ts`: menambah header dan token, melempar error terstruktur (`status`, `message`, `errors`). Respons `401` selain pada `/login` menghapus token dan mengarahkan ke `/login`. Tanpa interceptor berlapis atau retry otomatis.

### 8.2 Layout (halaman wajib login)

Satu layout dengan top bar:
- Kiri: "Toko Yafi" (aksen orange) dan tautan **Produk**, **Kategori**; tautan aktif diberi penanda orange.
- Kanan: nama user dan tombol **Keluar**.
- Konten di bawahnya, maks sekitar `max-w-6xl`, di tengah. Tanpa sidebar.

### 8.3 Halaman Login

- Card di tengah layar: judul "Masuk ke Toko Yafi", input Email, input Password, tombol "Masuk".
- Tombol nonaktif dan berteks "Memproses..." saat request berjalan.
- 401: pesan "Email atau password salah" di atas form. 422: pesan per field.
- Sukses: simpan token, arahkan ke `/products`.

### 8.4 Halaman Produk

- Judul "Produk" dan tombol **Tambah Produk**.
- Toolbar: input pencarian (nama/SKU, debounce 300 ms tanpa library) dan dropdown filter kategori. Opsi "Semua kategori" memakai value `all` karena Select tidak menerima value kosong; nilai `all` tidak dikirim ke API.
- Tabel: **SKU**, **Nama**, **Kategori**, **Harga**, **Stok**, **Aksi** (Ubah, Hapus).
  - Harga: `Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 })`.
  - Stok `0` tampil sebagai badge "Habis".
- Paginasi: tombol Sebelumnya/Berikutnya dan teks "Halaman X dari Y" (dari `meta`). Mengubah pencarian atau filter kembali ke halaman 1.
- Tambah/Ubah: dialog dengan field Kategori (select dari `GET /categories`), SKU, Nama, Harga (number, min 0), Stok (number, min 0). Harga dan stok dikirim sebagai integer. Tambah: stok awal `0`. Ubah: form terisi data produk. Tombol simpan nonaktif saat request berjalan. Sukses: dialog tertutup, toast "Produk berhasil ditambahkan" / "Produk berhasil diperbarui", daftar dimuat ulang.
- Hapus: dialog konfirmasi "Hapus produk {nama}?" → `DELETE` → toast "Produk berhasil dihapus" → muat ulang. Jika halaman jadi kosong dan bukan halaman 1, mundur satu halaman.
- State: loading (skeleton baris tabel); kosong tanpa filter ("Belum ada produk" + tombol Tambah Produk); kosong dengan filter/pencarian ("Tidak ada produk yang cocok"); gagal memuat (pesan + tombol "Coba lagi").

### 8.5 Halaman Kategori

- Judul "Kategori" dan tombol **Tambah Kategori**.
- Tabel: **Nama**, **Aksi** (Ubah, Hapus). Tanpa paginasi.
- Tambah/Ubah: dialog dengan satu field Nama; perilaku sukses dan error sama seperti Produk.
- Hapus: dialog konfirmasi. Bila API membalas `409`, tampilkan pesan dari API di toast dan biarkan data tetap ada.
- State loading, kosong ("Belum ada kategori"), dan gagal memuat sama seperti Produk.

### 8.6 Penanganan Error di UI

| Status | Penanganan |
|---|---|
| 401 | Saat login: pesan di form. Halaman lain: hapus token, arahkan ke `/login`. |
| 404 | Toast error dengan pesan dari API. |
| 409 | Toast error dengan pesan dari API, apa adanya. |
| 422 | Pesan pertama tiap field ditampilkan di bawah input terkait. |
| 5xx / jaringan gagal | Pesan umum: "Terjadi kesalahan. Coba lagi." |

### 8.7 Desain

**Warna (base orange).** Pilih base color **stone** saat inisialisasi shadcn-vue, lalu timpa variabel tema:

| Token | Nilai | Pemakaian |
|---|---|---|
| `primary` | orange-600 `#ea580c` | Tombol utama, penanda navigasi aktif |
| primary hover/active | orange-700 `#c2410c` | Hover tombol utama |
| `primary-foreground` | `#ffffff` | Teks di atas primary |
| `accent` | orange-50 `#fff7ed` | Hover baris tabel, latar item aktif |
| `ring` | orange-500 `#f97316` | Focus ring |
| `destructive` | bawaan shadcn (merah) | Aksi hapus, badge "Habis" |
| `background` / `foreground` | putih / stone-900 | Halaman dan teks |

- Orange dipakai hemat: tombol utama, navigasi aktif, nama aplikasi, focus ring. Jangan mewarnai seluruh latar.
- Teks atau ikon orange di atas latar putih memakai orange-700 agar kontras terbaca; orange-500 hanya untuk aksen non-teks.
- Warna ditulis sebagai variabel tema (CSS variables) sesuai konvensi shadcn-vue, bukan hex yang tersebar di komponen.

**Aturan visual (hindari AI slop).**
- Tanpa gradien, glassmorphism, bayangan berlebih, atau ikon emoji. Ikon hanya dari lucide dan hanya bila membantu.
- Tanpa teks "Lorem ipsum" atau salinan marketing. Semua teks UI berbahasa Indonesia, singkat, fungsional.
- Spasi dan ukuran font konsisten memakai skala Tailwind bawaan.
- Target browser desktop; layout tidak pecah hingga lebar 768 px; tabel boleh scroll horizontal di layar sempit.
- Pakai komponen shadcn-vue apa adanya, tanpa pembungkus tambahan tanpa alasan jelas.

**Komponen shadcn-vue yang boleh dipasang:** `button`, `input`, `label`, `select`, `table`, `dialog`, `alert-dialog`, `badge`, `sonner`, `skeleton`, `card`. Form memakai `Input` + `Label` + teks error biasa; jangan memakai komponen Form berbasis vee-validate/zod.

Instalasi Tailwind dan shadcn-vue mengikuti dokumentasi resmi (https://www.shadcn-vue.com, bagian instalasi Vite), bukan dari ingatan.

### 8.8 Struktur Berkas Frontend

```
frontend/
├── src/
│   ├── main.ts
│   ├── App.vue                (RouterView + Toaster)
│   ├── types.ts               (Category, Product, Paginated<T>)
│   ├── assets/main.css        (Tailwind + variabel tema orange)
│   ├── router/index.ts
│   ├── lib/
│   │   ├── api.ts
│   │   ├── auth.ts
│   │   ├── format.ts          (formatRupiah)
│   │   └── utils.ts           (dibuat shadcn-vue)
│   ├── views/                 LoginView, ProductsView, CategoriesView
│   └── components/
│       ├── AppLayout.vue
│       ├── ConfirmDialog.vue
│       ├── ProductFormDialog.vue
│       ├── CategoryFormDialog.vue
│       └── ui/                (dihasilkan CLI shadcn-vue)
├── Dockerfile
├── nginx.conf
├── .dockerignore
├── package.json
├── package-lock.json
└── vite.config.ts             (proxy dev: /api → http://localhost:8000)
```

Route wajib login berada sebagai child dari `AppLayout`. Jangan menambah folder lain (`stores`, `services`, `composables`, dll.).

## 9. Konfigurasi dan Rahasia

Aturan:
1. **Tidak ada nilai rahasia di repositori**: kode, Dockerfile, `docker-compose.yml`, workflow, koleksi Postman, README. Rahasia meliputi password, `APP_KEY`, dan token.
2. Docker Compose membaca berkas **`.env` di root** yang **tidak di-commit** (masuk `.gitignore`). Contohnya disertakan sebagai **`.env.example`** (di-commit) dengan nilai rahasia dikosongkan.
3. Dockerfile tidak boleh memuat `ENV` atau `ARG` berisi nilai rahasia. Semua nilai masuk lewat environment saat container dijalankan.
4. Nilai rahasia untuk CI/CD disimpan di **GitHub Secrets**, tidak pernah di berkas workflow.
5. Koleksi Postman tidak memuat password; gunakan variabel koleksi yang dikosongkan.

**`.env.example` (root)**
```
# Database (dipakai service db dan backend). DB_USERNAME tidak boleh root.
DB_DATABASE=toko_yafi
DB_USERNAME=toko_yafi
DB_PASSWORD=
DB_ROOT_PASSWORD=

# Kunci aplikasi Laravel, format base64:...
APP_KEY=

# Akun admin awal, dibuat seeder saat pertama kali berjalan
ADMIN_NAME=Admin Toko Yafi
ADMIN_EMAIL=admin@tokoyafi.test
ADMIN_PASSWORD=
```

Cara menyiapkan: `cp .env.example .env`, isi semua nilai kosong. Hindari karakter `$` dan spasi pada password (Compose menginterpretasikan `$`). Membuat `APP_KEY`:
```
echo "base64:$(openssl rand -base64 32)"
# tanpa openssl:
docker run --rm php:8.4-cli php -r "echo 'base64:'.base64_encode(random_bytes(32)).PHP_EOL;"
```

**`backend/.env.example`** berisi variabel Laravel yang dipakai (`APP_NAME`, `APP_ENV`, `APP_KEY`, `APP_DEBUG`, `APP_URL`, `DB_*` untuk MySQL, `SESSION_DRIVER=file`, `CACHE_STORE=file`, `QUEUE_CONNECTION=sync`, `ADMIN_*`) tanpa nilai rahasia. Dipakai untuk pengembangan di luar Docker dan disalin oleh CI.

**GitHub Secrets** (Settings → Secrets and variables → Actions): daftarkan `APP_KEY`, `DB_PASSWORD`, `DB_ROOT_PASSWORD`, dan `ADMIN_PASSWORD` sebagai satu-satunya tempat nilai ini di sisi GitHub. Workflow MVP ini sendiri tidak membutuhkannya: uji backend memakai SQLite in-memory dengan key sementara dari `key:generate`, dan job `deploy` memakai `GITHUB_TOKEN` bawaan. Bila suatu step nanti membutuhkan nilai rahasia, wajib dibaca lewat `${{ secrets.NAMA }}`, tidak pernah ditulis literal.

## 10. Docker

### 10.1 Dockerfile Backend (FrankenPHP)

Lokasi: `backend/Dockerfile`, build context `backend/`.

Persyaratan:
- Base image `dunglas/frankenphp` dengan **tag versi yang jelas, bukan `latest`**. Pola tag: `<versi-frankenphp>-php<versi-php>-<os>`, misalnya bentuk `dunglas/frankenphp:1.9-php8.4-bookworm`. Agen wajib memastikan tag benar-benar ada di Docker Hub (mis. `docker pull`) sebelum dipakai, dengan PHP 8.4.
- `composer.json` dan `composer.lock` disalin dan dipasang **lebih dulu** (`composer install --no-dev --no-scripts --no-autoloader`), baru kemudian seluruh source disalin, agar cache layer bekerja. Setelah source disalin, jalankan `composer dump-autoload --optimize --no-dev`.
- Ekstensi PHP tambahan dipasang dengan `install-php-extensions` (tersedia di base image): minimal `pdo_mysql` dan `zip`.
- Composer diambil dari image `composer:2` lewat `COPY --from`.
- FrankenPHP adalah satu-satunya server aplikasi. Dilarang `php artisan serve`, php-fpm, maupun nginx di container backend.
- Tidak ada nilai rahasia di `ENV`/`ARG`.
- Pastikan direktori `storage/framework/{cache,sessions,views}`, `storage/logs`, dan `bootstrap/cache` ada di image (`mkdir -p`).
- `ENTRYPOINT` menjalankan skrip, `CMD` harus ditulis eksplisit (mengubah `ENTRYPOINT` mengosongkan `CMD` bawaan image).

Kerangka acuan:
```dockerfile
FROM dunglas/frankenphp:<versi-frankenphp>-php8.4-bookworm

RUN install-php-extensions pdo_mysql zip
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
ENV COMPOSER_ALLOW_SUPERUSER=1

WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction

COPY . .
RUN composer dump-autoload --optimize --no-dev \
    && mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && cp Caddyfile /etc/frankenphp/Caddyfile \
    && chmod +x docker-entrypoint.sh

EXPOSE 80
ENTRYPOINT ["/app/docker-entrypoint.sh"]
CMD ["frankenphp", "run", "--config", "/etc/frankenphp/Caddyfile", "--adapter", "caddyfile"]
```

**`backend/Caddyfile`** (memastikan akses log tercetak ke stdout/stderr container sehingga bukti FrankenPHP terlihat di `docker compose logs`):
```
{
	frankenphp
}

:80 {
	root * /app/public
	encode zstd gzip
	log
	php_server
}
```

**`backend/docker-entrypoint.sh`**:
```sh
#!/bin/sh
set -e
php artisan migrate --force
php artisan db:seed --force
exec "$@"
```

### 10.2 Dockerfile Frontend dan Nginx

Lokasi: `frontend/Dockerfile`, build context `frontend/`. **Multi-stage**:
1. Tahap `build`: image Node.js dengan versi jelas (mis. `node:24-alpine`, sama dengan versi di CI). Salin `package.json` dan `package-lock.json`, `npm ci`, salin source, `npm run build`.
2. Tahap akhir: image Nginx dengan versi jelas (mis. `nginx:1.28-alpine`, pastikan tag ada), hanya menyalin `dist` hasil build ke `/usr/share/nginx/html` dan `nginx.conf`. **Image akhir tidak boleh berisi `node_modules`** (maupun Node.js).

```dockerfile
FROM node:24-alpine AS build
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
COPY . .
RUN npm run build

FROM nginx:<versi>-alpine
COPY nginx.conf /etc/nginx/conf.d/default.conf
COPY --from=build /app/dist /usr/share/nginx/html
EXPOSE 80
```

**`frontend/nginx.conf`**: menyajikan SPA dan meneruskan `/api` ke backend lewat **nama service** `backend`. `resolver` Docker dan variabel membuat Nginx me-resolve ulang DNS sehingga tidak 502 ketika container backend dibuat ulang dan IP-nya berubah.
```nginx
server {
    listen 80;
    root /usr/share/nginx/html;
    index index.html;

    resolver 127.0.0.11 valid=10s;

    location /api/ {
        set $backend http://backend:80;
        proxy_pass $backend;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }

    location / {
        try_files $uri $uri/ /index.html;
    }
}
```

### 10.3 `.dockerignore`

Satu berkas di masing-masing folder, karena build context adalah folder tersebut.

`backend/.dockerignore` minimal mengecualikan: `.git`, `vendor`, `node_modules`, `.env`, `.env.*`, `storage/logs`, `database/*.sqlite`, `tests`, `Dockerfile`, `.dockerignore`.

`frontend/.dockerignore` minimal mengecualikan: `.git`, `node_modules`, `dist`, `.env`, `.env.*`, `Dockerfile`, `.dockerignore`.

### 10.4 `docker-compose.yml` (root)

Tiga service: `db` (MySQL), `backend` (FrankenPHP), `frontend` (Nginx). Semuanya berkomunikasi lewat jaringan Compose bawaan memakai **nama service** (`db`, `backend`), bukan IP, `localhost`, maupun `host.docker.internal`. Port database tidak dipublikasikan ke host.

```yaml
services:
  db:
    image: mysql:8.4
    restart: unless-stopped
    environment:
      MYSQL_DATABASE: ${DB_DATABASE:?DB_DATABASE belum diisi di .env}
      MYSQL_USER: ${DB_USERNAME:?DB_USERNAME belum diisi di .env}
      MYSQL_PASSWORD: ${DB_PASSWORD:?DB_PASSWORD belum diisi di .env}
      MYSQL_ROOT_PASSWORD: ${DB_ROOT_PASSWORD:?DB_ROOT_PASSWORD belum diisi di .env}
    volumes:
      - mysql_data:/var/lib/mysql
    healthcheck:
      test: ['CMD-SHELL', 'mysqladmin ping -h 127.0.0.1 -u root -p"$$MYSQL_ROOT_PASSWORD" --silent']
      interval: 5s
      timeout: 5s
      retries: 20
      start_period: 20s

  backend:
    build: ./backend
    restart: unless-stopped
    depends_on:
      db:
        condition: service_healthy
    environment:
      APP_ENV: production
      APP_DEBUG: "false"
      APP_KEY: ${APP_KEY:?APP_KEY belum diisi di .env}
      LOG_CHANNEL: stderr
      SESSION_DRIVER: file
      CACHE_STORE: file
      QUEUE_CONNECTION: sync
      DB_CONNECTION: mysql
      DB_HOST: db
      DB_PORT: "3306"
      DB_DATABASE: ${DB_DATABASE}
      DB_USERNAME: ${DB_USERNAME}
      DB_PASSWORD: ${DB_PASSWORD}
      ADMIN_NAME: ${ADMIN_NAME:-Admin Toko Yafi}
      ADMIN_EMAIL: ${ADMIN_EMAIL:?ADMIN_EMAIL belum diisi di .env}
      ADMIN_PASSWORD: ${ADMIN_PASSWORD:?ADMIN_PASSWORD belum diisi di .env}
    ports:
      - "8000:80"

  frontend:
    build: ./frontend
    restart: unless-stopped
    depends_on:
      - backend
    ports:
      - "8080:80"

volumes:
  mysql_data:
```

Ketentuan:
- Seluruh sistem naik dengan `docker compose up -d --build`. Tanpa langkah manual lain setelah `.env` diisi.
- Data MySQL ada di **named volume** `mysql_data`. `docker compose down` lalu `docker compose up -d` tidak menghilangkan data. Jangan memakai `down -v` kecuali memang ingin mereset data.
- Backend (port host 8000) dipakai untuk Postman; frontend (port host 8080) dipakai di browser. Browser hanya berbicara dengan Nginx; Nginx yang berbicara dengan backend lewat nama service.
- Backend menjalankan migrasi dan seeder idempotent saat start (lihat entrypoint), sehingga data tidak diinisialisasi ulang.
- MySQL hanya menerapkan `MYSQL_*` saat volume masih kosong. Mengubah `DB_PASSWORD` di `.env` setelah volume terbentuk tidak mengubah password di database; reset dengan `docker compose down -v` bila perlu.

## 11. CI/CD (GitHub Actions)

Berkas: `.github/workflows/ci.yml`. Satu workflow bernama `CI/CD` dengan tiga job.

| Job (nilai `name:`) | Isi | Berjalan di |
|---|---|---|
| `backend-test` | Pasang PHP 8.4 dan dependensi, siapkan `.env` dari `backend/.env.example`, `php artisan key:generate`, `php artisan test` | Semua event |
| `frontend-build` | Pasang Node 24, `npm ci`, `npm run build` (di folder `frontend`) | Semua event |
| `deploy` | Build image backend dan frontend, push ke ghcr.io | **Hanya push ke `main`** |

Aturan:
- Trigger: `push` ke `main`, `dev`, dan `feature/**`, serta `pull_request` ke `dev` dan `main`. Push ke branch yang punya PR terbuka akan menjalankan workflow dua kali; itu diterima.
- `deploy`: `needs: [backend-test, frontend-build]` dan `if: github.event_name == 'push' && github.ref == 'refs/heads/main'`. Pada PR, branch `dev`, dan branch fitur, workflow berhenti setelah dua job pertama; job `deploy` tampil *skipped* dan workflow tetap hijau.
- Tag image memakai **SHA commit** (`${{ github.sha }}`). Dilarang tag `latest`.
- Nama image: `ghcr.io/<owner>/<repo>-backend:<sha>` dan `ghcr.io/<owner>/<repo>-frontend:<sha>`. Owner dan repo harus huruf kecil, jadi awalan dihitung dengan `${GITHUB_REPOSITORY,,}`.
- Login ke ghcr.io memakai `GITHUB_TOKEN` bawaan. `permissions` tingkat workflow `contents: read`; job `deploy` menambah `packages: write`.
- Beri label `org.opencontainers.image.source` pada image agar package terhubung ke repositori.
- Pin action ke versi mayor (mis. `@v4`), jangan `@main`/`@master`. Runner dipin ke `ubuntu-24.04`.
- Jangan memakai filter `paths`, agar status check selalu terlapor untuk PR.
- Tidak ada nilai rahasia literal di workflow (lihat bagian 9).
- Versi PHP dan Node di workflow harus sama dengan di Dockerfile.

Kerangka acuan:
```yaml
name: CI/CD

on:
  push:
    branches: [main, dev, 'feature/**']
  pull_request:
    branches: [dev, main]

permissions:
  contents: read

jobs:
  backend-test:
    name: backend-test
    runs-on: ubuntu-24.04
    defaults:
      run:
        working-directory: backend
    steps:
      - uses: actions/checkout@v4
      - uses: shivammathur/setup-php@v2
        with:
          php-version: '8.4'
          extensions: mbstring, pdo_sqlite, sqlite3
          coverage: none
      - run: composer install --prefer-dist --no-interaction --no-progress
      - run: cp .env.example .env && php artisan key:generate
      - run: php artisan test
        env:
          DB_CONNECTION: sqlite
          DB_DATABASE: ':memory:'

  frontend-build:
    name: frontend-build
    runs-on: ubuntu-24.04
    defaults:
      run:
        working-directory: frontend
    steps:
      - uses: actions/checkout@v4
      - uses: actions/setup-node@v4
        with:
          node-version: 24
          cache: npm
          cache-dependency-path: frontend/package-lock.json
      - run: npm ci
      - run: npm run build

  deploy:
    name: deploy
    needs: [backend-test, frontend-build]
    if: github.event_name == 'push' && github.ref == 'refs/heads/main'
    runs-on: ubuntu-24.04
    permissions:
      contents: read
      packages: write
    steps:
      - uses: actions/checkout@v4
      - run: echo "IMAGE_PREFIX=ghcr.io/${GITHUB_REPOSITORY,,}" >> "$GITHUB_ENV"
      - uses: docker/login-action@v3
        with:
          registry: ghcr.io
          username: ${{ github.actor }}
          password: ${{ secrets.GITHUB_TOKEN }}
      - uses: docker/build-push-action@v6
        with:
          context: ./backend
          push: true
          tags: ${{ env.IMAGE_PREFIX }}-backend:${{ github.sha }}
          labels: org.opencontainers.image.source=${{ github.server_url }}/${{ github.repository }}
      - uses: docker/build-push-action@v6
        with:
          context: ./frontend
          push: true
          tags: ${{ env.IMAGE_PREFIX }}-frontend:${{ github.sha }}
          labels: org.opencontainers.image.source=${{ github.server_url }}/${{ github.repository }}
```

Kerangka ini adalah acuan. Agen wajib menjalankan workflow sampai hijau di tab Actions dan menyesuaikan bila ada yang gagal.

## 12. Pengaturan Repositori GitHub (manual)

Dilakukan oleh pemilik repo di GitHub, bukan lewat kode:

1. **Perlindungan branch** untuk `main` dan `dev` (Settings → Branches, atau Rules → Rulesets):
   - Require a pull request before merging (tidak boleh push langsung).
   - Require status checks to pass before merging, lalu pilih `backend-test` dan `frontend-build`. Nama check baru bisa dipilih setelah workflow berjalan minimal sekali. Jangan menjadikan `deploy` sebagai required check.
   - Block force pushes dan deletions.
   - Aktifkan agar aturan juga berlaku untuk admin (tanpa bypass).
   - Catatan: pada repo privat paket gratis, fitur ini bisa tidak tersedia; repo publik mendukungnya.
2. **Secrets** (lihat bagian 9): daftarkan di Settings → Secrets and variables → Actions.
3. **Packages**: setelah deploy pertama di `main`, kedua image muncul di tab Packages repositori. Visibilitas default privat; ubah di pengaturan package bila perlu.

## 13. Aturan Git

Berlaku untuk semua kontributor, baik manusia maupun agentic AI. Backend dan frontend berada dalam satu repositori.

### 13.1 Alur Branch

```
feature/<sesuatu>  --PR-->  dev  --PR-->  main
     kerja harian          integrasi     rilis / dinilai
```

| Branch | Fungsi |
|---|---|
| `feature/<sesuatu>` | Tempat kerja harian. Satu branch untuk satu pekerjaan. |
| `dev` | Integrasi hasil kerja dari semua branch feature. |
| `main` | Rilis / versi yang dinilai. Harus selalu dalam keadaan berjalan. Merge ke `main` memicu job `deploy`. |

Aturan:
- Dilarang push langsung ke `dev` dan `main`. Semua perubahan masuk lewat Pull Request (PR) dan status check harus lulus (lihat bagian 12).
- Branch `feature/*` dibuat dari `dev` dan di-merge kembali ke `dev` lewat PR.
- `main` hanya diperbarui lewat PR dari `dev`.
- Nama branch: huruf kecil, kata dipisah tanda hubung. Contoh: `feature/backend-auth`, `feature/frontend-product-page`, `feature/docker-compose`, `feature/ci-workflow`.
- Hapus branch feature setelah PR di-merge.

### 13.2 Pesan Commit

Mengikuti [Conventional Commits 1.0.0](https://www.conventionalcommits.org/en/v1.0.0/).

Format:
```
<type>[scope opsional]: <deskripsi>

[body opsional]

[footer opsional]
```

| Type | Pemakaian |
|---|---|
| `feat` | Fitur baru |
| `fix` | Perbaikan bug |
| `test` | Menambah atau memperbaiki pengujian |
| `ci` | Perubahan pada pipeline CI |
| `docs` | Dokumentasi |
| `chore` | Pekerjaan rumah tangga (konfigurasi, dependensi) |
| `refactor` | Ubah struktur kode tanpa mengubah perilaku |

Aturan penulisan:
- Deskripsi dalam bahasa Indonesia, kalimat perintah, huruf kecil di awal, tanpa titik di akhir, maksimal 72 karakter.
- Scope opsional: `backend`, `frontend`, `docker`, `ci`, atau area fitur seperti `auth`, `product`, `category`.
- Satu commit berisi satu perubahan logis. Jangan menggabungkan perubahan yang tidak berkaitan.
- Perubahan yang merusak kompatibilitas ditandai `!` setelah type/scope atau footer `BREAKING CHANGE: ...`.

Contoh:
```
chore: inisialisasi monorepo dengan folder backend dan frontend
feat(auth): tambah endpoint login dan logout
feat(product): tambah crud produk dengan filter kategori
test(product): tambah pengujian validasi sku unik
fix(category): kembalikan 409 saat kategori masih punya produk
feat(frontend): tambah halaman produk dengan paginasi
feat(docker): tambah dockerfile backend dengan frankenphp
feat(docker): tambah docker-compose dengan mysql dan named volume
ci: tambah workflow uji backend, build frontend, dan push image ke ghcr
docs: tambah cara menjalankan sistem di readme
```

### 13.3 Pull Request

- Judul PR mengikuti format Conventional Commits, mis. `feat(product): tambah crud produk`.
- Deskripsi PR singkat: apa yang berubah dan cara mengujinya.
- Sebelum membuka PR: `php artisan test` lolos (bila backend berubah), `npm run build` lolos (bila frontend berubah), dan `docker compose up -d --build` berjalan (bila Docker berubah).
- PR ke `dev` dan PR ke `main` sama-sama wajib, tanpa pengecualian.

### 13.4 Ketentuan Tambahan untuk Agentic AI

- Selalu bekerja di branch `feature/*`. Jangan pernah commit langsung ke `dev` atau `main`.
- Jangan `git push --force` dan jangan menulis ulang riwayat pada `dev` atau `main`.
- Jangan meng-commit `.env`, `vendor/`, `node_modules/`, `dist/`, atau file SQLite. Pastikan semuanya ada di `.gitignore`.
- Jangan membuat commit gabungan besar di akhir pekerjaan. Commit bertahap mengikuti langkah pada bagian 14.

## 14. Langkah Implementasi

Setiap tahap dikerjakan di branch `feature/*` sendiri dan masuk lewat PR.

1. **Inisialisasi repo**: struktur folder (bagian 4), `.gitignore`, `.gitattributes`, `.env.example`.
2. **Backend**: buat proyek Laravel di `backend/`, pasang Sanctum (`php artisan install:api`), migrasi, model, factory, seeder, controller, Form Request, API Resource, route, penanganan error JSON.
3. **Pengujian backend**: tulis feature test (7.5) sampai `php artisan test` hijau.
4. **Frontend**: buat proyek Vite Vue TS di `frontend/`, pasang Tailwind dan shadcn-vue, tema orange, `lib/`, router, `AppLayout`, `LoginView`, `CategoriesView`, `ProductsView`. Pastikan `npm run build` lolos.
5. **Docker**: `Caddyfile`, `docker-entrypoint.sh`, `backend/Dockerfile`, `frontend/Dockerfile`, `nginx.conf`, dua `.dockerignore`, `docker-compose.yml`. Uji `docker compose up -d --build` secara lokal sampai semua kriteria Docker di bagian 15 terpenuhi.
6. **CI/CD**: tulis `.github/workflows/ci.yml`, push dari branch fitur, pastikan `backend-test` dan `frontend-build` hijau dan `deploy` tidak jalan.
7. **Integrasi**: PR ke `dev` (workflow hijau, tanpa deploy), lalu PR `dev` → `main` (workflow hijau, `deploy` jalan, dua image muncul di Packages).
8. **Dokumentasi**: koleksi Postman dan README.

## 15. Pembuktian dan Kriteria Penerimaan

### 15.1 Cara pembuktian

| Yang dibuktikan | Perintah / cara | Hasil yang diharapkan |
|---|---|---|
| Sistem naik dengan satu perintah | `docker compose up -d --build` lalu `docker compose ps` | `db`, `backend`, `frontend` berjalan; `db` healthy |
| API dilayani FrankenPHP | `docker compose logs backend` setelah memanggil API | Ada baris `FrankenPHP started` saat start dan log akses JSON (`http.log.access`) berisi `"uri":"/api/..."` |
| Header server | `curl -i http://localhost:8000/api/products` | `401` JSON dan header `Server: Caddy` |
| Proses di container | `docker compose top backend` | Proses `frankenphp run`; tanpa `php-fpm`, `nginx`, atau `artisan serve` |
| Backend → DB lewat nama service | `docker compose exec backend printenv DB_HOST` | `db` |
| Frontend → backend lewat nama service | Isi `nginx.conf` | `proxy_pass` ke `http://backend:80`; tanpa IP |
| Alur UI end-to-end | Buka `http://localhost:8080`, login, tambah/ubah/hapus produk dan kategori | Berfungsi; data dari Laravel dan tersimpan di MySQL |
| Data persisten | Buat data lewat UI, `docker compose down`, `docker compose up -d` | Data yang dibuat masih ada (tidak memakai `-v`) |
| Tanpa `node_modules` di image frontend | `docker compose exec frontend sh -c "find / -type d -name node_modules 2>/dev/null"` | Tidak ada output |
| Tanpa rahasia di image backend | `docker compose exec backend printenv` hanya menampilkan nilai dari environment runtime; `docker history --no-trunc` image tidak memuat `APP_KEY`/password | Tidak ada rahasia di layer image |
| Tanpa `.env` di repo | `git ls-files \| grep -E '(^\|/)\.env$'` | Tidak ada output |
| Workflow hijau | Tab Actions | Run di `main` hijau (3 job); run di `dev`, PR, dan branch fitur hijau dengan `deploy` skipped |
| Image di GHCR | Tab Packages repositori | Dua image (`-backend`, `-frontend`), tag berupa SHA commit, tanpa `latest` |

### 15.2 Checklist

**Backend**
- [ ] `php artisan test` hijau dan mencakup seluruh poin 7.5.
- [ ] Login/logout, CRUD kategori dan produk, validasi, 401/404/409/422 sesuai 7.2 dan 7.3, semua berformat JSON.
- [ ] Filter `category_id`, `search`, dan paginasi pada `GET /products` berfungsi.
- [ ] Seeder idempotent: start ulang tidak menggandakan data dan tidak menghidupkan kembali data yang sudah dihapus.
- [ ] Seluruh endpoint dapat diuji di Postman (`http://localhost:8000/api`).

**Frontend**
- [ ] `npm ci` dan `npm run build` lolos (termasuk pengecekan tipe).
- [ ] Guard route, login/logout, dan penanganan 401 sesuai 8.1.
- [ ] Halaman Kategori dan Produk sesuai 8.4 dan 8.5, termasuk state loading, kosong, dan gagal memuat.
- [ ] Error 422 tampil per field; error 409 tampil sebagai toast.
- [ ] Warna utama orange sesuai 8.7; tanpa gradien atau dekorasi di luar aturan.

**Docker dan Compose**
- [ ] Dockerfile backend: base image bertag versi jelas (bukan `latest`), `composer.json` + `composer.lock` dipasang sebelum source disalin.
- [ ] Dockerfile frontend: multi-stage Node → Nginx, image akhir tanpa `node_modules`.
- [ ] `.dockerignore` ada di `backend/` dan `frontend/`.
- [ ] Tidak ada nilai rahasia di Dockerfile maupun `docker-compose.yml`.
- [ ] `docker compose up -d --build` menjalankan db, backend, dan frontend; semua kredensial dibaca dari `.env` yang tidak di-commit; `.env.example` tersedia.
- [ ] Data bertahan setelah `docker compose down` lalu `docker compose up -d` (named volume).
- [ ] Backend ke DB dan frontend ke backend memakai nama service, bukan IP.
- [ ] Backend dilayani FrankenPHP dan terbukti dari log container.

**CI/CD dan repositori**
- [ ] `.github/workflows/ci.yml` berisi `backend-test`, `frontend-build`, dan `deploy`; hijau di tab Actions.
- [ ] `deploy` hanya berjalan di push ke `main`; PR, `dev`, dan branch fitur berhenti setelah dua job pertama.
- [ ] Image dipush ke ghcr.io dengan tag SHA commit; halaman Packages berisi kedua image.
- [ ] `main` dan `dev` terlindungi: hanya lewat PR dan status check lulus.
- [ ] Tidak ada nilai rahasia di repositori; nilai rahasia berada di `.env` lokal dan GitHub Secrets.
- [ ] Riwayat commit dan alur branch mengikuti bagian 13.
- [ ] Tidak ada paket, folder, atau abstraksi di luar yang disebutkan dokumen ini.

## 16. Deliverable Tambahan

1. **Koleksi Postman** (`toko-yafi.postman_collection.json`) berisi seluruh endpoint dengan variabel `{{base_url}}` (`http://localhost:8000/api`), `{{admin_email}}`, `{{admin_password}}` (dikosongkan), dan `{{token}}`. Request login menyimpan token otomatis ke variabel koleksi lewat test script.
2. **README.md** singkat: prasyarat (Docker dengan Compose v2), cara menyiapkan `.env` (termasuk membuat `APP_KEY`), menjalankan `docker compose up -d --build`, alamat aplikasi (`http://localhost:8080` untuk web, `http://localhost:8000/api` untuk API), cara melihat bukti FrankenPHP di log, cara menghentikan (`docker compose down`) dan mereset data (`docker compose down -v`), cara menjalankan test backend, cara dev frontend (`npm run dev` dengan backend Docker berjalan), ringkasan alur CI/CD, dan rujukan ke aturan Git (bagian 13).
3. Pastikan commit di setiap tahap yang dilakukan jangan jadi satu diakhir