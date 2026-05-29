<?php
// views/chat.php — v2 (shared: admin, penjual, pembeli)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$db = $conn ?? $koneksi ?? null;
if (!$db) {
    die("<div class='chat-empty-state'>Koneksi database belum didefinisikan.</div>");
}

$user_id_raw = $_SESSION['user_id'] ?? '';
$role_sekarang = $_SESSION['user_role'] ?? $_SESSION['role'] ?? '';
$id_toko_sesi = (int) ($_SESSION['id_toko'] ?? 0);

if (empty($user_id_raw)) {
    echo "<div class='chat-empty-state'>Silakan login terlebih dahulu.</div>";
    exit;
}

// Deteksi base path berdasarkan posisi file pemanggil
$uri = $_SERVER['REQUEST_URI'] ?? '';
if (str_contains($uri, '/admin/')) {
    $base_path = '../../';
} elseif (str_contains($uri, '/penjual/')) {
    $base_path = '../../../';
} elseif (str_contains($uri, '/pembeli/')) {
    $base_path = '../../';
} else {
    $base_path = '../';
}

// Kirim info role ke JS untuk menentukan deskripsi placeholder
$role_label = match (true) {
    $role_sekarang === 'admin' => 'Cari nama kantin atau pembeli...',
    $role_sekarang === 'penjual' => 'Cari nama pembeli atau admin...',
    in_array($role_sekarang, ['siswa', 'guru', 'murid']) => 'Cari nama kantin...',
    default => 'Cari pengguna...'
};
?>
<link rel="stylesheet" href="<?= $base_path ?>assets/css/chat.css">

<div class="chat-wrapper">
    <!-- Sidebar Kontak -->
    <div class="chat-sidebar" id="sidebarKontak">
        <div class="chat-sidebar-header">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                <h2 style="font-size: 16px; margin: 0;">💬 Chat Messenger</h2>
                <button type="button" class="btn-close-chat-sidebar" onclick="toggleChatSidebar()" title="Tutup Kontak"
                    style="background: none; border: none; font-size: 16px; cursor: pointer; color: #64748b; display: none;">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            <div style="display: flex; gap: 6px;">
                <input type="text" id="inputSearchGlobal" placeholder="<?= htmlspecialchars($role_label) ?>"
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

    <!-- Area Utama Chat -->
    <div class="chat-main" id="wadahRuangObrolan" style="position: relative;">
        <button type="button" class="btn-toggle-chat-sidebar" onclick="toggleChatSidebar()" title="Buka/Tutup Kontak">
            <i class="fa-solid fa-angles-left" id="ikonToggleSidebar"></i>
        </button>

        <div class="chat-empty-state"
            style="flex: 1; display: flex; flex-direction: column; justify-content: center; align-items: center;">
            <i class="fa-solid fa-comments" style="font-size: 56px; margin-bottom: 15px; color: #cbd5e1;"></i>
            <p style="font-weight: 600; font-size: 15px; margin: 0; color: #64748b;">Mulai Percakapan</p>
            <p style="font-size: 12px; margin: 5px 0 0 0; color: #94a3b8;">
                <?= htmlspecialchars($role_label) ?>
            </p>
        </div>
    </div>


</div>

<div class="chat-modal-overlay" id="modalHapusChat">
    <div class="chat-modal-box">
        <i class="fa-solid fa-trash-can"></i>
        <p>Hapus pesan ini?</p>
        <span>Pesan akan dihapus permanen dan tidak dapat di lihat oleh orang lain,namun notifikasi akan di perlihatkan
            pada riwayat.</span>
        <div class="chat-modal-actions">
            <button class="btn-modal-batal" onclick="tutupModalHapus()">Batal</button>
            <button class="btn-modal-hapus" id="btnKonfirmasiHapus">Hapus</button>
        </div>
    </div>
</div>


