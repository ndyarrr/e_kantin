<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
// backend/ambil_kontak.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json');

require_once __DIR__ . '/../config/database.php';

$db = $koneksi ?? $conn ?? null;
if (!$db) {
    echo json_encode(["error" => "Koneksi database gagal didefinisikan."]);
    exit;
}

$user_id_raw = $_SESSION['user_id'] ?? '';
$role_sekarang = $_SESSION['user_role'] ?? $_SESSION['role'] ?? '';
$keyword = isset($_GET['search']) ? trim($_GET['search']) : '';

if (empty($user_id_raw)) {
    echo json_encode([]);
    exit;
}

// Buat prefixed ID untuk user yang sedang login saat ini
$user_sekarang = '';
if ($role_sekarang === 'siswa') {
    $user_sekarang = 'murid_' . $user_id_raw;
} else if ($role_sekarang === 'guru') {
    $user_sekarang = 'guru_' . $user_id_raw;
} else {
    $user_sekarang = $role_sekarang . '_' . $user_id_raw;
}

$hasil = [];

try {
    if (!empty($keyword)) {
        // A. QUERY PENCARIAN GLOBAL (Ikut ambil kolom foto_profil)
        $param = "%" . $keyword . "%";
        $query = "SELECT id_user, nama, role_user, foto_profil FROM (
                    SELECT CONCAT('admin_', id_admin) as id_user, nama, 'admin' as role_user, foto_profil FROM admin WHERE deleted_at IS NULL
                    UNION
                    SELECT CONCAT('penjual_', id_penjual) as id_user, nama, 'penjual' as role_user, foto_profil FROM penjual WHERE deleted_at IS NULL
                    UNION
                    SELECT CONCAT('murid_', nisn) as id_user, nama, 'pembeli' as role_user, foto_profil FROM murid WHERE deleted_at IS NULL
                    UNION
                    SELECT CONCAT('guru_', nuptk) as id_user, nama, 'pembeli' as role_user, foto_profil FROM guru WHERE deleted_at IS NULL
                  ) AS u WHERE nama LIKE ? LIMIT 20";

        $stmt = $db->prepare($query);
        $stmt->bind_param("s", $param);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            if ($row['id_user'] === $user_sekarang)
                continue;
            $hasil[] = [
                'id' => $row['id_user'],
                'nama' => $row['nama'],
                'role' => $row['role_user'],
                'foto_profil' => $row['foto_profil'], // <-- Ditambahkan di sini
                'unread' => 0
            ];
        }
    } else {
        // B. QUERY DEFAULT: RIWAYAT CHAT TERAKHIR
        $query = "SELECT DISTINCT CASE WHEN id_pengirim = ? THEN id_penerima ELSE id_pengirim END as id_lawan
                  FROM pesan_chat WHERE id_pengirim = ? OR id_penerima = ? ORDER BY id_pesan DESC LIMIT 20";
        $stmt = $db->prepare($query);
        $stmt->bind_param("sss", $user_sekarang, $user_sekarang, $user_sekarang);
        $stmt->execute();
        $res = $stmt->get_result();

        while ($row = $res->fetch_assoc()) {
            $lawan = $row['id_lawan'];
            if (empty($lawan) || $lawan === $user_sekarang)
                continue;

            $parts = explode('_', $lawan, 2);
            $role_lawan = $parts[0] ?? '';
            $id_lawan_raw = $parts[1] ?? '';

            $nama_lawan = '';
            $role_display = '';
            $foto_profil = null;
            $q_det = '';

            // Ambil kolom foto_profil di setiap query detail
            if ($role_lawan === 'admin') {
                $q_det = "SELECT nama, foto_profil FROM admin WHERE id_admin = ? LIMIT 1";
                $role_display = 'admin';
            } else if ($role_lawan === 'penjual') {
                $q_det = "SELECT nama, foto_profil FROM penjual WHERE id_penjual = ? LIMIT 1";
                $role_display = 'penjual';
            } else if ($role_lawan === 'murid') {
                $q_det = "SELECT nama, foto_profil FROM murid WHERE nisn = ? LIMIT 1";
                $role_display = 'pembeli';
            } else if ($role_lawan === 'guru') {
                $q_det = "SELECT nama, foto_profil FROM guru WHERE nuptk = ? LIMIT 1";
                $role_display = 'pembeli';
            }

            if (!empty($q_det)) {
                $st_det = $db->prepare($q_det);
                $st_det->bind_param("s", $id_lawan_raw);
                $st_det->execute();
                $det = $st_det->get_result()->fetch_assoc();

                if ($det) {
                    $nama_lawan = $det['nama'];
                    $foto_profil = $det['foto_profil']; // <-- Mengambil foto_profil

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
                        'id' => $lawan,
                        'nama' => $nama_lawan,
                        'role' => $role_display,
                        'foto_profil' => $foto_profil, // <-- Ditambahkan ke output JSON
                        'unread' => $unread_count
                    ];
                }
            }
        }
    }
    echo json_encode($hasil);
} catch (Exception $e) {
    echo json_encode(["error" => $e->getMessage()]);
}