<?php
/** @var array $daftarPesanan */
/** @var array $jumlahPerStatus */
/** @var array $profilPenjual */

require_once __DIR__ . '/../../../../config/toko_foto.php';

$filterStatus = $_GET['status_filter'] ?? 'semua';
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
?>

<!-- ══ MODAL NOTA ══ -->
<div id="notaModal" class="nota-overlay" onclick="tutupNota(event)">
    <div class="nota-box" id="notaBox">

        <!-- Konten yang dicetak -->
        <div id="notaKonten">
            <div class="nota-header">
                <div class="nota-logo" id="notaLogo"></div>
                <div class="nota-toko-nama" id="notaTokoNama"></div>
                <div class="nota-sub">E-Kantin SMKN 1</div>
            </div>
            <div class="nota-garis"></div>
            <div class="nota-info">
                <div class="nota-info-row">
                    <span>No. Pesanan</span>
                    <span id="notaId"></span>
                </div>
                <div class="nota-info-row">
                    <span>Pembeli</span>
                    <span id="notaPembeli"></span>
                </div>
                <div class="nota-info-row">
                    <span>Kelas</span>
                    <span id="notaKelas"></span>
                </div>
                <div class="nota-info-row">
                    <span>Waktu</span>
                    <span id="notaWaktu"></span>
                </div>
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
            <div class="nota-total-row">
                <span>TOTAL</span>
                <span id="notaTotal"></span>
            </div>
            <div class="nota-garis"></div>
            <div class="nota-footer">Terima kasih!</div>
        </div>

        <!-- Tombol aksi (tidak ikut tercetak) -->
        <div class="nota-actions no-print">
            <button class="pcard-btn pcard-btn-batal" onclick="tutupNota()">
                <i class="fa-solid fa-xmark"></i> Tutup
            </button>
            <button class="pcard-btn pcard-btn-print" onclick="cetakNota()">
                <i class="fa-solid fa-print"></i> Cetak
            </button>
        </div>
    </div>
</div>

<!-- ══ TAB FILTER ══ -->
<div class="inbox-tabs" id="inboxTabs">
    <?php foreach ($tabs as $key => $tab):
        $count    = $key === 'semua' ? array_sum($jumlahPerStatus) : ($jumlahPerStatus[$key] ?? 0);
        $isActive = $filterStatus === $key ? 'active' : '';
    ?>
        <button class="inbox-tab <?= $isActive ?>" onclick="filterInbox('<?= $key ?>')">
            <i class="fa-solid <?= $tab['icon'] ?>"></i>
            <span class="tab-label"><?= $tab['label'] ?></span>
            <?php if ($count > 0): ?>
                <span class="tab-count"><?= $count ?></span>
            <?php endif; ?>
        </button>
    <?php endforeach; ?>
</div>

