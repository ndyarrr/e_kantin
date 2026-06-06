<?php
if (session_status() === PHP_SESSION_NONE)
    session_start();
require_once __DIR__ . '/../../../config/database.php';
global $conn;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id_staf = mysqli_real_escape_string($conn, $_POST['id_staf'] ?? '');

    // 1. TOGGLE STATUS AKTIF / NONAKTIF
    if ($action === 'toggle_status_staf') {
        $res = mysqli_query($conn, "SELECT status FROM penjual WHERE id_penjual = '$id_staf'");
        if ($res && $row = mysqli_fetch_assoc($res)) {
            $status_baru = (strtolower($row['status']) === 'aktif') ? 'Nonaktif' : 'Aktif';
            mysqli_query($conn, "UPDATE penjual SET status = '$status_baru' WHERE id_penjual = '$id_staf'");
            $_SESSION['feedback'] = ['type' => 'success', 'msg' => 'Status operasional staf berhasil diperbarui!'];
        }
        echo "<script>window.location.href='../owner/index.php?section=staf';</script>";
        exit;
    }

    // 2. HAPUS DATA STAF
    if ($action === 'hapus_staf') {
        $res_foto = mysqli_query($conn, "SELECT foto_profil FROM penjual WHERE id_penjual = '$id_staf'");
        if ($res_foto && $row = mysqli_fetch_assoc($res_foto)) {
            if (!empty($row['foto_profil'])) {
                $path_foto = __DIR__ . '/../../../assets/img/penjual/' . $row['foto_profil'];
                if (file_exists($path_foto))
                    unlink($path_foto);
            }
        }
        // Hapus relasi toko dan data akun penjual-nya
        mysqli_query($conn, "DELETE FROM toko_penjual WHERE id_penjual = '$id_staf'");
        mysqli_query($conn, "DELETE FROM penjual WHERE id_penjual = '$id_staf'");

        $_SESSION['feedback'] = ['type' => 'success', 'msg' => 'Data staf berhasil dihapus secara permanen!'];
        echo "<script>window.location.href='../owner/index.php?section=staf';</script>";
        exit;
    }
    // 🌟 3. PROSES SIMPAN AKUN STAF KANTIN BARU (MENGATASI LAYAR PUTIH)
    if ($action === 'action_staf_tambah') {
        $nama = mysqli_real_escape_string($conn, $_POST['nama'] ?? '');
        $username = mysqli_real_escape_string($conn, $_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $shift = mysqli_real_escape_string($conn, $_POST['shift'] ?? 'Pagi');

        $id_owner = (int) ($_SESSION['user_id'] ?? 0);

        // Ambil id_toko milik owner agar staf terikat ke toko yang sama
        $id_toko = (int) ($_SESSION['id_toko'] ?? 0);
        if ($id_toko <= 0) {
            $q_toko = mysqli_query($conn, "SELECT tp.id_toko FROM toko_penjual tp JOIN toko t ON tp.id_toko = t.id_toko WHERE tp.id_penjual = $id_owner AND tp.status = 'aktif' AND t.deleted_at IS NULL ORDER BY tp.id DESC LIMIT 1");
            $r_toko = mysqli_fetch_assoc($q_toko);
            $id_toko = (int) ($r_toko['id_toko'] ?? 0);
        }

        // Validasi: Cegah pembuatan staf jika toko tidak ditemukan
        if ($id_toko <= 0) {
            $_SESSION['feedback'] = ['type' => 'error', 'msg' => 'Gagal: Kantin tidak ditemukan untuk owner ini. Silakan hubungi Admin.'];
            echo "<script>window.location.href='../owner/index.php?section=staf';</script>";
            exit;
        }

        // Cek username ganda
        $cek_user = mysqli_query($conn, "SELECT id_penjual FROM penjual WHERE username = '$username' AND deleted_at IS NULL");
        if (mysqli_num_rows($cek_user) > 0) {
            $_SESSION['feedback'] = ['type' => 'error', 'msg' => 'Username sudah terpakai!'];
            echo "<script>window.location.href='../owner/index.php?section=staf&action=tambah';</script>";
            exit;
        }

        // Gunakan enkripsi MD5 (sesuaikan dengan gambar database kamu yang menggunakan md5 hash)
        $password_md5 = md5($password);

        // Insert ke tabel penjual asli milikmu
        $insert_user = mysqli_query($conn, "
            INSERT INTO penjual (nama, username, password, role, status) 
            VALUES ('$nama', '$username', '$password_md5', 'staf', 'aktif')
        ");

        if ($insert_user) {
            $id_staf_baru = mysqli_insert_id($conn);

            // Masukkan ikatan toko ke tabel toko_penjual
            mysqli_query($conn, "
                INSERT INTO toko_penjual (id_toko, id_penjual, shift) 
                VALUES ($id_toko, $id_staf_baru, '$shift')
            ");

            $_SESSION['feedback'] = ['type' => 'success', 'msg' => 'Staf baru berhasil ditambahkan!'];
        } else {
            $_SESSION['feedback'] = ['type' => 'error', 'msg' => 'Gagal menyimpan data staf.'];
        }

        // Kembalikan ke halaman daftar staf agar tidak memicu layar putih murni
        echo "<script>window.location.href='../owner/index.php?section=staf';</script>";
        exit;
    }
    // 🌟 4. PROSES UPDATE DATA STAF (PILIHAN DARI KLIK BARIS TABEL)
    if ($action === 'action_staf_edit') {
        $id_staf = mysqli_real_escape_string($conn, $_POST['id_staf'] ?? '');
        $nama = mysqli_real_escape_string($conn, $_POST['nama'] ?? '');
        $username = mysqli_real_escape_string($conn, $_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $shift = mysqli_real_escape_string($conn, $_POST['shift'] ?? 'Pagi');

        // Cek apakah username ganda dipakai orang lain selain staf itu sendiri
        $cek_user = mysqli_query($conn, "SELECT id_penjual FROM penjual WHERE username = '$username' AND id_penjual != '$id_staf' AND deleted_at IS NULL");
        if (mysqli_num_rows($cek_user) > 0) {
            $_SESSION['feedback'] = ['type' => 'error', 'msg' => 'Username tersebut sudah dipakai orang lain!'];
            echo "<script>window.location.href='../owner/index.php?section=staf';</script>";
            exit;
        }

        // Jalankan update data dasar dulu
        $update = mysqli_query($conn, "UPDATE penjual SET nama = '$nama', username = '$username' WHERE id_penjual = '$id_staf'");

        if ($update) {
            // Auto-heal relasi toko_penjual jika belum ada
            $cek_relasi = mysqli_query($conn, "SELECT id FROM toko_penjual WHERE id_penjual = '$id_staf' LIMIT 1");
            if (mysqli_num_rows($cek_relasi) > 0) {
                // Update shift di tabel relasi toko_penjual
                mysqli_query($conn, "UPDATE toko_penjual SET shift = '$shift' WHERE id_penjual = '$id_staf'");
            } else {
                // Cari id_toko untuk owner dan buat relasi baru
                $id_owner = (int) ($_SESSION['user_id'] ?? 0);
                $id_toko = (int) ($_SESSION['id_toko'] ?? 0);
                if ($id_toko <= 0) {
                    $q_toko = mysqli_query($conn, "SELECT tp.id_toko FROM toko_penjual tp JOIN toko t ON tp.id_toko = t.id_toko WHERE tp.id_penjual = $id_owner AND tp.status = 'aktif' AND t.deleted_at IS NULL ORDER BY tp.id DESC LIMIT 1");
                    $r_toko = mysqli_fetch_assoc($q_toko);
                    $id_toko = (int) ($r_toko['id_toko'] ?? 0);
                }
                if ($id_toko > 0) {
                    mysqli_query($conn, "INSERT INTO toko_penjual (id_toko, id_penjual, shift) VALUES ($id_toko, '$id_staf', '$shift')");
                }
            }

            // Jika password diisi baru, enkripsi menggunakan MD5 lalu simpan
            if (!empty($password)) {
                $password_md5 = md5($password);
                mysqli_query($conn, "UPDATE penjual SET password = '$password_md5' WHERE id_penjual = '$id_staf'");
            }

            $_SESSION['feedback'] = ['type' => 'success', 'msg' => 'Data staf berhasil diperbarui!'];
        } else {
            $_SESSION['feedback'] = ['type' => 'error', 'msg' => 'Gagal memperbarui data staf.'];
        }

        echo "<script>window.location.href='../owner/index.php?section=staf';</script>";
        exit;
    }
}
?>