<?php
require_once 'auth.php';
require_once 'db.php';

requireLogin();

$message = '';
$alertType = 'success';



ensureColumnExists($conn, 'peminjaman', 'tgl_jatuh_tempo', "DATE NOT NULL DEFAULT '1970-01-01'");
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pinjam_buku'])) {
    $id_anggota = trim($_POST['id_anggota']);
    $id_buku    = trim($_POST['id_buku']);
    $tgl_pinjam = date('Y-m-d'); 
    $tgl_jatuh_tempo = trim($_POST['tgl_jatuh_tempo'] ?? '');
    $id_pmj     = 'PMJ' . date('ymdHis'); 

    $cekStok = $conn->query("SELECT stok, judul FROM buku WHERE id_buku = '$id_buku'");
    $bukuData = $cekStok->fetch_assoc();

    if ($bukuData['stok'] < 1) {
        $message = "Maaf, stok buku <strong>{$bukuData['judul']}</strong> sedang kosong!";
        $alertType = 'danger';
    } elseif (empty($tgl_jatuh_tempo)) {
        $message = 'Tanggal jatuh tempo wajib diisi oleh admin/petugas.';
        $alertType = 'danger';
    } else {

        $conn->begin_transaction();

        try {

            $stmt1 = $conn->prepare("INSERT INTO peminjaman (id_peminjaman, tgl_pinjam, tgl_jatuh_tempo, status_transaksi, id_anggota) VALUES (?, ?, ?, 'Dipinjam', ?)");
            $stmt1->bind_param("ssss", $id_pmj, $tgl_pinjam, $tgl_jatuh_tempo, $id_anggota);
            $stmt1->execute();

            $stmt2 = $conn->prepare("INSERT INTO detail_peminjaman (id_peminjaman, id_buku, jumlah) VALUES (?, ?, 1)");
            $stmt2->bind_param("ss", $id_pmj, $id_buku);
            $stmt2->execute();

            $stmt3 = $conn->prepare("UPDATE buku SET stok = stok - 1 WHERE id_buku = ?");
            $stmt3->bind_param("s", $id_buku);
            $stmt3->execute();

            $conn->commit();
            
            $message = "Transaksi peminjaman berhasil dicatat! Stok buku otomatis berkurang.";
            $alertType = 'success';
        } catch (Exception $e) {

            $conn->rollback();
            $message = "Terjadi kesalahan sistem: " . $e->getMessage();
            $alertType = 'danger';
        }
    }
}



if (isset($_GET['kembalikan']) && isset($_GET['buku'])) {
    $id_pmj_kembali = $_GET['kembalikan'];
    $id_buku_kembali = $_GET['buku'];
    $tgl_kembali = date('Y-m-d');

    $conn->begin_transaction();
    try {

        $stmt4 = $conn->prepare("UPDATE peminjaman SET status_transaksi = 'Selesai', tgl_kembali = ? WHERE id_peminjaman = ?");
        $stmt4->bind_param("ss", $tgl_kembali, $id_pmj_kembali);
        $stmt4->execute();

        $stmt5 = $conn->prepare("UPDATE buku SET stok = stok + 1 WHERE id_buku = ?");
        $stmt5->bind_param("s", $id_buku_kembali);
        $stmt5->execute();

        $conn->commit();
        $message = "Buku berhasil dikembalikan. Status diperbarui dan stok telah ditambahkan kembali.";
        $alertType = 'info';
    } catch (Exception $e) {
        $conn->rollback();
        $message = "Gagal memproses pengembalian: " . $e->getMessage();
        $alertType = 'danger';
    }
}



$anggotaList = $conn->query("SELECT id_anggota, nama FROM anggota WHERE status = 'Aktif' ORDER BY nama ASC");
$bukuList = $conn->query("SELECT id_buku, judul, stok FROM buku WHERE stok > 0 ORDER BY judul ASC");

$queryTransaksi = "
    SELECT p.id_peminjaman, p.tgl_pinjam, p.tgl_jatuh_tempo, p.tgl_kembali, p.status_transaksi, 
           a.nama AS nama_peminjam, b.id_buku, b.judul AS judul_buku
    FROM peminjaman p
    JOIN anggota a ON p.id_anggota = a.id_anggota
    JOIN detail_peminjaman dp ON p.id_peminjaman = dp.id_peminjaman
    JOIN buku b ON dp.id_buku = b.id_buku
    ORDER BY p.tgl_pinjam DESC
";
$transaksiList = $conn->query($queryTransaksi);

