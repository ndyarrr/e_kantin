<?php
// actions/kantin.php
// Variabel $conn, $action sudah tersedia dari index.php

require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../config/toko_foto.php';

$selectedToko = (int) ($_POST['_selected_toko'] ?? 0);

/* Tambah toko */
if ($action === 'kantin_tambah') {
    $nama = trim($_POST['nama_toko'] ?? '');
    $desk = trim($_POST['deskripsi'] ?? '');

    if ($nama !== '') {
        $n = mysqli_real_escape_string($conn, $nama);
        $d = mysqli_real_escape_string($conn, $desk);
        mysqli_query($conn, "INSERT INTO toko (nama_toko, deskripsi, foto_toko) VALUES ('$n','$d',NULL)");
        $idBaru = (int) mysqli_insert_id($conn);

        if ($idBaru > 0) {
            $upload = tokoFotoProsesUpload($idBaru, $_FILES['foto_toko'] ?? []);
            if ($upload['attempted']) {
                if ($upload['error']) {
                    $feedback = ['type' => 'error', 'msg' => $upload['error']];
                } elseif ($upload['filename']) {
                    $f = mysqli_real_escape_string($conn, $upload['filename']);
                    mysqli_query($conn, "UPDATE toko SET foto_toko='$f' WHERE id_toko=$idBaru");
                }
            }
        }

        if (!isset($feedback) || $feedback['type'] !== 'error') {
            catatLog($conn, 'Tambah Kantin', 'Menambahkan data kantin baru bernama: ' . $nama);
            $feedback = ['type' => 'success', 'msg' => "Kantin <strong>" . htmlspecialchars($nama) . "</strong> berhasil ditambahkan."];
            $selectedToko = $idBaru;
        }
    }
}

/* Edit toko — alur foto sama owner (update_kantin_full) */
if ($action === 'kantin_edit') {
    $id = (int) ($_POST['id_toko'] ?? 0);
    $nama = trim($_POST['nama_toko'] ?? '');
    $desk = trim($_POST['deskripsi'] ?? '');

    if ($id && $nama !== '') {
        $rowLama = mysqli_fetch_assoc(mysqli_query($conn, "SELECT foto_toko FROM toko WHERE id_toko=$id"));
        $nama_foto_final = $rowLama['foto_toko'] ?? '';

        if (isset($_POST['hapus_foto'])) {
            tokoFotoHapusLama($id, $nama_foto_final);
            $nama_foto_final = '';
        }

        $upload = tokoFotoProsesUpload($id, $_FILES['foto_toko'] ?? []);
        if ($upload['attempted']) {
            if ($upload['error']) {
                $feedback = ['type' => 'error', 'msg' => $upload['error']];
            } elseif ($upload['filename']) {
                $nama_foto_final = $upload['filename'];
            }
        }

        if (!isset($feedback) || $feedback['type'] !== 'error') {
            $n = mysqli_real_escape_string($conn, $nama);
            $d = mysqli_real_escape_string($conn, $desk);

            if ($nama_foto_final === '') {
                $sqlFoto = 'foto_toko=NULL';
            } else {
                $f = mysqli_real_escape_string($conn, $nama_foto_final);
                $sqlFoto = "foto_toko='$f'";
            }

            mysqli_query($conn, "UPDATE toko SET nama_toko='$n', deskripsi='$d', $sqlFoto WHERE id_toko=$id");

            catatLog($conn, 'Edit Kantin', 'Memperbarui data kantin dengan ID: ' . $id);
            $feedback = ['type' => 'success', 'msg' => 'Kantin berhasil diperbarui.'];
            $selectedToko = $id;
        }
    }
}

if ($action === 'kantin_hapus') {
    $id = (int) ($_POST['id_toko'] ?? 0);
    if ($id) {
        $nama_target = mysqli_fetch_assoc(mysqli_query($conn, "SELECT nama_toko FROM toko WHERE id_toko=$id"))['nama_toko'] ?? '';
        mysqli_query($conn, "UPDATE toko SET deleted_at = NOW() WHERE id_toko=$id");
        mysqli_query($conn, "UPDATE menu SET deleted_at = NOW() WHERE id_toko=$id");
        catatLog($conn, 'Hapus Kantin', 'Menghapus kantin ID: ' . $id . ' (' . $nama_target . ')');
        $feedback = ['type' => 'success', 'msg' => "Kantin <strong>" . htmlspecialchars($nama_target) . "</strong> berhasil dihapus."];
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
        catatLog($conn, 'Assign Penjual', 'Mengassign penjual dengan ID: ' . $id_penjual . ' ke kantin dengan ID: ' . $id_toko);
        $feedback = ['type' => 'success', 'msg' => 'Penjual berhasil di-assign.'];
        $selectedToko = $id_toko;
    }
}

/* Lepas penjual */
if ($action === 'kantin_lepas_penjual') {
    $id_tp = (int) ($_POST['id_tp'] ?? 0);
    if ($id_tp) {
        mysqli_query($conn, "UPDATE toko_penjual SET status='nonaktif' WHERE id=$id_tp");
        catatLog($conn, 'Lepas Penjual', 'Melepas penjual dari kantin (id relasi: ' . $id_tp . ')');
        $feedback = ['type' => 'success', 'msg' => 'Penjual berhasil dilepas.'];
    }
}
