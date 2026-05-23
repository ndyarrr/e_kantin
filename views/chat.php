<?php
// views/chat.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. SINKRONISASI KONEKSI DATABASE (Mencoba mendeteksi semua nama variabel koneksi yang umum)
$db = $conn ?? $koneksi ?? $db ?? null;
if (!$db) {
    die("<div class='chat-empty-state'>Koneksi database belum didefinisikan. Pastikan file koneksi/database sudah di-include sebelum file chat ini.</div>");
}

$user_sekarang = $_SESSION['user_id'] ?? '';
$role_sekarang = $_SESSION['user_role'] ?? $_SESSION['role'] ?? '';

if (empty($user_sekarang)) {
    echo "<div class='chat-empty-state'>Silakan login terlebih dahulu.</div>";
    exit;
}

// =========================================================================
// API INTERNAL: Handle Request AJAX dari JavaScript untuk List Kontak
// =========================================================================
// =========================================================================
// API INTERNAL: Handle Request AJAX dari JavaScript untuk List Kontak
// =========================================================================
if (isset($_GET['aksi_internal']) && $_GET['aksi_internal'] === 'ambil_kontak') {
    header('Content-Type: application/json');
    $keyword = isset($_GET['search']) ? trim($_GET['search']) : '';
    $hasil = [];

    $user_id_raw = $_SESSION['user_id'] ?? '';
    $role_sekarang = $_SESSION['user_role'] ?? $_SESSION['role'] ?? '';

    // Buat prefixed ID untuk user yang sedang login
    $user_sekarang = '';
    if ($role_sekarang === 'siswa') {
        $user_sekarang = 'murid_' . $user_id_raw;
    } else if ($role_sekarang === 'guru') {
        $user_sekarang = 'guru_' . $user_id_raw;
    } else {
        $user_sekarang = $role_sekarang . '_' . $user_id_raw;
    }

    try {
        if (!empty($keyword)) {
            $param = "%" . $keyword . "%";
            $query = "SELECT id_user, nama, role_user FROM (
                        SELECT CONCAT('admin_', id_admin) as id_user, nama, 'admin' as role_user FROM admin WHERE deleted_at IS NULL
                        UNION
                        SELECT CONCAT('penjual_', id_penjual) as id_user, nama, 'penjual' as role_user FROM penjual WHERE deleted_at IS NULL
                        UNION
                        SELECT CONCAT('murid_', nisn) as id_user, nama, 'pembeli' as role_user FROM murid WHERE deleted_at IS NULL
                        UNION
                        SELECT CONCAT('guru_', nuptk) as id_user, nama, 'pembeli' as role_user FROM guru WHERE deleted_at IS NULL
                      ) AS u WHERE nama LIKE ? LIMIT 20";

            $stmt = $db->prepare($query);
            if (!$stmt) {
                throw new Exception($db->error);
            }
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
                    'unread' => 0
                ];
            }
        } else {
            $query = "SELECT DISTINCT CASE WHEN id_pengirim = ? THEN id_penerima ELSE id_pengirim END as id_lawan 
                      FROM pesan_chat WHERE id_pengirim = ? OR id_penerima = ? ORDER BY id_pesan DESC LIMIT 20";

            $stmt = $db->prepare($query);
            if (!$stmt) {
                throw new Exception($db->error);
            }
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
                $q_det = '';

                if ($role_lawan === 'admin') {
                    $q_det = "SELECT nama FROM admin WHERE id_admin = ? LIMIT 1";
                    $role_display = 'admin';
                } else if ($role_lawan === 'penjual') {
                    $q_det = "SELECT nama FROM penjual WHERE id_penjual = ? LIMIT 1";
                    $role_display = 'penjual';
                } else if ($role_lawan === 'murid') {
                    $q_det = "SELECT nama FROM murid WHERE nisn = ? LIMIT 1";
                    $role_display = 'pembeli';
                } else if ($role_lawan === 'guru') {
                    $q_det = "SELECT nama FROM guru WHERE nuptk = ? LIMIT 1";
                    $role_display = 'pembeli';
                }

                if (!empty($q_det)) {
                    $st_det = $db->prepare($q_det);
                    if ($st_det) {
                        $st_det->bind_param("s", $id_lawan_raw);
                        $st_det->execute();
                        $det = $st_det->get_result()->fetch_assoc();

                        if ($det) {
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
                                'nama' => $det['nama'],
                                'role' => $role_display,
                                'unread' => $unread_count
                            ];
                        }
                    }
                }
            }
        }

        echo json_encode($hasil);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(["error" => $e->getMessage()]);
    }
    exit;
}

