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

    // Cek slot kantin
    $slotKantin = 10;
    $qSlot = mysqli_query($conn, "SELECT nilai FROM pengaturan WHERE kunci = 'slot_kantin' LIMIT 1");
    if ($qSlot && mysqli_num_rows($qSlot) > 0) {
        $slotKantin = (int) mysqli_fetch_assoc($qSlot)['nilai'];
    }
    $totalTokoActive = (int) mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM toko WHERE deleted_at IS NULL"))['c'];

    if ($totalTokoActive >= $slotKantin) {
        $feedback = ['type' => 'error', 'msg' => 'Gagal menambahkan kantin: Slot stand kantin sudah penuh!'];
    } elseif ($nama !== '') {
        $n = mysqli_real_escape_string($conn, $nama);
        $d = mysqli_real_escape_string($conn, $desk);
        mysqli_query($conn, "INSERT INTO toko (nama_toko, deskripsi, foto_toko) VALUES ('$n','$d',NULL)");
        $idBaru = (int) mysqli_insert_id($conn);

        if ($idBaru > 0) {
            // Cek apakah ada file yang dikirim (bukan hanya no-file)
            $fileFoto = $_FILES['foto_toko'] ?? [];
            if (!empty($fileFoto) && isset($fileFoto['error']) && $fileFoto['error'] !== UPLOAD_ERR_NO_FILE) {
                $upload = tokoFotoProsesUpload($idBaru, $fileFoto);
                if ($upload['attempted']) {
                    if ($upload['error']) {
                        $feedback = ['type' => 'error', 'msg' => 'Kantin berhasil dibuat, tapi foto gagal diupload: ' . $upload['error']];
                    } elseif ($upload['filename']) {
                        $f = mysqli_real_escape_string($conn, $upload['filename']);
                        mysqli_query($conn, "UPDATE toko SET foto_toko='$f' WHERE id_toko=$idBaru");
                    }
                }
            }
        }

        if (!isset($feedback) || $feedback['type'] !== 'error') {
            catatLog($conn, 'Tambah Kantin', 'Menambahkan data kantin baru bernama: ' . $nama);
            $msgFoto = '';
            if (!empty($fileFoto) && isset($fileFoto['error']) && $fileFoto['error'] === UPLOAD_ERR_OK) {
                $msgFoto = ' (dengan foto)';
            }
            $feedback = ['type' => 'success', 'msg' => "Kantin <strong>" . htmlspecialchars($nama) . "</strong> berhasil ditambahkan{$msgFoto}."];
            $selectedToko = $idBaru;
        }
    } else {
        $feedback = ['type' => 'error', 'msg' => 'Nama kantin tidak boleh kosong.'];
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
        
        // Deactivate and soft delete all owners associated with this canteen
        $owner_res = mysqli_query($conn, "
            SELECT p.id_penjual FROM penjual p
            JOIN toko_penjual tp ON tp.id_penjual = p.id_penjual
            WHERE tp.id_toko = $id AND p.role = 'owner' AND p.deleted_at IS NULL
        ");
        if ($owner_res) {
            while ($owner_row = mysqli_fetch_assoc($owner_res)) {
                $owner_id = (int)$owner_row['id_penjual'];
                mysqli_query($conn, "UPDATE penjual SET deleted_at = NOW() WHERE id_penjual = $owner_id");
                mysqli_query($conn, "UPDATE toko_penjual SET status = 'nonaktif' WHERE id_penjual = $owner_id");
                catatLog($conn, 'Hapus Owner Otomatis', 'Owner ID: ' . $owner_id . ' dihapus otomatis karena kantin ID ' . $id . ' dihapus');
            }
        }

        // Deactivate all seller relationships associated with this canteen
        mysqli_query($conn, "UPDATE toko_penjual SET status = 'nonaktif' WHERE id_toko = $id");
        
        // Remove cart items for this canteen
        mysqli_query($conn, "DELETE FROM keranjang WHERE id_toko = $id");
        
        // Cancel all ongoing orders for this canteen
        mysqli_query($conn, "UPDATE pesanan SET status = 'dibatalkan' WHERE id_toko = $id AND status NOT IN ('selesai', 'dibatalkan')");
        
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