<script>
    const BASE_URL_CHAT = '<?= $base_path ?>';
    let ID_LAWAN_AKTIF = '';
    let NAMA_LAWAN_AKTIF = '';
    let terakhirIdPesan = 0;
    let intervalPollingChat = null;

    let intervalPollingKontak = null;

    document.addEventListener("DOMContentLoaded", function () {
        muatDaftarKontak('');
        // Polling daftar kontak setiap 5 detik untuk update badge unread realtime
        intervalPollingKontak = setInterval(() => {
            const searchInput = document.getElementById('inputSearchGlobal');
            if (!searchInput || searchInput.value.trim() === '') {
                muatDaftarKontak('');
            }
        }, 5000);
    });

    /* ══════════════════════════════════════════════
       MUAT DAFTAR KONTAK (riwayat atau pencarian)
    ══════════════════════════════════════════════ */
    function muatDaftarKontak(keyword = '') {
        fetch(`${BASE_URL_CHAT}backend/ambil_kontak.php?search=${encodeURIComponent(keyword)}`)
            .then(res => {
                if (!res.ok) throw new Error("HTTP " + res.status);
                return res.json();
            })
            .then(data => {
                const wadah = document.getElementById('wadahDaftarKontak');
                if (!wadah) return;
                wadah.innerHTML = '';

                // Jaga kontak aktif tetap tampil di list walau tidak ada di riwayat
                if (keyword === '' && ID_LAWAN_AKTIF !== '') {
                    const sudahAda = data.some(c => c.id == ID_LAWAN_AKTIF);
                    if (!sudahAda && NAMA_LAWAN_AKTIF !== '') {
                        let detectedRole = 'user';
                        if (ID_LAWAN_AKTIF.startsWith('murid_')) detectedRole = 'murid';
                        else if (ID_LAWAN_AKTIF.startsWith('guru_')) detectedRole = 'guru';
                        else if (ID_LAWAN_AKTIF.startsWith('toko_')) detectedRole = 'kantin';
                        else if (ID_LAWAN_AKTIF.startsWith('admin_')) detectedRole = 'admin';

                        data.unshift({ id: ID_LAWAN_AKTIF, nama: NAMA_LAWAN_AKTIF, role: detectedRole, unread: 0, foto_profil: null });
                    }
                }

                if (!data || data.length === 0) {
                    wadah.innerHTML = `
                        <div class="chat-empty-state" style="padding: 20px 10px; text-align: center;">
                            <i class="fa-solid fa-user-slash" style="font-size: 24px; margin-bottom: 8px; color: #cbd5e1;"></i>
                            <p style="margin:0; font-size: 13px; color: #94a3b8;">Tidak ada percakapan.<br>Cari nama untuk memulai chat.</p>
                        </div>`;
                    return;
                }

                data.forEach(c => {
                    const isActive = (c.id == ID_LAWAN_AKTIF) ? 'active' : '';
                    const badge = (c.unread > 0) ? `<span class="chat-badge">${c.unread}</span>` : '';

                    // Warna avatar berdasarkan role
                    let warnaAvatar = '#79b775';
                    if (c.role === 'admin') warnaAvatar = '#3b82f6';
                    if (c.role === 'kantin') warnaAvatar = '#f59e0b';
                    if (c.role === 'penjual') warnaAvatar = '#f59e0b';
                    if (c.role === 'murid' || c.role === 'guru') warnaAvatar = '#22c55e';

                    const inisialNama = escapeHtml(c.nama.substring(0, 2).toUpperCase());
                    let isiAvatar = inisialNama;
                    let styleAvatar = `background-color: ${warnaAvatar}; text-transform: uppercase; display:flex; align-items:center; justify-content:center; font-weight:700; color:#fff;`;

                    if (c.foto_profil && c.foto_profil.trim() !== '') {
                        let pathFoto = '';
                        if (c.role === 'admin') {
                            pathFoto = `${BASE_URL_CHAT}assets/img/admin/${c.foto_profil}`;
                        } else if (c.role === 'kantin' || c.role === 'penjual') {
                            pathFoto = `${BASE_URL_CHAT}assets/img/kantin/${c.foto_profil}`;
                        } else {
                            pathFoto = `${BASE_URL_CHAT}assets/img/pembeli/${c.foto_profil}`;
                        }
                        isiAvatar = `<img src="${pathFoto}" style="width:100%;height:100%;object-fit:cover;border-radius:50%;" onerror="this.outerHTML='${inisialNama}'">`;
                    }

                    // Label role yang ditampilkan
                    let roleLabel = c.role;
                    if (c.role === 'kantin') roleLabel = '🏪 Kantin';
                    else if (c.role === 'admin') roleLabel = '🛡 Admin';
                    else if (c.role === 'murid') roleLabel = '🎒 Murid';
                    else if (c.role === 'guru') roleLabel = '📚 Guru';

                    wadah.innerHTML += `
                        <div class="chat-item ${isActive}" data-id="${c.id}" onclick="bukaRoomChat('${c.id}', '${escapeHtml(c.nama)}')">
                            <div class="chat-item-info">
                                <div class="chat-avatar" style="${styleAvatar}">${isiAvatar}</div>
                                <div class="chat-meta">
                                    <strong>${escapeHtml(c.nama)}</strong>
                                    <span style="font-size: 10px; font-weight:600; color: #94a3b8;">${roleLabel}</span>
                                </div>
                            </div>
                            ${badge}
                        </div>`;
                });
            })
            .catch(err => {
                console.error("Gagal load kontak:", err);
                document.getElementById('wadahDaftarKontak').innerHTML =
                    '<div style="padding:20px;text-align:center;color:#ef4444;font-size:12px;"><i class="fa-solid fa-triangle-exclamation"></i> Gagal memuat kontak</div>';
            });
    }

    function eksekusiCariUserGlobal() {
        const keyword = document.getElementById('inputSearchGlobal').value.trim();
        muatDaftarKontak(keyword);
    }

    /* ══════════════════════════════════════════════
       BUKA ROOM CHAT
    ══════════════════════════════════════════════ */
    function bukaRoomChat(idLawan, namaLawan) {
        // Di mobile: tutup sidebar
        if (window.innerWidth <= 768) {
            const sidebar = document.getElementById('sidebarKontak');
            if (sidebar) sidebar.classList.add('collapsed');
        }

        ID_LAWAN_AKTIF = idLawan;
        NAMA_LAWAN_AKTIF = namaLawan;
        terakhirIdPesan = 0;

        document.querySelectorAll('.chat-item').forEach(item => item.classList.remove('active'));
        const itemTerpilih = document.querySelector(`.chat-item[data-id="${idLawan}"]`);
        if (itemTerpilih) itemTerpilih.classList.add('active');

        const isCollapsed = document.getElementById('sidebarKontak').classList.contains('collapsed');
        const ikonKelas = isCollapsed ? 'fa-angles-right' : 'fa-angles-left';

        const wadahMain = document.getElementById('wadahRuangObrolan');
        wadahMain.innerHTML = `
            <button type="button" class="btn-toggle-chat-sidebar" onclick="toggleChatSidebar()" title="Buka/Tutup Kontak">
                <i class="fa-solid ${ikonKelas}" id="ikonToggleSidebar"></i>
            </button>
            <div class="chat-main-header">
                <div class="chat-status-dot"></div>
                <h3>Chat dengan: <span style="color: #1e293b;">${escapeHtml(namaLawan)}</span></h3>
            </div>
            <div id="boxPesanChat" class="chat-body"></div>
            <form id="formKirimChat" onsubmit="kirimPesan(event)" class="chat-footer">
                <input type="text" id="inputTeksChat" placeholder="Ketik pesan..." autocomplete="off" required class="chat-input">
                <button type="submit" class="chat-btn-send">
                    <i class="fa-solid fa-paper-plane"></i> Kirim
                </button>
            </form>`;

        document.getElementById('inputSearchGlobal').value = '';

        clearInterval(intervalPollingChat);
        loadChat();
        intervalPollingChat = setInterval(loadChat, 2000);
        muatDaftarKontak('');
    }

    /* ══════════════════════════════════════════════
       LOAD PESAN (POLLING)
    ══════════════════════════════════════════════ */
    function loadChat() {
        if (!ID_LAWAN_AKTIF) return;

        fetch(`${BASE_URL_CHAT}backend/ambil_chat.php?id_lawan=${ID_LAWAN_AKTIF}&terakhir_id=${terakhirIdPesan}`)
            .then(res => res.json())
            .then(data => {
                if (!data || data.length === 0) return;
                const box = document.getElementById('boxPesanChat');
                if (!box) return;

                let adaBaru = false;

                data.forEach(msg => {
                    if (msg.id > terakhirIdPesan) {
                        terakhirIdPesan = msg.id;

                        let infoStaf = '';
                        if (!msg.is_me && msg.nama_staf) {
                            infoStaf = `<div class="chat-staf-info">— ${escapeHtml(msg.nama_staf)}</div>`;
                        }

                        // Wrapper
                        const wrapper = document.createElement('div');
                        wrapper.className = `chat-bubble-wrapper ${msg.is_me ? 'me' : 'them'}`;

                        // Tombol hapus (hanya pesan sendiri)
                        if (msg.is_me) {
                            const tombolHapus = document.createElement('button');
                            tombolHapus.className = 'chat-btn-hapus';
                            tombolHapus.title = 'Hapus pesan';
                            tombolHapus.innerHTML = '<i class="fa-solid fa-trash"></i>';
                            tombolHapus.onclick = () => hapusPesan(msg.id, wrapper);
                            wrapper.appendChild(tombolHapus);
                        }

                        // Bubble
                        const bubble = document.createElement('div');
                        bubble.className = `chat-bubble ${msg.is_me ? 'me' : 'them'}`;
                        bubble.dataset.id = msg.id;
                        const isAutoReply = msg.pesan.startsWith('[AUTO_REPLY_ORDER]');
                        const isiPesan = isAutoReply
                            ? msg.pesan.replace('[AUTO_REPLY_ORDER]', '').trim()
                            : `<div style="word-break:break-word;">${escapeHtml(msg.pesan)}</div>`;

                        bubble.innerHTML = `
                        ${isiPesan}
                        <div class="chat-time">${msg.jam}</div>
                        ${infoStaf}`;

                        wrapper.appendChild(bubble);
                        box.appendChild(wrapper);
                        adaBaru = true;
                    }
                });

                if (adaBaru) box.scrollTop = box.scrollHeight;
            })
            .catch(err => console.error("Error load chat:", err));
    }

    /* ══════════════════════════════════════════════
       KIRIM PESAN
    ══════════════════════════════════════════════ */
    function kirimPesan(e) {
        e.preventDefault();
        const input = document.getElementById('inputTeksChat');
        if (!input || !ID_LAWAN_AKTIF) return;
        const teks = input.value.trim();
        if (!teks) return;

        const formData = new FormData();
        formData.append('id_penerima', ID_LAWAN_AKTIF);
        formData.append('isi_pesan', teks);
        input.value = '';

        fetch(`${ BASE_URL_CHAT }backend/kirim_chat.php`, { method: 'POST', body: formData })
            .then(res => res.json())
            .then(res => { if (res.status === 'success') loadChat(); })
            .catch(err => console.error("Error kirim:", err));
    }

    /* ══════════════════════════════════════════════
       TOGGLE SIDEBAR
    ══════════════════════════════════════════════ */
    function toggleChatSidebar() {
        const sidebar = document.getElementById('sidebarKontak');
        const ikon = document.getElementById('ikonToggleSidebar');
        if (sidebar && ikon) {
            sidebar.classList.toggle('collapsed');
            ikon.className = sidebar.classList.contains('collapsed')
                ? 'fa-solid fa-angles-right'
                : 'fa-solid fa-angles-left';
        }
    }

    function escapeHtml(text) {
        if (!text) return '';
        return String(text)
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    let targetHapusBubble = null;
    let targetHapusId = null;

    function hapusPesan(idPesan, wrapperEl) {
        targetHapusId = idPesan;
        targetHapusBubble = wrapperEl; // ← langsung wrapper, bukan bubble
        document.getElementById('modalHapusChat').classList.add('show');
    }

    function tutupModalHapus() {
        document.getElementById('modalHapusChat').classList.remove('show');
        targetHapusId = null;
        targetHapusBubble = null;
    }

    document.getElementById('btnKonfirmasiHapus').addEventListener('click', function () {
        if (!targetHapusId) return;

        // Simpan dulu sebelum tutupModalHapus() me-null-kan variabel
        const bubbleYangDihapus = targetHapusBubble;
        const idYangDihapus = targetHapusId;

        const formData = new FormData();
        formData.append('id_pesan', idYangDihapus);

        fetch(`${ BASE_URL_CHAT }backend/hapus_chat.php`, { method: 'POST', body: formData })
            .then(res => res.json())
            .then(res => {
                tutupModalHapus(); // aman di-null-kan sekarang
                if (res.status === 'success') {
                    if (bubbleYangDihapus) {
                        bubbleYangDihapus.style.transition = 'opacity 0.3s, transform 0.3s';
                        bubbleYangDihapus.style.opacity = '0';
                        bubbleYangDihapus.style.transform = 'translateX(20px)';
                        setTimeout(() => bubbleYangDihapus.remove(), 300);
                    }
                } else {
                    alert(res.msg || 'Gagal menghapus pesan');
                }
            })
            .catch(err => console.error('Error hapus:', err));
    });

    // Tutup modal kalau klik overlay
    document.getElementById('modalHapusChat').addEventListener('click', function (e) {
        if (e.target === this) tutupModalHapus();
    });

</script>