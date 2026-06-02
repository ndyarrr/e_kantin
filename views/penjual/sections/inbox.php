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

$is_php_s = ($_SERVER['SERVER_PORT'] == '8000' || strpos($_SERVER['HTTP_HOST'], ':') !== false);
$inbox_base = $is_php_s ? '' : '/e_kantin';
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
        logoEl.innerHTML = `<img src="${data.foto}" style="width:70px;height:70px;object-fit:cover;border-radius:50%;margin-bottom:8px;border:2px solid #ddd;" onerror="this.onerror=null; this.outerHTML='🏪';">`;
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