// DETEKSI BASE PATH PATH URL UNTUK AMBIL & KIRIM CHAT
$is_admin_panel = (str_contains($_SERVER['REQUEST_URI'], '/admin/'));
$base_path = $is_admin_panel ? '../../' : '../';
?>
<link rel="stylesheet" href="<?= $base_path ?>assets/css/chat.css">

<div class="chat-wrapper">
    <!-- 1. Tambahkan id="sidebarKontak" pada sidebar -->
    <div class="chat-sidebar" id="sidebarKontak">
        <div class="chat-sidebar-header">
            <h2 style="font-size: 16px; margin: 0 0 10px 0;">E-Kantin Chat Messenger</h2>
            <div style="display: flex; gap: 6px;">
                <input type="text" id="inputSearchGlobal" placeholder="Cari nama orang..."
                    onkeyup="eksekusiCariUserGlobal()"
                    style="flex:1; padding: 7px 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 12px; outline: none;">
                <button type="button"
                    style="background: #79b775; color: white; border: none; padding: 0 12px; border-radius: 6px; pointer-events: none;">
                    <i class="fa-solid fa-magnifying-glass"></i>
                </button>
            </div>
        </div>

        <div class="chat-list" id="wadahDaftarKontak">
            <div style="padding: 20px; text-align: center; color: #94a3b8; font-size: 12px;">Memuat kontak...</div>
        </div>
    </div>

    <!-- 2. Tambahkan style="position: relative;" dan Tombol Toggle pada wadahRuangObrolan -->
    <div class="chat-main" id="wadahRuangObrolan" style="position: relative;">
        <button type="button" class="btn-toggle-chat-sidebar" onclick="toggleChatSidebar()" title="Buka/Tutup Kontak">
            <i class="fa-solid fa-angles-left" id="ikonToggleSidebar"></i>
        </button>

        <div class="chat-empty-state"
            style="flex: 1; display: flex; flex-direction: column; justify-content: center; align-items: center;">
            <i class="fa-solid fa-comments" style="font-size: 56px; margin-bottom: 15px; color: #cbd5e1;"></i>
            <p style="font-weight: 600; font-size: 15px; margin: 0; color: #64748b;">Mulai Hubungi Pengguna</p>
            <p style="font-size: 12px; margin: 5px 0 0 0; color: #94a3b8;">Cari nama admin, pedagang kantin, atau murid
                di atas untuk chatting.</p>
        </div>
    </div>
</div>

