<?php // sections/kantin.php
require_once __DIR__ . '/../../../config/toko_foto.php';
?>

<div class="stats-grid col2">
    <div class="stat-card">
        <div class="stat-label">Total Kantin</div>
        <div class="stat-row">
            <div class="stat-value">
                <?= $totalToko ?>
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
</div>

<div id="panelDaftarKantin" style="width: 100%;">
    <div class="table-card" style="width: 100%;">
        <div class="table-card-header">
            <h2>Daftar Kantin</h2>
        </div>
        <div class="table-scroll">
            <table>
                <thead>
                    <tr>
                        <th>Nama Kantin / Stand</th>
                        <th class="col-hide">Deskripsi</th>
                        <th class="center">Penjual</th>
                        <th class="center">Menu</th>
                        <th>Status</th>
                        <th class="center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($tokos)): ?>
                        <tr class="empty-row">
                            <td colspan="6">
                                <i class="fa-solid fa-store"
                                    style="color:var(--green-muted);font-size:22px;display:block;margin-bottom:8px"></i>
                                Belum ada kantin
                            </td>
                        </tr>
                    <?php else:
                        foreach ($tokos as $t):
                            $ownerNama = $t['nama_owner'] ?? 'Belum ada owner';
                            ?>
                            <tr class="toko-row <?= $selectedToko == $t['id_toko'] ? 'toko-row-active' : '' ?>"
                                onclick="selectToko(<?= $t['id_toko'] ?>)">
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
                                    <form method="POST" action="?section=kantin" style="display:inline"
                                        id="form-hapus-<?= $t['id_toko'] ?>">
                                        <input type="hidden" name="action" value="kantin_hapus">
                                        <input type="hidden" name="id_toko" value="<?= $t['id_toko'] ?>">
                                        <input type="hidden" name="_section" value="kantin">
                                        <button type="button" class="btn-aksi danger" title="Hapus"
                                            onclick="event.stopPropagation(); if(confirm('Hapus toko <?= htmlspecialchars($t['nama_toko']) ?>?')) document.getElementById('form-hapus-<?= $t['id_toko'] ?>').submit()">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
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
                <form method="POST" enctype="multipart/form-data" action="?section=kantin&toko=<?= (int) $selectedToko ?>"
                    style="display:flex; flex-direction:column; gap:14px; margin-top:12px;">
                    <input type="hidden" name="action" value="kantin_edit">
                    <input type="hidden" name="_section" value="kantin">
                    <input type="hidden" name="_selected_toko" value="<?= (int) $selectedToko ?>">
                    <input type="hidden" name="id_toko" value="<?= (int) $selectedToko ?>">

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
                                    <td style="font-weight:600;">Rp
                                        <?= number_format($m['harga'], 0, ',', '.') ?>
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
        if (e.key === 'Escape') tutupFotoKantin();
    });

    function selectToko(id) {
        sessionStorage.setItem('adminScrollPos', window.scrollY);
        window.location.href = '?section=kantin&toko=' + id;
    }

    function tutupDetailToko() {
        window.location.href = '?section=kantin';
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
</script>