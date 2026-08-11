<?php
require_once 'auth.php';
require_once 'db.php';

requireRole(['admin']);

$message = '';
if (isset($_GET['export']) && $_GET['export'] === 'csv') {

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=Data_Anggota_Perpus_' . date('Ymd') . '.csv');

    $output = fopen('php://output', 'w');

    fputcsv($output, array('id_anggota', 'nim', 'nama', 'email', 'status'));

    $queryEkspor = $conn->query("SELECT id_anggota, nim, nama, email, status FROM anggota ORDER BY id_anggota ASC");
    while ($row = $queryEkspor->fetch_assoc()) {
        fputcsv($output, $row);
    }
    
    fclose($output);
    exit; 
}

$alertType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file_csv'])) {
    $file = $_FILES['file_csv'];

    $fileExtension = pathinfo($file['name'], PATHINFO_EXTENSION);
    if (strtolower($fileExtension) !== 'csv') {
        $message = 'Format file tidak valid! Harap unggah file berkestensi .csv';
        $alertType = 'danger';
    } else {
        if (($handle = fopen($file['tmp_name'], 'r')) !== FALSE) {

            fgetcsv($handle, 1000, ',');

            $successCount = 0;
            $failCount = 0;

            $stmt = $conn->prepare("INSERT INTO anggota (id_anggota, nim, nama, email, status) VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE nama=VALUES(nama), email=VALUES(email)");

            while (($data = fgetcsv($handle, 1000, ',')) !== FALSE) {

                if (count($data) >= 5) {
                    $id_anggota = trim($data[0]);
                    $nim        = trim($data[1]);
                    $nama       = trim($data[2]);
                    $email      = trim($data[3]);
                    $status     = trim($data[4]);

                    $stmt->bind_param("sssss", $id_anggota, $nim, $nama, $email, $status);
                    
                    if ($stmt->execute()) {
                        $successCount++;
                    } else {
                        $failCount++;
                    }
                }
            }
            fclose($handle);
            $stmt->close();

            $message = "Integrasi Data Berhasil! <strong>{$successCount}</strong> data berhasil diimpor/diperbarui, <strong>{$failCount}</strong> gagal.";
            $alertType = 'success';
        } else {
            $message = 'Gagal membuka file CSV!';
            $alertType = 'danger';
        }
    }
}

$resultAnggota = $conn->query("SELECT * FROM anggota ORDER BY id_anggota ASC");
?>

<?php
$activePage   = 'import';
$pageTitle    = 'Impor Data Anggota';
$pageSubtitle = 'Integrasi data anggota eksternal dari format CSV';
$user         = $_SESSION['user'];
require 'partials/sidebar.php';
?>
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="pb-page-heading m-0"><span class="pb-heading-badge"><i class="bi bi-file-earmark-arrow-up"></i></span> Integrasi Data Anggota (CSV)</h4>
        </div>

        <?php if (!empty($message)): ?>
            <div class="alert alert-<?= $alertType ?> alert-dismissible fade show" role="alert">
                <?= $message ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white font-weight-bold">
                <i class="bi bi-upload me-1"></i> Form Unggah File CSV
            </div>
            <div class="card-body">
                <form action="" method="POST" enctype="multipart/form-data" class="row g-3 align-items-center">
                    <div class="col-md-8">
                        <input type="file" name="file_csv" class="form-control" accept=".csv" required>
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-success w-100">
                            <i class="bi bi-database-add me-1"></i> Impor Data CSV
                        </button>
                    </div>
                </form>
                <small class="text-muted mt-2 d-block">
                    * Format CSV harus berurutan: <code>id_anggota, nim, nama, email, status</code>
                </small>
            </div>
        </div>

        
        <div class="card shadow-sm border-0">
           <div class="card-header bg-white font-weight-bold d-flex justify-content-between align-items-center py-3">
                <span><i class="bi bi-people me-1"></i> Daftar Anggota Terdaftar</span>
                
                
                <a href="import_members.php?export=csv" class="btn btn-sm btn-outline-primary fw-bold">
                    <i class="bi bi-file-earmark-arrow-down me-1"></i> Ekspor Data (CSV)
                </a>
                
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-striped align-middle m-0">
                        <thead class="table-light">
                            <tr>
                                <th>ID Anggota</th>
                                <th>NIM</th>
                                <th>Nama Lengkap</th>
                                <th>Email</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($resultAnggota && $resultAnggota->num_rows > 0): ?>
                                <?php while ($row = $resultAnggota->fetch_assoc()): ?>
                                    <tr>
                                        <td><span class="badge bg-secondary"><?= htmlspecialchars($row['id_anggota']) ?></span></td>
                                        <td><?= htmlspecialchars($row['nim']) ?></td>
                                        <td class="fw-bold"><?= htmlspecialchars($row['nama']) ?></td>
                                        <td><?= htmlspecialchars($row['email']) ?></td>
                                        <td>
                                            <?php if ($row['status'] === 'Aktif'): ?>
                                                <span class="badge bg-success">Aktif</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">Nonaktif</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-3">Belum ada data anggota.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

<?php require 'partials/footer.php'; ?>