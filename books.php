<?php
require_once 'auth.php';
require_once 'db.php';

requireLogin();

$message = '';
$alertType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tambah_buku'])) {
    $id_buku     = trim($_POST['id_buku']);
    $isbn        = trim($_POST['isbn']);
    $judul       = trim($_POST['judul']);
    $pengarang   = trim($_POST['pengarang']);
    $stok        = (int) $_POST['stok'];
    $id_kategori = (int) $_POST['id_kategori'];

    if (empty($id_buku) || empty($isbn) || empty($judul)) {
        $message = 'ID Buku, ISBN, dan Judul wajib diisi!';
        $alertType = 'danger';
    } else {

        $cekDuplikat = $conn->prepare("SELECT id_buku FROM buku WHERE id_buku = ? OR isbn = ?");
        $cekDuplikat->bind_param("ss", $id_buku, $isbn);
        $cekDuplikat->execute();
        $resultCek = $cekDuplikat->get_result();

        if ($resultCek->num_rows > 0) {

            $message = "Peringatan! ID Buku <strong>$id_buku</strong> atau ISBN <strong>$isbn</strong> sudah digunakan. Harap gunakan data lain.";
            $alertType = 'warning'; 
        } else {

            $stmt = $conn->prepare("INSERT INTO buku (id_buku, isbn, judul, pengarang, stok, id_kategori) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssii", $id_buku, $isbn, $judul, $pengarang, $stok, $id_kategori);
            
            if ($stmt->execute()) {
                $message = "Buku <strong>$judul</strong> berhasil ditambahkan ke database!";
                $alertType = 'success';
            } else {
                $message = "Gagal menambahkan buku. Error: " . $conn->error;
                $alertType = 'danger';
            }
            $stmt->close();
        }
        $cekDuplikat->close();
    }
}



if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_buku'])) {
    $id_buku     = trim($_POST['id_buku']);
    $isbn        = trim($_POST['isbn']);
    $judul       = trim($_POST['judul']);
    $pengarang   = trim($_POST['pengarang']);
    $stok        = (int) $_POST['stok'];
    $id_kategori = (int) $_POST['id_kategori'];

    $stmt = $conn->prepare("UPDATE buku SET isbn = ?, judul = ?, pengarang = ?, stok = ?, id_kategori = ? WHERE id_buku = ?");
    $stmt->bind_param("sssiis", $isbn, $judul, $pengarang, $stok, $id_kategori, $id_buku);
    
    if ($stmt->execute()) {
        $message = "Buku <strong>$judul</strong> berhasil diperbarui!";
        $alertType = 'success';
    } else {
        $message = "Gagal memperbarui buku. Error: " . $conn->error;
        $alertType = 'danger';
    }
    $stmt->close();
}



if (isset($_GET['hapus'])) {
    $id_hapus = trim($_GET['hapus']);

    $stmtCekBuku = $conn->prepare("SELECT judul FROM buku WHERE id_buku = ?");
    $stmtCekBuku->bind_param("s", $id_hapus);
    $stmtCekBuku->execute();
    $resultCekBuku = $stmtCekBuku->get_result();

    if ($resultCekBuku && $resultCekBuku->num_rows > 0) {
        $data = $resultCekBuku->fetch_assoc();

        $stmtCekGuest = $conn->prepare("SELECT id_entry FROM guest_books WHERE id_buku = ? LIMIT 1");
        $stmtCekGuest->bind_param("s", $id_hapus);
        $stmtCekGuest->execute();
        $resultCekGuest = $stmtCekGuest->get_result();

        $stmtCekDetail = $conn->prepare("SELECT id_detail FROM detail_peminjaman WHERE id_buku = ? LIMIT 1");
        $stmtCekDetail->bind_param("s", $id_hapus);
        $stmtCekDetail->execute();
        $resultCekDetail = $stmtCekDetail->get_result();

        if ($resultCekGuest->num_rows > 0 || $resultCekDetail->num_rows > 0) {
            $message = "Buku <strong>{$data['judul']}</strong> tidak dapat dihapus karena masih digunakan pada data peminjaman/guest book.";
            $alertType = 'warning';
        } else {
            $stmtDelete = $conn->prepare("DELETE FROM buku WHERE id_buku = ?");
            $stmtDelete->bind_param("s", $id_hapus);

            if ($stmtDelete->execute()) {
                $message = "Buku <strong>{$data['judul']}</strong> berhasil dihapus!";
                $alertType = 'success';
            } else {
                $message = "Gagal menghapus buku. Error: " . $conn->error;
                $alertType = 'danger';
            }

            $stmtDelete->close();
        }

        $stmtCekGuest->close();
        $stmtCekDetail->close();
    } else {
        $message = "Buku yang akan dihapus tidak ditemukan.";
        $alertType = 'warning';
    }

    $stmtCekBuku->close();
}

