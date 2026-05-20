<?php // actions/pembeli.php

$action = $_POST['action'] ?? '';

/* ══ TAMBAH MURID ══ */
if ($action === 'pembeli_tambah_murid') {
    $nama = trim($_POST['nama'] ?? '');
    $nisn = trim($_POST['nisn'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $id_kelas = (int) ($_POST['id_kelas'] ?? 0);
    $id_jurusan = (int) ($_POST['id_jurusan'] ?? 0);

    if ($nama === '' || $nisn === '' || $password === '' || !$id_kelas || !$id_jurusan) {
        $feedback = ['type' => 'error', 'msg' => 'Semua field wajib diisi termasuk kelas dan jurusan.'];
    } else {
        $n = mysqli_real_escape_string($conn, $nama);
        $ni = mysqli_real_escape_string($conn, $nisn);
        $h = md5($password);

        $cek = mysqli_fetch_assoc(mysqli_query($conn, "SELECT nisn FROM murid WHERE nisn='$ni'"));
        if ($cek) {
            $feedback = ['type' => 'error', 'msg' => "NISN <strong>$ni</strong> sudah terdaftar."];
        } else {
            if (mysqli_query($conn, "INSERT INTO murid (nama, nisn, password, id_kelas, id_jurusan, status) VALUES ('$n','$ni','$h',$id_kelas,$id_jurusan,'aktif')")) {
                $feedback = ['type' => 'success', 'msg' => "Murid <strong>" . htmlspecialchars($nama) . "</strong> berhasil ditambahkan."];
            } else {
                $feedback = ['type' => 'error', 'msg' => 'Gagal: ' . mysqli_error($conn)];
            }
        }
    }
}

/* ══ TAMBAH GURU ══ */
if ($action === 'pembeli_tambah_guru') {
    $nama = trim($_POST['nama'] ?? '');
    $nuptk = trim($_POST['nuptk'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($nama === '' || $nuptk === '' || $password === '') {
        $feedback = ['type' => 'error', 'msg' => 'Nama, NUPTK, dan password wajib diisi.'];
    } else {
        $n = mysqli_real_escape_string($conn, $nama);
        $nu = mysqli_real_escape_string($conn, $nuptk);
        $h = md5($password);

        // cek duplikat NUPTK
        $cek = mysqli_fetch_assoc(mysqli_query($conn, "SELECT nuptk FROM guru WHERE nuptk='$nu'"));
        if ($cek) {
            $feedback = ['type' => 'error', 'msg' => "NUPTK <strong>$nu</strong> sudah terdaftar."];
        } else {
            if (mysqli_query($conn, "INSERT INTO guru (nama, nuptk, password, status) VALUES ('$n','$nu','$h','aktif')")) {
                $feedback = ['type' => 'success', 'msg' => "Guru <strong>" . htmlspecialchars($nama) . "</strong> berhasil ditambahkan."];
            } else {
                $feedback = ['type' => 'error', 'msg' => 'Gagal menambahkan guru: ' . mysqli_error($conn)];
            }
        }
    }
}

/* ══ TOGGLE STATUS MURID ══ */
if ($action === 'pembeli_toggle_murid') {
    $nisn = mysqli_real_escape_string($conn, trim($_POST['nisn'] ?? ''));
    $status = $_POST['status'] ?? '';
    if ($nisn && in_array($status, ['aktif', 'nonaktif'])) {
        $new = $status === 'aktif' ? 'nonaktif' : 'aktif';
        mysqli_query($conn, "UPDATE murid SET status='$new' WHERE nisn='$nisn'");
    }
    if ($feedback)
        $_SESSION['feedback'] = $feedback;
    header('Location: ?section=pembeli');
    exit;
}

/* ══ TOGGLE STATUS GURU ══ */
if ($action === 'pembeli_toggle_guru') {
    $nuptk = mysqli_real_escape_string($conn, trim($_POST['nuptk'] ?? ''));
    $status = $_POST['status'] ?? '';
    if ($nuptk && in_array($status, ['aktif', 'nonaktif'])) {
        $new = $status === 'aktif' ? 'nonaktif' : 'aktif';
        mysqli_query($conn, "UPDATE guru SET status='$new' WHERE nuptk='$nuptk'");
    }
    if ($feedback)
        $_SESSION['feedback'] = $feedback;
    header('Location: ?section=pembeli');
    exit;
}

/* ══ RESET PASSWORD MURID ══ */
if ($action === 'pembeli_reset_murid') {
    $nisn = mysqli_real_escape_string($conn, trim($_POST['nisn'] ?? ''));
    $pw_baru = trim($_POST['pw_reset'] ?? '');
    if ($nisn && $pw_baru !== '') {
        $h = md5($pw_baru);
        mysqli_query($conn, "UPDATE murid SET password='$h' WHERE nisn='$nisn'");
        $nama_target = mysqli_fetch_assoc(mysqli_query($conn, "SELECT nama FROM murid WHERE nisn='$nisn'"))['nama'] ?? '';
        $feedback = ['type' => 'success', 'msg' => "Password <strong>" . htmlspecialchars($nama_target) . "</strong> berhasil direset."];
    } else {
        $feedback = ['type' => 'error', 'msg' => 'Password baru wajib diisi.'];
    }
}

/* ══ RESET PASSWORD GURU ══ */
if ($action === 'pembeli_reset_guru') {
    $nuptk = mysqli_real_escape_string($conn, trim($_POST['nuptk'] ?? ''));
    $pw_baru = trim($_POST['pw_reset'] ?? '');
    if ($nuptk && $pw_baru !== '') {
        $h = md5($pw_baru);
        mysqli_query($conn, "UPDATE guru SET password='$h' WHERE nuptk='$nuptk'");
        $nama_target = mysqli_fetch_assoc(mysqli_query($conn, "SELECT nama FROM guru WHERE nuptk='$nuptk'"))['nama'] ?? '';
        $feedback = ['type' => 'success', 'msg' => "Password <strong>" . htmlspecialchars($nama_target) . "</strong> berhasil direset."];
    } else {
        $feedback = ['type' => 'error', 'msg' => 'Password baru wajib diisi.'];
    }
}

/* ══ HAPUS MURID ══ */
if ($action === 'pembeli_hapus_murid') {
    $nisn = mysqli_real_escape_string($conn, trim($_POST['nisn'] ?? ''));
    if ($nisn) {
        $nama_target = mysqli_fetch_assoc(mysqli_query($conn, "SELECT nama FROM murid WHERE nisn='$nisn'"))['nama'] ?? '';
        mysqli_query($conn, "DELETE FROM murid WHERE nisn='$nisn'");
        $feedback = ['type' => 'success', 'msg' => "Murid <strong>" . htmlspecialchars($nama_target) . "</strong> berhasil dihapus."];
    }
    if ($feedback)
        $_SESSION['feedback'] = $feedback;
    header('Location: ?section=pembeli');
    exit;
}

/* ══ HAPUS GURU ══ */
if ($action === 'pembeli_hapus_guru') {
    $nuptk = mysqli_real_escape_string($conn, trim($_POST['nuptk'] ?? ''));
    if ($nuptk) {
        $nama_target = mysqli_fetch_assoc(mysqli_query($conn, "SELECT nama FROM guru WHERE nuptk='$nuptk'"))['nama'] ?? '';
        mysqli_query($conn, "DELETE FROM guru WHERE nuptk='$nuptk'");
        $feedback = ['type' => 'success', 'msg' => "Guru <strong>" . htmlspecialchars($nama_target) . "</strong> berhasil dihapus."];
    }
    if ($feedback)
        $_SESSION['feedback'] = $feedback;
    header('Location: ?section=pembeli');
    exit;
}