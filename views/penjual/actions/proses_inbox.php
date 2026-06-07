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
$base_url = '';
if (preg_match('#^(.*)/(views|auth|backend|controllers|config|assets|scratch)/#', $_SERVER['SCRIPT_NAME'] ?? '', $m)) {
    $base_url = $m[1];
} elseif (preg_match('#^(.*)/index\.php#', $_SERVER['SCRIPT_NAME'] ?? '', $m)) {
    $base_url = $m[1];
}

$rolePath = (isset($_SESSION['user_sub_role']) && $_SESSION['user_sub_role'] === 'staf') 
    ? '/views/penjual/staf/index.php' 
    : '/views/penjual/owner/index.php';

$isAjax = !empty($_POST['ajax'])
    || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');

$ajaxResponse = ['success' => false, 'message' => 'Aksi tidak dikenali.'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'konfirmasi_pembayaran_qris') {
        $id_pesanan = (int)($_POST['id_pesanan'] ?? 0);
        if ($id_pesanan > 0) {
            // Validasi kepemilikan pesanan: pastikan pesanan ini milik toko penjual ini
            $cekPesanan = mysqli_query($conn, "SELECT status FROM pesanan WHERE id_pesanan = $id_pesanan AND id_toko = $idToko LIMIT 1");
            if (mysqli_num_rows($cekPesanan) > 0) {
                mysqli_begin_transaction($conn);
                try {
                    // Update status pembayaran menjadi lunas
                    mysqli_query($conn, "UPDATE pembayaran SET status = 'lunas' WHERE id_pesanan = $id_pesanan");

                    // Kirim pesan otomatis ke chat pembeli mengenai pembayaran QRIS dikonfirmasi
                    $q_pesanan_info = mysqli_query($conn, "SELECT nisn_pembeli, nuptk_pembeli FROM pesanan WHERE id_pesanan = $id_pesanan LIMIT 1");
                    if ($q_pesanan_info && mysqli_num_rows($q_pesanan_info) > 0) {
                        $r_p = mysqli_fetch_assoc($q_pesanan_info);
                        $penerima_chat = '';
                        if (!empty($r_p['nisn_pembeli'])) {
                            $penerima_chat = 'murid_' . $r_p['nisn_pembeli'];
                        } elseif (!empty($r_p['nuptk_pembeli'])) {
                            $penerima_chat = 'guru_' . $r_p['nuptk_pembeli'];
                        }

                        if (!empty($penerima_chat)) {
                            $pengirim_chat = 'toko_' . $idToko;
                            $auto_status_msg = '[AUTO_REPLY_STATUS]
                            <div style="font-family:-apple-system,BlinkMacSystemFont,\'Segoe UI\',Roboto,sans-serif;max-width:320px;padding:4px;">
                                <div style="font-weight:800;font-size:14px;color:#16a34a;margin-bottom:6px;">Pembayaran QRIS Diterima!</div>
                                <div style="font-size:12px;color:#64748b;margin-bottom:12px;">Pembayaran QRIS Anda untuk Pesanan #' . $id_pesanan . ' telah diverifikasi dan diterima oleh penjual. Terima kasih! 🙏</div>
                                <div style="padding:10px 12px;background:#f0fdf4;border-radius:10px;border:1px solid #bbf7d0;display:flex;justify-content:space-between;align-items:center;">
                                    <span style="font-size:12px;font-weight:600;color:#16a34a;">Status Pembayaran</span>
                                    <span style="font-size:12px;font-weight:800;color:#15803d;">LUNAS ✅</span>
                                </div>
                            </div>';

                            $msg_escaped = mysqli_real_escape_string($conn, $auto_status_msg);
                            mysqli_query($conn, "INSERT INTO pesan_chat (id_pengirim, id_penerima, isi_pesan, waktu_kirim, sudah_dibaca)
                                                 VALUES ('$pengirim_chat', '$penerima_chat', '$msg_escaped', NOW(), 0)");
                        }
                    }
                    
                    mysqli_commit($conn);

                    $roleLabel = (isset($_SESSION['user_sub_role']) && $_SESSION['user_sub_role'] === 'staf') ? 'Staf' : 'Owner';
                    if (function_exists('catatLog')) {
                        catatLog($conn, 'Konfirmasi Pembayaran QRIS', "$roleLabel mengonfirmasi pembayaran QRIS untuk pesanan #$id_pesanan menjadi lunas");
                    }

                    $ajaxResponse = [
                        'success' => true,
                        'message' => 'Pembayaran QRIS untuk pesanan #' . $id_pesanan . ' berhasil dikonfirmasi!'
                    ];
                    if (!$isAjax) {
                        $_SESSION['feedback'] = [
                            'type' => 'success',
                            'msg' => $ajaxResponse['message']
                        ];
                    }
                } catch (Exception $e) {
                    mysqli_rollback($conn);
                    $ajaxResponse = [
                        'success' => false,
                        'message' => 'Gagal mengonfirmasi pembayaran QRIS: ' . $e->getMessage()
                    ];
                    if (!$isAjax) {
                        $_SESSION['feedback'] = ['type' => 'danger', 'msg' => $ajaxResponse['message']];
                    }
                }
            } else {
                $ajaxResponse = ['success' => false, 'message' => 'Akses ditolak atau pesanan tidak ditemukan.'];
                if (!$isAjax) {
                    $_SESSION['feedback'] = ['type' => 'danger', 'msg' => $ajaxResponse['message']];
                }
            }
        } else {
            $ajaxResponse = ['success' => false, 'message' => 'ID pesanan tidak valid.'];
            if (!$isAjax) {
                $_SESSION['feedback'] = ['type' => 'danger', 'msg' => $ajaxResponse['message']];
            }
        }
    }

    if ($action === 'konfirmasi_pembayaran_tunai') {
        $id_pesanan = (int)($_POST['id_pesanan'] ?? 0);
        if ($id_pesanan > 0) {
            // Validasi kepemilikan pesanan dan status menunggu
            $cekPesanan = mysqli_query($conn, "
                SELECT p.status, pb.metode, pb.status AS status_bayar
                FROM pesanan p
                LEFT JOIN pembayaran pb ON pb.id_pesanan = p.id_pesanan
                WHERE p.id_pesanan = $id_pesanan AND p.id_toko = $idToko
                LIMIT 1
            ");
            if ($cekPesanan && mysqli_num_rows($cekPesanan) > 0) {
                $rowPesanan = mysqli_fetch_assoc($cekPesanan);
                if ($rowPesanan['status'] !== 'menunggu') {
                    $ajaxResponse = [
                        'success' => false,
                        'message' => 'Konfirmasi tunai hanya dapat dilakukan saat pesanan masih menunggu.'
                    ];
                    if (!$isAjax) {
                        $_SESSION['feedback'] = ['type' => 'danger', 'msg' => $ajaxResponse['message']];
                    }
                } elseif (($rowPesanan['metode'] ?? '') !== 'tunai') {
                    $ajaxResponse = ['success' => false, 'message' => 'Pesanan ini bukan pembayaran tunai.'];
                    if (!$isAjax) {
                        $_SESSION['feedback'] = ['type' => 'danger', 'msg' => $ajaxResponse['message']];
                    }
                } elseif (($rowPesanan['status_bayar'] ?? '') === 'lunas') {
                    $ajaxResponse = ['success' => false, 'message' => 'Pembayaran tunai pesanan ini sudah dikonfirmasi.'];
                    if (!$isAjax) {
                        $_SESSION['feedback'] = ['type' => 'danger', 'msg' => $ajaxResponse['message']];
                    }
                } else {
                mysqli_begin_transaction($conn);
                try {
                    // Update status pembayaran menjadi lunas
                    mysqli_query($conn, "UPDATE pembayaran SET status = 'lunas' WHERE id_pesanan = $id_pesanan");

                    // Kirim pesan otomatis ke chat pembeli mengenai pembayaran tunai dikonfirmasi
                    $q_pesanan_info = mysqli_query($conn, "SELECT nisn_pembeli, nuptk_pembeli FROM pesanan WHERE id_pesanan = $id_pesanan LIMIT 1");
                    if ($q_pesanan_info && mysqli_num_rows($q_pesanan_info) > 0) {
                        $r_p = mysqli_fetch_assoc($q_pesanan_info);
                        $penerima_chat = '';
                        if (!empty($r_p['nisn_pembeli'])) {
                            $penerima_chat = 'murid_' . $r_p['nisn_pembeli'];
                        } elseif (!empty($r_p['nuptk_pembeli'])) {
                            $penerima_chat = 'guru_' . $r_p['nuptk_pembeli'];
                        }

                        if (!empty($penerima_chat)) {
                            $pengirim_chat = 'toko_' . $idToko;
                            $auto_status_msg = '[AUTO_REPLY_STATUS]
                            <div style="font-family:-apple-system,BlinkMacSystemFont,\'Segoe UI\',Roboto,sans-serif;max-width:320px;padding:4px;">
                                <div style="font-weight:800;font-size:14px;color:#16a34a;margin-bottom:6px;">Pembayaran Tunai Diterima!</div>
                                <div style="font-size:12px;color:#64748b;margin-bottom:12px;">Pembayaran tunai Anda untuk Pesanan #' . $id_pesanan . ' telah diterima oleh penjual. Terima kasih! 🙏</div>
                                <div style="padding:10px 12px;background:#f0fdf4;border-radius:10px;border:1px solid #bbf7d0;display:flex;justify-content:space-between;align-items:center;">
                                    <span style="font-size:12px;font-weight:600;color:#16a34a;">Status Pembayaran</span>
                                    <span style="font-size:12px;font-weight:800;color:#15803d;">LUNAS ✅</span>
                                </div>
                            </div>';

                            $msg_escaped = mysqli_real_escape_string($conn, $auto_status_msg);
                            mysqli_query($conn, "INSERT INTO pesan_chat (id_pengirim, id_penerima, isi_pesan, waktu_kirim, sudah_dibaca)
                                                 VALUES ('$pengirim_chat', '$penerima_chat', '$msg_escaped', NOW(), 0)");
                        }
                    }
                    
                    mysqli_commit($conn);

                    $roleLabel = (isset($_SESSION['user_sub_role']) && $_SESSION['user_sub_role'] === 'staf') ? 'Staf' : 'Owner';
                    if (function_exists('catatLog')) {
                        catatLog($conn, 'Konfirmasi Pembayaran Tunai', "$roleLabel mengonfirmasi pembayaran tunai untuk pesanan #$id_pesanan menjadi lunas");
                    }

                    $ajaxResponse = [
                        'success' => true,
                        'message' => 'Pembayaran tunai untuk pesanan #' . $id_pesanan . ' berhasil dikonfirmasi!'
                    ];
                    if (!$isAjax) {
                        $_SESSION['feedback'] = [
                            'type' => 'success',
                            'msg' => $ajaxResponse['message']
                        ];
                    }
                } catch (Exception $e) {
                    mysqli_rollback($conn);
                    $ajaxResponse = [
                        'success' => false,
                        'message' => 'Gagal mengonfirmasi pembayaran tunai: ' . $e->getMessage()
                    ];
                    if (!$isAjax) {
                        $_SESSION['feedback'] = ['type' => 'danger', 'msg' => $ajaxResponse['message']];
                    }
                }
                }
            } else {
                $ajaxResponse = ['success' => false, 'message' => 'Akses ditolak atau pesanan tidak ditemukan.'];
                if (!$isAjax) {
                    $_SESSION['feedback'] = ['type' => 'danger', 'msg' => $ajaxResponse['message']];
                }
            }
        } else {
            $ajaxResponse = ['success' => false, 'message' => 'ID pesanan tidak valid.'];
            if (!$isAjax) {
                $_SESSION['feedback'] = ['type' => 'danger', 'msg' => $ajaxResponse['message']];
            }
        }
    }

    if ($action === 'update_status') {
        $id_pesanan = (int)($_POST['id_pesanan'] ?? 0);
        $status_baru = mysqli_real_escape_string($conn, $_POST['status_baru'] ?? '');
        
        $statusValid = ['menunggu', 'dikonfirmasi', 'siap_diambil', 'selesai', 'dibatalkan'];
        if ($id_pesanan > 0 && in_array($status_baru, $statusValid)) {
            
            // Validasi kepemilikan pesanan: pastikan pesanan ini milik toko penjual ini
            $cekPesanan = mysqli_query($conn, "
                SELECT p.status, pb.metode, pb.status AS status_bayar
                FROM pesanan p
                LEFT JOIN pembayaran pb ON pb.id_pesanan = p.id_pesanan
                WHERE p.id_pesanan = $id_pesanan AND p.id_toko = $idToko
                LIMIT 1
            ");
            if (mysqli_num_rows($cekPesanan) > 0) {
                $r_pesanan = mysqli_fetch_assoc($cekPesanan);
                $status_lama = $r_pesanan['status'];

                if (
                    $status_baru === 'dikonfirmasi'
                    && $status_lama === 'menunggu'
                    && ($r_pesanan['metode'] ?? '') === 'tunai'
                    && ($r_pesanan['status_bayar'] ?? '') !== 'lunas'
                ) {
                    $ajaxResponse = [
                        'success' => false,
                        'message' => 'Konfirmasi pembayaran tunai terlebih dahulu sebelum memproses pesanan.'
                    ];
                    if (!$isAjax) {
                        $_SESSION['feedback'] = ['type' => 'danger', 'msg' => $ajaxResponse['message']];
                    }
                } else {
                
                // Mulai transaksi untuk integritas data
                mysqli_begin_transaction($conn);
                try {
                    // Update status pesanan
                    mysqli_query($conn, "UPDATE pesanan SET status = '$status_baru' WHERE id_pesanan = $id_pesanan");

                    // Kirim pesan otomatis ke chat pembeli mengenai perubahan status
                    $q_pesanan_info = mysqli_query($conn, "SELECT nisn_pembeli, nuptk_pembeli, total_harga FROM pesanan WHERE id_pesanan = $id_pesanan LIMIT 1");
                    if ($q_pesanan_info && mysqli_num_rows($q_pesanan_info) > 0) {
                        $r_p = mysqli_fetch_assoc($q_pesanan_info);
                        $penerima_chat = '';
                        if (!empty($r_p['nisn_pembeli'])) {
                            $penerima_chat = 'murid_' . $r_p['nisn_pembeli'];
                        } elseif (!empty($r_p['nuptk_pembeli'])) {
                            $penerima_chat = 'guru_' . $r_p['nuptk_pembeli'];
                        }

                        if (!empty($penerima_chat)) {
                            $pengirim_chat = 'toko_' . $idToko;
                            $status_teks_chat = '';
                            $status_sub = '';
                            $status_status = '';

                            if ($status_baru === 'dikonfirmasi') {
                                $status_teks_chat = 'Pesanan #' . $id_pesanan . ' Diterima!';
                                $status_sub = 'Pesananmu sudah diterima dan sedang disiapkan pihak kantin. Mohon ditunggu 🙏';
                                $status_status = 'Diproses ⏳';
                            } elseif ($status_baru === 'siap_diambil') {
                                $status_teks_chat = 'Pesanan #' . $id_pesanan . ' Siap Diambil!';
                                $status_sub = 'Yey! Pesananmu sudah siap disajikan. Silakan ambil ke kantin.';
                                $status_status = 'Siap Diambil 🟢';
                            } elseif ($status_baru === 'selesai') {
                                $status_teks_chat = 'Pesanan #' . $id_pesanan . ' Selesai!';
                                $status_sub = 'Terima kasih telah mengambil pesananmu! Semoga harimu menyenangkan.';
                                $status_status = 'Selesai & Lunas ✅';
                            } elseif ($status_baru === 'dibatalkan') {
                                $status_teks_chat = 'Pesanan #' . $id_pesanan . ' Dibatalkan!';
                                $status_sub = 'Maaf, pesananmu dibatalkan/ditolak oleh pihak kantin.';
                                $status_status = 'Dibatalkan ❌';
                            }

                            if (!empty($status_teks_chat)) {
                                $auto_status_msg = '[AUTO_REPLY_STATUS]
                                <div style="font-family:-apple-system,BlinkMacSystemFont,\'Segoe UI\',Roboto,sans-serif;max-width:320px;padding:4px;">
                                    <div style="font-weight:800;font-size:14px;color:#0f172a;margin-bottom:6px;">' . $status_teks_chat . '</div>
                                    <div style="font-size:12px;color:#64748b;margin-bottom:12px;">' . $status_sub . '</div>
                                    <div style="padding:10px 12px;background:#f8fafc;border-radius:10px;border:1px solid #e2e8f0;display:flex;justify-content:space-between;align-items:center;">
                                        <span style="font-size:12px;font-weight:600;color:#475569;">Status Terbaru</span>
                                        <span style="font-size:12px;font-weight:800;color:#1e293b;">' . $status_status . '</span>
                                    </div>
                                </div>';

                                $msg_escaped = mysqli_real_escape_string($conn, $auto_status_msg);
                                mysqli_query($conn, "INSERT INTO pesan_chat (id_pengirim, id_penerima, isi_pesan, waktu_kirim, sudah_dibaca)
                                                     VALUES ('$pengirim_chat', '$penerima_chat', '$msg_escaped', NOW(), 0)");
                            }
                        }
                    }
                    
                    // KUNCI PERBAIKAN: Jika status berubah menjadi 'selesai' dan sebelumnya bukan selesai
                    if ($status_baru === 'selesai' && $status_lama !== 'selesai') {
                        // 1. Ubah status pembayaran
                        mysqli_query($conn, "UPDATE pembayaran SET status = 'lunas' WHERE id_pesanan = $id_pesanan");
                        
                        // 2. Ambil barang-barang yang dibeli, lalu tambahkan ke kolom 'terjual' di tabel menu
                        $items = mysqli_query($conn, "SELECT id_menu, jumlah FROM detail_pesanan WHERE id_pesanan = $id_pesanan");
                        while ($item = mysqli_fetch_assoc($items)) {
                            $id_menu = (int)$item['id_menu'];
                            $jumlah = (int)$item['jumlah'];
                            mysqli_query($conn, "UPDATE menu SET terjual = terjual + $jumlah WHERE id_menu = $id_menu");
                        }

                        // 3. Otomatis catat pemasukan ke keuangan berdasarkan total_harga pesanan
                        $q_detail_kas = mysqli_query($conn,
                            "SELECT p.total_harga, p.nisn_pembeli, p.nuptk_pembeli,
                                    GROUP_CONCAT(m.nama_menu, ' (', dp.jumlah, 'x @ Rp ', FORMAT(m.harga,0), ')' ORDER BY m.nama_menu SEPARATOR '\n') AS item_list
                             FROM pesanan p
                             JOIN detail_pesanan dp ON dp.id_pesanan = p.id_pesanan
                             JOIN menu m ON m.id_menu = dp.id_menu
                             WHERE p.id_pesanan = $id_pesanan
                             GROUP BY p.id_pesanan LIMIT 1"
                        );
                        if ($q_detail_kas && $r_detail_kas = mysqli_fetch_assoc($q_detail_kas)) {
                            $total_pesanan = (float)$r_detail_kas['total_harga'];
                            $tgl_selesai   = date('Y-m-d');

                            // Resolve nama pembeli
                            $nama_pembeli = 'Pembeli';
                            if (!empty($r_detail_kas['nisn_pembeli'])) {
                                $nisn_esc = mysqli_real_escape_string($conn, $r_detail_kas['nisn_pembeli']);
                                $q_nama   = mysqli_query($conn, "SELECT nama FROM murid WHERE nisn = '$nisn_esc' LIMIT 1");
                                if ($q_nama && $r_nama = mysqli_fetch_assoc($q_nama)) {
                                    $nama_pembeli = $r_nama['nama'];
                                }
                            } elseif (!empty($r_detail_kas['nuptk_pembeli'])) {
                                $nuptk_esc = mysqli_real_escape_string($conn, $r_detail_kas['nuptk_pembeli']);
                                $q_nama    = mysqli_query($conn, "SELECT nama FROM guru WHERE nuptk = '$nuptk_esc' LIMIT 1");
                                if ($q_nama && $r_nama = mysqli_fetch_assoc($q_nama)) {
                                    $nama_pembeli = $r_nama['nama'];
                                }
                            }

                            $item_list   = $r_detail_kas['item_list'] ?? '-';
                            $total_fmt   = 'Rp ' . number_format($total_pesanan, 0, ',', '.');
                            $ket_panjang = "Pemasukan otomatis dari pesanan #$id_pesanan yang telah selesai.\n\nPembeli   : $nama_pembeli\n\nItem yang dibeli:\n$item_list\n\nTotal     : $total_fmt";

                            $ket_auto  = mysqli_real_escape_string($conn, $ket_panjang);
                            mysqli_query($conn, "INSERT INTO keuangan (id_toko, tipe, jumlah, keterangan, tanggal)
                                                 VALUES ($idToko, 'masuk', $total_pesanan, '$ket_auto', '$tgl_selesai')");
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
                    
                    $ajaxResponse = [
                        'success' => true,
                        'message' => 'Status pesanan #' . $id_pesanan . ' berhasil diubah menjadi: ' . ucfirst($status_baru)
                    ];
                    if (!$isAjax) {
                        $_SESSION['feedback'] = [
                            'type' => 'success',
                            'msg' => $ajaxResponse['message']
                        ];
                    }
                } catch (Exception $e) {
                    mysqli_rollback($conn);
                    $ajaxResponse = [
                        'success' => false,
                        'message' => 'Gagal mengubah status pesanan: ' . $e->getMessage()
                    ];
                    if (!$isAjax) {
                        $_SESSION['feedback'] = ['type' => 'danger', 'msg' => $ajaxResponse['message']];
                    }
                }
                }
            } else {
                $ajaxResponse = ['success' => false, 'message' => 'Akses ditolak atau pesanan tidak ditemukan.'];
                if (!$isAjax) {
                    $_SESSION['feedback'] = ['type' => 'danger', 'msg' => $ajaxResponse['message']];
                }
            }
        } else {
            $ajaxResponse = ['success' => false, 'message' => 'Status pesanan tidak valid.'];
            if (!$isAjax) {
                $_SESSION['feedback'] = ['type' => 'danger', 'msg' => $ajaxResponse['message']];
            }
        }
    }
}

if ($isAjax) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($ajaxResponse);
    exit;
}

header('Location: ' . $base_url . $rolePath . '?section=inbox&t=' . time());
exit;