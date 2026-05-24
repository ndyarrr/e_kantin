<?php
// backend/ambil_kontak.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json');

require_once __DIR__ . '/../config/database.php';

$db = $koneksi ?? $conn ?? null;
if (!$db) {
    echo json_encode(["error" => "Koneksi database gagal."]);
    exit;
}

$user_id_raw    = $_SESSION['user_id'] ?? '';
$role_sekarang  = $_SESSION['user_role'] ?? $_SESSION['role'] ?? '';
$sub_role       = $_SESSION['user_sub_role'] ?? ''; // 'owner' atau 'staf'
$id_toko_sesi   = (int)($_SESSION['id_toko'] ?? 0);
$keyword        = isset($_GET['search']) ? trim($_GET['search']) : '';

if (empty($user_id_raw)) {
    echo json_encode([]);
    exit;
}

// Build prefixed ID user yang sedang login
$user_sekarang = '';
if ($role_sekarang === 'siswa') {
    $user_sekarang = 'murid_' . $user_id_raw;
} elseif ($role_sekarang === 'guru') {
    $user_sekarang = 'guru_' . $user_id_raw;
} elseif ($role_sekarang === 'penjual') {
    // Penjual mengirim & menerima pesan ATAS NAMA KANTIN
    $user_sekarang = 'toko_' . $id_toko_sesi;
} else {
    $user_sekarang = $role_sekarang . '_' . $user_id_raw;
}

$hasil = [];

try {
    if (!empty($keyword)) {
        // ══ MODE PENCARIAN ══
        $param = "%" . $keyword . "%";

        if ($role_sekarang === 'admin') {
            // Admin: bisa cari semua kantin + semua pembeli
            $query = "SELECT id_user, nama, role_user, foto_profil FROM (
                SELECT CONCAT('toko_', id_toko) as id_user, nama_toko as nama, 'penjual' as role_user, foto_toko as foto_profil FROM toko WHERE deleted_at IS NULL
                UNION
                SELECT CONCAT('murid_', nisn) as id_user, nama, 'murid' as role_user, foto_profil FROM murid WHERE deleted_at IS NULL
                UNION
                SELECT CONCAT('guru_', nuptk) as id_user, nama, 'guru' as role_user, foto_profil FROM guru WHERE deleted_at IS NULL
            ) AS u WHERE nama LIKE ? LIMIT 20";

        } elseif ($role_sekarang === 'penjual') {
            // Penjual: cari pembeli (murid+guru) + admin saja
            $query = "SELECT id_user, nama, role_user, foto_profil FROM (
                SELECT CONCAT('admin_', id_admin) as id_user, nama, 'admin' as role_user, foto_profil FROM admin WHERE deleted_at IS NULL
                UNION
                SELECT CONCAT('murid_', nisn) as id_user, nama, 'murid' as role_user, foto_profil FROM murid WHERE deleted_at IS NULL
                UNION
                SELECT CONCAT('guru_', nuptk) as id_user, nama, 'guru' as role_user, foto_profil FROM guru WHERE deleted_at IS NULL
            ) AS u WHERE nama LIKE ? LIMIT 20";

        } elseif (in_array($role_sekarang, ['siswa', 'guru'])) {
            // Pembeli: cari kantin saja
            $query = "SELECT CONCAT('toko_', id_toko) as id_user, nama_toko as nama, 'kantin' as role_user, foto_toko as foto_profil
                      FROM toko WHERE deleted_at IS NULL AND nama_toko LIKE ? LIMIT 20";

        } else {
            echo json_encode([]);
            exit;
        }

        $stmt = $db->prepare($query);
        $stmt->bind_param("s", $param);
        $stmt->execute();
        $res = $stmt->get_result();

        while ($row = $res->fetch_assoc()) {
            if ($row['id_user'] === $user_sekarang) continue;
            $hasil[] = [
                'id'          => $row['id_user'],
                'nama'        => $row['nama'],
                'role'        => $row['role_user'],
                'foto_profil' => $row['foto_profil'],
                'unread'      => 0
            ];
        }

    } else {
        // ══ MODE DEFAULT: Riwayat Chat ══
        $query = "SELECT DISTINCT CASE WHEN id_pengirim = ? THEN id_penerima ELSE id_pengirim END as id_lawan
                  FROM pesan_chat WHERE id_pengirim = ? OR id_penerima = ? ORDER BY id_pesan DESC LIMIT 20";
        $stmt = $db->prepare($query);
        $stmt->bind_param("sss", $user_sekarang, $user_sekarang, $user_sekarang);
        $stmt->execute();
        $res = $stmt->get_result();

        while ($row = $res->fetch_assoc()) {
            $lawan = $row['id_lawan'];
            if (empty($lawan) || $lawan === $user_sekarang) continue;

            $parts      = explode('_', $lawan, 2);
            $type_lawan = $parts[0] ?? '';
            $id_lawan   = $parts[1] ?? '';

            $nama_lawan   = '';
            $role_display = '';
            $foto_profil  = null;
            $q_det        = '';

            if ($type_lawan === 'admin') {
                $q_det = "SELECT nama, foto_profil FROM admin WHERE id_admin = ? LIMIT 1";
                $role_display = 'admin';
            } elseif ($type_lawan === 'toko') {
                $q_det = "SELECT nama_toko as nama, foto_toko as foto_profil FROM toko WHERE id_toko = ? LIMIT 1";
                $role_display = 'kantin';
            } elseif ($type_lawan === 'murid') {
                $q_det = "SELECT nama, foto_profil FROM murid WHERE nisn = ? LIMIT 1";
                $role_display = 'murid';
            } elseif ($type_lawan === 'guru') {
                $q_det = "SELECT nama, foto_profil FROM guru WHERE nuptk = ? LIMIT 1";
                $role_display = 'guru';
            }

            if (!empty($q_det)) {
                $st = $db->prepare($q_det);
                $st->bind_param("s", $id_lawan);
                $st->execute();
                $det = $st->get_result()->fetch_assoc();

                if ($det) {
                    // Hitung unread
                    $q_un = "SELECT COUNT(*) as unread FROM pesan_chat WHERE id_pengirim = ? AND id_penerima = ? AND sudah_dibaca = 0";
                    $st_un = $db->prepare($q_un);
                    $unread_count = 0;
                    if ($st_un) {
                        $st_un->bind_param("ss", $lawan, $user_sekarang);
                        $st_un->execute();
                        $un = $st_un->get_result()->fetch_assoc();
                        $unread_count = $un['unread'] ?? 0;
                    }

                    $hasil[] = [
                        'id'          => $lawan,
                        'nama'        => $det['nama'],
                        'role'        => $role_display,
                        'foto_profil' => $det['foto_profil'],
                        'unread'      => $unread_count
                    ];
                }
            }
        }
    }

    echo json_encode($hasil);
} catch (Exception $e) {
    echo json_encode(["error" => $e->getMessage()]);
}