<?php
require_once 'auth.php';
require_once 'db.php';

requireRole(['admin']);

$message = '';
$alertType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['scan_fix'])) {
    $issuesFixed = 0;

    $cekStok = $conn->query("SELECT id_buku, stok FROM buku WHERE stok < 0");
    if ($cekStok && $cekStok->num_rows > 0) {
        while ($row = $cekStok->fetch_assoc()) {
            $id_buku = $row['id_buku'];
            $stok_salah = $row['stok'];

            $conn->query("UPDATE buku SET stok = 0 WHERE id_buku = '$id_buku'");

            $deskripsi = "Buku ID $id_buku terdeteksi memiliki stok negatif ($stok_salah).";
            $tindakan  = "Reset nilai stok menjadi 0 untuk mencegah anomali sirkulasi.";
            
            $stmt = $conn->prepare("INSERT INTO data_quality_log (nama_tabel, jenis_isu, deskripsi_masalah, tindakan_perbaikan) VALUES ('buku', 'Stok Invalid', ?, ?)");
            $stmt->bind_param("ss", $deskripsi, $tindakan);
            $stmt->execute();
            $stmt->close();

            $issuesFixed++;
        }
    }

    $cekDataKosong = $conn->query("SELECT id_anggota, nim, no_telepon FROM anggota WHERE nim = '' OR nim IS NULL OR no_telepon = '' OR no_telepon IS NULL");
    if ($cekDataKosong && $cekDataKosong->num_rows > 0) {
        while ($row = $cekDataKosong->fetch_assoc()) {
            $id_anggota = $row['id_anggota'];
            $nim_kosong = empty($row['nim']) ? 'kosong' : $row['nim'];
            $telepon_kosong = empty($row['no_telepon']) ? 'kosong' : $row['no_telepon'];

            $deskripsi = "Anggota ID $id_anggota memiliki data tidak lengkap (NIM: $nim_kosong, No. Telepon: $telepon_kosong).";
            $tindakan  = "Perlu melakukan input ulang atau update data anggota di modul Data Anggota.";
            
            $stmt = $conn->prepare("INSERT INTO data_quality_log (nama_tabel, jenis_isu, deskripsi_masalah, tindakan_perbaikan) VALUES ('anggota', 'Data Kosong', ?, ?)");
            $stmt->bind_param("ss", $deskripsi, $tindakan);
            $stmt->execute();
            $stmt->close();

            $issuesFixed++;
        }
    }

    $cekEmail = $conn->query("SELECT id_anggota, email FROM anggota WHERE email NOT LIKE '%@%.%' AND email != ''");
    if ($cekEmail && $cekEmail->num_rows > 0) {
        while ($row = $cekEmail->fetch_assoc()) {
            $id_anggota = $row['id_anggota'];
            $email_salah = $row['email'];

            $conn->query("UPDATE anggota SET status = 'Nonaktif' WHERE id_anggota = '$id_anggota'");

            $deskripsi = "Anggota ID $id_anggota memiliki format email yang salah ($email_salah).";
            $tindakan  = "Mengubah status anggota menjadi 'Nonaktif' hingga update data dilakukan.";
            
            $stmt = $conn->prepare("INSERT INTO data_quality_log (nama_tabel, jenis_isu, deskripsi_masalah, tindakan_perbaikan) VALUES ('anggota', 'Format Salah', ?, ?)");
            $stmt->bind_param("ss", $deskripsi, $tindakan);
            $stmt->execute();
            $stmt->close();

            $issuesFixed++;
        }
    }

    $cekFormatTanggal = $conn->query("SELECT id_peminjaman, tgl_pinjam FROM peminjaman WHERE tgl_pinjam NOT REGEXP '^[0-9]{4}-[0-9]{2}-[0-9]{2}$' AND tgl_pinjam IS NOT NULL");
    if ($cekFormatTanggal && $cekFormatTanggal->num_rows > 0) {
        while ($row = $cekFormatTanggal->fetch_assoc()) {
            $id_peminjaman = $row['id_peminjaman'];
            $tgl_salah = $row['tgl_pinjam'];

            $deskripsi = "Peminjaman ID $id_peminjaman memiliki format tanggal yang salah ($tgl_salah).";
            $tindakan  = "Perlu perbaikan format tanggal agar sesuai YYYY-MM-DD.";
            
            $stmt = $conn->prepare("INSERT INTO data_quality_log (nama_tabel, jenis_isu, deskripsi_masalah, tindakan_perbaikan) VALUES ('peminjaman', 'Format Salah', ?, ?)");
            $stmt->bind_param("ss", $deskripsi, $tindakan);
            $stmt->execute();
            $stmt->close();

            $issuesFixed++;
        }
    }

    if ($issuesFixed > 0) {
        $message = "Pemindaian Selesai! Sistem berhasil menemukan dan memperbaiki <strong>$issuesFixed</strong> isu anomali data.";
        $alertType = 'warning'; 
    } else {
        $message = "Data Anda sehat! Tidak ditemukan anomali stok maupun kesalahan format data.";
        $alertType = 'success';
    }
}

