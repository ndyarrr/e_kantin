<?php
/** @var array $daftarPesanan */
/** @var array $jumlahPerStatus */
/** @var array $profilPenjual */

require_once __DIR__ . '/../../../config/toko_foto.php';

$filterStatus = $filterStatus ?? ($_GET['status_filter'] ?? 'semua');
$inboxSearch = $inboxSearch ?? ($_GET['inbox_search'] ?? '');
$fotoTokoNota = !empty($profilPenjual['foto_toko'])
    ? tokoFotoUrl($profilPenjual['foto_toko'], '../../../')
    : '';

$tabs = [
    'semua'        => ['label' => 'Semua',        'icon' => 'fa-inbox'],
    'menunggu'     => ['label' => 'Menunggu',     'icon' => 'fa-clock'],
    'dikonfirmasi' => ['label' => 'Diproses',     'icon' => 'fa-fire-burner'],
    'siap_diambil' => ['label' => 'Siap Diambil', 'icon' => 'fa-bell-concierge'],
    'selesai'      => ['label' => 'Selesai',      'icon' => 'fa-circle-check'],
    'dibatalkan'   => ['label' => 'Dibatalkan',   'icon' => 'fa-circle-xmark'],
];

$inbox_base = '';
if (preg_match('#^(.*)/(views|auth|backend|controllers|config|assets|scratch)/#', $_SERVER['SCRIPT_NAME'] ?? '', $m)) {
    $inbox_base = $m[1];
} elseif (preg_match('#^(.*)/index\.php#', $_SERVER['SCRIPT_NAME'] ?? '', $m)) {
    $inbox_base = $m[1];
}
$fotoBase = $inbox_base . '/assets/img/kantin/';
?>

<!-- ══ MODAL NOTA ══ -->
<div id="notaModal" class="nota-overlay" onclick="tutupNota(event)">
    <div class="nota-box" id="notaBox">
        <div id="notaKonten">
            <!-- Jagged Edge Top -->
            <div class="nota-jagged-top"></div>
            <div class="nota-header">
                <div class="nota-logo" id="notaLogo"></div>
                <div class="nota-toko-nama" id="notaTokoNama"></div>
                <div class="nota-sub">E-Kantin SMKN 1</div>
            </div>
            <div class="nota-garis"></div>
            <div class="nota-info">
                <div class="nota-info-row"><span>No. Pesanan</span><span id="notaId"></span></div>
                <div class="nota-info-row"><span>Pembeli</span><span id="notaPembeli"></span></div>
                <div class="nota-info-row"><span>Kelas</span><span id="notaKelas"></span></div>
                <div class="nota-info-row"><span>Waktu</span><span id="notaWaktu"></span></div>
                <div class="nota-info-row"><span>Kasir</span><span id="notaKasir"></span></div>
                <div class="nota-info-row"><span>Shift</span><span id="notaShift"></span></div>
                <div class="nota-info-row"><span>Pembayaran</span><span id="notaMetode"></span></div>
            </div>
            <div class="nota-garis"></div>
            <table class="nota-table" id="notaTable">
                <thead>
                    <tr>
                        <th>Item</th>
                        <th class="center">Qty</th>
                        <th class="right">Harga</th>
                    </tr>
                </thead>
                <tbody id="notaItems"></tbody>
            </table>
            <div class="nota-garis"></div>
            <div class="nota-total-row"><span>TOTAL</span><span id="notaTotal"></span></div>
            <div class="nota-garis"></div>
            <div class="nota-footer">
                <div class="nota-footer-bold">Terima kasih atas kunjungan Anda!</div>
                <div class="nota-footer-sub">Semoga hari Anda menyenangkan</div>
            </div>
            <!-- Jagged Edge Bottom -->
            <div class="nota-jagged-bottom"></div>
        </div>
        <div class="nota-actions no-print">
            <button type="button" class="pcard-btn pcard-btn-batal" onclick="tutupNota()">
                <i class="fa-solid fa-xmark"></i> Tutup
            </button>
            <?php if (($_SESSION['user_sub_role'] ?? '') !== 'owner'): ?>
            <button type="button" class="pcard-btn pcard-btn-print" onclick="cetakNota()">
                <i class="fa-solid fa-print"></i> Cetak
            </button>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- ══ SEARCH BAR ══ -->
