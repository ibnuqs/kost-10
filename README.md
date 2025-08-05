# Panduan Lengkap: Sistem Manajemen Kost (kost-10)

Dokumen ini adalah panduan utama untuk instalasi, konfigurasi, deployment, dan pemeliharaan proyek `kost-10`.

## 1. Gambaran Umum Proyek

`kost-10` adalah aplikasi full-stack yang dirancang sebagai sistem manajemen rumah kost dengan struktur monorepo.

-   **Backend (`kost-backend`)**: Aplikasi PHP/Laravel sebagai API, mengelola data, pembayaran (Midtrans), dan komunikasi IoT (MQTT).
-   **Frontend (`kost-frontend`)**: Aplikasi TypeScript/React sebagai antarmuka pengguna.

## 2. Tumpukan Teknologi (Tech Stack)

| Area | Teknologi/Library | Direktori |
| :--- | :--- | :--- |
| **Backend** | PHP 8.2, Laravel 11 | `kost-backend/` |
| **Frontend**| TypeScript, React 18, Vite | `kost-frontend/` |
| **Database**| MySQL (disarankan) / SQLite | `kost-backend/` |
| **DevOps** | Docker, Nginx | `./` |

---

## 3. Penyiapan Lokal (Development)

### Prasyarat
-   Node.js 18+
-   PHP 8.2 & Composer
-   MySQL atau DB Browser for SQLite

### Backend (`kost-backend`)
1.  **Masuk ke direktori:**
    ```bash
    cd kost-backend
    ```
2.  **Salin file environment:**
    ```bash
    cp .env.example .env
    ```
3.  **Install dependensi:**
    ```bash
    composer install
    ```
4.  **Generate app key:**
    ```bash
    php artisan key:generate
    ```
5.  **Konfigurasi `.env`:**
    -   Atur `DB_CONNECTION` ke `mysql` atau `sqlite`.
    -   Isi detail koneksi database (jika menggunakan MySQL).
    -   Isi kredensial `MIDTRANS`, `PUSHER`, dan `HIVEMQ`.
6.  **Jalankan migrasi dan seeder:**
    ```bash
    php artisan migrate --seed
    ```
7.  **Jalankan server development:**
    ```bash
    php artisan serve
    ```
    Backend akan berjalan di `http://localhost:8000`.

### Frontend (`kost-frontend`)
1.  **Masuk ke direktori:**
    ```bash
    cd kost-frontend
    ```
2.  **Salin file environment:**
    ```bash
    cp .env.example .env
    ```
3.  **Install dependensi:**
    ```bash
    npm install
    ```
4.  **Konfigurasi `.env`:**
    -   Pastikan `VITE_API_URL` mengarah ke URL backend (`http://localhost:8000/api`).
    -   Isi kredensial `VITE_PUSHER` dan `VITE_HIVEMQ`.
5.  **Jalankan server development:**
    ```bash
    npm run dev
    ```
    Frontend akan berjalan di `http://localhost:5173` atau port lain yang tersedia.

---

## 4. Panduan Deployment (VPS Ubuntu 22.04)

Panduan ini untuk domain `potunakos.my.id`. Sesuaikan jika menggunakan domain lain.

### Langkah 1: Instalasi Server
```bash
# Update sistem
sudo apt update && sudo apt upgrade -y

# Install Nginx, MySQL, PHP, dan Git
sudo apt install nginx mysql-server php8.2-fpm php8.2-mysql php8.2-xml php8.2-curl php8.2-zip php8.2-mbstring git -y

# Install Node.js 18+
curl -fsSL https://deb.nodesource.com/setup_18.x | sudo -E bash -
sudo apt-get install -y nodejs

# Install Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

### Langkah 2: Setup Proyek
```bash
# Clone proyek
cd /var/www
sudo git clone https://github.com/yourusername/kost-10.git
sudo chown -R www-data:www-data /var/www/kost-10
cd /var/www/kost-10
```

### Langkah 3: Konfigurasi Backend
```bash
cd kost-backend
sudo cp .env.production.example .env
sudo composer install --optimize-autoloader --no-dev
sudo php artisan key:generate
# Edit .env dengan kredensial produksi (database, Midtrans, dll)
sudo nano .env
sudo php artisan migrate --seed
sudo php artisan storage:link
sudo php artisan config:cache
sudo php artisan route:cache
```

### Langkah 4: Build Frontend
```bash
cd ../kost-frontend
sudo npm install
sudo npm run build
```

### Langkah 5: Konfigurasi Nginx
Buat file konfigurasi Nginx baru:
```bash
sudo nano /etc/nginx/sites-available/potunakos.my.id
```
Isi dengan konfigurasi berikut:
```nginx
server {
    listen 80;
    server_name potunakos.my.id www.potunakos.my.id;
    root /var/www/kost-10/kost-frontend/dist;
    index index.html;

    # Frontend (React SPA)
    location / {
        try_files $uri $uri/ /index.html;
    }

    # Backend API (Laravel)
    location /api/ {
        proxy_pass http://127.0.0.1:8000;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }
}
```
Aktifkan site dan restart Nginx:
```bash
sudo ln -s /etc/nginx/sites-available/potunakos.my.id /etc/nginx/sites-enabled/
sudo rm /etc/nginx/sites-enabled/default
sudo nginx -t
sudo systemctl restart nginx
```

### Langkah 6: Setup SSL (Let's Encrypt)
```bash
sudo apt install certbot python3-certbot-nginx -y
sudo certbot --nginx -d potunakos.my.id -d www.potunakos.my.id
```

### Langkah 7: Jalankan Backend sebagai Service
Buat file service untuk systemd:
```bash
sudo nano /etc/systemd/system/kost-backend.service
```
Isi dengan konfigurasi berikut:
```ini
[Unit]
Description=Kost Backend Service
After=network.target

[Service]
Type=simple
User=www-data
WorkingDirectory=/var/www/kost-10/kost-backend
ExecStart=/usr/bin/php artisan serve --host=127.0.0.1 --port=8000
Restart=on-failure

[Install]
WantedBy=multi-user.target
```
Aktifkan dan jalankan service:
```bash
sudo systemctl enable kost-backend.service
sudo systemctl start kost-backend.service
```

---

## 5. Konfigurasi Penting

### Midtrans
-   **Payment Notification URL:** `https://<domain-anda>/api/webhook/midtrans`

### MQTT & ESP32
-   Pastikan kredensial HiveMQ di `.env` backend dan frontend sudah sesuai.
-   ESP32 harus di-flash dengan firmware yang mempublikasikan status ke topik yang didengarkan oleh backend dan frontend.
-   Topik utama yang digunakan: `rfid/command`, `rfid/access`, `esp32/status`.

### CORS
Konfigurasi CORS di `kost-backend/config/cors.php` sudah diatur untuk secara otomatis mengizinkan domain produksi (`potunakos.my.id`) dan `localhost` untuk development.

---

## 6. Perintah Berguna

### Backend
-   `php artisan test`: Menjalankan tes.
-   `php artisan config:clear`: Menghapus cache konfigurasi.
-   `tail -f storage/logs/laravel.log`: Melihat log error Laravel.

### Frontend
-   `npm run lint`: Menjalankan linter.
-   `npm run preview`: Mempratinjau hasil build produksi.
```