<?php
require_once 'auth.php';
require_once 'db.php';
requireRole(['tamu']);

$id_anggota = $_SESSION['user']['id_user'];

$message = '';
$alertType = 'success';

$stmt = $conn->prepare("SELECT nim, nama FROM anggota WHERE id_anggota = ?");
$stmt->bind_param('s', $id_anggota);
$stmt->execute();
$result = $stmt->get_result();
$guest = $result->fetch_assoc();
$stmt->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_personal_book'])) {
    $id_buku = trim($_POST['id_buku'] ?? '');
    $status = trim($_POST['status'] ?? 'Membaca');
    $catatan = trim($_POST['catatan'] ?? '');

    if (empty($id_buku)) {
        $message = 'Pilih buku terlebih dahulu untuk dimasukkan ke daftar pribadi.';
        $alertType = 'danger';
    } else {
        $stmt = $conn->prepare("INSERT INTO guest_books (id_anggota, id_buku, status, catatan) VALUES (?, ?, ?, ?)");
        $stmt->bind_param('ssss', $id_anggota, $id_buku, $status, $catatan);
        if ($stmt->execute()) {
            $message = 'Buku berhasil ditambahkan ke daftar pribadi Anda.';
            $alertType = 'success';
        } else {
            $message = 'Gagal menambahkan buku: ' . $conn->error;
            $alertType = 'danger';
        }
        $stmt->close();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_personal_book'])) {
    $id_entry = trim($_POST['id_entry'] ?? '');
    $status = trim($_POST['status'] ?? 'Membaca');
    $catatan = trim($_POST['catatan'] ?? '');

    if (!empty($id_entry)) {
        $stmt = $conn->prepare("UPDATE guest_books SET status = ?, catatan = ? WHERE id_entry = ? AND id_anggota = ?");
        $stmt->bind_param('ssss', $status, $catatan, $id_entry, $id_anggota);
        if ($stmt->execute()) {
            $message = 'Daftar bacaan pribadi berhasil diperbarui.';
            $alertType = 'success';
        } else {
            $message = 'Gagal memperbarui daftar buku: ' . $conn->error;
            $alertType = 'danger';
        }
        $stmt->close();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_personal_book'])) {
    $id_entry = trim($_POST['id_entry'] ?? '');
    if (!empty($id_entry)) {
        $stmt = $conn->prepare("DELETE FROM guest_books WHERE id_entry = ? AND id_anggota = ?");
        $stmt->bind_param('ss', $id_entry, $id_anggota);
        $stmt->execute();
        $stmt->close();
        $message = 'Buku berhasil dihapus dari daftar pribadi.';
        $alertType = 'info';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_reminder'])) {
    $judul = trim($_POST['judul'] ?? '');
    $tanggal_target = trim($_POST['tanggal_target'] ?? '');
    $keterangan = trim($_POST['keterangan'] ?? '');

    if (empty($judul) || empty($tanggal_target)) {
        $message = 'Judul dan tanggal reminder wajib diisi.';
        $alertType = 'danger';
    } else {
        $stmt = $conn->prepare("INSERT INTO guest_reminders (id_anggota, judul, tanggal_target, keterangan) VALUES (?, ?, ?, ?)");
        $stmt->bind_param('ssss', $id_anggota, $judul, $tanggal_target, $keterangan);
        if ($stmt->execute()) {
            $message = 'Reminder berhasil ditambahkan.';
            $alertType = 'success';
        } else {
            $message = 'Gagal membuat reminder: ' . $conn->error;
            $alertType = 'danger';
        }
        $stmt->close();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['complete_reminder'])) {
    $id_reminder = trim($_POST['id_reminder'] ?? '');
    if (!empty($id_reminder)) {
        $stmt = $conn->prepare("UPDATE guest_reminders SET status = 'Selesai' WHERE id_reminder = ? AND id_anggota = ?");
        $stmt->bind_param('ss', $id_reminder, $id_anggota);
        $stmt->execute();
        $stmt->close();
        $message = 'Reminder telah ditandai selesai.';
        $alertType = 'success';
    }
}

ensureColumnExists($conn, 'peminjaman', 'tgl_jatuh_tempo', "DATE NOT NULL DEFAULT '1970-01-01'");
$books = $conn->query("SELECT id_buku, judul, pengarang, stok FROM buku ORDER BY judul ASC");
$bookCards = [];
while ($book = $books->fetch_assoc()) {
    $bookCards[] = $book;
}

$personalBooks = $conn->prepare("SELECT gb.id_entry, gb.status, gb.catatan, b.id_buku, b.judul, b.pengarang FROM guest_books gb JOIN buku b ON gb.id_buku = b.id_buku WHERE gb.id_anggota = ? ORDER BY gb.tgl_tambah DESC");
$personalBooks->bind_param('s', $id_anggota);
$personalBooks->execute();
$personalBooksResult = $personalBooks->get_result();
$personalBooks->close();

$activeLoans = $conn->prepare("SELECT p.id_peminjaman, p.tgl_pinjam, p.tgl_jatuh_tempo, b.judul, b.pengarang FROM peminjaman p JOIN detail_peminjaman dp ON p.id_peminjaman = dp.id_peminjaman JOIN buku b ON dp.id_buku = b.id_buku WHERE p.id_anggota = ? AND p.status_transaksi = 'Dipinjam' ORDER BY p.tgl_jatuh_tempo ASC");
$activeLoans->bind_param('s', $id_anggota);
$activeLoans->execute();
$activeLoansResult = $activeLoans->get_result();
$activeLoans->close();

$fines = $conn->prepare("SELECT id_denda, jumlah, keterangan, status, tgl_catat FROM guest_fines WHERE id_anggota = ? ORDER BY tgl_catat DESC");
$fines->bind_param('s', $id_anggota);
$fines->execute();
$finesResult = $fines->get_result();
$fines->close();
?>
<?php
$activePage   = 'guest_dashboard';
$pageTitle    = 'Dashboard Pengunjung';
$pageSubtitle = 'Daftar bacaan pribadi, reminder, dan denda Anda';
$user         = $_SESSION['user'];
require 'partials/sidebar.php';
?>

        <div class="p-4 rounded-4 shadow-sm border-0 mb-4 d-flex align-items-center justify-content-between flex-wrap gap-3" style="background:linear-gradient(120deg, var(--pb-navy-2), var(--pb-indigo));">
            <div>
                <h3 class="m-0 text-white">Dashboard Pengunjung</h3>
                <p class="mt-1 mb-0" style="color:rgba(255,255,255,.8);">Kelola daftar bacaan pribadi, reminder jatuh tempo, dan catatan denda Anda.</p>
            </div>
            <i class="bi bi-person-heart" style="font-size:3.2rem;color:rgba(255,255,255,.35);"></i>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?= htmlspecialchars($alertType) ?> alert-dismissible fade show shadow-sm" role="alert">
                <?= htmlspecialchars($message) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="row g-4">
            <div class="col-lg-6">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white fw-bold">
                        <i class="bi bi-journal-bookmark me-1"></i> Daftar Buku Pribadi
                    </div>
                    <div class="card-body">
                        <form method="POST" class="mb-4">
                            <div class="mb-3">
                                <label class="form-label">Pilih Buku</label>
                                <select name="id_buku" class="form-select" required>
                                    <option value="">-- Pilih buku --</option>
                                    <?php while ($book = $books->fetch_assoc()): ?>
                                        <option value="<?= htmlspecialchars($book['id_buku']) ?>"><?= htmlspecialchars($book['judul']) ?> oleh <?= htmlspecialchars($book['pengarang']) ?><?= $book['stok'] < 1 ? ' (Habis)' : '' ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-select">
                                    <option value="Membaca">Membaca</option>
                                    <option value="Selesai">Selesai</option>
                                    <option value="Ingin Dibaca">Ingin Dibaca</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Catatan</label>
                                <textarea name="catatan" class="form-control" rows="2" placeholder="Catatan singkat untuk buku ini..."></textarea>
                            </div>
                            <button type="submit" name="add_personal_book" class="btn btn-primary w-100">Tambah ke Daftar Pribadi</button>
                        </form>

                        <div class="table-responsive">
                            <table class="table table-sm table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Buku</th>
                                        <th>Status</th>
                                        <th class="text-end">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($personalBooksResult && $personalBooksResult->num_rows > 0): ?>
                                        <?php while ($item = $personalBooksResult->fetch_assoc()): ?>
                                            <tr>
                                                <td class="small">
                                                    <strong><?= htmlspecialchars($item['judul']) ?></strong><br>
                                                    <span class="text-muted"><?= htmlspecialchars($item['pengarang']) ?></span>
                                                </td>
                                                <td><span class="badge bg-info text-dark"><?= htmlspecialchars($item['status']) ?></span></td>
                                                <td class="text-end">
                                                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#editBookModal<?= $item['id_entry'] ?>">Edit</button>
                                                    <form method="POST" action="" class="d-inline">
                                                        <input type="hidden" name="id_entry" value="<?= htmlspecialchars($item['id_entry']) ?>">
                                                        <button type="submit" name="delete_personal_book" class="btn btn-sm btn-outline-danger" onclick="return confirm('Hapus dari daftar pribadi?');">Hapus</button>
                                                    </form>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td colspan="3" class="small text-muted"><?= nl2br(htmlspecialchars($item['catatan'] ?: '-')) ?></td>
                                            </tr>

                                            <div class="modal fade" id="editBookModal<?= $item['id_entry'] ?>" tabindex="-1" aria-labelledby="editBookLabel<?= $item['id_entry'] ?>" aria-hidden="true">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <div class="modal-header bg-info text-white">
                                                            <h5 class="modal-title" id="editBookLabel<?= $item['id_entry'] ?>">Edit Buku Pribadi</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <form method="POST" action="">
                                                            <input type="hidden" name="id_entry" value="<?= htmlspecialchars($item['id_entry']) ?>">
                                                            <div class="modal-body">
                                                                <div class="mb-3">
                                                                    <label class="form-label">Status</label>
                                                                    <select name="status" class="form-select">
                                                                        <option value="Membaca" <?= $item['status'] === 'Membaca' ? 'selected' : '' ?>>Membaca</option>
                                                                        <option value="Selesai" <?= $item['status'] === 'Selesai' ? 'selected' : '' ?>>Selesai</option>
                                                                        <option value="Ingin Dibaca" <?= $item['status'] === 'Ingin Dibaca' ? 'selected' : '' ?>>Ingin Dibaca</option>
                                                                    </select>
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label class="form-label">Catatan</label>
                                                                    <textarea name="catatan" class="form-control" rows="3"><?= htmlspecialchars($item['catatan']) ?></textarea>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                                                <button type="submit" name="update_personal_book" class="btn btn-info">Simpan</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="3" class="text-center text-muted py-4">Belum ada buku pribadi.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="row g-4">
                    <div class="col-12">
                        <div class="card shadow-sm border-0">
                            <div class="card-header bg-white fw-bold">
                                <i class="bi bi-bell me-1"></i> Pemberitahuan Jatuh Tempo
                            </div>
                            <div class="card-body">
                                <?php if ($activeLoansResult && $activeLoansResult->num_rows > 0): ?>
                                    <?php while ($loan = $activeLoansResult->fetch_assoc()): ?>
                                        <?php
                                            $dueDate = strtotime($loan['tgl_jatuh_tempo']);
                                            $today = strtotime(date('Y-m-d'));
                                            $isOverdue = $today > $dueDate;
                                        ?>
                                        <div class="alert <?= $isOverdue ? 'alert-danger' : 'alert-warning' ?> py-3">
                                            <div class="fw-bold mb-1"><?= htmlspecialchars($loan['judul']) ?></div>
                                            <div class="small text-muted">Target kembali: <?= date('d M Y', $dueDate) ?>.</div>
                                            <?php if ($isOverdue): ?>
                                                <div class="small mt-1">Lewat tempo! Denda otomatis: <strong>Rp 150.000</strong>.</div>
                                            <?php else: ?>
                                                <div class="small mt-1">Masih dalam batas tempo.</div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <div class="text-muted">Tidak ada peminjaman aktif. Semua pemberitahuan jatuh tempo akan muncul di sini.</div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    

                    <div class="col-12">
                        <div class="card shadow-sm border-0">
                            <div class="card-header bg-white fw-bold">
                                <i class="bi bi-book me-1"></i> Katalog Buku
                            </div>
                            <div class="card-body">
                                <div class="row row-cols-1 row-cols-md-2 g-3">
                                    <?php if (!empty($bookCards)): ?>
                                        <?php foreach ($bookCards as $card): ?>
                                            <div class="col">
                                                <div class="card h-100 border-0 shadow-sm">
                                                    <div class="card-body">
                                                        <div class="d-flex justify-content-between align-items-start mb-3">
                                                            <div>
                                                                <h6 class="card-title mb-1"><?= htmlspecialchars($card['judul']) ?></h6>
                                                                <p class="card-text small text-muted mb-0"><?= htmlspecialchars($card['pengarang']) ?></p>
                                                            </div>
                                                            <span class="badge <?= $card['stok'] > 0 ? 'bg-success' : 'bg-danger' ?>"><?= $card['stok'] > 0 ? 'Tersedia' : 'Habis' ?></span>
                                                        </div>
                                                        <p class="small text-muted mb-0">ID Buku: <?= htmlspecialchars($card['id_buku']) ?></p>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <div class="col-12 text-center text-muted">Belum ada buku di katalog.</div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

<?php require 'partials/footer.php'; ?>
