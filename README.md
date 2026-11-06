# PMkanban (Laravel)

Port **Laravel 13** dari aplikasi PMkanban (Next.js). Aplikasi Project Management berbasis Kanban untuk kolaborasi tim lintas unit kerja (IT/TD, Markom, MFK, PPSDM, Umum, dll), plus modul **Helpdesk IT**.

- **UI identik** dengan versi Next.js, dirender dengan Blade + Alpine.js (tanpa React, tanpa SPA).
- **Satu database dengan versi Next.js** (`kanbandb`). Format cookie `auth_token`, hash password, bentuk JSON, status code dan pesan error dibuat sama persis, jadi kedua aplikasi bisa jalan berdampingan dan user bisa login di salah satunya.
- **Realtime lewat Laravel Reverb** (WebSocket), ringan untuk ratusan koneksi sekaligus.

---

## Daftar Isi

1. [Fitur](#fitur)
2. [Tech Stack](#tech-stack)
3. [Arsitektur](#arsitektur)
4. [Struktur Proyek](#struktur-proyek)
5. [Instalasi](#instalasi)
6. [Menjalankan Aplikasi](#menjalankan-aplikasi)
7. [Konfigurasi `.env`](#konfigurasi-env)
8. [Autentikasi & Hak Akses](#autentikasi--hak-akses)
9. [Realtime (Reverb)](#realtime-reverb)
10. [Halaman Web](#halaman-web)
11. [API](#api)
12. [Helpdesk IT](#helpdesk-it)
13. [Upload File](#upload-file)
14. [Testing](#testing)
15. [Performa](#performa)
16. [Deploy ke Production](#deploy-ke-production)
17. [Troubleshooting](#troubleshooting)
18. [Kompatibilitas dengan Versi Next.js](#kompatibilitas-dengan-versi-nextjs)
19. [Catatan & Batasan](#catatan--batasan)

---

## Fitur

| Fitur | Keterangan |
|---|---|
| **Boards Hub** | Daftar board per unit dengan stage (`Planning`, `Active`, `Review`, `Completed`) dan sub-board. |
| **Kanban Board** | Kolom dan kartu tugas dengan drag-and-drop (SortableJS). Tambah/ubah/hapus kolom. |
| **Task Detail** | Prioritas, tanggal mulai/tenggat, assignee, label, customer, tag unit, sub-task, komentar (bisa pakai gambar), lampiran file, log aktivitas. |
| **Timeline / Gantt** | Tugas utama dengan sub-task bertingkat. |
| **Private Chat (P2P)** | Halaman `/messages` dan mini chat widget. Realtime: pesan masuk, indikator mengetik, status dibaca (centang biru), status online. |
| **Notifikasi** | Lonceng + toast realtime untuk assignment, mention/pesan, komentar, deadline, pengajuan helpdesk, sistem. |
| **Register & Approval** | Register → akun `pending` → disetujui admin → `active`. Semua admin dapat notifikasi saat ada pendaftar baru. |
| **Admin** | Kelola pengguna (approve/reject/hapus/ubah role) dan master unit. |
| **Pengaturan Profil** | Nama, avatar, password. |
| **Helpdesk IT** | Logbook layanan IT khusus unit TD (dashboard, antrian, input, data, trend, master data, export Excel). Lihat [Helpdesk IT](#helpdesk-it). |
| **Pengajuan Helpdesk** | Semua user bisa mengirim keluhan ke TD dan memantau statusnya. |
| **Dark Mode** | Diterapkan sebelum first paint (tanpa kedip), tersimpan di `localStorage`. |
| **Keyboard Shortcuts** | Lihat tabel di bawah. |
| **Kompresi Gambar** | Gambar jpg/png/webp dikompres otomatis saat upload (PHP GD). |
| **Rate Limit Login** | Login 5×/menit per email+IP, register 3×/menit per IP. |

### Keyboard Shortcuts

| Tombol | Aksi |
|---|---|
| `Ctrl + K` / `/` | Fokus ke pencarian |
| `N` | Buat tugas baru |
| `B` | Tampilan Kanban |
| `T` | Tampilan Timeline |
| `M` | Buka chat |
| `D` | Ganti light/dark mode |
| `?` | Bantuan shortcut |
| `Esc` | Tutup panel/modal |

---

## Tech Stack

| Bagian | Teknologi |
|---|---|
| Bahasa / Framework | PHP 8.3+ (diuji 8.4), **Laravel 13** |
| View | **Blade** + **Alpine.js 3** (satu file `Alpine.data()` per komponen) |
| Styling | **Tailwind CSS 4** via `@tailwindcss/vite`, ikon Lucide (SVG inline lewat `<x-icon>`, tanpa JS ikon) |
| Drag & drop | SortableJS |
| Build aset | Vite 8 + `laravel-vite-plugin` |
| Database | MySQL 8 / MariaDB, **raw SQL** lewat `DB::select/insert/update`. Eloquent hanya untuk model `User` (auth) |
| Realtime | **Laravel Reverb** (WebSocket, protokol Pusher) + **Laravel Echo** + `pusher-js` |
| Auth | Guard kustom `hmac`: token HMAC-SHA256 (JWT-style) di cookie `auth_token`, password PBKDF2-SHA512 |
| Excel | `phpoffice/phpspreadsheet` (export laporan helpdesk) |
| Server lokal | Laragon: Apache 2.4 + mod_fcgid, MySQL 8 |

---

## Arsitektur

```
Browser (Blade + Alpine)
   │  fetch /api/*  (cookie auth_token)            WebSocket ws://host:8080
   ▼                                                      ▲
Apache :8090 → public/index.php → Laravel ──HTTP POST──► Reverb :8080
                     │             (Broadcast::on(...)->sendNow())
                     ▼
                MySQL kanbandb  ◄── juga dipakai aplikasi Next.js
```

- **Halaman** (`routes/web.php`) dirender server-side. User sesi di-inject ke halaman sebagai `window.__USER__`, jadi tidak ada request `/api/auth/me` saat load.
- **Data** diambil Alpine lewat `/api/*` (`routes/api.php`), JSON sama persis dengan versi Next.js.
- **Realtime**: controller menulis ke DB, lalu `App\Support\Events` mengirim event ke Reverb. Reverb meneruskannya ke WebSocket browser yang subscribe ke channel privat `user.{id}`.
- **Error API** selalu berbentuk `{ "error": "..." }` (diatur di `bootstrap/app.php`).

---

## Struktur Proyek

```
PMkanbanLaravel/
├── app/
│   ├── Http/Controllers/
│   │   ├── Controller.php                  # Helper bersama: body(), err(), truthy()
│   │   ├── AuthController.php              # login, logout, me, register
│   │   ├── AdminUserController.php         # kelola user (Gate admin)
│   │   ├── BoardController.php             # GET /api/board
│   │   ├── ColumnController.php            # kolom kanban
│   │   ├── ProjectController.php           # board/proyek per unit (/api/units)
│   │   ├── MasterUnitController.php        # master unit
│   │   ├── TaskController.php              # tugas: buat, pindah, update, hapus, detail
│   │   ├── TaskCommentController.php       # komentar tugas
│   │   ├── TaskAttachmentController.php    # lampiran tugas (maks 50 MB)
│   │   ├── ImageUploadController.php       # upload gambar chat/komentar (maks 20 MB)
│   │   ├── MessageController.php           # chat P2P: percakapan, kirim, read, typing
│   │   ├── NotificationController.php      # notifikasi
│   │   ├── UserController.php              # daftar user, settings profil
│   │   ├── HelpdeskTicketController.php    # tiket helpdesk (CRUD + ambil)
│   │   ├── HelpdeskPengajuanController.php # pengajuan milik sendiri
│   │   ├── HelpdeskMasterController.php    # master kategori / unit-kerja / pic
│   │   └── HelpdeskReportController.php    # stats & export Excel
│   ├── Models/User.php                     # model auth (SESSION_COLUMNS)
│   ├── Providers/AppServiceProvider.php    # guard hmac, Gates, rate limiter
│   ├── Support/
│   │   ├── Auth.php                        # token HMAC, hash PBKDF2, resolver user, cek akses helpdesk
│   │   ├── Events.php                      # broadcast Reverb + notify() (insert notifikasi + push)
│   │   ├── Helpdesk.php                    # validasi tiket, diff riwayat, query helpdesk, mapping error DB
│   │   ├── Kanban.php                      # helper board/tugas (tag, activity log, dsb.)
│   │   └── Uploads.php                     # simpan + kompres gambar (GD)
│   └── View/Components/Icon.php            # <x-icon name="..."> → SVG Lucide inline
├── bootstrap/app.php                       # routing, broadcasting, middleware, render error {error}
├── config/                                 # app.php berisi auth_secret
├── public/
│   ├── build/                              # hasil `npm run build` (jangan diedit)
│   └── uploads/                            # attachments/ & chat/ (dibuat otomatis)
├── resources/
│   ├── css/                                # Tailwind
│   ├── icons/                              # SVG Lucide terpilih (lihat scripts/icons.mjs)
│   ├── js/
│   │   ├── app.js                          # entry: Alpine + registrasi semua komponen
│   │   ├── store.js                        # Alpine.store global (pengganti Zustand)
│   │   ├── alpine/*.js                     # satu file per komponen (kanban, chat, helpdesk, dll.)
│   │   └── lib/
│   │       ├── realtime.js                 # subscribeRealtime(): Echo/Reverb, 1 koneksi per tab
│   │       └── sortable.js, utils.js, hd.js
│   └── views/
│       ├── layouts/                        # app.blade.php (html, tema, __USER__), shell.blade.php
│       ├── pages/                          # home, login, messages, helpdesk, pengajuan
│       ├── partials/                       # header, sidebar, kanban, timeline, task sheet, chat, modals, helpdesk/*
│       └── components/                     # komponen Blade (modal helpdesk)
├── routes/
│   ├── web.php                             # halaman
│   ├── api.php                             # semua endpoint /api/*
│   ├── channels.php                        # otorisasi channel privat user.{id}
│   └── console.php
├── scripts/icons.mjs                       # salin ikon Lucide ke resources/icons
├── tests/
│   ├── Feature/AccessTest.php              # kontrak akses tamu (tanpa DB)
│   ├── check_helpdesk.php                  # self-check logika helpdesk + format password
│   └── check_throttle.mjs                  # uji rate limit login/register
├── .env.example
├── composer.json / package.json
└── vite.config.js
```

---

## Instalasi

### Prasyarat

| Kebutuhan | Versi | Catatan |
|---|---|---|
| PHP | 8.3+ | Ekstensi: `pdo_mysql`, `gd`, `mbstring`, `openssl`, `zip` (untuk Excel), `fileinfo` |
| Composer | 2.x | |
| Node.js | 20+ | Hanya untuk build aset |
| MySQL / MariaDB | 8.0 / 10.6+ | Database `kanbandb` |
| Web server | Apache (Laragon) / Nginx | Atau `php artisan serve` untuk development |

### 1. Install dependency

```bash
cd C:\laragon\www\MyProject\PMkanbanLaravel
composer install
npm install
```

### 2. Siapkan `.env`

```bash
copy .env.example .env      # Windows (Linux/macOS: cp)
php artisan key:generate
```

Lalu isi bagian berikut (detail di [Konfigurasi `.env`](#konfigurasi-env)):

```env
APP_URL=http://localhost:8090

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=kanbandb
DB_USERNAME=root
DB_PASSWORD=

SESSION_DRIVER=file
CACHE_STORE=file
QUEUE_CONNECTION=sync
BROADCAST_CONNECTION=reverb

AUTH_SECRET=<sama persis dengan AUTH_SECRET di aplikasi Next.js>

REVERB_APP_ID=<6 digit acak>
REVERB_APP_KEY=<string acak>
REVERB_APP_SECRET=<string acak>
```

> Di `.env.example` bawaan Laravel, `SESSION_DRIVER`, `CACHE_STORE` dan `QUEUE_CONNECTION` bernilai `database`. Aplikasi ini **tidak memakai migrasi Laravel**, jadi tabel `sessions`/`cache`/`jobs` tidak ada. Ganti nilainya ke `file` / `sync` seperti di atas.

Membuat key Reverb acak (PowerShell):

```powershell
-join ((97..122) | Get-Random -Count 20 | % {[char]$_})
```

### 3. Siapkan database

Skema dikelola oleh repo Next.js (`PMkanban`). **Jangan jalankan `php artisan migrate`**: migrasi bawaan Laravel di `database/migrations` tidak dipakai dan akan membuat tabel `users` yang bentrok.

Jika database belum ada, ikuti langkah **"Siapkan database"** di `PMkanban/README.md`: `schema.sql`, `seed.sql`, skrip `scripts/migrate_*.js`, lalu `scripts/migrate_helpdesk.sql`. Jika aplikasi Next.js sudah jalan di `kanbandb`, langkah ini dilewati.

### 4. Build aset

```bash
npm run build
```

> Variabel `VITE_REVERB_*` di-bake saat build. Setiap kali `VITE_REVERB_*` atau `REVERB_APP_KEY` berubah, jalankan `npm run build` lagi.

### 5. Cache konfigurasi (disarankan)

```bash
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

> Setelah `config:cache`, perubahan `.env` **tidak terbaca** sampai perintah di atas dijalankan ulang.

### 6. Buat admin pertama

Daftar lewat `/login` (tab Register), lalu promosikan akun:

```sql
UPDATE users SET role = 'admin', status = 'active' WHERE email = 'email@anda.com';
```

Untuk akses Helpdesk IT, user harus di unit TD:

```sql
UPDATE users SET unit_id = 'unit_it' WHERE email = 'email@anda.com';
```

---

## Menjalankan Aplikasi

Aplikasi butuh **dua proses**: web server (PHP) dan **Reverb** (WebSocket). Tanpa Reverb, aplikasi tetap jalan normal; yang tidak aktif hanya fitur realtime (chat langsung, typing, toast notifikasi). Chat yang sedang terbuka tetap tersinkron lewat polling cadangan setiap 30 detik.

### Opsi A: Laragon (Apache), setup saat ini

1. **Virtual host** di `C:\laragon\etc\apache2\sites-enabled\pmkanbanlaravel.test.conf`:

   ```apache
   # Port khusus supaya jalan tanpa entri hosts: http://localhost:8090
   Listen 8090

   <VirtualHost *:80 *:8090>
       DocumentRoot "C:/laragon/www/MyProject/PMkanbanLaravel/public"
       ServerName pmkanbanlaravel.test
       <Directory "C:/laragon/www/MyProject/PMkanbanLaravel/public">
           AllowOverride All
           Require all granted
       </Directory>
   </VirtualHost>
   ```

   > Baris `FcgidOutputBufferSize 0` dan `FcgidMinProcessesPerClass 12` dulu dipakai untuk SSE. Sekarang **tidak diperlukan** dan boleh dihapus (lalu restart Apache).

2. **Reverb otomatis saat Laragon start**: tambahkan baris ini ke `C:\laragon\usr\Procfile`:

   ```
   PMkanban Reverb: autorun php artisan reverb:start PWD=C:/laragon/www/MyProject/PMkanbanLaravel
   ```

   Atau jalankan manual di terminal terpisah:

   ```bash
   php artisan reverb:start            # tambah --debug untuk melihat setiap koneksi/event
   ```

3. Buka **http://localhost:8090** (atau http://pmkanbanlaravel.test).

### Opsi B: Tanpa Apache (development cepat)

```bash
php artisan serve --port=8000        # terminal 1
php artisan reverb:start             # terminal 2
npm run dev                          # terminal 3 (opsional: hot reload CSS/JS)
```

Buka http://localhost:8000.

> `npm run dev` membuat file `public/hot`. Selama file itu ada, halaman memuat aset dari dev server Vite. Hentikan `npm run dev` (file `hot` terhapus otomatis) lalu jalankan `npm run build` sebelum aplikasi dipakai orang lain.

---

## Konfigurasi `.env`

Hanya kunci yang penting atau berbeda dari default Laravel:

| Kunci | Contoh | Keterangan |
|---|---|---|
| `APP_ENV` | `local` / `production` | |
| `APP_DEBUG` | `true` / **`false` di production** | `true` membocorkan detail error (stack trace, path) |
| `APP_URL` | `http://localhost:8090` | |
| `DB_*` | `mysql`, `127.0.0.1`, `3306`, `kanbandb` | Database bersama dengan Next.js |
| `SESSION_DRIVER` | `file` | Auth tidak memakai session Laravel; `file` cukup |
| `CACHE_STORE` | `file` | Dipakai rate limiter dan pembatas update `last_active_at` (1×/menit/user) |
| `QUEUE_CONNECTION` | `sync` | Broadcast dikirim langsung (`sendNow`), tanpa worker queue |
| `AUTH_SECRET` | string acak panjang | **Wajib sama** dengan Next.js agar cookie `auth_token` valid di kedua app. Jika kosong, dipakai default hardcoded di `config/app.php` (**jangan dipakai di production**) |
| `BROADCAST_CONNECTION` | `reverb` | `null` untuk mematikan realtime (dipakai `phpunit.xml`) |
| `REVERB_APP_ID` / `REVERB_APP_KEY` / `REVERB_APP_SECRET` | acak | Kredensial app Reverb. `SECRET` jangan pernah dikirim ke browser |
| `REVERB_HOST` | `"127.0.0.1"` | Alamat Reverb **dari sisi PHP** saat mengirim event. Pakai `127.0.0.1`, bukan `localhost` (lihat [Troubleshooting](#troubleshooting)) |
| `REVERB_PORT` | `8080` | |
| `REVERB_SCHEME` | `http` | `https` jika lewat TLS |
| `REVERB_MAX_REQUEST_SIZE` | `200000` | Batas ukuran payload event (byte). Default Reverb 10 KB terlalu kecil untuk pesan chat panjang |
| `VITE_REVERB_APP_KEY` | `"${REVERB_APP_KEY}"` | Key publik untuk browser |
| `VITE_REVERB_HOST` | `""` | Kosong = browser memakai hostname halaman (jalan untuk `localhost`, IP LAN, maupun domain) |
| `VITE_REVERB_PORT` | `"${REVERB_PORT}"` | Port WebSocket dari sisi browser (`443` jika lewat reverse proxy TLS) |
| `VITE_REVERB_SCHEME` | `"${REVERB_SCHEME}"` | `https` → browser memakai `wss://` |

---

## Autentikasi & Hak Akses

### Token & password

- **Cookie `auth_token`**: `base64url(header).base64url(payload).base64url(HMAC-SHA256)`, payload `{ id, ..., exp }`, berlaku **7 hari**. Cookie ini dikecualikan dari enkripsi cookie Laravel (`bootstrap/app.php`) agar bisa dibaca Next.js.
- **Password**: format `salt:hash`, yaitu salt hex 16 byte dan `pbkdf2(sha512, 1000 iterasi, 64 byte)` dalam hex. Identik dengan `src/lib/auth.ts` di Next.js.
- **Guard**: `Auth::viaRequest('hmac', ...)` di `AppServiceProvider` memanggil `App\Support\Auth::user()`, yang memverifikasi token lalu memuat `User`. Di semua controller cukup `$request->user()`. **Identitas selalu diambil dari sesi**, tidak pernah dari `userId` di body/query.
- `last_active_at` (status online) diperbarui paling sering 1×/menit/user (dibatasi lewat cache).
- Middleware `TrimStrings` dan `ConvertEmptyStringsToNull` dimatikan supaya input (terutama password) diterima apa adanya, sama seperti di Next.js.

### Alur akun

1. Register → status `pending`, semua admin dapat notifikasi.
2. Admin approve di **Kelola Pengguna** → status `active`.
3. Login → cookie `auth_token`.
4. Halaman tanpa sesi → redirect ke `/login`. API tanpa sesi → `401 {"error":"Unauthorized"}`.

### Gates (`AppServiceProvider`)

| Gate | Syarat | Pesan saat ditolak (403) |
|---|---|---|
| `admin` | `role = 'admin'` (tamu juga dapat 403, bukan 401, sama seperti Next.js) | `Akses ditolak: Hanya untuk Administrator` |
| `helpdesk` | `unit_id = 'unit_it'` (TD) | `Khusus unit TD` |
| `helpdesk-master` | TD **dan** `role = 'admin'` | `Khusus admin unit TD` |

### Rate limit

| Endpoint | Batas | Respons |
|---|---|---|
| `POST /api/auth/login` | 5/menit per email+IP | `429 {"error":"Terlalu banyak percobaan login. Coba lagi dalam 1 menit."}` |
| `POST /api/auth/register` | 3/menit per IP | `429 {"error":"Terlalu banyak pendaftaran. Coba lagi dalam 1 menit."}` |

---

## Realtime (Reverb)

### Cara kerja

1. Browser membuka **satu** WebSocket per tab ke Reverb (`resources/js/lib/realtime.js`), lalu subscribe ke channel privat **`private-user.{idUserSendiri}`**.
2. Untuk channel privat, Echo meminta tanda tangan ke **`POST /api/broadcasting/auth`** dengan cookie `auth_token`. `routes/channels.php` hanya mengizinkan user masuk ke channel miliknya sendiri:

   ```php
   Broadcast::channel('user.{id}', fn ($user, string $id) => $user->id === $id);
   ```

3. Saat ada aksi (kirim pesan, assign tugas, dsb.), controller memanggil `App\Support\Events`:

   ```php
   Events::broadcast('message', $payload, [$senderId, $recipientId]); // ke channel user.{id} tiap penerima
   Events::notify($userId, $judul, $pesan, $tipe, $relatedId);        // insert ke tabel notifications + push 'notification'
   ```

   Pengiriman bersifat **best-effort**: jika Reverb mati, error hanya di-`report()` ke log dan operasi DB tetap sukses.

### Event

| Event | Dikirim ke | Payload | Dipakai oleh |
|---|---|---|---|
| `message` | pengirim + penerima | pesan lengkap + `recipient_id` | chat (halaman & mini widget) |
| `typing` | penerima | `senderId`, `recipientId`, `senderName`, `isTyping` | indikator "sedang mengetik" |
| `read` | kedua peserta percakapan | `conversationId`, `readerId` | centang biru & badge pesan belum dibaca |
| `notification` | user tujuan | baris notifikasi (`id`, `user_id`, `title`, `message`, `type`, `related_task_id`, `is_read`, `created_at`) | lonceng, toast, halaman helpdesk |

### Pemakaian di frontend

```js
import { subscribeRealtime } from '../lib/realtime';

// di init() komponen Alpine
this.unsub = subscribeRealtime({
  notification: (n) => { /* ... */ },
  message: (m) => { /* ... */ },
});
// di destroy()
this.unsub();
```

Semua komponen berbagi satu koneksi. Koneksi ditutup saat komponen terakhir berhenti berlangganan.

### Port & firewall

- Reverb listen di `0.0.0.0:8080`. Agar bisa diakses dari PC lain di LAN, buka port **8080** di Windows Firewall.
- Untuk mengubah port, set `REVERB_PORT` dan `REVERB_SERVER_PORT` (port listen), lalu jalankan `npm run build` dan `php artisan config:cache`.

---

## Halaman Web

| URL | Akses | Isi |
|---|---|---|
| `/login` | publik | Login & register |
| `/` | login | Boards Hub / Kanban / Timeline |
| `/messages` | login | Chat layar penuh |
| `/pengajuan` | login | Pengajuan helpdesk milik sendiri |
| `/helpdesk` | login + unit TD | Helpdesk IT. Non-TD diarahkan ke `/pengajuan` (200 + meta refresh, sama seperti Next.js) |
| `/up` | publik | Health check Laravel |

---

## API

Semua respons berupa JSON. Error: `{ "error": "pesan" }`. Kecuali disebut lain, endpoint butuh login (401 jika tidak).

### Auth

| Endpoint | Method | Keterangan |
|---|---|---|
| `/api/auth/login` | POST | `{email, password}` → set cookie `auth_token`. Rate limited |
| `/api/auth/register` | POST | Daftar (status `pending`). Rate limited |
| `/api/auth/logout` | POST | Hapus cookie |
| `/api/auth/me` | GET | User yang sedang login |
| `/api/broadcasting/auth` | POST | Otorisasi channel privat Reverb (dipanggil otomatis oleh Echo) |

### Board & Tugas

| Endpoint | Method | Keterangan |
|---|---|---|
| `/api/board` | GET | Proyek, kolom, semua tugas, master label |
| `/api/units` | GET, POST, PATCH, DELETE | Board / proyek per unit (stage, parent, posisi) |
| `/api/master-units` | GET, POST, PATCH | Master unit |
| `/api/columns` | POST, PATCH, DELETE | Kolom Kanban |
| `/api/tasks` | POST, PATCH, DELETE | Buat tugas (201), pindah/ubah sebagian, hapus (`?id=`) |
| `/api/tasks/{id}` | GET, PUT | Detail (tugas, komentar, aktivitas) / update penuh |
| `/api/tasks/{id}/comments` | POST | Tambah komentar (bisa dengan gambar) |
| `/api/tasks/{id}/attachments` | POST, DELETE | Upload lampiran (maks 50 MB) / hapus (`?attachmentId=`) |
| `/api/upload/image` | POST | Upload gambar chat/komentar (maks 20 MB) |

### Chat, Notifikasi, User

| Endpoint | Method | Keterangan |
|---|---|---|
| `/api/messages` | GET | Tanpa parameter: daftar percakapan + `totalUnreadMessages`. `?with={userId}`: buka/buat percakapan + pesan (otomatis ditandai dibaca). `?conversationId=`: pesan percakapan (hanya peserta) |
| `/api/messages` | POST | `{recipientId, content?, imageUrl?}`: kirim pesan. Percakapan selalu diturunkan dari pengirim+penerima |
| `/api/messages/read` | POST | `{conversationId}`: tandai dibaca |
| `/api/messages/typing` | POST | `{recipientId, isTyping}`: indikator mengetik |
| `/api/notifications` | GET, PATCH | Daftar + `unreadCount` / tandai dibaca |
| `/api/users` | GET | Daftar pengguna |
| `/api/settings` | PATCH | Profil & password |
| `/api/admin/users` | GET, PATCH, DELETE | Kelola pengguna (Gate `admin`; tamu → 403) |

### Helpdesk

| Endpoint | Method | Akses | Keterangan |
|---|---|---|---|
| `/api/helpdesk` | GET, POST | TD | Daftar tiket (filter + paging 25) / input langsung |
| `/api/helpdesk/{id}` | GET, PUT, DELETE | TD | Detail + riwayat / update / hapus permanen (pembuat atau admin TD) |
| `/api/helpdesk/{id}/ambil` | POST | TD | Ambil tiket `Open` → `Proses` (409 jika sudah diambil) |
| `/api/helpdesk/stats` | GET | TD | Kartu ringkasan, per hari, per kategori, trend 12 bulan |
| `/api/helpdesk/export` | GET | TD | Excel `?start&end[&kategori_ids]` |
| `/api/helpdesk/pengajuan` | GET, POST, DELETE | semua user | Pengajuan milik sendiri (maks 10 yang masih terbuka; hapus hanya saat `Open`) |
| `/api/helpdesk/master/{type}` | GET, POST, PATCH | login; tulis = admin TD | `type`: `kategori`, `unit-kerja`, `pic`. Urutan cek: 404 tipe → 401 → 403 |

---

## Helpdesk IT

Logbook layanan IT (pengganti `it_logbook`). Halaman `/helpdesk` khusus unit TD (`users.unit_id = 'unit_it'`).

| Tab | Isi |
|---|---|
| **Dashboard** | Kartu ringkasan (Total, Hari Ini, Open, Proses, Selesai, Batal, Total Menit, Total Biaya), grafik **Tiket per Hari** (klik batang = daftar tiket hari itu), aktivitas terbaru, per kategori. Periode default = bulan tiket terakhir. |
| **Antrian** | Pengajuan `Open`: **Ambil** (jadi `Proses`, staf = diri sendiri, jam mulai = sekarang jika kosong) atau **Tugaskan** ke staf lain. |
| **Input** | Tiket langsung: tanggal, jam mulai/selesai (total menit otomatis), PIC (nama baru otomatis masuk master), kategori, unit kerja, isi, tindakan, status, jenis pelayanan, staf IT, **biaya / barang keluar** (banyak baris: barang, qty, harga satuan; total otomatis). |
| **Data** | Tabel semua tiket + Total Menit + Biaya, filter tanggal/status/kategori/staf/sumber/cari, paging 25, edit, riwayat perubahan, hapus permanen, export Excel (format Laporan_Logbook). |
| **Trend Helpdesk** | Grafik 12 bulan (klik = dashboard bulan itu) + rekap per bulan. |
| **Master Data** | Kategori, unit kerja, PIC: tambah, ganti nama, aktif/nonaktif (admin TD). |

- **Status tiket**: `Open` → `Proses` → `Selesai` / `Batal`. `total_menit` adalah generated column di DB.
- **Validasi**: jam selesai ≥ jam mulai, staf harus anggota TD, baris biaya harus valid. Jika tidak, API membalas 400 dengan pesan yang jelas. Error MySQL dipetakan ke pesan ramah oleh `Helpdesk::dbError()`.
- **Notifikasi realtime**: TD menerima notifikasi saat ada pengajuan baru, pemohon dinotifikasi saat tiket diambil atau berubah status, dan staf yang ditugaskan juga dinotifikasi.

---

## Upload File

| Jenis | Endpoint | Batas | Lokasi | Kompresi |
|---|---|---|---|---|
| Lampiran tugas | `/api/tasks/{id}/attachments` | 50 MB | `public/uploads/attachments/` | jpg/png/webp: sisi maks 2048 px, kualitas 82 |
| Gambar chat/komentar | `/api/upload/image` | 20 MB | `public/uploads/chat/` | sisi maks 1600 px, kualitas 80 |

- Kompresi memakai PHP **GD**. Jika hasil kompres lebih besar dari aslinya, file asli yang disimpan.
- Pastikan `upload_max_filesize` dan `post_max_size` di `php.ini` ≥ 50M.
- Folder `public/uploads` bisa di-symlink ke folder upload aplikasi Next.js agar file terbaca di kedua aplikasi.

---

## Testing

| Perintah | Isi | Butuh |
|---|---|---|
| `php artisan test` | `tests/Feature/AccessTest.php`: redirect tamu, 401/403/404 JSON, `/api/broadcasting/auth` tanpa sesi → 401 | — (tanpa DB) |
| `php tests/check_helpdesk.php` | Self-check validasi tiket, diff riwayat, format password `salt:hash` | — |
| `node tests/check_throttle.mjs http://localhost:8090` | Rate limit login/register (pakai email palsu unik) | server jalan |
| `node scripts/test_helpdesk_access.mjs http://localhost:8090` | Uji akses API helpdesk end-to-end. Dijalankan **dari folder `PMkanban`** (Next.js). Membuat & menghapus 1 tiket uji | server + DB |

`phpunit.xml` memakai `BROADCAST_CONNECTION=null`, jadi tes tidak butuh Reverb.

---

## Performa

Hasil uji lokal (Laragon, Apache + mod_fcgid, Windows 11):

| Skenario | Hasil |
|---|---|
| 50 / 100 / 150 WebSocket terbuka → `GET /api/auth/me` | ~270 ms (sama dengan saat sepi) |
| 1 event → 150 socket | ~280 ms sampai semua socket menerima |
| Indikator mengetik (browser ke browser) | ~160 ms |
| Pesan masuk setelah POST selesai | ~10 ms |
| Read receipt | ~300 ms |

Dengan **SSE (implementasi sebelumnya)**, setiap tab memegang satu proses php-cgi (`FcgidMaxProcesses 50`), sehingga di 60 koneksi request biasa butuh 9,4 detik. Reverb memakai satu proses event-loop (ReactPHP) untuk semua koneksi, jadi jumlah tab tidak lagi memakan worker PHP.

Tips tambahan:
- Selalu jalankan `config:cache`, `route:cache`, `view:cache` di production.
- Aktifkan OPcache di `php.ini`.
- Untuk ribuan koneksi di Linux, naikkan batas file descriptor (`ulimit -n`) dan pertimbangkan ekstensi `ext-uv` untuk Reverb.

---

## Deploy ke Production

Checklist:

1. `.env`: `APP_ENV=production`, `APP_DEBUG=false`, `AUTH_SECRET` kuat (sama dengan Next.js), key Reverb acak yang baru.
2. `composer install --no-dev --optimize-autoloader`
3. `npm ci && npm run build`
4. `php artisan optimize` (cache config, route, view, event)
5. Folder `storage/` dan `bootstrap/cache/` harus writable oleh web server.
6. **Jalankan Reverb sebagai service** agar restart otomatis:
   - Linux: Supervisor/systemd menjalankan `php artisan reverb:start`.
   - Windows: NSSM / Task Scheduler, atau autorun Laragon (lihat di atas).
   - Setelah deploy kode baru: `php artisan reverb:restart`.
7. **HTTPS / wss**: jalankan Reverb di belakang reverse proxy di domain yang sama, lalu set `VITE_REVERB_SCHEME=https` dan `VITE_REVERB_PORT=443`, kemudian `npm run build`.

   Apache (aktifkan `mod_proxy`, `mod_proxy_http`, `mod_proxy_wstunnel`):

   ```apache
   ProxyPass        /app  ws://127.0.0.1:8080/app
   ProxyPassReverse /app  ws://127.0.0.1:8080/app
   ProxyPass        /apps http://127.0.0.1:8080/apps
   ProxyPassReverse /apps http://127.0.0.1:8080/apps
   ```

   Nginx:

   ```nginx
   location /app {
       proxy_http_version 1.1;
       proxy_set_header Host $http_host;
       proxy_set_header Upgrade $http_upgrade;
       proxy_set_header Connection "Upgrade";
       proxy_pass http://127.0.0.1:8080;
   }
   ```

   `REVERB_HOST` di sisi PHP tetap `127.0.0.1`, port 8080, tanpa TLS.
8. **Batasi origin WebSocket**: `php artisan vendor:publish --tag=reverb-config`, lalu di `config/reverb.php` ubah `allowed_origins` dari `['*']` ke domain aplikasi.
9. Pastikan tidak ada file `public/hot`.

---

## Troubleshooting

| Gejala | Penyebab & Solusi |
|---|---|
| Realtime tidak jalan, console browser: `WebSocket connection to 'ws://...:8080' failed` | Reverb belum jalan → `php artisan reverb:start`. Jika diakses dari PC lain, buka port 8080 di firewall. |
| Event telat ~2 detik | `REVERB_HOST=localhost` di Windows mencoba IPv6 dulu. Pakai `REVERB_HOST="127.0.0.1"`, lalu `php artisan config:cache`. |
| `POST /api/broadcasting/auth` → 401 | Cookie `auth_token` tidak ada, kedaluwarsa, atau dibuat dengan `AUTH_SECRET` lain. Login ulang. |
| `POST /api/broadcasting/auth` → 403 | Mencoba subscribe ke channel user lain (memang ditolak), atau `window.__USER__` kosong. |
| Pesan chat panjang tidak sampai realtime, log: `413` / payload too large | Naikkan `REVERB_MAX_REQUEST_SIZE`, `config:cache`, restart Reverb. |
| Perubahan `.env` tidak berpengaruh | Config ter-cache → `php artisan optimize:clear && php artisan config:cache`. |
| Perubahan `VITE_*` tidak berpengaruh | Nilainya di-bake saat build → `npm run build`. |
| Tampilan tanpa CSS / aset 404 dari `:5173` | File `public/hot` tertinggal dari `npm run dev` → hapus, lalu `npm run build`. |
| Login selalu gagal padahal password benar | Akun masih `pending`/`rejected`, atau terkena rate limit (tunggu 1 menit). |
| Error `Table '...sessions' doesn't exist` / `cache` | `SESSION_DRIVER`/`CACHE_STORE` masih `database` → ganti ke `file`. |
| Upload file besar gagal | Naikkan `upload_max_filesize` & `post_max_size` di `php.ini`, restart Apache. |
| `/helpdesk` selalu dialihkan ke `/pengajuan` | User bukan unit TD; `users.unit_id` harus `unit_it`. |

Log aplikasi ada di `storage/logs/laravel.log`. Untuk log Reverb, jalankan dengan `--debug`.

---

## Kompatibilitas dengan Versi Next.js

Kedua aplikasi boleh jalan bersamaan di database yang sama:

| Aspek | Status |
|---|---|
| Cookie `auth_token` (HMAC-SHA256, 7 hari) | Identik, dengan syarat `AUTH_SECRET` sama |
| Hash password `salt:pbkdf2-sha512` | Identik |
| Bentuk JSON, status code, pesan error API | Identik (diuji `test_helpdesk_access.mjs` terhadap kedua app) |
| Skema DB | Sama, dikelola repo Next.js |
| Realtime | **Berbeda**: Next.js memakai SSE in-process, Laravel memakai Reverb. Event dari satu aplikasi **tidak** sampai ke user yang membuka aplikasi lain. Data tetap konsisten karena DB-nya sama; chat tersinkron lewat polling 30 detik atau refresh. |
| Endpoint `/api/messages/sse` | Hanya ada di Next.js. Di Laravel diganti `/api/broadcasting/auth` + WebSocket |

---

## Catatan & Batasan

- **Satu server Reverb.** Untuk beberapa server Reverb (horizontal scaling), aktifkan scaling Redis (`REVERB_SCALING_ENABLED=true` + Redis).
- **File upload disimpan di disk lokal** (`public/uploads`), belum memakai S3/object storage.
- **`users.password_plain`** (warisan skema Next.js) menyimpan password dalam teks biasa untuk fitur admin. Pertimbangkan untuk menghapusnya sebelum production.
- **Guzzle 7**: `laravel/reverb` (lewat `pusher/pusher-php-server`) butuh Guzzle 7 / psr7 2.x, sehingga Guzzle diturunkan dari 8 ke 7.16. Aplikasi tidak memakai `Http::` client, jadi tidak berdampak.
- **Migrasi Laravel tidak dipakai.** File di `database/migrations` adalah bawaan skeleton; jangan dijalankan ke `kanbandb`.
- Sebagian besar query adalah raw SQL hasil port 1:1 dari Next.js agar hasilnya identik. Jangan ubah ke Eloquent tanpa menguji ulang bentuk JSON-nya.