<div class="inbox-search-bar-form" style="margin-bottom: 20px;">
    <div class="search-box" style="max-width: 100%; width: 100%; display: flex; align-items: center; background: #fff; padding: 12px 15px; border-radius: 10px; border: 1px solid #e5e7eb; box-shadow: 0 2px 4px rgba(0,0,0,0.02); box-sizing: border-box;">
        <i class="fa-solid fa-magnifying-glass" style="color: #9ca3af; margin-right: 10px; font-size: 14px;"></i>
        <input type="text" id="inboxSearchInput" placeholder="Cari nama pemesan..."
            value="<?= htmlspecialchars($inboxSearch) ?>"
            autocomplete="off"
            style="border: none; outline: none; width: 100%; font-size: 14px; background: transparent;">
        <button type="button" id="inboxSearchClear" title="Hapus pencarian"
            style="display: <?= $inboxSearch !== '' ? 'flex' : 'none' ?>; color: #9ca3af; margin-left: 10px; border: none; background: transparent; cursor: pointer; align-items: center;">
            <i class="fa-solid fa-circle-xmark" style="font-size: 16px;"></i>
        </button>
    </div>
</div>

<div id="inboxRealtimeWrap">
    <?php require __DIR__ . '/inbox_fragment.php'; ?>
</div>

<script>
window.INBOX_RT_CONFIG = {
    apiUrl: '<?= $inbox_base ?>/views/penjual/actions/ambil_inbox.php',
    prosesUrl: '<?= $inbox_base ?>/views/penjual/actions/proses_inbox.php',
    pollInterval: 5000,
    filterStatus: <?= json_encode($filterStatus) ?>,
    inboxSearch: <?= json_encode($inboxSearch) ?>
};
</script>

<script>
let _notaIdPesanan = null;

