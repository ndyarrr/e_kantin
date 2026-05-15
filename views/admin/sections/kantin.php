<?php // sections/kantin.php ?>

<div class="stats-grid col2">
    <div class="stat-card">
        <div class="stat-label">Total Kantin</div>
        <div class="stat-row">
            <div class="stat-value"><?= $totalToko ?></div>
            <i class="fa-solid fa-store stat-icon"></i>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Kantin Buka Hari Ini</div>
        <div class="stat-row">
            <div class="stat-value"><?= $tokoAktif ?><span class="sub"> / <?= $totalToko ?></span></div>
            <i class="fa-solid fa-circle-check stat-icon"></i>
        </div>
    </div>
</div>

<div class="page-grid">
    <div class="table-card">
        <div class="table-card-header">
            <h2>Daftar Kantin</h2>
        </div>
        <div class="table-scroll">
            <table>
                <thead>
                    <tr>
                        <th>Nama Toko</th>
                        <th class="col-hide">Deskripsi</th>
                        <th class="center">Menu</th>
                        <th class="center">Penjual</th>
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
                        foreach ($tokos as $t): ?>
                            <tr class="toko-row <?= $selectedToko == $t['id_toko'] ? 'toko-row-active' : '' ?>"
                                onclick="selectToko(<?= $t['id_toko'] ?>)">
                                <td>
                                    <div class="toko-name-cell">
                                        <?php if (!empty($t['foto_toko'])): ?>
                                            <img src="../../assets/img/kantin/<?= htmlspecialchars($t['foto_toko']) ?>"
                                                class="toko-thumb" onclick="event.stopPropagation();bukaFotoKantin(this.src)"
                                                style="cursor:zoom-in">
                                        <?php else: ?>
                                            <div class="toko-thumb-placeholder">
                                                <i class="fa-solid fa-store"></i>
                                            </div>
                                        <?php endif; ?>
                                        <span><?= htmlspecialchars($t['nama_toko']) ?></span>
                                    </div>
                                </td>
                                <td class="col-hide toko-desc"><?= htmlspecialchars($t['deskripsi'] ?? '-') ?></td>
                                <td class="center"><?= $t['total_menu'] ?></td>
                                <td class="center"><?= $t['total_penjual'] ?></td>
                                <td>
                                    <span class="badge <?= $t['status'] === 'buka' ? 'badge-aktif' : 'badge-nonaktif' ?>">
                                        <i
                                            class="fa-solid <?= $t['status'] === 'buka' ? 'fa-circle-check' : 'fa-circle-xmark' ?>"></i>
                                        <?= ucfirst($t['status']) ?>
                                    </span>
                                </td>
                                <td class="center" style="white-space:nowrap">

                                    <form method="POST" style="display:inline"
                                        onsubmit="event.stopPropagation();return confirm('Hapus toko <?= htmlspecialchars($t['nama_toko']) ?>?')">
                                        <input type="hidden" name="action" value="kantin_hapus">
                                        <input type="hidden" name="id_toko" value="<?= $t['id_toko'] ?>">
                                        <input type="hidden" name="_section" value="kantin">
                                        <button type="submit" class="btn-aksi danger" title="Hapus">
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

    <div class="form-card">
        <h2><i class="fa-solid fa-store" style="color:var(--green);margin-right:8px"></i>Tambah Kantin Baru</h2>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="kantin_tambah">
            <input type="hidden" name="_section" value="kantin">
            <div class="form-group">
                <label>Nama Kantin</label>
                <input type="text" name="nama_toko" placeholder="cth. Warung Bu Sari" required autocomplete="off">
            </div>
            <div class="form-group">
                <label>Deskripsi</label>
                <input type="text" name="deskripsi" placeholder="cth. Nasi, lauk, dan minuman">
            </div>
            <div class="form-group">
                <label>Foto Kantin</label>
                <input type="file" name="foto_toko" accept="image/*">
                <div class="form-note">Opsional.</div>
            </div>
            <button type="submit" class="btn-submit">
                <i class="fa-solid fa-floppy-disk" style="margin-right:6px"></i>Simpan Kantin
            </button>
        </form>
    </div>
