# 📚 Perpus Bubuku - Sistem Informasi Perpustakaan Kampus
<img width="1024" height="475" alt="image" src="https://github.com/user-attachments/assets/7d17669c-a4ee-4ca1-917b-5b9a85654f99" />

**Perpus Bubuku** adalah Sistem Informasi Perpustakaan berbasis web yang terintegrasi. Aplikasi ini dirancang tidak hanya untuk mengelola sirkulasi peminjaman buku standar, tetapi juga dilengkapi dengan fitur tingkat lanjut untuk menunjang standar **Sertifikasi Kompetensi Database Administrator (DBA)**, seperti pengelolaan metadata dokumen, integrasi data massal (CSV), dan pemantauan kualitas data (*Data Quality*).

## ✨ Fitur Utama

Sistem ini membagi hak akses (RBAC) menjadi dua antarmuka utama: **Administrator/Petugas** dan **Pengunjung (Mahasiswa)**.

### 🛡️ Modul Administrator (DBA & Petugas)
<img width="1024" height="474" alt="image" src="https://github.com/user-attachments/assets/b324483b-0299-4124-96f5-e9511d43ae73" />

*   **Kelola Master Data Buku:** Pengelolaan penuh (CRUD) untuk katalog buku, kategori, ISBN, dan pelacakan ketersediaan stok fisik secara *real-time*.
*   **Manajemen Data Anggota:** Pencatatan dan pengelolaan identitas anggota perpustakaan.
*   **Sirkulasi & Laporan:** Pencatatan transaksi peminjaman dan pengembalian buku. Dilengkapi dengan kalkulasi denda otomatis jika melewati tanggal jatuh tempo.
*   **Integrasi Data Anggota (Impor CSV):** Fasilitas untuk mendaftarkan mahasiswa secara massal hanya dengan mengunggah file `.csv`. Sistem otomatis menangani pembaruan data jika terjadi duplikasi (*On Duplicate Key Update*).
*   **Manajemen Metadata Dokumen:** Repositori arsip digital kampus untuk mengunggah dan mengelola file (PDF, DOCX, XLSX) seperti SOP dan Laporan, lengkap dengan pelacakan versi revisi.
*   **Pemeliharaan Kualitas Data (Data Quality):** Fitur khusus DBA untuk memindai basis data secara otomatis dan mendeteksi anomali seperti: data ganda, format email/tanggal yang salah, atribut kosong, hingga stok tidak valid. Dilengkapi dengan log histori perbaikan otomatis.

### 👤 Modul Pengunjung
<img width="1024" height="478" alt="image" src="https://github.com/user-attachments/assets/46c51e20-f926-496e-b40c-9474331e311c" />

*   **Dashboard Personal:** Ringkasan aktivitas perpustakaan mahasiswa.
*   **Daftar Bacaan Pribadi:** Fitur *wishlist* atau catatan buku yang sedang dibaca, selesai dibaca, atau ingin dibaca.
*   **Pengingat & Denda:** Notifikasi visual untuk buku yang mendekati jatuh tempo atau pemberitahuan denda keterlambatan yang harus dibayar.
*   **Katalog Buku:** Akses cepat untuk melihat ketersediaan buku di perpustakaan.

💻 Teknologi yang Digunakan
*   **Bahasa Pemrograman:** PHP (Native/Framework)
*   **Basis Data:** MySQL / MariaDB
*   **Antarmuka (Front-End):** HTML5, CSS3, JavaScript, dan Bootstrap
*   **Arsitektur Database:** Relasional (Dilengkapi Normalisasi, Primary Key, Foreign Key, dan Indexing)


