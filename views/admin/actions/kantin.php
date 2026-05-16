<?php
// actions/kantin.php
// Variabel $conn, $action sudah tersedia dari index.php

$selectedToko = (int) ($_POST['_selected_toko'] ?? 0);

/* Tambah toko */
if ($action === 'kantin_tambah') {
    $nama = trim($_POST['nama_toko'] ?? '');
    $desk = trim($_POST['deskripsi'] ?? '');
    $foto = null;

    if (!empty($_FILES['foto_toko']['name'])) {
        $ext = pathinfo($_FILES['foto_toko']['name'], PATHINFO_EXTENSION);
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        if (in_array(strtolower($ext), $allowed)) {
            $namaFile = 'toko_' . time() . '.' . strtolower($ext);
            $tujuan = __DIR__ . '/../../assets/img/kantin/' . $namaFile;
            if (move_uploaded_file($_FILES['foto_toko']['tmp_name'], $tujuan)) {
                $foto = $namaFile;
            }
        }
    }

    if ($nama !== '') {
        $n = mysqli_real_escape_string($conn, $nama);
        $d = mysqli_real_escape_string($conn, $desk);
        $f = $foto ? "'$foto'" : "NULL";
        mysqli_query($conn, "INSERT INTO toko (nama_toko, deskripsi, foto_toko) VALUES ('$n','$d',$f)");
        $feedback = ['type' => 'success', 'msg' => "Kantin <strong>" . htmlspecialchars($nama) . "</strong> berhasil ditambahkan."];
    }
}

/* Edit toko */
if ($action === 'kantin_edit') {
    $id = (int) ($_POST['id_toko'] ?? 0);
    $nama = trim($_POST['nama_toko'] ?? '');
    $desk = trim($_POST['deskripsi'] ?? '');
    $foto = null;

    if ($id && $nama !== '') {
        // hapus foto
        if (isset($_POST['hapus_foto'])) {
            $fotoLama = mysqli_fetch_assoc(mysqli_query($conn, "SELECT foto_toko FROM toko WHERE id_toko=$id"))['foto_toko'] ?? '';
            if ($fotoLama && file_exists(__DIR__ . '/../../assets/img/kantin/' . $fotoLama)) {
                unlink(__DIR__ . '/../../assets/img/kantin/' . $fotoLama);
            }
            mysqli_query($conn, "UPDATE toko SET foto_toko=NULL WHERE id_toko=$id");
        }

        // upload foto baru
        if (!empty($_FILES['foto_toko']['name'])) {
            $ext = pathinfo($_FILES['foto_toko']['name'], PATHINFO_EXTENSION);
            $allowed = ['jpg', 'jpeg', 'png', 'webp'];
            if (in_array(strtolower($ext), $allowed)) {
                $namaFile = 'toko_' . $id . '.' . strtolower($ext);
                $tujuan = __DIR__ . '/../../../assets/img/kantin/' . $namaFile;
                foreach (['jpg', 'jpeg', 'png', 'webp'] as $e) {
                    $lama = __DIR__ . '/../../../assets/img/kantin/toko_' . $id . '.' . $e;
                    if (file_exists($lama))
                        unlink($lama);
                }
                if (move_uploaded_file($_FILES['foto_toko']['tmp_name'], $tujuan)) {
                    $foto = $namaFile;
                }
            }
        }



        $n = mysqli_real_escape_string($conn, $nama);
        $d = mysqli_real_escape_string($conn, $desk);
        if ($foto) {
            $f = mysqli_real_escape_string($conn, $foto);
            mysqli_query($conn, "UPDATE toko SET nama_toko='$n', deskripsi='$d', foto_toko='$f' WHERE id_toko=$id");
        } else {
            mysqli_query($conn, "UPDATE toko SET nama_toko='$n', deskripsi='$d' WHERE id_toko=$id");
        }
        $feedback = ['type' => 'success', 'msg' => 'Kantin berhasil diperbarui.'];
        $selectedToko = $id;
    }
}

