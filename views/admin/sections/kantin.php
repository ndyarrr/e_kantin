<?php // sections/kantin.php
require_once __DIR__ . '/../../../config/toko_foto.php';
?>

<div class="stats-grid col3">
    <div class="stat-card">
        <div class="stat-label">Total Kantin</div>
        <div class="stat-row">
            <div class="stat-value">
                <span id="statTotalKantinVal"><?= $totalToko ?></span><span class="sub"> / <span id="statTotalKantinLimitVal"><?= $slotKantin ?></span></span>
            </div>
            <i class="fa-solid fa-store stat-icon"></i>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Kantin Buka Hari Ini</div>
        <div class="stat-row">
            <div class="stat-value">
                <?= $tokoAktif ?><span class="sub"> /
                    <?= $totalToko ?>
                </span>
            </div>
            <i class="fa-solid fa-circle-check stat-icon"></i>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Slot Stand Kantin</div>
        <div class="stat-row" style="justify-content: space-between; align-items: center;">
            <div class="stat-value" style="display: flex; align-items: center; gap: 12px; line-height: 1;">
                <span id="statSlotKantinVal"><?= $slotKantin ?></span>
                <?php if ($isAdminSuper): ?>
                    <button type="button" onclick="openModalEditSlot()" style="background: var(--green-pale); border: 1px solid var(--green-muted); color: var(--green-dark); width: 26px; height: 26px; border-radius: 6px; display: flex; align-items: center; justify-content: center; font-size: 12px; cursor: pointer; transition: 0.2s;" title="Edit Limit Slot">
                        <i class="fa-solid fa-pen-to-square"></i>
                    </button>
                <?php endif; ?>
            </div>
            <i class="fa-solid fa-cubes stat-icon"></i>
        </div>
    </div>
</div>

