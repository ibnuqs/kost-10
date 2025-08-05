# Panduan Proyek Gemini: kost-10

Dokumen ini menyediakan panduan komprehensif bagi asisten AI Gemini untuk memahami dan berkontribusi secara efektif pada proyek `kost-10`.

## 1. Gambaran Umum Proyek

`kost-10` adalah aplikasi full-stack yang dirancang sebagai sistem manajemen rumah kost. Proyek ini memiliki struktur monorepo yang berisi backend dan frontend yang terpisah.

- **Backend (`kost-backend`)**: Aplikasi PHP/Laravel yang berfungsi sebagai API dan lapisan logika bisnis inti. Aplikasi ini mengelola data untuk pengguna, kamar, penyewa, pembayaran, dan perangkat IoT. Integrasi penting termasuk **Midtrans** untuk pemrosesan pembayaran dan **MQTT** untuk komunikasi dengan perangkat IoT.
- **Frontend (`kost-frontend`)**: Aplikasi TypeScript/React yang menyediakan antarmuka pengguna untuk berinteraksi dengan sistem. Aplikasi ini menggunakan **TanStack Router** untuk navigasi, **TanStack Query** untuk manajemen state server, dan **Axios** untuk permintaan HTTP.

## 2. Tumpukan Teknologi (Tech Stack)

| Area      | Teknologi/Library         | Direktori         | File Konfigurasi/Kunci                  |
| :-------- | :------------------------ | :---------------- | :-------------------------------------- |
| **Backend** | PHP 8.2                   | `kost-backend/`   | `composer.json`                         |
|           | Laravel 11                | `kost-backend/`   | `artisan`, `routes/api.php`             |
|           | Midtrans (Payment Gateway)| `kost-backend/`   | `app/Services/MidtransService.php`      |
|           | PHP MQTT Client           | `kost-backend/`   | `config/mqtt.php`, `app/Services/MqttService.php` |
| **Frontend**| TypeScript                | `kost-frontend/`  | `tsconfig.json`                         |
|           | React 18                  | `kost-frontend/`  | `package.json`, `src/App.tsx`           |
|           | Vite                      | `kost-frontend/`  | `vite.config.ts`                        |
|           | TanStack Router & Query   | `kost-frontend/`  | `package.json`                          |
|           | Axios                     | `kost-frontend/`  | `package.json`                          |
|           | Tailwind CSS              | `kost-frontend/`  | `tailwind.config.js`                    |
| **DevOps**  | Docker                    | `./`              | `kost-backend/Dockerfile`, `kost-frontend/Dockerfile` |

## 3. Struktur Proyek

Proyek ini adalah monorepo dengan dua direktori utama:

- `C:/Users/user/Desktop/kost-10/kost-backend/`: Berisi seluruh aplikasi backend Laravel.
- `C:/Users/user/Desktop/kost-10/kost-frontend/`: Berisi seluruh aplikasi frontend React.

## 4. Dokumentasi Penting

- `C:/Users/user/Desktop/kost-10/README.md`: Informasi umum proyek.
- `C:/Users/user/Desktop/kost-10/config-setup-guide.md`: Panduan untuk menyiapkan konfigurasi proyek.
- `C:/Users/user/Desktop/kost-10/DATABASE_MIGRATION_GUIDE.md`: Instruksi untuk migrasi database.

## 5. Perintah Umum

Perintah berikut telah diverifikasi dari file `composer.json` dan `package.json`.

### Backend (`kost-backend`)

| Aksi                   | Perintah                      | Direktori      |
| :--------------------- | :---------------------------- | :------------- |
| Instal Dependensi      | `composer install`            | `kost-backend` |
| Jalankan Migrasi & Seed| `php artisan migrate --seed`  | `kost-backend` |
| Jalankan Server Dev    | `php artisan serve`           | `kost-backend` |
| Jalankan Tes           | `./vendor/bin/phpunit` atau `php artisan test` | `kost-backend` |

### Frontend (`kost-frontend`)

| Aksi                 | Perintah                                  | Direktori       |
| :------------------- | :---------------------------------------- | :-------------- |
| Instal Dependensi    | `npm install`                             | `kost-frontend` |
| Jalankan Server Dev  | `npm run dev`                             | `kost-frontend` |
| Build untuk Produksi | `npm run build`                           | `kost-frontend` |
| Lint Kode            | `npm run lint`                            | `kost-frontend` |
| Pratinjau Build      | `npm run preview`                         | `kost-frontend` |

## 6. Konvensi Penulisan Kode

- **Backend**: Ikuti standar pengkodean Laravel dan PSR-12. Logika bisnis sebaiknya ditempatkan di `app/Services`. Gunakan trait `HasISOStringFormat` yang disediakan untuk format tanggal yang konsisten dalam respons API.
- **Frontend**: Patuhi praktik terbaik standar React dan TypeScript. Gunakan struktur direktori yang ada untuk komponen, halaman, dan layanan. Manfaatkan `axios` untuk semua panggilan API. Ikuti konvensi penataan gaya yang didefinisikan dalam `tailwind.config.js`.