$logQuery = $conn->query("SELECT * FROM data_quality_log ORDER BY tgl_eksekusi DESC");

$activePage   = 'quality';
$pageTitle    = 'Kelola Kualitas Data';
$pageSubtitle = 'Deteksi dan perbaikan otomatis anomali data';
$user         = $_SESSION['user'];
require 'partials/sidebar.php';
?>
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
            <h4 class="pb-page-heading m-0"><span class="pb-heading-badge"><i class="bi bi-shield-check"></i></span> Pengelolaan Kualitas Data</h4>
            
            
            <form method="POST" action="" onsubmit="return confirm('Mulai pemindaian dan perbaikan data otomatis?');">
                <button type="submit" name="scan_fix" class="btn btn-warning shadow-sm fw-bold text-dark">
                    <i class="bi bi-search me-1"></i> Scan & Perbaiki Anomali
                </button>
            </form>
        </div>

        <?php if (!empty($message)): ?>
            <div class="alert alert-<?= $alertType ?> alert-dismissible fade show shadow-sm" role="alert">
                <i class="bi bi-info-circle-fill me-2"></i><?= $message ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        
        <div class="card shadow-sm border-0 border-top border-4 border-warning">
            <div class="card-header bg-white font-weight-bold d-flex justify-content-between align-items-center py-3">
                <span><i class="bi bi-journal-check me-1"></i> Log Histori Perbaikan Data (Bukti Fisik)</span>
                <span class="badge bg-secondary">Tabel: data_quality_log</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-striped align-middle m-0">
                        <thead class="table-light">
                            <tr>
                                <th width="15%">Waktu Eksekusi</th>
                                <th width="10%">Tabel</th>
                                <th width="15%">Jenis Isu</th>
                                <th width="30%">Deskripsi Masalah</th>
                                <th width="30%">Tindakan Perbaikan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($logQuery && $logQuery->num_rows > 0): ?>
                                <?php while ($row = $logQuery->fetch_assoc()): ?>
                                    <tr>
                                        <td class="small text-muted"><?= date('d-m-Y H:i', strtotime($row['tgl_eksekusi'])) ?></td>
                                        <td><span class="badge bg-dark"><?= htmlspecialchars($row['nama_tabel']) ?></span></td>
                                        <td>
                                            <?php if ($row['jenis_isu'] === 'Stok Invalid'): ?>
                                                <span class="badge bg-danger">Stok Invalid</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning text-dark"><?= htmlspecialchars($row['jenis_isu']) ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="small"><?= htmlspecialchars($row['deskripsi_masalah']) ?></td>
                                        <td class="small text-success fw-bold"><i class="bi bi-check-circle me-1"></i><?= htmlspecialchars($row['tindakan_perbaikan']) ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">
                                        <i class="bi bi-clipboard-x fs-3 d-block mb-2"></i>
                                        Belum ada riwayat perbaikan data yang tercatat.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

<?php require 'partials/footer.php'; ?>