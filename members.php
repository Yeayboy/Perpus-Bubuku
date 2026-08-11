<?php
require_once 'auth.php';
require_once 'db.php';

requireLogin();

$message = '';
$alertType = 'success';



if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tambah_anggota'])) {
    $id_anggota = trim($_POST['id_anggota']);
    $nim        = trim($_POST['nim']);
    $nama       = trim($_POST['nama']);
    $email      = trim($_POST['email']);
    $no_telepon = trim($_POST['no_telepon']);

    if (empty($id_anggota) || empty($nim) || empty($nama) || empty($email) || empty($no_telepon)) {
        $message = 'ID Anggota, NIM, Nama, Email, dan No. Telepon wajib diisi!';
        $alertType = 'danger';
    } else {

        $cekDuplikat = $conn->prepare("SELECT id_anggota FROM anggota WHERE id_anggota = ? OR nim = ? OR email = ?");
        $cekDuplikat->bind_param("sss", $id_anggota, $nim, $email);
        $cekDuplikat->execute();
        $resultCek = $cekDuplikat->get_result();

        if ($resultCek->num_rows > 0) {
            $message = "Peringatan! ID Anggota <strong>$id_anggota</strong>, NIM <strong>$nim</strong>, atau Email <strong>$email</strong> sudah terdaftar.";
            $alertType = 'warning';
        } else {

            $stmt = $conn->prepare("INSERT INTO anggota (id_anggota, nim, nama, email, no_telepon) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $id_anggota, $nim, $nama, $email, $no_telepon);
            
            if ($stmt->execute()) {
                $message = "Anggota <strong>$nama</strong> berhasil didaftarkan!";
                $alertType = 'success';
            } else {
                $message = "Gagal menambahkan anggota. Error: " . $conn->error;
                $alertType = 'danger';
            }
            $stmt->close();
        }
        $cekDuplikat->close();
    }
}



if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_anggota'])) {
    $id_anggota = trim($_POST['id_anggota']);
    $nim        = trim($_POST['nim']);
    $nama       = trim($_POST['nama']);
    $email      = trim($_POST['email']);
    $no_telepon = trim($_POST['no_telepon']);

    $stmt = $conn->prepare("UPDATE anggota SET nim = ?, nama = ?, email = ?, no_telepon = ? WHERE id_anggota = ?");
    $stmt->bind_param("sssss", $nim, $nama, $email, $no_telepon, $id_anggota);
    
    if ($stmt->execute()) {
        $message = "Anggota <strong>$nama</strong> berhasil diperbarui!";
        $alertType = 'success';
    } else {
        $message = "Gagal memperbarui anggota. Error: " . $conn->error;
        $alertType = 'danger';
    }
    $stmt->close();
}



if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['hapus_anggota'])) {
    $id_hapus = htmlspecialchars($_POST['id_anggota_hapus']);
    
    $cekAnggota = $conn->query("SELECT nama FROM anggota WHERE id_anggota = '$id_hapus'");
    
    if ($cekAnggota && $cekAnggota->num_rows > 0) {
        $data = $cekAnggota->fetch_assoc();

        $conn->query("DELETE FROM detail_peminjaman WHERE id_peminjaman IN (SELECT id_peminjaman FROM peminjaman WHERE id_anggota = '$id_hapus')");

        $conn->query("DELETE FROM peminjaman WHERE id_anggota = '$id_hapus'");

        if ($conn->query("DELETE FROM anggota WHERE id_anggota = '$id_hapus'")) {
            $message = "Anggota <strong>{$data['nama']}</strong> beserta semua data peminjamanya berhasil dihapus!";
            $alertType = 'success';
        } else {
            $message = "Gagal menghapus anggota.";
            $alertType = 'danger';
        }
    }
}

