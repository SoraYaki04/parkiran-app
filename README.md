# Parkiran App - Sistem Manajemen Parkir Modern

Sebuah solusi manajemen parkir yang efisien dan modern, dibangun menggunakan kekuatan **Laravel 10** dan **Livewire 3**. Aplikasi ini dirancang untuk menyederhanakan operasional parkir, mulai dari pencatatan masuk/keluar kendaraan, pengelolaan slot, hingga pelaporan keuangan yang akurat.

## 🚀 Fitur Utama

### 🚗 Manajemen Operasional
- **Check-in & Check-out Cepat**: Proses masuk dan keluar kendaraan yang efisien.
- **QR Code Integration**: Scan QR Code untuk tiket parkir guna mempercepat transaksi.
- **Real-time Slot Monitoring**: Pemantau ketersediaan slot parkir secara langsung.
- **Dukungan Berbagai Kendaraan**: Kategori tarif untuk Motor, Mobil, dll.

### 👥 Hak Akses & Keamanan (Role-Based Access Control)
- **Admin**: Akses penuh ke seluruh pengaturan sistem, manajemen user, dan konfigurasi tarif.
- **Petugas**: Fokus pada operasional harian (transaksi parkir).
- **Owner**: Akses khusus untuk memantau laporan pendapatan dan statistik tanpa mengubah data operasional.

### 📊 Laporan & Analitik
- **Dashboard Interaktif**: Grafik pendapatan harian/bulanan dan statistik kendaraan.
- **Laporan Keuangan**: Rekapitulasi pendapatan yang detail.
- **Ekspor Data**: Dukungan ekspor laporan ke format **PDF** dan **Excel**.

### 🛠️ Fitur Tambahan
- **Backup Database**: Fitur bawaan untuk mengamankan data sistem.
- **Cetak Struk**: Integrasi pencetakan tiket parkir.

## 💻 Teknologi yang Digunakan

- **Backend Framework**: [Laravel 10](https://laravel.com)
- **Full-Stack Framework**: [Livewire 3](https://livewire.laravel.com)
- **Styling**: [Tailwind CSS](https://tailwindcss.com)
- **Database**: MySQL
- **Build Tool**: [Vite](https://vitejs.dev)
- **Library Pendukung**:
  - `barryvdh/laravel-dompdf` (PDF Export)
  - `maatwebsite/excel` (Excel Export)
  - `endroid/qr-code` (QR Code Generator)
  - `spatie/laravel-backup` (System Backup)

## 📋 Prasyarat Sistem

Sebelum memulai, pastikan sistem Anda memenuhi kebutuhan berikut:
- **PHP**: ^8.1
- **Composer**
- **Node.js** & **NPM**
- **MySQL Database**

## ⚙️ Cara Instalasi

Ikuti langkah-langkah berikut untuk menjalankan proyek ini di komputer lokal Anda:

1.  **Clone Repositori**
    ```bash
    git clone https://github.com/username/parkiran-app.git
    cd parkiran-app
    ```

2.  **Install Dependencies**
    Install paket PHP dan JavaScript yang dibutuhkan.
    ```bash
    composer install
    npm install
    ```

3.  **Konfigurasi Environment**
    Salin file contoh konfigurasi `.env`.
    ```bash
    cp .env.example .env
    ```
    Buka file `.env` dan sesuaikan pengaturan database Anda (DB_DATABASE, DB_USERNAME, DB_PASSWORD).

4.  **Generate Key Aplikasi**
    ```bash
    php artisan key:generate
    ```

5.  **Setup Database**
    Jalankan migrasi untuk membuat tabel dan seeder untuk data awal (User admin, setting awal, dll).
    ```bash
    php artisan migrate --seed
    ```

6.  **Setup Storage**
    Tautkan folder storage agar file publik dapat diakses.
    ```bash
    php artisan storage:link
    ```

7.  **Build Assets**
    Compile asset CSS dan JS.
    ```bash
    npm run build
    ```

8.  **Jalankan Server**
    ```bash
    php artisan serve
    ```

    Akses aplikasi melalui browser di: `http://localhost:8000`

## 🔐 Akun Demo (Jika Menggunakan Seeder)

Jika Anda menjalankan `php artisan migrate --seed`, silakan gunakan akun berikut untuk login:

- **Admin**: `admin` / `admin123`
- **Petugas**: `petugas` / `petugas123`
- **Owner**: `owner` / `owner123`

## 📄 Lisensi

Project ini dilisensikan di bawah [MIT License](LICENSE).