if ($action === 'kantin_hapus') {
    $id = (int) ($_POST['id_toko'] ?? 0);
    if ($id) {
        // hapus foto dulu
        $fotoLama = mysqli_fetch_assoc(mysqli_query($conn, "SELECT foto_toko FROM toko WHERE id_toko=$id"))['foto_toko'] ?? '';
        if ($fotoLama && file_exists(__DIR__ . '/../../../assets/img/kantin/' . $fotoLama)) {
            unlink(__DIR__ . '/../../../assets/img/kantin/' . $fotoLama);
        }

        // hapus detail_pesanan dulu (anak dari pesanan)
        mysqli_query($conn, "DELETE dp FROM detail_pesanan dp 
            JOIN pesanan p ON p.id_pesanan = dp.id_pesanan 
            WHERE p.id_toko = $id");

        // hapus pesanan
        mysqli_query($conn, "DELETE FROM pesanan WHERE id_toko=$id");

        // hapus menu
        mysqli_query($conn, "DELETE FROM menu WHERE id_toko=$id");

        // hapus toko_penjual
        mysqli_query($conn, "DELETE FROM toko_penjual WHERE id_toko=$id");

        // baru hapus toko
        mysqli_query($conn, "DELETE FROM toko WHERE id_toko=$id");

        $feedback = ['type' => 'success', 'msg' => 'Kantin berhasil dihapus.'];
        $selectedToko = 0;
    }
}

/* Assign penjual */
if ($action === 'kantin_assign_penjual') {
    $id_toko = (int) ($_POST['id_toko'] ?? 0);
    $id_penjual = (int) ($_POST['id_penjual'] ?? 0);
    $shift = $_POST['shift'] ?? '';
    if ($id_toko && $id_penjual) {
        $s = $shift ? "'$shift'" : "NULL";
        mysqli_query($conn, "INSERT INTO toko_penjual (id_toko, id_penjual, shift) VALUES ($id_toko, $id_penjual, $s)");
        $feedback = ['type' => 'success', 'msg' => 'Penjual berhasil di-assign.'];
        $selectedToko = $id_toko;
    }
}

/* Lepas penjual */
if ($action === 'kantin_lepas_penjual') {
    $id_tp = (int) ($_POST['id_tp'] ?? 0);
    if ($id_tp) {
        mysqli_query($conn, "UPDATE toko_penjual SET status='nonaktif' WHERE id=$id_tp");
        $feedback = ['type' => 'success', 'msg' => 'Penjual berhasil dilepas.'];
    }
}

/* Tambah menu */
if ($action === 'menu_tambah') {
    $id_toko = (int) ($_POST['id_toko'] ?? 0);
    $nama_menu = trim($_POST['nama_menu'] ?? '');
    $harga = (float) ($_POST['harga'] ?? 0);
    $stok = (int) ($_POST['stok'] ?? 0);
    $desk = trim($_POST['deskripsi'] ?? '');
    $foto = null;

    if ($id_toko && $nama_menu !== '') {
        if (!empty($_FILES['foto_menu']['name'])) {
            $ext = pathinfo($_FILES['foto_menu']['name'], PATHINFO_EXTENSION);
            $allowed = ['jpg', 'jpeg', 'png', 'webp'];
            if (in_array(strtolower($ext), $allowed)) {
                $namaFile = 'menu_' . time() . '.' . strtolower($ext);
                $tujuan = __DIR__ . '/../../assets/img/menu/' . $namaFile;
                if (move_uploaded_file($_FILES['foto_menu']['tmp_name'], $tujuan)) {
                    $foto = $namaFile;
                }
            }
        }
        $n = mysqli_real_escape_string($conn, $nama_menu);
        $d = mysqli_real_escape_string($conn, $desk);
        $f = $foto ? "'$foto'" : "NULL";
        mysqli_query($conn, "INSERT INTO menu (id_toko, nama_menu, deskripsi, harga, stok, foto_menu) VALUES ($id_toko,'$n','$d',$harga,$stok,$f)");
        $feedback = ['type' => 'success', 'msg' => "Menu <strong>" . htmlspecialchars($nama_menu) . "</strong> berhasil ditambahkan."];
        $selectedToko = $id_toko;
    }
}

/* Hapus menu */
if ($action === 'menu_hapus') {
    $id_menu = (int) ($_POST['id_menu'] ?? 0);
    if ($id_menu) {
        $fotoLama = mysqli_fetch_assoc(mysqli_query($conn, "SELECT foto_menu, id_toko FROM menu WHERE id_menu=$id_menu"));
        if ($fotoLama['foto_menu'] && file_exists(__DIR__ . '/../../assets/img/menu/' . $fotoLama['foto_menu'])) {
            unlink(__DIR__ . '/../../assets/img/menu/' . $fotoLama['foto_menu']);
        }
        $selectedToko = $fotoLama['id_toko'] ?? 0;
        mysqli_query($conn, "DELETE FROM menu WHERE id_menu=$id_menu");
        $feedback = ['type' => 'success', 'msg' => 'Menu berhasil dihapus.'];
    }
}