<div id="panelDaftarKantin" style="width: 100%;">
    <div class="table-card" style="width: 100%;">
        <div class="table-card-header">
            <h2>Daftar Slot Stand Kantin</h2>
        </div>
        <div class="table-scroll">
            <table>
                <thead>
                    <tr>
                        <th class="center">No</th>
                        <th>Nama Kantin / Stand</th>
                        <th class="col-hide">Deskripsi</th>
                        <th class="center">Penjual</th>
                        <th class="center">Menu</th>
                        <th>Status</th>
                        <th class="center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    foreach ($slotRowsPage as $slot):
                        $nomor = (int) $slot['nomor'];
                        $isKosong = empty($slot['id_toko']);
                        if ($isKosong):
                    ?>
                            <tr class="slot-row slot-row-kosong" data-nomor="<?= $nomor ?>">
                                <td class="center">
                                    <?php if ($isAdminSuper): ?>
                                        <div style="display:flex; flex-direction:column; align-items:center; gap:2px;">
                                            <button type="button" class="btn-aksi btn-swap-up" onclick="geserUrutanKantin(this, 'up')" style="padding: 2px 4px; font-size: 10px; color: var(--green); visibility: <?= ($nomor > 1) ? 'visible' : 'hidden' ?>;" title="Naikkan Posisi Slot">
                                                <i class="fa-solid fa-chevron-up"></i>
                                            </button>

                                            <span class="slot-number-span" style="font-weight:700; font-size:12px;"><?= $nomor ?></span>

                                            <button type="button" class="btn-aksi btn-swap-down" onclick="geserUrutanKantin(this, 'down')" style="padding: 2px 4px; font-size: 10px; color: var(--green); visibility: <?= ($nomor < $slotKantin) ? 'visible' : 'hidden' ?>;" title="Turunkan Posisi Slot">
                                                <i class="fa-solid fa-chevron-down"></i>
                                            </button>
                                        </div>
                                    <?php else: ?>
                                        <span class="slot-number-span" style="font-weight:700;"><?= $nomor ?></span>
                                    <?php endif; ?>
                                </td>
                                <td colspan="5">
                                    <div class="slot-kosong-cell">
                                        <div class="slot-kosong-icon"><i class="fa-solid fa-cube"></i></div>
                                        <div>
                                            <span class="slot-kosong-label">Slot Kosong</span>
                                            <span class="slot-kosong-hint">Posisi stand ini belum terisi kantin</span>
                                        </div>
                                    </div>
                                </td>
                                <td class="center">
                                    <a href="?section=tambah_akun&slot_nomor=<?= $nomor ?>" class="btn-aksi" title="Isi Slot" style="text-decoration:none;">
                                        <i class="fa-solid fa-plus"></i>
                                    </a>
                                </td>
                            </tr>
                    <?php
                        else:
                            $t = $slot;
                            $ownerNama = $t['nama_owner'] ?? 'Belum ada owner';
                    ?>
                            <tr class="toko-row <?= $selectedToko == $t['id_toko'] ? 'toko-row-active' : '' ?>"
                                data-nomor="<?= $nomor ?>" data-toko-id="<?= $t['id_toko'] ?>"
                                onclick="selectToko(<?= $t['id_toko'] ?>)">
                                <td class="center">
                                    <?php if ($isAdminSuper): ?>
                                        <div style="display:flex; flex-direction:column; align-items:center; gap:2px;" onclick="event.stopPropagation();">
                                            <button type="button" class="btn-aksi btn-swap-up" onclick="geserUrutanKantin(this, 'up')" style="padding: 2px 4px; font-size: 10px; color: var(--green); visibility: <?= ($nomor > 1) ? 'visible' : 'hidden' ?>;" title="Naikkan Posisi Slot">
                                                <i class="fa-solid fa-chevron-up"></i>
                                            </button>

                                            <span class="slot-number-span" style="font-weight:700; font-size:12px;"><?= $nomor ?></span>

                                            <button type="button" class="btn-aksi btn-swap-down" onclick="geserUrutanKantin(this, 'down')" style="padding: 2px 4px; font-size: 10px; color: var(--green); visibility: <?= ($nomor < $slotKantin) ? 'visible' : 'hidden' ?>;" title="Turunkan Posisi Slot">
                                                <i class="fa-solid fa-chevron-down"></i>
                                            </button>
                                        </div>
                                    <?php else: ?>
                                        <span class="slot-number-span" style="font-weight:700;"><?= $nomor ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="toko-name-cell">
                                        <?php if (!empty($t['foto_toko'])): ?>
                                            <img src="<?= htmlspecialchars(tokoFotoUrl($t['foto_toko'], '../../')) ?>"
                                                class="toko-thumb" onclick="event.stopPropagation();bukaFotoKantin(this.src)"
                                                style="cursor:zoom-in">
                                        <?php else: ?>
                                            <div class="toko-thumb-placeholder">
                                                <i class="fa-solid fa-store"></i>
                                            </div>
                                        <?php endif; ?>
                                        <div>
                                            <span style="font-weight:600; display:block;">
                                                <?= htmlspecialchars($t['nama_toko']) ?>
                                            </span>
                                            <span style="font-size:11px; color:var(--text-light); font-style:italic;">
                                                <i class="fa-solid fa-user-tie"
                                                    style="font-size:9px; margin-right:3px;"></i>Owner:
                                                <?= htmlspecialchars($ownerNama) ?>
                                            </span>
                                        </div>
                                    </div>
                                </td>
                                <td class="col-hide toko-desc">
                                    <?= htmlspecialchars($t['deskripsi'] ?? '-') ?>
                                </td>
                                <td class="center" style="font-weight:600">
                                    <?= $t['total_penjual'] ?>
                                </td>
                                <td class="center" style="font-weight:600">
                                    <?= $t['total_menu'] ?>
                                </td>
                                <td>
                                    <span class="badge <?= $t['status'] === 'buka' ? 'badge-aktif' : 'badge-nonaktif' ?>">
                                        <i
                                            class="fa-solid <?= $t['status'] === 'buka' ? 'fa-circle-check' : 'fa-circle-xmark' ?>"></i>
                                        <?= ucfirst($t['status']) ?>
                                    </span>
                                </td>
                                <td class="center" style="white-space:nowrap">
                                    <form method="POST" action="?section=kantin<?= $slotPageQuery ?>" style="display:inline"
                                        id="form-hapus-<?= $t['id_toko'] ?>">
                                        <input type="hidden" name="action" value="kantin_hapus">
                                        <input type="hidden" name="id_toko" value="<?= $t['id_toko'] ?>">
                                        <input type="hidden" name="_section" value="kantin">
                                        <input type="hidden" name="slot_page" value="<?= $slotPage ?>">
                                        <button type="button" class="btn-aksi danger" title="Hapus (slot tetap kosong)"
                                            onclick="event.stopPropagation(); if(confirm('Hapus kantin <?= htmlspecialchars($t['nama_toko']) ?>? Slot stand akan tetap ada dan kosong.')) document.getElementById('form-hapus-<?= $t['id_toko'] ?>').submit()">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                    <?php endif; endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if ($showSlotPagination): ?>
            <div class="slot-pagination">
                <?php for ($i = 1; $i <= $slotTotalPages; $i++):
                    $pageHref = '?section=kantin&slot_page=' . $i . ($selectedToko ? '&toko=' . (int) $selectedToko : '');
                ?>
                    <a href="<?= $pageHref ?>" class="slot-page-btn <?= $slotPage === $i ? 'active' : '' ?>">
                        <?= $i ?>
                    </a>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php if ($selectedToko && $detailToko): ?>
    <div class="detail-toko-section" id="detailTokoSection" style="margin-top: 25px;">

        <div class="detail-toko-header">
            <div class="detail-toko-header-info">
                <div class="detail-toko-avatar">
                    <?php if (!empty($detailToko['foto_toko'])): ?>
                        <img src="<?= htmlspecialchars(tokoFotoUrl($detailToko['foto_toko'], '../../')) ?>?v=<?= time() ?>">
                    <?php else: ?>
                        <i class="fa-solid fa-store"></i>
                    <?php endif; ?>
                </div>
                <div>
                    <div class="detail-toko-nama">
                        <?= htmlspecialchars($detailToko['nama_toko']) ?>
                    </div>
                    <div class="detail-toko-desk">Status: <strong style="color:var(--green)">
                            <?= ucfirst($detailToko['status']) ?>
                        </strong></div>
                </div>
            </div>
            <button onclick="tutupDetailToko()" class="btn-aksi toggle-off" title="Tutup">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <div class="detail-toko-grid">

            <div class="form-card">
                <h2><i class="fa-solid fa-circle-info" style="color:var(--green);margin-right:8px"></i>Profil Kantin
                </h2>
                <form method="POST" enctype="multipart/form-data" action="?section=kantin&toko=<?= (int) $selectedToko ?><?= $slotPageQuery ?>"
                    style="display:flex; flex-direction:column; gap:14px; margin-top:12px;">
                    <input type="hidden" name="action" value="kantin_edit">
                    <input type="hidden" name="_section" value="kantin">
                    <input type="hidden" name="_selected_toko" value="<?= (int) $selectedToko ?>">
                    <input type="hidden" name="id_toko" value="<?= (int) $selectedToko ?>">
                    <input type="hidden" name="slot_page" value="<?= $slotPage ?>">

                    <div class="profile-preview-wrapper">
                        <div class="profile-avatar-circle" id="editKantinAvatarCircle">
                            <?php if (!empty($detailToko['foto_toko'])): ?>
                                <img id="editKantinAvatarImg" src="<?= htmlspecialchars(tokoFotoUrl($detailToko['foto_toko'], '../../')) ?>?v=<?= time() ?>"
                                    alt="Foto kantin">
                            <?php else: ?>
                                <div class="profile-avatar-placeholder" id="editKantinAvatarPlaceholder"><i class="fa-solid fa-image"></i></div>
                                <img id="editKantinAvatarImg" src="" alt="Foto kantin" style="display:none; width:100%; height:100%; object-fit:cover; border-radius:inherit;">
                            <?php endif; ?>
                        </div>
                        <div>
                            <label style="font-weight: bold; font-size: 14px; display: block; margin-bottom: 5px;">Foto Profil Kantin</label>
                            <input type="file" name="foto_toko" id="inputFotoEditKantin" accept="image/jpeg, image/jpg, image/png, image/webp"
                                style="font-size: 13px; display:block; width:100%;"
                                onchange="previewFotoKantinEdit(this)">
                            <small style="color: #666; display: block; margin-top: 3px;">Format: JPG, JPEG, PNG, WEBP (Max 2MB)</small>
                            <?php if (!empty($detailToko['foto_toko'])): ?>
                                <label style="display:flex; align-items:center; gap:6px; margin-top:8px; font-size:12px; cursor:pointer;">
                                    <input type="checkbox" name="hapus_foto" value="1" id="hapusFotoCheck"
                                        onchange="toggleHapusFoto(this)"> Hapus foto saat ini
                                </label>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Nama Stand</label>
                        <input type="text" name="nama_toko" value="<?= htmlspecialchars($detailToko['nama_toko']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Deskripsi</label>
                        <input type="text" name="deskripsi" value="<?= htmlspecialchars($detailToko['deskripsi'] ?? '') ?>">
                    </div>
                    <div>
                        <span style="font-size:11px; color:var(--text-light); display:block;">TANGGAL BERDIRI</span>
                        <span style="font-size:14px;">
                            <?= date('d F Y', strtotime($detailToko['dibuat_pada'])) ?>
                        </span>
                    </div>
                    <button type="submit" class="btn-submit">
                        <i class="fa-solid fa-floppy-disk" style="margin-right:6px"></i>Simpan Perubahan
                    </button>
                </form>
            </div>

            <div class="form-card">
                <h2><i class="fa-solid fa-user-tie" style="color:var(--green);margin-right:8px"></i>Pengelola Assigned</h2>

                <div style="display:flex; flex-direction:column; gap:15px; margin-top:10px;">
                    <div style="padding:12px; border:1px solid #eee; border-radius:6px; background:#fafafa;">
                        <span
                            style="font-size:10px; font-weight:600; color:var(--text-light); display:block; margin-bottom:5px;">OWNER
                            (PEMILIK KANTIN)</span>
                        <div style="display:flex; align-items:center; gap:8px;">
                            <i class="fa-solid fa-user-shield" style="font-size:16px; color:#ff9800;"></i>
                            <strong style="font-size:14px;">
                                <?= htmlspecialchars($detailToko['nama_owner'] ?? 'Belum ada Owner assigned') ?>
                            </strong>
                        </div>
                    </div>

                    <div>
                        <span
                            style="font-size:10px; font-weight:600; color:var(--text-light); display:block; margin-bottom:8px;">STAF
                            PENJUAL / KASIR</span>
                        <?php if (empty($penjualToko)): ?>
                            <div style="font-size:12px; color:var(--text-light); font-style:italic;">Belum ada staf terdaftar di
                                stand ini.</div>
                        <?php else:
                            foreach ($penjualToko as $p): ?>
                                <div class="penjual-item"
                                    style="padding:8px 10px; margin-bottom:8px; border:1px solid #f1f1f1; border-radius:4px;">
                                    <div>
                                        <div class="penjual-nama" style="font-weight:600; font-size:13px;">
                                            <?= htmlspecialchars($p['nama']) ?>
                                        </div>
                                        <div class="penjual-shift" style="font-size:11px; color:var(--text-light);">
                                            <?= $p['shift'] ? 'Shift: ' . ucfirst($p['shift']) : 'Shift Bebas' ?>
                                        </div>
                                    </div>
                                    <span class="badge badge-aktif" style="font-size:10px; padding:2px 6px;">Aktif</span>
                                </div>
                            <?php endforeach;
                        endif; ?>
                    </div>
                </div>
            </div>

        </div>

        <div class="table-card" style="margin-top:16px">
            <div class="table-card-header" id="menuCardHeader">
                <h2>Menu Stand Kantin</h2>
                <span style="font-size:11px; color:var(--text-light);">* Pengelolaan menu dikontrol oleh Owner Kantin</span>
            </div>

            <div class="table-scroll">
                <table>
                    <thead>
                        <tr>
                            <th>Nama Menu</th>
                            <th>Kategori</th>
                            <th class="col-hide">Deskripsi</th>
                            <th>Harga</th>
                            <th class="center">Stok</th>
                            <th class="center">Tersedia</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($menuToko)): ?>
                            <tr class="empty-row">
                                <td colspan="6">Belum ada menu di stand ini</td>
                            </tr>
                        <?php else:
                            foreach ($menuToko as $m):
                                $kat = $m['kategori'] ?? 'makanan';
                                $iconKat = match ($kat) {
                                    'minuman' => 'fa-glass-water',
                                    'snack' => 'fa-cookie',
                                    default => 'fa-utensils'
                                };
                                ?>
                                <tr>
                                    <td>
                                        <div class="menu-name-cell">
                                            <?php if (!empty($m['foto_menu'])): ?>
                                                <img src="../../assets/img/menu/<?= htmlspecialchars($m['foto_menu']) ?>"
                                                    class="menu-thumb">
                                            <?php else: ?>
                                                <div class="menu-thumb-placeholder">
                                                    <i class="fa-solid fa-burger"></i>
                                                </div>
                                            <?php endif; ?>
                                            <?= htmlspecialchars($m['nama_menu']) ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge"
                                            style="background-color: #f3f4f6; color: #374151; font-size: 11px; padding: 4px 8px; border-radius: 4px; display: inline-flex; align-items: center; gap: 5px;">
                                            <i class="fa-solid <?= $iconKat ?>" style="color: var(--green); font-size: 10px;"></i>
                                            <?= ucfirst($kat) ?>
                                        </span>
                                    </td>
                                    <td class="col-hide toko-desc">
                                        <?= htmlspecialchars($m['deskripsi'] ?? '-') ?>
                                    </td>
                                    <td style="font-weight:600;">
                                        <?php if (isset($m['is_fleksibel']) && $m['is_fleksibel'] == 1): ?>
                                            <span style="background: rgba(14, 165, 233, 0.1); color: #0ea5e9; padding: 4px 8px; border-radius: 6px; font-weight: 750; font-size: 11px; display: inline-flex; align-items: center; gap: 4px;">
                                                <i class="fa-solid fa-arrows-left-right-to-line"></i> Harga Fleksibel
                                            </span>
                                        <?php else: ?>
                                            Rp <?= number_format($m['harga'], 0, ',', '.') ?>
                                        <?php endif; ?>
                                    </td>
                                    <td class="center">
                                        <?= $m['stok'] ?>
                                    </td>
                                    <td class="center">
                                        <span class="badge <?= $m['tersedia'] ? 'badge-aktif' : 'badge-nonaktif' ?>">
                                            <?= $m['tersedia'] ? 'Ya' : 'Tidak' ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
