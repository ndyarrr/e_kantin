<?php
// views/penjual/actions/proses_inbox.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Proteksi agar yang mengeksekusi file ini murni user ber-role penjual (Owner/Staf)
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'penjual') {
    exit('Unauthorized access!');
}

require_once __DIR__ . '/../../../config/database.php';

$penjualId = (int)($_SESSION['user_id'] ?? 0);
$idToko = (int)($_SESSION['id_toko'] ?? 0);
if ($idToko === 0) {
    $rToko = mysqli_fetch_assoc(mysqli_query(
        $conn,
        "SELECT id_toko FROM toko_penjual WHERE id_penjual=$penjualId AND status='aktif' ORDER BY id DESC LIMIT 1"
    ));
    $idToko = (int) ($rToko['id_toko'] ?? 0);
    $_SESSION['id_toko'] = $idToko;
}

$is_php_s = ($_SERVER['SERVER_PORT'] == '8000' || strpos($_SERVER['HTTP_HOST'], ':') !== false);
$base_url = $is_php_s ? '' : '/e_kantin';

$rolePath = (isset($_SESSION['user_sub_role']) && $_SESSION['user_sub_role'] === 'staf') 
    ? '/views/penjual/staf/index.php' 
    : '/views/penjual/owner/index.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_status') {
        $id_pesanan = (int)($_POST['id_pesanan'] ?? 0);
        $status_baru = mysqli_real_escape_string($conn, $_POST['status_baru'] ?? '');
        
        $statusValid = ['menunggu', 'dikonfirmasi', 'siap_diambil', 'selesai', 'dibatalkan'];
        if ($id_pesanan > 0 && in_array($status_baru, $statusValid)) {
            
            // Validasi kepemilikan pesanan: pastikan pesanan ini milik toko penjual ini
            $cekPesanan = mysqli_query($conn, "SELECT status FROM pesanan WHERE id_pesanan = $id_pesanan AND id_toko = $idToko LIMIT 1");
            if (mysqli_num_rows($cekPesanan) > 0) {
                $r_pesanan = mysqli_fetch_assoc($cekPesanan);
                $status_lama = $r_pesanan['status'];
                
                // Mulai transaksi untuk integritas data
                mysqli_begin_transaction($conn);
                try {
                    // Update status pesanan
                    mysqli_query($conn, "UPDATE pesanan SET status = '$status_baru' WHERE id_pesanan = $id_pesanan");
                    
                    // KUNCI PERBAIKAN: Jika status berubah menjadi 'selesai' dan sebelumnya bukan selesai
                    if ($status_baru === 'selesai' && $status_lama !== 'selesai') {
                        // 1. Ubah status pembayaran
                        mysqli_query($conn, "UPDATE pembayaran SET status = 'sudah_bayar' WHERE id_pesanan = $id_pesanan");
                        
                        // 2. Ambil barang-barang yang dibeli, lalu tambahkan ke kolom 'terjual' di tabel menu
                        $items = mysqli_query($conn, "SELECT id_menu, jumlah FROM detail_pesanan WHERE id_pesanan = $id_pesanan");
                        while ($item = mysqli_fetch_assoc($items)) {
                            $id_menu = (int)$item['id_menu'];
                            $jumlah = (int)$item['jumlah'];
                            mysqli_query($conn, "UPDATE menu SET terjual = terjual + $jumlah WHERE id_menu = $id_menu");
                        }
                    }
                    
                    // Jika status_baru dibatalkan dan status sebelumnya bukan dibatalkan, kembalikan stok menu
                    if ($status_baru === 'dibatalkan' && $status_lama !== 'dibatalkan') {
                        $items = mysqli_query($conn, "SELECT id_menu, jumlah FROM detail_pesanan WHERE id_pesanan = $id_pesanan");
                        while ($item = mysqli_fetch_assoc($items)) {
                            $id_menu = (int)$item['id_menu'];
                            $jumlah = (int)$item['jumlah'];
                            mysqli_query($conn, "UPDATE menu SET stok = stok + $jumlah WHERE id_menu = $id_menu");
                        }
                    }
                    
                    mysqli_commit($conn);
                    
                    $roleLabel = (isset($_SESSION['user_sub_role']) && $_SESSION['user_sub_role'] === 'staf') ? 'Staf' : 'Owner';
                    if (function_exists('catatLog')) {
                        catatLog($conn, 'Update Status Pesanan', "$roleLabel mengubah status pesanan #$id_pesanan menjadi $status_baru");
                    }
                    
                    $_SESSION['feedback'] = [
                        'type' => 'success',
                        'msg' => 'Status pesanan #' . $id_pesanan . ' berhasil diubah menjadi: ' . ucfirst($status_baru)
                    ];
                } catch (Exception $e) {
                    mysqli_rollback($conn);
                    $_SESSION['feedback'] = [
                        'type' => 'danger',
                        'msg' => 'Gagal mengubah status pesanan: ' . $e->getMessage()
                    ];
                }
            } else {
                $_SESSION['feedback'] = [
                    'type' => 'danger',
                    'msg' => 'Akses ditolak atau pesanan tidak ditemukan.'
                ];
            }
        } else {
            $_SESSION['feedback'] = [
                'type' => 'danger',
                'msg' => 'Status pesanan tidak valid.'
            ];
        }
    }
}

header('Location: ' . $base_url . $rolePath . '?section=inbox&t=' . time());
exit;