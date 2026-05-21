<?php // sections/penjual.php ?>

<div class="stats-grid col2">
    <div class="stat-card">
        <div class="stat-label">Total Penjual</div>
        <div class="stat-row">
            <div class="stat-value"><?= $totalPenjual ?></div>
            <i class="fa-solid fa-user-tie stat-icon"></i>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Penjual Aktif</div>
        <div class="stat-row">
            <div class="stat-value"><?= $penjualAktif ?><span class="sub"> / <?= $totalPenjual ?></span></div>
            <i class="fa-solid fa-circle-check stat-icon"></i>
        </div>
    </div>
</div>

<div class="page-grid">
    <!-- Tabel Daftar Penjual -->
    <div class="table-card">
        <div class="table-card-header">
            <h2>Daftar Penjual</h2>
        </div>
        <div class="table-scroll">
            <table>
                <thead>
                    <tr>
                        <th>Nama Penjual</th>
                        <th class="col-hide">Username</th>
                        <th class="col-hide">Kantin</th>
                        <th>Status</th>
                        <th class="center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($penjuals)): ?>
                        <tr class="empty-row">
                            <td colspan="5">
                                <i class="fa-solid fa-user-tie"
                                    style="color:var(--green-muted);font-size:22px;display:block;margin-bottom:8px"></i>
                                Belum ada penjual
                            </td>
                        </tr>
                    <?php else:
                        foreach ($penjuals as $p): ?>
                            <tr class="toko-row <?= $selectedPenjual == $p['id_penjual'] ? 'toko-row-active' : '' ?>"
                                onclick="selectPenjual(<?= $p['id_penjual'] ?>)">
                                <td>
                                    <div class="toko-name-cell">
                                        <?php if (!empty($p['foto_profil'])): ?>
                                            <img src="../../assets/img/penjual/<?= htmlspecialchars($p['foto_profil']) ?>"
                                                class="toko-thumb" style="border-radius:50%">
                                        <?php else: ?>
                                            <div class="toko-thumb-placeholder" style="border-radius:50%">
                                                <i class="fa-solid fa-user"></i>
                                            </div>
                                        <?php endif; ?>
                                        <span><?= htmlspecialchars($p['nama']) ?></span>
                                    </div>
                                </td>
                                <td class="col-hide" style="color:var(--text-muted);font-size:12px">
                                    <?= htmlspecialchars($p['username'] ?: '-') ?>
                                </td>
                                <td class="col-hide" style="font-size:12px">
                                    <?= htmlspecialchars($p['kantin_dikelola'] ?: '-') ?>
                                </td>
                                <td>
                                    <span class="badge <?= $p['status'] === 'aktif' ? 'badge-aktif' : 'badge-nonaktif' ?>">
                                        <i
                                            class="fa-solid <?= $p['status'] === 'aktif' ? 'fa-circle-check' : 'fa-circle-xmark' ?>"></i>
                                        <?= ucfirst($p['status']) ?>
                                    </span>
                                </td>
                                <td class="center" style="white-space:nowrap">
                                    <button class="btn-aksi reset"
                                        onclick="event.stopPropagation();selectPenjual(<?= $p['id_penjual'] ?>)" title="Edit">
                                        <i class="fa-solid fa-pen"></i>
                                    </button>
                                    <!-- Toggle status -->
                                    <form method="POST" action="?section=penjual" style="display:inline"
                                        onsubmit="event.stopPropagation()">
                                        <input type="hidden" name="action" value="penjual_toggle">
                                        <input type="hidden" name="id_penjual" value="<?= $p['id_penjual'] ?>">
                                        <input type="hidden" name="status" value="<?= $p['status'] ?>">
                                        <input type="hidden" name="_section" value="penjual">
                                        <button type="submit"
                                            class="btn-aksi <?= $p['status'] === 'aktif' ? 'toggle-off' : 'toggle-on' ?>"
                                            title="<?= $p['status'] === 'aktif' ? 'Nonaktifkan' : 'Aktifkan' ?>"
                                            onclick="event.stopPropagation()">
                                            <i
                                                class="fa-solid <?= $p['status'] === 'aktif' ? 'fa-user-slash' : 'fa-user-check' ?>"></i>
                                        </button>
                                    </form>
                                    <!-- Hapus -->
                                    <form method="POST" action="?section=penjual" style="display:inline">
                                        <input type="hidden" name="action" value="penjual_hapus">
                                        <input type="hidden" name="id_penjual" value="<?= $p['id_penjual'] ?>">
                                        <input type="hidden" name="_section" value="penjual">
                                        <button type="button" class="btn-aksi danger" title="Hapus"
                                            onclick="event.stopPropagation();if(confirm('Hapus penjual <?= htmlspecialchars($p['nama']) ?>?'))this.closest('form').submit()">
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

    <!-- Form Tambah Penjual -->
    <div class="form-card">
        <h2><i class="fa-solid fa-user-plus" style="color:var(--green);margin-right:8px"></i>Tambah Penjual Baru</h2>
        <form method="POST" action="?section=penjual">
            <input type="hidden" name="action" value="penjual_tambah">
            <input type="hidden" name="_section" value="penjual">
            <div class="form-group">
                <label>Nama Penjual</label>
                <input type="text" name="nama" placeholder="cth. Bu Sari" required autocomplete="off">
            </div>
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" placeholder="cth. busari" required autocomplete="off">
            </div>
            <div class="form-group">
                <label>Password</label>
                <div class="password-wrap">
                    <input type="password" name="password" id="inputPassPenjual" placeholder="Minimal 6 karakter"
                        required>
                    <button type="button" class="btn-eye" onclick="togglePw('inputPassPenjual','eyePassPenjual')">
                        <i class="fa-solid fa-eye" id="eyePassPenjual"></i>
                    </button>
                </div>
            </div>
            <div class="form-group">
                <label>Assign ke Kantin <span style="color:var(--text-light);font-weight:400">(opsional)</span></label>
                <select name="id_toko" class="form-select">
                    <option value="">Pilih kantin...</option>
                    <?php foreach ($semuaToko as $t): ?>
                        <option value="<?= $t['id_toko'] ?>"><?= htmlspecialchars($t['nama_toko']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="btn-submit">
                <i class="fa-solid fa-floppy-disk" style="margin-right:6px"></i>Simpan Penjual
            </button>
        </form>
    </div>
</div>

<!-- Detail Penjual -->
<?php if ($selectedPenjual && $detailPenjual): ?>
    <div class="detail-toko-section" id="detailPenjualSection" style="margin-top:20px">

        <div class="detail-toko-header">
            <div class="detail-toko-header-info">
                <div class="detail-toko-avatar">
                    <?php if (!empty($detailPenjual['foto_profil'])): ?>
                        <img src="../../assets/img/penjual/<?= htmlspecialchars($detailPenjual['foto_profil']) ?>?v=<?= time() ?>"
                            style="border-radius:50%;width:100%;height:100%;object-fit:cover">
                    <?php else: ?>
                        <i class="fa-solid fa-user"></i>
                    <?php endif; ?>
                </div>
                <div>
                    <div class="detail-toko-nama"><?= htmlspecialchars($detailPenjual['nama']) ?></div>
                    <div class="detail-toko-desk">@<?= htmlspecialchars($detailPenjual['username'] ?: '-') ?></div>
                </div>
            </div>
            <button onclick="tutupDetailPenjual()" class="btn-aksi toggle-off" title="Tutup">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <div class="detail-toko-grid">

            <!-- Form Edit Penjual -->
            <div class="form-card">
                <h2><i class="fa-solid fa-pen" style="color:var(--green);margin-right:8px"></i>Edit Penjual</h2>
                <form method="POST" action="?section=penjual&penjual=<?= $detailPenjual['id_penjual'] ?>"
                    enctype="multipart/form-data">
                    <input type="hidden" name="action" value="penjual_edit">
                    <input type="hidden" name="id_penjual" value="<?= $detailPenjual['id_penjual'] ?>">
                    <input type="hidden" name="_section" value="penjual">
                    <input type="hidden" name="_selected_penjual" value="<?= $detailPenjual['id_penjual'] ?>">
                    <div class="form-group">
                        <label>Nama Penjual</label>
                        <input type="text" name="nama" value="<?= htmlspecialchars($detailPenjual['nama']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Username</label>
                        <input type="text" name="username"
                            value="<?= htmlspecialchars($detailPenjual['username'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>Password Baru <span style="color:var(--text-light);font-weight:400">(kosongkan jika tidak
                                diubah)</span></label>
                        <div class="password-wrap">
                            <input type="password" name="password_baru" id="inputPassEditPenjual"
                                placeholder="Isi untuk ganti password">
                            <button type="button" class="btn-eye"
                                onclick="togglePw('inputPassEditPenjual','eyePassEditPenjual')">
                                <i class="fa-solid fa-eye" id="eyePassEditPenjual"></i>
                            </button>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Foto Profil</label>
                        <?php if (!empty($detailPenjual['foto_profil'])): ?>
                            <div style="display:flex;align-items:center;gap:10px;margin-bottom:8px">
                                <img src="../../assets/img/penjual/<?= htmlspecialchars($detailPenjual['foto_profil']) ?>?v=<?= time() ?>"
                                    style="width:40px;height:40px;border-radius:50%;object-fit:cover">
                            </div>
                        <?php endif; ?>
                        <input type="file" name="foto_profil" accept="image/*">
                    </div>
                    <button type="submit" class="btn-submit">
                        <i class="fa-solid fa-floppy-disk" style="margin-right:6px"></i>Simpan Perubahan
                    </button>
                </form>
            </div>

            <!-- Kantin yang dikelola -->
            <div class="form-card">
                <h2><i class="fa-solid fa-store" style="color:var(--green);margin-right:8px"></i>Kantin Dikelola</h2>

                <?php if (empty($kantinPenjual)): ?>
                    <div class="empty-penjual">Penjual ini belum assigned ke kantin manapun</div>
                <?php else:
                    foreach ($kantinPenjual as $k): ?>
                        <div class="penjual-item">
                            <div>
                                <div class="penjual-nama"><?= htmlspecialchars($k['nama_toko']) ?></div>
                                <div class="penjual-shift">
                                    <?= $k['shift'] ? 'Shift: ' . ucfirst($k['shift']) : 'Shift tidak ditentukan' ?>
                                </div>
                            </div>
                            <form method="POST" action="?section=penjual&penjual=<?= $detailPenjual['id_penjual'] ?>"
                                style="display:inline" onsubmit="return confirm('Lepas dari kantin ini?')">
                                <input type="hidden" name="action" value="kantin_lepas_penjual">
                                <input type="hidden" name="id_tp" value="<?= $k['id'] ?>">
                                <input type="hidden" name="_section" value="penjual">
                                <input type="hidden" name="_selected_penjual" value="<?= $detailPenjual['id_penjual'] ?>">
                                <button type="submit" class="btn-aksi danger" title="Lepas dari kantin">
                                    <i class="fa-solid fa-store-slash"></i>
                                </button>
                            </form>
                        </div>
                    <?php endforeach; endif; ?>

                <div class="assign-penjual-wrap">
                    <div class="form-group-label">Assign ke Kantin</div>
                    <form method="POST" action="?section=penjual&penjual=<?= $detailPenjual['id_penjual'] ?>">
                        <input type="hidden" name="action" value="kantin_assign_penjual">
                        <input type="hidden" name="id_penjual" value="<?= $detailPenjual['id_penjual'] ?>">
                        <input type="hidden" name="_section" value="penjual">
                        <input type="hidden" name="_selected_penjual" value="<?= $detailPenjual['id_penjual'] ?>">
                        <div class="form-group">
                            <select name="id_toko" class="form-select">
                                <option value="">Pilih kantin...</option>
                                <?php foreach ($semuaToko as $t): ?>
                                    <option value="<?= $t['id_toko'] ?>"><?= htmlspecialchars($t['nama_toko']) ?></option>
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
                            <i class="fa-solid fa-store" style="margin-right:6px"></i>Assign ke Kantin
                        </button>
                    </form>
                </div>
            </div>

        </div>
    </div>
<?php endif; ?>

<script>
    function selectPenjual(id) {
        sessionStorage.setItem('adminScrollPos', window.scrollY);
        window.location.href = '?section=penjual&penjual=' + id;
    }

    function tutupDetailPenjual() {
        window.location.href = '?section=penjual';
    }

    // Scroll ke detail kalau ada penjual dipilih
    const adminScrollPos = sessionStorage.getItem('adminScrollPos');
    if (adminScrollPos) {
        sessionStorage.removeItem('adminScrollPos');
        setTimeout(() => {
            window.scrollTo({ top: parseInt(adminScrollPos), behavior: 'instant' });
        }, 300);
    }
</script>