$queryBuku = "
    SELECT b.id_buku, b.isbn, b.judul, b.pengarang, b.stok, b.id_kategori, k.nama_kategori 
    FROM buku b 
    LEFT JOIN kategori k ON b.id_kategori = k.id_kategori 
    ORDER BY b.id_buku ASC
";
$resultBuku = $conn->query($queryBuku);

$resultKategori = $conn->query("SELECT * FROM kategori ORDER BY nama_kategori ASC");

$activePage   = 'books';
$pageTitle    = 'Master Data Buku';
$pageSubtitle = 'Kelola data buku, kategori, dan ketersediaan stok fisik';
$user         = $_SESSION['user'];
require 'partials/sidebar.php';
?>
        <h4 class="pb-page-heading mb-4"><span class="pb-heading-badge"><i class="bi bi-journal-text"></i></span> Kelola Master Data Buku</h4>

        <?php if (!empty($message)): ?>
            <div class="alert alert-<?= $alertType ?> alert-dismissible fade show shadow-sm" role="alert">
                <?= $message ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="row g-4">
            
            <div class="col-md-4">
                <div class="card shadow-sm border-0 border-top border-4 border-primary">
                    <div class="card-header bg-white fw-bold">
                        <i class="bi bi-plus-circle me-1"></i> Tambah Buku Baru
                    </div>
                    <div class="card-body">
                        <form action="" method="POST">
                            <div class="mb-3">
                                <label class="form-label small fw-bold">ID Buku</label>
                                <input type="text" name="id_buku" class="form-control form-control-sm" placeholder="Contoh: BUK-005" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-bold">ISBN</label>
                                <input type="text" name="isbn" class="form-control form-control-sm" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-bold">Judul Buku</label>
                                <input type="text" name="judul" class="form-control form-control-sm" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-bold">Pengarang</label>
                                <input type="text" name="pengarang" class="form-control form-control-sm" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-bold">Kategori</label>
                                <select name="id_kategori" class="form-select form-select-sm" required>
                                    <option value="">-- Pilih Kategori --</option>
                                    <?php while ($kat = $resultKategori->fetch_assoc()): ?>
                                        <option value="<?= $kat['id_kategori'] ?>"><?= htmlspecialchars($kat['nama_kategori']) ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="mb-4">
                                <label class="form-label small fw-bold">Stok Awal</label>
                                <input type="number" name="stok" class="form-control form-control-sm" value="0" min="0" required>
                            </div>
                            <button type="submit" name="tambah_buku" class="btn btn-primary w-100 btn-sm fw-bold">
                                <i class="bi bi-save me-1"></i> Simpan Buku
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            
            <div class="col-md-8">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white fw-bold">
                        <i class="bi bi-table me-1"></i> Daftar Buku Tersedia
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover table-striped align-middle m-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>ID</th>
                                        <th>ISBN</th>
                                        <th>Judul & Pengarang</th>
                                        <th>Kategori</th>
                                        <th class="text-center">Stok</th>
                                        <th class="text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($resultBuku && $resultBuku->num_rows > 0): ?>
                                        <?php while ($row = $resultBuku->fetch_assoc()): ?>
                                            <tr>
                                                <td><span class="badge bg-secondary"><?= htmlspecialchars($row['id_buku']) ?></span></td>
                                                <td class="small"><?= htmlspecialchars($row['isbn']) ?></td>
                                                <td>
                                                    <div class="fw-bold"><?= htmlspecialchars($row['judul']) ?></div>
                                                    <div class="small text-muted"><?= htmlspecialchars($row['pengarang']) ?></div>
                                                </td>
                                                <td><span class="badge bg-info text-dark"><?= htmlspecialchars($row['nama_kategori']) ?></span></td>
                                                <td class="text-center">
                                                    <?php if ($row['stok'] > 0): ?>
                                                        <span class="badge bg-success"><?= $row['stok'] ?></span>
                                                    <?php else: ?>
                                                        <span class="badge bg-danger">Habis</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-center">
                                                    <button type="button" class="btn btn-sm btn-warning me-1" data-bs-toggle="modal" data-bs-target="#editModal<?= $row['id_buku'] ?>"><i class="bi bi-pencil-square me-1"></i>Edit</button>
                                                    <a href="?hapus=<?= urlencode($row['id_buku']) ?>" class="btn btn-sm btn-danger" onclick="return confirm('Yakin hapus buku ini?');"><i class="bi bi-trash me-1"></i>Hapus</a>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="6" class="text-center text-muted py-4">Belum ada data buku.</td>
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

    $resultKategori = $conn->query("SELECT * FROM kategori ORDER BY nama_kategori ASC");

    $queryBuku = "
        SELECT b.id_buku, b.isbn, b.judul, b.pengarang, b.stok, b.id_kategori, k.nama_kategori 
        FROM buku b 
        LEFT JOIN kategori k ON b.id_kategori = k.id_kategori 
        ORDER BY b.id_buku ASC
    ";
    $resultBuku = $conn->query($queryBuku);
    
    if ($resultBuku && $resultBuku->num_rows > 0):
        while ($row = $resultBuku->fetch_assoc()): 
    ?>
    <div class="modal fade" id="editModal<?= $row['id_buku'] ?>" tabindex="-1" aria-labelledby="editLabel<?= $row['id_buku'] ?>" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-warning">
                    <h5 class="modal-title" id="editLabel<?= $row['id_buku'] ?>"><i class="bi bi-pencil-square me-2"></i>Edit Buku</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <input type="hidden" name="id_buku" value="<?= $row['id_buku'] ?>">
                        <div class="mb-3">
                            <label class="form-label fw-bold">ID Buku</label>
                            <input type="text" class="form-control" value="<?= htmlspecialchars($row['id_buku']) ?>" disabled>
                            <small class="text-muted">ID Buku tidak dapat diubah</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">ISBN</label>
                            <input type="text" name="isbn" class="form-control" value="<?= htmlspecialchars($row['isbn']) ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Judul Buku</label>
                            <input type="text" name="judul" class="form-control" value="<?= htmlspecialchars($row['judul']) ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Pengarang</label>
                            <input type="text" name="pengarang" class="form-control" value="<?= htmlspecialchars($row['pengarang']) ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Kategori</label>
                            <select name="id_kategori" class="form-select" required>
                                <option value="">-- Pilih Kategori --</option>
                                <?php 
                                $resultKategoriEdit = $conn->query("SELECT * FROM kategori ORDER BY nama_kategori ASC");
                                while ($kat = $resultKategoriEdit->fetch_assoc()): 
                                ?>
                                    <option value="<?= $kat['id_kategori'] ?>" <?= ($row['id_kategori'] == $kat['id_kategori']) ? 'selected' : '' ?>><?= htmlspecialchars($kat['nama_kategori']) ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Stok</label>
                            <input type="number" name="stok" class="form-control" value="<?= $row['stok'] ?>" min="0" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="edit_buku" class="btn btn-warning"><i class="bi bi-check-lg me-1"></i>Perbarui</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endwhile; endif; ?>

<?php require 'partials/footer.php'; ?>