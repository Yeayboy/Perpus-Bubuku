<?php
require_once 'auth.php';
require_once 'db.php';

if (!empty($_SESSION['user'])) {
    header('Location: index.php');
    exit;
}

$error = '';
$loginMode = $_REQUEST['mode'] ?? 'admin';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $loginMode = $_POST['login_mode'] ?? $loginMode;

    if ($loginMode !== 'tamu' && empty($_POST['login_mode']) && !empty($_POST['nim'])) {
        $loginMode = 'tamu';
    }

    if ($loginMode === 'tamu') {
        $nim = trim($_POST['nim'] ?? '');

        if (!empty($nim)) {
            $stmt = $conn->prepare("SELECT id_anggota, nim, nama FROM anggota WHERE nim = ? AND status = 'Aktif'");
            $stmt->bind_param("s", $nim);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($guest = $result->fetch_assoc()) {
                $_SESSION['user'] = [
                    'id_user'      => $guest['id_anggota'],
                    'username'     => $guest['nim'],
                    'nama_lengkap' => $guest['nama'],
                    'role'         => 'tamu'
                ];

                header('Location: index.php');
                exit;
            } else {
                $error = 'NIM tidak ditemukan atau akun tamu belum aktif.';
            }
            $stmt->close();
        } else {
            $error = 'Harap masukkan NIM Anda!';
        }
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = trim($_POST['password'] ?? '');

        if (!empty($username) && !empty($password)) {
            $stmt = $conn->prepare("SELECT id_user, username, password, nama_lengkap, role FROM users WHERE username = ?");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();

            $passwordValid = $user && password_verify($password, $user['password']);

            if (!$passwordValid && $user && hash_equals($user['password'], md5($password))) {
                $passwordValid = true;
                $newHash = password_hash($password, PASSWORD_DEFAULT);
                $updateStmt = $conn->prepare("UPDATE users SET password = ? WHERE id_user = ?");
                $updateStmt->bind_param("si", $newHash, $user['id_user']);
                $updateStmt->execute();
                $updateStmt->close();
            }

            if ($passwordValid) {
                $_SESSION['user'] = [
                    'id_user'      => $user['id_user'],
                    'username'     => $user['username'],
                    'nama_lengkap' => $user['nama_lengkap'],
                    'role'         => $user['role']
                ];

                header('Location: index.php');
                exit;
            } else {
                $error = 'Username atau password salah!';
            }
            $stmt->close();
        } else {
            $error = 'Harap isi semua kolom!';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Login - Perpus Bubuku</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
        <link rel="stylesheet" href="assets/css/theme.css">
    </head>
    <body>
<div class="pb-login-wrap">
    <div class="pb-login-visual">
        <img src="assets/img/logo.png" alt="Logo Perpus Bubuku" style="width:156px;height:auto;border-radius:14px;">
        <h1>Perpus Bubuku</h1>
        <p>Sistem informasi perpustakaan kampus — kelola koleksi buku, keanggotaan, sirkulasi, dan kualitas data dalam satu portal.</p>
    </div>
    <div class="pb-login-form">
    <div class="card shadow-sm p-4 pb-login-card border-0">
    <div class="text-center mb-1 d-md-none">
        <img src="assets/img/logo.png" alt="Logo Perpus Bubuku" style="width:56px;height:56px;border-radius:14px;">
    </div>
    <h4 class="text-center mb-1" style="color:var(--pb-navy);">Masuk ke Akun Anda</h4>
    <p class="text-center text-muted small mb-4">Sertifikasi Kompetensi DBA</p>

    <?php if ($error): ?>
        <div class="alert alert-danger py-2 small"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="">
        <input type="hidden" name="login_mode" id="login_mode" value="<?= htmlspecialchars($loginMode) ?>">

        <div class="d-flex gap-2 mb-4">
            <button type="button" id="btn-admin" class="btn btn-sm <?= $loginMode === 'tamu' ? 'btn-outline-primary' : 'btn-primary' ?> flex-fill">Admin / Petugas</button>
            <button type="button" id="btn-guest" class="btn btn-sm <?= $loginMode === 'tamu' ? 'btn-secondary' : 'btn-outline-secondary' ?> flex-fill">Pengunjung</button>
        </div>

        <div id="admin-fields" class="<?= $loginMode === 'tamu' ? 'd-none' : '' ?>">
            <div class="mb-3">
                <label class="form-label">Username</label>
                <input type="text" name="username" class="form-control" placeholder="Masukkan username" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" autocomplete="off" <?= $loginMode === 'tamu' ? 'disabled' : 'required' ?>>
            </div>
            <div class="mb-3">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" placeholder="Masukkan password" <?= $loginMode === 'tamu' ? 'disabled' : 'required' ?>>
            </div>
        </div>

        <div id="guest-fields" class="<?= $loginMode === 'tamu' ? '' : 'd-none' ?>">
            <div class="mb-3">
                <label class="form-label">NIM</label>
                <input type="text" name="nim" class="form-control" placeholder="Masukkan NIM Anda" value="<?= htmlspecialchars($_POST['nim'] ?? '') ?>" <?= $loginMode === 'tamu' ? 'required' : 'disabled' ?> autocomplete="off">
            </div>
            <div class="form-text text-muted">Masuk sebagai pengunjung perpustakaan menggunakan NIM. Tanpa password, cukup NIM aktif di data anggota.</div>
        </div>

        <button type="submit" class="btn btn-primary w-100">Masuk</button>
    </form>
    </div>
    </div>
</div>

<script>
    const btnAdmin = document.getElementById('btn-admin');
    const btnGuest = document.getElementById('btn-guest');
    const adminFields = document.getElementById('admin-fields');
    const guestFields = document.getElementById('guest-fields');
    const loginMode = document.getElementById('login_mode');

    const adminUsername = document.querySelector('input[name="username"]');
    const adminPassword = document.querySelector('input[name="password"]');
    const guestNim = document.querySelector('input[name="nim"]');

    const updateFields = () => {
        const mode = loginMode.value;
        const isGuest = mode === 'tamu';

        adminFields.classList.toggle('d-none', isGuest);
        guestFields.classList.toggle('d-none', !isGuest);

        adminUsername.required = !isGuest;
        adminPassword.required = !isGuest;
        guestNim.required = isGuest;

        adminUsername.disabled = isGuest;
        adminPassword.disabled = isGuest;
        guestNim.disabled = !isGuest;

        btnAdmin.classList.toggle('btn-primary', !isGuest);
        btnAdmin.classList.toggle('btn-outline-primary', isGuest);
        btnGuest.classList.toggle('btn-secondary', isGuest);
        btnGuest.classList.toggle('btn-outline-secondary', !isGuest);
    };

    btnAdmin.addEventListener('click', () => {
        loginMode.value = 'admin';
        updateFields();
    });

    btnGuest.addEventListener('click', () => {
        loginMode.value = 'tamu';
        updateFields();
    });

    updateFields();
</script>

</body>
</html>
