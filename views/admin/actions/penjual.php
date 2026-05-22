<?php
// actions/penjual.php
// $conn, $action sudah tersedia dari index.php

$selectedPenjual = (int) ($_POST['_selected_penjual'] ?? 0);
$sel = $selectedPenjual ?: (int) ($_POST['_selected_penjual'] ?? 0);

/* Tambah penjual */
if ($action === 'penjual_tambah') {
    $nama = trim($_POST['nama'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $role = in_array(trim($_POST['role'] ?? ''), ['staf', 'owner']) ? trim($_POST['role']) : 'staf'; // TANGKAP ROLE BARU
    $id_toko = (int) ($_POST['id_toko'] ?? 0);

    if ($nama === '' || $username === '' || $password === '') {
        $feedback = ['type' => 'error', 'msg' => 'Nama, username, dan password wajib diisi.'];
    } else {
        $cek = mysqli_fetch_assoc(mysqli_query($conn, "SELECT id_penjual FROM penjual WHERE username='" . mysqli_real_escape_string($conn, $username) . "'"));
        if ($cek) {
            $feedback = ['type' => 'error', 'msg' => 'Username sudah digunakan.'];
        } else {
            $n = mysqli_real_escape_string($conn, $nama);
            $u = mysqli_real_escape_string($conn, $username);
            $h = md5($password);

            // MASUKKAN KOLOM ROLE KE QUERY
            mysqli_query($conn, "INSERT INTO penjual (nama, username, password, role) VALUES ('$n','$u','$h','$role')");
            $id_baru = mysqli_insert_id($conn);

            if ($id_toko && $id_baru) {
                // cek dulu penjual ini belum di kantin lain (harusnya belum karena baru)
                mysqli_query($conn, "INSERT INTO toko_penjual (id_toko, id_penjual) VALUES ($id_toko, $id_baru)");
            }

            // Memperbaiki bug variable $nama_penjual -> $nama
            catatLog($conn, 'Tambah Penjual', "Menambahkan data pengelola ($role) baru bernama: " . $nama);

            $labelRole = $role === 'owner' ? 'Owner Kantin' : 'Penjual';
            $feedback = ['type' => 'success', 'msg' => "$labelRole <strong>" . htmlspecialchars($nama) . "</strong> berhasil ditambahkan."];
        }
    }
}

/* Edit penjual */
if ($action === 'penjual_edit') {
    $id = (int) ($_POST['id_penjual'] ?? 0);
    $nama = trim($_POST['nama'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $role = in_array(trim($_POST['role'] ?? ''), ['staf', 'owner']) ? trim($_POST['role']) : 'staf'; // TANGKAP ROLE BARU

    if ($id && $nama !== '') {
        $n = mysqli_real_escape_string($conn, $nama);
        $u = mysqli_real_escape_string($conn, $username);

        // Upload foto profil
        if (!empty($_FILES['foto_profil']['name'])) {
            $ext = pathinfo($_FILES['foto_profil']['name'], PATHINFO_EXTENSION);
            $allowed = ['jpg', 'jpeg', 'png', 'webp'];
            if (in_array(strtolower($ext), $allowed)) {
                $namaFile = 'penjual_' . $id . '.' . strtolower($ext);
                $tujuan = __DIR__ . '/../../../assets/img/penjual/' . $namaFile;
                foreach (['jpg', 'jpeg', 'png', 'webp'] as $e) {
                    $lama = __DIR__ . '/../../../assets/img/penjual/penjual_' . $id . '.' . $e;
                    if (file_exists($lama))
                        unlink($lama);
                }
                if (move_uploaded_file($_FILES['foto_profil']['tmp_name'], $tujuan)) {
                    mysqli_query($conn, "UPDATE penjual SET foto_profil='$namaFile' WHERE id_penjual=$id");
                }
            }
        }

        // Reset password kalau diisi
        $pw_baru = trim($_POST['password_baru'] ?? '');
        if ($pw_baru !== '') {
            $h = md5($pw_baru);
            // UPDATE DENGAN MEMASUKKAN ROLE BARU
            mysqli_query($conn, "UPDATE penjual SET nama='$n', username='$u', password='$h', role='$role' WHERE id_penjual=$id");
        } else {
            // UPDATE DENGAN MEMASUKKAN ROLE BARU
            mysqli_query($conn, "UPDATE penjual SET nama='$n', username='$u', role='$role' WHERE id_penjual=$id");
        }

        catatLog($conn, 'Update Penjual', "Memperbarui data pengelola kantin dengan ID: $id (Role: $role)");
        $feedback = ['type' => 'success', 'msg' => 'Data pengelola berhasil diperbarui.'];
        $selectedPenjual = $id;
    }
}

/* Toggle status penjual */
if ($action === 'penjual_toggle') {
    $id = (int) ($_POST['id_penjual'] ?? 0);
    $status = $_POST['status'] ?? '';
    if ($id && in_array($status, ['aktif', 'nonaktif'])) {
        $new = $status === 'aktif' ? 'nonaktif' : 'aktif';
        mysqli_query($conn, "UPDATE penjual SET status='$new' WHERE id_penjual=$id");
        catatLog($conn, 'Toggle Status Penjual', 'Mengubah status ID Penjual ' . $id . ' menjadi ' . $new);
        $feedback = ['type' => 'success', 'msg' => 'Status pengelola diperbarui.'];
    }
}

/* Hapus penjual */
if ($action === 'penjual_hapus') {
    $id = (int) ($_POST['id_penjual'] ?? 0);
    if ($id) {
        // Hapus foto
        foreach (['jpg', 'jpeg', 'png', 'webp'] as $e) {
            $f = __DIR__ . '/../../../assets/img/penjual/penjual_' . $id . '.' . $e;
            if (file_exists($f))
                unlink($f);
        }
        mysqli_query($conn, "DELETE FROM toko_penjual WHERE id_penjual=$id");
        mysqli_query($conn, "DELETE FROM penjual WHERE id_penjual=$id");
        catatLog($conn, 'Hapus Penjual', 'Menghapus data penjual dengan ID: ' . $id);
        $feedback = ['type' => 'success', 'msg' => 'Data pengelola berhasil dihapus.'];
        $selectedPenjual = 0;
    }
}

/* Assign penjual ke kantin — dengan validasi 1 penjual 1 kantin */
if ($action === 'kantin_assign_penjual') {
    $id_toko = (int) ($_POST['id_toko'] ?? 0);
    $id_penjual = (int) ($_POST['id_penjual'] ?? 0);
    $shift = $_POST['shift'] ?? '';

    if ($id_toko && $id_penjual) {
        $cek = mysqli_fetch_assoc(mysqli_query(
            $conn,
            "SELECT id FROM toko_penjual WHERE id_penjual=$id_penjual AND status='aktif' LIMIT 1"
        ));

        if ($cek) {
            $_SESSION['feedback'] = ['type' => 'error', 'msg' => 'Pengelola sudah assigned ke kantin lain. Lepas dulu sebelum assign ke kantin baru.'];
        } else {
            $s = $shift ? "'$shift'" : "NULL";
            mysqli_query($conn, "INSERT INTO toko_penjual (id_toko, id_penjual, shift) VALUES ($id_toko, $id_penjual, $s)");
            catatLog($conn, 'Assign Penjual', 'Mengassign pengelola dengan ID: ' . $id_penjual . ' ke kantin dengan ID: ' . $id_toko);
            $_SESSION['feedback'] = ['type' => 'success', 'msg' => 'Pengelola berhasil di-assign.'];
        }
        $selectedPenjual = $id_penjual;
    }
}

/* Lepas penjual dari kantin */
if ($action === 'kantin_lepas_penjual') {
    $id_tp = (int) ($_POST['id_tp'] ?? 0);
    if ($id_tp) {
        mysqli_query($conn, "UPDATE toko_penjual SET status='nonaktif' WHERE id=$id_tp");
        catatLog($conn, 'Lepas Penjual', 'Melepas penjual dengan ID toko_penjual: ' . $id_tp);
        $feedback = ['type' => 'success', 'msg' => 'Pengelola berhasil dilepas dari kantin.'];
    }
}

/* Redirect bersih setelah action */
if (
    str_starts_with($action, 'penjual_')
    || $action === 'kantin_assign_penjual'
    || $action === 'kantin_lepas_penjual'
) {
    if ($feedback)
        $_SESSION['feedback'] = $feedback;
    $sel = $selectedPenjual ?: (int) ($_POST['_selected_penjual'] ?? 0);
    header("Location: ?section=penjual" . ($sel ? "&penjual=$sel" : ""));
    exit;
}