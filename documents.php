<?php
require_once 'auth.php';
require_once 'db.php';

requireLogin();

$message = '';
$alertType = 'success';

$uploadDir = 'uploads/documents/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_dokumen'])) {
    $judul_dokumen = trim($_POST['judul_dokumen']);
    $versi         = trim($_POST['versi']);

    if (isset($_FILES['file_dokumen']) && $_FILES['file_dokumen']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath   = $_FILES['file_dokumen']['tmp_name'];
        $fileName      = $_FILES['file_dokumen']['name'];
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        $jenis_file  = strtoupper($fileExtension);
        $newFileName = time() . '_' . preg_replace("/[^a-zA-Z0-9.]/", "_", $fileName);
        $lokasi_file = $uploadDir . $newFileName;

        if (move_uploaded_file($fileTmpPath, $lokasi_file)) {

            $stmt = $conn->prepare("INSERT INTO dokumen_metadata (judul_dokumen, nama_file, jenis_file, lokasi_file, versi) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $judul_dokumen, $newFileName, $jenis_file, $lokasi_file, $versi);
            
            if ($stmt->execute()) {
                $message = "Dokumen <strong>$judul_dokumen</strong> berhasil diunggah dan metadata tersimpan!";
                $alertType = 'success';
            } else {
                $message = "Gagal menyimpan metadata ke database. Error: " . $conn->error;
                $alertType = 'danger';
            }
            $stmt->close();
        } else {
            $message = "Terjadi kesalahan sistem saat memindahkan file fisik.";
            $alertType = 'danger';
        }
    } else {
        $message = "Harap pilih file dokumen yang valid!";
        $alertType = 'danger';
    }
}



if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_dokumen'])) {
    $id_edit       = (int) $_POST['id_dokumen'];
    $judul_dokumen = trim($_POST['judul_dokumen']);
    $versi         = trim($_POST['versi']);

    $stmt = $conn->prepare("UPDATE dokumen_metadata SET judul_dokumen = ?, versi = ? WHERE id_dokumen = ?");
    $stmt->bind_param("ssi", $judul_dokumen, $versi, $id_edit);
    
    if ($stmt->execute()) {
        $message = "Dokumen <strong>$judul_dokumen</strong> berhasil diperbarui!";
        $alertType = 'success';
    } else {
        $message = "Gagal memperbarui dokumen. Error: " . $conn->error;
        $alertType = 'danger';
    }
    $stmt->close();
}



if (isset($_GET['hapus'])) {
    $id_hapus = (int) $_GET['hapus'];

    $cekFile = $conn->query("SELECT judul_dokumen, lokasi_file FROM dokumen_metadata WHERE id_dokumen = $id_hapus");
    
    if ($cekFile && $cekFile->num_rows > 0) {
        $data = $cekFile->fetch_assoc();
        $lokasi_fisik = $data['lokasi_file'];

        if (file_exists($lokasi_fisik)) {
            unlink($lokasi_fisik);
        }

        if ($conn->query("DELETE FROM dokumen_metadata WHERE id_dokumen = $id_hapus")) {
            $message = "Dokumen <strong>{$data['judul_dokumen']}</strong> beserta file fisiknya berhasil dihapus!";
            $alertType = 'success';
        } else {
            $message = "Gagal menghapus data dari database.";
            $alertType = 'danger';
        }
    }
}

$queryDocs = $conn->query("SELECT * FROM dokumen_metadata ORDER BY tgl_unggah DESC");