<?php endif; ?>

<div id="modalFotoKantin" onclick="tutupFotoKantin()"
    style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.8);z-index:9999;align-items:center;justify-content:center;cursor:zoom-out">
    <img id="modalFotoImg" src="" style="max-width:90vw;max-height:90vh;border-radius:12px;object-fit:contain">
</div>

<!-- Toast Notification Container -->
<div id="toastContainerAdmin"></div>

<!-- Modal Edit Slot Kantin -->
<div id="modalEditSlot">
    <div class="modal-backdrop" onclick="closeModalEditSlot()"></div>
    <div class="modal-box">
        <div class="modal-header">
            <h2>Kelola Slot Stand Kantin</h2>
            <button type="button" class="modal-close" onclick="closeModalEditSlot()"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form id="formEditSlot" onsubmit="event.preventDefault(); simpanSlotKantin();" style="display:flex; flex-direction:column; gap:16px;">
            <div class="form-group" style="margin-bottom:0;">
                <label for="inputSlotKantin">Limit Jumlah Slot Stand</label>
                <div style="display:flex; align-items:center; gap:8px;">
                    <button type="button" onclick="adjustSlotInput(-1)" style="padding:10px 14px; background:#f3f4f6; border:1px solid var(--border); border-radius:8px; cursor:pointer; font-weight:700;"><i class="fa-solid fa-minus"></i></button>
                    <input type="number" id="inputSlotKantin" name="nilai" value="<?= $slotKantin ?>" min="1" required style="text-align:center; font-size:16px; font-weight:700; flex-grow:1;">
                    <button type="button" onclick="adjustSlotInput(1)" style="padding:10px 14px; background:#f3f4f6; border:1px solid var(--border); border-radius:8px; cursor:pointer; font-weight:700;"><i class="fa-solid fa-plus"></i></button>
                </div>
                <small class="form-note">Gagal jika limit slot dikurangi di bawah jumlah stand yang sedang aktif terisi.</small>
            </div>
            <button type="submit" class="modal-btn-submit">
                <i class="fa-solid fa-floppy-disk" style="margin-right:6px"></i>Simpan Perubahan
            </button>
        </form>
    </div>
