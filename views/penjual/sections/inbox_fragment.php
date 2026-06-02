<?php
/** @var array $tabs */
/** @var string $filterStatus */
/** @var array $jumlahPerStatus */
/** @var array $daftarPesanan */
/** @var array $profilPenjual */
/** @var string $fotoTokoNota */
/** @var string $inboxSearch */
?>

<div class="inbox-tabs" id="inboxTabs">
    <?php foreach ($tabs as $key => $tab):
        $count = $key === 'semua' ? array_sum($jumlahPerStatus) : ($jumlahPerStatus[$key] ?? 0);
        $isActive = $filterStatus === $key ? 'active' : '';
    ?>
        <button type="button" class="inbox-tab <?= $isActive ?>" data-status="<?= htmlspecialchars($key) ?>">
            <i class="fa-solid <?= $tab['icon'] ?>"></i>
            <span class="tab-label"><?= $tab['label'] ?></span>
            <?php if ($count > 0): ?>
                <span class="tab-count"><?= $count ?></span>
            <?php endif; ?>
        </button>
    <?php endforeach; ?>
</div>

<div class="inbox-list" id="inboxList">
    <?php if (empty($daftarPesanan)): ?>
        <div class="inbox-empty">
            <div class="inbox-empty-icon"><i class="fa-solid fa-inbox"></i></div>
            <p class="inbox-empty-title">Tidak ada pesanan</p>
            <p class="inbox-empty-sub">
                <?php if ($inboxSearch !== ''): ?>
                    Tidak ada pesanan dengan nama pemesan "<strong><?= htmlspecialchars($inboxSearch) ?></strong>"
                <?php else: ?>
                    <?= $filterStatus !== 'semua' ? 'Tidak ada pesanan dengan status ini' : 'Pesanan yang masuk akan muncul di sini' ?>
                <?php endif; ?>
            </p>
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

            $notaItems = [];
            foreach ($ps['items'] as $item) {
                $notaItems[] = [
                    'nama'   => $item['nama_menu'],
                    'jumlah' => $item['jumlah'],
                    'harga'  => $item['harga_satuan'] * $item['jumlah'],
                ];
            }

            // Tentukan kasir & shift yang memproses pesanan ini secara dinamis
            if (($_SESSION['user_sub_role'] ?? '') === 'owner') {
                $kasirNama = '-';
                $kasirShift = '';
                $q_log = mysqli_query($conn, "
                    SELECT l.user_id, l.user_nama, tp.shift
                    FROM log_sistem l
                    LEFT JOIN toko_penjual tp ON tp.id_penjual = l.user_id AND tp.status = 'aktif'
                    WHERE l.keterangan LIKE '%pesanan #{$ps['id_pesanan']}%' 
                      AND l.user_role = 'penjual' 
                    ORDER BY l.dibuat_pada DESC 
                    LIMIT 1
                ");
                if ($q_log && $r_log = mysqli_fetch_assoc($q_log)) {
                    $kasirNama = $r_log['user_nama'];
                    $kasirShift = $r_log['shift'] ?? '';
                }
            } else {
                $kasirNama = $profilPenjual['nama'] ?? $_SESSION['user_nama'] ?? 'Kasir';
                $kasirShift = $profilPenjual['shift'] ?? 'Bebas';
            }

            // Prepare absolute path for photo
            $fotoAbsolute = '';
            if (!empty($profilPenjual['foto_toko'])) {
                $base_url = '';
                if (preg_match('#^(.*)/(views|auth|backend|controllers|config|assets|scratch)/#', $_SERVER['SCRIPT_NAME'] ?? '', $m)) {
                    $base_url = $m[1];
                } elseif (preg_match('#^(.*)/index\.php#', $_SERVER['SCRIPT_NAME'] ?? '', $m)) {
                    $base_url = $m[1];
                }
                $fotoAbsolute = $base_url . '/assets/img/kantin/' . $profilPenjual['foto_toko'];
            }

            // Ambil bukti_foto dari tabel pembayaran (sudah di-select di query utama)
            $bukti_foto_penjual = $ps['bukti_foto'] ?? '';

            $notaData = json_encode([
                'id'      => $ps['id_pesanan'],
                'pembeli' => $ps['nama_pembeli'],
                'kelas'   => $ps['kelas_pembeli'] !== '-' ? $ps['kelas_pembeli'] : '-',
                'waktu'   => date('d/m/Y H:i', strtotime($ps['waktu_pesan'])),
                'total'   => $ps['total_harga'],
                'items'   => $notaItems,
                'toko'    => $profilPenjual['nama_toko'] ?? 'Kantin',
                'foto'    => $fotoAbsolute,
                'kasir'   => $kasirNama,
                'shift'   => $kasirShift,
                'metode'  => $ps['metode_pembayaran'] === 'transfer' ? 'QRIS' : 'Tunai',
                'status_bayar' => $ps['status_pembayaran'] === 'lunas' ? 'Lunas' : 'Belum Bayar',
            ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
        ?>
        <div class="pcard <?= $st['bar'] ?>" data-id-pesanan="<?= (int) $ps['id_pesanan'] ?>">
            <div class="pcard-inner">
                <div class="pcard-header">
                    <div class="pcard-buyer">
                        <div class="pcard-avatar"><?= strtoupper(substr($ps['nama_pembeli'], 0, 1)) ?></div>
                        <div>
                            <div class="pcard-nama"><?= htmlspecialchars($ps['nama_pembeli']) ?></div>
                            <div class="pcard-meta">
                                <i class="fa-solid fa-clock"></i>
                                <?= date('d M Y, H:i', strtotime($ps['waktu_pesan'])) ?>
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

                <div class="pcard-footer">
                    <div class="pcard-total" style="display: flex; flex-direction: column; align-items: flex-start; gap: 4px;">
                        <div style="display: flex; justify-content: space-between; width: 100%; align-items: center;">
                            <span class="pcard-total-label">Total</span>
                            <span class="pcard-total-value">Rp <?= number_format($ps['total_harga'], 0, ',', '.') ?></span>
                        </div>
                        <div style="display: flex; align-items: center; gap: 6px; margin-top: 4px; font-size: 12px; font-family: 'Poppins', sans-serif; flex-wrap: wrap;">
                            <span style="color: #64748b; font-weight: 500;">Pembayaran:</span>
                            <span style="font-weight: 700; color: #1e293b; display: inline-flex; align-items: center; gap: 4px;">
                                <?php if ($ps['metode_pembayaran'] === 'transfer'): ?>
                                    <i class="fa-solid fa-qrcode" style="color: #0ea5e9; font-size: 11px;"></i> QRIS
                                <?php else: ?>
                                    <i class="fa-solid fa-money-bill-wave" style="color: #16a34a; font-size: 11px;"></i> Tunai
                                <?php endif; ?>
                            </span>
                            <?php if ($ps['status_pembayaran'] === 'lunas'): ?>
                                <span style="background: #dcfce7; color: #15803d; padding: 2px 8px; border-radius: 9999px; font-size: 10px; font-weight: 700; display: inline-flex; align-items: center; gap: 3px; border: 1px solid #bbf7d0;">
                                    <i class="fa-solid fa-circle-check" style="font-size: 9px;"></i> Lunas
                                </span>
                            <?php elseif (!empty($bukti_foto_penjual)): ?>
                                <span style="background: #fef9c3; color: #854d0e; padding: 2px 8px; border-radius: 9999px; font-size: 10px; font-weight: 700; display: inline-flex; align-items: center; gap: 3px; border: 1px solid #fde68a;">
                                    <i class="fa-solid fa-clock" style="font-size: 9px;"></i> Menunggu Verifikasi
                                </span>
                                <button type="button" onclick="lihatBuktiQris('<?= htmlspecialchars($bukti_foto_penjual, ENT_QUOTES) ?>', <?= (int)$ps['id_pesanan'] ?>)" style="background: #0284c7; color: #ffffff; border: none; border-radius: 9999px; font-size: 10px; font-weight: 700; padding: 2px 10px; cursor: pointer; display: inline-flex; align-items: center; gap: 3px; font-family: 'Poppins', sans-serif;">
                                    <i class="fa-solid fa-image" style="font-size: 9px;"></i> Lihat Bukti
                                </button>
                            <?php else: ?>
                                <span style="background: #fee2e2; color: #b91c1c; padding: 2px 8px; border-radius: 9999px; font-size: 10px; font-weight: 700; display: inline-flex; align-items: center; gap: 3px; border: 1px solid #fecaca;">
                                    <i class="fa-solid fa-circle-xmark" style="font-size: 9px;"></i> Belum Bayar
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="pcard-actions">
                        <?php if (($_SESSION['user_sub_role'] ?? '') === 'owner'): ?>
                            <!-- Owner only monitors but can view details -->
                            <button type="button" class="pcard-btn" 
                                style="background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; border-radius: 12px; padding: 8px 14px; font-size: 11.5px; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; transition: all 0.2s; font-family: 'Poppins', sans-serif;"
                                data-action="cetak_nota" data-nota='<?= $notaData ?>' data-id="<?= (int) $ps['id_pesanan'] ?>">
                                <i class="fa-solid fa-eye" style="color: #475569; font-size: 11px;"></i> Lihat Pesanan
                            </button>
                        <?php else: ?>
                            <?php if ($ps['metode_pembayaran'] === 'transfer' && $ps['status_pembayaran'] === 'belum_bayar' && $ps['status'] !== 'dibatalkan' && $ps['status'] !== 'selesai'): ?>
                                <?php if (!empty($bukti_foto_penjual)): ?>
                                    <button type="button" class="pcard-btn" 
                                        style="background: #16a34a; color: #ffffff; border: none; border-radius: 12px; padding: 8px 14px; font-size: 11.5px; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; transition: all 0.2s; box-shadow: 0 4px 12px rgba(22, 163, 74, 0.15);"
                                        onclick="lihatBuktiQris('<?= htmlspecialchars($bukti_foto_penjual, ENT_QUOTES) ?>', <?= (int)$ps['id_pesanan'] ?>)">
                                        <i class="fa-solid fa-check-double"></i> Konfirmasi QRIS
                                    </button>
                                <?php else: ?>
                                    <button type="button" class="pcard-btn" 
                                        style="background: #cbd5e1; color: #64748b; border: none; border-radius: 12px; padding: 8px 14px; font-size: 11.5px; font-weight: 700; cursor: not-allowed; display: inline-flex; align-items: center; gap: 6px;"
                                        disabled title="Pembeli belum mengunggah bukti transfer">
                                        <i class="fa-solid fa-ban"></i> Konfirmasi QRIS
                                    </button>
                                <?php endif; ?>
                            <?php endif; ?>

                            <?php if ($ps['metode_pembayaran'] === 'tunai' && $ps['status_pembayaran'] === 'belum_bayar' && $ps['status'] !== 'dibatalkan' && $ps['status'] !== 'selesai'): ?>
                                 <button type="button" class="pcard-btn" 
                                     style="background: #16a34a; color: #ffffff; border: none; border-radius: 12px; padding: 8px 14px; font-size: 11.5px; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; transition: all 0.2s; box-shadow: 0 4px 12px rgba(22, 163, 74, 0.15);"
                                     onclick="konfirmasiPembayaranTunai(<?= (int)$ps['id_pesanan'] ?>)">
                                     <i class="fa-solid fa-money-bill-wave"></i> Konfirmasi Tunai
                                 </button>
                             <?php endif; ?>

                            <?php if ($ps['status'] === 'menunggu'): ?>
                                <button type="button" class="pcard-btn pcard-btn-proses"
                                    data-action="update_status" data-id="<?= (int) $ps['id_pesanan'] ?>" data-status="dikonfirmasi">
                                    <i class="fa-solid fa-check"></i> Proses
                                </button>
                                <button type="button" class="pcard-btn pcard-btn-batal"
                                    data-action="update_status" data-id="<?= (int) $ps['id_pesanan'] ?>" data-status="dibatalkan" data-confirm="Batalkan pesanan ini?">
                                    <i class="fa-solid fa-xmark"></i> Tolak
                                </button>
                            <?php elseif ($ps['status'] === 'dikonfirmasi'): ?>
                                <button type="button" class="pcard-btn pcard-btn-siap"
                                    data-action="update_status" data-id="<?= (int) $ps['id_pesanan'] ?>" data-status="siap_diambil">
                                    <i class="fa-solid fa-bell-concierge"></i> Siap Diambil
                                </button>
                            <?php elseif ($ps['status'] === 'siap_diambil'): ?>
                                <button type="button" class="pcard-btn pcard-btn-print"
                                    data-action="cetak_nota" data-nota='<?= $notaData ?>' data-id="<?= (int) $ps['id_pesanan'] ?>">
                                    <i class="fa-solid fa-receipt"></i> Cetak Nota
                                </button>
                                <button type="button" class="pcard-btn pcard-btn-selesai pcard-btn-selesai-locked"
                                    id="btnSelesai-<?= (int) $ps['id_pesanan'] ?>"
                                    data-action="update_status" data-id="<?= (int) $ps['id_pesanan'] ?>" data-status="selesai"
                                    disabled title="Cetak nota terlebih dahulu">
                                    <i class="fa-solid fa-circle-check"></i> Selesai
                                </button>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