</div>

<?php if ($selectedToko && $detailToko): ?>
    <div class="detail-toko-section" id="detailTokoSection">

        <div class="detail-toko-header">
            <div class="detail-toko-header-info">
                <div class="detail-toko-avatar">
                    <?php if (!empty($detailToko['foto_toko'])): ?>
                        <img src="../../assets/img/kantin/<?= htmlspecialchars($detailToko['foto_toko']) ?>?v=<?= time() ?>">
                    <?php else: ?>
                        <i class="fa-solid fa-store"></i>
                    <?php endif; ?>
                </div>
                <div>
                    <div class="detail-toko-nama"><?= htmlspecialchars($detailToko['nama_toko']) ?></div>
                    <div class="detail-toko-desk"><?= htmlspecialchars($detailToko['deskripsi'] ?? '-') ?></div>
                </div>
            </div>
            <button onclick="tutupDetailToko()" class="btn-aksi toggle-off" title="Tutup">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <div class="detail-toko-grid">

            <div class="form-card">
                <h2><i class="fa-solid fa-pen" style="color:var(--green);margin-right:8px"></i>Edit Kantin</h2>
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="kantin_edit">
                    <input type="hidden" name="id_toko" value="<?= $detailToko['id_toko'] ?>">
                    <input type="hidden" name="_section" value="kantin">
                    <input type="hidden" name="_selected_toko" value="<?= $detailToko['id_toko'] ?>">
                    <div class="form-group">
                        <label>Nama Kantin</label>
                        <input type="text" name="nama_toko" value="<?= htmlspecialchars($detailToko['nama_toko']) ?>"
                            required>
                    </div>
                    <div class="form-group">
                        <label>Deskripsi</label>
                        <input type="text" name="deskripsi" value="<?= htmlspecialchars($detailToko['deskripsi'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>Foto Kantin</label>
                        <?php if (!empty($detailToko['foto_toko'])): ?>
                            <div class="foto-preview-wrap">
                                <img src="../../assets/img/kantin/<?= htmlspecialchars($detailToko['foto_toko']) ?>?v=<?= time() ?>"
                                    class="foto-preview-thumb" onclick="bukaFotoKantin(this.src)">
                                <label class="hapus-foto-label">
                                    <input type="checkbox" name="hapus_foto" value="1" style="accent-color:var(--red)">
                                    Hapus foto
                                </label>
                            </div>
                        <?php endif; ?>
                        <input type="file" name="foto_toko" accept="image/*">
                    </div>
                    <button type="submit" class="btn-submit">
                        <i class="fa-solid fa-floppy-disk" style="margin-right:6px"></i>Simpan Perubahan
                    </button>
                </form>
            </div>

            <div class="form-card">
                <h2><i class="fa-solid fa-user-tag" style="color:var(--green);margin-right:8px"></i>Penjual Assigned</h2>

                <?php if (empty($penjualToko)): ?>
                    <div class="empty-penjual">Belum ada penjual di kantin ini</div>
                <?php else:
                    foreach ($penjualToko as $p): ?>
                        <div class="penjual-item">
                            <div>
                                <div class="penjual-nama"><?= htmlspecialchars($p['nama']) ?></div>
                                <div class="penjual-shift"><?= $p['shift'] ? ucfirst($p['shift']) : 'Shift tidak ditentukan' ?>
                                </div>
                            </div>
                            <form method="POST" style="display:inline" onsubmit="return confirm('Lepas penjual ini?')">
                                <input type="hidden" name="action" value="kantin_lepas_penjual">
                                <input type="hidden" name="id_tp" value="<?= $p['id_tp'] ?>">
                                <input type="hidden" name="_section" value="kantin">
                                <input type="hidden" name="_selected_toko" value="<?= $detailToko['id_toko'] ?>">
                                <button type="submit" class="btn-aksi danger" title="Lepas penjual">
                                    <i class="fa-solid fa-user-minus"></i>
                                </button>
                            </form>
                        </div>
                    <?php endforeach; endif; ?>

                <div class="assign-penjual-wrap">
                    <div class="form-group-label">Assign Penjual Baru</div>
                    <form method="POST">
                        <input type="hidden" name="action" value="kantin_assign_penjual">
                        <input type="hidden" name="id_toko" value="<?= $detailToko['id_toko'] ?>">
                        <input type="hidden" name="_section" value="kantin">
                        <input type="hidden" name="_selected_toko" value="<?= $detailToko['id_toko'] ?>">
                        <div class="form-group">
                            <select name="id_penjual" class="form-select">
                                <option value="">Pilih penjual...</option>
                                <?php foreach ($semuaPenjual as $pj): ?>
                                    <option value="<?= $pj['id_penjual'] ?>"><?= htmlspecialchars($pj['nama']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <select name="shift" class="form-select">
                                <option value="">Shift (opsional)</option>
                                <option value="pagi">Pagi (07.00–09.30)</option>
                                <option value="istirahat">Istirahat (09.30–12.00)</option>
                                <option value="siang">Siang (12.00–15.00)</option>
                            </select>
                        </div>
                        <button type="submit" class="btn-submit">
                            <i class="fa-solid fa-user-plus" style="margin-right:6px"></i>Assign Penjual
                        </button>
                    </form>
                </div>
            </div>

        </div>

        <!-- ══ Tabel Menu + Toggle Edit Mode ══ -->
        <div class="table-card" style="margin-top:16px">
            <div class="table-card-header" id="menuCardHeader">
                <h2>Menu Kantin</h2>
                <button id="btnModeEditMenu" onclick="toggleModeEditMenu()" class="btn-tambah-menu">
                    <i class="fa-solid fa-lock" id="ikonModeEdit"></i> Mode Edit
                </button>
            </div>

            <!-- Form tambah menu — tersembunyi by default -->
            <div id="formTambahMenu" class="form-tambah-menu" style="display:none">
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="menu_tambah">
                    <input type="hidden" name="id_toko" value="<?= $detailToko['id_toko'] ?>">
                    <input type="hidden" name="_section" value="kantin">
                    <input type="hidden" name="_selected_toko" value="<?= $detailToko['id_toko'] ?>">
                    <input type="hidden" name="_menu_edit_mode" value="1">
                    <div class="form-tambah-menu-grid">
                        <div class="form-group">
                            <label>Nama Menu</label>
                            <input type="text" name="nama_menu" placeholder="cth. Nasi Ayam" required>
                        </div>
                        <div class="form-group">
                            <label>Harga (Rp)</label>
                            <input type="number" name="harga" placeholder="cth. 12000" required min="0">
                        </div>
                        <div class="form-group">
                            <label>Stok</label>
                            <input type="number" name="stok" placeholder="cth. 30" required min="0">
                        </div>
                        <div class="form-group">
                            <label>Foto Menu</label>
                            <input type="file" name="foto_menu" accept="image/*">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Deskripsi</label>
                        <input type="text" name="deskripsi" placeholder="Opsional">
                    </div>
                    <div class="form-tambah-menu-actions">
                        <button type="submit" class="btn-submit" style="width:auto;padding:10px 20px">
                            <i class="fa-solid fa-floppy-disk" style="margin-right:6px"></i>Simpan
                        </button>
                        <button type="button" onclick="document.getElementById('formTambahMenu').style.display='none'"
                            class="btn-batal">Batal</button>
                    </div>
                </form>
            </div>

            <div class="table-scroll">
                <table>
                    <thead>
                        <tr>
                            <th>Nama Menu</th>
                            <th class="col-hide">Deskripsi</th>
                            <th>Harga</th>
                            <th class="center">Stok</th>
                            <th class="center">Tersedia</th>
                            <th class="center col-aksi-menu" style="display:none">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($menuToko)): ?>
                            <tr class="empty-row">
                                <td colspan="5" id="emptyMenuColspan">Belum ada menu</td>
                            </tr>
                        <?php else:
                            foreach ($menuToko as $m): ?>
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
                                    <td class="col-hide toko-desc"><?= htmlspecialchars($m['deskripsi'] ?? '-') ?></td>
                                    <td>Rp <?= number_format($m['harga'], 0, ',', '.') ?></td>
                                    <td class="center"><?= $m['stok'] ?></td>
                                    <td class="center">
                                        <span class="badge <?= $m['tersedia'] ? 'badge-aktif' : 'badge-nonaktif' ?>">
                                            <?= $m['tersedia'] ? 'Ya' : 'Tidak' ?>
                                        </span>
                                    </td>
                                    <!-- Kolom aksi hapus, tersembunyi by default -->
                                    <td class="center col-aksi-menu" style="display:none">
                                        <form method="POST" style="display:inline" onsubmit="return confirm('Hapus menu ini?')">
                                            <input type="hidden" name="action" value="menu_hapus">
                                            <input type="hidden" name="id_menu" value="<?= $m['id_menu'] ?>">
                                            <input type="hidden" name="_section" value="kantin">
                                            <input type="hidden" name="_selected_toko" value="<?= $detailToko['id_toko'] ?>">
                                            <input type="hidden" name="_menu_edit_mode" value="1">
                                            <button type="submit" class="btn-aksi danger" title="Hapus menu">
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

