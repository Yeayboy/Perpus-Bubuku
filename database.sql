CREATE DATABASE IF NOT EXISTS perpus_kampus;
Use perpus_kampus;



DROP TABLE IF EXISTS data_quality_log;
DROP TABLE IF EXISTS dokumen_metadata;
DROP TABLE IF EXISTS users;

DROP TABLE IF EXISTS detail_peminjaman;

DROP TABLE IF EXISTS peminjaman;

DROP TABLE IF EXISTS buku;

DROP TABLE IF EXISTS kategori;
DROP TABLE IF EXISTS anggota;


CREATE TABLE users (
    id_user INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    nama_lengkap VARCHAR(100) NOT NULL,
    role ENUM('admin', 'petugas') NOT NULL DEFAULT 'petugas'
) ENGINE=InnoDB;


CREATE TABLE kategori (
    id_kategori INT AUTO_INCREMENT PRIMARY KEY,
    nama_kategori VARCHAR(50) NOT NULL
) ENGINE=InnoDB;


CREATE TABLE buku (
    id_buku VARCHAR(10) PRIMARY KEY,
    isbn VARCHAR(13) NOT NULL UNIQUE,
    judul VARCHAR(255) NOT NULL,
    pengarang VARCHAR(100) NOT NULL,
    stok INT DEFAULT 0,
    id_kategori INT,
    FOREIGN KEY (id_kategori) REFERENCES kategori(id_kategori) 
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;


CREATE TABLE anggota (
    id_anggota VARCHAR(10) PRIMARY KEY,
    nim VARCHAR(10) UNIQUE,
    nama VARCHAR(150) NOT NULL,
    email VARCHAR(100) UNIQUE,
    no_telepon VARCHAR(20),
    status ENUM('Aktif', 'Nonaktif') DEFAULT 'Aktif'
) ENGINE=InnoDB;

CREATE TABLE guest_books (
    id_entry INT AUTO_INCREMENT PRIMARY KEY,
    id_anggota VARCHAR(10) NOT NULL,
    id_buku VARCHAR(10) NOT NULL,
    status ENUM('Membaca', 'Selesai', 'Ingin Dibaca') DEFAULT 'Membaca',
    catatan TEXT,
    tgl_tambah DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_anggota) REFERENCES anggota(id_anggota) ON UPDATE CASCADE ON DELETE CASCADE,
    FOREIGN KEY (id_buku) REFERENCES buku(id_buku) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE guest_reminders (
    id_reminder INT AUTO_INCREMENT PRIMARY KEY,
    id_anggota VARCHAR(10) NOT NULL,
    judul VARCHAR(255) NOT NULL,
    tanggal_target DATE NOT NULL,
    keterangan TEXT,
    status ENUM('Aktif', 'Selesai') DEFAULT 'Aktif',
    tgl_dibuat DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_anggota) REFERENCES anggota(id_anggota) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE guest_fines (
    id_denda INT AUTO_INCREMENT PRIMARY KEY,
    id_anggota VARCHAR(10) NOT NULL,
    jumlah DECIMAL(10,2) NOT NULL,
    keterangan TEXT,
    status ENUM('Belum Dibayar', 'Dibayar') DEFAULT 'Belum Dibayar',
    tgl_catat DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_anggota) REFERENCES anggota(id_anggota) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE peminjaman (
    id_peminjaman VARCHAR(15) PRIMARY KEY,
    tgl_pinjam DATE NOT NULL,
    tgl_jatuh_tempo DATE NOT NULL,
    tgl_kembali DATE DEFAULT NULL,
    status_transaksi ENUM('Dipinjam', 'Selesai') DEFAULT 'Dipinjam',
    id_anggota VARCHAR(10),
    FOREIGN KEY (id_anggota) REFERENCES anggota(id_anggota) 
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;


CREATE TABLE detail_peminjaman (
    id_detail INT AUTO_INCREMENT PRIMARY KEY,
    id_peminjaman VARCHAR(15),
    id_buku VARCHAR(10),
    jumlah INT DEFAULT 1,
    FOREIGN KEY (id_peminjaman) REFERENCES peminjaman(id_peminjaman) 
        ON UPDATE CASCADE ON DELETE CASCADE,
    FOREIGN KEY (id_buku) REFERENCES buku(id_buku) 
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

-- 7. TABEL DOKUMEN METADATA (Unit 5: Mengelola Dokumen/Konten)
CREATE TABLE dokumen_metadata (
    id_dokumen INT AUTO_INCREMENT PRIMARY KEY,
    judul_dokumen VARCHAR(255) NOT NULL,
    nama_file VARCHAR(100) NOT NULL,
    jenis_file VARCHAR(10) NOT NULL,
    lokasi_file VARCHAR(255) NOT NULL,
    versi VARCHAR(10) DEFAULT '1.0',
    tgl_unggah DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE data_quality_log (
    id_log INT AUTO_INCREMENT PRIMARY KEY,
    nama_tabel VARCHAR(50) NOT NULL,
    jenis_isu ENUM('Data Ganda', 'Data Kosong', 'Format Salah', 'Stok Invalid') NOT NULL,
    deskripsi_masalah TEXT NOT NULL,
    tindakan_perbaikan TEXT NOT NULL,
    tgl_eksekusi DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;




-- Buat user admin secara lokal dengan password_hash() dan jangan commit password asli.
-- Contoh:
-- INSERT INTO users (username, password, nama_lengkap, role) VALUES
-- ('admin', '<HASIL_PASSWORD_HASH_LOKAL>', 'Administrator Utama', 'admin');


INSERT INTO kategori (nama_kategori) VALUES 
('Teknologi Informasi'),
('Sistem Informasi'),
('Matematika dan Sains');

INSERT INTO buku (id_buku, isbn, judul, pengarang, stok, id_kategori) VALUES 
('B001', '978-3-16-148410-0', 'Pemrograman Python untuk Pemula', 'John Doe', 10, 1),
('B002', '978-1-234-56789-7', 'Sistem Informasi Manajemen', 'Jane Smith', 5, 2),
('B003', '978-0-123-45678-9', 'Matematika Diskrit', 'Albert Einstein', 8, 3);

INSERT INTO anggota (id_anggota, nim, nama, email, status) VALUES 
('A001', '1234567890', 'Anggota Contoh 1', 'anggota1@example.invalid', 'Aktif'),
('A002', '0987654321', 'Anggota Contoh 2', 'anggota2@example.invalid', 'Aktif');

INSERT INTO dokumen_metadata (judul_dokumen, nama_file, jenis_file, lokasi_file, versi) VALUES 
('Panduan Penggunaan Perpustakaan', 'panduan_perpustakaan.pdf', 'pdf', '/dokumen/panduan_perpustakaan.pdf', '1.0'),
('SOP Peminjaman Buku', 'sop_peminjaman.docx', 'docx', '/dokumen/sop_peminjaman.docx', '1.0'),
('Laporan Tahunan Perpustakaan', 'laporan_tahunan.docx', 'docx', '/dokumen/laporan_tahunan.docx', '1.0');

INSERT INTO data_quality_log (nama_tabel, jenis_isu, deskripsi_masalah, tindakan_perbaikan) VALUES 
('buku', 'Data Ganda', 'Terdapat buku dengan ISBN yang sama lebih dari satu kali.', 'Hapus data duplikat dan pastikan setiap buku memiliki ISBN unik.'),
('anggota', 'Data Kosong', 'Beberapa anggota tidak memiliki email yang valid.', 'Periksa dan lengkapi data email anggota yang kosong.'),
('peminjaman', 'Format Salah', 'Tanggal peminjaman tidak sesuai format YYYY-MM-DD.', 'Perbaiki format tanggal peminjaman agar sesuai');