</div>

<script>
    function bukaFotoKantin(src) {
        const modal = document.getElementById('modalFotoKantin');
        document.getElementById('modalFotoImg').src = src;
        modal.style.display = 'flex';
    }

    function tutupFotoKantin() {
        document.getElementById('modalFotoKantin').style.display = 'none';
    }

    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') {
            tutupFotoKantin();
            closeModalEditSlot();
        }
    });

    function selectToko(id) {
        sessionStorage.setItem('adminScrollPos', window.scrollY);
        const params = new URLSearchParams(window.location.search);
        let url = '?section=kantin&toko=' + id;
        const slotPage = params.get('slot_page');
        if (slotPage && parseInt(slotPage, 10) > 1) {
            url += '&slot_page=' + slotPage;
        }
        window.location.href = url;
    }

    function tutupDetailToko() {
        const params = new URLSearchParams(window.location.search);
        let url = '?section=kantin';
        const slotPage = params.get('slot_page');
        if (slotPage && parseInt(slotPage, 10) > 1) {
            url += '&slot_page=' + slotPage;
        }
        window.location.href = url;
    }

    // Live preview foto kantin saat edit
    function previewFotoKantinEdit(input) {
        const imgEl = document.getElementById('editKantinAvatarImg');
        const placeholder = document.getElementById('editKantinAvatarPlaceholder');
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                if (imgEl) {
                    imgEl.src = e.target.result;
                    imgEl.style.display = 'block';
                    imgEl.style.width = '100%';
                    imgEl.style.height = '100%';
                    imgEl.style.objectFit = 'cover';
                    imgEl.style.borderRadius = 'inherit';
                }
                if (placeholder) placeholder.style.display = 'none';
            };
            reader.readAsDataURL(input.files[0]);
        }
    }

    // Saat hapus_foto dicentang, disable file input
    function toggleHapusFoto(checkbox) {
        const fileInput = document.getElementById('inputFotoEditKantin');
        if (fileInput) {
            fileInput.disabled = checkbox.checked;
            fileInput.value = '';
            if (checkbox.checked) {
                const imgEl = document.getElementById('editKantinAvatarImg');
                if (imgEl) { imgEl.style.opacity = '0.3'; }
            } else {
                const imgEl = document.getElementById('editKantinAvatarImg');
                if (imgEl) { imgEl.style.opacity = '1'; }
            }
        }
    }

    // ── REAL-TIME AJAX SCRIPTS ──
    function showToastAdmin(title, message, type = 'success') {
        const container = document.getElementById('toastContainerAdmin');
        if (!container) return;

        const toast = document.createElement('div');
        toast.className = `admin-toast ${type}`;

        const icon = type === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation';

        toast.innerHTML = `
            <i class="fa-solid ${icon} admin-toast-icon"></i>
            <div class="admin-toast-body">
                <div class="admin-toast-title">${title}</div>
                <div class="admin-toast-message">${message}</div>
            </div>
            <button type="button" class="admin-toast-close" onclick="this.parentElement.remove()"><i class="fa-solid fa-xmark"></i></button>
        `;

        container.appendChild(toast);

        // Auto remove after 4 seconds
        setTimeout(() => {
            toast.style.animation = 'toastSlideOut 0.3s cubic-bezier(0.4, 0, 1, 1) forwards';
            toast.addEventListener('animationend', () => {
                toast.remove();
            });
        }, 4000);
    }

    function openModalEditSlot() {
        const modal = document.getElementById('modalEditSlot');
        if (modal) {
            modal.classList.add('show');
            document.body.style.overflow = 'hidden';
            const input = document.getElementById('inputSlotKantin');
            if (input) input.focus();
        }
    }

    function closeModalEditSlot() {
        const modal = document.getElementById('modalEditSlot');
        if (modal) {
            modal.classList.remove('show');
            document.body.style.overflow = '';
        }
    }

    function adjustSlotInput(amount) {
        const input = document.getElementById('inputSlotKantin');
        if (input) {
            let val = parseInt(input.value) || 0;
            val = Math.max(1, val + amount);
            input.value = val;
        }
    }

    function simpanSlotKantin() {
        const input = document.getElementById('inputSlotKantin');
        if (!input) return;

        const val = parseInt(input.value) || 0;
        const btnSubmit = document.querySelector('#modalEditSlot .modal-btn-submit');
        const originalText = btnSubmit.innerHTML;

        btnSubmit.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Menyimpan...';
        btnSubmit.disabled = true;

        const formData = new FormData();
        formData.append('_ajax', '1');
        formData.append('action', 'kantin_ubah_slot_ajax');
        formData.append('nilai', val);

        fetch(window.location.href, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            btnSubmit.innerHTML = originalText;
            btnSubmit.disabled = false;

            if (data.status === 'success') {
                showToastAdmin('Sukses', data.msg, 'success');
                
                // Update stats cards in UI
                const statSlotKantinVal = document.getElementById('statSlotKantinVal');
                const statTotalKantinLimitVal = document.getElementById('statTotalKantinLimitVal');
                const inputEditSlot = document.getElementById('inputSlotKantin');

                if (statSlotKantinVal) statSlotKantinVal.textContent = val;
                if (statTotalKantinLimitVal) statTotalKantinLimitVal.textContent = val;
                if (inputEditSlot) inputEditSlot.value = val;

                closeModalEditSlot();

                // Reload page after brief delay to refresh empty slots grid correctly
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            } else {
                showToastAdmin('Gagal', data.msg, 'error');
            }
        })
        .catch(err => {
            btnSubmit.innerHTML = originalText;
            btnSubmit.disabled = false;
            showToastAdmin('Error', 'Gagal memproses request. Hubungi administrator.', 'error');
        });
    }

    function geserUrutanKantin(btn, arah) {
        const rowEl = btn.closest('tr');
        if (!rowEl) return;

        const nomor = parseInt(rowEl.getAttribute('data-nomor'));
        const swapNomor = (arah === 'up') ? (nomor - 1) : (nomor + 1);
        const targetRowEl = document.querySelector('tr[data-nomor="' + swapNomor + '"]');

        const originalContent = btn.innerHTML;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';
        btn.disabled = true;

        const formData = new FormData();
        formData.append('_ajax', '1');
        formData.append('action', 'kantin_geser_urutan_ajax');
        formData.append('nomor_slot', nomor);
        formData.append('arah', arah);

        fetch(window.location.href, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            btn.innerHTML = originalContent;
            btn.disabled = false;

            if (data.status === 'success') {
                showToastAdmin('Sukses', data.msg, 'success');
                
                if (targetRowEl) {
                    // Highlight rows
                    rowEl.classList.add('row-swap-active');
                    targetRowEl.classList.add('row-swap-active');

                    setTimeout(() => {
                        rowEl.classList.remove('row-swap-active');
                        targetRowEl.classList.remove('row-swap-active');
                    }, 1000);

                    // Swap data-nomor attributes
                    rowEl.setAttribute('data-nomor', swapNomor);
                    targetRowEl.setAttribute('data-nomor', nomor);

                    // Swap the actual numbers shown in the cells
                    const numSpan = rowEl.querySelector('.slot-number-span');
                    const targetNumSpan = targetRowEl.querySelector('.slot-number-span');
                    if (numSpan && targetNumSpan) {
                        numSpan.textContent = swapNomor;
                        targetNumSpan.textContent = nomor;
                    }

                    // Swap DOM nodes
                    if (arah === 'up') {
                        rowEl.parentNode.insertBefore(rowEl, targetRowEl);
                    } else {
                        rowEl.parentNode.insertBefore(targetRowEl, rowEl);
                    }

                    // Update Chevrons visibility
                    updateSwapArrows();
                } else {
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                }
            } else {
                showToastAdmin('Gagal', data.msg, 'error');
            }
        })
        .catch(err => {
            btn.innerHTML = originalContent;
            btn.disabled = false;
            showToastAdmin('Error', 'Gagal memproses request. Hubungi administrator.', 'error');
        });
    }

    function updateSwapArrows() {
        const rows = document.querySelectorAll('tbody tr[data-nomor]');
        const limitEl = document.getElementById('statSlotKantinVal');
        if (!limitEl) return;
        const limit = parseInt(limitEl.textContent);

        rows.forEach(row => {
            const nomor = parseInt(row.getAttribute('data-nomor'));
            const btnUp = row.querySelector('.btn-swap-up');
            const btnDown = row.querySelector('.btn-swap-down');

            if (btnUp) {
                btnUp.style.visibility = (nomor > 1) ? 'visible' : 'hidden';
            }
            if (btnDown) {
                btnDown.style.visibility = (nomor < limit) ? 'visible' : 'hidden';
            }
        });
    }
</script>