<?php endif; ?>
<!-- Modal foto kantin -->
<div id="modalFotoKantin" onclick="tutupFotoKantin()"
    style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.8);z-index:9999;align-items:center;justify-content:center;cursor:zoom-out">
    <img id="modalFotoImg" src="" style="max-width:90vw;max-height:90vh;border-radius:12px;object-fit:contain">
</div>
<script>
    let menuEditMode = false;

    function toggleModeEditMenu() {
        menuEditMode = !menuEditMode;

        const btn = document.getElementById('btnModeEditMenu');
        const ikon = document.getElementById('ikonModeEdit');
        const header = document.getElementById('menuCardHeader');
        const colAksi = document.querySelectorAll('.col-aksi-menu');
        const formTambah = document.getElementById('formTambahMenu');
        const btnTambahId = 'btnTambahMenuEdit';

        if (menuEditMode) {
            // Aktifkan mode edit
            btn.innerHTML = '<i class="fa-solid fa-lock-open"></i> Mode Edit: ON';
            btn.style.cssText = 'background:var(--red,#e74c3c);color:#fff;border-color:var(--red,#e74c3c)';
            colAksi.forEach(el => el.style.display = '');

            // Sisipkan tombol "Tambah Menu" sebelum tombol Mode Edit
            if (!document.getElementById(btnTambahId)) {
                const btnTambah = document.createElement('button');
                btnTambah.id = btnTambahId;
                btnTambah.className = 'btn-tambah-menu';
                btnTambah.innerHTML = '<i class="fa-solid fa-plus"></i> Tambah Menu';
                btnTambah.onclick = () => {
                    formTambah.style.display = formTambah.style.display === 'none' ? 'block' : 'none';
                };
                header.insertBefore(btnTambah, btn);
            }
        } else {
            // Nonaktifkan mode edit
            btn.innerHTML = '<i class="fa-solid fa-lock"></i> Mode Edit';
            btn.style.cssText = '';
            colAksi.forEach(el => el.style.display = 'none');
            formTambah.style.display = 'none';

            // Hapus tombol tambah menu
            const btnTambah = document.getElementById(btnTambahId);
            if (btnTambah) btnTambah.remove();
        }
    }

    function bukaFotoKantin(src) {
        const modal = document.getElementById('modalFotoKantin');
        document.getElementById('modalFotoImg').src = src;
        modal.style.display = 'flex';
    }

    function tutupFotoKantin() {
        document.getElementById('modalFotoKantin').style.display = 'none';
    }

    // Tutup dengan ESC
    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') tutupFotoKantin();
    });

    // HAPUS semua ini
    function selectToko(id) {
        sessionStorage.setItem('adminScrollPos', window.scrollY);
        window.location.href = '?section=kantin&toko=' + id;
    }

    function tutupDetailToko() {
        window.location.href = '?section=kantin';
    }


</script>