$activePage   = 'documents';
$pageTitle    = 'Metadata Dokumen';
$pageSubtitle = 'Arsip digital, SOP, dan repository dokumen kampus';
$user         = $_SESSION['user'];
require 'partials/sidebar.php';
?>
        <h4 class="pb-page-heading mb-4"><span class="pb-heading-badge"><i class="bi bi-folder2-open"></i></span> Kelola Metadata Dokumen</h4>

        <?php if (!empty($message)): ?>
            <div class="alert alert-<?= $alertType ?> alert-dismissible fade show shadow-sm" role="alert">
                <?= $message ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="row g-3">
            
            <div class="col-lg-3">
                <div class="card shadow-sm border-0 border-top border-4 border-info">
                    <div class="card-header bg-white fw-bold">
                        <i class="bi bi-cloud-upload me-1"></i> Form Unggah Arsip
                    </div>
                    <div class="card-body">
                        <form action="" method="POST" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label class="form-label small fw-bold">Judul Dokumen</label>
                                <input type="text" name="judul_dokumen" class="form-control form-control-sm" placeholder="Contoh: SOP Peminjaman" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-bold">Versi Revisi</label>
                                <input type="text" name="versi" class="form-control form-control-sm" placeholder="Contoh: 1.0" required>
                            </div>
                            <div class="mb-4">
                                <label class="form-label small fw-bold">File Dokumen</label>
                                <input type="file" name="file_dokumen" class="form-control form-control-sm" required>
                                <div class="form-text small">Mendukung file .pdf, .docx, .xlsx</div>
                            </div>
                            <button type="submit" name="upload_dokumen" class="btn btn-info w-100 btn-sm fw-bold text-white">
                                <i class="bi bi-upload me-1"></i> Simpan & Unggah
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            
            <div class="col-lg-9">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white fw-bold">
                        <i class="bi bi-card-list me-1"></i> Repository Metadata Dokumen
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover table-striped align-middle m-0">
                                <thead class="table-light">
                                    <tr>
                                        <th style="min-width: 200px;">Judul Dokumen</th>
                                        <th style="min-width: 120px;">Jenis & Versi</th>
                                        <th style="min-width: 200px;">Lokasi Server (Path)</th>
                                        <th style="min-width: 220px;">Waktu Unggah & Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($queryDocs && $queryDocs->num_rows > 0): ?>
                                        <?php while ($row = $queryDocs->fetch_assoc()): ?>
                                            <tr>
                                                <td class="fw-bold text-primary">
                                                    <a href="<?= htmlspecialchars($row['lokasi_file']) ?>" class="text-decoration-none text-primary" target="_blank" download="<?= htmlspecialchars($row['nama_file']) ?>">
                                                    <i class="bi bi-file-earmark-text me-1"></i><?= htmlspecialchars($row['judul_dokumen']) ?>
                                                    </a>
                                                    <div class="small text-muted fw-normal mt-1"><?= htmlspecialchars($row['nama_file']) ?></div>
                                                </td>
                                                <td>
                                                    <span class="badge bg-secondary"><?= htmlspecialchars($row['jenis_file']) ?></span>
                                                    <span class="badge bg-light text-dark border">V <?= htmlspecialchars($row['versi']) ?></span>
                                                </td>
                                                <td class="small text-muted font-monospace" style="word-break: break-all;">
                                                    <?= htmlspecialchars($row['lokasi_file']) ?>
                                                </td>
                                                <td class="small">
                                                    <div class="mb-2"><?= date('d M Y, H:i', strtotime($row['tgl_unggah'])) ?></div>
                                                    <button type="button" class="btn btn-sm btn-warning me-1" data-bs-toggle="modal" data-bs-target="#editModal<?= $row['id_dokumen'] ?>"><i class="bi bi-pencil-square me-1"></i>Edit</button>
                                                    <a href="?hapus=<?= $row['id_dokumen'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Yakin hapus dokumen ini?');"><i class="bi bi-trash me-1"></i>Hapus</a>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="4" class="text-center text-muted py-4">Belum ada metadata dokumen yang diunggah.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    
    <?php 

    $queryDocs = $conn->query("SELECT * FROM dokumen_metadata ORDER BY tgl_unggah DESC");
    if ($queryDocs && $queryDocs->num_rows > 0):
        while ($row = $queryDocs->fetch_assoc()): 
    ?>
    <div class="modal fade" id="editModal<?= $row['id_dokumen'] ?>" tabindex="-1" aria-labelledby="editLabel<?= $row['id_dokumen'] ?>" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-warning">
                    <h5 class="modal-title" id="editLabel<?= $row['id_dokumen'] ?>"><i class="bi bi-pencil-square me-2"></i>Edit Dokumen</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <input type="hidden" name="id_dokumen" value="<?= $row['id_dokumen'] ?>">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Judul Dokumen</label>
                            <input type="text" name="judul_dokumen" class="form-control" value="<?= htmlspecialchars($row['judul_dokumen']) ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Versi Revisi</label>
                            <input type="text" name="versi" class="form-control" value="<?= htmlspecialchars($row['versi']) ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">File Saat Ini</label>
                            <div class="alert alert-info small mb-0">
                                <i class="bi bi-info-circle me-2"></i><?= htmlspecialchars($row['nama_file']) ?>
                                <br><small class="mt-2 d-block">Untuk mengganti file, unggah dokumen baru dengan judul berbeda</small>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="edit_dokumen" class="btn btn-warning"><i class="bi bi-check-lg me-1"></i>Perbarui</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endwhile; endif; ?>

<?php require 'partials/footer.php'; ?>