<!-- ══ DAFTAR PESANAN ══ -->
<div class="inbox-list">
    <?php if (empty($daftarPesanan)): ?>
        <div class="inbox-empty">
            <div class="inbox-empty-icon"><i class="fa-solid fa-inbox"></i></div>
            <p class="inbox-empty-title">Tidak ada pesanan</p>
            <p class="inbox-empty-sub"><?= $filterStatus !== 'semua' ? 'Tidak ada pesanan dengan status ini' : 'Pesanan yang masuk akan muncul di sini' ?></p>
        </div>
    <?php else: ?>
        <?php foreach ($daftarPesanan as $ps):
            $statusMap = [
                'menunggu'     => ['class' => 'status-menunggu', 'label' => 'Menunggu',     'icon' => 'fa-clock',          'bar' => 'bar-menunggu'],
                'dikonfirmasi' => ['class' => 'status-proses',   'label' => 'Diproses',     'icon' => 'fa-fire-burner',    'bar' => 'bar-proses'],
                'siap_diambil' => ['class' => 'status-siap',     'label' => 'Siap Diambil', 'icon' => 'fa-bell-concierge', 'bar' => 'bar-siap'],
                'selesai'      => ['class' => 'status-selesai',  'label' => 'Selesai',      'icon' => 'fa-circle-check',   'bar' => 'bar-selesai'],
                'dibatalkan'   => ['class' => 'status-batal',    'label' => 'Dibatalkan',   'icon' => 'fa-circle-xmark',   'bar' => 'bar-batal'],
            ];
            $st = $statusMap[$ps['status']] ?? ['class' => 'status-menunggu', 'label' => ucfirst($ps['status']), 'icon' => 'fa-clock', 'bar' => 'bar-menunggu'];

            /* Siapkan data nota sebagai JSON untuk JS */
            $notaItems = [];
            foreach ($ps['items'] as $item) {
                $notaItems[] = [
                    'nama'   => $item['nama_menu'],
                    'jumlah' => $item['jumlah'],
                    'harga'  => $item['harga_satuan'] * $item['jumlah'],
                ];
            }

            $notaData = json_encode([
                'id'      => $ps['id_pesanan'],
                'pembeli' => $ps['nama_pembeli'],
                'kelas'   => $ps['kelas_pembeli'] !== '-' ? $ps['kelas_pembeli'] : '-',
                'waktu'   => date('d/m/Y H:i', strtotime($ps['waktu_pesan'])),
                'total'   => $ps['total_harga'],
                'items'   => $notaItems,
                'toko'    => $profilPenjual['nama_toko'] ?? 'Kantin',
                'foto'    => $fotoTokoNota,
            ]);
        ?>
        <div class="pcard <?= $st['bar'] ?>">
            <div class="pcard-inner">

                <!-- Header -->
                <div class="pcard-header">
                    <div class="pcard-buyer">
                        <div class="pcard-avatar"><?= strtoupper(substr($ps['nama_pembeli'], 0, 1)) ?></div>
                        <div>
                            <div class="pcard-nama"><?= htmlspecialchars($ps['nama_pembeli']) ?></div>
                            <div class="pcard-meta">
                                <i class="fa-solid fa-clock"></i>
                                <?= date('H:i', strtotime($ps['waktu_pesan'])) ?>
                                <?php if ($ps['kelas_pembeli'] !== '-'): ?>
                                    <span class="pcard-dot">·</span>
                                    <i class="fa-solid fa-school"></i>
                                    <?= htmlspecialchars($ps['kelas_pembeli']) ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <span class="pcard-badge <?= $st['class'] ?>">
                        <i class="fa-solid <?= $st['icon'] ?>"></i>
                        <?= $st['label'] ?>
                    </span>
                </div>

                <div class="pcard-divider"></div>

                <!-- Items -->
                <div class="pcard-items">
                    <?php foreach ($ps['items'] as $item): ?>
                        <div class="pcard-item-row">
                            <span class="pcard-qty"><?= $item['jumlah'] ?>×</span>
                            <span class="pcard-item-name"><?= htmlspecialchars($item['nama_menu']) ?></span>
                            <span class="pcard-item-price">Rp <?= number_format($item['harga_satuan'] * $item['jumlah'], 0, ',', '.') ?></span>
                        </div>
                        <?php if (!empty($item['catatan'])): ?>
                            <div class="pcard-catatan">
                                <i class="fa-solid fa-comment-dots"></i>
                                <?= htmlspecialchars($item['catatan']) ?>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>

                <!-- Footer -->
                <div class="pcard-footer">
                    <div class="pcard-total">
                        <span class="pcard-total-label">Total</span>
                        <span class="pcard-total-value">Rp <?= number_format($ps['total_harga'], 0, ',', '.') ?></span>
                    </div>
                    <div class="pcard-actions">

                        <?php if ($ps['status'] === 'menunggu'): ?>
                            <form method="POST" style="display:inline">
                                <input type="hidden" name="_section"    value="inbox">
                                <input type="hidden" name="action"      value="update_status">
                                <input type="hidden" name="id_pesanan"  value="<?= $ps['id_pesanan'] ?>">
                                <input type="hidden" name="status_baru" value="dikonfirmasi">
                                <button type="submit" class="pcard-btn pcard-btn-proses">
                                    <i class="fa-solid fa-check"></i> Proses
                                </button>
                            </form>
                            <form method="POST" style="display:inline"
                                  onsubmit="return confirm('Batalkan pesanan ini?')">
                                <input type="hidden" name="_section"    value="inbox">
                                <input type="hidden" name="action"      value="update_status">
                                <input type="hidden" name="id_pesanan"  value="<?= $ps['id_pesanan'] ?>">
                                <input type="hidden" name="status_baru" value="dibatalkan">
                                <button type="submit" class="pcard-btn pcard-btn-batal">
                                    <i class="fa-solid fa-xmark"></i> Tolak
                                </button>
                            </form>

                        <?php elseif ($ps['status'] === 'dikonfirmasi'): ?>
                            <form method="POST" style="display:inline">
                                <input type="hidden" name="_section"    value="inbox">
                                <input type="hidden" name="action"      value="update_status">
                                <input type="hidden" name="id_pesanan"  value="<?= $ps['id_pesanan'] ?>">
                                <input type="hidden" name="status_baru" value="siap_diambil">
                                <button type="submit" class="pcard-btn pcard-btn-siap">
                                    <i class="fa-solid fa-bell-concierge"></i> Siap Diambil
                                </button>
                            </form>

                        <?php elseif ($ps['status'] === 'siap_diambil'): ?>
                            <!-- Tombol Cetak Nota -->
                            <button type="button"
                                    class="pcard-btn pcard-btn-print"
                                    onclick='bukaNotaModal(<?= $notaData ?>, <?= $ps['id_pesanan'] ?>)'>
                                <i class="fa-solid fa-receipt"></i> Cetak Nota
                            </button>
                            <!-- Tombol Selesai — muncul setelah cetak -->
                            <form method="POST" style="display:inline"
                                  id="formSelesai-<?= $ps['id_pesanan'] ?>">
                                <input type="hidden" name="_section"    value="inbox">
                                <input type="hidden" name="action"      value="update_status">
                                <input type="hidden" name="id_pesanan"  value="<?= $ps['id_pesanan'] ?>">
                                <input type="hidden" name="status_baru" value="selesai">
                                <button type="submit"
                                        class="pcard-btn pcard-btn-selesai pcard-btn-selesai-locked"
                                        id="btnSelesai-<?= $ps['id_pesanan'] ?>"
                                        disabled
                                        title="Cetak nota terlebih dahulu">
                                    <i class="fa-solid fa-circle-check"></i> Selesai
                                </button>
                            </form>
                        <?php endif; ?>

                    </div>
                </div>

            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<script>