function bukaNotaModal(data, idPesanan) {
    _notaIdPesanan = idPesanan;
    document.getElementById('notaTokoNama').textContent = data.toko;
    const logoEl = document.getElementById('notaLogo');
    if (data.foto) {
        logoEl.innerHTML = `<img src="${data.foto}" style="width:100px;height:100px;object-fit:cover;border-radius:8px;margin-bottom:8px;border:2px solid #ddd;" onerror="this.onerror=null; this.outerHTML='🏪';">`;
    } else {
        logoEl.innerHTML = '🏪';
    }
    document.getElementById('notaId').textContent = '#' + data.id;
    document.getElementById('notaPembeli').textContent = data.pembeli;
    document.getElementById('notaKelas').textContent = data.kelas;
    document.getElementById('notaWaktu').textContent = data.waktu;

    // Set new fields
    document.getElementById('notaKasir').textContent = data.kasir || 'Kasir';
    document.getElementById('notaShift').textContent = data.shift ? 'Shift ' + data.shift : '-';
    document.getElementById('notaMetode').textContent = data.metode || 'Tunai';

    document.getElementById('notaTotal').textContent = 'Rp ' + Number(data.total).toLocaleString('id-ID');

    const tbody = document.getElementById('notaItems');
    tbody.innerHTML = '';
    data.items.forEach(item => {
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td>${item.nama}</td>
            <td class="center">${item.jumlah}×</td>
            <td class="right">Rp ${Number(item.harga).toLocaleString('id-ID')}</td>`;
        tbody.appendChild(tr);
    });

    document.getElementById('notaModal').classList.add('show');
    document.body.style.overflow = 'hidden';
}

function tutupNota(e) {
    if (e && e.target !== document.getElementById('notaModal')) return;
    document.getElementById('notaModal').classList.remove('show');
    document.body.style.overflow = '';
}

function cetakNota() {
    window.print();
    if (_notaIdPesanan) {
        localStorage.setItem('printed_order_' + _notaIdPesanan, 'true');
        const btn = document.getElementById('btnSelesai-' + _notaIdPesanan);
        if (btn) {
            btn.disabled = false;
            btn.classList.remove('pcard-btn-selesai-locked');
            btn.title = '';
        }
    }
    setTimeout(() => {
        document.getElementById('notaModal').classList.remove('show');
        document.body.style.overflow = '';
    }, 400);
}
</script>

<!-- Modal Lihat Bukti QRIS (Penjual) -->
<div id="modalBuktiQrisPenjual" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.6);backdrop-filter:blur(8px);-webkit-backdrop-filter:blur(8px);z-index:9999999;align-items:center;justify-content:center;padding:16px;box-sizing:border-box;">
    <div id="buktiModalBox" style="background:#ffffff;width:100%;max-width:400px;border-radius:24px;padding:24px;box-shadow:0 20px 40px rgba(0,0,0,0.2);text-align:center;max-height:92vh;overflow-y:auto;box-sizing:border-box;">
        <div style="font-size:22px;color:#0284c7;margin-bottom:10px;"><i class="fa-solid fa-file-image"></i></div>
        <h3 style="margin:0 0 4px;font-size:16px;font-weight:800;color:#0f172a;font-family:'Poppins',sans-serif;">Bukti Pembayaran QRIS</h3>
        <p style="margin:0 0 14px;font-size:12px;color:#64748b;font-family:'Poppins',sans-serif;">Pesanan #<span id="buktiModalIdPesanan"></span></p>

        <div onclick="bukaFullscreenBukti()" style="display:block;margin-bottom:14px;cursor:pointer;">
            <div style="border-radius:14px;border:2px solid #e2e8f0;overflow:hidden;background:#f8fafc;position:relative;">
                <img id="buktiModalImg" src="" alt="Bukti Transfer" style="width:100%;object-fit:contain;max-height:420px;display:block;">
                <div style="position:absolute;bottom:6px;right:6px;background:rgba(15,23,42,0.7);color:#ffffff;font-size:10px;font-weight:700;padding:4px 8px;border-radius:6px;backdrop-filter:blur(4px);display:flex;align-items:center;gap:4px;">
                    <i class="fa-solid fa-expand" style="font-size:9px;"></i> Ketuk untuk perbesar
                </div>
            </div>
        </div>

        <div style="display:flex;gap:8px;">
            <button onclick="tutupModalBuktiQris()" style="flex:1;padding:11px;font-weight:700;font-size:13px;color:#475569;background:#f1f5f9;border:1px solid #e2e8f0;border-radius:12px;cursor:pointer;font-family:'Poppins',sans-serif;">
                Tutup
            </button>
            <button id="btnKonfirmasiBayarQris" onclick="konfirmasiPembayaranQris()" style="flex:2;padding:11px;font-weight:800;font-size:13px;color:#fff;background:#16a34a;border:none;border-radius:12px;cursor:pointer;font-family:'Poppins',sans-serif;display:inline-flex;align-items:center;justify-content:center;gap:6px;box-shadow:0 4px 12px rgba(22,163,74,0.2);">
                <i class="fa-solid fa-check-double"></i> Konfirmasi Lunas
            </button>
        </div>
    </div>
</div>

<!-- Modal Konfirmasi Batalkan Pesanan (Penjual) -->
<div id="modalBatalPesananPenjual" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(15,23,42,0);backdrop-filter:blur(0px);-webkit-backdrop-filter:blur(0px);z-index:9999999;align-items:center;justify-content:center;padding:16px;box-sizing:border-box;transition:all 0.25s ease-out;">
    <div id="batalModalBox" style="background:#ffffff;width:100%;max-width:380px;border-radius:24px;padding:28px 24px;box-shadow:0 20px 40px rgba(15,23,42,0.15);text-align:center;box-sizing:border-box;transform:scale(0.8);opacity:0;transition:all 0.25s cubic-bezier(0.34, 1.56, 0.64, 1);">
        <!-- Warning Icon -->
        <div style="width: 64px; height: 64px; background: #fff1f2; color: #f43f5e; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 28px; margin: 0 auto 20px; border: 4px solid #ffe4e6; box-shadow: 0 4px 14px rgba(244, 63, 94, 0.15); animation: pulseWarning 2s infinite;">
            <i class="fa-solid fa-triangle-exclamation"></i>
        </div>
        
        <h3 style="margin:0 0 10px;font-size:18px;font-weight:800;color:#0f172a;font-family:'Poppins',sans-serif;">Batalkan Pesanan?</h3>
        
        <p style="margin:0 0 24px;font-size:14px;color:#475569;font-family:'Poppins',sans-serif;line-height:1.6;">
            Apakah Anda yakin ingin membatalkan pesanan dari <strong id="batalModalNamaPembeli" style="color: #0f172a;"></strong>?<br>
            <span style="font-size: 12px; color: #64748b;">Tindakan ini tidak dapat dibatalkan.</span>
        </p>

        <div style="display:flex;gap:12px;">
            <button onclick="tutupModalBatal()" style="flex:1;padding:12px;font-weight:700;font-size:14px;color:#475569;background:#f1f5f9;border:1px solid #e2e8f0;border-radius:12px;cursor:pointer;font-family:'Poppins',sans-serif;transition:all 0.2s;" onmouseover="this.style.background='#e2e8f0'" onmouseout="this.style.background='#f1f5f9'">
                Kembali
            </button>
            <button id="btnConfirmBatalSubmit" onclick="prosesBatalPesanan()" style="flex:1.2;padding:12px;font-weight:800;font-size:14px;color:#fff;background:linear-gradient(135deg, #f43f5e, #e11d48);border:none;border-radius:12px;cursor:pointer;font-family:'Poppins',sans-serif;display:inline-flex;align-items:center;justify-content:center;gap:6px;box-shadow:0 4px 12px rgba(225,29,72,0.25);transition:all 0.2s;" onmouseover="this.style.transform='translateY(-1px)'; this.style.boxShadow='0 6px 16px rgba(225,29,72,0.35)';" onmouseout="this.style.transform='none'; this.style.boxShadow='0 4px 12px rgba(225,29,72,0.25)';">
                <i class="fa-solid fa-ban"></i> Ya, Batalkan
            </button>
        </div>
    </div>
</div>

<style>
@keyframes pulseWarning {
    0% { transform: scale(1); box-shadow: 0 4px 14px rgba(244, 63, 94, 0.15); }
    50% { transform: scale(1.05); box-shadow: 0 4px 20px rgba(244, 63, 94, 0.3); }
    100% { transform: scale(1); box-shadow: 0 4px 14px rgba(244, 63, 94, 0.15); }
}
#modalBatalPesananPenjual.show {
    background: rgba(15, 23, 42, 0.6) !important;
    backdrop-filter: blur(8px) !important;
    -webkit-backdrop-filter: blur(8px) !important;
}
#modalBatalPesananPenjual.show #batalModalBox {
    transform: scale(1) !important;
    opacity: 1 !important;
}
</style>

<!-- Fullscreen Image Viewer (shared: inbox + chat) -->
<div id="fullscreenBuktiViewer" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.92);z-index:99999999;flex-direction:column;align-items:center;justify-content:center;padding:0;box-sizing:border-box;">
    <button onclick="tutupFullscreenBukti()" style="position:absolute;top:16px;left:16px;background:rgba(255,255,255,0.15);backdrop-filter:blur(8px);border:1px solid rgba(255,255,255,0.2);color:#ffffff;font-size:13px;font-weight:700;padding:8px 16px;border-radius:12px;cursor:pointer;display:inline-flex;align-items:center;gap:6px;font-family:'Poppins',sans-serif;z-index:10;transition:background 0.2s;">
        <i class="fa-solid fa-arrow-left" style="font-size:12px;"></i> Kembali
    </button>
    <img id="fullscreenBuktiImg" src="" alt="Bukti Transfer Fullscreen" style="max-width:95%;max-height:90vh;object-fit:contain;border-radius:8px;">
</div>

<script>
let _buktiActivePesananId = 0;

function lihatBuktiQris(fileName, idPesanan) {
    _buktiActivePesananId = idPesanan;
    const baseUrl = (typeof BASE_URL_CHAT !== 'undefined') ? BASE_URL_CHAT : '../../';
    const img = document.getElementById('buktiModalImg');
    
    img.src = baseUrl + 'assets/img/bukti_bayar/' + fileName;

    document.getElementById('buktiModalIdPesanan').textContent = idPesanan;

    // Reset konfirmasi button
    const btn = document.getElementById('btnKonfirmasiBayarQris');
    btn.disabled = false;
    btn.innerHTML = '<i class="fa-solid fa-check-double"></i> Konfirmasi Lunas';

    const modal = document.getElementById('modalBuktiQrisPenjual');
    modal.style.display = 'flex';
}

function tutupModalBuktiQris() {
    document.getElementById('modalBuktiQrisPenjual').style.display = 'none';
}

function bukaFullscreenBukti() {
    const src = document.getElementById('buktiModalImg').src;
    document.getElementById('fullscreenBuktiImg').src = src;
    document.getElementById('fullscreenBuktiViewer').style.display = 'flex';
}

function tutupFullscreenBukti() {
    document.getElementById('fullscreenBuktiViewer').style.display = 'none';
}

function konfirmasiPembayaranQris() {
    if (!_buktiActivePesananId) return;
    const btn = document.getElementById('btnKonfirmasiBayarQris');
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Memproses...';

    const fd = new FormData();
    fd.append('action', 'konfirmasi_pembayaran_qris');
    fd.append('id_pesanan', _buktiActivePesananId);
    fd.append('ajax', '1');

    const prosesUrl = (window.INBOX_RT_CONFIG && window.INBOX_RT_CONFIG.prosesUrl)
        ? window.INBOX_RT_CONFIG.prosesUrl
        : '../actions/proses_inbox.php';

    fetch(prosesUrl, { method: 'POST', body: fd })
        .then(r => r.json())
        .then(res => {
            tutupModalBuktiQris();
            if (res.success) {
                if (typeof muatInbox === 'function') muatInbox();
                else if (typeof reloadInboxFragment === 'function') reloadInboxFragment();
                else location.reload();
            } else {
                alert(res.message || 'Konfirmasi gagal. Coba lagi.');
                btn.disabled = false;
                btn.innerHTML = '<i class="fa-solid fa-check-double"></i> Konfirmasi Lunas';
            }
        })
        .catch(err => {
            console.error(err);
            tutupModalBuktiQris();
            alert('Koneksi gagal!');
        });
}

function konfirmasiPembayaranTunai(idPesanan) {
    if (!idPesanan) return;
    if (!confirm('Apakah Anda yakin sudah menerima pembayaran tunai untuk pesanan #' + idPesanan + '?')) {
        return;
    }

    const fd = new FormData();
    fd.append('action', 'konfirmasi_pembayaran_tunai');
    fd.append('id_pesanan', idPesanan);
    fd.append('ajax', '1');

    const prosesUrl = (window.INBOX_RT_CONFIG && window.INBOX_RT_CONFIG.prosesUrl)
        ? window.INBOX_RT_CONFIG.prosesUrl
        : '../actions/proses_inbox.php';

    fetch(prosesUrl, { method: 'POST', body: fd })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                if (typeof muatInbox === 'function') muatInbox();
                else if (typeof reloadInboxFragment === 'function') reloadInboxFragment();
                else location.reload();
            } else {
                alert(res.message || 'Konfirmasi gagal. Coba lagi.');
            }
        })
        .catch(err => {
            console.error(err);
            alert('Koneksi gagal!');
        });
}

let _batalActivePesananId = null;

function bukaModalBatal(idPesanan, namaPembeli) {
    _batalActivePesananId = idPesanan;
    document.getElementById('batalModalNamaPembeli').textContent = namaPembeli || 'Pembeli';
    const modal = document.getElementById('modalBatalPesananPenjual');
    modal.style.display = 'flex';
    // Small timeout to allow transition to trigger
    setTimeout(() => {
        modal.classList.add('show');
    }, 10);
}

function tutupModalBatal() {
    const modal = document.getElementById('modalBatalPesananPenjual');
    modal.classList.remove('show');
    setTimeout(() => {
        modal.style.display = 'none';
        _batalActivePesananId = null;
    }, 250);
}

function prosesBatalPesanan() {
    if (!_batalActivePesananId) return;
    const btn = document.getElementById('btnConfirmBatalSubmit');
    btn.disabled = true;
    const oldContent = btn.innerHTML;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Memproses...';

    const fd = new FormData();
    fd.append('action', 'update_status');
    fd.append('id_pesanan', _batalActivePesananId);
    fd.append('status_baru', 'dibatalkan');
    fd.append('ajax', '1');

    const prosesUrl = (window.INBOX_RT_CONFIG && window.INBOX_RT_CONFIG.prosesUrl)
        ? window.INBOX_RT_CONFIG.prosesUrl
        : '../actions/proses_inbox.php';

    fetch(prosesUrl, { method: 'POST', body: fd })
        .then(r => r.json())
        .then(res => {
            tutupModalBatal();
            if (res.success) {
                localStorage.removeItem('printed_order_' + _batalActivePesananId);
                if (typeof muatInbox === 'function') muatInbox();
                else if (typeof reloadInboxFragment === 'function') reloadInboxFragment();
                else location.reload();
            } else {
                alert(res.message || 'Pembatalan gagal. Coba lagi.');
                btn.disabled = false;
                btn.innerHTML = oldContent;
            }
        })
        .catch(err => {
            console.error(err);
            tutupModalBatal();
            alert('Koneksi gagal!');
            btn.disabled = false;
            btn.innerHTML = oldContent;
        });
}

// Tutup modal jika klik backdrop
document.getElementById('modalBuktiQrisPenjual').addEventListener('click', function(e) {
    if (e.target === this) tutupModalBuktiQris();
});

// Tutup fullscreen viewer jika klik area gelap (bukan gambar)
document.getElementById('fullscreenBuktiViewer').addEventListener('click', function(e) {
    if (e.target === this) tutupFullscreenBukti();
});

// Tutup modal batal jika klik backdrop
document.getElementById('modalBatalPesananPenjual').addEventListener('click', function(e) {
    if (e.target === this) tutupModalBatal();
});
</script>
