<?php // actions/pembeli.php

$action = $_POST['action'] ?? '';

/* ══ TAMBAH MURID ══ */
if ($action === 'pembeli_tambah_murid') {
    $nama = trim($_POST['nama'] ?? '');
    $nisn = trim($_POST['nisn'] ?? '');
    $password = trim($_POST['password'] ?? '');

    // 🔥 Tangkap kiriman 2 dropdown baru dari form
    $tingkat = trim($_POST['tingkat_pembeli'] ?? ''); // cth: "10"
    $rombel = trim($_POST['rombel_pembeli'] ?? '');  // cth: "Akuntansi 1"

    // Validasi awal: pastikan tingkat dan rombel dipilih
    if ($nama === '' || $nisn === '' || $tingkat === '' || $rombel === '') {
        $feedback = ['type' => 'error', 'msg' => 'Semua field wajib diisi termasuk kelas dan jurusan.'];
    } elseif (!ctype_digit($nisn) || strlen($nisn) !== 10) {
        $feedback = ['type' => 'error', 'msg' => 'NISN harus tepat 10 digit angka.'];
    } else {

        // 🔥 LOGIKA BARU: Cari id_kelas & id_jurusan berdasarkan gabungan tingkat + rombel
        $t = mysqli_real_escape_string($conn, $tingkat);
        $r = mysqli_real_escape_string($conn, $rombel);

        $kelasQuery = mysqli_query($conn, "
            SELECT k.id_kelas, k.id_jurusan 
            FROM kelas k
            JOIN jurusan j ON j.id_jurusan = k.id_jurusan
            WHERE k.kelas = '$t' 
            AND CONCAT(j.nama_jurusan, ' ', k.rombel) = '$r'
            LIMIT 1
        ");

        $kelasData = mysqli_fetch_assoc($kelasQuery);
        $id_kelas = (int) ($kelasData['id_kelas'] ?? 0);
        $id_jurusan = (int) ($kelasData['id_jurusan'] ?? 0);

        // Jika kombinasi kelas dan jurusan fiktif/tidak ditemukan
        if (!$id_kelas) {
            $feedback = ['type' => 'error', 'msg' => 'Data kombinasi Kelas & Jurusan tidak valid di database.'];
        } else {

            $finalPass = $password !== '' ? $password : $nisn;
            $h = md5($finalPass);
            $n = mysqli_real_escape_string($conn, $nama);
            $ni = mysqli_real_escape_string($conn, $nisn);

            $cek = mysqli_fetch_assoc(mysqli_query($conn, "SELECT nisn FROM murid WHERE nisn='$ni'"));
            if ($cek) {
                $feedback = ['type' => 'error', 'msg' => "NISN <strong>$ni</strong> sudah terdaftar."];
            } else {
                if (
                    mysqli_query($conn, "INSERT INTO murid (nama, nisn, password, id_kelas, id_jurusan, status)
                                         VALUES ('$n','$ni','$h',$id_kelas,$id_jurusan,'aktif')")
                ) {
                    // 🔥 OPSI 1: Set Session agar form tambah murid otomatis stand-by kebuka lagi pasca-refresh
                    $_SESSION['keep_form_open'] = 'murid';

                    $defaultInfo = $password === '' ? " (password default: NISN)" : '';
                    $feedback = ['type' => 'success', 'msg' => "Murid <strong>" . htmlspecialchars($nama) . "</strong> berhasil ditambahkan.$defaultInfo"];
                } else {
                    $feedback = ['type' => 'error', 'msg' => 'Gagal: ' . mysqli_error($conn)];
                }
            }
        }
    }
}

/* ══ TAMBAH GURU ══ */
if ($action === 'pembeli_tambah_guru') {
    $nama = trim($_POST['nama'] ?? '');
    $nuptk = trim($_POST['nuptk'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($nama === '' || $nuptk === '') {
        $feedback = ['type' => 'error', 'msg' => 'Nama dan NUPTK wajib diisi.'];
    } elseif (!ctype_digit($nuptk)) {
        $feedback = ['type' => 'error', 'msg' => 'NUPTK hanya boleh berisi angka.'];
    } elseif (strlen($nuptk) !== 16) {
        $feedback = ['type' => 'error', 'msg' => 'NUPTK harus tepat 16 digit.'];
    } else {
        $finalPass = $password !== '' ? $password : $nuptk;
        $h = md5($finalPass);
        $n = mysqli_real_escape_string($conn, $nama);
        $nu = mysqli_real_escape_string($conn, $nuptk);

        $cek = mysqli_fetch_assoc(mysqli_query($conn, "SELECT nuptk FROM guru WHERE nuptk='$nu'"));
        if ($cek) {
            $feedback = ['type' => 'error', 'msg' => "NUPTK <strong>$nu</strong> sudah terdaftar."];
        } else {
            if (mysqli_query($conn, "INSERT INTO guru (nama, nuptk, password, status) VALUES ('$n','$nu','$h','aktif')")) {

                // 🔥 OPSI 1: Set Session agar form tambah guru otomatis stand-by kebuka lagi pasca-refresh
                $_SESSION['keep_form_open'] = 'guru';

                $defaultInfo = $password === '' ? " (password default: NUPTK)" : '';
                $feedback = ['type' => 'success', 'msg' => "Guru <strong>" . htmlspecialchars($nama) . "</strong> berhasil ditambahkan.$defaultInfo"];
                catatLog($conn, 'Tambah Guru', 'Menambahkan data guru baru bernama: ' . $nama);
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
        catatLog($conn, 'Toggle Status Murid', 'Mengubah status ID Murid ' . $nisn . ' menjadi ' . $new);
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
        catatLog($conn, 'Toggle Status Guru', 'Mengubah status ID Guru ' . $nuptk . ' menjadi ' . $new);
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
        catatLog($conn, 'Reset Password Murid', 'Mereset paksa password untuk murid: ' . $nisn);
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
        catatLog($conn, 'Reset Password Guru', 'Mereset paksa password untuk guru: ' . $nuptk);
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
        mysqli_query($conn, "UPDATE murid SET deleted_at = NOW() WHERE nisn='$nisn'");
        catatLog($conn, 'Hapus Murid', 'Menghapus murid NISN: ' . $nisn . ' (' . $nama_target . ')');
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
        mysqli_query($conn, "UPDATE guru SET deleted_at = NOW() WHERE nuptk='$nuptk'");
        catatLog($conn, 'Hapus Guru', 'Menghapus guru NUPTK: ' . $nuptk . ' (' . $nama_target . ')');
        $feedback = ['type' => 'success', 'msg' => "Guru <strong>" . htmlspecialchars($nama_target) . "</strong> berhasil dihapus."];
    }
    if ($feedback)
        $_SESSION['feedback'] = $feedback;
    header('Location: ?section=pembeli');
    exit;
}

/* ══ TAMBAH JURUSAN ══ */
if ($action === 'pembeli_tambah_jurusan') {
    $nama_jurusan = strtoupper(trim($_POST['nama_jurusan'] ?? ''));
    if ($nama_jurusan === '') {
        $feedback = ['type' => 'error', 'msg' => 'Nama jurusan tidak boleh kosong.'];
    } else {
        $nj = mysqli_real_escape_string($conn, $nama_jurusan);
        $cek = mysqli_fetch_assoc(mysqli_query($conn, "SELECT id_jurusan FROM jurusan WHERE nama_jurusan='$nj'"));
        if ($cek) {
            $feedback = ['type' => 'error', 'msg' => "Jurusan <strong>$nama_jurusan</strong> sudah terdaftar."];
        } else {
            if (mysqli_query($conn, "INSERT INTO jurusan (nama_jurusan) VALUES ('$nj')")) {
                catatLog($conn, 'Tambah Jurusan', "Menambahkan jurusan baru: $nama_jurusan");
                $feedback = ['type' => 'success', 'msg' => "Jurusan <strong>$nama_jurusan</strong> berhasil ditambahkan."];
            } else {
                $feedback = ['type' => 'error', 'msg' => 'Gagal menambahkan jurusan: ' . mysqli_error($conn)];
            }
        }
    }
    if ($feedback)
        $_SESSION['feedback'] = $feedback;
    header('Location: ?section=pembeli');
    exit;
}

/* ══ TAMBAH KELAS ══ */
if ($action === 'pembeli_tambah_kelas') {
    $kelas = trim($_POST['kelas'] ?? '');
    $id_jurusan = (int) ($_POST['id_jurusan'] ?? 0);
    $rombel = (int) ($_POST['rombel'] ?? 1);

    if ($kelas === '' || !$id_jurusan || $rombel < 1) {
        $feedback = ['type' => 'error', 'msg' => 'Semua field (tingkat, jurusan, rombel) wajib diisi.'];
    } else {
        $kls = mysqli_real_escape_string($conn, $kelas);
        $cek = mysqli_fetch_assoc(mysqli_query($conn, "SELECT id_kelas FROM kelas WHERE kelas='$kls' AND id_jurusan=$id_jurusan AND rombel=$rombel"));
        if ($cek) {
            $feedback = ['type' => 'error', 'msg' => 'Kombinasi kelas tersebut sudah terdaftar.'];
        } else {
            if (mysqli_query($conn, "INSERT INTO kelas (kelas, id_jurusan, rombel) VALUES ('$kls', $id_jurusan, $rombel)")) {
                $jur = mysqli_fetch_assoc(mysqli_query($conn, "SELECT nama_jurusan FROM jurusan WHERE id_jurusan=$id_jurusan"));
                $nama_jur = $jur['nama_jurusan'] ?? '';
                $nama_kelas_lengkap = "$kelas $nama_jur $rombel";
                catatLog($conn, 'Tambah Kelas', "Menambahkan kelas baru: $nama_kelas_lengkap");
                $feedback = ['type' => 'success', 'msg' => "Kelas <strong>$nama_kelas_lengkap</strong> berhasil ditambahkan."];
            } else {
                $feedback = ['type' => 'error', 'msg' => 'Gagal menambahkan kelas: ' . mysqli_error($conn)];
            }
        }
    }
    if ($feedback)
        $_SESSION['feedback'] = $feedback;
    header('Location: ?section=pembeli');
    exit;
}

/* ══ HAPUS KELAS ══ */
if ($action === 'pembeli_hapus_kelas') {
    $id_kelas = (int) ($_POST['id_kelas'] ?? 0);
    if ($id_kelas) {
        $cek_murid = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM murid WHERE id_kelas=$id_kelas"));
        if ($cek_murid['c'] > 0) {
            $feedback = ['type' => 'error', 'msg' => 'Gagal menghapus kelas: Kelas ini masih dirujuk oleh ' . $cek_murid['c'] . ' data murid di database.'];
        } else {
            $k_info = mysqli_fetch_assoc(mysqli_query($conn, "SELECT k.kelas, k.rombel, j.nama_jurusan FROM kelas k JOIN jurusan j ON j.id_jurusan = k.id_jurusan WHERE k.id_kelas=$id_kelas"));
            $nama_kelas = $k_info ? ($k_info['kelas'] . ' ' . $k_info['nama_jurusan'] . ' ' . $k_info['rombel']) : "ID $id_kelas";
            
            if (mysqli_query($conn, "DELETE FROM kelas WHERE id_kelas=$id_kelas")) {
                catatLog($conn, 'Hapus Kelas', "Menghapus kelas: $nama_kelas");
                $feedback = ['type' => 'success', 'msg' => "Kelas <strong>$nama_kelas</strong> berhasil dihapus."];
            } else {
                $feedback = ['type' => 'error', 'msg' => 'Gagal menghapus kelas: ' . mysqli_error($conn)];
            }
        }
    }
    if ($feedback)
        $_SESSION['feedback'] = $feedback;
    header('Location: ?section=pembeli');
    exit;
}

/* ══ HAPUS JURUSAN ══ */
if ($action === 'pembeli_hapus_jurusan') {
    $id_jurusan = (int) ($_POST['id_jurusan'] ?? 0);
    if ($id_jurusan) {
        $cek_kelas = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM kelas WHERE id_jurusan=$id_jurusan"));
        $cek_murid = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM murid WHERE id_jurusan=$id_jurusan"));
        
        if ($cek_kelas['c'] > 0 || $cek_murid['c'] > 0) {
            $feedback = ['type' => 'error', 'msg' => 'Gagal menghapus jurusan: Masih digunakan oleh ' . $cek_kelas['c'] . ' kelas atau ' . $cek_murid['c'] . ' data murid.'];
        } else {
            $j_info = mysqli_fetch_assoc(mysqli_query($conn, "SELECT nama_jurusan FROM jurusan WHERE id_jurusan=$id_jurusan"));
            $nama_jur = $j_info['nama_jurusan'] ?? "ID $id_jurusan";
            
            if (mysqli_query($conn, "DELETE FROM jurusan WHERE id_jurusan=$id_jurusan")) {
                catatLog($conn, 'Hapus Jurusan', "Menghapus jurusan: $nama_jur");
                $feedback = ['type' => 'success', 'msg' => "Jurusan <strong>$nama_jur</strong> berhasil dihapus."];
            } else {
                $feedback = ['type' => 'error', 'msg' => 'Gagal menghapus jurusan: ' . mysqli_error($conn)];
            }
        }
    }
    if ($feedback)
        $_SESSION['feedback'] = $feedback;
    header('Location: ?section=pembeli');
    exit;
}