/* ── Filter tab ── */
function filterInbox(status) {
    const url = new URL(window.location.href);
    url.searchParams.set('section', 'inbox');
    url.searchParams.set('status_filter', status);
    window.location.href = url.toString();
}

/* ── Modal Nota ── */
let _notaIdPesanan = null;

function bukaNotaModal(data, idPesanan) {
    _notaIdPesanan = idPesanan;

    document.getElementById('notaTokoNama').textContent = data.toko;
    const logoEl = document.getElementById('notaLogo');
    if (data.foto) {
        logoEl.innerHTML = `<img src="${data.foto}" style="width:90px;height:90px;object-fit:cover;border-radius:14px;" onerror="this.onerror=null; this.outerHTML='🏪';">`;
    } else {
        logoEl.innerHTML = '🏪';
    }
    document.getElementById('notaId').textContent       = '#' + data.id;
    document.getElementById('notaPembeli').textContent  = data.pembeli;
    document.getElementById('notaKelas').textContent    = data.kelas;
    document.getElementById('notaWaktu').textContent    = data.waktu;
    document.getElementById('notaTotal').textContent    = 'Rp ' + Number(data.total).toLocaleString('id-ID');

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

    /* Aktifkan tombol Selesai setelah cetak */
    if (_notaIdPesanan) {
        const btn = document.getElementById('btnSelesai-' + _notaIdPesanan);
        if (btn) {
            btn.disabled = false;
            btn.classList.remove('pcard-btn-selesai-locked');
            btn.title = '';
        }
    }

    /* Tutup modal setelah jeda singkat */
    setTimeout(() => {
        document.getElementById('notaModal').classList.remove('show');
        document.body.style.overflow = '';
    }, 400);
}
</script>