$activePage   = 'loans';
$pageTitle    = 'Sirkulasi & Laporan';
$pageSubtitle = 'Pencatatan transaksi peminjaman dan pengembalian buku';
$user         = $_SESSION['user'];
require 'partials/sidebar.php';
?>
        <h4 class="pb-page-heading mb-4"><span class="pb-heading-badge"><i class="bi bi-arrow-left-right"></i></span> Sirkulasi & Laporan</h4>

        <?php if (!empty($message)): ?>
            <div class="alert alert-<?= $alertType ?> alert-dismissible fade show shadow-sm" role="alert">
                <?= $message ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="row g-4">
            
            <div class="col-md-4">
                <div class="card shadow-sm border-0 border-top border-4 border-danger">
                    <div class="card-header bg-white fw-bold">
                        <i class="bi bi-cart-plus me-1"></i> Form Peminjaman Buku
                    </div>
                    <div class="card-body">
                        <form action="loans.php" method="POST">
                            <div class="mb-3">
                                <label class="form-label small fw-bold">Pilih Anggota</label>
                                <select name="id_anggota" class="form-select form-select-sm" required>
                                    <option value="">-- Pilih Mahasiswa --</option>
                                    <?php while ($agt = $anggotaList->fetch_assoc()): ?>
                                        <option value="<?= $agt['id_anggota'] ?>"><?= htmlspecialchars($agt['nama']) ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-bold">Pilih Buku</label>
                                <select name="id_buku" class="form-select form-select-sm" required>
                                    <option value="">-- Pilih Buku Tersedia --</option>
                                    <?php while ($bk = $bukuList->fetch_assoc()): ?>
                                        <option value="<?= $bk['id_buku'] ?>"><?= htmlspecialchars($bk['judul']) ?> (Stok: <?= $bk['stok'] ?>)</option>
                                    <?php endwhile; ?>
                                </select>
                                <div class="form-text small">*Hanya buku dengan stok > 0 yang tampil.</div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-bold">Tanggal Jatuh Tempo</label>
                                <input type="date" name="tgl_jatuh_tempo" class="form-control form-control-sm" required>
                                <div class="form-text small">Tanggal ini hanya diisi oleh admin/petugas ke pengunjung.</div>
                            </div>
                            <button type="submit" name="pinjam_buku" class="btn btn-danger w-100 btn-sm fw-bold">
                                <i class="bi bi-send-check me-1"></i> Proses Peminjaman
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            
            <div class="col-md-8">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white fw-bold d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-clock-history me-1"></i> Laporan Transaksi Peminjaman</span>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover table-striped align-middle m-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>ID Transaksi</th>
                                        <th>Peminjam</th>
                                        <th>Buku</th>
                                        <th>Tgl Pinjam</th>
                                        <th>Status / Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($transaksiList && $transaksiList->num_rows > 0): ?>
                                        <?php while ($row = $transaksiList->fetch_assoc()): ?>
                                            <tr>
                                                <td class="small font-monospace text-muted"><?= htmlspecialchars($row['id_peminjaman']) ?></td>
                                                <td class="fw-bold text-primary"><?= htmlspecialchars($row['nama_peminjam']) ?></td>
                                                <td class="small"><?= htmlspecialchars($row['judul_buku']) ?></td>
                                                <td class="small"><?= date('d M Y', strtotime($row['tgl_pinjam'])) ?></td>
                                                <td class="small">
                                                    <?php
                                                        $dueDate = strtotime($row['tgl_jatuh_tempo']);
                                                        $today = strtotime(date('Y-m-d'));
                                                        $isOverdue = $row['status_transaksi'] === 'Dipinjam' && $today > $dueDate;
                                                    ?>
                                                    <span class="badge <?= $isOverdue ? 'bg-danger' : 'bg-warning text-dark' ?>">
                                                        <?= date('d M Y', $dueDate) ?>
                                                    </span>
                                                    <?php if ($isOverdue): ?>
                                                        <div class="small text-danger mt-1">Lewat tempo, denda Rp 150.000</div>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if ($row['status_transaksi'] === 'Dipinjam'): ?>
                                                        <a href="loans.php?kembalikan=<?= urlencode($row['id_peminjaman']) ?>&buku=<?= urlencode($row['id_buku']) ?>" 
                                                           class="btn btn-sm btn-warning fw-bold text-dark w-100"
                                                           onclick="return confirm('Proses pengembalian buku ini? Stok akan otomatis bertambah kembali.');">
                                                           <i class="bi bi-arrow-return-left me-1"></i> Kembalikan
                                                        </a>
                                                    <?php else: ?>
                                                        <span class="badge bg-success w-100 py-2">
                                                            <i class="bi bi-check2-all me-1"></i> Selesai (<?= date('d/m/y', strtotime($row['tgl_kembali'])) ?>)
                                                        </span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="5" class="text-center text-muted py-4">Belum ada transaksi peminjaman.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

<?php require 'partials/footer.php'; ?>