$queryAnggota = "SELECT * FROM anggota ORDER BY id_anggota ASC";
$resultAnggota = $conn->query($queryAnggota);
?>
<?php
$activePage   = 'members';
$pageTitle    = 'Data Anggota';
$pageSubtitle = 'Kelola data anggota, daftar anggota baru, dan hapus anggota';
$user         = $_SESSION['user'];
require 'partials/sidebar.php';
?>
        <h4 class="pb-page-heading mb-4"><span class="pb-heading-badge"><i class="bi bi-people-fill"></i></span> Kelola Data Anggota</h4>

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
                        <i class="bi bi-person-plus me-1"></i> Daftarkan Anggota Baru
                    </div>
                    <div class="card-body">
                        <form action="" method="POST">
                            <div class="mb-3">
                                <label class="form-label small fw-bold">ID Anggota</label>
                                <input type="text" name="id_anggota" class="form-control form-control-sm" placeholder="Contoh: ANG-001" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-bold">NIM</label>
                                <input type="text" name="nim" class="form-control form-control-sm" placeholder="Nomor Induk Mahasiswa" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-bold">Nama Lengkap</label>
                                <input type="text" name="nama" class="form-control form-control-sm" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-bold">Email</label>
                                <input type="email" name="email" class="form-control form-control-sm" required>
                            </div>
                            <div class="mb-4">
                                <label class="form-label small fw-bold">No. Telepon</label>
                                <input type="text" name="no_telepon" class="form-control form-control-sm" placeholder="08xxxxxxxxxx" required>
                            </div>
                            <button type="submit" name="tambah_anggota" class="btn btn-info w-100 btn-sm fw-bold text-white">
                                <i class="bi bi-plus-circle me-1"></i> Daftarkan
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            
            <div class="col-lg-9">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white fw-bold">
                        <i class="bi bi-table me-1"></i> Daftar Anggota Terdaftar
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
                                        <th>No. Telepon</th>
                                        <th class="text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($resultAnggota && $resultAnggota->num_rows > 0): ?>
                                        <?php while ($row = $resultAnggota->fetch_assoc()): ?>
                                            <tr>
                                                <td><span class="badge bg-secondary"><?= htmlspecialchars($row['id_anggota']) ?></span></td>
                                                <td class="small fw-bold"><?= htmlspecialchars($row['nim']) ?></td>
                                                <td class="fw-bold"><?= htmlspecialchars($row['nama']) ?></td>
                                                <td class="small"><?= htmlspecialchars($row['email']) ?></td>
                                                <td class="small text-muted"><?= htmlspecialchars($row['no_telepon']) ?></td>
                                                <td class="text-center">
                                                    <button type="button" class="btn btn-sm btn-warning me-1" data-bs-toggle="modal" data-bs-target="#editModal<?= $row['id_anggota'] ?>"><i class="bi bi-pencil-square me-1"></i>Edit</button>
                                                    </td>
                                                <td class="text-center">
                                                    
                                                    <form method="POST" action="" style="display:inline;">
                                                        <input type="hidden" name="id_anggota_hapus" value="<?= htmlspecialchars($row['id_anggota']) ?>">
                                                        <button type="submit" name="hapus_anggota" class="btn btn-sm btn-danger" onclick="return confirm('Yakin hapus anggota ini?');"><i class="bi bi-trash me-1"></i>Hapus</button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="6" class="text-center text-muted py-4">Belum ada data anggota.</td>
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
    $queryAnggota = "SELECT * FROM anggota ORDER BY id_anggota ASC";
    $resultAnggota = $conn->query($queryAnggota);
    if ($resultAnggota && $resultAnggota->num_rows > 0):
        while ($row = $resultAnggota->fetch_assoc()): 
    ?>
    <div class="modal fade" id="editModal<?= $row['id_anggota'] ?>" tabindex="-1" aria-labelledby="editLabel<?= $row['id_anggota'] ?>" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-warning">
                    <h5 class="modal-title" id="editLabel<?= $row['id_anggota'] ?>"><i class="bi bi-pencil-square me-2"></i>Edit Anggota</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <input type="hidden" name="id_anggota" value="<?= $row['id_anggota'] ?>">
                        <div class="mb-3">
                            <label class="form-label fw-bold">ID Anggota</label>
                            <input type="text" class="form-control" value="<?= htmlspecialchars($row['id_anggota']) ?>" disabled>
                            <small class="text-muted">ID Anggota tidak dapat diubah</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">NIM</label>
                            <input type="text" name="nim" class="form-control" value="<?= htmlspecialchars($row['nim']) ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Nama Lengkap</label>
                            <input type="text" name="nama" class="form-control" value="<?= htmlspecialchars($row['nama']) ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Email</label>
                            <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($row['email']) ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">No. Telepon</label>
                            <input type="text" name="no_telepon" class="form-control" value="<?= htmlspecialchars($row['no_telepon']) ?>" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="edit_anggota" class="btn btn-warning"><i class="bi bi-check-lg me-1"></i>Perbarui</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endwhile; endif; ?>

<?php require 'partials/footer.php'; ?>