<script>
    const BASE_URL_CHAT = '<?= $base_path ?>';
    let ID_LAWAN_AKTIF = '';
    let NAMA_LAWAN_AKTIF = '';
    let terakhirIdPesan = 0;
    let intervalPollingChat = null;

    // Ambil daftar kontak default (riwayat chat) saat halaman selesai dimuat
    document.addEventListener("DOMContentLoaded", function () {
        muatDaftarKontak('');
    });

    // Menembak ke file backend/ambil_kontak.php
    // Menembak ke file backend/ambil_kontak.php
       // Menembak ke file backend/ambil_kontak.php
    function muatDaftarKontak(keyword = '') {
        fetch(`${BASE_URL_CHAT}backend/ambil_kontak.php?search=${encodeURIComponent(keyword)}`)
            .then(res => {
                if (!res.ok) throw new Error("HTTP error " + res.status);
                return res.json();
            })
            .then(data => {
                const wadah = document.getElementById('wadahDaftarKontak');
                if (!wadah) return;
                wadah.innerHTML = '';

                // JIKA SEDANG TIDAK MENCARI, TAPI ADA ROOM AKTIF YANG SEDANG DIBUKA
                if (keyword === '' && ID_LAWAN_AKTIF !== '') {
                    const sudahAda = data.some(c => c.id == ID_LAWAN_AKTIF);
                    if (!sudahAda && NAMA_LAWAN_AKTIF !== '') {
                        data.unshift({ id: ID_LAWAN_AKTIF, nama: NAMA_LAWAN_AKTIF, role: 'user', unread: 0, foto_profil: null });
                    }
                }

                if (!data || data.length === 0) {
                    wadah.innerHTML = `
                        <div class="chat-empty-state" style="padding: 20px 10px; text-align: center;">
                            <i class="fa-solid fa-user-slash" style="font-size: 24px; margin-bottom: 8px; color: #cbd5e1;"></i>
                            <p style="margin:0; font-size: 13px; color: #94a3b8;">User tidak ditemukan.</p>
                        </div>`;
                    return;
                }

                data.forEach(c => {
                    const isActive = (c.id == ID_LAWAN_AKTIF) ? 'active' : '';
                    const badge = (c.unread > 0) ? `<span class="chat-badge">${c.unread}</span>` : '';
                    
                    // 1. Tentukan warna background avatar bawaan berdasarkan role
                    let warnaAvatar = '#79b775'; // default hijau pembeli
                    if (c.role === 'admin') warnaAvatar = '#3b82f6';
                    if (c.role === 'penjual') warnaAvatar = '#f59e0b';

                    // Inisial 2 huruf nama depan
                    const inisialNama = escapeHtml(c.nama.substring(0, 2).toUpperCase());

                    // 2. Logic Render Foto vs Inisial Nama Depan
                    let isiAvatar = '';
                    let styleAvatar = `background-color: ${warnaAvatar}; text-transform: uppercase;`;

                    if (c.foto_profil && c.foto_profil.trim() !== '') {
                        // Tentukan path gambar kustom
                        let pathFoto = '';
                        if (c.role === 'admin') {
                            pathFoto = `${BASE_URL_CHAT}assets/img/admin/${c.foto_profil}`;
                        } else if (c.role === 'penjual') {
                            pathFoto = `${BASE_URL_CHAT}assets/img/penjual/${c.foto_profil}`;
                        } else {
                            pathFoto = `${BASE_URL_CHAT}assets/img/pembeli/${c.foto_profil}`;
                        }
                        
                        // Isi dengan tag <img>. Jika gambar gagal dimuat (error 404), dia otomatis fallback ke inisial nama
                        isiAvatar = `<img src="${pathFoto}" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%; display: block;" onerror="this.outerHTML='${inisialNama}';">`;
                    } else {
                        // Jika tidak ada foto profil, render teks inisial
                        isiAvatar = inisialNama;
                    }

                    wadah.innerHTML += `
                        <div class="chat-item ${isActive}" data-id="${c.id}" onclick="bukaRoomChatRealtime('${c.id}', '${escapeHtml(c.nama)}')">
                            <div class="chat-item-info">
                                <div class="chat-avatar" style="${styleAvatar}">
                                    ${isiAvatar}
                                </div>
                                <div class="chat-meta">
                                    <strong>${escapeHtml(c.nama)}</strong>
                                    <span style="font-size: 10px; text-transform: uppercase; font-weight: bold; color: #94a3b8;">${c.role}</span>
                                </div>
                            </div>
                            ${badge}
                        </div>`;
                });
            })
            .catch(err => {
                console.error("Gagal load kontak:", err);
                document.getElementById('wadahDaftarKontak').innerHTML = '<div style="padding:20px;text-align:center;color:#ef4444;font-size:12px;"><i class="fa-solid fa-triangle-exclamation"></i> Gagal memuat data kontak</div>';
            });
    }

    function eksekusiCariUserGlobal() {
        const keyword = document.getElementById('inputSearchGlobal').value.trim();
        muatDaftarKontak(keyword);
    }

    function bukaRoomChatRealtime(idLawan, namaLawan) {

        if (window.innerWidth <= 768) {
            const sidebar = document.getElementById('sidebarKontak');
            if (sidebar) sidebar.classList.add('collapsed');
        }
        
        ID_LAWAN_AKTIF = idLawan;
        NAMA_LAWAN_AKTIF = namaLawan;
        terakhirIdPesan = 0; // Reset ID pesan ke 0

        // Atur class active pada element list kontak secara instant
        document.querySelectorAll('.chat-item').forEach(item => item.classList.remove('active'));
        const itemTerpilih = document.querySelector(`.chat-item[data-id="${idLawan}"]`);
        if (itemTerpilih) itemTerpilih.classList.add('active');

        // Cek status sidebar saat ini untuk menentukan arah panah ikon toggle
        const isCollapsed = document.getElementById('sidebarKontak').classList.contains('collapsed');
        const ikonKelas = isCollapsed ? 'fa-angles-right' : 'fa-angles-left';

        const wadahMain = document.getElementById('wadahRuangObrolan');
        wadahMain.innerHTML = `
            <!-- Tombol Toggle Sidebar tetap muncul di room chat -->
            <button type="button" class="btn-toggle-chat-sidebar" onclick="toggleChatSidebar()" title="Buka/Tutup Kontak">
                <i class="fa-solid ${ikonKelas}" id="ikonToggleSidebar"></i>
            </button>
            <div class="chat-main-header">
                <div class="chat-status-dot"></div>
                <h3>Chat dengan: <span style="color: #1e293b;">${namaLawan}</span></h3>
            </div>
            <div id="boxPesanChat" class="chat-body"></div>
            <form id="formKirimChat" onsubmit="kirimPesanRealtime(event)" class="chat-footer">
                <input type="text" id="inputTeksChat" placeholder="Ketik pesan kamu di sini..." autocomplete="off" required class="chat-input">
                <button type="submit" class="chat-btn-send">
                    <i class="fa-solid fa-paper-plane"></i> Kirim
                </button>
            </form>`;

        // Bersihkan kolom pencarian global
        document.getElementById('inputSearchGlobal').value = '';

        clearInterval(intervalPollingChat);
        loadChatRealtime(); // Tarik chat pertama kali secara instant
        intervalPollingChat = setInterval(loadChatRealtime, 2000);

        // Panggil kembali muatDaftarKontak agar list kembali normal
        muatDaftarKontak('');
    }

    function loadChatRealtime() {
        if (!ID_LAWAN_AKTIF) return;

        fetch(`${BASE_URL_CHAT}backend/ambil_chat.php?id_lawan=${ID_LAWAN_AKTIF}&terakhir_id=${terakhirIdPesan}`)
            .then(res => res.json())
            .then(data => {
                if (!data || data.length === 0) return;
                const box = document.getElementById('boxPesanChat');
                if (!box) return;

                let adaPesanBaru = false;

                data.forEach(msg => {
                    // VALIDASI KETAT ANTI-DUPLIKAT: Hanya cetak jika ID pesan belum ada di layar
                    if (msg.id > terakhirIdPesan) {
                        terakhirIdPesan = msg.id; // Update pointer ID pesan terakhir

                        const bubble = document.createElement('div');
                        bubble.className = `chat-bubble ${msg.is_me ? 'me' : 'them'}`;
                        bubble.innerHTML = `
                            <div style="word-break: break-word;">${msg.pesan}</div>
                            <div class="chat-time">${msg.jam}</div>`;
                        box.appendChild(bubble);
                        adaPesanBaru = true;
                    }
                });

                // Auto scroll ke bawah hanya jika beneran ada pesan baru masuk
                if (adaPesanBaru) {
                    box.scrollTop = box.scrollHeight;
                }
            })
            .catch(err => console.error("Error ambil chat:", err));
    }

    function kirimPesanRealtime(e) {
        e.preventDefault();
        const input = document.getElementById('inputTeksChat');
        if (!input || !ID_LAWAN_AKTIF) return;
        const teks = input.value.trim();
        if (!teks) return;

        const formData = new FormData();
        formData.append('id_penerima', ID_LAWAN_AKTIF);
        formData.append('isi_pesan', teks);
        input.value = ''; // Mengosongkan inputan text secara cepat (Instant UI Feel)

        fetch(`${BASE_URL_CHAT}backend/kirim_chat.php`, {
            method: 'POST',
            body: formData
        })
            .then(res => res.json())
            .then(res => {
                if (res.status === 'success') {
                    loadChatRealtime(); // Trigger penarikan data instan setelah kirim sukses
                }
            })
            .catch(err => console.error("Error kirim chat:", err));
    }

    function escapeHtml(text) {
        return text ? text.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;") : '';
    }

    // Fungsi Toggle Buka / Tutup Sidebar Kontak
    function toggleChatSidebar() {
        const sidebar = document.getElementById('sidebarKontak');
        const ikon = document.getElementById('ikonToggleSidebar');

        if (sidebar && ikon) {
            sidebar.classList.toggle('collapsed');

            if (sidebar.classList.contains('collapsed')) {
                ikon.className = 'fa-solid fa-angles-right';
            } else {
                ikon.className = 'fa-solid fa-angles-left';
            }
        }
    }
</script>